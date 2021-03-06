<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/17
 * Time: 11:03
 */
namespace common\models;

use common\helpers\ToolsUtil;
use common\models\MarketingMessageSubmitLog;
use yii;
use yii\base\Exception;
use yii\base\Component;


/**
 * 营销短信
 */
class SuDunMarketingMessage extends Component
{

    private  $account = "xianjinbaika";
    private  $pswd = "60K84fJk";
    private  $send_msg_url = "http://139.196.218.201:8080/sms.aspx";



    public function __get($name){
        return parent::__get($name); // TODO: Change the autogenerated stub
    }

    /**
     * 短信提交
     * @param array $mobile 被发送的手机号组
     * @param string $msg  信息
     * @param integer $user_id 用户UID
     * @param string $operator_name 操作人
     */
    public function sendMessage($mobile,$msg,$user_id,$operator_name){
        $account = $this->account;
        $pswd = $this->pswd;
        $send_msg_url = $this->send_msg_url;
        if(!is_array($mobile)){
            return false ;
        }
        /*$mobile_string = "";
        $i=1;
        foreach($mobile as $item){
            if(1==$i){
                $mobile_string = $item;
            }else{
                $mobile_string = $mobile_string.",".$item;
            }
            $i++;
        }*/
        $mobile_string = implode(',', $mobile);

        if(empty($mobile_string)||empty($msg)){
            return false;
        }
        $post_data = [
            'action'=>'send',
            'userid'=>50,
            'account'=>$account,
            'password'=>$pswd,
            'mobile'=>$mobile_string,
            'content'=>$msg,
        ];
        try{
            $mobile_string_new = implode('--',$mobile);
            $marketing_message = new MarketingMessageSubmitLog();
            $marketing_message->user_id = $user_id;
            $marketing_message->mobile = htmlspecialchars($mobile_string_new);
            $marketing_message->msg = $msg;
            $marketing_message->operator_name = $operator_name;
            $marketing_message->created_at = time();
            $marketing_message->updated_at = time();
            $response=$this->send_http_request($send_msg_url,http_build_query($post_data));
            if($response){
                if(0 == $response['code']){
                    $marketing_message->status = MarketingMessageSubmitLog::STATUS_SUBMIT_SUCCESS;
                    $marketing_message->respstatus = 0;
                    $marketing_message->resptime = time();
                    $marketing_message->msgid = $response['taskID'];
                    $marketing_message->save();
                    return [
                        'code'=>0,
                        'message'=>'success',
                        'data'=>$response
                    ];
                }else{
                    $marketing_message->status = MarketingMessageSubmitLog::STATUS_SUBMIT_FAILED;
                    $marketing_message->respstatus = $response['code'];
                    $marketing_message->save();
                    return [
                        'code'=>-1,
                        'message'=>$response['message']
                    ];
                }

            }else{
                $marketing_message->status = MarketingMessageSubmitLog::STATUS_SUBMIT_FAILED;
                $marketing_message->save();
                return [
                    'code'=>-1,
                    'message'=>'不支持curl',
                ];
            }
        }catch(\Exception $e){
            Yii::error('MarketingMessageSubmitLog保存失败:'.json_encode($marketing_message),'collection');
        }
    }

    function send_http_request($url,$data = null){

        if(function_exists('curl_init')){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);

            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);

            $xmlstring = simplexml_load_string($output, 'SimpleXMLElement', LIBXML_NOCDATA);
            $result = json_decode(json_encode($xmlstring),true);

            if (strtolower($result['returnstatus']) === 'success') {
                return [
                    'code'=>0,
                    'status'=>$result['returnstatus'],
                    'error'=>$result['message'],
                    'taskID'=>$result['taskID'],
                    'success_count'=>$result['successCounts'],
                ];
            }else{  //发送失败
                return [
                    'code'=>1,
                    'status'=>$result['returnstatus'],
                    'error'=>$result['message']
                ];
            }

        }else{
            return false;
        }

    }
    //获取剩余金额
    public function getRest(){
        $account = $this->account;
        $pswd = $this->pswd;
        $send_msg_url = $this->send_msg_url;
        $post = [
            'action'=>'overage',
            'userid'=>50,
            'account'=>$account,
            'password'=>$pswd,
        ];
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$send_msg_url);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post));
        //返回值
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        curl_close($curl);
        $xmlstring = simplexml_load_string($output, 'SimpleXMLElement', LIBXML_NOCDATA);
        $result = json_decode(json_encode($xmlstring),true);
        if (strtolower($result['returnstatus']) === 'sucess') {
            return '剩余:'.$result['overage'].'条  总共'.$result['sendTotal'].'条';
        }else{
            return '---';
        }
    }
}
