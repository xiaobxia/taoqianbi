<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;

class CongYuSms extends BaseSms
{

    /**
    * @desc
    * @param string $baseUrl  请求地址   对应文档 APIURL
    * @param string $userName 用户名     对应文档nonce_str
    * @param string $password 密码       对应文档app_secret
    * @param string $extArr   扩展参数
    * @return
    */
    public function __construct($baseUrl, $userName, $password, $extArr = '', $smsServiceUse = '')
    {
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_password   = $password;
        $this->_extArr	   = $extArr;
        $this->_smsServiceUse   = $smsServiceUse;

        $this->_timeStamp = date("YmdHis");
    }

    public function getSmsId()
    {
        return $this->_smsId;
    }

    /**
    * @desc
    * @param array $phone
    * @param string $message
    * @return
    */
    public function sendSMS($phone, $message, $name = APP_NAMES)
    {
        $url = $this->_baseUrl;
        $acc = $this->_userName;
        $pwd = $this->_password;
        $timestamp = $this->_timeStamp;
        $userId = $this->_extArr['userid'];
        $smsServiceUse = $this->_smsServiceUse;

        $sign = self::_createPublicKey($acc, $pwd, $timestamp);
        $prefix_message = '【'.$name.'】 ';
        $suffix_message = ($smsServiceUse == 'smsService_CongYu_YX') ? ', 退订回N' : '';

        $post_data = [
            'userid' => $userId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'mobile' => $phone,
            'content' => $prefix_message . $message . $suffix_message,
            'action' => 'send',
            'extno' => '',     // 扩展子号
            'sendTime' => '', // 定时发送 格式：2017-04-28 09:08:10
        ];

        $xml_result = '';
        try {
            $xml_result = self::_post($url, $post_data);
        } catch (\Exception $e) {
            \yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
        	return false;
        }

        $result = simplexml_load_string($xml_result);
        if ($result !== false) {
            $result_arr = (array)$result;
            if (isset($result_arr['returnstatus']) && $result_arr['returnstatus'] == 'Success') {
                return true;
            }
        }

        return false;
    }

    private static function _post($url, $data, $is_json = false) {
        if ($is_json) {
            $header = 'Content-Type: application/json; charset=utf-8';
            $data = json_encode($data);
        } else {
            $header = 'Content-type: application/x-www-form-urlencoded';
            $data = http_build_query($data);
        }

        $options = ['http' =>
            [
                'method'  => 'POST',
                'header'  => $header,
                'content' => $data,
                'timeout' => self::$ctx_params['http']['timeout'],
            ]
        ];
        $context = stream_context_create($options);

        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * 生成聪裕短信sign
     * @param string $user
     * @param string $password
     * @param string $timestamp YmdHis
     * @return string
     */
    private static function _createPublicKey($user, $password, $timestamp) {
        return md5($user . $password . $timestamp);
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
