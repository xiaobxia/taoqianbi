<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\helpers\Url;
use yii\base\Exception;
use yii\web\IdentityInterface;
use common\models\FinancialDebitRecord;
use common\models\Order;
use common\exceptions\PayException;
use common\helpers\TimeHelper;
use common\models\BankConfig;
use common\helpers\Util;
use common\models\UserLog;
use common\api\RedisQueue;

require_once Yii::getAlias('@common') . '/api/yeepay/yeepay.php';

class YeePayService extends Component
{

    const LOG_CATEGORY = "koudai.yeepay.*";

    // 口袋消费，代扣
    const MerchantAccountCp = "xxxx";
    const MerchantPublicKeyCp = "xxxx";
    const MerchantPrivateKeyCp = "xxxx";
    const YeepayPublicKeyCp = "xxxx";

    // 极速钱包消费，代扣
    const MerchantAccountCp_XJK = "xxxx";
    const MerchantPublicKeyCp_XJK = "xxxx";
    const MerchantPrivateKeyCp_XJK = "xxxx";
    const YeepayPublicKeyCp_XJK = "xxxx";

    const MerchantAccount = "xxxx";  //测试商编
    const MerchantPublicKey = "xxxx";
    const MerchantPrivateKey = "xxxx";
    const YeepayPublicKey = "xxxx";


    //充值状态
    const ChargeStatusSuccess = 1;
    const ChargeStatusFailed  = 2;

    //账户类型
    const AccountTypeP2P = 1;   //口袋P2p投资
    const AccountTypeCP  = 2;   //口袋消费
    const AccountTypeCP_XJK  = 3;   //极速钱包消费账号

    public  $yeepay = null;
    private $user = null;
    private $identityid = "";
    private $identitytype = "";
    public $account = '';
    public  $phone = '';

    public static $accounts = [
        self::AccountTypeP2P,
        self::AccountTypeCP,
        self::AccountTypeCP_XJK,
    ];

    public function __construct($user, $flag=self::AccountTypeP2P) {
        self::_init_yeepay($flag);
        if(!empty($user)) {
            $this->user = $user;
            $this->identityid = YII_ENV != 'prod' ? md5($user['id'])/*.'test'*/ : md5($user['id']);
        }
        $this->identitytype = intval("01");        //固定值
    }
    public function switchAccount($account){
        self::_init_yeepay($account);
    }
    /**
     * 绑定银行卡申请
     */
    public function bindBankcardApply($cardno) {
        $requestid          = Order::generateOrderId(0, $this->user['id']);  //生成绑卡请求号，存入redis，确认绑卡时需要
        $identityid         = $this->identityid;
        $identitytype       = $this->identitytype;
        $idcardno           = $this->user['id_card'];
        $username           = $this->user['real_name'];
        $phone              = $this->user['stay_phone']; //手机号
        $registerphone      = "";
        $registerdate       = "";
        $registerip         = "";
        $registeridcardno   = "";
        $registercontact    = "";
        $os                 = Util::getClientType();//操作系统
        $imei               = ""; //设备唯一标识
        $userip             = Util::getUserIP();
        $ua                 = ""; //浏览器版本
        $this->_setRequestId($requestid); //绑卡请求号 存入redis
        try {
            //发送绑卡请求
            $data = $this->yeepay->bindBankcard($identityid, $identitytype, $requestid, $cardno, $idcardno, $username, $phone, $registerphone, $registerdate, $registerip, $registeridcardno, $registercontact, $os, $imei, $userip, $ua);
            if(is_array($data) && array_key_exists("requestid", $data)) { //成功
                //消费类，不发送短信
                //if($this->yeepay->getMerchartAccount() == self::MerchantAccountCp) {
                    //短信验证码存入redis，用户确认付款
                    Yii::$app->redis->set('kdkj-yee-sms-'.$this->user['id'], $data['smscode']);
                    Yii::$app->redis->expire('kdkj-yee-sms-'.$this->user['id'], 5*60);
                //}
                return true;
            }
            else {
                return ['err_code' => '1001', 'err_msg' => '发送绑卡请求失败'];
            }
        }
        catch (\Exception $e) {
            return ['err_code' => $e->getCode(), 'err_msg' => $e->getMessage()];
        }
    }

    /**
     * 确定绑卡
     * @param string $smscode 短信验证码
     * @return true|false 成功|失败
     */
    public function bindBankcardConfirm($smscode='') {

        //if($this->yeepay->getMerchartAccount() == self::MerchantAccountCp) {
            $smscode = Yii::$app->redis->get('kdkj-yee-sms-'.$this->user['id']);
            if(empty($smscode)) {
                return false;
            }
        //}
        $requestid = $this->_getRequestId(); //获取绑卡请求号
        if(!$smscode || !$this->user || !$requestid) {
            return false;
        }
        try {
            //绑卡确认接口
            $data = $this->yeepay->bindBankcardConfirm($requestid, $smscode);
            if(is_array($data) && array_key_exists("requestid", $data)) { //成功
                //绑卡成功，删除绑卡请求号
                $this->_delRequestId();
                Yii::$app->redis->del('kdkj-yee-sms-'.$this->user['id']);
                return $data;
            }
            else {
                return false;
            }
        }
        catch (\Exception $e) {
            $data = [
                'err_code' => $e->getCode(),
                'err_msg' => $e->getMessage(),
                'smscode' => $smscode,
            ];
        }
        return false;
    }

    /**
     * 查看银行卡是否绑定
     * @param unknown $card_no
     * @return unknown|boolean
     */
    public function queryBindCard($card_no) {
        $identityid         = $this->identityid;
        $identitytype       = $this->identitytype;
        try {
            $data = $this->yeepay->bankcardList($identityid, $identitytype);
            $card_top = substr($card_no, 0, 6);
            $card_last =  substr($card_no, -4);
            $cardlist = $data['cardlist'];
            foreach ($cardlist as $k => $v) {
                if($v['card_top'] == $card_top && $v['card_last'] == $card_last) {
                    return $v;
                }
            }
        }
        catch (\Exception $e) {
        }
        return false;
    }

    /**
     * 检查银行卡号
     * @param string $card_no
     */
    public function bankcardCheck($cardno) {

        if (!$this->user) {
            return false;
        }

        $requestid          = Order::generateOrderId(1);
        $identityid         = $this->identityid;
        $identitytype       = $this->identitytype;
        $idcardno           = $this->user['id_card'];
        $username           = $this->user['real_name'];
        $phone              = $this->_getPhone();   //手机号
        $registerphone      = "";
        $registerdate       = "";
        $registerip         = "";
        $registeridcardno   = "";
        $registercontact    = "";
        $os                 = 'pc';
        $imei               = ""; //设备唯一标识
        $userip             = '127.0.0.1';
        $ua                 = ""; //浏览器版本

        try {
            //发送绑卡请求
            $data = $this->yeepay->bindBankcard($identityid, $identitytype, $requestid, $cardno, $idcardno, $username, $phone, $registerphone, $registerdate, $registerip, $registeridcardno, $registercontact, $os, $imei, $userip, $ua);
            if(is_array($data) && array_key_exists("requestid", $data)) { //成功
                return true;
            }
            else {
                return false;
            }
        }
        catch (\Exception $e) {
            $data = [
                    'err_code' => $e->getCode(),
                    'err_msg' => $e->getMessage(),
                    'cardno' => $cardno,
                    'idcardno' => $this->user['id_card'],
                    'username' => $this->user['real_name'],
                    'phone' => $this->user['stay_phone']
            ];
            return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        }
    }

    /**
     * 直接扣款
     */
    public function directPay($pro_name, $amount, $card_no, $orderid) {
        $transtime = time();
        $productname = $pro_name;
        $productdesc = "口袋理财".$pro_name."扣款";
        $identityid =  $this->identityid;
        $identitytype = $this->identitytype;
        $card_top = substr($card_no, 0, 6);
        $card_last =  substr($card_no, -4);
        $orderexpdate = 60;
        $callbackurl = '';
        if (YII_ENV_PROD) {
            $url = 'https://api.kdqugou.com/';
        } else{
            $url = 'http://42.96.204.114/koudai/kdkj/frontend/web/';
        }
        if($this->yeepay->getMerchartAccount() == self::MerchantAccountCp) {
            $callbackurl = $url.'notify/yeepay-cp-notify';
            $productdesc = "口袋理财".$pro_name."扣款";
        }elseif($this->account == self::AccountTypeCP_XJK) {
            $callbackurl = $url.'notify/yeepay-cp-notify-xjk';
            $productdesc = "凌融科技".$pro_name."扣款";
        }
        $userip = Util::getUserIP();
        $imei = "";
        $ua = "";
        $data = $this->yeepay->directPay($orderid, $transtime, $amount, $productname, $productdesc, $identityid, $identitytype, $card_top, $card_last, $orderexpdate, $callbackurl, $imei, $userip, $ua);
    }
    /**
     * 直接扣款
     */
    public function directPayNew($pro_name, $amount, $card_no, $orderid) {
        $transtime = time();
        $productname = $pro_name;
        $productdesc = "口袋理财".$pro_name."扣款";
        $identityid =  $this->identityid;
        $identitytype = $this->identitytype;
        $card_top = substr($card_no, 0, 6);
        $card_last =  substr($card_no, -4);
        $orderexpdate = 60;
        $callbackurl = '';
        if (YII_ENV_PROD) {
            $url = 'https://api.kdqugou.com/';
        } else{
            $url = 'http://42.96.204.114/koudai/kdkj/frontend/web/';
        }
        if($this->yeepay->getMerchartAccount() == self::MerchantAccountCp) {
            $callbackurl = $url.'notify/yeepay-cp-notify-new';
            $productdesc = "口袋理财".$pro_name."扣款";
        }elseif($this->account == self::AccountTypeCP_XJK) {
            $callbackurl = $url.'notify/yeepay-cp-notify-xjk-new';
            $productdesc = "凌融科技".$pro_name."扣款";
        }
        $userip = Util::getUserIP();
        $imei = "";
        $ua = "";
        return $this->yeepay->directPay($orderid, $transtime, $amount, $productname, $productdesc, $identityid, $identitytype, $card_top, $card_last, $orderexpdate, $callbackurl, $imei, $userip, $ua);
    }
    public function confirmPayment($smscode) {
        $orderid = $this->_getOrderId();
        try {
            $data = $this->yeepay->confirmPayment($orderid, $smscode);
            $data['order_id'] = $orderid;
            if(is_array($data) && array_key_exists("merchantaccount", $data)) { //成功

                $this->_delOrderId();
                $data['ret_code'] = 1;
                return $data;
            }
            else {
                return false;
            }
        }
        catch (\Exception $e) {
            //throw $e;
            $data = [
                    'ret_code' => 0,
                    'code' => $e->getCode(),
                    'msg' => $e->getMessage(),
                    'smscode' => $smscode
            ];
            return $data;
        }

        return false;
    }

    /**
     * 用RSA 签名请求
     * @param array $query
     * @return string
     */
    public function RSASign(array $query) {
        return $this->yeepay->RSASign($query);
    }

    private function _init_yeepay($flag) {
        if(!in_array($flag, self::$accounts)){
            throw new \Exception('非法操作');
        }
        $this->account = $flag;
        if($flag == self::AccountTypeCP_XJK) {
            $this->yeepay = new \yeepay(array(
                    'merchantAccount' => YeePayService::MerchantAccountCp,
                    'merchantPublicKey' => YeePayService::MerchantPublicKeyCp,
                    'merchantPrivateKey' => YeePayService::MerchantPrivateKeyCp,
                    'yeepayPublicKey' => YeePayService::YeepayPublicKeyCp
            ));
        }elseif($flag == self::AccountTypeP2P) {
            $this->yeepay = new \yeepay(array(
                    'merchantAccount' => YeePayService::MerchantAccount,
                    'merchantPublicKey' => YeePayService::MerchantPublicKey,
                    'merchantPrivateKey' => YeePayService::MerchantPrivateKey,
                    'yeepayPublicKey' => YeePayService::YeepayPublicKey
            ));
        }else {
            $this->yeepay = new \yeepay(array(
                    'merchantAccount' => YeePayService::MerchantAccountCp,
                    'merchantPublicKey' => YeePayService::MerchantPublicKeyCp,
                    'merchantPrivateKey' => YeePayService::MerchantPrivateKeyCp,
                    'yeepayPublicKey' => YeePayService::YeepayPublicKeyCp
            ));
        }
    }

    private function _getPhone() {
        return empty($this->phone) ? $this->user['stay_phone'] : $this->phone;
    }

    public static $YeePayBankInfo = [
            'ICBC'  => "工商银行",
            'BOC'   => "中国银行",
            'CCB'   => "建设银行",
            'POST'  => "邮政储蓄银行",
            'ECITIC' => "中信银行",
            'CEB'   => "光大银行",
            'HXB'   => "华夏银行",
            'CMBCHINA' => "招商银行",
            'CIB'   => "兴业银行",
            'SPDB'  => "浦发银行",
            'PINGAN' => "平安银行",
            'GDB'   => "广发银行",
            'CMBC'  => "民生银行",
            'ABC'   => "农业银行",
            'BOCO'  => "交通银行",
            'BCCB'  => "北京银行"
    ];

    public static $BankCodeToId = [
        'ICBC'  => "1",
        'BOC'   => "9",
        'CCB'   => "7",
        'POST'  => "4",
        'ECITIC' => "13",
        'CEB'   => "3",
        'HXB'   => "12",
        'CMBCHINA' => "8",
        'CIB'   => "5",
        'SPDB'  => "10",
        'PINGAN' => "11",
        'GDB'   => "16",
        'CMBC'  => "15",
        'ABC'   => "2",
        'BOCO'  => "14",
        'BCCB'  => "17"
    ];

    /**
     * 请求号redis缓存
     */
    private function _setRequestId($requestid) {
        Yii::$app->redis->set($this->_bindCardRequestIdKey(), $requestid); //绑卡请求号 存入redis
    }
    private function _getRequestId() {
        return Yii::$app->redis->get($this->_bindCardRequestIdKey());
    }
    private function _delRequestId() {
        return Yii::$app->redis->del($this->_bindCardRequestIdKey());
    }
    private function _bindCardRequestIdKey() {
        return "kdkj-bing-card-" . $this->user['id'];
    }

    /**
     * 订单号redis缓存
     */
    private function _setOrderId($orderid) {
        Yii::$app->redis->set($this->_orderIdKey(), $orderid);
    }
    private function _getOrderId() {
        return Yii::$app->redis->get($this->_orderIdKey());
    }
    private function _delOrderId() {
        return Yii::$app->redis->del($this->_orderIdKey());
    }
    private function _orderIdKey() {
        return "order-id-" . $this->user['id'];
    }

    public function paymentQuery($orderid, $yborderid){
        return $this->yeepay->paymentQuery($orderid, $yborderid);
    }
    /**
     * 获取易宝数据
    **/
    public function yeedata($date){
        /*
            $startdate = date('Y-m-d',intval($date));
            //$enddate = $startdate;
            //$startdate = '2016-10-08';
            $merchantaccount = "10012463779";
            $end_time = intval($date);
            $start_time = strtotime('2016-10-07');
            $j = 0;
            $http_url_arr = [];
            while ($end_time - $j * 86400 > $start_time) {
                $s_time = date('Y-m-d',$end_time-$j*86400);
                $e_time = $s_time;
                $urlsign = $this->yeepay->buildRequest(['startdate'=>$s_time,'enddate'=>$e_time,'merchantaccount'=>$merchantaccount]);
                $http_url_arr[$s_time] = 'https://ok.yeepay.com/merchant/query_server/pay_clear_data?'.http_build_query($urlsign);
                $j++;
            }
            return $http_url_arr;
        */
        $startdate = date('Y-m-d',intval($date));
        $enddate = $startdate;
        $merchantaccount = "10012463779";
        $urlsign = $this->yeepay->buildRequest(['startdate'=>$startdate,'enddate'=>$enddate,'merchantaccount'=>$merchantaccount]);
        return 'https://ok.yeepay.com/merchant/query_server/pay_clear_data?'.http_build_query($urlsign);

    }
}
