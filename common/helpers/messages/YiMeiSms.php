<?php
namespace common\helpers\messages;

date_default_timezone_set('PRC');
//define("END",               "\n");

class YiMeiSms extends BaseSms{
const YM_SMS_SEND_URI="/simpleinter/sendSMS";/*发送短信接口*/

    public function __construct($baseUrl, $userName, $password, $extArr = '', $smsServiceUse = '')
    {
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_password   = $password;
        $this->_extArr	   = $extArr;
        $this->_smsServiceUse   = $smsServiceUse;

        $this->_timeStamp = date("Y-m-d H:m:s");
    }

    public function getSmsId()
    {
        return $this->_smsId;
    }

    public function sendSMS($phone, $message, $name = '')
    {
        /* 短信内容请以商务约定的为准，如果已经在通道端绑定了签名，则无需在这里添加签名 */

        $sign = $this->signmd5($this->_userName,$this->_password,$this->_timeStamp);
        // 如果您的系统环境不是UTF-8，需要转码到UTF-8。如下：从gb2312转到了UTF-8
        // $content = mb_convert_encoding( $content,"UTF-8","gb2312");
        // 另外，如果包含特殊字符，需要对内容进行urlencode
        $data = array(
            "appId" => $this->_userName,
            "timestamp" => $this->_timeStamp,
            "sign" => $sign,
            "mobiles" => $phone,
            "content" =>  $message,
            'customSmsId' => 1,
        );
        $url = $this->_baseUrl.self::YM_SMS_SEND_URI;
//        return 'YiMei';
        $ret = $this->http_request($url, $data);
        $result = json_decode($ret,true);

        if (isset($result['code']) && $result['code'] == 'SUCCESS') {
            return true;
        }
        return false;

    }

    private function signmd5($appId,$secretKey,$timestamp){
        return md5($appId.$secretKey.$timestamp);
    }

    private function http_request($url, $data)
    {
        $data = http_build_query($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    #请求数据和接收数据大集合
    public function getRequestReturnCollect()
    {
        return array('url'=>$this->_baseUrl,'raw'=>$this->_raw,'return'=>$this->_return);
    }

    #取得余额
    public function balance()
    {
        return $this->_return;
    }


    public function acceptReport()
    {

    }

    public function collectReport()
    {

    }
}