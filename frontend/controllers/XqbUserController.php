<?php
namespace frontend\controllers;

use common\components\Session;
use common\models\ChannelGeneralCount;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserCredit;
use common\models\UserDevice;
use common\models\UserHfdInfo;
use common\models\UserInstallmentCredit;
use common\models\UserLevelMission;
use common\models\UserRegisterInfo;
use common\models\UserRentCredit;
use common\models\UserVerification;
use common\services\InfoService;
use Yii;
use yii\filters\AccessControl;
use common\services\UserService;
use common\models\UserCaptcha;
use common\models\UserLoginLog;
use yii\web\Response;
use yii\captcha\CaptchaValidator;
use common\models\UserPhoneChange;
use common\helpers\Util;
use common\exceptions\UserExceptionExt;
use common\exceptions\CodeException;
use common\models\LoanPersonInvite;
use common\helpers\ToolsUtil;
use common\helpers\MessageHelper;
use common\api\RedisQueue;
use common\models\PromotionMobileUpload;
use common\helpers\Lock;
use common\base\LogChannel;
use common\models\VisitStat;
use common\models\WeixinUser;
use yii\helpers\Html;

/**
 * User controller
 */
class XqbUserController extends BaseController
{
    protected $userService;

    /**
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
                'except' => [
                    'captcha',
                    'check-verify',
                    'login',
                    'logout',
                    'quick-login',
                    'reg-get-audio-code',
                    'reg-get-code',
                    'register',
                    'reset-pwd-code',
                    'reset-password',
                    'state',
                    'verify',
                    'verify-reset-password',
                    'tj-app-down',
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
            'verify' => [
                'class' => \common\captcha\NumberCaptchaAction::class,
                'testLimit' => 1,
                'height' => 75,
                'width' => 150,
                'padding' => 0,
                'minLength' => 4,
                'maxLength' => 4,
                'offset'=>2,        //设置字符偏移量 有效果
            ],
        ];
    }

    /**
     * 新渠道注册页面 2017-09-20
     * 说明：检查验证码  如果验证码合法发送短信
     * @param $phone
     * @param $source_tag
     * @param $channel
     * @return array
     */
    public function regVerifyCode($phone,$source_tag,$channel) {
        if (ToolsUtil::isTransparentProxy()) {
            \yii::warning(sprintf('xqb_user_reg_get_code fake_success: %s', $phone), LogChannel::SMS_FAKE_SUCCESSS);
            return [
                'code' => 0,
                'message' => '成功获取验证码',
                'data' => [],
            ];
        }

        $ip = ToolsUtil::getIp();
        try {
            $pro = PromotionMobileUpload::findOne(['mobile' => $phone]);
            $loan_person = LoanPerson::findOne(['phone' => $phone]);
            if (!$pro && $source_tag && !$loan_person) { //该用户没有注册过
                $lock_name = 'Page_AppReg_lock'.$phone;
                if (Lock::get($lock_name, 30)) {
                    $PromotionMobileUpload = new PromotionMobileUpload();
                    $PromotionMobileUpload->mobile = $phone;
                    $PromotionMobileUpload->channel = $source_tag;
                    $PromotionMobileUpload->status = PromotionMobileUpload::ISTATUS_DEFAULT;
                    $PromotionMobileUpload->created_by = PromotionMobileUpload::CREATED_BY_ONE;
                    if ($PromotionMobileUpload->save()) {
                        Lock::del($lock_name);
                    }
                }
            }
            $ret = $this->userService->getKdlcRegisterStatus($phone,$channel);
            if (CodeException::MOBILE_REGISTERED == $ret['code']) {
                $loan_person = $ret['loan_person'];
                if ($loan_person) {
                    $user_password = $this->userService->getUserPassword($loan_person->id);
                    // done : 将自动注册转成通过
                    if (!$user_password || $loan_person->status == LoanPerson::STATUS_TO_REGISTER) {
                        if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_REGISTER, false, $channel)) {
                            return [
                                'code' => 0,
                                'message' => '成功获取验证码',
                                'data' => ['item' => [], 'inner' => __LINE__,],
                            ];
                        }
                        else {
                            return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                        }
                    }
                    if ($user_password) {
                        return [
                            'code' => CodeException::MOBILE_REGISTERED,
                            'message' => '手机号已注册',
                            'data' => [],
                        ];
//                        return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::MOBILE_REGISTERED]);
//                        return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::MOBILE_REGISTERED], ['code' => CodeException::MOBILE_REGISTERED]);
                    }
                }
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::KDLC_USER], ['code' => CodeException::MOBILE_REGISTERED]);
            }
            else if (0 == $ret['code']) {
                if(!YII_ENV_DEV && !YII_ENV_TEST){
                    if (!Lock::lockCode(Lock::LOCK_H5_USER_REG_CODE, ['phone' => $phone, 'ip' => $ip])) {
                        \yii::warning( sprintf('device_locked [%s][%s].', $ip, $phone), 'channel.user.reg' );
                        return [
                            'code' => -1,
                            'message' => '验证码请求过于频繁，请1分钟后再试',
                            'data' => []
                        ];
                    }
                }
                if ($this->userService->generateAndSendCaptcha($phone, UserCaptcha::TYPE_REGISTER, false, $channel)) {
                    return [
                        'code' => 0,
                        'message' => '成功获取验证码',
                        'data' => ['item' => [], 'inner' => __LINE__,],
                    ];
                }
                else {
                    return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                }
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt($ret['message']);
            }
        }
        catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 注册步骤一：手机号获取验证码
     *     [170529] resp 长度 86，表示成功获取
     * @name    获取注册验证码 [userRegGetCode]
     * @uses    用户注册是拉取验证码
     * @method  post
     * @param   string $phone 手机号
     */
    public function actionRegGetCode() {
        $phone = trim($this->request->post('phone', ''));
        if (! MessageHelper::getType($phone)) {
            \yii::warning(sprintf('xqb_user_reg_get_code phone_err: %s', $phone), LogChannel::SMS_REGISTER);
            return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
        }

        if (ToolsUtil::isTransparentProxy()) {
            \yii::warning(sprintf('xqb_user_reg_get_code fake_success: %s', $phone), LogChannel::SMS_FAKE_SUCCESSS);
            return [
                'code' => 0,
                'message' => '成功获取验证码',
                'data' => [],
            ];
        }

        $sess_key = trim(\yii::$app->session->get('reg_sms_key'));
        $key = trim(\yii::$app->request->post('key'));
        if (empty($sess_key) || $sess_key != $key) {
            \yii::warning(sprintf('xqb_user_reg_get_code sess_key_error: %s', $phone), LogChannel::SMS_FAKE_SUCCESSS);
            return UserExceptionExt::throwCodeAndMsgExt('页面已过期，请刷新重试！');
        }
        else if (!Session::validSmsKey($key)) {
            \yii::warning(sprintf('xqb_user_reg_get_code sess_key_invalid: %s', $phone), LogChannel::SMS_FAKE_SUCCESSS);
            return UserExceptionExt::throwCodeAndMsgExt('页面已过期，请刷新重试！');
        }

        $ip = ToolsUtil::getIp();

        /* @var $redis_kj \yii\redis\Connection */
        $redis_kj = \yii::$app->redis;
        $phone_key = "user:reg_get_code:{$phone}";
        if ( $redis_kj->EXISTS($phone_key) ) {
            \yii::warning(sprintf('xqb_user_reg_get_code feq_err: %s', $phone), LogChannel::SMS_REGISTER);
            $redis_kj->hincrby(sprintf('user:reg_get_code_ip:%s', date('ymd')), $ip, 1);
            $redis_kj->hincrby(sprintf('user:reg_get_code_phone:%s', date('ymd')), $phone, 1);
            return [
                'code' => -1,
                'message' => '验证码请求过于频繁，请1分钟后再试',
                'data' => []
            ];
        }
        //else pass...
        $source_tag = trim($this->request->post('source_tag',''));
        try {
            $pro = PromotionMobileUpload::findOne(['mobile' => $phone]);
            $loan_person = LoanPerson::findOne(['phone' => $phone]);
            if (!$pro && $source_tag && !$loan_person) { //该用户没有注册过
                $lock_name = 'Page_AppReg_lock'.$phone;
                if (Lock::get($lock_name, 30)) {
                    $PromotionMobileUpload = new PromotionMobileUpload();
                    $PromotionMobileUpload->mobile = $phone;
                    $PromotionMobileUpload->channel = $source_tag;
                    $PromotionMobileUpload->status = PromotionMobileUpload::ISTATUS_DEFAULT;
                    $PromotionMobileUpload->created_by = PromotionMobileUpload::CREATED_BY_ONE;
                    if ($PromotionMobileUpload->save()) {
                        Lock::del($lock_name);
                    }
                }
            }
            $channel = intval($this->request->post('source_id',LoanPerson::PERSON_SOURCE_MOBILE_CREDIT));

            $ret = $this->userService->getKdlcRegisterStatus($phone,$channel);
            if (CodeException::MOBILE_REGISTERED == $ret['code']) {
                $loan_person = $ret['loan_person'];

                if ($loan_person) {
                    // if(\Yii::$app->controller->isFromApp() && !\Yii::$app->controller->isFromXjk()){ //阻止非极速钱包用户在其他同类app注册
                    //     return UserExceptionExt::throwCodeAndMsgExt('对不起，系统忙!');
                    // }
                    $user_password = $this->userService->getUserPassword($loan_person->id);
                    // done : 将自动注册转成通过
                    if (!$user_password || $loan_person->status == LoanPerson::STATUS_TO_REGISTER) {
                        if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_REGISTER, false, $channel)) {
                            //设置锁
                            $redis_kj->SET($phone_key,1);
                            $redis_kj->EXPIRE($phone_key, 59); # 60秒一次
                            return [
                                'code' => 0,
                                'message' => '成功获取验证码',
                                'data' => ['item' => [], 'inner' => __LINE__,],
                            ];
                        }
                        else {
                            return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                        }
                    }
                    if ($user_password) {
                        return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::MOBILE_REGISTERED], ['code' => CodeException::MOBILE_REGISTERED]);
                    }
                }
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::KDLC_USER], ['code' => CodeException::MOBILE_REGISTERED]);
            }
            else if (0 == $ret['code']) {
                if(!YII_ENV_DEV && !YII_ENV_TEST){
                    if (!Lock::lockCode(Lock::LOCK_H5_USER_REG_CODE, ['phone' => $phone, 'ip' => $ip])) {
                        \yii::warning( sprintf('device_locked [%s][%s].', $ip, $phone), 'channel.user.reg' );
                        return [
                            'code' => -1,
                            'message' => '验证码请求过于频繁，请1分钟后再试',
                            'data' => []
                        ];
                    }
                }
                if ($this->userService->generateAndSendCaptcha($phone, UserCaptcha::TYPE_REGISTER, false, $channel)) {
                    //设置锁
                    $redis_kj->SET($phone_key,1);
                    $redis_kj->EXPIRE($phone_key, 59); # 60秒一次
                    return [
                        'code' => 0,
                        'message' => '成功获取验证码',
                        'data' => ['item' => [], 'inner' => __LINE__,],
                    ];
                }
                else {
                    return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                }
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt($ret['message']);
            }
        }
        catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 图片验证码
     * @param $phone
     * @param $code
     * @param $ip
     * @return array
     */
    private function _checkCodeStatus($phone, $code, $ip) {
        $phone = trim($this->request->post('phone'));
        if (!Util::verifyPhone($phone)) {
            return [
                'code' => -1,
                'message' => '请输入正确的手机号码',
            ];
        }

        $key = md5('reg-' . $phone . '-' . ip2long($ip) . '-' . date('m.d'));
        $rules = intval(RedisQueue::get(['key'=>$key]));
        if( $rules > 2 ){
            $code = trim($this->request->post('code'));

            $captcha = \Yii::createObject([
                'class' => CaptchaValidator::class,
                'captchaAction' => 'page/captcha'
            ]);
            if( ! $code ) {
                return [
                    'code' => 102,
                    'message' => '请输入图片验证码',
                ];
            }
            if( !$captcha->validate($code) ) {
                return [
                    'code' => 101,
                    'message' => '请输入正确的图片验证码',
                ];
            }
        }else{
            ++ $rules;
            RedisQueue::set(['expire'=>3600*(24-date('H')), 'key'=>$key, 'value'=>$rules]);
        }

        return [
            'code' => 0,
            'message' => 'succ',
        ];
    }

    /**
     * 注册步骤二：验证手机号获和验证码，并设置登录密码
     *
     * @name 注册 [userRegister]
     * @method  post
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $invite_code 邀请码, 6位字符
     * @param string $password 密码
     * @param integer $source
     * @param string $name 姓名
     */
    public function actionRegister() {
        $session = Yii::$app->session;
        $openid = $session->get('openid');

        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));

        if(!empty($phone) && $phone!='' && !empty($code) && $code!=''){
            //注册统计，用户输入短信验证码
            VisitStat::getDb()->createCommand()->insert('tb_visit_stat', [
                'ip' => ToolsUtil::getIp(),
                'source_tag' => 'register',
                'created_at' => time(),
                'source_url' => '/frontend/web/xqb-user/register?appMarket=NoneAppMarket&clientType=wap',
                'current_url' => $code,
                'remark' => $phone,
            ])->execute();
        }

        if($this->request->post('password')){
            $password = trim($this->request->post('password'));
            $passwordLen = strlen($password);
            if($passwordLen<6 || $passwordLen>12 ){
                return UserExceptionExt::throwCodeAndMsgExt('密码长度需为6到12位');
            }
        }else{
            $password = ToolsUtil::randStr(8);
        }
        $source_id = intval($this->request->post('source_id',LoanPerson::PERSON_SOURCE_MOBILE_CREDIT));
        $source_tag = trim($this->request->post('source_tag'));
        $invite_code = $this->request->post('invite_code', '');
        $name = trim($this->request->post('name'), '');
        (new ChannelGeneralCount())->saveReg($source_tag);
        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_REGISTER, $source_id)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        }
        else {
            return $this->_autoRegister($phone, $password, $source_id, $invite_code, UserCaptcha::TYPE_REGISTER,$openid);
        }
    }

    private function _autoRegister($phone, $password = '', $source = '', $invite_code = '', $captcha_type = '',$openid='') {
        $user = $this->userService->registerByPhone($phone, $password, $source);
        if (! $user) {
            return UserExceptionExt::throwCodeAndMsgExt('注册失败，请稍后重试');
        }

        //插入记录用户注册信息表(一次)
        $check = UserRegisterInfo::findOne(['user_id' => $user->id]);
        if (empty($check)) {
            if (! $this->_saveRegisterInfo($user->id, $source)) {
                \yii::warning("304_saveRegisterInfo failed: {$user->id}, {$source}", LogChannel::USER_REGISTER);
            }
        }

        // 插入用户注册或绑卡成功队列（注册消息太多，等优化之后再加） TODO 这功能暂时不用
        // $this->userService->pushUserMessageList(UserService::USER_REGISTER, $user->id);

        // 注册成功后即登录
        if (Yii::$app->user->login($user)) { // 记录登录日志
            $loginLog = new UserLoginLog();
            $loginLog->user_id = $user->id;
            $loginLog->created_at = time();
            $loginLog->created_ip = $this->request->getUserIP();
            $loginLog->source = $this->client->serialize();
            $loginLog->type = UserLoginLog::TYPE_NORMAL;
            $loginLog->save();
        }

        $invite_user = null;
        if ($invite_code && !($invite_user = LoanPerson::findByInviteCode($invite_code))) {
            return UserExceptionExt::throwCodeAndMsgExt('邀请码不正确');
        }

        if ($invite_user) {//插入邀请关系
//            LoanPersonInvite::insertLog($invite_user->id, $user);
        }

        // 重新查一下，避免很多字段为null
        $user = LoanPerson::findByPhone($phone,$source);
        UserCaptcha::deleteAll(['phone' => $phone, 'type' => $captcha_type,'source_id'=>$source]);

        //事件处理队列    注册成功
        // RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
        //     'event_name' => AppEventService::EVENT_SUCCESS_REGISTER,
        //     'params' => [
        //         'user_id' => $user->id,
        //         'invite_user_id' => $invite_user ? $invite_user->id : 0,
        //         'from_app' => Util::t('from_app'),
        //     ],
        // ])]);

        $app_source = APP_NAMES;
        $message = '您的'.$app_source.'密码为 ' . $password . ' ， 马上下载'.$app_source.'APP，登录即可查看您的当前授信额度。';
//        \yii::info("发送注册的短信为: {$message}", LogChannel::SMS_REGISTER);
        $smsServiceUse = 'smsService_TianChang_HY';
//        if (!MessageHelper::sendSMS($phone, $message,$smsServiceUse,$source)) {
//            \yii::warning("短信推送随机密码失败: {$message}", LogChannel::SMS_REGISTER);
//
//            $res = false;
//            for ($i = 0; $i < 3; $i ++) {
//                if (MessageHelper::sendSMS($phone, $message)) {
//                    $res = true;
//                    break;
//                }
//            }
//
//            if (!$res) {
//                return UserExceptionExt::throwCodeAndMsgExt('短信发送失败，请稍后重试');
//            }
//        }

        if(isset($openid) && !empty($openid)){
            $user_verification = UserVerification::find()->where(['user_id'=>$user->id])->one();
            $weixin_user = WeixinUser::find()->where(['openid'=>$openid])->one();
            if($weixin_user){
                $transaction = \yii::$app->db->beginTransaction();
                $weixin_user->phone = $user->username;
                $weixin_user->uid  = $user->id;

                $user_verification->real_weixin_status = 1;
                $user_verification->save();
                try {
                    if($weixin_user->save() && $user_verification->save()){
                        $transaction->commit();
                    }else{
                        $transaction->rollBack();
                    }
                }catch (\Exception $e){
                    $transaction->rollBack();
                    return UserExceptionExt::throwCodeAndMsgExt('微信绑定失败，请稍后重试');
                }
            }
        }
        $data = [
            'uid' => $user->id,
            'username' => $user->username,
            'realname' => $user->name,
            'id_card' => $user->id_number,
            'real_verify_status' => $user->is_verify,
            'user_sign' => $user->auth_key,
            'sessionid' => \yii::$app->session->id,
            'openid'=>$openid
        ];

        return [
            'code' => 0,
            'message' => '注册成功',
            'data' => ['item' => $data],
        ];
    }

    /*
     * 保存 UserRegisterInfo 记录
     */
    private function _saveRegisterInfo($user_id, $source) {
        $clientType = trim($this->request->get('clientType', ''));
        $osVersion = trim($this->request->get('osVersion', ''));
        $appVersion = trim($this->request->get('appVersion', ''));
        $deviceName = trim($this->request->get('deviceName', ''));
        $appMarket = trim($this->request->get('appMarket', ''));
        $deviceId = trim($this->request->get('deviceId', ''));

        $now = \time();
        $user_login_upload_log = new UserRegisterInfo();
        $user_login_upload_log->user_id = $user_id;
        $user_login_upload_log->clientType = $clientType;
        $user_login_upload_log->osVersion = $osVersion;
        $user_login_upload_log->appVersion = $appVersion;
        $user_login_upload_log->deviceName = $deviceName;
        $user_login_upload_log->appMarket = empty($appMarket) && ('ios'==$clientType) ? "appstore" : $appMarket;
        $user_login_upload_log->deviceId = $deviceId;
        $user_login_upload_log->source = $source;
        $user_login_upload_log->created_at = $now;
        $user_login_upload_log->date = \date('Y-m-d', $now);
        return $user_login_upload_log->save();
    }

    /**
     * 判断口袋理财那边是否已经注册
     * @param $phone
     * @return array
     */
    public function getKdlcRegisterStatus($phone)
    {
        $loan_person = LoanPerson::findByPhone($phone);
        return [
            'code' => $loan_person ? CodeException::MOBILE_REGISTERED : 0,
            'loan_person' => $loan_person,
            'message' => 'success',
        ];
    }

    /**
     * 获取用户密码表
     * @param unknown $user_id
     */
    public function getUserPassword($user_id)
    {
        $class = \common\models\BaseActiveRecord::getChannelModelClass(\common\models\BaseActiveRecord::TB_UPWD);
        return $class::find()->where(['user_id' => $user_id])->limit(1)->one();
    }

    /**
     * 检查验证码是否正确
     * @return array
     */
    public function actionCheckVerify(){

        Yii::$app->response->format = Response::FORMAT_JSON;
        $phone = trim($this->request->post('phone', ''));

        if(!empty($phone) && $phone!=''){
            //注册统计，获取验证码判断手机号正确性
            VisitStat::getDb()->createCommand()->insert('tb_visit_stat', [
                'ip' => ToolsUtil::getIp(),
                'source_tag' => 'checkverify',
                'created_at' => time(),
                'source_url' => '/frontend/web/xqb-user/check-verify?clientType=wap',
                'current_url' => '/frontend/web/xqb-user/check-verify?clientType=wap',
                'remark' => $phone,
            ])->execute();
        }
        if (! MessageHelper::getType($phone)) {
            \yii::warning(sprintf('xqb_user_reg_get_code phone_err: %s', $phone), LogChannel::SMS_REGISTER);
            return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
        }
        $channel = intval($this->request->post('source_id',LoanPerson::PERSON_SOURCE_MOBILE_CREDIT));
        $source_tag = trim($this->request->post('source_tag',''));
        $request = Yii::$app->request;
        if (!$request->isAjax || !$request->isPost){
            return [
                'code'=>-1,
                'msg'=>'参数错误',
                'data'=>[]
            ];
        }

        //加上session判断，访问短信被刷
        $session = Yii::$app->session;
        $register_sms = $session->get('registersms');
        if($register_sms==''||empty($register_sms)){
            $register_sms=$this->getCookie('registersms');
        }
        $reg_sms_quantity=0;
        if($register_sms!=''&&!empty($register_sms)){
            $register_sms_array=json_decode($register_sms,true);
            //时间
            $reg_sms_time=$register_sms_array['time'];
            //次数
            $reg_sms_quantity=$register_sms_array['quantity'];
            //判断是否同一天操作
            $now_date=date('Y-m-d');
            $reg_sms_date=date('Y-m-d',$reg_sms_time);
            if(strtotime($now_date)!=strtotime($reg_sms_date)){
                $reg_sms_quantity=0;
            }
            //20秒钟内不能获取
            if(time()-$reg_sms_time<=20){
                return [
                    'code'=>-1,
                    'msg'=>'抱歉，请在20秒后获取短信验证码！',
                    'data'=>[]
                ];
            }
            if($reg_sms_quantity>10){
                return [
                    'code'=>-1,
                    'msg'=>'抱歉，同一天获取10次短信验证码！',
                    'data'=>[]
                ];
            }
        }
        //统计渠道获取验证码的次数
        (new ChannelGeneralCount())->saveGetCode($source_tag);
        $result=$this->regVerifyCode($phone,$source_tag,$channel);
        if(isset($result['code'])){
            if($result['code']==0){
                $reg_sms_quantity++;
                $val=json_encode(['time'=>time(),'quantity'=>$reg_sms_quantity]);
                $this->setCookie('registersms',$val);
                $session->set('registersms', $val);
            }
        }
        return $result;
    }

    /**
     * 统计APP下载
     * @return array
    **/
    public function actionTjAppDown(){
        $type = Html::encode($this->request->post('type'));
        $status = Html::encode($this->request->post('status'));
        $phone = Html::encode($this->request->post('phone'));
        if($type!='' && $status!=''){
            $source_tag='downapp';
            if($phone=='' || empty($phone)){
                $source_tag='visit';
            }
            //统计注册下载APP
            VisitStat::getDb()->createCommand()->insert('tb_visit_stat', [
                'ip' => ToolsUtil::getIp(),
                'source_tag' => $source_tag,
                'created_at' => time(),
                'source_url' => $type,
                'current_url' => $status,
                'remark' => $phone,
            ])->execute();
            return [
                'code' => 0,
                'message' => '统计成功',
            ];
        }
    }
}
