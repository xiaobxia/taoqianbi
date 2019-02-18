<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/13
 * Time: 19:49
 */
namespace common\helpers;

use common\models\HfdOrder;
use common\models\LoanHfdOrder;
use common\models\LoanPerson;
use common\models\HfdFinancialRecord;
use Yii;
use common\helpers\MessageHelper;

class BuildingMessageHelper
{
    public static function sendSMS($order_id,$phone,$status,$source)
    {
        $name = "客户";
        $loan_person = LoanPerson::find()->where(['phone'=>$phone])->limit(1)->one();
        if(!empty($loan_person)){
            if(!empty($loan_person['name'])){
                $name = $loan_person['name'];
            }
        }
        if($status == LoanHfdOrder::LOAN_STATUS_RISK_TRIAL_CANCEL || $status == LoanHfdOrder::LOAN_STATUS_RISK_RETRIAL_CANCEL || $status == LoanHfdOrder::LOAN_STATUS_RISK_FINAL_TRIAL_CANCEL){
            $message = "尊敬的".$name."，很遗憾您的提单（订单号为".$order_id."）未通过风控审核，无法授信，订单作废。";
        }elseif($status == LoanHfdOrder::LOAN_STATUS_STAY_DISPATCH){
            $message = "尊敬的".$name."，恭喜您订单号为".$order_id."的提单已通过风控终审，现在处在待签约状态，请联系运营人员做好签约安排。";
        }elseif($status == HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY){
            $message = "尊敬的".$name."，恭喜您订单号为".$order_id."的提单已放款成功，感谢您的辛勤努力。";
        }elseif($status == HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL){
            $message = "尊敬的".$name."，很遗憾您的提单（订单号为".$order_id."）放款失败，订单作废。";
        }elseif($status == LoanHfdOrder::LOAN_STATUS_ALREADY_DISPATCH_STAY_PUSH_FINANCE){
            $message = "尊敬的".$name."，恭喜您订单号为".$order_id."的提单已签约成功，现在处在待放款状态，请联系运营人员做好放款安排。";
        }elseif($status == LoanHfdOrder::LOAN_STATUS_NOTARIZATION_CANCEL){
            $message = "尊敬的".$name."，很遗憾您的提单（订单号为".$order_id."）签约失败，订单作废。";
        } else{
            return false;
        }
        $hfd_order = HfdOrder::find()->where(['order_id'=>$order_id])->one();
        $choice = "smsService1";
        if($source == HfdOrder::SOURCE_HF_APP || $hfd_order['shop_code'] == '6GHWWO'){
            $choice = 'smsService9';
        }
        MessageHelper::sendSMS($phone,$message,$choice);
    }
}