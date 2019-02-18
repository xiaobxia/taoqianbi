<?php
/**
 * Created by PhpStorm.
* User: User
* Date: 2017/8/14
* Time: 10:22
*/

namespace common\helpers\messages;

use Yii;
use common\models\mongo\sms\VoiceNoticeMongo;

class DaHanSms extends BaseSms
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
    }

    /**
     * @desc
     * @param array $phone
     * @param string $message
     * @return
     */
    public function sendSMS($phone, $message, $name = APP_NAMES)
    {
        
        if($this->_smsServiceUse != 'smsService_DaHan_TZ'  && $this->_smsServiceUse != 'smsService_DaHan_YZM' ){
            return false;
        }

        $params = [
            'account'=>$this->_userName,
            'password'=>md5($this->_password),
            'phones'=>$phone,
            'content'=>$message,
            'sign'=>'【'.$name.'】',
            'sendtime'=>'',
        ];
      
        $res = $this->curlPostHttps($this->_baseUrl,$params);
        
        $this->insertMongoLog($phone,'',$params,$res,$this->_smsServiceUse);
        
        return $res;
    }

   
    public function curlPostHttps($url,$params){
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        return  trim($response, "\xEF\xBB\xBF");
    }

    // 记录请求
    public function insertMongoLog($phone,$header,$request,$response,$channel){
 
        $data['phone']  =  $phone;
        $data['request'] = $request;
        $data['request']['header'] = $header;
        $data['response'] = $response;
        $data['channel']  = $channel;
        $data['callback'] = '';
        VoiceNoticeMongo::addNoticeLog($data,$phone.time());  
    }

}