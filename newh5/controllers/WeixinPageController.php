<?php
namespace newh5\controllers;

use common\base\LogChannel;
use common\exceptions\UserExceptionExt;
use common\helpers\Lock;
use common\helpers\MessageHelper;
use common\helpers\ToolsUtil;
use common\models\ContentActivity;
use common\models\LoanPerson;
use common\models\UserCaptcha;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserPassword;
use common\models\UserVerification;
use common\models\WeixinUser;
use common\services\UserService;
use Yii;
use yii\data\Pagination;
use yii\helpers\Url;
use yii\web\Response;
use yii\filters\AccessControl;
use common\api\RedisQueue;
use common\helpers\Util;
use common\services\WeixinService;


class WeixinPageController extends BaseController
{
    public $layout = 'channel';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 仅下面的action需要登录
                'only' => ['data-stat'],
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
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'testLimit' => 1,
                'height' => 35,
                'width' => 80,
                'padding' => 0,
                'minLength' => 4,
                'maxLength' => 4,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * 用户登录绑定页面
     */
    public function actionUserLogin(){
		$this->layout = 'weixin';
        $weixinService = Yii::$app->weixinService;
        $session = Yii::$app->session;
        $session_openid = $session->get('openid');
        $openid = $this->getCookie('openid');
        $weixin_url = $baseUrl = $this->request->getHostInfo() ;
        if(isset($session_openid) || isset($openid)){
            $data = Yii::$app->request->post();
            //查询是否做了绑定
            if(isset($session_openid)){
                $openid = $session_openid;
            }
            $weixin = WeixinUser::findOne(['openid'=>$openid]);
            if(!isset($weixin) || !isset($openid)){
                $login_rul = url::to(['weixin-page/user-login']);
                $url = $weixinService->geOpenid($login_rul,$weixin_url);
                $this->redirect($url);
            }
            if(isset($weixin) && !empty($weixin->phone) && !empty($weixin->uid)){//查询用户是否绑定过
                return $this->render('wx-auth-result',[
                    'phone'=>$weixin->phone,
                ]);
            }
            if(isset($weixin) && isset($openid) && $data){//查询是否做了绑定 openid
                Yii::$app->response->format=Response::FORMAT_JSON;
                $phone = $data['phone'];
                $password = $data['pwd'];
                if(!Util::verifyPhone($phone)){
                    //提示手机的格式错误
                    return ['code'=>0,'message'=>'手机格式错误'];
                }
                $lona_person = LoanPerson::findByPhone($phone,LoanPerson::PERSON_SOURCE_MOBILE_CREDIT);
                //验证微信是否绑定过
                if(!$lona_person){
                    return ['code'=>2,'message'=>'用户不存在请注册'];
                }
                if(!empty($weixin->phone) && !empty($weixin->uid)){
                    return ['code'=>0,'message'=>'该微信已被绑定过'];
                }
                //判断该手机号是否绑定过
                $weixin_phone = WeixinUser::find()->where(['phone'=>$phone])->count();
                if($weixin_phone > 0){
                    return ['code'=>0,'message'=>'该账号已经绑定过微信'];
                }
                $userService = new UserService;
                $ret = $userService->loginKdlc($phone, $password, $lona_person);
                if (false == $ret) {
                    return ['code'=>0,'message'=>'系统繁忙,请稍后再试'];
                }
                if (0 != $ret['code']) {
                    return ['code'=>0,'message'=>$ret['message']];
                }
                if($lona_person){
                    $user_verfication = UserVerification::findOne(['user_id'=>$lona_person->id]);
                    $weixin->uid = $lona_person->id;
                    $weixin->phone = $lona_person->phone;
                    $weixin->bind_time = time();//微信绑定时间
                    $user_verfication->real_weixin_status = 1;
                    if(!$weixin->save(false) || !$user_verfication->save(false)){
                        return ['code'=>0,'message'=>'绑定微信账号失败'];
                    }else{
                        //回调成功跳转
                        return ['code'=>1,'message'=>'微信绑定成功','phone'=>$phone];
                    }
                }
            }
        }else{
            $login_rul = url::to(['weixin-page/user-login']);
            $url = $weixinService->geOpenid($login_rul,$weixin_url);
            $this->redirect($url);
        }
        $key = $this->_codeSmsKey(); // 注册验证码防刷key
        $company = $this->getSubInfoTypeA();
        $company_name = $company['company_name'];
        return $this->render('user-login',[
            'reg_sms_key' => $key,
            'company_name'=>$company_name,
        ]);
    }

    //还款落地页
    public function actionLoanPage(){
        $openid = $this->getCookie('openid');
        $session = Yii::$app->session;
        $session_openid = $session->get('openid');
        if(isset($session_openid)){
            $openid = $session_openid;
        }
        if($openid){
            $weixin = WeixinUser::find()->where(['openid'=>$openid])->one();
        }
        if($openid && $weixin && !empty($weixin->uid) && !empty($weixin->phone)){
            $url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt';
        }else{
            $url = Url::to(['weixin-page/register-xybt-one']);
        }
        return $this->render('wx-loan-tips',[
            'url'=>$url,
        ]);
    }

    //借款落地页
    public function actionPayPage(){
        $openid = $this->getCookie('openid');
        $session = Yii::$app->session;
        $session_openid = $session->get('openid');
        if(isset($session_openid)){
            $openid = $session_openid;
        }
        if($openid){
            $weixin = WeixinUser::find()->where(['openid'=>$openid])->one();
        }
        if($openid && $weixin && !empty($weixin->uid) && !empty($weixin->phone)){
            $url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt';
        }else{
            $url = Url::to(['weixin-page/register-xybt-one']);
        }
        return $this->render('wx-pay-tips',[
            'url'=>$url,
        ]);
    }



    public function actionRegisterXybtOne()
    {
        $this->_viewSource();
        $key = $this->_codeSmsKey(); // 注册验证码防刷key
        $openid = $this->getCookie('openid');
        $session = Yii::$app->session;
        $session_openid = $session->get('openid');
        if(isset($session_openid)){
            $openid = $session_openid;
        }
        if(empty($openid)){
            $jump_url = Url::to(['weixin-page/register-xybt-one']);
            $weixin_url = $baseUrl = $this->request->getHostInfo() ;
            $weixinService = Yii::$app->weixinService;
            $url = $weixinService->geOpenid($jump_url,$weixin_url);
            $this->redirect($url);
        }
        $company = $this->getSubInfoTypeA();
        $company_name = $company['company_name'];
        return $this->render('register-xybt-one', [
            'reg_sms_key' => $key,
            'openid'=>$openid,
            'company_name'=>$company_name,
        ]);
    }

    /**
     * 发送修改密码短信
     * @return array
     */
    public function actionSendMsg() {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $phone = trim(\yii::$app->request->post('phone', ''));
        $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        if (! MessageHelper::getType($phone)) { //判断是否传递了手机号
            return [
                'code' => -1,
                'message' => '请输入正确的手机号',
                'data' => [],
            ];
        }

        /* @var $user_service \common\services\UserService */
        $user_service = Yii::$container->get('userService');

        $ip = ToolsUtil::getIp(); //获取加锁文件
        if (YII_ENV_PROD && !Lock::lockCode(Lock::LOCK_REG_GET_CODE, ['phone' => $phone, 'ip' => $ip])) {
//            \yii::warning( sprintf('device_locked [%s][%s].', $ip, $phone), LogChannel::CHANNEL_USER_REG);
            return [
                'code' => -1,
                'message' => '验证码请求过于频繁，请稍后再试',
                'data' => []
            ];
        }

        if ($user_service->generateAndSendCaptcha($phone, UserCaptcha::TYPE_FIND_PWD, false, $source)) { //添加锁的次数
            \yii::warning( sprintf('wx_send_find_pwd [%s][%s].', $ip, $phone), LogChannel::SMS_GENERAL);
            return [
                'code' => 0,
                'message' => '成功获取验证码',
                'data' => [],
            ];
        }
        else {
            return [
                'code' => -1,
                'message' => '发送验证码失败，请稍后再试',
                'data' => [],
            ];
        }
    }

    //修改登录密码
    public function actionChangePwd(){
		$this->layout = 'weixin';
        $data = Yii::$app->request->post();
        if(!empty($data) && !empty($data['phone']) && !empty($data['code']) && $data['res_pwd']){
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $phone = $data['phone'];
            $code = $data['phone'];
            $pwd = $data['pwd'];
            $pwd_res = $data['res_pwd'];
            if(empty($data['code'])){
                return UserExceptionExt::throwCodeAndMsgExt('验证码不能为空.');
            }
            if(empty($data['phone'])){
                return UserExceptionExt::throwCodeAndMsgExt('手机号不能为空.');
            }
            if(empty($data['phone'])){
                return UserExceptionExt::throwCodeAndMsgExt('手机号不能为空.');
            }
            if($pwd != $pwd_res){
                return UserExceptionExt::throwCodeAndMsgExt('两次输入的内容不一致');
            }
            //验证
            $user_service = New UserService();
            $res = $user_service->validatePhoneCaptcha($phone,$code,UserCaptcha::TYPE_FIND_PWD,LoanPerson::PERSON_SOURCE_MOBILE_CREDIT);
            if($res != false){
                return UserExceptionExt::throwCodeAndMsgExt('手机验证码错误.');
            }
            //修改密码
            $loan_person = LoanPerson::findOne(['phone' => $phone,'source_id' =>21]);
            if (!$loan_person) {
                return UserExceptionExt::throwCodeAndMsgExt('手机号不存在.');
            }
            $userPayPwd = UserPassword::findOne(['user_id' => $loan_person->id]);
            if (empty($userPayPwd)) {
                return UserExceptionExt::throwCodeAndMsgExt('用户不存在');
            }
            if ($user_service->resetPassword($loan_person, $pwd)) {
                //删除验证码
                $captcha = UserCaptcha::findOne(['phone'=>$phone,'type'=>UserCaptcha::TYPE_FIND_PWD,'source_id'=>LoanPerson::PERSON_SOURCE_MOBILE_CREDIT]);
                if($captcha){
                    $captcha->delete();
                }
                return [
                    'code' => 0,
                    'message' => '修改密码成功',
                    'data' => ['item' => []],
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        }
        $key = $this->_codeSmsKey();
        $company = $this->getSubInfoTypeA();
        $company_name = $company['company_name'];
        return $this->render('forget-pswd',[
            'reg_sms_key' => $key,
            'company_name'=>$company_name,
        ]);
    }
    //发送短信验证码


    //微信下载APP
    public function actionDownload(){
        $url = '';//应用市场下载地址
        header("Location: {$url}");
    }

    /*--------------- 私有方法 ---------------------------------------------------------*/
    //未绑定微信账号的用户跳转绑定页面
    private function _checkUserBang($jump_url){
        //获取用户的openid //
        $openid = $this->getCookie('openid');
        //判断用户是否绑定过
        if($openid){
            $weixinUser = WeixinUser::findOne(['openid'=>$openid]);
            if(!$weixinUser ||
                empty($weixinUser->phone) ||
                empty($weixinUser->uid)){//未授权过的 或 未绑定的用户
                $weixin_url = $baseUrl = $this->request->getHostInfo();
                $login_rul = url::to(['weixin-page/user-login']);
                $weixinService = Yii::$app->weixinService;
                $url = $weixinService->geOpenid($login_rul,$weixin_url);
                $this->redirect($url);
            }else{
                return true;
            }
        }else{//如果用户openid失效跳转绑定页面
            if(empty($jump_url)){
                $jump_url = url::to(['weixin-page/user-login']);
            }
            $weixin_url = $baseUrl = $this->request->getHostInfo();//当前域名的地址
            $weixinService = Yii::$app->weixinService;
            $url = $weixinService->geBaseOpenid($jump_url,$weixin_url);//生成微信跳转的地址
            $this->redirect($url);
        }
    }

    // 生成注册验证码防刷key
    private function _codeSmsKey() {
        $key = \common\components\Session::getSmsKey();
        \yii::$app->session->set('reg_sms_key', $key);
        return $key;
    }
    private function _viewSource($default_source = LoanPerson::APPMARKET_JSHB)
    {
        $source_tag = trim($this->request->get('source_tag', 'NoneAppMarket'));
        $source_app = $default_source; // 默认

        new LoanPerson();
        $this->view->source_id = LoanPerson::$source_app_info[$source_app]['source_id'];
        $this->view->title = LoanPerson::$source_app_info[$source_app]['title'];
        $this->view->keywords = LoanPerson::$source_app_info[$source_app]['keywords'];
        $this->view->icon = LoanPerson::$source_app_info[$source_app]['icon'];
        $this->view->shareLogo = LoanPerson::$source_app_info[$source_app]['share_logo'];
        $this->view->source_app = LoanPerson::$source_app_info[$source_app]['source_app'];
        $this->view->source_tag = $source_tag;
        $this->view->showDownload = intval($this->request->get('show_download', 0)) ? 1 : 0; // 是否展示下载浮框
        return true;
    }

}
