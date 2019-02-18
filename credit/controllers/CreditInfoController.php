<?php

namespace credit\controllers;

use common\api\RedisQueue;
use common\base\LogChannel;
use common\exceptions\CodeException;
use common\exceptions\UserExceptionExt;
use common\helpers\GlobalHelper;
use common\helpers\Lock;
use common\helpers\Util;
use common\models\CardInfo;
use common\models\Company;
use common\models\CreditJxl;
use common\models\CreditJxlQueue;
use common\models\CreditJxlRawData;
use common\models\CreditMxBill;
use common\models\CreditYysQueue;
use common\models\ErrorMessage;
use common\models\IcekreditAlipay;
use common\models\IcekreditAlipayData;
use common\models\LoanPerson;
use common\models\LoanPersonInfo;
use common\models\MessageAppLog;
use common\models\MoxieCreditTask;
use common\models\UserAlipayInfo;
use common\models\UserCaptcha;
use common\models\UserContact;
use common\models\UserCreditDetail;
use common\models\UserFeedback;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserQuotaPersonInfo;
use common\models\UserVerification;
use common\services\credit_line\CreditLineService;
use common\services\JsqbService;
use common\services\JxlService;
use common\services\UserService;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * User controller
 */
class CreditInfoController extends BaseController
{

    protected $userService;

    public $bucket;
    public $ossService;

    const JXL_YYS = 1; //聚信立运营商
    const HLJR_YYS = 2; //葫芦金融运营商

    public function init()
    {
        parent::init();

        require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';

        $this->ossService = new \ALIOSS();
        $this->ossService->set_debug_mode(true);
        $this->bucket = DEFAULT_OSS_BUCKET;
    }

    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // 除了下面的action其他都需要登录
                'except' => [ // 'leaving-message', 'up-load-contents',
                    'user-offer-reward', # 用户提交申请
                    'captcha',
                    'login-mjp-jjp', # 拒就赔
                    'send-phone-captcha', # 发送短信验证码
                    'spring-festival',
                    'get-free-coupon',
                    'get-christmas-coupon',
                    'upload-app-exception',
                    'ice-kredit-alipay-callback',
                    'get-my-bill-status',
                    'search-my-bill',
                    'moxie-credit-task',
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'testLimit' => 1,
                'height' => 35,
                'width' => 80,
                'padding' => 0,
                'minLength' => 4,
                'maxLength' => 4,
                'foreColor' => 0x444444,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * 获取用户卡列表
     *
     * @name    获取用户卡列表 [infoGetCardList]
     * @uses    获取用户卡列表
     * @method  post
     * @param   string $type 借款类型：1、零钱包，2、房租宝，3、分期购
     * @param   string $credit_card 银行卡类型：1、信用卡
     * @author  honglifeng
     * @author2  yuxuejin
     */
    public function actionGetCardList()
    {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $credit_card = trim($this->request->post('credit_card', ''));
        if (isset($credit_card) && $credit_card == 1) {
            $bank_info = CardInfo::find()->where(['user_id' => $user_id, 'type' => CardInfo::TYPE_CREDIT_CARD])->all();
            $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
            $info = [];
            for ($i = 0; $i < count($bank_info); ++$i) {
                $info[$i]['card_id'] = $bank_info[$i]['id'];
                $info[$i]['url'] = $baseUrl . "/image/bank/bank_" . $bank_info[$i]['bank_id'] . ".png";
                $info[$i]['bank_info_name'] = $bank_info[$i]['bank_name'] . CardInfo::$type[$bank_info[$i]['type']];
                $info[$i]['bank_info_num'] = "****" . substr($bank_info[$i]['card_no'], -4);
                if ($bank_info[$i]['main_card'] == CardInfo::MAIN_CARD) {
                    $info[$i]['main_card'] = CardInfo::MAIN_CARD;
                } else {
                    $info[$i]['main_card'] = CardInfo::MAIN_CARD_NO;
                }
            }
            $title_info = "信用卡列表";
            $info_notice = "";
        } else {
            $type = trim($this->request->post('type', ''));

            if (empty($type) && !isset(UserLoanOrder::$loan_type[$type])) {
                return [
                    'code' => -1,
                    'message' => '参数丢失',
                    'data' => [
                        'item' => [],
                    ],
                ];
            }

            $bank_info = CardInfo::find()->where(['user_id' => $user_id, 'main_card' => CardInfo::MAIN_CARD])->all();
            $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
            $info = [];
            for ($i = 0; $i < count($bank_info); ++$i) {
                $info[$i]['card_id'] = $bank_info[$i]['id'];
                $info[$i]['url'] = $baseUrl . "/image/bank/bank_" . $bank_info[$i]['bank_id'] . ".png";
                $info[$i]['bank_info_name'] = $bank_info[$i]['bank_name'] . CardInfo::$type[$bank_info[$i]['type']];
                $info[$i]['bank_info_num'] = "****" . substr($bank_info[$i]['card_no'], -4);
                if ($bank_info[$i]['main_card'] == CardInfo::MAIN_CARD) {
                    $info[$i]['main_card'] = CardInfo::MAIN_CARD;
                } else {
                    $info[$i]['main_card'] = CardInfo::MAIN_CARD_NO;
                }
            }

            $title_info = "收款银行卡";
            $info_notice = "";
            switch ($type) {
                case UserLoanOrder::LOAN_TYPE_LQD:
                    $info_notice = "申请还款后,平台从此卡扣款";
                    break;
                case UserLoanOrder::LOAN_TYPR_FZD:
                    $info_notice = "每月还款当日凌晨自动从此卡扣款";
                    break;
                case UserLoanOrder::LOAN_TYPE_FQSC:
                    break;
                default:
                    break;
            }
        }
        return [
            'code' => 0,
            'message' => '获取成功',
            'data' => [
                'item' => [
                    'card_info' => $info,
                    'title_info' => $title_info,
                    'info_notice' => $info_notice,
                ],
            ],
        ];
    }

    /**
     * 上报用户位置信息
     *
     * @name    上报用户位置信息 [infoUploadLocation]
     * @uses    上报用户位置信息
     * @method  post
     * @param   string $longitude 经度
     * @param   string $latitude 纬度
     * @param   string $address  具体地址
     * @param   int    $time    上报时间戳
     */
    public function actionUploadLocation() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();

        if (! Lock::get(Lock::LOCK_USER_UPLOAD_LOC_PREFIX . $user_id, 30)) {
            return [
                'code' => -1,
                'message' => '请求频率过高',
            ];
        }

        $longitude = trim($this->request->post('longitude', ''));
        $latitude = trim($this->request->post('latitude', ''));
        $address = trim($this->request->post('address', ''));
        $time = trim($this->request->post('time', ''));

        if (empty($longitude) || empty($latitude) || empty($address) || empty($time)) {
            \yii::warning( sprintf('upload_loc_missing_param_404 %s, %s', $user_id, json_encode($this->request->post())),
                LogChannel::USER_UPLOAD );
            return [
                'code' => -1,
                'message' => '参数丢失',
                'data' => [
                    'item' => [],
                ],
            ];
        }

        $clientType = $this->request->get('clientType', '');
        $osVersion = $this->request->get('osVersion', '');
        $appVersion = $this->request->get('appVersion', '');
        $deviceName = $this->request->get('deviceName', '');
        $appMarket = $this->request->get('appMarket', '');
        $deviceId = $this->request->get('deviceId', '');

        $user_login_upload_log = new UserLoginUploadLog();
        $user_login_upload_log->user_id = $user_id;
        $user_login_upload_log->longitude = $longitude;
        $user_login_upload_log->latitude = $latitude;
        $user_login_upload_log->address = $address;
        $user_login_upload_log->time = $time;
        $user_login_upload_log->clientType = $clientType;
        $user_login_upload_log->osVersion = $osVersion;
        $user_login_upload_log->appVersion = $appVersion;
        $user_login_upload_log->deviceName = $deviceName;
        $user_login_upload_log->appMarket = $appMarket;
        $user_login_upload_log->deviceId = $deviceId;
        $user_login_upload_log->created_at = time();
        if (!$user_login_upload_log->save()) {
            \yii::warning( sprintf('upload_loc_save_failed %s, %s', $user_id, json_encode($this->request->post())),
                LogChannel::USER_UPLOAD );
            return [
                'code' => -1,
                'message' => '上传失败',
                'data' => [
                    'item' => [],
                ],
            ];
        }

        return [
            'code' => 0,
            'message' => '上传成功',
            'data' => [
                'item' => [],
            ],
        ];
    }

    /**
     * 保存个人信息
     * @name    保存个人信息 [quotaSavePersonInfo]
     * @uses    保存个人信息
     * @method  POST
     * @param   string address_distinct  地址区域
     * @param   string address 现居住地址
     * @param   integer live_time_type 居住时长1、半年以内，2、半年到一年，3、一年以上
     * @param   integer degrees 个人学历1、高中、中专及以下，2、本科或大专，3、硕士及以上
     * @param    integer marriage 婚姻状况1、已婚，2、未婚，3、其它
     * @author  honglifeng
     */
    public function actionSavePersonInfo() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $ret = $this->userService->getVerifyInfo($user_id);

        if (!$ret['real_verify_status']) {
            return UserExceptionExt::throwCodeAndMsgExt('请先实名认证或者保存身份证正反面和人脸照片');
        }

        $address_distinct = trim($this->request->post('address_distinct', ''));
        if (empty($address_distinct)) {
            return UserExceptionExt::throwCodeAndMsgExt('现居住地址区域不能为空');
        }

        $address = trim($this->request->post('address', ''));
        if (empty($address)) {
            return UserExceptionExt::throwCodeAndMsgExt('现居住地址不能为空');
        }

        $live_time_type = $this->request->post('live_time_type');
        if (!isset(UserQuotaPersonInfo::$live_time_type[$live_time_type])) {
            // return UserExceptionExt::throwCodeAndMsgExt('请选择居住时长');
        }

        $degrees = $this->request->post('degrees');
        if (!isset(UserQuotaPersonInfo::$degrees[$degrees])) {
            //return UserExceptionExt::throwCodeAndMsgExt('请选择学历');
        }

        $marriage = $this->request->post('marriage');
        if (!isset(UserQuotaPersonInfo::$marriage[$marriage])) {
            //return UserExceptionExt::throwCodeAndMsgExt('请选择婚姻状况');
        }
        $params = $this->request->post();
        $params['user_id'] = $user_id;
        $ret = UserQuotaPersonInfo::saveUserQuotaPersonInfo($params);
        if ($ret) {
            return [
                'code' => 0,
                'message' => '保存成功',
                'data' => [
                    'item' => [],
                ],
            ];
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
        }
    }

    /**
     * 上传短信/app/通讯录等信息
     * @name   上传短信 /app/通讯录等信息 [infoUpLoadContents]
     * @uses   上传各种信息
     * @method POST
     * @param  string $data 上传的数据json格式{{'user_id':148,'mobile':'13651896145:13600000000:021-12345678','name'=>'姓名'}，{}，...}
     * @param  int type  1短信，2app，3通讯录
     */
    public function actionUpLoadContents()
    {
        if (isset($_SERVER['HTTP_CONTENT_ENCODING']) && $_SERVER['HTTP_CONTENT_ENCODING'] == 'gzip') {  // decompress
            $encoding = $_SERVER['HTTP_CONTENT_ENCODING'];
            $rawBody = file_get_contents('php://input');
            $body = '';
            try {
                switch ($encoding) {
                    case 'gzip':
                        $body = gzdecode($rawBody);
                        break;
                    case 'deflate':
                        $body = gzinflate(substr($rawBody, 2, -4)) . PHP_EOL . PHP_EOL;
                        break;
                    case 'deflate-raw':
                        $body = gzinflate($rawBody);
                        break;
                }
            } catch (\Exception $e) {
                \yii::warning(sprintf('upload_contents_ex: %s, %s, %s', $encoding, $rawBody, $e), LogChannel::USER_UPLOAD);
                throw $e;
            }
            mb_parse_str($body, $_POST);
        }

        $data = $this->request->post('data');
        $type = $this->request->post('type');
        $params = $this->request->get();
        if (empty($data)) {
            \yii::warning(sprintf('uploadcontents_empty: %s', json_encode($_REQUEST)), LogChannel::USER_UPLOAD);
            return [
                'code' => -1,
                'message' => '上传数据为空',
                'data' => [
                    'item' => [],
                ],
            ];
        }
        $key = '';
        if ($type == 1) {
            $content = json_decode($data, true);
            $json_last_error = json_last_error();
            if ($json_last_error != 0 || empty($content)) {
                // 记录新版本json编码方式是否有效
                $app_version = $params['appVersion'] ?? "";
                if ($app_version >= '2.3.4') {
                    \yii::warning('uploadcontents_error_730:data:' . base64_encode($this->request->post('data')) . ' ,type:' . $type . 'get:' . json_encode($params), LogChannel::USER_UPLOAD);
                }

                try {
                    for ($i = 0; $i <= 31; $i++) {
                        $ascii_str = chr($i);
                        $data = str_replace($ascii_str, '', $data);
                    }
                    $data = str_replace('\0', '', $data);
                    $data = str_replace('\1', '', $data);
                    $data = str_replace('\3', '', $data);
                    $data = str_replace('\4', '', $data);
                    $data = json_decode($data, true);
                } catch (\Exception $e) {
                    $data = false;
                    \yii::warning('uploadcontents_error_720:data:' . base64_encode($this->request->post('data')) . ' ,type:' . $type . 'get:' . json_encode($params), LogChannel::USER_UPLOAD);
                }
            } else {
                $data = $content;
            }

            if ($data) {
                $data = json_encode(['data' => $data, 'params' => $params]);
                $key = RedisQueue::LIST_USER_MOBILE_MESSAGES_UPLOAD;
            }
            else {
                if(!empty($this->request->post('data')) && !is_null($this->request->post('data')) && $this->request->post('data') != 'null'){
                    \yii::warning('uploadcontents_error_721:json_error:'.json_last_error().',data:'. base64_encode($this->request->post('data')) . ' ,type:'.$type . 'get:'.json_encode($params), LogChannel::USER_UPLOAD);
                }
            }
        } else if ($type == 2) {
            $data = @json_decode($data, true);
            if ($data) {
                $data = json_encode(['data' => $data, 'params' => $params]);
                $key = RedisQueue::LIST_USER_MOBILE_APPS_UPLOAD;
            }
        } else if ($type == 3) {
            $key = RedisQueue::LIST_USER_MOBILE_CONTACTS_UPLOAD;
        }

        if ($key && RedisQueue::push([$key, $data])) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => [],
                ],
            ];
        }
        //else

        $_req = json_encode($_REQUEST);
        if ((isset($_SERVER['HTTP_CONTENT_ENCODING']) && $_SERVER['HTTP_CONTENT_ENCODING'] == 'gzip') || $type == 3) {
            \yii::warning(sprintf('uploadcontents_error_719: %s raw:%s type:%s', $_req, $rawBody, $type), LogChannel::USER_UPLOAD);
        } else {
            \yii::warning(sprintf('uploadcontents_error_722:request length:%s content:%s', strlen($_req), $_req), LogChannel::USER_UPLOAD);
        }

        return [
            'code' => -1,
            'message' => '上传失败',
            'data' => [
                'item' => [],
            ],
        ];
    }

    private function _log($user_id, $data)
    {
        if ($user_id && in_array($user_id, [])) {
            \yii::error($data, __CLASS__ . '\\' . __FUNCTION__);
        }
    }

    /**
     * 上传通讯录
     * @name    上传通讯录 [infoUpLoadContacts]
     * @uses    上传通讯录
     * @method POST
     * @param  string $data 上传的数据json格式{{'user_id':148,'mobile':'13651896145:13600000000:021-12345678','name'=>'姓名'}，{}，...}
     * @author  honglifeng
     */
    public function actionUpLoadContacts()
    {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser ? $curUser->getId() : 0;
        if (isset($_SERVER['HTTP_CONTENT_ENCODING']) && $_SERVER['HTTP_CONTENT_ENCODING'] == 'gzip') {  // decompress
            $encoding = $_SERVER['HTTP_CONTENT_ENCODING'];
            $rawBody = file_get_contents('php://input');
            $body = '';
            try {
                switch ($encoding) {
                    case 'gzip':
                        $body = gzdecode($rawBody);
                        break;
                    case 'deflate':
                        $body = gzinflate(substr($rawBody, 2, -4)) . PHP_EOL . PHP_EOL;
                        break;
                    case 'deflate-raw':
                        $body = gzinflate($rawBody);
                        break;
                }
            } catch (\Exception $e) {
                \yii::warning(sprintf('upload_contents_ex: %s, %s, %s', $encoding, $rawBody, $e), LogChannel::USER_UPLOAD);
                throw $e;
            }
            mb_parse_str($body, $_POST);
        }

        $data = $this->request->post('data');
        $this->_log($user_id, $data);
        if (empty($data)) {
            return [
                'code' => -1,
                'message' => '上传数据为空',
                'data' => [
                    'item' => [],
                ],
            ];
        }


        $ret = RedisQueue::push([RedisQueue::LIST_USER_MOBILE_CONTACTS_UPLOAD, $data]);
        if ($ret) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => [],
                ],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '上传失败',
                'data' => [
                    'item' => [],
                ],
            ];
        }
    }

    /**
     * 获取个人基本信息
     * @name    获取个人基本信息 [infoGetPersonInfo]
     * @uses    获取个人基本信息
     * @author  honglifeng
     */
    public function actionGetPersonInfo()
    {

        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        $loan_person = LoanPerson::find()->where(['id' => $curUser->getId()])->one();

        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('该用户不存在');
        }

        return [
            'code' => 0,
            'message' => '成功获取个人基本信息',
            'data' => ['item' => $loan_person],
        ];
    }

    //获取聚信立状态
    public function actionGetJxlStatus()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();

        return $this->getJxlStatus($user_id);

    }

    public function getJxlStatus($user_id)
    {
        $queue = CreditJxlQueue::find()->where(['user_id' => $user_id])->one();
        $time = time();
        if (is_null($queue)) {
            $data = -1;
            // $message = '系统错误，请重试';
            $message = '未查找到认证请求，请重新进行认证';
            ErrorMessage::getMessage($user_id, '运营商认证运行追踪日志：队列为空', ErrorMessage::SOURCE_JXL);
        } elseif ((($time - $queue->created_at) > 60 * 30 && !in_array($queue->current_status, [4, 6]))) {
            $queue->current_status = -1;
            $queue->message = '提交超时,请重新输入服务密码';

            $queue->updated_at = $time;
            //$queue->save();
            if (!$queue->save()) {
                ErrorMessage::getMessage($user_id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
            }
            $data = -1;
            $message = '提交超时,请重新输入服务密码';
            ErrorMessage::getMessage($user_id, '运营商认证运行追踪日志：提交超时1', ErrorMessage::SOURCE_JXL);
        } elseif ((($time - $queue->created_at) > 7200 && in_array($queue->current_status, [4]))) {
            $queue->current_status = -1;
            $queue->message = '提交超时,请重新输入服务密码';
            $queue->save();
            $data = -1;
            $message = '提交超时,请重新输入服务密码';
            ErrorMessage::getMessage($user_id, '运营商认证运行追踪日志：提交超时2', ErrorMessage::SOURCE_JXL);
        } else if ($queue->type == 2) {
            /** @var JxlService $service */
            $service = Yii::$app->jxlService;
            $open_id = $queue->token;
            if (empty($open_id)) {
                if (!empty($queue->message) && $queue->message != '获取查询令牌失败' && $queue->message != '令牌验证失败') {
                    return [
                        'code' => 0,
                        'data' => -1,
                        'message' => $queue->message,
                    ];
                } else {
                    return [
                        'code' => 0,
                        'data' => -1,
                        'message' => '提交客户手机服务密码失败，请重新进行认证',
                    ];
                }
            }
            $data = 0;
            $status = $service->getStatus($queue);
            if ($status['code'] != 0) {
                return [
                    'code' => 0,
                    'message' => $status['message'],
                    'data' => $data,
                ];
            } else {
                return [
                    'code' => 0,
                    'data' => $status['status'],
                    'message' => $status['message'],
                ];
            }
        } else {
            $data = $queue->current_status;
            /* if($data == 10){
              $data = 3;
              }
              if($data == 11){
              $data = 4;
              }
              if($data == -4){
              $data = 3;
              } */
            $message = $queue->message;
        }
        if (empty(trim($message))) {
            $message = "信息获取失败，建议您一周后再尝试";
        } elseif ($message == '没有可用数据源') {
            $message = '手机号暂不支持';
        }

        return [
            'code' => 0,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 提交客户手机服务密码
     * @name    提交客户手机服务密码 [infoPostQueryPwd]
     * @uses    提交客户手机服务密码
     * @method  post
     * @author  王成
     * @param  string $password 密码
     */
    public function actionPostServiceCode()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;

        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();

        return $this->postJxlPersonPhonePwd($user_id);
    }

    public function postJxlPersonPhonePwd($user_id) {
        $loan_person = LoanPerson::findById($user_id);
        if ($loan_person == false) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }

        $password = $this->request->post('p');
        if (empty($password)) {
            return UserExceptionExt::throwCodeAndMsgExt('手机服务密码不符合规范');
        }

        $time = time();
        $queue = CreditJxlQueue::find()->where([
            'user_id' => $user_id,
        ])->one();
        if (is_null($queue)) {
            $queue = new CreditJxlQueue();
            $queue->type = 2;
        }
        elseif (in_array($queue->current_status, [CreditJxlQueue::STATUS_PROCESS_FINISH])) {
            return UserExceptionExt::throwCodeAndMsgExt('流程已完成，请勿重复提交');
        }
        else {
            if (!in_array($queue->current_status, [ CreditJxlQueue::STATUS_INPUT_PHONE_PWD, 0, CreditJxlQueue::STATUS_RESTART_PROCESS, ])
                && (($time - $queue->created_at) < 60 * 20)) {

                return UserExceptionExt::throwCodeAndMsgExt('正在查询，请耐心等待');
            }
        }

        $queue->type = 2;
        $queue->user_id = $user_id;
        $queue->service_code = $password;
        $queue->token = null;
        $queue->website = null;
        $queue->captcha = null;
        $queue->process_code = 0;
        if (isset($queue->type) && $queue->type == 2) {
            $queue->current_status = 1;
        }
        else {
            $queue->current_status = 2;
        }
        $queue->message = '';

        $queue->channel = CreditJxlQueue::CHANNEL_JSQB;

        $queue->created_at = $time;
        $queue->updated_at = $time;
        if (!$queue->save()) {
            ErrorMessage::getMessage($loan_person->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试2');
        }
        if (isset($queue->type) && $queue->type == 2) {
            $result = $this->submitJxlServicePassword($queue);
            return [
                'code' => 0,
                'message' => $result['message'],
                'data' => '',
            ];
        } else {
            RedisQueue::push([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_USER_INFO, $queue->id]);
            return [
                'code' => 0,
                'message' => '',
                'data' => ''
            ];
        }
    }


    /**
     * 提交客户手机验证码
     * @name    提交客户手机验证码 [infoPostPhoneCaptcha]
     * @uses    提交客户手机验证码
     * @method  post
     * @author  王成
     * @param  string $captcha 验证码
     */
    public function actionPostPhoneCaptcha()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();


        return $this->postJxlPersonPhoneCaptcha($user_id);

    }

    public function postJxlPersonPhoneCaptcha($user_id)
    {
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试3');
        }

        $captcha = $this->request->post('p');
        if (empty($captcha)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码不符合规则');
        }
        $queue = CreditJxlQueue::find()->where(['user_id' => $user_id])->one();
        $time = time();
        if ($queue->type == 2) {
            $this->getJxlStatus($user_id);
        }
        //if(!is_null($queue) && in_array($queue->current_status,[3,-2,-1,-4])){
        if (!is_null($queue) && in_array($queue->current_status, [3, -4])) {
            $queue->captcha = $captcha;
            //$queue->current_status = 4;
            if (isset($queue->type) && $queue->type == 2) {
                $queue->current_status = 3;
            } else {
                $queue->current_status = 4;
            }
            $queue->message = '';
            $queue->updated_at = $time;
            if (!$queue->save()) {
                ErrorMessage::getMessage($loan_person->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试4');
            }
            if (isset($queue->type) && $queue->type == 2) {
                $result = $this->submitJxlPhoneCaptcha($queue);
                return [
                    'code' => 0,
                    'message' => $result['message'],
                    'data' => '',
                ];
            } else {
                RedisQueue::push([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_CAPTCHA, $queue->id]);
            }
        }
        return [
            'code' => 0,
            'message' => '',
            'data' => ''
        ];
    }

    /**
     * 提交客户手机查询密码
     * @name    提交客户手机验证码 [infoPostPersonPhoneCaptcha]
     * @uses    提交客户手机验证码
     * @method  post
     * @author  王成
     * @param  string $captcha 验证码
     */
    public function actionPostPhoneQueryPwd()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();

        return $this->postJxlPersonPhoneQueryPwd($user_id);
    }

    public function postJxlPersonPhoneQueryPwd($user_id)
    {
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试5');
        }

        $query_pwd = $this->request->post('p');
        if (empty($query_pwd)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码不符合规则');
        }
        $queue = CreditJxlQueue::find()->where(['user_id' => $user_id])->one();
        $time = time();
        if ($queue->type == 2) {
            $this->getJxlStatus($user_id);
        }
        //if(!is_null($queue) && in_array($queue->current_status,[10,3,-2,-1])){
        if (!is_null($queue) && in_array($queue->current_status, [10])) {
            $queue->query_pwd = $query_pwd;
            if (isset($queue->type) && $queue->type == 2) {
                $queue->current_status = 10;
            } else {
                $queue->current_status = 11;
            }
            $queue->updated_at = $time;
            if (!$queue->save()) {
                ErrorMessage::getMessage($loan_person->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试6');
            }
            if (isset($queue->type) && $queue->type == 2) {
                $result = $this->submitJxlQueryPassword($queue);
                return [
                    'code' => 0,
                    'message' => $result['message'],
                    'data' => '',
                ];
            } else {
                RedisQueue::push([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_QUERY_PWD, $queue->id]);
            }
        }
        return [
            'code' => 0,
            'message' => '',
            'data' => ''
        ];
    }

    public function postHljrPersonPhoneQueryPwd($user_id)
    {
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试7');
        }

        $query_pwd = $this->request->post('p');
        if (empty($query_pwd)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码不符合规则');
        }
        $queue = CreditYysQueue::find()->where(['user_id' => $user_id])->one();
        if (!is_null($queue) && in_array($queue->current_status, [10, 3, -2, -1])) {
            $queue->query_pwd = $query_pwd;
            $queue->current_status = 4;
            if (!$queue->save()) {
                return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试8');
            }
            RedisQueue::push([RedisQueue::LIST_GET_USER_YYS_BASIC_REPORT_QUERY_PWD, $queue->id]);
        }
        return [
            'code' => 0,
            'message' => '',
            'data' => ''
        ];
    }

    /**
     * 重新发送验证码
     * @name    重新发送验证码 [infoResendPhoneCaptcha]
     * @uses    重新发送验证码
     * @method  post
     * @author  王成
     */
    public function actionResendPhoneCaptcha()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();

        return $this->resendJxlPhoneCaptcha($user_id);

    }

    public function resendJxlPhoneCaptcha($user_id)
    {
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试9');
        }
        $queue = CreditJxlQueue::find()->where(['user_id' => $user_id])->one();
        $time = time();
        if (is_null($queue) || is_null($queue->token) || is_null($queue->website)) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试10');
        }
        if (!in_array($queue->current_status, [3, -2, -4])) {
            return UserExceptionExt::throwCodeAndMsgExt('当前状态不可获取验证码');
        }
        $token = $queue->token;
        $website = $queue->website;
        $type = $queue->type;

        $service = Yii::$app->jxlService;

//        if ($type == 2) {
//            $result = $service->resendCaptcha($token);
//        } else {
            $result = $service->resendMobileCaptcha($token, $website);
//        }
        if ($result['code'] != 0) {
            $queue->current_status = -1;
            $queue->error_code = 1;
            $queue->save();
            ErrorMessage::getMessage($user_id, $result['message'], ErrorMessage::SOURCE_JXL);
            return UserExceptionExt::throwCodeAndMsgExt($result['message']);
        } else {
            //$queue->current_status = 3;
            //$queue->error_code = 0;
            //$queue->save();
        }
        return [
            'code' => 0,
            'message' => '',
            'data' => '',
        ];
    }

    /**
     * 反馈意见
     * @name    反馈意见 [settingFeedback]
     * @uses    反馈意见
     * @method  post
     * @param   text $content 反馈意见
     * @author  honglifeng
     */
    public function actionFeedback()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        if (!$this->request->isPost) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'sub_type' => UserFeedback::$show_sub_text,
                ],
            ];
        }

        $content = trim($this->request->post('content', '0'));
        $content = stripslashes($content);
        $type = Yii::$app->request->post('type', 0);
        //$type = trim($this->request->post('type', UserFeedback::TYPE_OPINION));
        $sub_type = trim($this->request->post('sub_type', '0'));
        $order_id = trim($this->request->post('order_id', 0));

        $result = [];
        if ($order_id != 0 && $type == 1) {
            $type = 2;
            $result = CollectionService::order_id($order_id);
        }
        $orderUserId = 0;
        if ($type == 1) {
            $orderUser = UserLoanOrder::find()->where(['user_id' => $curUser->id])->orderBy('id desc')->limit(1)->one();
            if ($orderUser) {
                $orderUserId = $orderUser->id;
            }
        }
        if (empty($content)) {
            return UserExceptionExt::throwCodeAndMsgExt('请填写反馈意见');
        }

        $user_feedback = new UserFeedback();
        $user_feedback->user_id = $curUser->getId();
        $user_feedback->content = $content;
        $user_feedback->type = $type;
        $user_feedback->order_id = empty($order_id) ? $orderUserId : $order_id;
        $user_feedback->sub_type = $sub_type;
        $user_feedback->outside = isset($result['outside']) ? $result['outside'] : 0;
        $user_feedback->admin_user_id = isset($result['admin_user_id']) ? $result['admin_user_id'] : 0;
        $user_feedback->created_at = time();
        $user_feedback->updated_at = time();

        if ($user_feedback->save()) {
            // 内容匹配 TODO: 处理操作
            if ((false !== strpos($content, "还款") || false !== strpos($content, "还不了款"))
                && (false !== strpos($content, "打不开")
                    || false !== strpos($content, "进不去")
                    || false !== strpos($content, "无法")
                    || false !== strpos($content, "不能")
                    || false !== strpos($content, "没反应")
                    || false !== strpos($content, "不跳转")
                    || false !== strpos($content, "不进去")
                    || false !== strpos($content, "点不开")
                    || false !== strpos($content, "空白")
                    || false !== strpos($content, "还不了"))
            ) {

                $total = UserLoanOrderRepayment::find()
                    ->from(UserLoanOrderRepayment::tableName() . ' as r')
                    ->leftJoin(UserLoanOrder::tableName() . ' as o', 'r.order_id=o.id')
                    ->where(['r.user_id' => $curUser->getId(), 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
                    ->andWhere(" r.status <> " . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere(['sub_order_type' => $this->sub_order_type]))
                    ->select(['r.*', 'o.counter_fee'])
                    ->orderBy('r.id desc')
                    ->count();
                if ($this->isFromXjk() && $this->client->clientType == 'ios' && $total > 0) {
                    $message = "消息已提交成功。感谢您的回复，如无法打开还款页，请卸载并重装APP。";
                    return [
                        'code' => -1,
                        'message' => $message,
                        'data' => ['item' => []],
                    ];
                }
            }

            return [
                'code' => 0,
                'message' => '反馈成功',
                'data' => ['item' => []],
            ];
        }

        return UserExceptionExt::throwCodeAndMsgExt('反馈失败,请稍后再试');
    }

    /**
     * 发送前程数据的短信验证码接口
     */
    public function actionSendPhoneCaptcha()
    {
        $phone = trim($this->request->post('phone'));
        if (!Util::verifyPhone($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
        }

        try {
            $source_id = $this->getSource();
            if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_QIANCHENG_DATA_CAPTCHA, false, $source_id)) {
                return [
                    'code' => 0,
                    'message' => '成功获取验证码',
                    'data' => ['item' => []],
                ];
            } else {
                return [
                    'code' => -1,
                    'message' => '获取验证码失败',
                    'data' => ['item' => []],
                ];
            }
        } catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 发送支付宝验证的短信验证码
     */
    public function actionSendAlipayAuth()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        // 获取用户手机号
        $user_id = $curUser->getId();
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
        $phone = $loan_person->phone;
        try {
            if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_BIND_ALIPAY_AUTH)) {
                return [
                    'code' => 0,
                    'message' => '成功获取验证码',
                    'data' => ['item' => []],
                ];
            } else {
                return [
                    'code' => -1,
                    'message' => '获取验证码失败',
                    'data' => ['item' => []],
                ];
            }
        } catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 绑定支付宝认证时：验证用户输入的验证码
     *
     * @name 验证登录 [CaptchaLogin]
     * @method post
     * @param string phone 当前手机号
     * @param string captcha 用户的验证码
     * @return array
     */
    public function actionAuthBindAlipay()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        // 获取用户手机号
        $user_id = $curUser->getId();
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
        $phone = $loan_person->phone;
        $code = trim($this->request->post('captcha'));

        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_BIND_ALIPAY_AUTH)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {
            return [
                'code' => 0,
                'message' => '验证成功',
                'data' => ['item' => []],
            ];
        }
    }


    /**
     * 设置支付宝的信息
     */
    public function actionGetAlipayInfo()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        $account = trim($this->request->post('account', ''));
        $password = trim($this->request->post('password', ''));
        $isupdate = trim($this->request->post('isupdate', '0'));

        if (empty($account) || empty($password)) {
            return UserExceptionExt::throwCodeAndMsgExt('支付宝账号或者密码不能为空');
        }

        $user_id = $curUser->getId();
        // $user_id = '449841';
        $loan_person = LoanPerson::findById($user_id);
        if (false == $loan_person) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
        $phone = $loan_person->phone;
        // $phone   = '18200000000';

        $bind_person = UserAlipayInfo::findByUserId($user_id);

        $flag = false;
        // 更新现有的用户账号
        if ($bind_person) {
            if ($isupdate) {
                $bind_person->account = $account;
                $bind_person->password = $password;
                $flag = $bind_person->save();
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('该用户已经绑定过支付宝账号');
            }
        } else {
            $uerAlipayInfo = new UserAlipayInfo();
            $uerAlipayInfo->user_id = $user_id;
            $uerAlipayInfo->phone = $phone;
            $uerAlipayInfo->account = $account;
            $uerAlipayInfo->password = $password;
            $flag = $uerAlipayInfo->save();
        }

        if ($flag) {
            return [
                'code' => 0,
                'message' => '反馈成功',
                'data' => ['item' => []],
            ];
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('反馈失败,请稍后再试');
        }
    }

    /**
     * 处理首页激活弹窗的显示
     */
    public function actionCreditShow()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return [
                "code" => CodeException::LOGIN_DISABLED,
                "message" => CodeException::$code[CodeException::LOGIN_DISABLED]
            ];
        }
        $user_id = $curUser->getId();
        $userService = Yii::$container->get('userService');
        $card_detail_info = $userService->getCreditDetail($user_id);
        $card_detail_info->golden_show = 0;
        $card_detail_info->save();

        return [
            "code" => 0,
            "message" => "通知成功",
            "data" => []
        ];
    }

    /**
     * @name 通知风控获取用户额度操作
     * @param $type  【可选】认证标识 1 激活金卡 ；0表示提额
     */
    public function actionUserCreditTop()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return [
                "code" => CodeException::LOGIN_DISABLED,
                "message" => CodeException::$code[CodeException::LOGIN_DISABLED]
            ];
        }

        $user_id = $curUser->getId();

        $card_type = trim($this->request->post('type'));
        if ($card_type == 1) {
            Yii::warning(\sprintf("%s : %s", $user_id, json_encode($this->getRequest()->get())), 'golden_show');
            $card_type = 0;
        }

        $footer = [
            "title" => "额度获取中",
            "status" => 1,
            "card_type" => $card_type == 1 ? 2 : 1,
        ];

        // 加认证锁
        if (UserCreditDetail::lockUserCreditRecord($user_id)) {
            $userService = Yii::$container->get('userService');
            $card_detail_info = $userService->getCreditDetail($user_id);

            if ($card_type == 1) { // 1. 金卡(目前不用)
                $card_detail_info->card_golden = UserCreditDetail::CARD_GOLDEN_ING;
                $card_detail_info->golden_show = 1;
                $card_detail_info->save();
            }
            else { // 2. 白卡
                if (UserCreditDetail::STATUS_ING != $card_detail_info->credit_status) {
                    $card_detail_info->credit_status = UserCreditDetail::STATUS_ING;
                    $card_detail_info->credit_total += 1;
                    $card_detail_info->save();
                    CreditLineService::checkUserCreditLines($user_id);
                }

                // RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $user_id]);
            }
        }
        $header = [
            "status" => 2,
            "title" => "额度计算中，预计需要1分钟，1分钟后到个人中心查看",
            "data" => "额度获取中",
            "active_url" => "",
            "active_title" => "我的额度",
        ];
        if(version_compare($this->client->appVersion,'2.4.2') >= 0
            && Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO
        ){
            $header = [
                "status" => 2,
                "title" => "额度计算中，预计需要1分钟，1分钟后到个人中心查看",
                "data" => "额度获取中",
                "active_url" => "",
                "active_title" => "我的额度",
            ];
        }


        // 处理区分 开卡 ，提额 ，升级发薪卡
        $message = "";
        // 升级发薪卡
        if ($card_type == 1) {
            $message = "亲，正在努力为您开通发薪卡！此过程预计需要1分钟，1分钟后到个人中心查看！";
            $data = [
                "message" => $message,
                "footer" => $footer
            ]; // 提额认证
        }
        elseif ($card_type == 2) {
            $data = [
                "message" => $message,
                "header" => $header,
            ]; // 开卡
        }
        else {
            $message = "亲，正在努力为您计算额度！此过程预计需要1分钟，1分钟后到个人中心查看！";
            $data = [
                "message" => $message,
                "header" => $header,
                "footer" => $footer
            ];
        }
        $refresh_time = 20;//20秒请求一次
        $data['refresh_time'] = $refresh_time;
        return [
            "code" => 0,
            "message" => $message,
            "data" => $data
        ];
    }

    /*
     * 调用失败处理
     */
    private function _submitJxlServicePasswordFailed(&$queue, $user_id, $msg1, $msg2)
    {
        $queue->current_status = -1;
        $queue->message = $msg1;
        $queue->updated_at = time();
        if (!$queue->save()) {
            ErrorMessage::getMessage($user_id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
        }

        ErrorMessage::getMessage($user_id, "{$msg2}: {$msg1}", ErrorMessage::SOURCE_JXL);
    }

    /**
     * @uses New interface. Submit service password to JXL through wealida.
     */
    protected function submitJxlServicePassword(CreditJxlQueue $queue)
    {
        try {
            $id = $queue->id;
            GlobalHelper::connectDb('db_kdkj');
            if ($queue->current_status != 1) {
                return [
                    'code' => -1,
                    'message' => '提交客户手机服务密码流程异常，请重新进行认证',
                    'data' => '',
                ];
            }

            $loanPerson = LoanPerson::findOne($queue['user_id']);
            if (false == $loanPerson) {
                return UserExceptionExt::throwCodeAndMsgExt('用户不存在,id:' . $id);
            }

            $phone = $loanPerson['phone'];
            $id_number = strtoupper($loanPerson['id_number']);
            $name = $loanPerson['name'];
            $home_tel = "";
            $contacts = UserContact::findOne(['user_id' => $loanPerson->id]);
            $contacts_arr = [];
            if (!empty($contacts)) {
                $mobile_list = explode(":", $contacts->mobile);
                $contacts_arr[] = [
                    'contact_tel' => current($mobile_list),
                    'contact_name' => $contacts->name,
                    'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation] ?? "0"  // 默认配偶
                ];
                if (!empty($contacts->mobile_spare) && !empty($contacts->name_spare)) {
                    $spare_mobile_list = explode(":", $contacts->mobile_spare);
                    $contacts_arr[] = [
                        'contact_tel' => current($spare_mobile_list),
                        'contact_name' => $contacts->name_spare,
                        'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation_spare] ?? "6" // 默认其他
                    ];
                }
            }

            $options['home_tel'] = $home_tel;
            $options['contacts'] = $contacts_arr;

            $service = Yii::$app->jxlService;

            $time = time();

            $result = $service->getCarrierOpenIdNew($name, $id_number, $phone, "", $options);
            if ($result['code'] != 0 || empty($result['open_id'])) {
                if ($result['message'] == "获取查询令牌失败" || $result['message'] == "令牌验证失败") {
                    $result = $service->getToken(true);
                    if ($result['code'] != 0 || empty($result['token'])) {
                        $this->_submitJxlServicePasswordFailed($queue, $loanPerson->id, $result['message'], 'get_token2 failed');
                        return UserExceptionExt::throwCodeAndMsgExt('token获取失败: ' . $result['message']);
                    }

                    $token = $result['token'];
                    $result = $service->getCarrierOpenIdNew($name, $id_number, $phone, "", $options);
                    if ($result['code'] != 0 || empty($result['open_id'])) {
                        $this->_submitJxlServicePasswordFailed($queue, $loanPerson->id, $result['message'], 'get_openid failed');
                        return UserExceptionExt::throwCodeAndMsgExt('open_id获取失败: ' . $result['message']);
                    }
                } else {
                    $this->_submitJxlServicePasswordFailed($queue, $loanPerson->id, $result['message'], 'get_openid failed');
                    return UserExceptionExt::throwCodeAndMsgExt('open_id获取失败: ' . $result['message']);
                }
            }

            $open_id = $result['open_id'];
            $website = $result['website'];
            $queue->token = $open_id;
            $result = $service->submitServicePasswordNew($open_id,$website,$phone,$queue['service_code']);
            if ($result['code'] == 0) {
                //$verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                //$verification->real_jxl_status = 1;
                //$verification->updated_at = $time;
                //$verification->save();
                $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                if (is_null($jxl)) {
                    $jxl = new CreditJxl();
                }
                $jxl->person_id = $loanPerson->id;
                $jxl->token = $open_id;
                $jxl->status = CreditJxl::STATUS_FALSE;
                $jxl->updated_at = $time;
                if (!$jxl->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxl记录表保存失败', ErrorMessage::SOURCE_JXL);
                    return UserExceptionExt::throwCodeAndMsgExt('CreditJxl记录表保存失败');
                }
                $queue->message = '输入手机服务密码后等待结果';
                $queue->current_status = 2;
//                $queue->token = $open_id;
                $queue->process_code = $result['process_code'];
                $queue->website = $website;
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                    return UserExceptionExt::throwCodeAndMsgExt('CreditJxlQueue队列表保存失败');
                }
                return [
                    'code' => 0,
                    'message' => '手机服务密码提交成功，等待结果中',
                    'data' => '',
                ];
            } else {
                if ($result['code'] == 1001) {
                    $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                    $queue->error_code = 10003;
                    $queue->message = $result['message'];
                    $queue->updated_at = $time;
                } else {
                    $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                    $queue->error_code = 1;
                    $queue->message = $result['message'];
                    $queue->updated_at = $time;
                }
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('手机服务密码提交失败: ' . $result['message']);
            }
        } catch (\Exception $e) {
            ErrorMessage::getMessage($queue->user_id, $e->getMessage() . $e->getFile() . $e->getLine(), ErrorMessage::SOURCE_JXL);
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙，请稍后再试.');
        }
    }

    /**
     *
     * @author  Shayne Song
     * @uses New interface. Submit phone captcha to JXL through wealida.
     *
     */
    protected function submitJxlPhoneCaptcha(CreditJxlQueue $queue)
    {
        $id = $queue->id;
        GlobalHelper::connectDb('db_kdkj');
        if (!in_array($queue->current_status, [3])) {
            return [
                'code' => -1,
                'message' => '提交客户手机验证码流程异常，请重新进行认证',
                'data' => '',
            ];
        }
        $loanPerson = LoanPerson::findOne($queue['user_id']);

        if (false == $loanPerson) {
            return UserExceptionExt::throwCodeAndMsgExt('用户不存在,id:' . $id);
        }
        $open_id = $queue->token;
        $captcha = $queue->captcha;
        $service_code = $queue->service_code;
        $website = $queue->website;

        $service = Yii::$app->jxlService;

        $result = $service->submitCaptchaNew($loanPerson,$open_id,$service_code,$website,$captcha);
        $time = time();
        switch ($result['code']) {
            case -1:
                $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                $queue->error_code = 1;
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('手机验证码提交失败：' . $result['message']);
                break;
            case 1000:
                $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                $queue->error_code = 1;
                $queue->message = $result['message'];
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('提交客户手机验证码失败：' . $result['message']);
                break;
            case 1001:
                $queue->current_status = CreditJxlQueue::STATUS_CAPTCHA_ERROR;
                $queue->error_code = 10004;
                $queue->message = $result['message'];
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('提交客户手机验证码失败：' . $result['message']);
                break;
            default:
                $queue->message = '输入验证码后等待结果';
                $queue->current_status = 4;
                if ($result['process_code']){
                    $queue->process_code = $result['process_code'];
                }
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                //$verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                //$verification->real_jxl_status = 1;
                //$verification->updated_at = $time;
                //$verification->save();
                $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                if (is_null($jxl)) {
                    $jxl = new CreditJxl();
                }
                $jxl->person_id = $loanPerson->id;
                $jxl->token = $open_id;
                $jxl->status = CreditJxl::STATUS_FALSE;
                $jxl->updated_at = $time;
                if (!$jxl->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxl记录表保存失败', ErrorMessage::SOURCE_JXL);
                }
                return [
                    'code' => 0,
                    'message' => '手机验证码提交成功',
                    'data' => '',
                ];
        }
    }

    /**
     * @name 当前用户剩余短信的次数
     * @author
     * @method  post
     * @uses 当前用户剩余短信的次数
     */
    public function actionGetInvitateLast()
    {
        $user = Yii::$app->user->identity;
        $user_id = $user->getId();

        $total = $this->getUserLastTotal($user_id);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                "item" => $total,
            ],
        ];
    }

    private function getUserLastTotal($user_id)
    {
        $cache_key = sprintf("%s%s:%s", MessageAppLog::USER_INVITE_SMS_TOTAL_BY_USERID, date("Ymd"), $user_id);
        $total = RedisQueue::get(['key' => $cache_key]);

        if (empty($total)) {
            $total = MessageAppLog::USER_INVITE_SMS_TOTAL;
            $expire_time = strtotime(date("Y-m-d 23:59:59")) - time();
            RedisQueue::set(["expire" => $expire_time, "key" => $cache_key, "value" => $total]);
        }

        if ($total < 0) {
            $total = 0;
        }

        return intval($total);
    }

    /**
     * @author  Shayne Song
     * @uses New interface. Submit query password to JXL through wealida.
     */
    protected function submitJxlQueryPassword(CreditJxlQueue $queue)
    {
        $id = $queue->id;
        GlobalHelper::connectDb('db_kdkj');
        if (!in_array($queue->current_status, [10])) {
            return [
                'code' => -1,
                'message' => '提交客户手机查询密码流程异常，请重新进行认证',
                'data' => '',
            ];
        }
        $loanPerson = LoanPerson::findOne($queue['user_id']);
        if (false == $loanPerson) {
            return UserExceptionExt::throwCodeAndMsgExt('用户不存在,id:' . $id);
        }
        $open_id = $queue->token;
        $query_password = $queue->query_pwd;
        $service_code = $queue->service_code;
        $website = $queue->website;
        $service = Yii::$app->jxlService;
        $result = $service->submitQueryPasswordNew($loanPerson,$open_id,$service_code,$website,$query_password);
        $time = time();
        switch ($result['code']) {
            case -1:
                $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                $queue->error_code = 1;
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('提交客户手机查询密码失败：' . $result['message']);
                break;
            case 1000:
                $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                $queue->error_code = 1;
                $queue->message = $result['message'];
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('提交客户手机查询密码失败：' . $result['message']);
                break;
            case 1001:
                $queue->current_status = CreditJxlQueue::STATUS_CAPTCHA_ERROR;
                $queue->error_code = 10003;
                $queue->message = $result['message'];
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                return UserExceptionExt::throwCodeAndMsgExt('提交客户手机查询密码失败：' . $result['message']);
                break;
            default:
                $queue->message = '输入查询密码后等待结果';
                $queue->current_status = 11;
                if ($result['process_code']){
                    $queue->process_code = $result['process_code'];
                }
                $queue->updated_at = $time;
                if (!$queue->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxlQueue队列表保存失败', ErrorMessage::SOURCE_JXL);
                }
                //$verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                //$verification->real_jxl_status = 1;
                //$verification->updated_at = $time;
                //$verification->save();
                $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                if (is_null($jxl)) {
                    $jxl = new CreditJxl();
                }
                $jxl->person_id = $loanPerson->id;
                $jxl->token = $open_id;
                $jxl->status = CreditJxl::STATUS_FALSE;
                $jxl->updated_at = $time;
                if (!$jxl->save()) {
                    ErrorMessage::getMessage($loanPerson->id, 'CreditJxl记录表保存失败', ErrorMessage::SOURCE_JXL);
                }
                return [
                    'code' => 0,
                    'message' => '手机查询密码提交成功',
                    'data' => '',
                ];
        }
    }

    /**
     * 提交公积金查询申请
     *
     * @param string website    【必填】数据源名称【英文缩写】
     * @param string sort    【必填】数据源编码
     * @param string type    【必填】采集方式
     *
     * @param string account    【动态参数】数据源账号【公积金账号】
     * @param string password    【动态参数】公积金密码
     *
     * @param string id_card_num    【必填】用户身份证号码
     * @param string cell_phone_num    【必填】用户手机号码
     * @param string name    【必填】用户姓名
     *
     */
    public function actionSubmitHouseFundReq()
    {
        \yii::$app->response->format = Response::FORMAT_JSON;
        $params = \yii::$app->request->post();
        foreach (['website', 'sort', 'type'] as $_key) {
            if (empty($params[$_key])) {
                return [
                    'code' => -1,
                    'message' => '请求参数缺失',
                    'data' => '',
                ];
            }
        }

        $user = \yii::$app->user->identity;
        $user_id = $user->getId();
        if (!$user->isRealVerify) {
            return [
                'code' => -1,
                'message' => '当前用户未实名',
            ];
        }

        $params['id_card_num'] = $user->id_number;
        $params['cell_phone_num'] = $user->phone;
        $params['name'] = $user->name;

        $service = \Yii::$app->jxlService;
        try {
            $token = $service->submitHouseFundReq($params);
        } catch (\Exception $e) {
            return [
                'code' => -1,
                'message' => '申请公积金验证失败',
                'data' => [
                    'error' => $e->getMessage()
                ],
            ];
        }

        return [
            'code' => 0,
            'message' => '请求已提交，请等待认证结束',
            'data' => [
                'token' => $token,
            ],
        ];
    }

    /**
     * @name 获取冰鉴token
     * @return array
     */
    public function actionGetIceKreditToken()
    {
        \yii::$app->response->format = Response::FORMAT_JSON;
        $service = \yii::$container->get('iceKreditService');

        $user_id = \yii::$app->getUser()->getId() ?? 0;

        if ($alipay = IcekreditAlipay::findOne(['user_id' => $user_id])) {
            if ($alipay->status == IcekreditAlipay::STATUS_SUCCESS) {
                return [
                    'code' => -1,
                    'message' => '已认证成功，请勿重复提交',
                ];
            }
        } else {
            $alipay = new IcekreditAlipay();
            $alipay->user_id = $user_id;
            $alipay->status = IcekreditAlipay::STATUS_INIT;
            $alipay->message = '未认证';

            if (!$alipay->save()) {
                return [
                    'code' => -1,
                    'message' => 'token获取失败',
                ];
            }
        }

        try {
            if ($token = $service->getAccessToken()) {
                return [
                    'code' => 0,
                    'message' => '请求成功',
                    'data' => [
                        'token_id' => $token,
                        'pid' => $service->getPid(),
                        'rid' => (string)$alipay->id,
                        'callBackUrl' => 'http://qb.wzdai.com/credit/web/credit-info/ice-kredit-alipay-callback'
                    ]
                ];
            }
        } catch (\Exception $e) {
            \yii::error(\sprintf("user: %s token获取失败：%s", $user_id, $e->getMessage()), LogChannel::CREDIT_ICEKREDIT);
        }

        return [
            'code' => -1,
            'message' => 'token获取失败',
        ];
    }

    /**
     * 更改支付宝认证状态
     * @return array
     */
    public function actionChangeAlipayStatus()
    {
        \yii::$app->response->format = Response::FORMAT_JSON;

        $user_id = \yii::$app->getUser()->getId() ?? 0;

        if ($user_id && $alipay = IcekreditAlipay::findOne(['user_id' => $user_id])) {
            if ($alipay->status == IcekreditAlipay::STATUS_SUCCESS) {
                return [
                    'code' => -1,
                    'message' => '已认证成功，不可修改状态',
                ];
            }
            $alipay->status = IcekreditAlipay::STATUS_CALLBACKING;
            $alipay->message = '待认证';

            if ($alipay->save()) {
                return [
                    'code' => 0,
                    'message' => '状态更改成功',
                ];
            }
        }

        return [
            'code' => -1,
            'message' => '状态更改失败',
        ];
    }

    /**
     * @name 接受来自冰鉴的支付宝回调数据
     * @return array
     */
    public function actionIceKreditAlipayCallback()
    {
        \yii::$app->response->format = Response::FORMAT_JSON;
        \yii::$app->getResponse()->setStatusCode(202);

        $json = file_get_contents('php://input');
        \yii::warning($json, LogChannel::CREDIT_ICEKREDIT);
        $arr = json_decode($json, true);
        $callBackUrl = $arr['callBackUrl'] ?? null;
        $rid = isset ($arr['rid']) ? intval($arr['rid']) : null;

        if (empty($callBackUrl) || empty($rid)) {
            return [
                'code' => -1,
                'message' => 'callbackurl/rid不存在',
            ];
        }

        if (!$alipay_model = IcekreditAlipay::findOne(['id' => $rid])) {
            $error_message = 'rid有误';
            \yii::error(\sprintf("rid: %s , {$error_message}", $rid), LogChannel::CREDIT_ICEKREDIT);
            return [
                'code' => -1,
                'message' => $error_message,
            ];
        }

        //已认证成功
        if ($alipay_model->status == IcekreditAlipay::STATUS_SUCCESS) {
            \yii::$app->getResponse()->setStatusCode(200);
            return [
                'code' => 0,
                'message' => '回调成功',
            ];
        }
        $alipay_model->data = $callBackUrl;

        $service = \yii::$container->get('iceKreditService');
        $response_info = $service->curl($callBackUrl);

        if (!$response_info || !isset($response_info['response_code']) || $response_info['response_code'] != 0) {
            $error_message = '请求支付宝信息失败';
            \yii::error(\sprintf("rid: %s , {$error_message}", $rid), LogChannel::CREDIT_ICEKREDIT);
            $alipay_model->status = IcekreditAlipay::STATUS_FAILED;
            $alipay_model->message = $error_message;
            $alipay_model->save();
            return [
                'code' => -1,
                'message' => $error_message,
            ];
        }

        $alipay_info = $response_info['alipayInfo'] ?? null;

        if (!isset($alipay_info['status']) || $alipay_info['status'] != 0) {
            $error_message = '支付宝信息错误';
            $alipay_model->status = IcekreditAlipay::STATUS_FAILED;
            $alipay_model->message = $error_message;
            $alipay_model->save();
            \yii::error(\sprintf("rid: %s , {$error_message}", $rid), LogChannel::CREDIT_ICEKREDIT);
            return [
                'code' => -1,
                'message' => $error_message,
            ];
        }

        $user = LoanPerson::findById($alipay_model->user_id);

        if (!$user || $user->name != $alipay_info['data']['base_info']['name']) {
            $error_message = '支付宝姓名与用户姓名不一致';
            \yii::error(\sprintf("rid: %s , {$error_message}", $rid), LogChannel::CREDIT_ICEKREDIT);
            $alipay_model->status = IcekreditAlipay::STATUS_FAILED;
            $alipay_model->message = $error_message;
            $alipay_model->save();
            return [
                'code' => -1,
                'message' => $error_message,
            ];
        }

        $alipay_info['url'] = $callBackUrl;

        $transaction = \yii::$app->getDb()->beginTransaction();
        try {
            $alipay_model->data = $callBackUrl;
            $alipay_model->status = IcekreditAlipay::STATUS_SUCCESS;
            $alipay_model->message = '认证成功';

            $verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $alipay_model->user_id,
                'real_alipay_status' => UserVerification::VERIFICATION_YES,
                'operator_name' => $alipay_model->user_id,
            ]);

            $icekredit_alipay_data = new IcekreditAlipayData();
            $icekredit_alipay_data->user_id = $alipay_model->user_id;
            $icekredit_alipay_data->rid = $alipay_model->id;
            $icekredit_alipay_data->data = json_encode($alipay_info, JSON_UNESCAPED_UNICODE);

            if ($alipay_model->save() && $verification && $icekredit_alipay_data->save()) {
                \yii::$app->getResponse()->setStatusCode(200);
                $transaction->commit();
                return [
                    'code' => 0,
                    'message' => '回调成功',
                ];
            } else {
                $transaction->rollBack();
            }

        } catch (\Exception $e) {
            \yii::error(\sprintf("rid: %s , 数据保存失败 : %s", $rid, $e->getMessage()), LogChannel::CREDIT_ICEKREDIT);
            $transaction->rollBack();

        }

        return [
            'code' => -1,
            'message' => '数据保存失败',
        ];
    }

    /**
     * @name 获取冰鉴报告
     * @return mixed
     */
    public function actionGetIceKreditReport()
    {
        $user_id = intval(\yii::$app->getRequest()->post('user_id'));
        if (!$user_id) {
            var_dump('user_id is null');
            die;
        }
        \yii::$app->response->format = Response::FORMAT_JSON;

        $service = \yii::$container->get('iceKreditService');

        $credit = CreditJxlRawData::findOne(['user_id' => $user_id]);
        var_dump($user_id, $credit);
        $report = $service->getSuggestion($user_id);

        var_dump($report);
        die;

    }

    /**
     * @name debug
     */
    public function actionChangeStatusDebug()
    {
        $v = UserVerification::findOne(['user_id' => 5]);
        $v->real_alipay_status = 0;

        $alipay = IcekreditAlipay::findOne(['user_id' => 5]);
        $alipay->status = 0;

        var_dump($v->save(), $alipay->save());
        die;
    }

    /**
     * 接收app端推送获得的魔蝎 网银任务task_id
     * @return array
     */
    public function actionMoxieCreditTask()
    {

        \yii::$app->response->format = Response::FORMAT_JSON;

        $params = \yii::$app->request->post();
        if (empty($params) || !isset($params['taskId']) || !isset($params['code']) || empty($params['taskId'])) {
            return [
                'code' => -1,
                'message' => '参数错误',
            ];
        }

        // 数据提供商服务异常
        if (in_array($params['code'], [-2, -3])) {
            return [
                'code' => 0,
                'message' => '数据已接收',
            ];
        }
        //taskType 缺省默认为email  为bank时为网银
        if(!\yii::$app->request->post('taskType') || \yii::$app->request->post('taskType')=='email'){
            $params['credit_type'] = 1;
        }else{
            $params['credit_type'] = 2;
        }

        try {
            $transaction = \yii::$app->getDb()->beginTransaction();

            $user_id = 2;
//            $user_id = \yii::$app->getUser()->getId() ?? 0;

            $service = \yii::$container->get('moxieService');

            if (MoxieCreditTask::findOne(['user_id' => $user_id, 'task_id' => $params['taskId']]))
            {

                return [
                    'code' => -6,
                    'message' => "任务task_id: {$params['taskId']} 已存在",
                ];
            }

            // 记录信用卡账单任务
            if (!$service->saveCreditTask($user_id, $params)) {
                return [
                    'code' => -3,
                    'message' => "任务task_id: {$params['taskId']} 保存失败",
                ];
            }

            $verification_data = [
                'user_id' => $user_id,
                'operator_name' => $user_id,
            ];

            //保存认证状态
            if($params['credit_type']==1){
                $verification_data['real_credit_status'] = $service->mappingTaskStatus($params['code']);
            }else{
                $verification_data['online_banking_status'] = $service->mappingTaskStatus($params['code']);
            }

            if (!$verification = UserVerification::saveUserVerificationInfo($verification_data)) {
                $transaction->rollBack();
                return [
                    'code' => -4,
                    'message' => '任务状态保存失败',
                ];
            }

            $transaction->commit();
            return [
                'code' => 0,
                'message' => "数据接收成功",
            ];
        } catch (Exception $e) {
            \YII::error($e->getMessage(), LogChannel::CREDIT_MOXIE);
            isset($transaction) && !empty($transaction) && $transaction->rollBack();
            return [
                'code' => -5,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * 获取信用卡账单认证状态
     * @return array
     */
    public function actionGetMyBillStatus() {
        $user_id = 2;
//        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;

        $user_verification = UserVerification::findOne(['user_id' => $user_id]);
        if (!$user_verification) {
            return [
                'code' => -1,
                'message' => '状态异常',
                'data' => [
                    'email' => 0,
                    'online_bank' => 0,
                ]
            ];
        }

        return [
            'code' => 0,
            'message' => '获取状态成功',
            'data' => [
                'email' => $user_verification->real_credit_status,
                'online_bank' => $user_verification->online_banking_status,
            ]
        ];
    }

    /**
     * 查询我的账单
     * @return array
     */
    public function actionSearchMyBill() {
        $user_id = 2;
        //$user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        $type = trim($this->request->post('type'));
        if (!in_array($type, [1, 2])) {
            return [
                'code' => -1,
                'message' => '参数错误',
                'data' => [],
            ];
        }

        $user_verification = UserVerification::findOne(['user_id' => $user_id]);
        if (!$user_verification ||
            ($type == 1 && $user_verification->real_credit_status != 3) ||
            ($type == 2 && $user_verification->online_banking_status != 3)) {
            return [
                'code' => -1,
                'message' => '请先完成信用卡账单认证',
                'data' => [],
            ];
        }

        $bill = CreditMxBill::findOne(['user_id' => $user_id, 'type' => $type]);
        if (!$bill || empty($bill['data'])) {
            return [
                'code' => -1,
                'message' => '你的信用卡账单为空',
                'data' => [],
            ];
        }

        $bill_data = json_decode($bill['data'], true);
        $tmp_data = $type == 1 ? $bill_data['bills'] : $bill_data[0]['bills'];

        foreach ($tmp_data as $item) {
            $data[] = [
                'bill_date' => isset($item['bill_date']) ? date('Y-m-d', strtotime($item['bill_date'])) : date('Y-m-d', strtotime($item['bill_month'])),
                'new_balance' => $item['new_balance'],
            ];
        }

        return [
            'code' => 0,
            'message' => '账单查询成功',
            'data' => $data,
        ];
    }


}
