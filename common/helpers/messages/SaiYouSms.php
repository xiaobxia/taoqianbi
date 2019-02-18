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

class SaiYouSms extends BaseSms
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
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_extArr	   = $extArr;
        $this->_smsServiceUse   = $smsServiceUse;
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
        $smsServiceUse = $this->_smsServiceUse;
        $prefix_message = '【'.$name.'】 ';
        $type = '';
        if($smsServiceUse == 'smsService_SaiYou_YY'){
            $type = 'voice';
            $prefix_message = '';
        }

        $suffix_message = ($smsServiceUse == 'smsService_SaiYou') ? '退订回N' : '';

        if(is_array($phone)){  //需要project
            $multi = array();
            $request = array();
            if(empty($phone['project']) || !is_array($phone['phone_list'])){
                return false;
            }
            foreach($phone['phone_list'] as $item){
                array_push($multi, array("to"=>$item,"vars"=>array()));
            }
            $request['project']=$phone['project'];
            $request['multi']=json_encode($multi);
            unset($phone);
            unset($multi);
            $res = $this->multixsend($request,$type);
        }else{
            $request = array(
                'to' => $phone,
                'content' => $prefix_message . $message . $suffix_message
            );
            $res = $this->send($request,$type);
        }
        
        @$this->insertMongoLog('Saiyou','',$request,$res,$smsServiceUse);
        
        unset($request);        
        if($res['status']='success'){
            return true;
        }
        return false;
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
    
    protected function createSignature($request){
        $r="";
        switch($this->signType){
            case 'normal':
                $r=$this->_extArr['appkey'];
                break;
            case 'md5':
                $r=$this->buildSignature($this->argSort($request));
                break;
            case 'sha1':
                $r=$this->buildSignature($this->argSort($request));
                break;
        }
        return $r;
    }

    protected function buildSignature($request){
        $arg="";
        $app=$this->_userName;
        $appkey=$this->_extArr['appkey'];
        while (list ($key, $val) = each ($request)) {
            $arg.=$key."=".$val."&";
        }
        $arg = substr($arg,0,count($arg)-2);
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        if($this->signType=='sha1'){
            $r=sha1($app.$appkey.$arg.$app.$appkey);
        }else{
            $r=md5($app.$appkey.$arg.$app.$appkey);
        }
        return $r;
    }

    protected function argSort($request) {
        ksort($request);
        reset($request);
        return $request;
    }

    protected function getTimestamp(){
        $api=$this->_baseUrl.'service/timestamp.json';
        $ch = curl_init($api) ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
        $output = curl_exec($ch) ;
        $timestamp=json_decode($output,true);
        return $timestamp['timestamp'];
    }

    protected function APIHttpRequestCURL($api,$post_data,$method='post'){
        if($method!='get'){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: $method"));
            if($method!='post'){
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            }
        }else{
            $url=$api.'?'.http_build_query($post_data);
            $ch = curl_init($url) ;
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1) ;
        }
        $output = curl_exec($ch);
        curl_close($ch);
        $output = trim($output, "\xEF\xBB\xBF");
        return json_decode($output,true);
    }

    public function send($request,$type){
        if($type=='voice'){
            $api=$this->_baseUrl.'voice/send.json';
        }else{
            $api=$this->_baseUrl.'message/send.json';
        }
        $request['appid']=$this->_userName;
        $request['timestamp']=$this->getTimestamp();
        if(empty($this->signType)
            && $this->signType==""
            && $this->signType!="normal"
            && $this->signType!="md5"
            && $this->signType!="sha1"){
            $this->signType='normal';
        }else{
            $request['sign_type']=$this->signType;
        }
        $request['signature']=$this->createSignature($request);
        $send=$this->APIHttpRequestCURL($api,$request);
        return $send;
    }

//    public function xsend($request){
//        $api=$this->_baseUrl.'message/xsend.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $send=$this->APIHttpRequestCURL($api,$request);
//        return $send;
//    }
    public function multixsend($request, $type){
        if($type=='voice'){
            $api=$this->_baseUrl.'voice/multixsend.json';
        }else{
            $api=$this->_baseUrl.'message/multixsend.json';
        }
        $request['appid']=$this->_userName;
        $request['timestamp']=$this->getTimestamp();
        if(empty($this->signType)
            && $this->signType==""
            && $this->signType!="normal"
            && $this->signType!="md5"
            && $this->signType!="sha1"){
            $this->signType='normal';
        }else{
            $request['sign_type']=$this->signType;
        }


        $request['signature']=$this->createSignature($request);
        $send=$this->APIHttpRequestCURL($api,$request);
        return $send;
    }
//
//    public function subscribe($request){
//        $api=$this->_baseUrl.'addressbook/message/subscribe.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $subscribe=$this->APIHttpRequestCURL($api,$request);
//        return $subscribe;
//    }
//
//    public function unsubscribe($request){
//        $api=$this->_baseUrl.'addressbook/message/unsubscribe.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $unsubscribe=$this->APIHttpRequestCURL($api,$request);
//        return $unsubscribe;
//    }
    public function log($request){
        $api=$this->_baseUrl.'log/message.json';
        $request['appid']=$this->_userName;
        $request['timestamp']=$this->getTimestamp();
        if(empty($this->signType)
            && $this->signType==""
            && $this->signType!="normal"
            && $this->signType!="md5"
            && $this->signType!="sha1"){
            $this->signType='normal';
        }else{
            $this->signType=$this->signType;
            $request['sign_type']=$this->signType;
        }
        $request['signature']=$this->createSignature($request);
        $log=$this->APIHttpRequestCURL($api,$request);
        print_r($log);exit;
        return $log;
    }
//    public function getTemplate($request){
//        $api=$this->_baseUrl.'message/template.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $templates=$this->APIHttpRequestCURL($api,$request,'get');
//        return $templates;
//    }
//    public function postTemplate($request){
//        $api=$this->_baseUrl.'message/template.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $templates=$this->APIHttpRequestCURL($api,$request,'post');
//        return $templates;
//    }
//    public function putTemplate($request){
//        $api=$this->_baseUrl.'message/template.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $templates=$this->APIHttpRequestCURL($api,$request,'PUT');
//        return $templates;
//    }
//    public function deleteTemplate($request){
//        $api=$this->_baseUrl.'message/template.json';
//        $request['appid']=$this->_userName;
//        $request['timestamp']=$this->getTimestamp();
//        if(empty($this->signType)
//            && $this->signType==""
//            && $this->signType!="normal"
//            && $this->signType!="md5"
//            && $this->signType!="sha1"){
//            $this->signType='normal';
//        }else{
//            $this->signType=$this->signType;
//            $request['sign_type']=$this->signType;
//        }
//        $request['signature']=$this->createSignature($request);
//        $templates=$this->APIHttpRequestCURL($api,$request,'DELETE');
//        return $templates;
//    }
}