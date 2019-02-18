<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;

class WoTaoSms extends BaseSms {

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
        // $msg = urlencode($message);

        $msg = $message;
        $url = $this->_baseUrl;
        $uid = $this->_userName;
        $pwd = md5($this->_password);
        $smstype = $this->_extArr['smstype'];
        $smsServiceUse = $this->_smsServiceUse;
        $result = '';
        try {
            $msg = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . "<MtMessage><content>" . $msg . "</content><phoneNumber>" . $phone . "</phoneNumber><subCode></subCode></MtMessage>";
            $data = [
                'message' => $msg,
                'account' => $uid,
                'password' => $pwd,
                'smsType' => $smstype,
            ];

            $query = http_build_query($data);

            $post = [
                'http' => [
                    'timeout' => self::$timeout,
                    'method' => 'POST',
                    'header' => 'Content-type:application/x-www-form-urlencoded',
                    'content' => $query
                ]
            ];

            $ctx = stream_context_create($post);
            $result = \file_get_contents("{$url}", false, $ctx);
        }
        catch (\Exception $e) {
            \yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
        }

        $result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
        $result = (array)$result;
        if (isset($result['subStat']) == 'r:000') {
            return $result['smsId'];
        }
        return false;
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
