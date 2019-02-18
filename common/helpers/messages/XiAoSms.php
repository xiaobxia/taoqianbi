<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;

class XiAoSms extends BaseSms {

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
        $msg = urlencode($message);
        $url = $this->_baseUrl;
        $uid = $this->_userName;
        $pwd = $this->_password;
        $smsServiceUse = $this->_smsServiceUse;
        $code = $this->_extArr['code'];
        $auth = md5($code . $pwd);
        $result = '';
        try {
            $ctx = stream_context_create(self::$ctx_params);
            $result = \file_get_contents("{$url}?uid={$uid}&auth={$auth}&mobile={$phone}&msg={$msg}&expid=0&encode=utf-8", false, $ctx);
        }
        catch (\Exception $e) {
            \yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
        }
        // 返回值要是0这种格式才成功，后面是短信id
        if ($result && strpos($result, ',') !== false) {
            list($resCode, $resMsg) = \explode(',', $result);
            if ($resCode == '0') {
                return $resMsg;
            }
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
