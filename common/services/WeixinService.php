<?php

namespace common\services;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use common\helpers\Curl;
use common\models\UserLoanOrderRepayment;
use common\api\RedisQueue;
use common\external\WeixinJssdk;
use common\models\UserCreditTotal;
use common\models\WeixinUser;
use common\base\RESTComponent;

class WeixinService extends RESTComponent
{
    /**
     * 只能获取用户openid
     */
    const SNSAPI_BASE = 'snsapi_base';

    /**
     * （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     */
    const SNSAPI_USERINFO = 'snsapi_userinfo';

    /**
     * appID
     * app编号
     * ------
     * @author Verdient。
     */
    public $appID;

    /**
     * @var $appSecret
     * 秘钥
     * ---------------
     * @author Verdient。
     */
    public $appSecret;

    /**
     * @var $appToken
     * App令牌
     * --------------
     * @author Verdient。
     */
    public $appToken;

    /**
     * @var $_tokenErrorCode
     * token错误的状态码
     * ---------------------
     * @author Verdient。
     */
    protected $_tokenErrorCode = [40001, 41001];

    /**
     * init()
     * 初始化
     * ------
     * @inheritdoc
     * -----------
     * @author Verdient。
     */
    public function init(){
//        parent::init();
        if(!$this->appID){
            $this->appID = WEIXIN_APPID;
//            throw new InvalidConfigException('appID must be set');
        }
        if(!$this->appSecret){
            $this->appSecret = WEIXIN_SECRET;
//            throw new InvalidConfigException('appSecret must be set');
        }
//        if(!$this->appToken){
//            throw new InvalidConfigException('appToken must be set');
//        }
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
        $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=". $this->appID ."&secret=".$this->appSecret."&code={$code}&grant_type=authorization_code";
        $token_data = self::postData($token_url);
        return json_decode($token_data,TRUE);
    }

    /**
     * 根据OPENID获取微信的用户信息
     * @param $openid
     * @param $access_token
     */
    public function get_user_info($openid,$access_token=null){
        if(!$access_token){
            $access_token = $this->get_access_token();
        }
        $params = array('access_token'=>$access_token,'openid'=>$openid,'lang'=>'zh_CN');
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?'.http_build_query($params);
        $json_data = self::postData($url);
        $result = json_decode($json_data,true);
        if (isset($result['subscribe'])){   //获取成功
            return $result;
        }
        if (in_array($result['errcode'] , $this->_tokenErrorCode)){
            self::get_user_info($openid);
        }
        return false;
    }

    /**
     * 获取ACCESS_TOKEN接口
     * @param string $appid
     * @param string $secret
     * @return mixed
     */
    public function get_access_token(){
        $jssdk = new WeixinJssdk($this->appID, $this->appSecret);
        return $jssdk->getAccessToken();
    }


    /**
     * 验证授权
     * @param string $access_token
     * @param string $open_id
     */
    public function check_access_token($access_token = '', $open_id = ''){
        if($access_token && $open_id){
//            $url = $this->_requestUrl['check_access_token'] . '?access_token=' . $access_token . '&openid=' . $open_id . '&lang=zh_CN';
            $url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $result = self::postData($url);
            if($result[0] == 200){
                return json_decode($result[1], TRUE);
            }
        }
        return FALSE;
    }

    /**
     * 验证用户网页授权
     * @param string $access_token
     * @param string $open_id
     */
    public function check_auth_access_token($access_token = '', $open_id = ''){
        if($access_token && $open_id){
//            $url = $this->_requestUrl['check_auth_access_token'] . '?access_token=' . $access_token . '&openid=' . $open_id . '&lang=zh_CN';
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

    public function get_access_token_redis(){
        $key = 'weixin_token_api';
        if(!empty(RedisQueue::get(['key'=>$key]))){
            return RedisQueue::get(['key'=>$key]);
        }
        if (!empty($_SESSION['access_token'])) {
            return $_SESSION['access_token'];
        }
        $appid = $this->appID;
        $secret = $this->appSecret;
//        $url = $this->_requestUrl['get_access_token_redis'] . '?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $json_data = self::postData($url);
        $result = json_decode($json_data,true);
        if (isset($result['access_token'])){
            return RedisQueue::set(["expire" => 7100, "key" => $key, "value" => $result['access_token']]);
        }
        return false;
    }


    /**
     * 网页授权获取微信用户信息
     * @param $access_token
     * @param $openid
     */
    public function get_auth_user_info($openid,$access_token=null){
        if(!$access_token){
            $access_token = $this->get_access_token();
        }
//        $get_url = $this->_requestUrl['get_auth_user_info'] . '?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $get_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $json_data = self::postData($get_url);
        $userinfo = json_decode($json_data,true);
        if (isset($userinfo['errcode']) && $userinfo['errcode']){  //获取失败
            return false;
        }
        return $userinfo;
    }

    /**
     * 获取微信短链
     * @param string $url
     * @return bool|string
     */
	public function getShortUrl($url)    {
        $token = $this->get_access_token();
        $data = ['action'=>'long2short','long_url'=>$url];
//        $ret = self::postData($this->_requestUrl['getShortUrl'] . '?access_token=' . $token . ',' . json_encode($data));
        $ret = self::postData("https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$token}",json_encode($data));

        $retData = json_decode($ret);

        if(!isset($retData->errcode)||$retData->errcode!=0)
            return false;
        else
            return $retData->short_url;
    }

    /**
     * 配置微信的菜单
     */
    public function getMenu($data){
        $access_token = $this->get_access_token();
//        $url = $this->_requestUrl['getMenu'] . '?access_token=' . $access_token;
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $ret = self::postData($url,$data);
        $retData = json_decode($ret);
        if($retData->errcode!=0){
            return $retData->errcode;
        }else{
            return true;
        }
    }
    /**
     *查询用户的账户信息
     */

    public function getUserInfo($openid,$url){
        //查询用户是否绑定过账号
        $weixin_res = $this->checkUser($openid);
        if($weixin_res == false){
            $msg = "请先绑定账号再进行操作,\r\n";
            $msg .= "地址为：<a href='$url'>点击绑定</a>";
        }else{
            $money = UserCreditTotal::find()
                ->where(['user_id'=>$weixin_res->uid])
                ->select('amount,used_amount,locked_amount')->one();
            $amount = $money->amount/100;
            $user_amount = $money->used_amount/100;
            $locked_amount = $money->locked_amount/100;
            $no_user_amount = $amount-$locked_amount-$user_amount;
            //优惠券
            $time = time();
            $num = 0;
            //借款信息
            $order_info = UserLoanOrderRepayment::find()->where(['user_id'=>$weixin_res->uid])->orderBy('id DESC')->limit(1)->one();
            $msg = "您好，截止目前您账户信息如下:\r\n";//总额度
            $msg .= "你的总额度为：$amount.00元\r\n";//总额度
            $msg .= "你的可用额度：$no_user_amount.00元\r\n";//可用额度
            $msg .= "剩余可用券：$num 张\r\n";//可用券
            if(empty($order_info)){
                $msg .= "待还金额：无\r\n";
                $msg .= "计划还款日期：无\r\n";
            }elseif (!empty($order_info) && $order_info->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                $msg .= "待还金额：无\r\n";
                $msg .= "计划还款日期：无\r\n";
            }else{
                $order_info_money = $order_info->total_money;// 待还金额
                $pay_money = sprintf("%0.2f",$order_info_money / 100);
                $pay_money_time = $order_info->plan_fee_time;
                $pay_money_time_res = date('Y年m月d日',$pay_money_time);
                $msg .= "待还金额：$pay_money.元\r\n";
                $msg .= "计划还款日期：$pay_money_time_res\r\n";
            }
            $msg .= "如有任何疑问请咨询客服";
        }
        return $msg;
    }
    /**
     * 返回客服消息
     */
    public function getCustomerInfo(){
        $msg = "您好,有什么可以帮助您?\r\n";
        $msg .="客服QQ\r\n";
        $msg .="客服电话\r\n";
        $msg .="工作时间工作日 9:00-18:00\r\n";
        $msg .="节假日,双休日不提供客服服务\r\n";
        return $msg;
    }
    /**
     * 查询用户是否绑定过微信账号
     */
    public function checkUser($openid){
        $weixin = WeixinUser::findOne(['openid'=>$openid]);
        if($weixin && !empty($weixin->uid) && !empty($weixin->phone)){
            return $weixin;
        }else{
            return false;
        }
    }
    /**
     * 回复关注的消息
     */
    public function getMsg(){
        $msg = "欢迎关注信合宝官方服务号\r\n<a href='http://t.cn/EUCTDZh'>点我提现5000元额度</a>"."\r\n";
        return $msg;
    }

    /**
     * 发送消息
     */
    public function sendMsg($data){
        $access_token = $this->get_access_token();
//        $url = $this->_requestUrl['sendMsg'] . '?access_token=' . $access_token;
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $ret = self::postData($url,$data);
        $retData = json_decode($ret);
        if($retData->errcode!=0){
            return $retData->errcode;
        }else{
            return true;
        }
    }
    //获取新用户的信息
    public function geOpenid($toUrl,$weixin_url){
        $abs_url = Url::to(['wx/user-auth-template']);
        $abs_url_r = \str_replace('newh5/','credit/', $abs_url);
        $tourl_res = $weixin_url.$toUrl;
        $abs_url_res = $weixin_url.$abs_url_r.'?redirectUrl='.$tourl_res;
        $base_url = $weixin_url.$abs_url_r.'?redirectUrl='.$abs_url_res;
        $base_url_res = urlencode($base_url);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appID . "&redirect_uri={$base_url_res}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
    }

    //获取open_id失效的用户的信息
    public function geBaseOpenid($toUrl,$weixin_url){
        $abs_url = Url::to(['wx/user-auth-template']);
        $abs_url_r = \str_replace('newh5/','credit/', $abs_url);
        $tourl_res = $weixin_url.$toUrl;
        $abs_url_res = $weixin_url.$abs_url_r.'?redirectUrl='.$tourl_res;
        $base_url = $weixin_url.$abs_url_r.'?redirectUrl='.$abs_url_res;
        $base_url_res = urlencode($base_url);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appID . "&redirect_uri={$base_url_res}&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
    }


    /**
     * @param $scope 跳转到微信认证
     * @void
     */
    public function redirectOAuth($scope, $toUrl , $weixin_url)
    {
        $appid = Yii::$app->wechat->appid;
        $abs_url = Url::to(['wx/user-auth-template']);
        $abs_url_r = \str_replace('newh5/','wechat/', $abs_url);
        $tourl_res = $weixin_url.$toUrl;
        $abs_url_res = $weixin_url.$abs_url_r.'?redirectUrl='.$tourl_res;
        $base_url = $weixin_url.$abs_url_r.'?redirectUrl='.$abs_url_res;
        $base_url_res = urlencode($base_url);

        if($scope == self::SNSAPI_BASE)
        {
            Yii::$app->response->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri={$base_url_res}&response_type=code&scope=snsapi_base&state=123#wechat_redirect");
        }
        else
        {
            Yii::$app->response->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri={$base_url_res}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
        }
    }

    /*-----------------------------消息模板----------------------------------*/
    /**
     *模板test
     */


    /**
     * 通用的模板发送方法
     * @param $template_id
     * @param $data
     * @param $keyword_count
     *
     * @return bool
     */
    public function sendTpl($template_id, $data, $keyword_count) {
        $temp_res['template_id'] = $template_id;

        $temp_res['touser'] = $data['openid'];
        $temp_res['url'] = $data['url'];

        $temp_res['data']['first']['value'] = $data['first'];

        for($i=1; $i <= $keyword_count; $i++) {
            $temp_res['data']["keyword{$i}"]['value'] = isset($data["keyword{$i}"]) ? $data["keyword{$i}"] : '';
        }

        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res, JSON_UNESCAPED_UNICODE));
    }


    public function TemplateTest($data){
        $template_id = 'QXKK2XujfOgsVvBJdEQiJOi6CzZ15STGWqreUsEjNB0';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['first'] = $data['first'];//开头
        $temp_res['keyword1'] = $data['keyword1'];
        $temp_res['keyword2'] = $data['keyword2'];
        $temp_res['keyword3'] = $data['keyword3'];
        $temp_res['remark'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $temp_res;
    }
    /**
     *模板 用户查询结果 Td7mS0iTs50MQXqTnO-TWo0hqqA_1zNrFONKBg4XIt4
     */
    //待还款金额：{{keyword1.DATA}}
    //还款日期：{{keyword2.DATA}}
    //账户额度：{{keyword3.DATA}}
    //剩余可用额度：{{keyword4.DATA}}
    //可用券：{{keyword5.DATA}}
    public function TemplateOne($data){
        $template_id = 'irhZyool_Csh0KPg1FMhsh-pkENZFP5U2yd0x_aCuGE';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = $data['url'];
        $temp_res['data']['first']['value'] = '您好，截止目前您账户信息如下:';//开头
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['keyword4']['value'] = $data['keyword4'];
        $temp_res['data']['keyword5']['value'] = $data['keyword5'];
        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }
    /**
     * 用户打款成功
     */
    /*  4baNh5eIl3B1PommKXwvSJ8H4rw5q4uDIHJbBuqGRGA
    {{first.DATA}}
    放款金额：{{keyword1.DATA}}
    借款期限：{{keyword2.DATA}}
    还款日：{{keyword3.DATA}}
    {{remark.DATA}}*/
    public function TemplateLoanSTip($data){
        $template_id = '-NmuTav_74UkM82t0hPOTb_vghBxdP2-tyNGle_-idI';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = $data['url'];
        $temp_res['data']['first']['value'] = '恭喜！您的申请已放款到绑定的银行卡。';
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['keyword4']['value'] = $data['keyword4'];
        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 用户还款成功
     */
    /*{{first.DATA}}  3tdDit3jc6-ZFn-yCw7MmE2uZwkO1RUFD5WSC_cTE7E
    还款金额：{{keyword1.DATA}}
    收款人：{{keyword2.DATA}}乐清闪电荷包网络借贷信息中介服务有限公司
    还款方式：{{keyword3.DATA}}
    {{remark.DATA}}*/
    public function TemplatePaySTip($data){
        $template_id = '3khK_xywirHZB74vzXDS9KquOz-CoktnBILvRwd4ngk';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = $data['url'];
        $temp_res['data']['first']['value'] = '亲爱的用户，您已成功还款。';
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 用户还款失败
     */
    /*{{first.DATA}}
    应还金额：{{keyword1.DATA}}
    还款银行卡：{{keyword2.DATA}}
    失败原因：{{keyword3.DATA}}
    {{remark.DATA}}*/ // MC21L8GKRRUzJJcfdDRSkcYBZ9cN1LZrgdO_C_zthjY
    public function TemplatePayETip($data){
        $template_id = 'qs7SJofU7ZJ9qcwM323hAfXerJ27LJGeZQAhYaDSZzc';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = $data['url'];
        $temp_res['data']['first']['value'] = '亲爱的用户，您本次还款失败。请查看绑定银行卡资金是否充足或者选用支付宝还款。';
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 提醒用户还款14天
     */
    /*{{first.DATA}}  -YJs05IdPz1YwINAUwqGyr7U7XjotwQ7fJAHJHwUCoo
    应还金额：{{keyword1.DATA}}
    最后还款日：{{keyword2.DATA}}
    {{remark.DATA}}*/
    public function TemplateLoanTip($data){
        $template_id = '82-fp_FYyQoNG3q-1xPIVo6uOqL98NlXpRPD8x1V7mI';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = 'http://qb.wzdai.com/newh5/web/page/free-coupons?v=170729_1';
        $temp_res['data']['first']['value'] = "尊敬的{$data['name']}：今天是您在".APP_NAMES."本次借款的还款日，请及时还款";//开头
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['remark']['value'] = '你好，今天是您还款的最后期限，逾期将会在网络征信登记，对您今后的信用产生极大影响。今日前500名还款有免单机会！还款成功100%提额！';//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 提醒用户还款13天 NQrKAlbPFec3uGRwiYdqDGfUBxtJG7WhYyCsApmJBh8
     */
    /*{{first.DATA}}
    账单日期：{{keyword1.DATA}}
    到期还款日：{{keyword2.DATA}}
    信用额度：{{keyword3.DATA}}
    账单金额：{{keyword4.DATA}}
    {{remark.DATA}}*/
    public function TemplateLoanTips($data){
        $template_id = 'kovJjd6-xiz3-xjoR-sTq2AQxDrIw9dCeYGuQgpzJuQ';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = 'http://qb.wzdai.com/newh5/web/page/free-coupons?v=170729_1';
        $temp_res['data']['first']['value'] = "尊敬的{$data['name']}：您在".APP_NAMES."本期借款即将临近还款日，您的本次借款账单的总账信息：";//开头
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['keyword4']['value'] = $data['keyword4'];
        $temp_res['data']['remark']['value'] = '主人，明天是您的还款日，提前还款礼包请笑纳：大额抵扣券、提前还款前500名免单、100%提额！';//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->sendMsg(json_encode($temp_res,JSON_UNESCAPED_UNICODE));
    }
    /*-----------------------------消息模板----------------------------------*/
    public function endMsg($uid){
        //查询用户是否有优惠券
        $url='https://mp.weixin.qq.com/s/HjNXfrFNg8oVeiRSy-PrQw';
        $info_count = 0;
        $msg = "温馨提示：按时还款可提高额度还有机会免单点击查看详情";
        if($info_count){
            $msg = "您有一张还款抵扣券，还款当日可用，过期失效，按时还款可提高额度还有机会免单点击查看详情";
        }
        $msg1['msg'] = $msg;
        $msg1['url'] = $url;
        return $msg1;
    }
    //添加token的加锁
    public static function tokenLock(){
        $key = RedisQueue::WEIXIN_TOKEN_LOCK;
        return RedisQueue::set(["expire" => 5, "key" => $key, "value" => 1]);
    }

    //获取token的锁
    public static function getTokenLock(){
        $key = RedisQueue::WEIXIN_TOKEN_LOCK;
        return RedisQueue::get(['key'=>$key]);
    }
}
