<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/6/13
 * Time: 11:51
 */
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Application;
use yii\web\Response;
use yii\helpers\Url;
use common\helpers\AlarmHelper;
use common\helpers\MessageHelper;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\UserCredit;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRentCredit;
use common\models\UserRepayment;
use common\models\UserRepaymentPeriod;
use common\helpers\StringHelper;
use common\helpers\Util;


//推送信息
class MessageService extends Component
{

    /**
     * 发送注册成功活动短信
     */
    public static function sendRegisterSuccessMsg($phone){
        try{
            $actInfo = Util::getActInfos('ygd_act_national_day');
            if($actInfo && isset($actInfo['send_message']) && $actInfo['send_message']){
                //\Yii::error($actInfo['send_message']);
                @MessageHelper::sendSMS($phone,$actInfo['send_message'],"smsService");
            }
        }catch (\Exception $e){
        }
        return true;
    }

    /**
     * 发送微信
     * @param $id
     * @param $message
     */
    public static function sendWeixin($id,$message){
       // AlarmHelper::send($id,$message);

        return [
            'code'=>0,
            'message'=>'发送成功'
        ];
    }


    //发送短信
    public static function sendMessage($phone,$send_message,$smsService="smsService"){

        $ret = MessageHelper::sendSMS($phone,$send_message,$smsService);
        if($ret){
            return [
                'code'=>0,
                'message'=>'发送成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>'发送失败'
            ];
        }
    }


    /**
     * 员工帮资产借款审核通过
     * @param $user_id 借款用户ID
     * @param $order_id 订单ID
     * @return array
     */
    public static function sendMessageLoanYgbPass($user_id,$order_id){

        $loan_person = LoanPerson::findOne(['id'=>$user_id]);
        if(false == $loan_person){
            return [
                'code'=>-1,
                'message'=>'借款用户查找失败'
            ];
        }
        $phone = $loan_person->phone;

        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'该订单不存在'
            ];
        }

        if($user_id != $user_loan_order->user_id){
            return [
                'code'=>-1,
                'message'=>'借款人和订单不匹配'
            ];
        }

        if(UserLoanOrder::STATUS_PAY != $user_loan_order->status){
            return [
                'code'=>-1,
                'message'=>'订单状态不正确，该订单不处于打款中'
            ];
        }

        $order_type = $user_loan_order->order_type;
        $money_amount = sprintf("%0.2f",$user_loan_order->money_amount/100);
        $card_id = $user_loan_order->card_id;
        $card_info = CardInfo::findOne(['user_id'=>$user_id,'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-1,
                'message'=>'获取银行卡信息失败'
            ];
        }
        $bank_id = $card_info->bank_id;
        $bank_name = CardInfo::$bankInfo[$bank_id];
        $type = $card_info->type;
        $type_name = CardInfo::$type[$type];
        $card_no = $card_info->card_no;
        $card_no_name = substr($card_no,-4);

        $send_message ="恭喜您申请的".$money_amount."元借款通过审核，我们将在一个工作日内打款到您的".$bank_name.$type_name."(尾号".$card_no_name.")上，请注意查收。";
        $ret = UserLoanOrder::sendSms($phone, $send_message,$user_loan_order);
        //$ret = self::sendMessage($phone,$send_message,"smsService");
        if(0 == $ret['code']){
            return [
                'code'=>0,
                'message'=>'发送成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>'短信发送失败'
            ];
        }

    }


    /**
     * 员工帮资产借款审核拒绝
     * @param $user_id 借款用户ID
     * @param $order_id 订单ID
     * @return array
     */
    public static function sendMessageLoanYgbReject($user_id,$order_id){
        $loan_person = LoanPerson::findOne(['id'=>$user_id]);
        if(false == $loan_person){
            return [
                'code'=>-1,
                'message'=>'借款用户查找失败'
            ];
        }
        $phone = $loan_person->phone;

        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'该订单不存在'
            ];
        }

        if($user_id != $user_loan_order->user_id){
            return [
                'code'=>-1,
                'message'=>'借款人和订单不匹配'
            ];
        }

        if(UserLoanOrder::STATUS_CHECK <= $user_loan_order->status){
            return [
                'code'=>-1,
                'message'=>'订单状态不正确，该订单不处于拒绝中'
            ];
        }

        $order_type = $user_loan_order->order_type;
        $money_amount = sprintf("%0.2f",$user_loan_order->money_amount/100);
        $reason_remark = $user_loan_order->reason_remark;
        if(empty($reason_remark)){
            return [
                'code'=>-1,
                'message'=>'拒绝原因未填写'
            ];
        }

        $send_message = "您申请的一笔".$money_amount."元借款";
        $send_message =$send_message."审核未通过,原因为：".$reason_remark;
        $ret = UserLoanOrder::sendSyncSms($phone, $send_message,$user_loan_order);
        //$ret = self::sendMessage($phone,$send_message,"smsService");
        if($ret){
            return [
                'code'=>0,
                'message'=>'发送成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>'短信发送失败'
            ];
        }
    }

    /**
     * 员工帮资产借款打款到账
     * @param $user_id 借款用户ID
     * @param $order_id 订单ID
     * @return array
     */
    public static function sendMessageLoanYgbArrival($user_id,$order_id){
        $loan_person = LoanPerson::findOne(['id'=>$user_id]);
        if(false == $loan_person){
            return [
                'code'=>-1,
                'message'=>'借款用户查找失败'
            ];
        }
        $phone = $loan_person->phone;

        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'该订单不存在'
            ];
        }

        if($user_id != $user_loan_order->user_id){
            return [
                'code'=>-1,
                'message'=>'借款人和订单不匹配'
            ];
        }

        if(UserLoanOrder::STATUS_LOAN_COMPLETE != $user_loan_order->status){
            return [
                'code'=>-1,
                'message'=>'订单状态不正确，该订单不处于已放款'
            ];
        }

        $order_type = $user_loan_order->order_type;
        $money_amount = sprintf("%0.2f",$user_loan_order->money_amount/100);
        $card_id = $user_loan_order->card_id;
        $card_info = CardInfo::findOne(['user_id'=>$user_id,'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-1,
                'message'=>'获取银行卡信息失败'
            ];
        }
        $bank_id = $card_info->bank_id;
//        $bank_name = CardInfo::$bankInfo[$bank_id];
        $bank_name = CardInfo::$loan_bankInfo[$bank_id];
        $type = $card_info->type;
        $type_name = CardInfo::$type[$type];
        $card_no = $card_info->card_no;
        $card_no_name = substr($card_no,-4);
        $back_type_name = "";
        switch($order_type){
            case UserLoanOrder::LOAN_TYPE_LQD:
                $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id]);
                if(false == $user_loan_order_repayment){
                    return [
                        'code'=>-1,
                        'message'=>'获取零钱包分期总表数据失败'
                    ];
                }
                $plan_repayment_time = $user_loan_order_repayment->plan_fee_time;
                if(empty($plan_repayment_time)){
                    return [
                        'code'=>-1,
                        'message'=>'获取零钱包计划还款时间失败'
                    ];
                }
                $plan_repayment_time = date('Y年m月d日',$plan_repayment_time);
                $back_type_name = "还款日为:".$plan_repayment_time;
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $back_type_name = "每月还款日凌晨平台自动扣款，请确保卡内金额充足.";
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                return [
                    'code'=>-1,
                    'message'=>'订单类型错误'
                ];
                break;
        }

        $send_message = "您的".$money_amount."元借款已到账，收款银行卡为".$bank_name."(尾号".$card_no_name.")，请注意查收。";
        // todo:异步发送短信的方式
        $ret = MessageHelper::sendSMS($phone, $send_message, 'smsService_TianChang_HY',$loan_person->source_id);
        if($ret){
            return [
                'code'=>0,
                'message'=>'发送成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>'短信发送失败'
            ];
        }
    }

    /**
     * 催收
     * @param $user_id 借款用户ID
     * @param $order_id 订单ID
     * @param $repayment_id 分期总表ID
     * @param $repayment_period_id 分期计划表ID
     * @return array
     */
    public static function sendMessageLoanYgbollection($user_id,$order_id,$repayment_id,$repayment_period_id){

        $loan_person = LoanPerson::findOne(['id'=>$user_id]);
        if(false == $loan_person){
            return [
                'code'=>-1,
                'message'=>'借款用户查找失败'
            ];
        }
        $phone = $loan_person->phone;

        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'该订单不存在'
            ];
        }

        if($user_id != $user_loan_order->user_id){
            return [
                'code'=>-1,
                'message'=>'借款人和订单不匹配'
            ];
        }

        if(UserLoanOrder::STATUS_LOAN_COMPLETE != $user_loan_order->status){
            return [
                'code'=>-1,
                'message'=>'订单状态不正确，该订单不处于已放款'
            ];
        }

        $order_type = $user_loan_order->order_type;
        $money_amount = sprintf("%0.2f",$user_loan_order->money_amount/100);
        $card_id = $user_loan_order->card_id;
        $card_info = CardInfo::findOne(['user_id'=>$user_id,'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-1,
                'message'=>'获取银行卡信息失败'
            ];
        }
        $bank_id = $card_info->bank_id;
        $bank_name = CardInfo::$bankInfo[$bank_id];
        $type = $card_info->type;
        $type_name = CardInfo::$type[$type];
        $card_no = $card_info->card_no;
        $card_no_name = substr($card_no,-4);
        $back_type_name = "";
        switch($order_type){
            case UserLoanOrder::LOAN_TYPE_LQD:
                $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id]);
                if(false == $user_loan_order_repayment){
                    return [
                        'code'=>-1,
                        'message'=>'获取零钱包分期总表数据失败'
                    ];
                }
                $plan_repayment_time = $user_loan_order_repayment->plan_repayment_time;
                if(empty($plan_repayment_time)){
                    return [
                        'code'=>-1,
                        'message'=>'获取零钱包计划还款时间失败'
                    ];
                }
                if((strtotime(date('Y-m-d',time()))+4*24*3600)<strtotime(date('Y-m-d',$plan_repayment_time))){
                    return [
                        'code'=>-1,
                        'message'=>'该单子还款时间在4天内无需还款，不需要催收'
                    ];
                }
                $back_type_name = "申请后平台将自动扣款（逾期将收取本息的".UserCredit::POCKET_LATE_APR."%滞纳金）。";
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $user_repayment = UserRepayment::findOne(['user_id'=>$user_id,'id'=>$repayment_id]);
                if(false == $user_repayment){
                    return [
                        'code'=>-1,
                        'message'=>'获取房租宝分期总表数据失败'
                    ];
                }
                $user_repayment_period = UserRepaymentPeriod::findOne(['user_id'=>$user_id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
                if(false == $user_repayment){
                    return [
                        'code'=>-1,
                        'message'=>'获取房租宝分期计划表数据失败'
                    ];
                }
                $plan_repayment_time = $user_repayment_period->plan_repayment_time;
                if((strtotime(date('Y-m-d',time()))+4*24*3600)<strtotime(date('Y-m-d',$plan_repayment_time))){
                    return [
                        'code'=>-1,
                        'message'=>'该单子还款时间在4天内无需还款，不需要催收'
                    ];
                }

                $back_type_name = "3天后平台将自动扣款（逾期将收取本息的".UserCredit::FZD_LATE_APR."%滞纳金）。";
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                return [
                    'code'=>-1,
                    'message'=>'订单类型错误'
                ];
                break;
        }

        $send_message = "您申请的一笔".$money_amount."元'".UserLoanOrder::$loan_type[$order_type]."'";
        $send_message =$send_message."3天后到期，请在3天内使用APP提交还款申请，并确保扣款卡内(".$bank_name.$type_name."(尾号".$card_no_name.")金额充足，";
        $send_message =$send_message.$back_type_name;

        $ret = self::sendMessage($phone,$send_message,"smsService");
        if(0 == $ret['code']){
            return [
                'code'=>0,
                'message'=>'发送成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>'短信发送失败'
            ];
        }
    }


    // 所有短信接口超时时间
    public static $timeout = 5;
    public static $ctx_params = array(
        'http' => array(
            'timeout' => 5
        )
    );

    /**
     * 查看短信发送状态
     * @return [type] [description]
     */
    public static function statusMessage($smsServiceUse = 'smsServiceXQB_XiAo_YX')
    {
        $smsServiceParams = \Yii::$app->params[$smsServiceUse];
        $url = $smsServiceParams['rpturl'];
        $uid = $smsServiceParams['uid'];
        $auth = md5($smsServiceParams['code'] . $smsServiceParams['password']);
        $result = '';
        try {
            $ctx = stream_context_create(self::$ctx_params);
            $result = \file_get_contents("{$url}?uid={$uid}&auth={$auth}", false, $ctx);
        } catch (\Exception $e) {
            \yii::error(\sprintf('获取短信状态异常 %s exception %s', $smsServiceUse, $e), \common\base\LogChannel::SMS_STATUS);
        }

        if (!empty($result)) {
            $result = explode(";", $result);
            $res = [];
            $tmp = [];
            foreach ($result as $key => $val) {
                $d = explode(',', $val);
                if (isset($d[2])) {
                    $tmp['phone'] = $d[2];
                    $tmp['sms_id'] = $d[1];
                    $tmp['code'] = $d[3];
                    $tmp['send_time'] = strtotime($d[0]);
                    $tmp['type'] = \common\models\message\MessageStatusLog::TYPE_XIAO;
                    $tmp['type_channel'] = $smsServiceUse;
                    $res[] = $tmp;
                }
            }
        } else {
            $res = [];
        }
        return $res;
    }

}

