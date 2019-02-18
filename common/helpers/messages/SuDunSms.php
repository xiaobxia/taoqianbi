<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;
use common\models\mongo\sms\VoiceNoticeMongo;

class SuDunSms extends BaseSms {
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
        $pwd = $this->_password;
        $account = $this->_userName;
        $smsServiceUse = $this->_smsServiceUse;

        $result = '';
        $extno='';
        $ctx = stream_context_create(self::$ctx_params);
        $result = \file_get_contents("{$url}&account={$account}&password={$pwd}&mobile={$phone}&content={$msg}&extno={$extno}&rt=json", false, $ctx);
        $res = json_decode($result,true);        

        $this->insertMongoLog($phone,'',"{$url}&account={$account}&password={$pwd}&mobile={$phone}&content={$msg}&extno={$extno}&rt=json",$result,$this->_smsServiceUse);        
        
        if($res['status']== 0 && $res['list'][0]['result'] ==0) {
            return true;
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

    // 记录请求
    public function insertMongoLog($phone,$header='',$request,$response,$channel){
    
        $data['phone']  =  $phone;
        $data['request'] = $request;
//         $data['request']['header'] = '';
        $data['response'] = $response;
        $data['channel']  = $channel;
        $data['callback'] = '';
        
        VoiceNoticeMongo::addNoticeLog($data,$phone.time());
    }
    
}
