<?php
namespace credit\controllers;

use common\helpers\MessageHelper;
use common\models\WeixinUser;
use Yii;
use common\services\WeixinService;
use yii\helpers\Url;

/**
 * WeixinAPIController 微信所有API接口
 */
class WeixinApiController extends BaseController
{
    // 测试
    // 微信号：  gh_c1dc1bfc9347

    public $enableCsrfValidation = false;

    public $token = WEIXIN_Token; // token TODO
    public $appid = WEIXIN_APPID;
    public $secret = WEIXIN_SECRET;

    //3eLST1X3SnbmO7FPokXZEDsqLmS24LWisDFiiS2fMwQ EncodingAESKey

    public $debug = true;//是否debug的状态标示，方便我们在调试的时候记录一些中间数据
    public $setFlag = true;

    /** *************************  方法入口 START  *********************** */

    /**
     * 接收自定义菜单事件
     */
    public function actionIndex(){
        //签名效验     忽删 第一次效验用 。。。
//        if ($this->checkSignature()) {
//            echo isset($_GET['echostr']) ? $_GET['echostr'] : '';
//        }

        //回复数据方法
        $this->responseMsg();
    }

    /** *************************  方法入口 END  *********************** */

    /** *************************  消息验证 模块 start ***********************  */
    protected $weixinService;

    public function __construct($id, $module, WeixinService $weixinService, $config = []){
        $this->weixinService = $weixinService;
        parent::__construct($id, $module, $config);
    }
    
    public static function getIndexUrl(){
        $url = Url::toRoute(['site/index'],true);
        return $url;
    }

    /**
     * 相关回复
     */
    public function responseMsg(){
        $postStr = file_get_contents("php://input", 'r'); //$GLOBALS["HTTP_RAW_POST_DATA"]; //返回回复数据

        $jump_url = url::to(['weixin-page/user-login']);
        $jump_url_res = \str_replace('credit/','newh5/', $jump_url);
        $jump_url_register = url::to(['weixin-page/register-xybt-one']);
        $jump_url_register_res = \str_replace('credit/','newh5/', $jump_url_register);
        $weixin_url = $baseUrl = $this->request->getHostInfo();//当前域名的地址
        $weixinService = Yii::$app->weixinService;
        $url = $weixinService->geOpenid($jump_url_res,$weixin_url);//
        $url2 = $weixinService->geOpenid($jump_url_register_res,$weixin_url);//

        //微信注册页面地址
        if (!empty($postStr)) {
            try {
                $postObj = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $MsgType = strtoupper($postObj['MsgType']);//消息类型
                $openid = $postObj['FromUserName'];
                if ($MsgType=='EVENT') {
                    $MsgEvent = strtoupper($postObj['Event']);//获取事件类型ll
                    if ($MsgEvent=='CLICK'){
                        //点击事件
                        $EventKey = $postObj['EventKey'];//菜单的自定义的key值，可以根据此值判断用户点击了什么内容，从而推送不同信息
                        switch($EventKey)
                        {
                            case "CONTACT_CUSTOMER" :  // 联系客服
                                //要返回相关内容
                                $msg = $this->weixinService->getCustomerInfo();
                                $this->makeText($postObj,$msg);
                                break;

                            case "QUERY_ACCOUNT_INFO" ://查询账户信息
                                $msg = $this->weixinService->getUserInfo($openid,$url);
                                $this->makeText($postObj,$msg);
                                break;
                            default:
                                break;

                        }
                    }
                    else if ($MsgEvent=='SUBSCRIBE') {  //subscribe
                        $weixiUser = WeixinUser::find()->select('*')->where(['openid' => $openid])->one();
                        if($weixiUser)
                        {
                            $weixiUser->status = 1;//取消关注
                            $weixiUser->save();
                        }

                        //订阅事件
                        $msg = $this->weixinService->getMsg();
                        $this->makeText($postObj,$msg);
                    }
                    else if ($MsgEvent=='UNSUBSCRIBE')
                    {
                        //取消关注
                        $weixiUser = WeixinUser::find()->select('*')->where(['openid' => $openid])->one();
                        if($weixiUser)
                        {
                            $weixiUser->status = 0;//取消关注

                            if($weixiUser->unsubscribe_time == 0)
                            {
                                $weixiUser->unsubscribe_time = time();
                            }
                            $weixiUser->save();
                        }

                    }
                }
                /*
                else if (!empty($postObj['keyword']))
                {   //回复用户输入事件
                    /* $this->makeText($postObj,'nihaop');
                }else if ($MsgType == 'TEXT'){   //回复用户输入事件
                $this->makeText($postObj,'欢迎加入口袋理财，您的留言：' . $postObj['Content'] . ' 已收到');
                }*/
            }
            catch (\Exception $e) {
                MessageHelper::sendSMS(NOTICE_MOBILE,   date('Y-m-d H:i:s').':' . $e->getMessage());
            }
        }
        else {
            echo '没有任何消息传递';
            $this->write_log('responseMsg function no message ... ');
        }
    }

    //回复文本消息
    public function makeText($msg = array() , $text = '暂无内容'){
        $CreateTime = time ();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        echo sprintf($textTpl, $text, $FuncFlag);
        exit;
    }

    //根据数组参数回复图文消息
    public function makeNews($msg,$newsData = array()){
        $CreateTime = time ();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $newTplHeader = "<xml>
            <ToUserName><![CDATA[{$msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <ArticleCount><![CDATA[%s]]></ArticleCount><Articles>";
        $newTplItem = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
        $newTplFoot = "</Articles>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        $Content = '';
        $itemsCount = count ($newsData['items']);
        $itemsCount = $itemsCount < 10 ? $itemsCount : 10;//微信公众平台图文回复的消息一次最多10条
        if ($itemsCount) {
            foreach ($newsData['items'] as $key => $item) {
                if ($key <= 9) {
                    $Content .= sprintf ($newTplItem, $item['title'], $item['description'], $item['picurl'], $item['url']);
                }
            }
        }
        $header = sprintf ($newTplHeader, $newsData['content'], $itemsCount);
        $footer = sprintf ($newTplFoot, $FuncFlag);
        echo $header . $Content . $footer;
        exit;
    }

    public function valid()
    {
        if ($this->checkSignature()) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                echo $_GET['echostr'];
                exit;
            }
        } else {
            $this->write_log('认证失败');
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"] ?? '';
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $tmpArr = array($this->token, $timestamp, $nonce);


        sort ($tmpArr, SORT_STRING);
        $tmpStr = implode ($tmpArr);
        $tmpStr = sha1 ($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //这里是你记录调试信息的地方  请自行完善   以便中间调试<br>　　
    private function write_log($log) {
        Yii::error($log);
    }

    /** *************************   消息验证 模块 end *************************   */



    /**
     * 封装的获取微信用户信息方法
     * @return bool|mixed
     */
    public function get_bll_userinfo(){
        $access_token = !empty($_SESSION['access_token']) ? $_SESSION['access_token'] : '';
        if (empty($access_token)){
            $access_token = $this->get_access_token();
        }
        return $this->get_user_info($access_token,$this->openid);
    }

    /**
     * 保存SESSION值
     * @param $key
     * @param $val
     */
    public function setsession($key,$val){
        if (isset($key) && isset($val)){
            $lifeTime = 7200;
            session_set_cookie_params($lifeTime);
            $_SESSION[$key] = $val;
        }
    }

    /**
     * 获取授权token
     *
     * @param string $code 通过get_authorize_url获取到的code
     */
    public function get_code_token($code = ''){
        if (empty($code)){
            return false;
        }
        $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->secret."&code={$code}&grant_type=authorization_code";
        $token_data = self::postData($token_url);
        return json_decode($token_data,TRUE);
    }

    /**
     * 根据OPENID获取微信的用户信息
     * @param $access_token
     * @param $openid
     */
    public function get_user_info($access_token,$openid){
        $params = array('access_token'=>$access_token,'openid'=>$openid,'lang'=>'zh_CN');
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?'.http_build_query($params);
        $json_data = self::postData($url);
        $result = json_decode($json_data,true);
        if (isset($result['subscribe']) && $result['subscribe'] == 1){   //获取成功
            return $result;
        }

        if (in_array($result['errcode'] , $this->token_err)){
            $access_token = $this->get_access_token();
            self::get_user_info($access_token,$this->openid);
        }

        return false;
    }

    /**
     * 获取ACCESS_TOKEN接口
     * @param string $appid
     * @param string $secret
     * @return mixed  //过期时间 问题
     */
    public function get_access_token(){
        if (!empty($_SESSION['access_token'])) {
            return $_SESSION['access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $json_data = self::postData($url);
        $result = json_decode($json_data,true);
        if (isset($result['access_token'])){
            $this->setsession('access_token',$result['access_token']);
            return $result['access_token'];
        }
        return false;
    }
    public function actionGetAllToken(){
        $code = Yii::$app->request->post('code','');
        if($code == 'wangweitest'){
            var_dump($this->get_access_token());die;
        }
    }

    /**
     * 验证授权
     * @param string $access_token
     * @param string $open_id
     */
    public function check_access_token($access_token = '', $open_id = ''){
        if($access_token && $open_id){
            $url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $result = self::postData($url);
            if($result[0] == 200){
                return json_decode($result[1], TRUE);
            }
        }
        return FALSE;
    }

    /**
     * 验证用户网页授权By_zhufeng
     * @param string $access_token
     * @param string $open_id
     */
    public static function check_auth_access_token($access_token = '', $open_id = ''){
        if($access_token && $open_id){
            $url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $result = self::postData($url);
            $result = json_decode($result, TRUE);
            if($result['errcode'] == 0){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * CURL获取
     * @param $url
     * @return mixed
     */
    public static function postData($url , $data = ''){
        $timeout = 1000;
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在

        if (!empty($data)){
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        }
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
            return $tmpInfo; // 返回数据
    }

    /**
     * 网页授权获取微信用户信息
     * @param $access_token
     * @param $openid
     */
    public function get_auth_user_info($access_token,$openid){
        $get_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $json_data = self::postData($get_url);
        $userinfo = json_decode($json_data,true);
        if (isset($userinfo['errcode']) && $userinfo['errcode']){  //获取失败
            return false;
        }
        return $userinfo;
    }

}
