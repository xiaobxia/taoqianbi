<?php
namespace frontend\controllers;

use common\exceptions\CodeException;
use common\helpers\CurlHelper;
use common\models\LoanPerson;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\UserCredit;
use common\models\UserLoanOrderRepayment;
use common\models\UserRentCredit;
use common\models\Version;
use common\services\MarketingMessageService;
use common\services\statistics\OrderDetailService;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use common\models\Setting;
use common\services\PayService;
use common\api\HttpRequest;
use common\exceptions\UserExceptionExt;
use common\models\UserHfdInfo;
use common\models\Shop;
use common\helpers\Util;

use common\models\DeviceInfo;
use common\models\DeviceVisitInfo;
use common\models\IntegralWall;
use common\models\KdkjIntegralWall;
use common\services\IntegralWallService;
use frontend\components\ApiUrl;

/**
 * App controller
 */
class AppController extends BaseController {

	/**
	 * 测试接口
	 */
    public function actionTest(){
        $order_detail_service = new OrderDetailService();
        $order_detail_service->createOrderDetail(15313);
        exit;

//        $ret = new MarketingMessageService();
//        var_dump($ret);exit;
//        $query = Yii::$app->solr->createSelect();
        //'edismax&qf=title^20.0 OR description^0.3'
//        $query->setQuery('id=11875');
//        $result = Yii::$app->solr->select($query);
//        var_dump($result);exit;
//        var_dump($result->getNumFound());
//        exit;
       // var_dump(__DIR__.'/init.php');exit;
       // $config = Yii::$app->get('solr');
       // htmlHeader();
      //  $client = new Solarium\Client($config);
        $client = Yii::$app->solr;
        $update = $client->createUpdate();
        $doc = $update->createDocument();
        $doc->id = 12344555;
        $doc->name = "name_fine111";
        $update->addDocument($doc);
        $update->addCommit();
        $result = $client->update($update);
        var_dump($result->getStatus());
        exit;




       // $service = Yii::$container->get("marketingMessageService");
        $mobile=[
            0=>'13651899628',
            1=>'18358592358'

        ];
        $msg="测试短信[回N退订]";
        //$ret = $service->sendMessage($mobile,$msg,$user_id=1,$operator_name="operator_name");
        return [
            'code'=>0,
            'message'=>'success',
          //  'data'=>$ret
        ];
//        $data = strtotime(date("Y-m-d",(1476270871 + (7 + 1) * 86400))) - 1;
//        var_dump(date("Y-m-d H:i:s",$data));exit;
//        $user_mobile_contacts_mongo = UserMobileContactsMongo::findOne(['_id'=>"5974_18779124425"]);
//        if(empty($user_mobile_contacts_mongo)){
//            $user_mobile_contacts_mongo = new UserMobileContactsMongo();
//            $user_mobile_contacts_mongo->_id = "5974_18779124425";
//            $user_mobile_contacts_mongo->user_id = 5974;
//            $user_mobile_contacts_mongo->mobile = 18779124425;
//            $user_mobile_contacts_mongo->name = "钟秋萍";
//            $user_mobile_contacts_mongo->type = 0;
//            $user_mobile_contacts_mongo->text = "会大大";
//            $user_mobile_contacts_mongo->created_at = 1464774412;
//            $user_mobile_contacts_mongo->updated_at = 1464774412;
//
//            $ret = $user_mobile_contacts_mongo->save();
//            var_dump($ret);
//        }
//        $user_mobile_contacts_mongo = UserMobileContactsMongo::findOne(['_id'=>"5974_18779124425"]);
//        var_dump($user_mobile_contacts_mongo);
//        exit;
//        $user_loan_order_all = UserLoanOrderRepayment::find()->where(['user_id'=>380,'status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])->orderBy(['id'=>SORT_DESC])->limit(3)->asArray()->all();
//        foreach ($user_loan_order_all as $item){
//            var_dump($item['id']);
//           // principal
//        }
//        exit;

        $image="http://res.koudailc.com/ygb/face_recognition/479/E962B8D6501E4.jpg";
       // $image="http://res.koudailc.com/ygb/face_recognition/622356/D87E394049747.jpg";
       // $image="/tmp/E962B8D6501E4.jpg";
       // $fp = fopen($image, 'rb');
        //$content = fread($fp, filesize($image)); //二进制数据
        $content = file_get_contents($image);

        $curl = curl_init();
        $idcard_name = "蔡亮";
        $idcard_number = "411522199208282718";

        $post_data = [
            'api_key'=>'LDJSIAMp4c4etro5BDAldtPKy-qQ4n2l',
            'api_secret'=>'DXjIqSDKGaUG8IvGduv1kMFjSU_r0hc5',
            'comparison_type'=>1,
            'face_image_type'=>'raw_image',
            'idcard_name'=>$idcard_name,
            'idcard_number'=>$idcard_number,
            'image";filename="image'=>$content

        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.megvii.com/faceid/v2/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,//array('image'=>"@$image", 'api_key'=>"-WcwHwtQAOrd2PWptXyTLtpv0Un819SZ",'api_secret'=>"wyvtwwB1jxdg7V5eFNn94EbO0VO4Uu-X"),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response);

        curl_close($curl);
        return[
            'code'=>0,
            'message'=>'success',
            'data'=>$response
        ];






        $user_id = 4997;
        $data = [
            'name'=>'',
            'mobile'=>'',
            'id_num'=>'',
            'money'=>'',
            'period'=>'',
            'shop_code'=>'',
            'shop_name'=>'',
            'can_choose'=>0,
            'shop_code_list'=>[],
        ];



        $user_hdf_info = UserHfdInfo::findOne(['user_id'=>$user_id]);
        if($user_hdf_info){
            $data['shop_code'] = $user_hdf_info->shop_code;
        }
        $data['can_choose'] = empty($user_hdf_info->can_choose)?UserHfdInfo::CAN_NO_CHOOSE_SHOP:UserHfdInfo::CAN_CHOOSE_SHOP;
        $shop = [];
        $shop = Shop::find()->where(['loan_project_id'=>3,'status'=>Shop::SHOP_ACTIVE])->select(['shop_code','shop_name'])->asArray()->all();
        foreach($shop as $item){
            if(!empty($data['shop_code'])&&($data['shop_code'] == $item['shop_code'] )){
                $data['shop_name'] = $item['shop_name'] ;
            }
            if($data['can_choose']){
                $data['shop_code_list'][] = [
                    'id'=>$item['shop_code'],
                    'name'=>$item['shop_name'],
            ];
        }
        }

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>[
                'item'=>$data,
            ],
        ];


    }

    /**
     * 错误代码查询
     *
     * @name 错误代码查询 [getErrCode]
     * @author honglifeng
     */
    public function actionErrCode(){
        $code =  CodeException::$code;
        $data = array();
        foreach($code as $key=> $item){
            $data[] = [
                'code'=>$key,
                'message'=>$item,
            ];
        }
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>[
                'item'=>$data,
            ],
        ];
    }
    /**
     * 下发配置
     *
     * @name 下发配置 [getConfig]
     * @param string $configVersion 配置版本号
     * @uses 用于客户端获取url配置
     */
    public function actionConfig($configVersion)
    {

        // 此处版本号时间戳尽量取当前时间点
        $ver = '';
        $setting = Setting::findByKey('app_time_stamp'); //后台配置下发时间戳(后台可更改)
        if (isset($setting) && $setting->svalue) {
            $ver = $setting->svalue;
        }

        // 取大值为配置的时间戳
        $confVer = $confVer = YII_ENV_PROD ? max(strtotime('2016-10-09 10:00:00'), $ver) : time();
        if ($configVersion == $confVer) {
            return [
                'code' => -1,
                'message' => '配置无更新',
                'data'=>['item'=>[]],
            ];
        }

        // 约定：api域名且是json返回的情况下考虑使用https,即拼接地址的时候采用$baseUrlHttps
        $baseUrl = $this->getRequest()->getHostInfo() . $this->getRequest()->getBaseUrl();

        if (YII_ENV_PROD && version_compare($this->client->appVersion, '4.1.0') >= 0 && $this->getRequest()->getHostInfo() == 'https://api.kdqugou.com') {
            $baseUrlHttps = 'https://api.kdqugou.com';
        } else {
            $baseUrlHttps = $baseUrl;
        }

        $showBannerAdv = 1;
        $showBannerAdvArd = 1;
        $project_banner = Setting::findByKey('app_project_banner');

        if(isset($project_banner) && $project_banner->svalue){
            $svalue = unserialize($project_banner->svalue);
            $showBannerAdv = $svalue['showBannerAdv'];
            $showBannerAdvArd = $svalue['showBannerAdvArd'];
        }

        $lqd_money = UserCredit::POCKET_AMOUNT/100;
        $lx = 1000*UserCredit::CURRENT_POCKET_APR/10000*10;
        $day = Util::getLqbLoanPeriod();
        $lqd_text = "期限".$day."天,最快半小时放款";
        $fzd_money = UserRentCredit::AMOUNT_MONEY/100;
        $lx = 10000*UserRentCredit::CURRENT_APR/100;
        $fzd_text = "期限".$day."天,最快半小时放款";

        $update_msg = [];
        $clientType = Yii::$app->getRequest()->getClient()->clientType;
        $version_info = Version::findOne(['id'=>100,'type'=>1]);
        if($version_info){
            $update_msg=[
                'has_upgrade'=>$version_info->has_upgrade,
                'is_force_upgrade'=>$version_info->is_force_upgrade,
                'new_version'=>$clientType=='ios' ? $version_info->new_ios_version : $version_info->new_version,
                'new_features'=>$version_info->new_features,
                'ard_url'=>$version_info->ard_url,
                'ard_size'=>$version_info->ard_size,
                'ios_url'=>'http://itunes.apple.com/app/id1156410247?mt=8',
            ];
        }


        //还款
        $repayment_method = [
            'title'=>'支持还款支付方式：',
            'item'=>[
                ['title'=>'方式一','detail'=>"还款日系统会发起上述绑定银行卡扣款，请保证该卡余额>=还款金额，如果该卡不支持扣款请采用其他支付方式"],
                //['title'=>'方式二','detail'=>"银行卡转账到：6216610800008133523\n姓名：黄铭\n分行地址：中国银行上海市创智天地科技园支行\n转账备注：您的注册手机号"],
                ['title'=>'方式二','detail'=>"支付宝转账到：支付宝账号：ygd@staff.kdqugou.com \n转账备注：您的姓名，注册手机号"],
                ['title'=>'备注','detail'=>"转账过程中遇到任何问题皆可加入小钱包官方群：399211584咨询客服。"],
            ],
        ];
		$repayment_detail_h5 = 1;
		$ht_url = "";
		$clientType = Yii::$app->getRequest()->getClient()->clientType;
		$appVersion = Yii::$app->getRequest()->getClient()->appVersion;
		if($clientType == 'ios'){
		    $detail_h5_loan = 1;
		    $detail_h5_repayment = 0;
		    $detail_h5_loan_url = ApiUrl::toRouteMobile(['loan/loan-detail'],true);
		    $detail_h5_repayment_url = ApiUrl::toRouteMobile(['loan/loan-detail'],true);
		    if(version_compare($appVersion,"1.7.0")>=0){
		        $detail_h5_repayment = 1;
		    }
		}else{
		    if(version_compare($appVersion,"1.6.1")>=0){
		        $detail_h5_loan = 1;
		        $detail_h5_repayment = 1;
		        $detail_h5_loan_url = ApiUrl::toRouteMobile(['loan/loan-detail'],true);
		        $detail_h5_repayment_url = ApiUrl::toRouteMobile(['loan/loan-detail'],true);
		    }else{
		        $detail_h5_loan = 0;
		        $detail_h5_repayment = 0;
		        $detail_h5_loan_url = '';
		        $detail_h5_repayment_url = '';
		    }
		}

        $config = [
            'repayment_method'				=> $repayment_method,
			'repayment_detail_h5'				=> $repayment_detail_h5,
			'ht_url'				=> $ht_url,
            'detail_h5_loan'				=> $detail_h5_loan,
            'detail_h5_repayment'				=> $detail_h5_repayment,
            'detail_h5_loan_url'				=> $detail_h5_loan_url,
            'detail_h5_repayment_url'				=> $detail_h5_repayment_url,
            'jxl_h5_url'		=> ApiUrl::toRouteCredit(['credit-web/verification-jxl'],true),
            'name'				=> APP_NAMES,
            'configVersion'		=> $confVer,
            'iosVersion'		=> Yii::$app->params['appConfig']['iosVersion'],
            'androidVersion'	=> Yii::$app->params['appConfig']['androidVersion'],
            'siteUrl'			=> SITE_DOMAIN,
            'callCenter'        => '',
            'callQQService'		=> QQ_SERVICE,
            'callQQGroup'       => '',
            'companyAddress'	=> COMPANY_ADDRESS,
            'companyEmail'		=> SITE_EMAIL,
            'register_protocol_url'=>'https://api.kdqugou.com/page/detail?id=535',
            'companyAbout'		=> '
公司简介：
'.APP_NAMES.'，首批国家“普惠金融”实践企业，是一个提供综合性互联网金融理财的服务平台，隶属上海凌融网络科技有限公司。
我们为大众用户和传统金融机构搭建简单、快捷、安全的服务通道，为用户提供更广泛、更专业、更安全的投资理财服务。
核心价值观：
秉承高效严谨、勇于探索的精神，竭诚为我们的用户服务。
我们的使命：
用自己的努力和诚心，让普惠金融政策惠及每一个人，实现金融民主化的目标。
我们的愿景：
打造一个最值得信赖的互联网金融服务平台，让每一位用户的口袋都能“鼓起来”。',
            'warrantWords'		=> '招商银行千万风险保证金',
            // ios是否展示启动广告
            'showLaunchImg'		=> 1,
            // android是否展示启动广告
            'showLaunchImgArd'	=> 0,
            'showIndexAdv'		=> 0,
            'showIndexAdvArd'	=> 0,
            'showBannerAdv'		=> $showBannerAdv,
            'showBannerAdvArd'	=> $showBannerAdvArd,
            'projectDiscoverTabName' => '发现',
            'lqd_money'=>$lqd_money,
            'lqd_text'=>$lqd_text,
            'fzd_money'=>$fzd_money,
            'fzd_text'=>$fzd_text,
            'update_msg'=>$update_msg,
            'shareCookieDomain' => ['.koudailc.com','.koudaicash.com','.kdqugou.com','.kdqugou.com','.koudaixj.com'],//共享cookie的域名
            'dataUrl'			=> [
                'appDeviceReport' => "{$baseUrlHttps}/app/device-report",
                'userRegGetCode' => "{$baseUrlHttps}/user/reg-get-code",
                'userRegister' => "{$baseUrlHttps}/user/register",
                'userLogin' => "{$baseUrlHttps}/user/login",
                'userQuickLogin' => "{$baseUrlHttps}/user/quick-login",
                'userLogout' => "{$baseUrlHttps}/user/logout",
                'userChangePwd' => "{$baseUrlHttps}/user/change-pwd",
                'userSetPaypassword'=>"{$baseUrlHttps}/user/set-paypassword",
                'userChangePaypassword'=>"{$baseUrlHttps}/user/change-paypassword",
                'userResetPwdCode'=>"{$baseUrlHttps}/user/reset-pwd-code",
                'userVerifyResetPassword'=>"{$baseUrlHttps}/user/verify-reset-password",
                'userResetPwdCode' => "{$baseUrlHttps}/user/reset-pwd-code",
                'userResetPassword' => "{$baseUrlHttps}/user/reset-password",
                'userResetPayPassword' => "{$baseUrlHttps}/user/reset-pay-password",
                'userState' => "{$baseUrlHttps}/user/state",
                'userVerifyCode' => "{$baseUrlHttps}/user/verify-code",
                'infoRealVerify'=>"{$baseUrlHttps}/info/real-verify",
                'infoSavePersonInfo' => "{$baseUrlHttps}/info/save-person-info",
                'infoGetCompanyInfo' => "{$baseUrlHttps}/info/get-company-info",
                'infoSendCompanyEmail' => "{$baseUrlHttps}/info/send-company-email",
                'infoGetPersonCompanyInfo'=>"{$baseUrlHttps}/info/get-person-company-info",
                'infoSaveCompanyInfo' => "{$baseUrlHttps}/info/save-company-info",
                'infoGetReation' => "{$baseUrlHttps}/info/get-reation",
                'infoGetContacts'=>"{$baseUrlHttps}/info/get-contacts",
                'infoSaveContacts' => "{$baseUrlHttps}/info/save-contacts",
                'infoDeleteImage'=>"{$baseUrlHttps}/info/delete-image",
                'infoUploadImage'=>"{$baseUrlHttps}/info/upload-image",
                'infoGetEducationLevel'=>"{$baseUrlHttps}/info/get-education-level",
                'infoSavePersonEducation'=>"{$baseUrlHttps}/info/save-person-education",
                'infoGetPersonEducation'=>"{$baseUrlHttps}/info/get-person-education",
                'ZmSmsAuthorize'=>"{$baseUrlHttps}/creditreport/zm-sms-authorize",
                'ZmAuthorizeStatus'=>"{$baseUrlHttps}/creditreport/zm-authorize-status",
                'loanGetYgbQuota'=>"{$baseUrlHttps}/loan/get-ygb-quota",
                'loanCalculateInterests'=>"{$baseUrlHttps}/loan/calculate-interests",
                'loanApplyLoan'=>"{$baseUrlHttps}/loan/apply-loan",
                'RentLoanGetRentQuota'=>"{$baseUrlHttps}/rent-loan/get-rent-quota",
                'RentLoanGetAverageCapitalPlusInterest'=>"{$baseUrlHttps}/rent-loan/get-average-capital-plus-interest",
                'RentLoanApplyLoan'=>"{$baseUrlHttps}/rent-loan/apply-loan",
                'repaymentGetMyOrders'=>"{$baseUrlHttps}/repayment/get-my-orders",
                'repaymentGetMyLoan'=>"{$baseUrlHttps}/repayment/get-my-loan",
                'repaymentGetOneOrder'=>"{$baseUrlHttps}/repayment/get-one-order",
                'repaymentCalculation'=>"{$baseUrlHttps}/repayment/calculation",
                'repaymentPayment'=>"{$baseUrlHttps}/repayment/payment",
                'settingFeedback' => "{$baseUrlHttps}/setting/feedback",
                'settingGetInfo' => "{$baseUrlHttps}/setting/get-info",
                'quotaGetIndex'=>"{$baseUrlHttps}/quota/get-index",
                'quotaGetPersonInfo'=>"{$baseUrlHttps}/quota/get-person-info",
                'quotaSavePersonInfo'=>"{$baseUrlHttps}/quota/save-person-info",
                'quotaSaveWorkInfo'=>"{$baseUrlHttps}/quota/save-work-info",
                'quotaGetWorkInfo'=>"{$baseUrlHttps}/quota/get-work-info",
                'quotaGetAdditional'=>"{$baseUrlHttps}/quota/get-additional",
                'zmMobileApi'=>"{$baseUrlHttps}/creditreport/zm-mobile-api",
                'ZmMobileResultSave'=>"{$baseUrlHttps}/creditreport/zm-mobile-result-save",
                'BankCardInfo'=>"{$baseUrlHttps}/card-info/bank-card-info",
                'cardInfoAddDebitCard'=>"{$baseUrlHttps}/card-info/add-debit-card",
                'getLoanDetail'=>"{$baseUrlHttps}/loan/get-loan-detail",
                'cardInfoUserBank'=>"{$baseUrlHttps}/card-info/user-bank",
                'pictureDeletePic'=>"{$baseUrlHttps}/picture/delete-pic",
                'pictureGetPicList'=>"{$baseUrlHttps}/picture/get-pic-list",
                'pictureUploadImage'=>"{$baseUrlHttps}/picture/upload-image",
                'PersonCenterGetPersonCenter'=>"{$baseUrlHttps}/personal-center/get-person-center",
                'repaymentPaymentDetail'=>"{$baseUrlHttps}/repayment/payment-detail",
                'rentLoanGetConfirmLoan'=>"{$baseUrlHttps}/rent-loan/get-confirm-loan",
                'loanGetConfirmLoan'=>"{$baseUrlHttps}/loan/get-confirm-loan",
                'quotaSaveWorkInfoFzd'=>"{$baseUrlHttps}/quota/save-work-info-fzd",
                'quotaGetWorkInfoFzd'=>"{$baseUrlHttps}/quota/get-work-info-fzd",
                'installmentShop'=>"{$baseUrlHttps}/installment-shop/index",
                'infoUpLoadContacts'=>"{$baseUrlHttps}/info/up-load-contacts",
                'infoUpLoadContents'=>"{$baseUrlHttps}/info/up-load-contents",
                'infoUploadLocation'=>"{$baseUrlHttps}/info/upload-location",
                'infoGetCardList'=>"{$baseUrlHttps}/info/get-card-list",
                'infoGetCredit'=>"{$baseUrlHttps}/info/get-credit",
                'infoGetCompanyByEmail'=>"{$baseUrlHttps}/info/get-company-by-email",
                'cardInfoGetCode'=>"{$baseUrlHttps}/card-info/get-code",
                'getJxlStatus'=>"{$baseUrlHttps}/info/get-jxl-status",
                'resendPhoneCaptcha'=>"{$baseUrlHttps}/info/resend-phone-captcha",
                'postPersonPhoneCaptcha'=>"{$baseUrlHttps}/info/post-person-phone-captcha",
                'postPersonPhonePwd'=>"{$baseUrlHttps}/info/post-person-phone-pwd",
                'FacePlusIdcard'=>"{$baseUrlHttps}/credit-card/face-plus-idcard",
                //''=>"{$baseUrlHttps}/",

            ],
        ];

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>['item'=>$config],
        ];
    }


    /**
     * @name 客户端设备信息上报
     * @method post
     * 新增 devicevisitinfo 记录，同时（也许）新增 deviceinfo 记录。
     *
     * @param string $device_id 设备唯一标识
     * @param string $installed_time 安装时间，建议首次安装启动或升级时传否则传空，格式：2014-12-03 10:00:00
     * @param string $uid 用户ID，客户端有缓存就传
     * @param string $username 用户名，客户端有缓存就传
     * @param string $net_type 网络类型：[2G, 3G, 4G, WIFI]
     * @param string $IdentifierId 设备标识 idfa
     */
    public function actionDeviceReport() {
        $ret = ['code' => 0 ];
        if ($this->client->deviceName == 'iPhone Simulator') { // 如果是ios模拟器，则直接忽略
            return $ret;
        }

        $now = time();
        $device_id = trim($this->request->post('device_id'));
        $idfa = trim($this->request->post('IdentifierId', '')); #由于 appstore 的限制，这个字段可能没有
        $installed_time = trim($this->request->post('installed_time'));
        $uid = intval($this->request->post('uid'));
        $username = intval($this->request->post('username'));
        $net_type = strtoupper(trim($this->request->post('net_type')));

        // 新增或更新设备信息
        $device = DeviceInfo::findOne([
            'device_id' => $device_id,
            'app_type' => IntegralWallService::APP_TYPE_KJ,
        ]);
        if (!$device) {
            $device = new DeviceInfo();
        }
        $device->device_id = $device_id;
        $device->idfa = $idfa;
        $device->device_info = $this->client->deviceName;
        $device->os_type = $this->client->clientType;
        $device->os_version = $this->client->osVersion;
//        $device->app_type = IntegralWallService::APP_TYPE_KJ;
        $device->app_version = $this->client->appVersion;
        $device->source_tag = $this->client->appMarket;
        $device->reserved = ''; # 备用字段
        if ($installed_time) {
            $device->installed_time = \strtotime($installed_time);
        }
        if ($username) {
            $device->last_login_user = $username;
            $device->last_login_time = $now;
        }
        $device_save = $device->save();
        if (! $device_save) {
            //\yii::error(\sprintf('[IntegralWall]添加 DeviceInfo 记录失败, %s', \json_encode($device->getErrors())), IntegralWallService::LOG_CHANNEL_KDKJ);
        }

        //设备号写入redis缓存
        if (!empty($idfa)) {
            //\yii::info("[IntegralWall]idfa cache: {$idfa}", IntegralWallService::LOG_CHANNEL_KDKJ);
            $idfa_cache = IntegralWallService::cacheDeviceIdfa($idfa, IntegralWallService::APP_TYPE_KJ); # 缓存设备号
//             \call_user_func(
//                 [\yii::class, ($idfa_cache ? 'info' : 'error')],
//                 \sprintf('[IntegralWall]idfa cache: %s', ($idfa_cache ? 'success' : 'failed')),
//                 IntegralWallService::LOG_CHANNEL_KDKJ
//             );
        }

        // 新增上报记录
        $visit = new DeviceVisitInfo();
        $visit->device_id = $idfa;
        $visit->idfa = $idfa;
        $visit->reserved = $device_id; #备用字段
        $visit->uid = $uid;
        $visit->username = $username;
        $visit->visit_time = $now;
        $visit->net_type = $net_type;
        $visit->reserved = IntegralWallService::APP_TYPE_KJ;
        $visit_save = $visit->save();
        if (! $visit_save) {
            //\yii::error(\sprintf('[IntegralWall]添加 DeviceVisitInfo 记录失败, %s', \json_encode($visit->getErrors())), IntegralWallService::LOG_CHANNEL_KDKJ);
        }

        //判断是否通过点击广告激活的
        if ((!empty($idfa)) && preg_match("/^[a-zA-Z0-9-]*$/", $idfa)) {
            //\yii::info("[IntegralWall]idfa active: {$idfa}", IntegralWallService::LOG_CHANNEL_KDKJ);
            $idfa_active = IntegralWallService::activeDeviceByIdfa($idfa, IntegralWallService::APP_TYPE_KJ); //激活
//             \call_user_func(
//                 [\yii::class, ($idfa_active ? 'info' : 'error')],
//                 \sprintf('[IntegralWall]idfa active: %s', ($idfa_active ? 'success' : 'failed')),
//                 IntegralWallService::LOG_CHANNEL_KDKJ
//             );
        }

        return $ret;
    }

    /**
     * 非线上环境，生成积分墙所需的 sign
     * @throws HttpException
     */
    public function actionIntegralSign() {
        if (YII_ENV_PROD) {
            throw new HttpException(404);
        }

        $get = \yii::$app->request->get();
        $post = \yii::$app->request->post();
        $params = \array_merge($get, $post);
        if ( (!isset($params['channel'])) || (! isset(IntegralWall::$channel_key[ $params['channel'] ])) ) {
            return [
                'code' => -1,
                'message' => 'channel invalid',
            ];
        }

        $channel = IntegralWall::$channel_key[ $params['channel'] ];
        unset($params['channel']);
        $sign = IntegralWallService::createSign($params, IntegralWall::$channel_key[ $channel ]);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [ 'sign' => $sign ],
        ];
    }

    /**
     * 非线上环境，idfa 列表
     * @throws HttpException
     */
    public function actionIntegralList($channel) {
        if (YII_ENV_PROD) {
            throw new HttpException(404);
        }

        $ret = [];
        $models = KdkjIntegralWall::find(['channel' => $channel])->all();
        foreach($models as $_model) {
            $_tmp = [];
            foreach(KdkjIntegralWall::$attribute_labels as $_key => $_) {
                $_tmp[$_key] = $_model->$_key;
            }
            $ret[] = $_tmp;
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $ret,
        ];
    }

    /**
     * @name (积分墙)idfa排重
     * @param string $idfa
     * @param string $channel
     * @param string $sign
     */
    public function actionIntegralCheckIdfa() {
        $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : NULL;
        $idfa = isset($_REQUEST['idfa']) ? $_REQUEST['idfa'] : NULL;
        $sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : NULL;
        if (empty($idfa) || empty($channel) || empty($sign)) {
            return [
                'code' => -1,
                'message' => 'param missing',
            ];
        }

        $channel_key = isset(IntegralWall::$channel_key[$channel]) ? IntegralWall::$channel_key[$channel] : '';
        if (empty($channel_key)) {
            return [
                'code' => -1,
                'message' => 'channel invalid',
            ];
        }

        $mk_sign = IntegralWallService::createSign([
            'idfa' => $idfa,
            'channel' => $channel,
        ], $channel_key);
        if ($mk_sign != $sign) {
            return [
                'code' => -1,
                'message' => 'sign invalid',
            ];
        }

        $ret = [];
        $idfas = \explode(',', $idfa);
        foreach($idfas as $_idfa) {
            $ret[ $_idfa ] = KdkjIntegralWall::find()->where(['idfa' => $_idfa])->count();
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $ret,
        ];
    }

    /**
     * @name (积分墙)点击请求
     * 缓存 idfa 进 redis，新增 KdkjIntegralWall 记录
     * @param string $channel
     * @param string $idfa
     * @param string $sign
     */
    public function actionIntegralClick() {
        $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : NULL;
        $idfa = isset($_REQUEST['idfa']) ? $_REQUEST['idfa'] : NULL;
        $sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : NULL;
        if (empty($sign) || empty($idfa) || empty($channel)) {
            return [
                'code' => -1,
                'message' => 'param missing',
            ];
        }

        if (! preg_match("/^[a-zA-Z0-9-]*$/", $idfa)) {
            return [
                'code' => -1,
                'message' => 'invalid idfa',
            ];
        }

        if (! isset(IntegralWall::$channel_key[ $channel ])) {
            return [
                'code' => -1,
                'message' => 'invalid channel',
            ];
        }

        $mk_sign = IntegralWallService::createSign([
            'idfa' => $idfa,
            'channel' => $channel,
        ], IntegralWall::$channel_key[ $channel ]);
        if ($sign != $mk_sign) {
            return [
                'code' => -1,
                'message' => 'invalid sign',
            ];
        }

        $search = KdkjIntegralWall::findOne([
            'idfa' => $idfa,
        ]);
        if ($search) {
            if (empty($search->channel) || $search->channel == $channel) {
                if (empty($search->channel)) {
                    $search->channel = $channel;
                    if (! $search->save()) {
                        //\yii::error('[IntegralWall] channel update failed', IntegralWallService::LOG_CHANNEL_KDKJ);
                        return [
                            'code' => -1,
                            'message' => 'create record error',
                        ];
                    }
                }

                return [
                    'code' => 0,
                    'message' => 'success',
                ];
            }
            else {
                return [
                    'code' => -1,
                    'message' => 'exists',
                ];
            }
        }

        //else create new record
        $cache = new KdkjIntegralWall();
        $cache->idfa = $idfa;
        $cache->channel = $channel;
        $save = $cache->save();
        if ($save) {
            return [
                'code' => 0,
                'message' => 'success',
            ];
        }
        else {
            return [
                'code' => -1,
                'message' => 'create record error',
            ];
        }
    }

    /**
     * @name (积分墙)检查是否激活
     * 通过 idfa 和 channel 判断 KdkjIntegralWall 记录是否被激活
     * @param string $idfa
     * @param string $channel
     * @param string $sign
     */
    public function actionIntegralCheckActive() {
        $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : NULL;
        $idfa = isset($_REQUEST['idfa']) ? $_REQUEST['idfa'] : NULL;
        $sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : NULL;
        if (empty($sign) || empty($idfa) || empty($channel)) {
            return [
                'code' => -1,
                'message' => 'param missing',
            ];
        }

        if (! preg_match("/^[a-zA-Z0-9-]*$/", $idfa)) {
            return [
                'code' => -1,
                'message' => 'invalid idfa',
            ];
        }

        if (! isset(IntegralWall::$channel_key[ $channel ])) {
            return [
                'code' => -1,
                'message' => 'invalid channel',
            ];
        }

        $mk_sign = IntegralWallService::createSign([
            'idfa' => $idfa,
            'channel' => $channel,
        ], IntegralWall::$channel_key[ $channel ]);
        if ($sign != $mk_sign) {
            return [
                'code' => -1,
                'message' => 'invalid sign',
            ];
        }

        $search = KdkjIntegralWall::findOne([
            'idfa' => $idfa,
            'channel' => $channel,
        ]);
        if ( empty($search) ) {
            return [
                'code' => -1,
                'message' => 'not exists',
            ];
        }

        if ($search->is_active == IntegralWall::IS_ACTIVE_YES) {
            return [
                'code' => 0,
                'message' => 'active',
            ];
        }
        return [
            'code' => -1,
            'message' => 'not active',
        ];
    }

    /**
     * 验证卡
     **/
    public function actionCardBin(){
        //get post
        $post=$_POST;
        if(!isset($post['merchant']) || !isset($post['cardno']) || !isset($post['token']) || !isset($post['time'])){
            echo json_encode(array('err_code'=>11009,'reason'=>'fail','result'=>'传递参数有误！'));
            return;
        }
        Yii::info(json_encode($post),'card_bin_info');

        $merchant=trim($post['merchant']);
        $cardno=trim($post['cardno']);
        $token=trim($post['token']);
        $time=trim($post['time']);
        if($merchant!='dichang' || !is_numeric($cardno) || strlen($token)!=32){
            echo json_encode(array('err_code'=>10000,'reason'=>'fail','result'=>'传递参数有误！'));
            return;
        }

        //卡号的长度在16位-19位之间 不能少于16位 大于19位
        if(strlen($cardno)<16 || strlen($cardno)>19){
            echo json_encode(array('err_code'=>10001,'reason'=>'fail','result'=>'卡号的长度在16位-19位之间 不能少于16位 大于19位！'));
            return;
        }

        unset($post['token']);
        $post['md5_key']='5830621275e66078fc9359308d23a730';
        ksort($post);
        $md5_str='';
        foreach ($post as $key=>$val){
            $md5_str.=trim($val);
        }
        $new_token=md5($md5_str);
        if($token!=$new_token){
            echo json_encode(array('err_code'=>10002,'reason'=>'fail','result'=>'验证token失败！'));
            return;
        }

        //大于3分钟将失效
        $dftime=time()-intval($time);
        if($dftime<0 || $dftime>180){
            echo json_encode(array('err_code'=>10003,'reason'=>'fail','result'=>'本次请求已失效！'));
            return;
        }
        $prefixKey=substr($cardno,0,5);
        $table='fanwe_card_bin';
        $where=" where prefixKey='$prefixKey' ";
        $sql="select cardBin,bankId,cpBankName from ".$table.$where;
        $db =Yii::$app->get('db');
        $data=$db->createCommand($sql)->queryAll();
        if(empty($data)){
            echo json_encode(array('err_code'=>0,'reason'=>'success','result'=>array()));
            return;
        }
        if(count($data)==1){
            $bank=array('bankname'=>trim($data[0]['cpBankName']),'cardtype'=>'借记卡','abbreviation'=>trim($data[0]['bankId']));
            echo json_encode(array('err_code'=>0,'reason'=>'success','result'=>$bank));
            return;
        }
        $prefixKey=substr($cardno,0,6);
        $sgbank=array();$newsgbank=array();
        foreach ($data as $key=>$val){
            if(count($newsgbank)==0){
                $newsgbank=$val;
            }
            if(strstr($val['cardBin'],$prefixKey)){
                $sgbank=$val;
                break;
            }
        }
        if(count($sgbank)==0 && count($newsgbank)>0){
            $sgbank=$newsgbank;
        }
        unset($data);
        if(count($sgbank)>0){
            $bank=array('bankname'=>trim($sgbank['cpBankName']),'cardtype'=>'借记卡','abbreviation'=>trim($sgbank['bankId']));
            echo json_encode(array('err_code'=>0,'reason'=>'success','result'=>$bank));
            return;
        }
        echo json_encode(array('err_code'=>0,'reason'=>'success','result'=>array()));
    }
}

