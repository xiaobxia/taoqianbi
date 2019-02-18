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

class XuanWuSms extends BaseSms
{
    var $signType='normal';  //normal  md5  sha1

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
        if($this->_smsServiceUse !== 'smsService_XuanWu_YYTZ'){
            return false;
        }   

        if(!$phone) return false;
        
        $date = date('YmdHis');
        $times = strtotime($date);
   
        $url = $this->_extArr['url'].'?sig='.sha1($this->_extArr['account'].$this->_extArr['password'].$date);

        $autr =  base64_encode($this->_extArr['account'].':'.$date);
     
        $header = $this->getHttpHeader($autr);

        foreach($phone['phone_list'] as $k=>$val){
            $params = [
                'info'=>['appID'=>$this->_extArr['appid']],
                'subject'=>[
                    'called'=>$val,
                    'calledDisplay'=>'',
                    'templateID'=>$phone['project'],
                    'params'=>'',
                    'playtimes'=>$this->_extArr['playtimes'],
                ],
                'data'=>$val.$times,    
                'timestamp'=> $times.'001',
            ];  
            $res =  $this->curlPostHttps($url,$header, $params);
            
            $this->insertMongoLog($val,$header,$params,$res,$this->_smsServiceUse);
            echo  $res;
        }
    }   

    public  function getHttpHeader($str,$type='json'){

           return  $type=='json' ?  array(
                   'Authorization:'.$str,
                   'Accept: application/json;charset=utf-8',
                   'Content-Type: application/json;charset=utf-8',
                   'Content_Length:'.strlen($str)
               ) : '';       
    }
    
    
    public function curlPostHttps($url,$header,$params){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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