<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;

class ChuangLanSms extends BaseSms
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
        $uid = $this->_userName;
        $pwd = $this->_password;
        $prefix_message = '【' . $name . '】 ';
        $suffix_message = $this->_smsServiceUse == 'smsService_ChuangLan' ? '' : ', 退订回TD';
        $post_data = [
            'account' => $uid,
            'password' => $pwd,
            'phone' => $phone,
            'msg' => urlencode($prefix_message . $message . $suffix_message),
            'report' => 'false',
        ];
        try {
            $result = self::_post($url, $post_data, true);
        }
        catch (\Exception $e) {
            \yii::error(\sprintf('%s:%s exception %s', $this->_smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
            return false;
        }
        if ($output = json_decode($result, true)) {
            if(isset($output['code']) && $output['code'] == '0') {
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
