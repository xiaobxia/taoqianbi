<?php
namespace common\components;

use common\base\LogChannel;
use common\helpers\MessageHelper;
use Yii;
use yii\web\Controller;
use yii\base\UserException;
use common\models\LoanPerson;
use yii\web\ForbiddenHttpException;
use common\helpers\Util;
/**
 * Base controller
 *
 * @property \yii\web\Request $request The request component.
 * @property \yii\web\Response $response The response component.
 * @property \common\models\Client $client The Client model.
 */
abstract class BaseController extends Controller {

    /**
     * 获得请求对象
     */
    public function getRequest()
    {
        return Yii::$app->getRequest();
    }

    /**
     * 获得返回对象
     */
    public function getResponse()
    {
        return Yii::$app->getResponse();
    }

    /**
     * 获得请求客户端信息
     * 从request中获得，便于调试，有默认值
     */
    public function getClient()
    {
        return Yii::$app->getRequest()->getClient();
    }

    public function params()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * 判断是否是app打开
     * @return boolean
     */
    public function isFromApp() {
        return $this->isFromXjk()
            || $this->isFromHBJB()
            || $this->isFromXYBT()
            || $this->isFromWZD()
            || $this->isFromSXD();
    }

    /**
     * 判断是否是微信打开
     * @return boolean
     */
    public function isFromWeichat(){
        return @strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') ? true : false;
    }

    /**
     * 判断是否是极速荷包
     * @return bool
     */
    public function isFromXjk() {
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_XYBT) ? true : false;
//        return true;
    }

    /**
     * 判断是否是汇邦钱包
     * @return bool
     */
    public function isFromHBJB(){
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_HBQB) ? true : false;
    }

    /**
     * 判断是否是温州贷借款
     * @return bool
     */
    public function isFromWZD(){
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_WZD_LOAN) ? true : false;
    }

    /**
     * 判断是否是随心贷
     * @return bool
     */
    public function isFromSXD(){
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_SX_LOAN) ? true : false;
    }

    /**
     * 判断是否来自极速荷包
     * @return bool
     */
    public function isFromXYBT() {
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_XYBT) ? true : false;
    }

    /**
     * 判断是否来自开心借
     * @return bool
     */
    public function isFromKxjie() {
        return @stristr($_SERVER['HTTP_USER_AGENT'], LoanPerson::USER_AGENT_KXJIE) ? true : false;
    }

    /**
     * 获取 HTTP_USER_AGENT 中传递的约定ua
     * @retunr bool
     */
    public function getUserAgent() {
        $res = '';
        new LoanPerson();
        foreach (LoanPerson::$user_agent_list as $key) {
            if (@stristr( $_SERVER[ 'HTTP_USER_AGENT' ], $key )) {
                $res = $key;
                break;
            }
        }

        if (empty($res)) {
            try {
                $msg = sprintf( 'empty_source_from_ua, %s %s',
                    json_encode($_SERVER[ 'HTTP_USER_AGENT' ]),
                    json_encode([
                        getenv('HTTP_CLIENT_IP'),
                        getenv('HTTP_X_FORWARDED_FOR'),
                        getenv('REMOTE_ADDR'),
                        $_SERVER['REMOTE_ADDR']
                    ])
                );

                \yii::warning( $msg, LogChannel::SYSTEM_GENERAL );
            }
            catch (\Exception $e) {
                \yii::warning( sprintf( 'empty_source_from_ua, %s', $e), LogChannel::SYSTEM_UA_MISSING );
            }

            $res = LoanPerson::USER_AGENT_XYBT; // default
        }

        return $res;
    }

    /**
     * 获取客户端版本 字符串
     */
    public function getClientVersion() {
        $clent_version = "0";

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        new LoanPerson();
        if ( $user_agent && $this->isFromApp() ) {
            foreach (LoanPerson::$user_agent_list as $key) {
                $key .= '/';
                if (@strpos($user_agent, $key) !== false) {
                    $ver_str   = @strstr($user_agent, $key);
                    $agent_arr = @explode("/", $ver_str);
                    if (is_array($agent_arr)) {
                        $clent_version = end($agent_arr);
                        break;
                    }
                }
            }

        }

        return $clent_version;
    }

    /**
     * 翻译文字
     * @param unknown $key
     * @param string $channel
     */
    public function t($key, $channel=''){
        return \common\helpers\Util::t($key, $channel);
    }

    /**
     * 统一设置cookie
     * @param unknown $name
     * @param unknown $val
     * @param unknown $expire
     * @return boolean
     */
    public function setCookie($name,$val,$expire=0){
        $cookieParams = ['httpOnly' => true, 'domain'=>YII_ENV_PROD ? APP_DOMAIN : ''];
        if($expire !== null){
            $cookieParams['expire'] = $expire;
        }
        $cookies = new \yii\web\Cookie($cookieParams);
        $cookies->name = $name;
        $cookies->value = $val;
        $this->response->getCookies()->add($cookies);

        $reqCookie = $this->request->cookies;
        $cookieVal = $reqCookie->get($name);
        if(!$cookieVal)
//            \Yii::info('Cookie 种植失败');
        return true;
    }
    /**
     * 统一获取cookie
     * @param unknown $name
     * @return mixed
     */
    public function getCookie($name){
        $val = $this->request->getCookies()->getValue($name);
        if($val){
            return $val;
        }
        $val = $this->response->getCookies()->getValue($name);
        return $val;
    }

    public function autoCheckPayPwdSign(){
        $params = $this->params();
        if( !isset($params['pay_pwd_sign']) ){
            throw new UserException('缺少必要参数');
        }
        $user_id = Yii::$app->user->id;
        $sign_key = \common\models\UserPayPassword::PAY_PWD_CHECK_KEY."_{$user_id}";
        $trade_pwd_sign = Yii::$app->redis->executeCommand('GET', [$sign_key]);
        if( $params['pay_pwd_sign'] != $trade_pwd_sign || !$trade_pwd_sign ){
            throw new UserException('验证失败，稍后重试');
        }else{
            Yii::$app->redis->executeCommand('DEL', [$sign_key]);
        }
    }

    /**
     * 获取静态资源URL全路径
     * @param unknown $path
     */
    public function staticUrl($path, $type='') {
        if (!$path) {
            return '';
        }
        if (2 == $type) {
            if (YII_ENV_PROD) {
                return 'http://'.SITE_DOMAIN.'/newh5/web/'.$path;//这里有个坑 https
            }
        }

        return Yii::$app->getRequest()->getAbsoluteBaseUrl().'/'.$path;
    }

    /**
     * 获取source_id
     * @return int
     */
    public function getSource(){
        new LoanPerson();
        $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        //判断来源是否在极速荷包
        if ($user_agent = $this->getUserAgent()) {
            $source = LoanPerson::$user_agent_source[$user_agent];
        }

        return $source;
    }
    /**
     * 获取公司名字
     */
    public function getCompany($fan_id ='',$zfb = ''){
        $company = COMPANY_NAME;
        return $company;
    }

    # 返回A类配置
    public function getSubInfoTypeA(){
        return ['company_name'=>COMPANY_NAME];
        # return Yii::$app->params['TypeA'];
    }

    /**
     * 获取app名称
     */
    public function getAppName(){
        $app_name = APP_NAMES;
        $source = $this->getSource();
        if(isset($source)){
            new LoanPerson();
            $app_name = LoanPerson::$person_source[$source];
        }
        return $app_name;
    }

    /**
     * 获取主题色
     */
    public function getColor($source = ''){
        $color = '#1ec8e1';//默认
        if(empty($source) || !isset($source)){
            $source = $this->getSource();
        }

        switch ($source){
            case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT://极速荷包
                $color = '#1ec8e1';//默认
                break;
        }
        return $color;
    }
    /**
     * 统一返回全部请求类型
     */
    public function getClientInfo(){
        $infos = $this->client;
        $header = $this->request->headers;
        $headers = array_values((array)$header);
        $clientyype = $headers[0]['clienttype'][0]??'';
        $devicename = $headers[0]['devicename'][0]??'';
        $appversion = $headers[0]['appversion'][0]??'';
        $osversion = $headers[0]['osversion'][0]??'';
        $appmarket = $headers[0]['appmarket'][0]??'';
        $packname = $headers[0]['packname'][0]??'';
        if(
            !empty($clientyype) ||
            !empty($devicename) ||
            !empty($appversion) ||
            !empty($osversion)  ||
            !empty($appmarket)  ||
            !empty($packname)
        ){
            $infos = (object)[];
            $infos->clientType = $clientyype;
            $infos->deviceName = $devicename;
            $infos->appVersion = $appversion;
            $infos->osVersion = $osversion;
            $infos->appMarket = $appmarket;
            $infos->packname = $packname;
        }
        return $infos;
    }
    /**
     * 兼容旧的包返回对应的APP名称
     */
    public function getVersion(){
        $header = $this->request->headers;
        $appMarket = $header->get('appmarket');
        $clientType = $header->get('clientType');

        //兼容老包
        $info = $this->getClientInfo();
        if(!$clientType){
            $clientType = $info->clientType;
        }
        if(!$appMarket){
              $appMarket = $info->appMarket;
        }

            if($clientType == 'android'){
                if(stristr($appMarket,'xybt_fuli')){//三级渠道
                    $appname = 'xybt_fuli';
                }elseif (stristr($appMarket,'xybt_fund')){
                    $appname = 'xybt_fund';
                }elseif (stristr($appMarket,'xybt_xjbtfuli')){
                    $appname = 'xybt_xjbt_fuli';
                }elseif (stristr($appMarket,'hbqb')){
                    $appname = 'hbqb';
                }elseif (stristr($appMarket,'sxdai')){
                    $appname = 'sxdai';
                }elseif (stristr($appMarket,'wzdai_loan')){
                    $appname = 'wzdai_loan';
                }elseif (stristr($appMarket,'xybt')){
                    $appname = 'xybt';
                }
            }else if($clientType == 'ios'){
                switch ($appMarket){
                    case 'AppStore':$appname = 'xybt';break;
                    case 'AppStoreWelfare':$appname = 'xybt_fuli';break;
                    case 'AppStoreWZD':$appname = 'wzdai_loan';break;
                    case 'AppStorehbqb':$appname = 'hbqb';break;
                    case 'AppStoreFund':$appname = 'yxbt_fund';break;
                    case 'AppStoreSxd':$appname = 'sxdai';break;
                    case 'AppStoreXjbt':$appname = 'xybt_xjbt_fuli';break;
                    default: $appname = 'xybt';break;
                }
            }
            if(empty($appname)){
                $appname = 'xybt';
            }
        return $appname;
    }
}
