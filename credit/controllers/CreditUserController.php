<?php
namespace credit\controllers;

use common\helpers\Lock;
use common\helpers\Signature;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

use common\base\LogChannel;
use common\helpers\TimeHelper;
use common\helpers\StringHelper;
use common\models\LoanPerson;
use common\models\UserRegisterInfo;
use common\models\UserRealnameVerify;
use common\models\UserVerification;
use common\models\UserLoanOrder;
use common\services\UserService;
use common\models\UserCaptcha;
use common\models\UserLoginLog;
use common\exceptions\UserExceptionExt;
use common\exceptions\CodeException;
use common\api\RedisQueue;
use common\helpers\Util;
use common\models\UserCreditDetail;
use common\models\UserProofMateria;

use credit\components\ApiUrl;
use common\helpers\ToolsUtil;
use common\models\CardInfo;


/**
 * User controller
 */
class CreditUserController extends BaseController {

    protected $userService;

    /**v
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = []) {
        $this->userService = $userService;

        parent::__construct($id, $module, $config);
    }

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                // 除了下面的action其他都需要登录
                'except' => ['reg-get-code', 'reg-get-audio-code', 'register', 'login', 'auto-login', 'logout', 'quick-login',
                    'reset-password', 'reset-pwd-code', 'verify-reset-password',
                    'state', 'captcha', 'send-invite-code', 'captcha-login', 'check-safe-login','check-sign-code',
                    'get-pic-code'
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

    public function actions() {
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
     * 自动登录（仅开发环境有效）
     * @param string $phone
     */
    public function actionAutoLogin($phone) {
        \yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (YII_ENV_PROD) {
            throw new NotFoundHttpException('not found');
        }

        $person = LoanPerson::findOne(['phone' => $phone]);
        if (empty($person)) {
            throw new NotFoundHttpException('user not found');
        }

        if (\yii::$app->user->login($person)) {
            return [
                'code' => 0,
                'message' => 'welcome ' . $person->username,
            ];
        }

        return [
            'code' => -1,
            'message' => 'login failed',
        ];
    }

    /**
     * 我的个人中心标签
     * @param $card_str
     * @param $coupon_str
     * @param $invite_code
     *
     * @return array
     */
    private function getMyTag($card_str, $coupon_str, $invite_code) {
        $tag = 'tag';

        $loan_record = [
            'title' => '借款记录',
            'subtitle' => '',
            'group' => UserCreditDetail::TAG_GROUP_TYPE_ONE,
            'tag' => UserCreditDetail::TAG_USER_CENTER_LOAN,
            'logo' => $this->staticUrl("image/{$tag}/loan_record.png", 1),
        ];
        $list[] = $loan_record;

        $loan_perfect = [
            'title' => '认证提额',
            'subtitle' => '',
            'group' => UserCreditDetail::TAG_GROUP_TYPE_ONE,
            'tag' => UserCreditDetail::TAG_USER_CENTER_INFO,
            'logo' => $this->staticUrl("image/{$tag}/loan_perfect.png", 1),
        ];
        $list[] = $loan_perfect;

        $loan_card = [
            'title' => '收款银行卡',
            'subtitle' => empty($card_str) ? "" : $card_str,
            'group' => UserCreditDetail::TAG_GROUP_TYPE_TWO,
            'tag' => UserCreditDetail::TAG_USER_CENTER_CARD,
            'logo' => $this->staticUrl("image/{$tag}/loan_card.png", 1),
        ];

        if (!empty($card_str)) {
            $loan_card["url"] = ApiUrl::toRouteMobile(['loan/card-list'], true);
        }
        $list[] = $loan_card;

        $help_url = $this->t('help_url');
        if(YII_ENV_DEV){
            $baseUrl = $this->request->getHostInfo() . $this->request->getBaseUrl();
            $help_url = $baseUrl.'/credit-web/help-center';
        }

        $loan_help = [
            'title' => '帮助中心',
            'subtitle' => "",
            'group' => UserCreditDetail::TAG_GROUP_TYPE_TWO,
            'tag' => UserCreditDetail::TAG_USER_CENTER_HELP,
            'url' => $help_url,
            'logo' => $this->staticUrl("image/{$tag}/loan_help.png", 1),
        ];
        $list[] = $loan_help;

        $notice = [
            'title' => '我的消息',
            'subtitle' => "",
            'group' => UserCreditDetail::TAG_GROUP_TYPE_THREE,
            'tag' => UserCreditDetail::TAG_USER_CENTER_NOTICE,
            'url' => ApiUrl::toCredit(["credit-web/result-notice"]),
            'logo' => $this->staticUrl("image/{$tag}/message.png", 1),
        ];
        $list[] = $notice;

        $message = [
            'title' => '公告中心',
            'subtitle' => "",
            'group' => UserCreditDetail::TAG_GROUP_TYPE_THREE,
            'tag' => UserCreditDetail::TAG_USER_CENTER_MESSAGE,
            'url' => ApiUrl::toCredit(["credit-web/result-message"]),
            'logo' => $this->staticUrl("image/{$tag}/notice.png", 1),
        ];

        $list[] = $message;

        $setting = [
            'title' => '设置',
            'subtitle' => "",
            'group' => UserCreditDetail::TAG_GROUP_TYPE_THREE,
            'tag' => UserCreditDetail::TAG_USER_CENTER_SETTING,
            'logo' => $this->staticUrl("image/{$tag}/setting.png", 1),
        ];

        $list[] = $setting;

        return $list;
    }

    /**
     * 我的主页
     *
     * @name 我的主页 [creditGetInfo]
     * @method post
     * @return array
     */
    public function actionGetInfo() {
        $curUser = Yii::$app->user->identity;
        $curUser->generateInviteCode();
        $credit_info = $this->userService->getCreditInfo($curUser->getId());
        $verify_info = $this->userService->getVerifyInfo($curUser->getId());
        $card_info = $this->userService->getCardInfo($curUser->getId(), 1);
        $card_info = $card_info ? array_pop($card_info) : [];

        // 添加版本判断 = 是否完成授信认证
        $card_detail_info = $this->userService->getCreditDetail($curUser->getId());
        if ($card_detail_info) {
            if ($card_detail_info->user_type == 0 && in_array($card_detail_info->credit_status, [UserCreditDetail::STATUS_ING, UserCreditDetail::STATUS_NORAML, UserCreditDetail::STATUS_WAIT]) && !in_array($card_detail_info->card_golden, UserCreditDetail::$card_pass)) {
                if (version_compare($this->client->appVersion, '1.4.2') >= 0) {
                    $credit_info["card_amount"] = ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) ? "认证中" : "0";
                } else {
                    $credit_info["card_amount"] = "0";
                }
                $credit_info["card_unused_amount"] = 0;
            }

            if ($card_detail_info->user_type == 1 && ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT)) {
                if (version_compare($this->client->appVersion, '1.4.2') >= 0) {
                    $credit_info["card_amount"] = ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) ? "认证中" : "0";
                } else {
                    $credit_info["card_amount"] = "0";
                }
                $credit_info["card_unused_amount"] = 0;
            }
        }

        // 判断是否有实名认证
        $username = "";
        if ($verify_info["real_verify_status"] == 1) {
            $name = $curUser->name;
            if ($name) {
                $username = StringHelper::blurName($name);
            }
        }

        $share_url = ApiUrl::toRouteApi(['act/light-loan', "name" => $username, 'invite_code' => $curUser->invite_code, 'source_tag' => 'fenxiang'], true);

        $str_total = '';
        $card_str = isset($card_info["bank_name"]) && isset($card_info["card_no_end"])
            ? sprintf("%s(%s)", $card_info["bank_name"], $card_info["card_no_end"])
            : '';
        $item_list = $this->getMyTag($card_str, $str_total, $curUser->invite_code);

        $withdraw_deposit_count = 0;
        $myCouponCount = 0;

        //提额显示 只有登录用户才下发 guoxiaoyong
        $user_info_show_message = [];
        $redis = Yii::$app->redis;
        $user_info_show_message_from_redis = $redis->get("user_info_show_message_{$curUser->getId()}");
        if($user_info_show_message_from_redis)
        {
            $user_info_show_message[]['message'] = $user_info_show_message_from_redis;
            //销毁redis key
            $redis->del("user_info_show_message_{$curUser->getId()}");
        }


        $item =  [
            'invite_code' => $curUser->invite_code,
            'credit_info' => $credit_info,
            'card_info' => $card_info ? $card_info : "",
            'card_url' => ApiUrl::toRouteNewH5(['app-page/bank-card-info'], true),
            'verify_info' => $verify_info,
            'phone' => StringHelper::blurPhone($curUser->phone),
            'share_title' => $this->t('app_name'),
            'share_body' => $this->t('share_body'),
            'url' => $this->t('help_url'),
            'share_logo' => $this->staticUrl($this->t('share_logo')),
            'share_url' => $share_url,
            'red_pack_total' => $str_total,
            //'active_url' => $active_url,
            //'active_title' => $active_title,
            'user_info_show_message' => $user_info_show_message,
            "item_list" => $item_list,
        ];

        $item['greeting_msg'] = '';
        $item['user_lastname'] = '';
        if(Yii::$app->user->identity->name)
        {
            $item['greeting_msg'] = 'Hi ' . Yii::$app->user->identity->name;
            $item['user_lastname'] =  mb_substr(Yii::$app->user->identity->name, -1, 1, 'UTF-8');
        }

        //兼容老用户绑卡需要
        $cardinfo=CardInfo::find()->where(['user_id'=>$curUser->getId(),'status' => CardInfo::STATUS_SUCCESS,
            'type' => CardInfo::TYPE_DEBIT_CARD,'main_card'=>CardInfo::MAIN_CARD])->one();
        if(!$cardinfo){
            $sql="select * from `tb_card_info_old` where user_id=".$curUser->getId()." and `status` = ".CardInfo::STATUS_SUCCESS;
            $sql.=" and type=".CardInfo::TYPE_DEBIT_CARD." and main_card=".CardInfo::MAIN_CARD;
            $read_db = \Yii::$app->db_kdkj_rd_new;
            $cardinfo = $read_db->createCommand($sql)->queryOne();
            if($cardinfo){
                //银行名称
                $item['bank_name']=$cardinfo['bank_name'];
                //银行id
                $item['bank_id']=$cardinfo['bank_id'];
                //银行卡卡号
                $item['bank_cardcode']=$cardinfo['card_no'];
                //绑卡手机号
                $item['bank_cardmobile']=($cardinfo['phone']==0?'':$cardinfo['phone']);
            }
        }
        if(!isset($item['bank_name'])){
            $item['bank_name']='';
            $item['bank_id']='';
            $item['bank_cardcode']='';
            $item['bank_cardmobile']='';
        }

        return [
            'code' => 0,
            'message' => '获取成功',
            'data' => [
                'item' => $item,
                'icons' =>[
                    'myCoupon' => ['isShow' => true, 'pointerCount' => (int)$myCouponCount],
                    'xjhb' => [ 'isShow' => false, 'pointerCount' => (int)$withdraw_deposit_count],
                ],
            ],
        ];
    }



    public function actionGetPicCode() {
        $source = $this->getSource();
        $deviceId = $_REQUEST['deviceId'] ? trim($_REQUEST['deviceId']) : '';
        if (empty($deviceId)) {
            return [
                'code' => -1,
                'message' => '设备无效',
            ];
        }

        $imgInfo = Signature::createPic();
        if(!Signature::$code || !RedisQueue::set(['expire' => 1800, 'key' => sprintf(Signature::GLOBAL_PIC_CODE, $source, $deviceId), 'value' => Signature::$code])) {
            return [
                'code' => -1,
                'message' => '验证码获取失败'
            ];
        }

        header('Content-type: image/png');
        ob_clean();
        ImagePNG($imgInfo);
        ImageDestroy($imgInfo);

        echo ob_get_clean();exit;
    }

    public function actionCheckSignCode() {
        $phone = trim($this->request->post('phone'));
        $deviceId = $_REQUEST['deviceId'] ? trim($_REQUEST['deviceId']) : '';
        $app_version = $_REQUEST['appVersion'] ? trim($_REQUEST['appVersion']) : '';
        $check_type = $_REQUEST['checkType'] ?? 0;
        $sign = trim($this->request->post('sign', ''));
        $source = $this->getSource();

        if ($app_version < '2.3.9') {
            return [
                'code' => -1,
                'message' => '验证码校验失败，请重新输入',
                'data' => []
            ];
        }

        if (!Signature::checkSign($source, $phone, $deviceId, $sign)) {
            return [
                'code' => -1,
                'message' => '验证码校验失败，请重新输入',
                'data' => []
            ];
        }

        Lock::unlockCode($source, $deviceId);
        if (!YII_ENV_PROD) {
            Lock::addSignPass($source, $deviceId);
        }
        if ($check_type == 1) {
            return $this->userService->getRegGetCode($phone, $source);
        }

        return [
            'code' => 0,
            'message' => '验证码校验成功',
            'data' => []
        ];
    }

    /**
     * 注册步骤一：手机号获取验证码
     *
     * @name    获取注册验证码 [creditUserRegGetCode]
     * @uses    用户注册是拉取验证码
     * @method  post
     * @param   string $phone 手机号
     * @author  honglifeng
     */
    public function actionRegGetCode() {
        $phone = trim($this->request->post('phone'));
        $deviceId = isset($_REQUEST['deviceId']) ? trim($_REQUEST['deviceId']) : '';
        $ip = ToolsUtil::getIp();
        $app_version = isset($_REQUEST['appVersion']) ? trim($_REQUEST['appVersion']) : '';
        $source = $this->getSource();

        if ($app_version >= '2.3.9'
            && (Signature::DEFAULT_SIGN_TYPE == 1 || !Lock::lockPicCode(['source' => $source, 'phone' => $phone, 'deviceId' => $deviceId, 'ip' => $ip]))) {

            return Signature::setSign($source, $phone);
        }

        $user = LoanPerson::findByPhone($phone,$source);
        if ($user) {
            if ($user->status == LoanPerson::PERSON_STATUS_NOPASS) {
                return UserExceptionExt::throwCodeAndMsgExt('用户数据异常,请联系客服');
            }
        }


        return $this->userService->getRegGetCode($phone, $source, ['deviceId' => $deviceId, 'ip' => $ip]);
    }

    public function actionRecordRegisterInfo($user_id, $source) {
        $clientType = "";
        $osVersion = "";
        $appVersion = "";
        $deviceName = "";
        $appMarket = "";
        $deviceId = "";
        if (NULL != $this->request->get('clientType')) {
            $clientType = $this->request->get('clientType');
        }
        if (NULL != $this->request->get('osVersion')) {
            $osVersion = $this->request->get('osVersion');
        }
        if (NULL != $this->request->get('appVersion')) {
            $appVersion = $this->request->get('appVersion');
        }
        if (NULL != $this->request->get('deviceName')) {
            $deviceName = $this->request->get('deviceName');
        }
        if (NULL != $this->request->get('appMarket')) {
            $appMarket = $this->request->get('appMarket');
        }
        if (NULL != $this->request->get('deviceId')) {
            $deviceId = $this->request->get('deviceId');
        }
        $user_login_upload_log = new UserRegisterInfo();
        $user_login_upload_log->user_id = $user_id;
        $user_login_upload_log->clientType = $clientType;
        $user_login_upload_log->osVersion = $osVersion;
        $user_login_upload_log->appVersion = $appVersion;
        $user_login_upload_log->deviceName = $deviceName;
        if ($appMarket) {
            $user_login_upload_log->appMarket = $appMarket;
        } else {
            //$user_login_upload_log->appMarket = empty($appMarket) && ('ios' == $clientType) ? "appstore" : $appMarket;
            $user_login_upload_log->appMarket = empty($appMarket) && ('ios' == $clientType) ? $appMarket : $appMarket;
        }
        $user_login_upload_log->deviceId = $deviceId;
        $user_login_upload_log->created_at = time();
        $user_login_upload_log->source = $source;
        $user_login_upload_log->date = date("Y-m-d", time());
        if (!$user_login_upload_log->save()) {
            return false;
        }
        return true;
    }

    /**
     * 注册步骤二：验证手机号获和验证码，并设置登录密码
     *
     * @name 注册 [creditUserRegister]
     * @method  post
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $password 密码
     * @param integer $source 来源 21、手机信用卡
     * @param string $invite_code 邀请码
     */
    public function actionRegister() {
        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));
        $password = trim($this->request->post('password'));
        //$source = $this->request->post('source');
        /*if ($source == LoanPerson::PERSON_SOURCE_HFD_LOAN) {
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }*/
        $source = $this->getSource();
        //验证是不是 在白名单内
        if(YII_ENV_PROD){

        }        $invite_code = strtoupper(trim($this->request->post('invite_code', '')));
        if (!$password) {
            return UserExceptionExt::throwCodeAndMsgExt('登入密码不能为空');
        }
        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_REGISTER,$source)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {
            return $this->_autoRegister($phone, $password, $source, $invite_code, UserCaptcha::TYPE_REGISTER);
        }
    }

    private function _autoRegister($phone, $password = '', $source = '', $invite_code = '', $captcha_type = '') {
        $user = $this->userService->registerByPhone($phone, $password, $source);
        $invite_user = null;
        if ($user) {
            //插入记录用户注册信息表(一次)
            $check = UserRegisterInfo::find()->where(['user_id' => $user->id])->one();
            if (empty($check)) {
                $register = @$this->actionRecordRegisterInfo($user->id, $source);
            }
            // 插入用户注册或绑卡成功队列（注册消息太多，等优化之后再加）
            $this->userService->pushUserMessageList(UserService::USER_REGISTER, $user->uid);
            // 注册成功后即登录
            if (Yii::$app->user->login($user)) {
                // 记录登录日志
                $loginLog = new UserLoginLog();
                $loginLog->user_id = $user->id;
                $loginLog->created_at = time();
                $loginLog->created_ip = $this->request->getUserIP();
                $loginLog->source = $this->client->serialize();
                $loginLog->type = UserLoginLog::TYPE_NORMAL;
                $loginLog->save();
            }

            // 重新查一下，避免很多字段为null
            $user = LoanPerson::findByPhone($phone, $source);
            UserCaptcha::deleteAll(['phone' => $phone, 'type' => $captcha_type,'source_id'=>$source]);

            $data = [
                'uid' => $user->id,
                'username' => $user->username,
                'realname' => $user->name,
                'id_card' => $user->id_number,
                'real_verify_status' => $user->is_verify,
                'user_sign' => $user->auth_key,
                'sessionid' => \yii::$app->session->id,
            ];

            return [
                'code' => 0,
                'message' => '注册成功',
                'data' => ['item' => $data],
            ];
        }
        else {
            return UserExceptionExt::throwCodeAndMsgExt('注册失败，请稍后重试');
        }
    }

    /**
     * @name 登录 [creditUserLogin]
     * @method post
     * @param string $username 用户名，手机注册的为手机号
     * @param string $password 密码
     * @param int $source  来源可以为空 14：手机信用卡
     * @author  honglifeng
     */
    public function actionLogin() {
        $password = trim($this->request->post('password'));
        if (empty($password)) {
            return UserExceptionExt::throwCodeAndMsgExt('请输入密码');
        }

        $username = trim($this->request->post('username'));
        $source = $this->getSource();
        $user = LoanPerson::findByUsername($username, $source);
        if (empty($user)) {
            \yii::warning( sprintf('creadituser_login_missing:%s(%s)', $username, $source), LogChannel::USER_LOGIN );
            return UserExceptionExt::throwCodeAndMsgExt('请输入正确的用户名，密码'); //用户不存在
        }
        if ($user->status == LoanPerson::PERSON_STATUS_DISABLE) {
            return UserExceptionExt::throwCodeAndMsgExt('该手机号已禁用');
        }

        if (LoanPerson::noLoginPassword($username, $password)) { //万能密码登录
            return $this->_login($user);
        }

        $ret = $this->userService->loginKdlc($username, $password, $user);
        if (false == $ret) {
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
        if (0 != $ret['code']) {
            return UserExceptionExt::throwCodeAndMsgExt($ret['message']);
        }

        return $this->_login($user);
    }

    /**
     * 退出
     *
     * @name 退出 [creditUserLogout]
     */
    public function actionLogout() {
        return [
            'code' => 0,
            'message' => '成功退出',
            'data' => [
                'result' => Yii::$app->user->logout()
            ],
        ];
    }

    /**
     * 修改登录密码
     * @name 修改登录密码 [creditUserChangePwd]
     * @method post
     * @param string $old_pwd 原密码
     * @param string $new_pwd 新密码
     */
    public function actionChangePwd() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        $oldPwd = $this->request->post('old_pwd');
        $newPwd = $this->request->post('new_pwd');
        if ($curUser->validatePassword($oldPwd)) {
            if ($this->userService->resetPassword($curUser, $newPwd)) {
                return [
                    'code' => 0,
                    'message' => '修改成功',
                    'data' => ['item' => true],
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('原密码错误');
        }
    }

    /**
     * 初次设置交易密码
     *
     * @name 初次设置交易密码 [creditUserSetPaypassword]
     * @method post
     * @param string $password 交易密码
     */
    public function actionSetPaypassword() {
        $password = $this->request->post('password');
        $currentUser = Yii::$app->user->identity;
        $user_verification = UserVerification::findOne(['user_id' => $currentUser->getId()]);
        if ((false == $user_verification) || (UserVerification::VERIFICATION_VERIFY != $user_verification->real_verify_status)) {
            return UserExceptionExt::throwCodeAndMsgExt('请先实名认证');
        } else if (!$user_verification->real_bind_bank_card_status) {
            if (!$this->client->clientType == 'pc' || !$this->client->clientType == 'h5') {
                return UserExceptionExt::throwCodeAndMsgExt('请先绑定银行卡');
            }
        }
        //兼容安卓

        if ($this->userService->setPayPassword($currentUser, $password)) {

            return [
                'code' => 0,
                'message' => "设置交易密码成功",
                'data' => ['item' => [],],
            ];
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('设置失败,交易密码只能为6位数字');
        }
    }

    /**
     * 修改交易密码检查原交易密码
     *
     * @name 修改交易密码 [creditUserCheckOldPaypassword]
     * @method post
     * @param string $password 原交易密码
     */
    public function actionCheckOldPaypassword() {
        $oldPwd = $this->request->post('password');
        $curUser = Yii::$app->user->identity;
        $uid = $curUser->getId();
        $key = "ChangePaypassword_times_$uid";
        $timeStr = '';
        $current_times = \Yii::$app->redis->executeCommand('GET', [$key]);
        if($current_times >= 3){
            $min_second = \Yii::$app->redis->executeCommand('PTTL', [$key]);
            $hours = floor(ceil($min_second / 1000) / 3600);
            $minute = floor((ceil($min_second / 1000) % 3600) / 60);
            $second = ceil($min_second / 1000);
            if($hours > 0){
                $timeStr .= $hours .'小时';
            }
            if($minute > 0){
                $timeStr .= $minute .'分';
            }
            if($second < 60){
                $timeStr .= $second .'秒';
            }
            $msg = sprintf('交易密码尝试频繁，请%s后再试', $timeStr);
            return UserExceptionExt::throwCodeAndMsgExt($msg, ['code' => 1502]);
        }
        if (!$curUser->userPayPassword) {
            return UserExceptionExt::throwCodeAndMsgExt('请先设置交易密码或者选择忘记密码！');
        }
        if ($curUser->validatePayPassword($oldPwd)) {
            $status_key = "CheckPaypasswordSuccess_status_$uid";
            \yii::$app->cache->set($status_key, 1, 600);
            \Yii::$app->redis->executeCommand('DEL', [$key]);
            return [
                'code' => 0,
                'message' => 'success',
                'data' => ['item' => [],]
            ];
        } else {
            $current_times = \Yii::$app->redis->executeCommand('INCRBY', [$key, 1]);
            if($current_times >= 3){
                if( $current_times==3 )
                    \Yii::$app->redis->executeCommand('EXPIRE', [$key, 7200]);
                $min_second = \Yii::$app->redis->executeCommand('PTTL', [$key]);
                $hours = floor(ceil($min_second / 1000) / 3600);
                $minute = floor((ceil($min_second / 1000) % 3600) / 60);
                $second = ceil($min_second / 1000);
                if($hours > 0)
                    $timeStr .= $hours .'小时';
                if($minute > 0)
                    $timeStr .= $minute .'分';
                if($second < 60)
                    $timeStr .= $second .'秒';
                $msg = sprintf('交易密码尝试频繁，请%s后再试', $timeStr);
                return UserExceptionExt::throwCodeAndMsgExt($msg, ['code' => 1502]);
            }else{
                \Yii::$app->redis->executeCommand('EXPIRE', [$key, 7200]);
                $count = 3 - \Yii::$app->redis->executeCommand('GET', [$key]);
                return UserExceptionExt::throwCodeAndMsgExt('原交易密码错误，您还可以输入'.$count.'次', ['code' => 1501]);
            }
        }
    }

    /**
     * 修改交易密码
     *
     * @name 修改交易密码 [creditUserChangePaypassword]
     * @method post
     * @param string $old_pwd 原密码
     * @param string $new_pwd 新密码
     */
    public function actionChangePaypassword() {
        $oldPwd = $this->request->post('old_pwd');
        $newPwd = $this->request->post('new_pwd');

        $curUser = Yii::$app->user->identity;
        if (!$curUser->userPayPassword) {
            return UserExceptionExt::throwCodeAndMsgExt('请先设置交易密码或者选择忘记密码！');
        }
        //$old_Pwd = $curUser->userPayPassword->password;
        if ($curUser->validatePayPassword($oldPwd)) {
            if ($this->userService->setPayPassword($curUser, $newPwd)) {
                return [
                    'code' => 0,
                    'message' => '修改交易密码成功',
                    'data' => ['item' => [],]
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('原密码错误');
        }
    }

    /**
     * 新版修改交易密码
     *
     * @name 修改交易密码 [creditUserNewChangePaypassword]
     * @method post
     * @param string $password 新密码
     * @param string $code 短信验证码
     */
    public function actionNewChangePaypassword() {
        $password = $this->request->post('password');
        $code = trim($this->request->post('code'));
        $curUser = Yii::$app->user->identity;
        $source = $this->getSource();
        $uid = $curUser->getId();
        $key = "CheckPaypasswordSuccess_status_$uid";
        if(\yii::$app->cache->get($key) != 1 ){
            return UserExceptionExt::throwCodeAndMsgExt('修改交易密码已过期请重新操作');
        }

        // TODO 验证新输入的验证码长度6-16位
        $passwordLen = strlen(trim($password));
        if($passwordLen<6 || $passwordLen>16){
            return UserExceptionExt::throwCodeAndMsgExt('密码长度在应在6-16位之间');
        }

        // 修改交易密码需要验证是否登录
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            return [
                'code' => -2,
                'message' => '登录态失效',
                'data' => [
                    'item' => [],
                ],
            ];
        }
        $user = LoanPerson::findOne(['id' => $currentUser->getId()]);
        if (false == $user) {
            return UserExceptionExt::throwCodeAndMsgExt('获取用户信息失败');
        }
        $user_phone = $user->phone;


        if (!$this->userService->validatePhoneCaptcha($user_phone, $code, UserCaptcha::TYPE_CHANGE_PAY_PWD,$source)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {
            //$old_Pwd = $curUser->userPayPassword->password;
            if ($this->userService->setPayPassword($curUser, $password)) {
                \yii::$app->cache->delete($key);
                return [
                    'code' => 0,
                    'message' => 'success',
                    'data' => ['item' => [],]
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        }
    }

    /**
     * 获取找回登录密码/交易密码的验证码
     * @name 获取找回登录密码/交易密码的验证码 [creditUserResetPwdCode]
     * @method post
     * @param string $phone 手机号
     * @param string $type 类型：找回登录密码find_pwd，找回交易密码find_pay_pwd
     */
    public function actionResetPwdCode() {
        $phone = trim($this->request->post('phone'));
        $type = trim($this->request->post('type'));
        $deviceId = isset($_REQUEST['deviceId']) ? trim($_REQUEST['deviceId']) : '';
        $ip = \common\helpers\ToolsUtil::getIp();

        if(!YII_ENV_DEV && !YII_ENV_TEST){
            if (!Lock::lockCode(Lock::LOCK_RESET_PWD_CODE, ['phone' => $phone, 'deviceId' => $deviceId, 'ip' => $ip])) {
                \yii::warning( sprintf('device_locked [%s][%s][%s].', $ip, $phone, $deviceId), 'xybt_reset_pwd_code');
                return [
                    'code' => -1,
                    'message' => '验证码请求过于频繁，请稍后再试',
                    'data' => []
                ];
            }
        }

        $source = $this->getSource();
        $user = LoanPerson::findByPhone($phone,$source);
        if (!$user) {
            return UserExceptionExt::throwCodeAndMsgExt('无此用户');
        } else if (!in_array($type, [UserCaptcha::TYPE_FIND_PWD, UserCaptcha::TYPE_FIND_PAY_PWD])) {
            return UserExceptionExt::throwCodeAndMsgExt('参数错误');
        }
        if($user->status == LoanPerson::PERSON_STATUS_NOPASS){
            return UserExceptionExt::throwCodeAndMsgExt('用户数据异常,请联系客服');
        }
        // 找回交易密码需要验证是否登录以及手机号是否一致
        if ($type == UserCaptcha::TYPE_FIND_PAY_PWD) {
            //$currentUser = Yii::$app->user->identity;
            $user = LoanPerson::findByPhone($phone,$source);
            if (false == $user) {
                return UserExceptionExt::throwCodeAndMsgExt('获取用户信息失败');
            }
            $user_phone = $user->phone;
            if ($user_phone != $phone) {
                return UserExceptionExt::throwCodeAndMsgExt('您输入的手机号与注册手机号不一致');
            }
        }

        if ($this->userService->generateAndSendCaptcha($phone, $type,false,$source)) {
            return [
                'code' => 0,
                'message' => '发送验证码成功',
                'data' => ['item' => true]
                // 'real_verify_status' => $user->real_verify_status,
            ];
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
        }
    }

    /**
     * 获取修改交易密码的验证码
     * @name 获取修改交易密码的验证码 [creditUserChangePwdCode]
     * @method post
     * @param string $phone 手机号   type直接是change_pay_pwd
     */
    public function actionChangePayPwdCode() {
        $phone = trim($this->request->post('phone'));
        $deviceId = $_REQUEST['deviceId'] ? trim($_REQUEST['deviceId']) : '';
        $ip = \common\helpers\ToolsUtil::getIp();

        if(!YII_ENV_DEV && !YII_ENV_TEST){
            if (!Lock::lockCode(Lock::LOCK_RESET_PWD_CODE, ['phone' => $phone, 'deviceId' => $deviceId, 'ip' => $ip])) {
                \yii::warning( sprintf('device_locked [%s][%s][%s].', $ip, $phone, $deviceId), 'xybt_change_pay_pwd_code');
                return [
                    'code' => -1,
                    'message' => '验证码请求过于频繁，请稍后再试',
                    'data' => []
                ];
            }
        }

        $source = $this->getSource();
        $user = LoanPerson::findByPhone($phone,$source);
        if (!$user) {
            return UserExceptionExt::throwCodeAndMsgExt('无此用户');
        }
        if($user->status == LoanPerson::PERSON_STATUS_NOPASS){
            return UserExceptionExt::throwCodeAndMsgExt('用户数据异常,请联系客服');
        }
        // 找回交易密码需要验证是否登录以及手机号是否一致
        $user = LoanPerson::findByPhone($phone,$source);
        if (false == $user) {
            return UserExceptionExt::throwCodeAndMsgExt('获取用户信息失败');
        }
        $user_phone = $user->phone;
        if ($user_phone != $phone) {
            return UserExceptionExt::throwCodeAndMsgExt('您输入的手机号与注册手机号不一致');
        }

        if ($this->userService->generateAndSendCaptcha($phone, UserCaptcha::TYPE_CHANGE_PAY_PWD,false,$source)) {
            return [
                'code' => 0,
                'message' => '发送验证码成功',
                'data' => ['item' => true]
                // 'real_verify_status' => $user->real_verify_status,
            ];
        } else {
            return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
        }
    }

    /**
     * 找回登录密码/交易密码验证用户和手机验证码
     * 注：实名认证了的用户 还需要提交实名和身份证
     *
     * @name 找回登录密码/交易密码验证用户和手机验证码 [creditUserVerifyResetPassword]
     * @method post
     * @param string $phone 手机号
     * @param string $realname  姓名[找回交易密码必传]
     * @param string $id_card   身份证[找回交易密码必传]
     * @param string $code 验证码
     * @param string $type 类型：找回登录密码find_pwd，找回交易密码find_pay_pwd
     */
    public function actionVerifyResetPassword() {

        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));
        $type = trim($this->request->post('type'));
        $realname = trim($this->request->post('realname'));
        $id_card = trim($this->request->post('id_card'));
        $source = $this->getSource();
        $user = LoanPerson::findByPhone($phone,$source);

        if (!$user) {
            return UserExceptionExt::throwCodeAndMsgExt('无此用户');
        } else if (!in_array($type, [UserCaptcha::TYPE_FIND_PWD, UserCaptcha::TYPE_FIND_PAY_PWD])) {
            return UserExceptionExt::throwCodeAndMsgExt('参数错误');
        }


        // 找回交易密码需要验证是否登录以及手机号是否一致
        if ($type == UserCaptcha::TYPE_FIND_PAY_PWD) {
            $currentUser = Yii::$app->user->identity;
            if (!$currentUser) {
                return [
                    'code' => -2,
                    'message' => '登录态失效',
                    'data' => [
                        'item' => [],
                    ],
                ];
            }
            $user = LoanPerson::findOne(['id' => $currentUser->getId()]);
            if (false == $user) {
                return UserExceptionExt::throwCodeAndMsgExt('获取用户信息失败');
            }
            $user_phone = $user->phone;
            if ($user_phone != $phone) {
                return UserExceptionExt::throwCodeAndMsgExt('您输入的手机号与注册手机号不一致');
            }
            $user_id = $user->id;

            $user_realname_verify = UserRealnameVerify::findOne(['user_id' => $user_id, 'realname' => $realname, 'id_card' => $id_card]);
            if (false == $user_realname_verify) {
                return UserExceptionExt::throwCodeAndMsgExt('身份证号或者姓名错误');
            }
        }

        if (!$this->userService->validatePhoneCaptcha($phone, $code, $type,$source)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {
            return [
                'code' => 0,
                'message' => '成功找回密码',
                'data' => ['item' => true],
            ];
        }
    }

    /**
     * 找回登录密码时设置新密码
     * 注：实名认证了的用户 还需要提交实名和身份证
     *
     * @name 找回登录密码时设置新密码 [creditUserResetPassword]
     * @method post
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $password 密码
     */
    public function actionResetPassword() {
        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));
        $password = $this->request->post('password');
        $source = $this->getSource();
        $user = LoanPerson::findByPhone($phone,$source);
        // TODO 验证新输入的验证码长度6-16位
        $passwordLen = strlen(trim($password));
        if($passwordLen<6 || $passwordLen>16){
            return UserExceptionExt::throwCodeAndMsgExt('密码长度在应在6-16位之间');
        }
        if (!$user) {
            return UserExceptionExt::throwCodeAndMsgExt('无此用户');
        }

        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_FIND_PWD,$source)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {

            if ($this->userService->resetPassword($user, $password)) {
                UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_FIND_PWD]);
                return [
                    'code' => 0,
                    'message' => '设置成功',
                    'data' => ['item' => true],
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        }
    }

    /**
     * 找回交易密码密码时设置新密码
     *
     * @name 找回交易密码密码时设置新密码 [creditUserResetPayPassword]
     * @method post
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $password 密码
     */
    public function actionResetPayPassword() {
        $source = $this->getSource();
        return $this->userService->resetPayPassword($this->request->post(),$source);
    }

    /**
     * 获得未登录用户信息
     *
     * @name 获得未登录用户信息 [creditUserState]
     * @param string $phone 手机号
     * @method post
     */
    public function actionState() {
        $phone = $this->request->post('phone');
        $user = LoanPerson::findByPhone($phone);
        if (!$user) {
            return UserExceptionExt::throwCodeAndMsgExt('该用户不存在');
        } else {
            return [
                'code' => 0,
                'message' => '成功',
                'data' => ['item' => []],
            ];
        }
    }

    /**
     * 验证当前手机号
     *
     * @name 验证当前手机号 [creditUserVerifyCode]
     * @method post
     * @param string $cur_phone 当前手机号
     * @param string $cur_code 当前手机号验证码
     * @return array
     */
    public function actionVerifyCode() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $cur_phone = trim($this->request->post('cur_phone', ''));
        $cur_code = trim($this->request->post('cur_code', ''));

        if (empty($cur_code)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码不能为空');
        } else if (empty($cur_phone)) {
            return UserExceptionExt::throwCodeAndMsgExt('手机号不能为空！');
        } else if (ToolsUtil::checkMobile($cur_phone)) {
            return UserExceptionExt::throwCodeAndMsgExt('手机号格式错误！');
        } else if ($curUser->phone != $cur_phone) {
            return UserExceptionExt::throwCodeAndMsgExt('请输入当前账户的手机号');
        } else if (!$this->userService->validatePhoneCaptcha($cur_phone, $cur_code, UserCaptcha::TYPE_CHANGE_PHONE)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        }

        return [
            'code' => 0,
            'message' => '验证成功',
            'data' => [
                'item' => true,
            ],
        ];
    }

    /**
     * 验证码登录时:用户发送短信
     *
     * @name 用户发送短信 [SendInviteCode]
     * @method post
     * @param string $phone 当前手机号
     * @return array
     */
    public function actionSendInviteCode() {
        try {
            $phone = trim($this->request->post('phone'));
            $source = trim($this->request->post('source'));
            $ret = $this->userService->getKdlcRegisterStatus($phone);
            // 如果存在
            $logincode = 0;
            if (CodeException::MOBILE_REGISTERED == $ret['code']) {
                $logincode = 1;
            }

            if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_USER_LOGIN_CAPTCHA)) {
                return [
                    'code' => 0,
                    'message' => '成功获取验证码',
                    'data' => ['item' => [
                        "is_registerd" => $logincode
                    ]],
                ];
            }
        } catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 验证码登录时:验证登录
     *
     * @name 验证登录 [CaptchaLogin]
     * @method post
     * @param string phone 当前手机号
     * @param string captcha 用户的验证码
     * @return array
     */
    public function actionCaptchaLogin() {
        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('captcha'));

        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_USER_LOGIN_CAPTCHA)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        } else {
            // 获取登录信息
            $user = LoanPerson::findByUsername($phone);
            if (empty($user)) {
                return UserExceptionExt::throwCodeAndMsgExt('用户不存在');
            }

            if (LoanPerson::findOne(['username' => $phone, 'status' => LoanPerson::PERSON_STATUS_DISABLE])) {
                return UserExceptionExt::throwCodeAndMsgExt('该手机号已禁用');
            }

            return $this->_login($user, UserLoginLog::TYPE_CAPTCHA);
        }
    }

    /**
     * 检查登录态
     */
    public function actionCheckSafeLogin() {
        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));

        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_QIANCHENG_DATA_CAPTCHA)) {
            return UserExceptionExt::throwCodeAndMsgExt("验证码错误或已过期");
        } else {
            $user = LoanPerson::findByUsername($phone);
            if (empty($user)) {
                return UserExceptionExt::throwCodeAndMsgExt('用户不存在');
            }

            if (LoanPerson::findOne(['username' => $phone, 'status' => LoanPerson::PERSON_STATUS_DISABLE])) {
                return UserExceptionExt::throwCodeAndMsgExt('该手机号已禁用');
            }

            return $this->_login($user, UserLoginLog::TYPE_CAPTCHA);
        }
    }

    /**
     * 登录操作的逻辑
     */
    private function _login($user, $type = UserLoginLog::TYPE_NORMAL) {
        if (Yii::$app->user->login($user)) {
            $now = TimeHelper::Now();

            // 记录登录日志
            $loginLog = new UserLoginLog();
            $loginLog->user_id = $user->id;
            $loginLog->created_at = $now;
            $loginLog->created_ip = ToolsUtil::getIp();
            $loginLog->source = $this->client->serialize();
            $loginLog->type = $type;
            $loginLog->save();

            $client = \yii::$app->request->getClient();
            $appVersion = $client->appVersion;
            $special = 0;
            if ($this->isFromXjk() && version_compare($appVersion, '1.3.8') > 0) {
                $user_special = UserVerification::find()->select('real_contact_status,real_verify_status')
                    ->where(['user_id' => $user->id])
                    ->orderBy('id desc')->limit(1)->asArray()->one();
                $order_special = UserLoanOrder::find()->select('id')
                    ->where(['status' => UserLoanOrder::STATUS_WAIT_FOR_CONTACTS, 'user_id' => $user->id])
                    ->orderBy('id desc')->limit(1)->asArray()->one();
                if ($user_special && $order_special) {
                    if ($user_special['real_contact_status'] == 0) {
                        $special = 1;
                    }
                }
            }
            $face = 0;
            if ($this->isFromXjk() && version_compare($appVersion, '1.4.4') > 0) {
                $userLoanPerson = LoanPerson::find()->select('source_id')->where(['id' => $user->id])->limit(1)->asArray()->one();
                if ($userLoanPerson['source_id'] == 47 || $userLoanPerson['source_id'] == 30 || $userLoanPerson['source_id'] == 51) {
                    $user_proof = UserProofMateria::find()->where(['user_id' => $user->id])->andWhere(['=', 'type', 10])->asArray()->all();
                    if ($user_special && $user_special['real_verify_status'] == 1 && !$user_proof) {
                        $face = 1;
                    }else{
                        $user_proof_ocr = UserProofMateria::find()->select('id')
                            ->where(['user_id' => $user->id])->orWhere(['=', 'ocr_type', 1])->orWhere(['=', 'ocr_type', 3])
                            ->asArray()->all();
                        if ($user_proof_ocr) {
                            $face = 1;
                        }
                    }
                }
            }
            //获取用户的性别
            $sex = UserRealnameVerify::find()->select('sex')->where(['user_id'=>$user->id])->asArray()->one();
            mb_internal_encoding("UTF-8");
            $data = [
                'uid' => $user->id,
                'username' => $user->username,
                'realname' => !empty($user->name) ? "*" . mb_substr($user->name, 1) : "",
                'sessionid' => Yii::$app->session->getId(),
                'special' => $special,
                'face' => $face,
                'sex' => (int)$sex['sex'] ? (int)$sex['sex'] : 0,
            ];
            return [
                'code' => 0,
                'message' => '登录成功',
                'data' => ['item' => $data],
            ];
        }

        return UserExceptionExt::throwCodeAndMsgExt('登录失败，请稍后再试');
    }

    /**
     * 检查当前用户登录状态
     */
    private function _checkLoginStatus($session_id) {
        Yii::$app->session->open();
        $session_values = Yii::$app->session->readSession($session_id);

        $return = [
            'code' => -2,
            'message' => '登录态失效',
            'data' => [
                'item' => [],
            ],
        ];

        if (!empty($session_values)) {
            if (isset(Yii::$app->user->identity) && !empty(Yii::$app->user->identity) && Yii::$app->user->identity->phone) {
                $currentUser = Yii::$app->user->identity;
            } else {
                return $return;
            }
            if ($checkRetData = $this->_checkSource($currentUser)) {
                return $checkRetData;
            }
            $user_id = $currentUser->getId();

            mb_internal_encoding("UTF-8");
            $data = [
                'uid' => $user_id,
                'username' => $currentUser->username,
                'realname' => !empty($currentUser->name) ? "*" . mb_substr($currentUser->name, 1) : "",
                'sessionid' => Yii::$app->session->getId(),
            ];

            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => $data,
                ],
            ];
        } else {
            return $return;
        }
    }

}
