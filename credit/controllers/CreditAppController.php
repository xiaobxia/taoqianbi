<?php

namespace credit\controllers;

use common\helpers\MessageHelper;
use common\models\AppBanner;
use common\models\GlispaLog;
use common\models\LoanBlackList;
use common\models\LoanPerson;
use common\models\LoanSearchPublicList;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\Version;
use common\models\PopBox;
use common\models\AccumulationFund;
use common\models\LoanOrderDayQuota;
use common\services\OrderService;
use Yii;
use yii\helpers\Url;
use common\models\Setting;
use common\api\RedisQueue;
use common\helpers\Util;
use common\models\DeviceInfo;
use common\models\DeviceVisitInfo;
use common\models\ContentActivity;
use common\models\BaseUserCreditTotalChannel;
use common\models\UserCreditDetail;
use common\models\UserCreditTotal;
use common\services\UserContactService;
use common\models\UserVerification;
use credit\components\ApiUrl;

/**
 * App controller
 */
class CreditAppController extends BaseController {

    /**
     * 下发配置
     * @name 下发配置 [getConfig]
     * @param string $configVersion 配置版本号
     * @uses 用于客户端获取url配置
     */
    public function actionConfig($configVersion) {
        // 此处版本号时间戳尽量取当前时间点
        $ver = '';
        $setting = Setting::findByKey('credit_app_time_stamp'); //后台配置下发时间戳(后台可更改)
        if (isset($setting) && $setting->svalue) {
            $ver = $setting->svalue;
        }
        // 取大值为配置的时间戳
        $confVer = YII_ENV_PROD ? \max(\strtotime('2017-06-14 10:15:00'), $ver) : \time();
        if ($configVersion == $confVer) {
            return [
                'code' => -1,
                'message' => '配置无更新',
                'data' => ['item' => []],
            ];
        }

        // 约定：api域名且是json返回的情况下考虑使用https, 即拼接地址的时候采用 $baseUrlHttps
        $baseUrl = $this->request->getHostInfo() . $this->request->getBaseUrl();
        $baseUrlHttps = $baseUrl;
        $clientType = \yii::$app->request->getClient()->clientType;

        //$version_id = $this->t('version_id');
        $update_msg = [];
        $source_id = $this->getSource();
        //获取app_market
        $app_Mark = \Yii::$app->request->getClient()->appMarket??'';
        $packname = $this->getClientInfo()->packname??'';
        $version = $this->client->appVersion;

        if( ($clientType == 'ios' || $clientType == 'android')) // && $version != '2.2.1' && $version != '2.2.0'
        {
            $update_msg = $this->getUpdate($app_Mark,$packname,$version,$clientType);//新的获取版本的方法
        }

        // 处理启动弹窗广告
        $show_pop = PopBox::isCanTotalShowPop(PopBox::SHOW_SITE_TWO);
        $show_pop = $show_pop > 0 ? 1 : 0 ;
        $show_ad  = PopBox::isCanTotalShowPop(PopBox::SHOW_SITE_ONE);
        $show_ad  = $show_ad > 0 ? 1 : 0 ;

        // 处理首页红点
        $show_Arr  = $this->getHotDotTime();
        $show_time = $show_Arr["loan_time"] >=  $show_Arr["msg_time"] ?  $show_Arr["loan_time"] :  $show_Arr["msg_time"] ;

        new LoanPerson();
        $source = $this->getSource();
        $name = LoanPerson::$person_source[$source];
        //判断是否来自马甲包
        $app_Mark = $this->request->headers->get('appMarket');
        if(isset($app_Mark)){
            if($clientType == 'ios'){//IOS没有渠道包
                if(strstr($app_Mark,LoanPerson::APPMARKET_IOS_XYBTFUND)){
                    $name =  LoanPerson::$source_ios_app[$app_Mark];
                }
            }
        }
        //处理维护通知
        $show_service = [
            'service_begin_time' => '2019-01-03 22:00:00',
            'service_end_time' => '2019-01-04 03:00:00',
            'service_msg' => '1月3日临时维护',
            'is_show' => 1,
        ];
        $qq = $this->t('callQQService');//默认的QQ
        $call = $this->t('callCenter');//默认的客服
        //是否显示客服入口
        $show = 1;

        $gzip = false;
        $help_url = $this->t('help_url');
        $repayment_help_url = $this->t('repayment_help_url');
        $register_protocol_url = $this->t('register_protocol_url');
        $about_url = $this->t('about_url');

        if(YII_ENV_DEV){
            $help_url = $baseUrlHttps.'/credit-web/help-center';
            $repayment_help_url = $baseUrlHttps.'/credit-web/repayment-process';
            $register_protocol_url = $baseUrlHttps.'/credit-web/safe-login-txt';
            $about_url = $this->request->getHostInfo() .'/newh5/web/app-page/about-company';
        }

        //guoxiaoyong 增加动态修改认证中心皮肤
        $cc_version = 1;
        $config = [
            //'name'				=> $this->t('app_name'),
            'cc_version' => $cc_version,//新增区分下发类型 1:默认 2：新的
            'name' => $name,
            'configVersion' => $confVer,
            'iosVersion' => Yii::$app->params['appConfig']['iosVersion'],
            'androidVersion' => Yii::$app->params['appConfig']['androidVersion'],
            'siteUrl' => 'http://'.SITE_DOMAIN.'/',
            'br_apicode' => Yii::$app->params['br']['apicode'],
            'help_url' => $help_url . '?clientType=' . $clientType,
            'repayment_help_url' => $repayment_help_url,
            'about_url' => $about_url . '?clientType=' . $clientType .'&app_version='.$version,
            'majia1_about_url' => $this->t('majia1_about_url'),
            'majia2_about_url' => $this->t('majia2_about_url'),
            'majia3_about_url' => $this->t('majia3_about_url'),
            'activity_url' => "{$baseUrlHttps}/credit-web/result-message",
            'coupon_url' => "{$baseUrlHttps}/credit-web/use-instruction",
            'invite_url' => $this->t('invite_url'),
            'register_protocol_url' => $register_protocol_url,
            'register_protocol_msg' => '我已阅读并同意<font color="#f18d00">《' . $name . '注册协议》</font>',
            'info_capture_script' => $this->staticUrl('js/info_capture/info_capture.js'),
            'index_card_bg' => $this->staticUrl('image/card/card_bg.png'),
            'show_type' => $show,
            'callCenter' => $call,
            'callQQService' => QQ_SERVICE,//$qq,
            'callQQGroup' => $this->t('callQQGroup'),
            'companyAddress' => $this->t('companyAddress'),
            'companyEmail' => $this->t('companyEmail'),
            'serverTime' => $this->t('serverTime'),
            'weekServerTime' => $this->t('weekServerTime'),
            'is_show_ad' => $show_pop,
            'is_show_pop' => $show_ad,
            'is_show_hotdot' => $show_time,
            'service_notice' => $show_service,
            'refresh_text' => ["有借有还,再借不难", "有借有还,再借不难", "有借有还,再借不难"],
            'safe_protocol_url' => "{$baseUrlHttps}/credit-web/safe-login-text",
            'is_use_gzip'   =>$gzip,
            'is_open_bill_page' => 0,  // TODO 是否开启账单认证页, ios审核用, 审核通过后置为0
            'shareCookieDomain' => [
                APP_DOMAIN,'47.98.135.244'
            ], //共享cookie的域名
            'infoCaptureDomain' => ['alipay', 'taobao', 'tmall'], //数据采集的域名
            'dataUrl' => [
                'creditAppDeviceReport' => "{$baseUrlHttps}/credit-app/device-report",
                'creditAppIndex' => "{$baseUrlHttps}/credit-app/index",
                'creditUserGetInfo' => "{$baseUrlHttps}/credit-user/get-info",
                'creditUserRegGetCode' => "{$baseUrlHttps}/credit-user/reg-get-code",
                'creditUserRegister' => "{$baseUrlHttps}/credit-user/register",
                'creditUserLogin' => "{$baseUrlHttps}/credit-user/login",
                'creditUserLogout' => "{$baseUrlHttps}/credit-user/logout",
                'creditUserChangePwd' => "{$baseUrlHttps}/credit-user/change-pwd",
                'creditUserSetPaypassword' => "{$baseUrlHttps}/credit-user/set-paypassword",
                'creditUserChangePaypassword' => "{$baseUrlHttps}/credit-user/change-paypassword",
                'creditUserResetPwdCode' => "{$baseUrlHttps}/credit-user/reset-pwd-code",
                'creditUserVerifyResetPassword' => "{$baseUrlHttps}/credit-user/verify-reset-password",
                'creditUserResetPassword' => "{$baseUrlHttps}/credit-user/reset-password",
                'creditUserResetPayPassword' => "{$baseUrlHttps}/credit-user/reset-pay-password",
                'creditUserState' => "{$baseUrlHttps}/credit-user/state",
                'creditUserVerifyCode' => "{$baseUrlHttps}/credit-user/verify-code",
                'creditCardSavePersonInfo' => "{$baseUrlHttps}/credit-card/save-person-info",
                'creditCardGetPersonInfo' => "{$baseUrlHttps}/credit-card/get-person-info",
                'creditCardSaveContacts' => "{$baseUrlHttps}/credit-card/save-contacts",
                'creditCardGetContacts' => "{$baseUrlHttps}/credit-card/get-contacts",
                'creditCardSaveWorkInfo' => "{$baseUrlHttps}/credit-card/save-work-info",
                'creditCardGetWorkInfo' => "{$baseUrlHttps}/credit-card/get-work-info",
                'creditCardGetCode' => "{$baseUrlHttps}/credit-card/get-code",
                'creditCardAddBankCard' => "{$baseUrlHttps}/credit-card/add-bank-card",
                'creditCardGetBankCard' => "{$baseUrlHttps}/credit-card/get-bank-card",
                'creditCreditCardAddBankCard' => "{$baseUrlHttps}/credit-card/add-credit-bank-card",
                'creditCreditCardGetBankCard' => "{$baseUrlHttps}/credit-card/get-credit-bank-card",
                'creditCardBankCardInfo' => "{$baseUrlHttps}/credit-card/bank-card-info",
                'creditCardGetCardInfo' => "{$baseUrlHttps}/credit-card/get-card-info",
                'creditCardGetVerificationInfo' => "{$baseUrlHttps}/credit-card/get-verification-info",
                'creditCardGetVerificationInfoV2' => "{$baseUrlHttps}/credit-card/get-verification-info-v2",
                'creditLoanGetConfirmLoan' => "{$baseUrlHttps}/credit-loan/get-confirm-loan",
                'creditLoanApplyLoan' => "{$baseUrlHttps}/credit-loan/apply-loan",
                'creditLoanGetLoanDetail' => "{$baseUrlHttps}/credit-loan/get-loan-detail",
                'creditLoanGetMyOrders' => "{$baseUrlHttps}/credit-loan/get-my-orders",
                'creditLoanGetMyLoan' => "{$baseUrlHttps}/credit-loan/get-my-loan",
                'creditLoanConfirmFailedLoan' => "{$baseUrlHttps}/credit-loan/confirm-failed-loan",
                'creditInfoSavePersonInfo' => "{$baseUrlHttps}/credit-info/save-person-info",
                'creditInfoUploadLocation' => "{$baseUrlHttps}/credit-info/upload-location",
                'creditInfoUploadContacts' => "{$baseUrlHttps}/credit-info/up-load-contacts", #未使用
                'creditInfoUploadContents' => "{$baseUrlHttps}/credit-info/up-load-contents", #1.短信列表;2.app列表;3.联系人列表
                'creditInfoFeedback' => "{$baseUrlHttps}/credit-info/feedback",
                'creditPictureUploadImage' => "{$baseUrlHttps}/picture/upload-image",
                'creditPictureGetPicList' => "{$baseUrlHttps}/picture/get-pic-list",
                'creditPictureDeletePic' => "{$baseUrlHttps}/picture/delete-pic",
                'creditZmVerifyUrl' => "{$baseUrlHttps}/creditreport/zm-authorize-url",
                'creditZmMobileApi' => "{$baseUrlHttps}/creditreport/zm-mobile-api",
                'creditZmMobileResultSave' => "{$baseUrlHttps}/creditreport/zm-mobile-result-save",
                'creditZmSmsAuthorize' => "{$baseUrlHttps}/creditreport/zm-sms-authorize", #未使用
                'creditZmAuthorizeStatus' => "{$baseUrlHttps}/creditreport/zm-authorize-status", #未使用
                'creditInfoCaptureAlipayInfoUpload' => "{$baseUrlHttps}/info-capture/info-upload", #（支付宝认证信息上传） 未使用
                'creditInfoCaptureUpload' => "{$baseUrlHttps}/info-capture/info-upload", #(支付宝，淘宝，京东信息上传)  未使用
                'creditInfoCaptureInit' => "{$baseUrlHttps}/info-capture/info-capture-init",
                'creditUpdateRedPacket' => "{$baseUrlHttps}/notice/update-red-packet",
                'infoUpLoadContacts' => "{$baseUrlHttps}/credit-info/up-load-contacts", #（上传手机联系人） 未使用
                'noticePopBox' => "{$baseUrlHttps}/notice/pop-box",
                'creditMyPacket' => "{$baseUrlHttps}/notice/my-packet",
                'creditHotDot' => "{$baseUrlHttps}/credit-app/hot-dot",
//                'creditNoticePopList' => "{$baseUrlHttps}/notice/pop-box-list",  //接口暂时未用 临时关闭 modify by guoxiaoyong 2017-7-14
                'creditNoticeStartPop' => "{$baseUrlHttps}/notice/start-pop-ad",
                'creditGetUserLoanList' => "{$baseUrlHttps}/credit-loan/user-repayment-list",
                'creditMyCouponList' => "{$baseUrlHttps}/notice/my-packet-slow",
                'creditIsExistCoupon' => "{$baseUrlHttps}/notice/is-coupon",
                'creditCouponDeductible' => "{$baseUrlHttps}/credit-loan/coupon-deductible",
                'creditCouponsExistence' => "{$baseUrlHttps}/credit-loan/coupons-existence", #未使用
                'creditPictureUploadImg' => "{$baseUrlHttps}/picture/upload-img", #上传图片操作（还款凭证）
                'creditAppUserCreditTop' => "{$baseUrlHttps}/credit-info/user-credit-top",
                'creditAppActiveShow' => "{$baseUrlHttps}/credit-info/credit-show",
                'creditTabBarList' => "{$baseUrlHttps}/credit-app/tab-bar-list",
                'creditUploadAppException' => "{$baseUrlHttps}/credit-info/upload-app-exception",
                'creditCreditGetInviteLast' => "{$baseUrlHttps}/credit-info/get-invitate-last",
                'creditSendInviteSms' => "{$baseUrlHttps}/credit-info/send-invite-sms",
                'FacePlusIdcard' => "{$baseUrlHttps}/credit-card/face-plus-idcard",
                'creditCardHouseFundLoginMethods' => "{$baseUrlHttps}/credit-card/house-fund-login-methods", //公积金城市列表
                'creditInfoSubmitHouseFundReq' => "{$baseUrlHttps}/credit-info/submit-house-fund-req", //公积金认证提交
                'creditInfoGetIceKreditToken' => "{$baseUrlHttps}/credit-info/get-ice-kredit-token", //获取冰鉴token
                'creditInfoChangeAlipayStatus' => "{$baseUrlHttps}/credit-info/change-alipay-status",
                'CreditLoanGetNewMyLoan' => "{$baseUrlHttps}/credit-loan/get-new-my-loan", //信用卡账单任务提交
                'BrSdk' => "{$baseUrlHttps}/credit-br/br-sdk", //百融sdk
                'CreditAppRepayTip' => "{$baseUrlHttps}/credit-app/repay-tip", //提示用户还款
                'CreditUserCheckSignCode' => "{$baseUrlHttps}/credit-user/check-sign-code", //获取随机校验码
                'CreditUserGetPicCode' => "{$baseUrlHttps}/credit-user/get-pic-code", //获取图片验证码
                'creditLoanRepayGetCoupon' => "{$baseUrlHttps}/credit-loan/repay-get-coupon",//还款后获取优惠券
                'discoverApp'         => $baseUrlHttps . '/credit-app/discover-app',//发现-app列表
                'NoticeShowActivity'    =>"{$baseUrlHttps}/notice/show-activity",
                'popAllBox'=>$baseUrlHttps . '/credit-app/pop-all-box', //首页弹框列表整合
                'calendar'       => $baseUrlHttps . '/credit-app/calendar', //日历提醒
                'Redpacket'     => $baseUrlHttps . '/credit-app/redpacket',//红包信息页
                'RedpacketList' => $baseUrlHttps . '/credit-app/redpacket-list',//红包数据列表
                'actionCheckOldPaypassword' => $baseUrlHttps . '/credit-user/check-old-paypassword',//修改交易密码新版
                'ChangePayPwdCode'          => $baseUrlHttps . '/credit-user/change-pay-pwd-code', //新修改密码的发送短信
                'NewChangePaypassword'     => $baseUrlHttps . '/credit-user/new-change-paypassword',//新版本保存交易密码
                'CreditCardGetVerification' =>$baseUrlHttps . '/credit-card/get-verification',//获取认证的状态
                'getCerificationProcess'  => $baseUrlHttps . '/credit-card/get-cerification-process',
                'creditInfoMoxieCreditTask' => "{$baseUrlHttps}/credit-info/moxie-credit-task", //信用卡账单任务提交
                'creditInfoGetBillStatus' => "{$baseUrlHttps}/credit-info/get-my-bill-status", //获取信用卡账单认证状态
                'creditInfoSearchMyBill' => "{$baseUrlHttps}/credit-info/search-my-bill", //查询我的账单
            ],
        ];
        if (!empty($update_msg)) {
            $config['update_msg'] = $update_msg;
        }

        if (!YII_ENV_PROD) {
            $config["invite_url"] = "{$baseUrlHttps}/credit-invite/invite-rebate-start";
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'item' => $config,
            ],
        ];
    }
    /**
     * 获取强制更新的配置
     */
    public function getUpdate($app_Mark,$packname,$version,$clientType){
        $update_msg = [];
        $data = Version::find()->where(['type'=>1])->andWhere(['<>','id',101])->asArray()->all();
        $arr = [];
        $android = [];
        $ios = [];
        foreach ($data as $key=>$val){
            if(isset($val['versions']) && !empty($val['versions'])){
                $arr = explode('-',$val['versions']);
                $count = count($arr);
                unset($arr[$count-1]);
            }
        }

        // TODO 选择版本范围  多条规则同时生效
        foreach ($data as $key =>$val){
            if($clientType == 'ios'){
//            if($clientType == 'ios' && true == $this->checkAppMarket($val['remark'],$val['pkgname'],$app_Mark,$packname)){
                if(version_compare($val['new_ios_version'], $version) > 0) {
                    $update_msg = [
                        'has_upgrade' => $val['has_upgrade'],
                        'is_force_upgrade' => $val['is_force_upgrade'],
                        'new_version' => $clientType == 'ios' ?$val['new_ios_version'] : $val['new_version'],
                        'new_features' =>  $val['new_features'],
                        'ard_url' => '',
                        'ard_size' => $val['ard_size'],
                        'ios_url' => '53eb3d162f859ebe3af187f0e59486f6',//应用token
                        'app_id' => '5bdc4445ca87a818438bcc4d',//应用appid
                    ];
                }
            }
            if($clientType == 'android'){
//            if($clientType == 'android' && true == $this->checkAppMarket($val['remark'],$val['pkgname'],$app_Mark,$packname)){
                if(version_compare($val['new_version'], $version) > 0){
                    $update_msg = [
                        'has_upgrade' => $val['has_upgrade'],
                        'is_force_upgrade' => $val['is_force_upgrade'],
                        'new_version' => $clientType == 'ios' ?$val['new_ios_version'] : $val['new_version'],
                        'new_features' =>  $val['new_features'],
                        'ard_url' => $val['ard_url'],
                        'ard_size' => $val['ard_size'],
                        'ios_url' => '',
                    ];
                }
            }
        }
        if($update_msg != ''){
            return $update_msg;
        }
    }

    private function checkAppMarket($vAppMark,$vpackname,$app_Mark,$packname){
        if(isset($packname) && isset($vpackname)){
            if($vpackname == $packname){
                return true;
            }
        }
        if(isset($vAppMark) && isset($app_Mark)){
            if(stristr($app_Mark,$vAppMark)){
                return true;
            }
        }
        return false;
    }


    /**
     * 设备上报
     *
     * @name 设备上报 [appDeviceReport]
     * @method post
     * @param string $device_id 设备唯一标识
     * @param string $installed_time 安装时间，建议首次安装启动或升级时传否则传空，格式：2014-12-03 10:00:00
     * @param string $uid 用户ID，客户端有缓存就传
     * @param string $username 用户名，客户端有缓存就传
     * @param string $net_type 网络类型：[2G, 3G, 4G, WIFI]
     * @param string $identifyID 设备标识
     */
    public function actionDeviceReport()
    {
        $ret = ['code' => 0];
        if ($this->client->deviceName == 'iPhone Simulator') { // 如果是ios模拟器，则直接忽略
            return $ret;
        }
        $now = \time();
        $device_id = trim($this->request->post('device_id'));
        $idfa = trim($this->request->post('idfva', '')); #由于 appstore 的限制，这个字段可能没有
        $installed_time = trim($this->request->post('installed_time'));
        $uid = intval($this->request->post('uid'));
        $username = intval($this->request->post('username'));
        $net_type = strtoupper(trim($this->request->post('net_type')));
        $app_type = 'credit';

        // 新增或更新设备信息
        $device = DeviceInfo::findOne([
            'device_id' => $device_id,
            'idfa' => !empty($idfa)?$idfa:$device_id,
            'app_type' => $app_type,
        ]);
        if (!$device) {
            $device = new DeviceInfo();
            $ip = Util::getUserIP();
            $glispaObj = Yii::$app->cache->get($ip);
            $glispa = json_decode($glispaObj);
            if ($glispaObj && !empty($glispa)) {
                $glispaModel = new GlispaLog();
                $glispaModel->click_id = strval($glispa->clickid);
                $glispaModel->glispa = $glispaObj;
                $glispaModel->device_id = $device_id;
                $glispaModel->idfa = $idfa?$idfa:$device_id;
                $glispaModel->ip = $ip;
                $glispaModel->status = GlispaLog::STATUS_TRIAL;
                $glispaModel->created_at = time();
                $glispaModel->updated_at = time();
                $glispaModel->save();
            }
        }
        $device->device_id = $device_id;
        $device->idfa = !empty($idfa)?$idfa:$device_id;
        $device->device_info = $this->client->deviceName;
        $device->os_type = $this->client->clientType;
        $device->os_version = $this->client->osVersion;
        $device->app_type = $app_type;
        $device->app_version = $this->client->appVersion;
        $device->source_tag = $this->client->appMarket;
        $device->reserved = $app_type; # 备用字段
        if ($installed_time) {
            $device->installed_time = \strtotime($installed_time);
        }
        if ($username) {
            $device->last_login_user = $username;
            $device->last_login_time = $now;
        }
        $device->save();

        // 新增上报记录
        $visit = new DeviceVisitInfo();
        $visit->device_id = $idfa ?: $device_id;
        $visit->idfa = $idfa;
        $visit->reserved = $device_id; #备用字段
        $visit->uid = $uid;
        $visit->username = $username;
        $visit->visit_time = $now;
        $visit->net_type = $net_type;
        $visit->reserved = $app_type; # 备用字段
        $visit->save();

        //更新用户设备信息
        $user_detail = UserDetail::findOne(['user_id' => $uid]);
        if (!is_null($user_detail)){
            $user_detail->reg_device_name = $this->client->deviceName;
            $user_detail->reg_os_version = $this->client->osVersion;
            $user_detail->save();
        }

        return $ret;
    }

    /**
     * @name 轮播信息（借款信息 + 提额信息 + 借款总额）
     */
    public function actionUserMultiMessage() {
        $message = $this->_getUserMultiMessage();
        return [
            'code' => 0,
            'message' => $message,
        ];
    }


    /**
     * 日历提醒
     * @author guoxiaoyong
     */
    public function actionCalendar()
    {
        $data = [];
        $user_id = Yii::$app->user->getId();
        $post_unique_id = Yii::$app->request->post('unique_id', '0-0');

        $explode = explode('-', $post_unique_id);

        $type = $explode[0];

        $unique_id = $explode[1];


        $order = UserLoanOrderRepayment::find()->select(['id', 'status', 'is_overdue', 'principal', 'plan_fee_time'])->where(['user_id' => $user_id])->orderBy('id DESC')->asArray()->one();


        if($order)
        {
            if(in_array($order['status'], [0, 1, 2]))
            {

                $order['principal'] = number_format($order['principal'] / 100, 2);


                if($order['is_overdue'] == UserLoanOrderRepayment::OVERDUE_NO)
                {
                    //未逾期
                    $data['title'] = '还款提醒';
                    $data['description'] = '【'.APP_NAMES.'】您的' . $order['principal'] .'元借款今日到期，按时还款立享提额。';
                    $data['type'] = UserLoanOrderRepayment::OVERDUE_NO;
                    $data['unique_id'] = 'H-' . $order['id'];
                    $data['start_time'] = strtotime(date('Y-m-d 09:00:00', $order['plan_fee_time']));
                    $data['end_time'] =  strtotime(date('Y-m-d 21:00:00', $order['plan_fee_time']));
                    $data['minutes'] = 0;
                }
                else
                {
                    //已逾期
                    $data['title'] = '逾期还款提醒';
                    $data['description'] = '【'.APP_NAMES.'】您的'.  $order['principal']  .'元借款已逾期，请及时还款。';
                    $data['type'] = UserLoanOrderRepayment::OVERDUE_YES;
                    $data['unique_id'] = 'C-' . $order['id'];
                    $data['minutes'] = 0;

                    if( strtotime(date('Y-m-d 18:00:00')) < time() )
                    {
                        $data['start_time'] = strtotime(date('Y-m-d 09:00:00'));
                        $data['end_time'] =  strtotime(date('Y-m-d 21:00:00'));
                    }
                    else
                    {
                        $data['start_time'] = strtotime(date('Y-m-d 09:00:00', strtotime('+1 day')));
                        $data['start_time'] = strtotime(date('Y-m-d 21:00:00', strtotime('+1 day')));
                    }

                }

                //数据相同 清空
                if($data['unique_id'] == $post_unique_id)
                {
                    $data = [];
                }

            }
        }



        //获取用户最后的借款订单信息

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data
        ];


    }

    /**
     * 处理借款信息 + 提额信息 + 借款总额
     */
    private function _getUserMultiMessage() {
        $queueLoanList = RedisQueue::getQueueList([RedisQueue::LIST_USER_LOAN_LOG_MESSAGE,0,-1]);
        if (count($queueLoanList) <= 5) {
            $queueLoanList = [
                "**8962成功借款9000元，申请至放款耗时5分钟",
                "**2396成功借款6000元，申请至放款耗时3分钟",
                "**1914成功借款8000元，申请至放款耗时4分钟",
                "**2836成功借款7000元，申请至放款耗时3分钟",
                "**1914成功借款5000元，申请至放款耗时2分钟",
                "**1914成功借款6500元，申请至放款耗时3分钟"
            ];
        }
        $queueIncList  = RedisQueue::getQueueList([RedisQueue::LIST_USER_INCREASE_LOG_MESSAGE,0,-1]);
        if (count($queueIncList) <= 5) {
            $queueIncList = [
                "**5701正常还款，成功提额至3000元",
                "**8963正常还款，成功提额至3500元",
                "**7896正常还款，成功提额至2600元",
                "**2698正常还款，成功提额至3200元",
                "**1596正常还款，成功提额至3800元",
                "**9263正常还款，成功提额至2000元"
            ];
        }
        $max_loan_len  = count($queueLoanList);
        $max_incr_len  = count($queueIncList);

        $size = $max_loan_len > $max_incr_len ? $max_loan_len : $max_incr_len;
        $queueList = array();
        for($i=0;$i < $size; $i++) {
            if ($i < $max_loan_len) {
                array_push($queueList,$queueLoanList[$i]);
            }
            if ($i < $max_incr_len) {
                array_push($queueList,$queueIncList[$i]);
            }
        }

        $log_message = sprintf("今日待抢额度：%s元",$this->_getAppTodayAmount());
        array_push($queueList,$log_message);

        return $queueLoanList;
    }

    /**
     * 处理借款总额
     */
    public function _getAppTodayAmount()
    {
        $todayAmount = Setting::getAppCardAmount();
        // 8位返回字符处理
        $return_amount = ceil($todayAmount / 100);
        $str_amount    = (string)$return_amount;

        if (strlen($str_amount) > 8) {
            $str_amount = substr($str_amount, 0 ,8);
        }else{
            $str_amount = str_pad($str_amount, 8, "0", STR_PAD_LEFT);
        }

        return $str_amount;
    }

    /**
     * app新版首页 ** 五期
     * @name App首页
     */
    public function actionMultiIndex() {
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        $userService = Yii::$container->get('userService');
        $loanService = Yii::$container->get('loanService');

        $phone = Yii::$app->user->identity ? Yii::$app->user->identity->phone : "00000000000";

        $verify_info      = $userService->getVerifyInfo($user_id);
        $card_normal_info = $userService->getMultiCreditInfo($user_id,$phone);

        $card_detail_info = $userService->getCreditDetail($user_id);

        $card_normal_info['card_open_span']   = "";
        $card_normal_info['card_open_tip']    = "综合费用=借款利息+居间服务费+信息认证费，综合费用将在借款时一次性扣除";

        foreach ($card_normal_info["card"] as $key => &$item) {
            $item["card_logo"]  = $this->staticUrl('image/app/card_logo_n'.$item["card_type"].'.png?v=1.1.0');
            // 添加背景图片
            $item["card_bg_img"]= $this->staticUrl($item["card_bg_img"]);
            $item["card_centor_img"]= $this->staticUrl($item["card_centor_img"]);

            $item["amount_sub_url"]   = "";
            // 标题显示链接
            $item["amount_open_url"]  = Yii::$app->params['app_golden_card'] == true ? ApiUrl::toCredit(['credit-web/cardlevel-description']) : "";
            if ($item["card_type"] == BaseUserCreditTotalChannel::CARD_TYPE_TWO) {
                $pass  = $verify_info['authentication_pass']  + $verify_info['real_verfy_senior'];
                $total = $verify_info['authentication_total'] + 1;
                $item['card_verify_step'] = sprintf('认证%d/%d',$pass,$total);
            }else{
                $item['card_verify_step'] = sprintf('认证%d/%d',$verify_info['authentication_pass'],$verify_info['authentication_total']);
            }
            $item['card_open_click']  = 0;
            $item['card_open_text']   = "";
            $item['card_open_message']   = "";

            // 处理金额的已抢完
            if ($item["card_type"] == BaseUserCreditTotalChannel::CARD_TYPE_TWO) {
                $gloden = Setting::getAppGlodenAmount();
                if (intval($gloden) <= 0) {
                    $item['amount_button'] = 1;
                }else{
                    $item['amount_button'] = 0;
                }
            }else{
                // 白卡的额度不限制
                $item['amount_button'] = 0;
            }

            $guess_flag = true;
            if ($user_id) {
                // $current = end($card_normal_info["card"]);
                // $senior_end_card = $current["card_type"];
                $senior_card = $card_normal_info['card_type'];

                // 白卡用户
                if ($senior_card == BaseUserCreditTotalChannel::CARD_TYPE_ONE) {
                    //
                    if ($item['card_type'] == BaseUserCreditTotalChannel::CARD_TYPE_ONE) {
                        if ($verify_info['real_verfy_base'] < 5) {
                            if ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO) {
                                $item["amount_title"]     = "你猜";
                                $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                                $item['verify_loan_pass'] = 0;
                                $item['card_amount']      = $item['card_money_max'];
                                $guess_flag = false;
                            }else{
                                $item["amount_title"]     = (string)($item["card_amount"]/100);
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }
                        }else{
                            if ($card_detail_info->credit_status == UserCreditDetail::STATUS_FINISH) {
                                $item["amount_title"]     = (string)($item["card_amount"]/100);
                                if ($verify_info['real_verfy_senior'] > 0) {
                                    $item["amount_sub_title"] = "提额攻略>";
                                    $item["amount_sub_url"] = ApiUrl::toCredit(['credit-web/add-quota']);
                                }else{
                                    $item["amount_sub_title"] = "完成高级认证 立即提额>";
                                }
                                // 处理授信时间
                                $item["card_validity"]    = $this->getCardValidity($card_detail_info->expire_time);
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }elseif ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) {
                                $item["amount_title"]     = "认证中";
                                $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                                if (intval($card_detail_info->credit_total) > 1) {
                                    $item["amount_sub_title"] = "提额攻略>";
                                    $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                    $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                                }else{
                                    $item['amount_button']     = 2;
                                    $item['card_open_message'] = "亲，您的卡片正在开通中，请耐心等待哦。";
                                    $item['verify_loan_pass'] = 0;
                                }
                                $item['card_amount']      = $item['card_money_max'];
                                // 是否首次
                                $guess_flag = intval($card_detail_info->credit_total) > 1 ? true:false;
                            }elseif ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ONE && $card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML) {
                                $item["amount_title"]     = (string)($item["card_amount"]/100);
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }else{
                                $item["amount_title"]     = "你猜";
                                $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                                $item['verify_loan_pass'] = 0;
                                $item['card_amount']      = $item['card_money_max'];
                                $guess_flag = false;
                            }
                        }
                        if ($guess_flag == false) {
                            // 显示默认额度
                            $min_amount  = 50000;
                            $max_amount  = 500000;
                            $card_period = [7,14];
                            $item["amount_days"] = $this->_handleAmount($min_amount,$max_amount,$card_period,$max_amount,1,false);
                        }else{
                            // 显示额度
                            $item["amount_days"]= $this->_handleAmount($item['card_money_min'],$item['card_money_max'],$item['card_period'],$item['card_amount'],$item['card_type'],$guess_flag);
                        }

                        // 借款信息
                        $loan_infos = $loanService->getUnConfirLoanOrderInfos($user_id,['sub_order_type'=>$this->sub_order_type]);
                        if ($loan_infos) {
                            $item['loan_infos'] = $loan_infos;
                        }
                    }else{
                        // 金卡
                        $user_credit_status_flag = $card_detail_info->credit_status == UserCreditDetail::STATUS_ING;
                        $item["amount_title"]     = $user_credit_status_flag ? "认证中" : "你猜";
                        $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);

                        if (intval($card_detail_info->credit_total) > 1) {
                            $item["amount_sub_title"] = "提额攻略>";
                            $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                        }
                        // 处理未认证跳转到立即认证
                        $item['amount_button'] = $user_credit_status_flag ? 2 : 0;
                        $item['card_open_message'] = $user_credit_status_flag ? "亲，您的卡片正在开通中，请耐心等待哦。" : "";
                        $guess_flag = false;
                        // 显示默认额度
                        $min_amount  = 50000;
                        $max_amount  = 500000;
                        $card_period = [7,14];
                        $item["amount_days"] = $this->_handleAmount($min_amount,$max_amount,$card_period,$max_amount,1,$guess_flag);
                        $item['verify_loan_pass'] = 0;
                    }
                    // 金卡用户
                }else{

                    // 白卡
                    if ($item['card_type'] == BaseUserCreditTotalChannel::CARD_TYPE_ONE) {
                        if ($verify_info['real_verfy_base'] < 5 || $card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT || ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO && $card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML  && !in_array($card_detail_info->card_golden, UserCreditDetail::$card_pass))) {
                            $item["amount_title"]     = ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) ? "认证中" : "你猜";
                            $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                            if (intval($card_detail_info->credit_total) > 1) {
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }else{
                                $item['amount_button']     = 2;
                                $item['card_open_message'] = "亲，您的卡片正在开通中，请耐心等待哦。";
                                $item['verify_loan_pass'] = 0;
                            }
                            $guess_flag = false;
                        }else{
                            $item["amount_title"]     = (string)($item["card_amount"]/100);
                            $item["amount_sub_title"] = "提额攻略>";
                            $item["amount_sub_url"] = ApiUrl::toCredit(['credit-web/add-quota']);
                            $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                        }
                        // 额度说明
                        $item["amount_days"]= $this->_handleAmount($item['card_money_min'],$item['card_money_max'],$item['card_period'],$item["card_amount"],$item['card_type'],$guess_flag);
                        // 借款信息
                        $loan_infos = $loanService->getUnConfirLoanOrderInfos($user_id,['sub_order_type'=>$this->sub_order_type],1);
                        if ($loan_infos) {
                            $item['loan_infos'] = $loan_infos;
                        }
                    }else{
                        // 金卡
                        if ($verify_info['real_verfy_base'] < 5 || $card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT || ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO && $card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML && !in_array($card_detail_info->card_golden, UserCreditDetail::$card_pass))) {
                            $item["amount_title"]     = ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) ? "认证中" : "你猜";
                            $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                            if (intval($card_detail_info->credit_total) > 1) {
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }else{
                                $item['verify_loan_pass'] = 0;
                            }
                            $item['amount_button'] = 0;
                            $guess_flag = false;
                        }
                        else {
                            if (in_array($card_detail_info->card_golden,[UserCreditDetail::CARD_GOLDEN_AUTO_PASS,UserCreditDetail::CARD_GOLDEN_MANUAL_PASS])) {
                                $item["amount_title"]     = (string)($item["card_amount"]/100);
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["card_validity"]  = $this->getCardValidity($card_detail_info->expire_time);
                                $item["amount_sub_url"] = ApiUrl::toCredit(['credit-web/add-quota']);
                                if ($card_detail_info->golden_show != 0) {
                                    $card_normal_info['card_open_span']   = "恭喜你，由于信用良好，发薪卡开通成功！平台每天限量放出发薪卡额度，手慢无哦！";
                                }
                                $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                            }elseif ($card_detail_info->card_golden == UserCreditDetail::CARD_GOLDEN_ING) {
                                $item["amount_title"]     = "认证中";
                                $item["amount_sub_title"] = "提额攻略>";
                                $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                $item['verify_loan_pass'] = 0;
                                $guess_flag = false;
                            }else{
                                $item["amount_title"]     = "你猜";
                                $item["amount_sub_title"] = sprintf("完成%s>",$item['card_verify_step']);
                                if (intval($card_detail_info->credit_total) > 1) {
                                    $item["amount_sub_title"] = "提额攻略>";
                                    $item["amount_sub_url"]   = ApiUrl::toCredit(['credit-web/add-quota']);
                                    $item['verify_loan_pass'] = $verify_info['verify_loan_pass'];
                                }else{
                                    $item['verify_loan_pass'] = 0;
                                }
                                $guess_flag = false;
                            }
                        }
                        // 额度说明
                        $item["amount_days"] = $this->_handleAmount($item['card_money_min'],$item['card_money_max'],$item['card_period'],$item["card_amount"],$item['card_type'],$guess_flag);

                        // 借款信息
                        $loan_infos = $loanService->getUnConfirLoanOrderInfos($user_id,['sub_order_type'=>$this->sub_order_type],2);
                        if ($loan_infos) {
                            $item['loan_infos'] = $loan_infos;
                        }
                    }
                }
                if ($guess_flag == false && intval($card_detail_info->credit_total) <= 1 && ($card_detail_info->credit_status !=UserCreditDetail::STATUS_ING && $card_detail_info->credit_status !=UserCreditDetail::STATUS_WAIT) ) {
                    $item['amount_button'] = 3;
                }

                if ($item["card_type"] == BaseUserCreditTotalChannel::CARD_TYPE_TWO) {
                    $gloden = Setting::getAppGlodenAmount();
                    if (intval($gloden) <= 0) {
                        $item['amount_button'] = 1;
                    }
                }elseif ($item["card_type"] == BaseUserCreditTotalChannel::CARD_TYPE_ONE) {
                    $today = Setting::getAppCardAmount();
                    if (intval($today) <= 0) {
                        $item['amount_button'] = 1;
                    }
                }
            }else{
                $item["amount_title"] = "你猜";
                $item["amount_sub_title"] = "登录查看>";
                $item['verify_loan_pass'] = 0;
                $item['amount_button']    = 3;

                $min_amount = 50000;
                $max_amount = 500000;
                $card_period = [7,14];
                $item["amount_days"] = $this->_handleAmount($min_amount,$max_amount,$card_period,$max_amount,1,false);
                // // 未登录显示
                // $item["amount_days"]["amount_text"] = "开卡揭晓";
                // $item["amount_days"]["interests_text"] = "开卡揭晓";
                $item["amount_days"]["amount_text"] = "";
                $item["amount_days"]["interests_text"] = "";
            }
        }

        // 标题
        $card_normal_info['title'] = $this->t('app_name');

        //处理隐藏金卡
        $card_normal_info["card"] = array_values($card_normal_info["card"]);
        // 处理待上传通讯录用户
        $is_user_contact = 1; //0不上传 1上传
        if ($user_id) {
            if (UserContactService::isExistSameUserRecord($user_id)) {
                $is_user_contact = 0;
            }
        }

        // 处理下面的文字
        $data = [
            'item'=> $card_normal_info,
            'is_login' => $user_id > 0 ? 1 : 0,
            'is_contact' => $is_user_contact,
            "user_loan_log_list"=> $this->_getUserMultiMessage(),
        ];

        if ($user_id && $userService->getBlackDetailList($user_id) == true) {
            $client = isset($this->client->clientType) ? $this->client->clientType : "";
            $data["active_info"] = array(
                "title" => '戳我看看>>>',
                "link"  => "https://h5.kdqugou.com/flow/index.html?tag=index&app=".$client,
                //ApiUrl::toCredit(['credit-web/rebate-slow']),
            );
        }else{
            $data["active_info"] = array(
                "title" => '借的不爽，我们赔',
                "link"  => "https://h5.kdqugou.com/activity/compensate",//ApiUrl::toCredit(['credit-web/rebate-slow']),
            );
        }

        return [
            'code'=>0,
            'message'=>'success',
            'data'=> $data
        ];
    }


    /**
     * app首页
     * @name app首页
     * @uses 获取 app首页信息
     */
    public function actionIndex() {
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->id : 0;

        if (empty($user_id)) {
            $cache_key = 'xybt:page_cache:credit_app:index:' . $user_id;
            $cache = \yii::$app->cache->get($cache_key);
            if ($cache && is_array($cache) && isset($cache['data'])) {
                $cache['data']['is_cache'] = 1;
                return $cache;
            }
        }

        $userService = Yii::$container->get('userService');
        $card_detail_info = $userService->getCreditDetail($user_id);
        $source = $this->getSource();
        $redis = Yii::$app->redis;

        //banner列表
        if(!$redis->EXISTS('app-index-banner:'.$source)){
            $banner_list = AppBanner::bannerList($source);
            $redis->SET('app-index-banner:'.$source,json_encode($banner_list));
        }else{
            $banner_list = json_decode($redis->get('app-index-banner:'.$source),true);
        }

        //悬浮banner
        $float_banner = [];
        foreach ($banner_list as $banner_k => $_banner) {
            if ($_banner['loan_search_public_list_id'] > 0) {
                if (!$user_id) { // 如果未登录不显示
                    unset($banner_list[$banner_k]);
                } else {
                    // 判断用户是否在可显示列表里
                    $record = LoanSearchPublicList::findOne($_banner['loan_search_public_list_id']);
                    if ($record && $redis->sismember($record->key, \yii::$app->user->identity->phone)) {
                        //将浮动banner和非浮动banner分开
                        if($_banner['is_float']==AppBanner::BANNER_TYPE_FLOAT){
                            //从banner列表中剔除
                            unset($banner_list[$banner_k]);
                            //加入悬浮banner 并根据id大小取最新的一个
                            if(empty($float_banner) || ($float_banner && $float_banner['id'] < $_banner['id'])){
                                $float_banner = self::floatBanner($_banner);
                            }
                        }
                    }else{
                        unset($banner_list[$banner_k]);
                    }
                }
            } else {
                //将浮动banner和非浮动banner分开
                if($_banner['is_float']==AppBanner::BANNER_TYPE_FLOAT){
                    unset($banner_list[$banner_k]);//从banner列表中剔除
                    //加入悬浮banner
                    if(empty($float_banner) || ($float_banner && $float_banner['id'] < $_banner['id'])){
                        $float_banner = self::floatBanner($_banner);
                    }
                }

            }
        }

        //多增加个jump 对应客户端跳转功能
        $is_show_repayment = false;
        $repayment = null;
        if ($user_id > 0) {
            //判断用户是否有到期、逾期还款
            $repayment = UserLoanOrderRepayment::find()->select(['user_id'])
                ->where(['user_id' => $user_id, 'status' => UserLoanOrderRepayment::STATUS_NORAML])
                ->andWhere(['<=', 'plan_fee_time', time()])
                ->one();
            if ($repayment) {
                $is_show_repayment = true;
                unset($float_banner['id']);
            }
        }

        if($float_banner){
            //处理等于2的类型 判断用户的还款情况显示
            if($float_banner['type']==2 && $is_show_repayment == false)
            {
                $float_banner = (object)[];
            }else{
                unset($float_banner['type']);
                unset($float_banner['id']);
            }
        }else{
            $float_banner = (object)[];
        }

        foreach ($banner_list as $key=>&$banner_item) {
            if($banner_item['type'] == 2 && $banner_item['app_type'] == AppBanner::APP_TYPE_REPAYMENT) {
                if($is_show_repayment == false) {
                    unset($banner_list[$key]);
                }
                else {
                    $banner_item['jump'] = ['type' => AppBanner::JUMP_APP];
                }
            }
            else if ($banner_item['type'] == 1) { //url跳转
                $banner_item['jump'] = ['type' => AppBanner::JUMP_URL, 'url' => $banner_item['link_url']];
            }
        }

        $banner = [];
        foreach ($banner_list as $b_item) {
            $banner[] = $b_item;
        }

        $amounts = [];
        $unused_amount = 0;
        $amounts_new_max = 0;
        $message = '';
        $clientType = $this->request->headers->get('clientType');
        $app_Mark = $this->request->headers->get('appMarket');
        $tag = false;
        if ($clientType && $app_Mark) {
            if($clientType == 'ios'){ //IOS没有渠道包
                if(strstr($app_Mark,LoanPerson::APPMARKET_IOS_XYBTFUND)){//判断是不是公积金版本
                    $tag = true;
                }else{
                    $tag = false;
                }

            }else if($clientType == 'android'){
                if(strstr($app_Mark,LoanPerson::APPMARKET_XYBTFUND)){//判断是不是公积金版本
                    $tag = true;
                }else{
                    $tag = false;
                }
            }
        }
        if ($user_id > 0 ) {
            $UserCreditTotal = UserCreditTotal::find()
                ->select(['amount','used_amount','locked_amount'])
                ->where(['user_id'=>$user_id])
                ->one();
            if ($UserCreditTotal) {
                $amounts_max = $UserCreditTotal->amount;
                $amounts_status = UserCreditDetail::findOne(['user_id'=>$user_id]);
                if($tag == true && $amounts_status->credit_status != UserCreditDetail::STATUS_FINISH){//公积金版本且未完成认证的
                    $amounts_max = 300000;
                }
                if($amounts_status->credit_status != UserCreditDetail::STATUS_FINISH){//未完成授信的显示5000
                    $amounts_max = 300000;
                }
                if ($amounts_max >= 100000) {
                    $amounts_max = intval($amounts_max/10000)*10000;
                }else{
                    $amounts_max = intval($amounts_max/1000)*1000;
                }
                $amounts_new_max = $amounts_max;
                $unused_amount =  ($amounts_new_max - $UserCreditTotal->used_amount - $UserCreditTotal->locked_amount);
                for($amounts_max; $amounts_max>=50000; $amounts_max -= 10000) {
                    $amounts[] = $amounts_max;
                }
            }
            //处理用户额度为0的列表
            if($UserCreditTotal->amount == 0){
                $amounts = [0,0];
            }

            $amount = $userService->getVerifyInfo($user_id);
            if ($amount['real_verify_status'] && $amount['real_contact_status'] && $amount['real_bind_bank_card_status']
                && $amount['real_jxl_status']
                && $card_detail_info->credit_status == UserCreditDetail::STATUS_FINISH
            ) {

                $amount_button = 1; // 认证完成
            }
            else {
                $amount_button = 2;  // 认证中
            }

            $user_credit_total = UserCreditTotal::findOne(['user_id' => $user_id]);
            $apr = $user_credit_total['counter_fee_rate'] ? : \Yii::$app->params['counter_fee_rate'];

            //王者来袋或开心借  2017-09-13 guoxiaoyong
            if( ($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT && Util::getMarket()=='xybt') || Util::getMarket() == LoanPerson::APPMARKET_XH ){
                $message = '<font color="#999999" size="2">最高可达到<font color="#f18d00" size="3">5000</font>元</font>';

                //根据是否首单用户,5项基本认证,公积金认证 判断首页是否显示公积金认证按钮
                if(OrderService::checkHasSuccessOrderByUid($user_id) && $amount['real_verify_status']&&$amount['real_contact_status']&&$amount['real_bind_bank_card_status']&&$amount['real_jxl_status']){

                    $accumulation_fund = AccumulationFund::findLatestOne(['user_id' => $user_id]);

                    //没有公积金认证的用户 或者公积金认证不成功的用户显示
                    if (!$accumulation_fund  || $accumulation_fund->status != AccumulationFund::STATUS_SUCCESS) {
                        $status = 0;
                        $error_message ='未填写';

                        if($accumulation_fund){
                            if($accumulation_fund->status == AccumulationFund::STATUS_INIT || $accumulation_fund->status == AccumulationFund::STATUS_GET_TOKEN){
                                $error_message = '待认证';
                                $status = UserVerification::VERIFICATION_ACCUMULATION_DOING;
                            }
                            if ($accumulation_fund->status == AccumulationFund::STATUS_FAILED){
                                $error_message = '认证失败';
                                $status = UserVerification::VERIFICATION_ACCUMULATION_FILED;
                            }
                        }

                        $work_fund_verify = [
                            [
                                'icon'=>$this->staticUrl('image/card/credit_index_work.png', 1),
                                'title'=>'工作信息',
                                'tag'=>UserVerification::TAG_WORK_INFO,
                                'link'=>'',
                                'status'=>$amount['real_work_status']
                            ],
                            [
                                'icon'=>$this->staticUrl('image/card/credit_index_fund.png', 1),
                                'title'=>'公积金认证',
                                'title_tip'=>'5千提额',
                                'tag'=>UserVerification::TAG_ACCREDIT_FUND,
                                'link'=>'',
                                'status'=>$status,
                                'error_message'=>$error_message
                            ]
                        ];

                    }

                }

            }
            if($tag == true){
                $message = '<font color="#999999" size="2">最高可达到<font color="#f18d00" size="3">10000</font>元</font>';
            }

        }
        else
        {
            $amounts_max = 300000;
            if($this->getSource() == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){
                $amounts_max = 300000;
            }
            if(Util::getMarket() == LoanPerson::APPMARKET_XH)//开心借
            {
                $amounts_max = 300000;
            }
            $amount_button = 0;
            $amounts_new_max = $amounts_max;
            for($amounts_max; $amounts_max>=50000; $amounts_max-=10000) {
                $amounts[] = $amounts_max;
            }

            $apr = \Yii::$app->params['counter_fee_rate'];
        }

        $is_user_contact = 1;
        if ($user_id) {
            if (UserContactService::isExistSameUserRecord($user_id)) {
                $is_user_contact = 0;
            }
        }
        if (false && $this->_getAppTodayAmount() <= 50000) { //TODO 禁止首页的下单按钮变灰
            $amount_button = 3;
        }

        // 放款单数限制 从数据库获取每日限额
        if ($user_id > 0) {
            $loan_person = LoanPerson::findOne($user_id);
            if ($loan_person) {
                $order_quota_model = new LoanOrderDayQuota();
                $order_quota = $order_quota_model->getTodayRemainingQouta();
                $order_total_count = $order_quota['norm'];
                $order_total_count_third = $order_quota['other'];
                $order_total_count_gjj = $order_quota['gjj'];
                $order_total_count_old_user = $order_quota['old_user'];

                $_order_count_key_third = sprintf('credit:order_count_third:%s', date('ymd'));
                $_order_count_key_gjj = sprintf('credit:order_count_gjj:%s', date('ymd'));
                $_order_count_key = sprintf('credit:order_count:%s', date('ymd'));
                $_order_count_key_old_user = sprintf('credit:order_count_old_user:%s', date('ymd'));

                $_order_real_count_key = sprintf('credit:order_real_count:%s', date('ymd'));
                if ($loan_person->customer_type == LoanPerson::CUSTOMER_TYPE_OLD) {
                    //老用户逻辑
                    if ($this->_reachOrderCount($_order_count_key_old_user, $order_total_count_old_user)) {
                        $amount_button = 3;
                    }
                } elseif (AccumulationFund::validateAccumulationStatus($loan_person)) { //公积金 驳回到人工初审
                    if ($this->_reachOrderCount($_order_count_key_gjj, $order_total_count_gjj)) {
                        $amount_button = 3;
                    }
                } elseif (in_array($loan_person->source_id, [LoanPerson::PERSON_SOURCE_HBJB, LoanPerson::PERSON_SOURCE_KDJZ, LoanPerson::PERSON_SOURCE_JBGJ])) { //第三方
                    if ($this->_reachOrderCount($_order_count_key_third, $order_total_count_third)) {
                        $amount_button = 3;
                    }
                } else {
                    if ($this->_reachOrderCount($_order_count_key, $order_total_count)) {
                        $amount_button = 3;
                    }
                }
            }
        }
        //判断对应的版本
        $app_Mark = $this->request->headers->get('appMarket');
        $client_type = $this->request->headers->get('clientType');
        $app_mark_android = 'xybt_fund';
        $app_mark_ios = 'AppStoreFund';
        $app_mark_s = '';
        if($client_type == 'ios'){
            $app_mark_s = $app_mark_ios;
        }else if($client_type == 'android'){
            $app_mark_s = $app_mark_android;
        }
        if($app_mark_s){
            if(stristr($app_Mark,$app_mark_s)){
                $userService = Yii::$container->get('userService');
                $ret = $userService->getVerifyInfo($user_id,true);
                $ret_data = $ret["authentication_pass"];
                if($ret_data < 7 || $ret_data != 7){
                    $amount_button = 2;
                }
            }
        }


        //判断状态在登录下且为认证完成 或今日额度已用完
        if ($amount_button ==1 || $amount_button == 3) {
            $crad_info = UserCreditTotal::find()->select(['card_no', 'created_at', 'card_type'])->where(['user_id' => $user_id])->one();
            //添加卡号间的空格
            $card_info_list = [];
            $car_no = $crad_info->card_no ? $crad_info->card_no : '';
            if (preg_match('/^(\d{4})(\d{4})(\d{4})(\d{1,})$/', $car_no, $match)) {
                unset($match[0]);
                $car_no = implode(' ', $match);
            }
            $card_info_list['card_num'] = $car_no;

        }
        if ($amounts_new_max <= 50000 && $amounts_new_max >0) {
            $amounts = [$amounts_new_max,$amounts_new_max];
        }
        sort($amounts);


        // 确认是否审核不过用户 添加 banner
//        if($user_id > 0){
//
//            $audit_status = UserLoanOrder::find()->select(['id','status'])
//                        ->where(['user_id'=>$user_id])->orderBy('id desc')->asArray()->one();
//
//            if($audit_status && (($audit_status['status'] <= -3 && $audit_status['status'] !=7) || $audit_status['status'] >=10000)){
//                $_app_banner = AppBanner::find()->where('id = 5')->asArray()->one();
//                $_app_banner['jump'] =['type' => AppBanner::JUMP_URL, 'url' => $_app_banner['link_url']];
//                $banner =[];
//                $banner[] = $_app_banner;
//            }
//        }

        new LoanPerson();
        $data = [
            'item' => $banner,
            'amounts' => $amounts,
            'is_contact' => $is_user_contact,
            "user_loan_log_list"=> $this->_getUserMultiMessage(),
            'amounts_max' => $amounts_new_max,
            'unused_amount' => $unused_amount,
            'card_open_tip' => "综合费用=借款利息+居间服务费+信息认证费，综合费用将在借款时一次性扣除",
            'amounts_min' => 50000,
            'apr' => $apr / 100,
            'title' => LoanPerson::$person_source[$source],
            'amount_days' => 7,
            'tay_message' => $message,
            'amount_button' => $amount_button,
            'notice'        => '<font color="#f18d00" size="3">不向未满20岁及学生提供服务</font>',
            'float_banner'=>$float_banner
        ];

        if(isset($work_fund_verify)){
            $data['work_fund_verify'] = $work_fund_verify;
        }

        //提额显示 只有登录用户才下发 guoxiaoyong
        $data['up_credit_line'] = '';
        if($user_id)
        {
            $redis = Yii::$app->redis;
            $creidt_line = $redis->get("up_credit_line_{$user_id}");
            if($creidt_line)
            {
                //销毁redis key
                $redis->del("up_credit_line_{$user_id}");
                $data['up_credit_line'] = '+'. (int)$creidt_line / 100 . '元';
            }
        }



        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data
        ];
    }

    /**
     * 处理浮动banner
     * @param $_banner
     * @return array
     */
    private static function floatBanner($_banner){
        if($_banner['type'] == 2){
            //内部
            return [
                'img_url'=>$_banner['image_url'],
                'jump'=>['type' => AppBanner::JUMP_APP, 'url' => ''],
                'type'=>2,
                'id'=>$_banner['id']
            ];
        } else if ($_banner['type'] == 1){
            //url跳转
            return [
                'img_url'=>$_banner['image_url'],
                'jump'=>['type' => AppBanner::JUMP_URL, 'url' => $_banner['link_url']],
                'type'=>1,
                'id'=>$_banner['id']
            ];
        } else if($_banner['type'] == 0){
            //普通
            return [
                'img_url'=>$_banner['image_url'],
                'jump'=>(object)[],
                'type'=>0,
                'id'=>$_banner['id']
            ];
        }

    }


    private function _reachOrderCount($key, $total)
    {
        $curr_count = \yii::$app->redis->get($key);
        if (is_null($curr_count)) {
            $curr_count = 0;
        }

        if ($curr_count < $total) { //未到最大量 pass
            return false;
        }
        return true;
    }

    private function _handleAmount($min, $max, $days, $amount = '', $card_type = '1', $flag = false)
    {
        $min_amount = isset($min) ? $min : 50000;
        if ($card_type == 1) {
            $max_amount = isset($max) ? $max : 300000;
            $max_amount = $max_amount > 300000 ? 300000 : $max_amount;
        } else {
            $max_amount = isset($max) ? $max : 500000;
            $max_amount = $max_amount > 500000 ? 500000 : $max_amount;
        }

        if ($amount) {
            $amount = intval($amount / 10000) * 10000;
            if ($amount <= 50000) {
                $min_amount = 20000;
                $max_amount = $amount;
            } elseif ($amount <= $min_amount) {
                $min_amount = 50000;
                $max_amount = $amount;
            } elseif ($min_amount < $amount && $amount <= $max_amount) {
                $min_amount = $min_amount;
                $max_amount = $amount;
            } else {
                $min_amount = $min_amount;
                $max_amount = $amount > $max_amount ? $max_amount : $amount;
            }
        }

        $amounts_arr = [];
        for ($i = $min_amount; $i <= $max_amount; $i += 10000) {
            array_push($amounts_arr, (string)$i);
        }

        $days = is_array($days) && count($days) > 0 ? $days : [7, 14];

        $loan_term_flag = Setting::handleLoanTerm();
        switch ($loan_term_flag) {
            case Setting::APP_LOAN_TERM_SEVEN:
                $days = [7];
                break;
            case Setting::APP_LOAN_TERM_FOURTEEN:
                $days = [14];
                break;
            case Setting::APP_LOAN_TERM_TWENTY:
                $days = [21];
                break;
            default:
                $days = YII_ENV_PROD ? $days : [7, 14, 21];
                break;
        }

        $interests = [];
        $min_inter = [];
        foreach ($days as $item) {
            $inter_arr = Util::calcLoanInfo($item, $max_amount, $card_type);
            $interests[] = (string)$inter_arr["counter_fee"];
            if ($flag == false) {
                $min_interests = Util::calcLoanInfo($item, $max_amount, 2);
                $min_inter[] = (string)$min_interests["counter_fee"];//sprintf("%d",$inter_arr["counter_fee"] * 0.5);
            }
        }
        $data = [
            'days' => $days,
            'interests' => $interests,
            'amounts' => $amounts_arr,
            'amount_text' => "",
            'interests_text' => "",
        ];

        if ($flag == false) {
            $data['inter_min'] = $min_inter;
        }

        return $data;
    }

    /**
     * 操作用户的剩余额度 以及 利息算法
     * @amount 单位分
     */
    public function _unusedAmount($amount)
    {
        $amount_days_list_param = Yii::$app->params['amount_days_list'];

        $min_amount = 20000;
        $max_amount = isset($amount) ? intval($amount / 10000) * 10000 : 150000;

        $amounts_arr = [];
        if ($max_amount > 100000) {
            if ($max_amount >= 300000) {
                $max_amount = 300000;
            }
            $min_amount = 50000;
        } elseif ($max_amount > $min_amount) {
            $min_amount = 20000;
            $max_amount = 100000;
        } else {
            $max_amount = 150000;
        }
        $interests = [];

        $days = $amount_days_list_param["days"];
        $loan_term_flag = Setting::handleLoanTerm();
        switch ($loan_term_flag) {
            case Setting::APP_LOAN_TERM_SEVEN:
                $days = ["7", "7"];
                break;
            case Setting::APP_LOAN_TERM_FOURTEEN:
                $days = ["14", "14"];
                break;
            case Setting::APP_LOAN_TERM_TWENTY:
                $days = ["21", "21"];
                break;
            default:
                $days = YII_ENV_PROD ? ["7", "14"] : ["7", "14", "21"];
                break;
        }

        foreach ($days as $item) {
            $interests_arr = Util::calcLoanInfo($item, $max_amount);
            $interest_str = (string)$interests_arr["counter_fee"];
            array_push($interests, $interest_str);
        }

        for ($i = $min_amount; $i <= $max_amount; $i += 10000) {
            array_push($amounts_arr, (string)$i);
        }

        $amount_days_list = [
            'days' => $days,
            'interests' => $interests,//[$minInterest_str,$maxInterest_str],
            'amounts' => $amounts_arr
        ];

        return $amount_days_list;
    }

    /**
     * tab_bar APP底部图标
     */
    public function actionTabBarList()
    {
        $theme = "spring_9";
        $front_color = "#999999";
        $theme_color = $this->getColor();

        $back_color = "#adadad";
        //默认颜色
        // $back_color  = "#ffffff";

        $loan = [
            "title" => "借款",
            "tag" => 1,
            "image" => $this->staticUrl(sprintf('image/tag/%s/tab_loan.png', $theme), 1),
            "sel_image" => $this->staticUrl(sprintf('image/tag/%s/tab_loan_1.png', $theme), 1),
            "red_image" => "",
            "span_color" => $front_color,
            "sel_span_color" => $theme_color,
        ];

        $list[] = $loan;

        $repayment = [
            "title" => "还款",
            "tag" => 2,
            "image" => $this->staticUrl(sprintf('image/tag/%s/tab_repayment.png', $theme), 1),
            "sel_image" => $this->staticUrl(sprintf('image/tag/%s/tab_repayment_1.png', $theme), 1),
            "red_image" => "",
            "span_color" => $front_color,
            "sel_span_color" => $theme_color,
        ];

        $list[] = $repayment;

        $mine = [
            "title" => "个人中心",
            "tag" => 3,
            "image" => $this->staticUrl(sprintf('image/tag/%s/tab_mine.png', $theme), 1),
            "sel_image" => $this->staticUrl(sprintf('image/tag/%s/tab_mine_2.png', $theme), 1),
            "red_image" => $this->staticUrl(sprintf('image/tag/%s/tab_mine_1.png', $theme), 1),
            "span_color" => $front_color,
            "sel_span_color" => $theme_color,
        ];

        $list[] = $mine;

        //默认只显示3个菜单
        $is_show = false;

        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                "item" => $list,
                'is_show' => $is_show,
                "bg_color" => $back_color,
            ]
        ];
    }

    /**
     * 消息中心红点显示
     * @name 消息中心红点
     */
    public function actionHotDot() {
        $timeArr = $this->getHotDotTime();

        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                "loan" => [
                    "time" => $timeArr["loan_time"],
                ],
                "message" => [
                    "time" => $timeArr["msg_time"],
//                    "time" => floor(\time()/60),
                ],
                "coupon" => [
                    "time" => $timeArr["coupon_time"],
                ]
            ]
        ];
    }

    /**
     * 处理卡的过期时间
     */
    private function getCardValidity($validity)
    {
        $now = time();
        if ($validity < $now) {
            $validity = strtotime("+6 month", $now);
        }
        $date = $validity ? date("d/m/Y", $validity) : "31/07/2017";
        return sprintf("VALID THRU %s", $date);
    }

    /**
     * 获取更新的时间
     */
    private function getHotDotTime()
    {
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        // 消息中心红点逻辑
        $show_time = ContentActivity::getHotMessage();
        // 借款消息
        $loan_time = 0;
        $coupon_time = 0;

        return array(
            "loan_time" => $loan_time,
            "msg_time" => $show_time,
            "coupon_time" => $coupon_time,
        );
    }

    /**
     * @name 处理提示用户最新的还款提示
     * @return array
     * @return repay_tip_time int
     * @return repay_tip_text string
     */
    public function actionRepayTip(){
        $data = [];
        //查询该用户的借款数据
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        //初始化变量
        $type = 0;
        $repay_tip_time = '';
        $repay_tip_text = '';
        $unique_id = 0;
        if ($user_id != 0) {
            $day_time = strtotime(date('Y-m-d', \time()));
            $user_loan = UserLoanOrder::find()->where(['user_id' => $user_id,])
                ->andWhere(['in', 'status', [UserLoanOrder::STATUS_LOAN_COMPLETE, UserLoanOrder::STATUS_REPAYING]])
                ->select(['money_amount', 'status', 'id'])->orderBy('id desc')->one();
            $unique_id = 0;
            if ($user_loan) {
                $loan_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $user_loan->id])->one();
                $plan_repayment_time = strtotime(date('Y-m-d', $loan_repayment['plan_repayment_time']));
                if ($loan_repayment) {
                    $time_res = $plan_repayment_time - $day_time;
                    $is_overdue = $loan_repayment['is_overdue'];
                    if ($is_overdue == 0 && $time_res >= 0) {
                        $unique_id = $loan_repayment['order_id'];
                        $type = 1;
                        $repay_tip_time = strtotime(date('Y-m-d', $loan_repayment['plan_repayment_time'] - 86400));
                        $repay_tip_text = '您有一笔借款即将到期，现在就去还款吧';
                    } else if ($day_time > ($plan_repayment_time + 24 * 3600)) {//不知道 is_overdue 修改字段的脚本会不会出错先不加
                        $unique_id = $loan_repayment['order_id'];
                        $type = 2;
                        $repay_tip_text = '您有一笔借款已逾期，快去还款吧！';
                    }
                }
            }
        }
        //获取当前用户最后一笔的借款的状态
        $data['tip_time'] = $repay_tip_time;
        $data['tip_text'] = $repay_tip_text;
        $data['status'] = $type;
        $data['unique_id'] = $unique_id;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * @author guoxiaoyong
     * 将cretid 转换成其他模块子域
     * @param string $url
     * @param string $modulName
     */
    protected function replaceUrl($url, $moduleName)
    {
        $oldUrl = Url::to($url);
        $newUrl = \str_replace([
            'credit/', 'api.', 'qbapi.'
        ], [
            $moduleName . '/', 'credit.', 'qbcredit.'
        ], $oldUrl); //兼容多种环境和绝对路径


        return $newUrl;
    }



    /**
     * 弹框整合
     * 每次只弹一个框
     * 优先级 活动 > 还款提示 > 优惠券
     * @return array
     */
    public function actionPopAllBox(){
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        $data = Yii::$app->request->post();

        $popList = [];

        //处理app传递的参数
        $popJson = [];
        if(isset($data['popJson']) && $data['popJson']){
            $popJsonArr = json_decode($data['popJson'],true);
            if(json_last_error()===JSON_ERROR_NONE){
                $popJson = $popJsonArr;
            }else{
                return [
                    "code" => -1,
                    'message' => "数据格式错误",
                    'data' => [],
                ];
            }
        }
        $popType = [];
        if($popJson){
            foreach($popJson as $value){
                if(isset($value['uid'])){
                    $popType[$value['type']][] = $value['uid'];
                }
            }
        }

        //活动提示
        $redis = Yii::$app->redis;
        $source = $this->getSource();


        if(Util::getMarket()==LoanPerson::APPMARKET_XJBT){
            $popList=[];
        }
        if($popList){
            return [
                "code" => 0,
                'message' => "",
                'data' => $popList,
            ];
        }

        //用户最新的还款提示
        if ($user_id != 0) {
            $day_time = strtotime(date('Y-m-d', \time()));
            $user_loan = UserLoanOrder::find()
                ->where(['user_id' => $user_id])
                ->andWhere(['in', 'status', [UserLoanOrder::STATUS_LOAN_COMPLETE, UserLoanOrder::STATUS_REPAYING]])
                ->select(['money_amount', 'status', 'id'])
                ->orderBy('id desc')
                ->one();

            if ($user_loan) {
                $loan_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $user_loan->id])->one();
                if ($loan_repayment) {
                    $plan_repayment_time = strtotime(date('Y-m-d', $loan_repayment['plan_fee_time']));
                    $time_res = $plan_repayment_time - $day_time;
                    $is_overdue = $loan_repayment['is_overdue'];

                    if(!isset($popType[2])){

                        //还没有弹框  按照顺序来获取此用户当前订单状态值的弹框
                        if ($is_overdue == 0 && $time_res >= 0 && $time_res <= 86400) {

                            $popList[] = $this->paymentCoupon($loan_repayment,$user_id,1);
                        } else if ($is_overdue == 1) {//不知道 is_overdue 修改字段的脚本会不会出错先不加

                            $popList[] = $this->paymentCoupon($loan_repayment,0,2);
                        }
                    }else{

                        if ($is_overdue == 0  && $time_res >= 0 && $time_res <= 86400) {
                            //已经弹过框
                            if(!in_array($loan_repayment['order_id'].'-1',$popType[2])){
                                $popList[] = $this->paymentCoupon($loan_repayment,$user_id,1);
                            }
                        } else if ($is_overdue == 1) {
                            if(!in_array($loan_repayment['order_id'].'-2',$popType[2])){
                                $popList[] = $this->paymentCoupon($loan_repayment,0,2);
                            }
                        }

                        /*if(in_array($loan_repayment['order_id'].'-2',$popType[2],true)){
                        }*/

                    }

                }
            }
        }
        if(Util::getMarket()==LoanPerson::APPMARKET_XJBT){
            $popList=[];
        }
        if($popList){
            return [
                "code" => 0,
                'message' => "",
                'data' => $popList,
            ];
        }

        if(Util::getMarket()==LoanPerson::APPMARKET_XJBT){
            $popList=[];
        }

        return [
            "code" => 0,
            'message' => "",
            'data' => $popList,
        ];

    }

    /**
     * 还款提示弹框
     * @param $loan_repayment
     * @param $user_id
     * @param $type 1,2
     * @return array
     */
    private function paymentCoupon($loan_repayment,$user_id,$type){
        if($type==1){
            $tip_text = '今日还款立享提额，快去还款吧！';
            //获取当前用户最后一笔的借款的状态
            $main_title = '您有一笔借款今日到期';
        }else{
            $main_title = '您有一笔借款已逾期';
            $tip_text = '您的借款已逾期'.$loan_repayment['overdue_day'].'天，已产生逾期费用，快去还款吧！';
        }

        return [
            'pop_type'=>2,
            'show_id'=>$loan_repayment['order_id'],
            'use_period'=>0,
            'main_title'=>$main_title,
            'tip_text'=>$tip_text,
            'describe'=>'',
            'link_url'=>ApiUrl::toRouteMobile(['loan/loan-detail', 'id' => $loan_repayment['order_id']]),
            'sub_type'=>0,
            'image_url'=>'',
            'unique_id'=>$loan_repayment['order_id'].'-'.$type,
            'type'=>0
        ];

    }

}

