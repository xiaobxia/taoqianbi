<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\models\CreditZmop;
use common\models\ErrorMessage;

require_once Yii::getAlias('@common/api/zmop/ZmopSdk.php');

class ZmopService extends Component
{
    //芝麻信用网关地址
    protected $gatewayUrl = "https://zmopenapi.zmxy.com.cn/openapi.do";
    //商户私钥文件
    protected $privateKeyFile = '';
    //芝麻公钥文件
    protected $zmPublicKeyFile = '';
    //数据编码格式
    protected $charset = "UTF-8";

    //芝麻分配给商户的 appId
    public $appId = '';
    public $product_code = '';
    public $transaction_id = '';

    //数据反馈文件的路径
    public $data_feedback_file_path = '';
    //芝麻积分产品码
    protected $score_product_code = 'w1010100100000000001';
    //rain积分产品码
    protected $rain_product_code = 'w1010100000000000105';
    //行业关注名单产品码
    protected $watch_product_code = 'w1010100100000000022';
    //ivs产品码
    protected $ivs_product_code = 'w1010100000000000103';
    //das产品码
    protected $das_product_code = 'w1010100200000000001';
    //das合同外标
    protected $das_contract_key = 'si201604100003673003';

    protected $api_public_key_file = '';
    protected $api_private_key_file = '';

    public function init() {
        //默认 极速荷包 正式appid
        $this->appId = CreditZmop::APPID;
        $this->privateKeyFile =  Yii::getAlias('@common/config/cert/zmop/jshb_prod_private_key.pem');
        $this->zmPublicKeyFile = Yii::getAlias('@common/config/cert/zmop/jshb_prod_public_key_zm.pem');
        $this->data_feedback_file_path = Yii::getAlias('@common/api/zmop/zmop_data_feedback.json');
    }

    public function setAppId($app_id) {
        if (isset(CreditZmop::$appids[$app_id])) {
            $this->appId = $app_id;

            $cfg = CreditZmop::$appids[$app_id];
            $this->privateKeyFile = Yii::getAlias($cfg['privateKeyFile']);
            $this->zmPublicKeyFile = Yii::getAlias($cfg['zmPublicKeyFile']);
        }
    }

    /**
     * 芝麻信用短信授权
     * @param string $name 用户姓名
     * @param string $id_number 身份证
     * @param integer $phone 手机号
     * @param integer $loan_person_id 用户id
     * @return mixed
     * @throws \Exception
     */
    public function batchFeedback($name,$id_number,$phone,$loan_person_id=0){
        $client  = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile, $this->zmPublicKeyFile);
        $request = new \ZhimaAuthEngineSmsauthRequest();
        $request->setIdentityType("2");// 必要参数
        $identityParam = [
            'name' => $name,
            'certType' => 'IDENTITY_CARD',
            'certNo' => strval($id_number),
            'mobileNo' => strval($phone),
        ];
        $identityParam = json_encode($identityParam);
        $request->setIdentityParam($identityParam);// 必要参数
        $bizparams = [
            'auth_code' => 'M_SMS',
            'state' => "{$loan_person_id},kdkj",
        ];
        $bizparams = json_encode($bizparams);
        $request->setBizParams($bizparams);// 必要参数
        $request->setChannel("windows");
        $response = $client->execute($request);
        $arr = json_decode(json_encode($response, true), true);
        return $arr;
    }

    /**
     * 获取芝麻积分
     * @param string $open_id 用户芝麻信用的授权码
     * @return array
     * @throws \Exception
     */
    public function getScore($open_id){
        $person = CreditZmop::gainCreditZmopLatest(['open_id'=>$open_id],'db_kdkj_rd');
        $this->product_code = $this->score_product_code;
        $transaction_id = $this->_getMillisecond();
        $this->transaction_id = $transaction_id;
        $client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile, $this->zmPublicKeyFile);
        $request = new \ZhimaCreditScoreGetRequest();
        $request->setTransactionId($transaction_id);// 必要参数
        $request->setProductCode($this->score_product_code);// 必要参数
        $request->setOpenId($open_id);// 必要参数
        $response = $client->execute($request);
        $arr = get_object_vars($response);
        if (isset($arr['error_message'])) {
            ErrorMessage::getMessage($person->person_id, $arr['error_message'], ErrorMessage::SOURCE_ZM);
        }
        return $arr;
    }


    /**
     * 获取手机RAIN分
     * @param integer $phone
     * @return array
     * @throws \Exception
     */
    public function getRain($phone){
        $this->product_code = $this->rain_product_code;
        $transaction_id = $this->_getMillisecond();
        $this->transaction_id = $transaction_id;
        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaCreditMobileRainGetRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $request->setTransactionId($transaction_id);// 必要参数
        $request->setProductCode($this->rain_product_code);// 必要参数
        $request->setMobile($phone);// 必要参数
        $response = $client->execute($request);
        $arr = get_object_vars($response);
        return $arr;
    }


    /**
     * 获取行业关注
     * @param integer $open_id 用户芝麻信用的授权码
     * @return array
     * @throws \Exception
     */
    public function getWatch($open_id){
        $person = CreditZmop::gainCreditZmopLatest(['open_id'=>$open_id],'db_kdkj_rd');
        $this->product_code = $this->watch_product_code;
        $transaction_id = $this->_getMillisecond();
        $this->transaction_id = $transaction_id;
        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaCreditWatchlistGetRequest();
        $request->setProductCode($this->watch_product_code);// 必要参数
        $request->setTransactionId($transaction_id);// 必要参数
        $request->setOpenId($open_id);// 必要参数
        $response = $client->execute($request);
        $arr = get_object_vars($response);
        if (isset($arr['error_message'])) {
            ErrorMessage::getMessage($person->person_id, $arr['error_message'], ErrorMessage::SOURCE_ZM);
        }
        return $arr;
    }


    /**
     * 获取IVS
     * @param integer $phone 手机号
     * @param string $id_number 身份证
     * @return array
     * @throws \Exception
     */
    public function getIvs($phone,$id_number){
        $person = CreditZmop::gainCreditZmopLatest(['id_number'=>$id_number],'db_kdkj_rd');
        $this->product_code = $this->ivs_product_code;
        $transaction_id = $this->_getMillisecond();
        $this->transaction_id = $transaction_id;
        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaCreditIvsDetailGetRequest();
        $request->setProductCode($this->ivs_product_code);// 产品码
        $request->setTransactionId($transaction_id);// 商户传入的业务流水号
        $request->setCertNo($id_number);//
        $request->setCertType("100");//
        $request->setMobile($phone);//
        $response = $client->execute($request);
        $arr = get_object_vars($response);
        if (isset($arr['error_message'])) {
            ErrorMessage::getMessage($person->person_id, $arr['error_message'], ErrorMessage::SOURCE_ZM);
        }
        return $arr;
    }


    /**
     * 获取DAS
     * @param integer $open_id 用户芝麻信用的授权码
     * @param integer $phone 手机号
     * @return array
     * @throws \Exception
     */
    public function getDas($open_id,$phone){
        $person = CreditZmop::gainCreditZmopLatest(['open_id'=>$open_id],'db_kdkj_rd');
        $this->product_code = $this->das_product_code;
        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaCreditDasGetRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $transaction_id = $this->_getMillisecond();
        $this->transaction_id = $transaction_id;
        $request->setTransactionId($transaction_id);// 必要参数
        $request->setProductCode($this->das_product_code);// 必要参数
        $request->setOpenId($open_id);// 必要参数
        $request->setContractFlag($this->das_contract_key);// 必要参数
        $extparas = [
            'mobile' => $phone
        ];
        $extparas = json_encode($extparas);
        $request->setExtParas($extparas);
        $response = $client->execute($request);
        $arr = get_object_vars($response);
        if (isset($arr['error_message'])) {
            ErrorMessage::getMessage($person->person_id, $arr['error_message'], ErrorMessage::SOURCE_ZM);
        }
        return $arr;
    }


    /**
     * 生成流水号
     * @return string
     */
    private function _getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        $now = time();
        $time =  sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $arr = explode('.', $time / 1000);
        if(!isset($arr[1])){
            return $this->_getMillisecond();
        }
        return date("YmdHis", $now).sprintf("%03d", $arr[1]).mt_rand(10000000,99999999).mt_rand(10000,99999);
    }


    /**
     * 生成芝麻信用H5授权页面URL
     * @param string $cert_no 身份证
     * @param string $name 姓名
     * @param integer $loan_person_id 用户id
     * @param string $source 用户来源，用于回调判断，如kdkj
     * @return string
     */
    public function zmAuthorize($cert_no,$name,$loan_person_id,$source='1', $callback_url = '', $title= '' ,$content= '' , $describe= ''){
        $client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile, $this->zmPublicKeyFile);
        $request = new \ZhimaAuthInfoAuthorizeRequest();
        $request->setIdentityType("2");// 必要参数
        $identity_param  = [
            'certNo' => strval($cert_no),
            'certType' => 'IDENTITY_CARD',
            'name' => strval($name)
        ];
        $identity_param = json_encode($identity_param);
        $request->setIdentityParam($identity_param);
        $request->setChannel("app");
        $biz_params = [
            'auth_code' => 'M_H5',
            'state' => strval($loan_person_id).','.strval($source) . ',' . $callback_url . ',' . $title . ',' . $content .',' .$describe,
        ];
        $biz_params = json_encode($biz_params);
        $request->setBizParams($biz_params);
        $response = $client->generatePageRedirectInvokeUrl($request);
        return $response;
    }

    public function getDataBatchFeedback($records,$desc=false){
        if(0 == $records){
            return false;
        }
        $client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile, $this->zmPublicKeyFile);
        $request = new \ZhimaDataBatchFeedbackRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $request->setFileType("json_data");// 必要参数
        $request->setFileCharset("UTF-8");// 必要参数
        $records = strval($records);
        $request->setRecords($records);// 必要参数

        $request->setColumns("user_name,user_credentials_no,user_credentials_type,order_no,biz_type,order_status,create_amt,pay_month,
        gmt_ovd_date,overdue_days,overdue_amt,gmt_pay,memo");// 必要参数
        $request->setPrimaryKeyColumns("order_no,pay_month");// 必要参数
        if($desc){
            $request->setFileDescription($desc);//描述信息，非必要参数
        }
        $request->setTypeId("1002202-default-order");// 必要参数
        $request->setFile($this->data_feedback_file_path);// 必要参数
        $response = $client->execute($request);
        return $response;
    }

    /**
     * 生成芝麻信用Sign信息
     * @param string $id_number 身份证
     * @param string $name 姓名
     * @param integer $person_id 用户id
     * @return string
     */
    public function generateSign($id_number,$name,$person_id){
        $client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile,
            $this->zmPublicKeyFile);
        $request = new \ZhimaAuthInfoAuthorizeRequest();
        // 授权来源渠道设置为appsdk
        $request->setChannel("appsdk");
        // 授权类型设置为2标识为证件号授权见“章节4中的业务入参说明identity_type”
        $request->setIdentityType("2");
        // 构造授权业务入参证件号，姓名，证件类型;“章节4中的业务入参说明identity_param”
        $identityParam = [
            'certNo' => strval($id_number),
            'certType' => 'IDENTITY_CARD',
            'name' => $name
        ];
        $identityParam = json_encode($identityParam);
        $request->setIdentityParam($identityParam);
        // 构造业务入参扩展参数“章节4中的业务入参说明biz_params”
        $bizParams = [
            'auth_code' => 'M_APPSDK',
            'state' => "{$person_id},kdkj"
        ];
        $bizParams = json_encode($bizParams);
        $request->setBizParams($bizParams);
        return $client->generateSignWithUrlEncode($request);
    }


    /**
     * 生成芝麻信用params信息
     * @param string $id_number 身份证
     * @param string $name 姓名
     * @param integer $person_id 用户id
     * @return string
     */
    public function generateParams($id_number,$name,$person_id){
        $client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile,
            $this->zmPublicKeyFile);
        $request = new \ZhimaAuthInfoAuthorizeRequest();
        // 授权来源渠道设置为appsdk
        $request->setChannel("appsdk");
        // 授权类型设置为2标识为证件号授权见“章节4中的业务入参说明identity_type”
        $request->setIdentityType("2");
        // 构造授权业务入参证件号，姓名，证件类型;“章节4中的业务入参说明identity_param”
        $identityParam = [
            'certNo' => strval($id_number),
            'certType' => 'IDENTITY_CARD',
            'name' => $name
        ];
        $identityParam = json_encode($identityParam);
        $request->setIdentityParam($identityParam);
        // 构造业务入参扩展参数“章节4中的业务入参说明biz_params”
        $bizParams = [
            'auth_code' => 'M_APPSDK',
            'state' => "{$person_id},kdkj"
        ];
        $bizParams = json_encode($bizParams);
        $request->setBizParams($bizParams);
        return $client->generateEncryptedParamWithUrlEncode($request);
    }

    /**
     * 解密芝麻信用回调数据
     * @param string $params
     * @param string $sign
     * @return array|bool
     */
    public function decodingResult($params,$sign){
        $result = \RSAUtil::rsaDecrypt($params, $this->privateKeyFile);
        $sign = str_replace(' ', '+', $sign);
        $bool_result = \RSAUtil::verify($result, $sign, $this->zmPublicKeyFile);//验签
        if(is_bool($bool_result) && $bool_result === true) {
            if ($result) {
                $result = urldecode($result);
                $result = explode('&', $result);
                $arr = [];
                foreach ($result as $v) {
                    $item = explode('=', $v);
                    $arr[$item[0]] = $item[1];
                }
                return $arr;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 获取H5页面芝麻授信授权URL
     * @param string $mobile 手机号
     * @param string $state 用于回调地址识别的state值
     * @return string
     */
    public function h5AuthorizeUrl($mobile, $state) {
        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaAuthInfoAuthorizeRequest();
        $request->setChannel("app");
        $request->setPlatform("zmop");
        $request->setIdentityType("1");// 必要参数
        $request->setIdentityParam('{"mobileNo":"'.$mobile.'"}');// 必要参数
        $request->setBizParams("{\"auth_code\":\"M_H5\",\"channelType\":\"app\",\"state\":\"$state\"}");//
        $url = $client->generatePageRedirectInvokeUrl($request);
        return $url;
    }

}
