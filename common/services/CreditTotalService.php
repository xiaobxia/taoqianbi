<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/20
 * Time: 20:19
 */
namespace common\services;

use common\models\loan\LoanCollectionOrder;
use common\models\UserCreditReviewLog;
use common\models\UserCreditTotal;
use common\models\UserLoanCollection;
use common\models\UserLoanOrder;
use common\models\LoanPerson;
use common\models\UserLoanOrderRepayment;
use Yii;
use yii\base\Component;
use yii\base\UserException;
use yii\base\Exception;
use yii\helpers\Url;

class CreditTotalService extends Component
{
    const STATUS_NORMAL= 1;
    const STATUS_NO_NORMAL = 0;
    const STATUS_EXCEPTION = -1;
    const CREDIT_TOTAL_MAX = 300000;
    // 单笔提额最大心智
    const CREDIT_QUOTA_MAX_AMOUNT = 15000;
    const CREDIT_TOTAL_ADD = 100000;

    /**
     * 判断一笔订单是否是正常还款
     * @param  integer $order_id 订单ID
     * @param  integer $day 逾期天数分割线
     * @return integer $status 1:正常 0：非正常 -1：异常
     */
    public function checkOrderRepaymentNoraml($order_id,$day=3){

        //查询订单是否存在
        $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$order_id]);
        if(false == $user_loan_order_repayment){
            return [
                'status'=>self::STATUS_EXCEPTION
            ];
        }
        $status = $user_loan_order_repayment->status;
        $overdue_day = $user_loan_order_repayment->overdue_day;
        if(UserLoanOrderRepayment::STATUS_REPAY_COMPLETE == $user_loan_order_repayment->status){
            if($user_loan_order_repayment['true_repayment_time'] > 0 && ($user_loan_order_repayment['true_repayment_time'] - $user_loan_order_repayment['loan_time']) < 86400*4){
                return [
                        'status'=>self::STATUS_NO_NORMAL
                ];
            }
            //表示已经还款
            $loan_collection_order = LoanCollectionOrder::find()->where(['user_loan_order_id'=>$order_id])->all();
            if(false === $loan_collection_order){
                return [
                    'status'=>self::STATUS_EXCEPTION
                ];
            }
            if(empty($loan_collection_order)){
                //正常还款
                return [
                    'status'=>self::STATUS_NORMAL
                ];
            }
            if($overdue_day <= $day){
                $is_noraml = 1;
                foreach($loan_collection_order as $item){
                    $renew_status = $item->renew_status;
                    if(LoanCollectionOrder::RENEW_REJECT == $renew_status){
                        $is_noraml = 0;
                        break;
                    }
                }
                return  ['status'=> ($is_noraml ? self::STATUS_NORMAL : self::STATUS_NO_NORMAL)];
            }else{
                $is_noraml = 1;
                foreach($loan_collection_order as $item) {
                    $renew_status = $item->renew_status;
                    if(LoanCollectionOrder::RENEW_PASS != $renew_status){
                        $is_noraml = 0;
                        break;
                    }
                }
                return  ['status'=> ($is_noraml ? self::STATUS_NORMAL : self::STATUS_NO_NORMAL)];
            }
        } else{
            //表示未还款
            return [
                'status'=>self::STATUS_EXCEPTION
            ];
        }
    }

    //还款后提额
    public static  function increaseUserCreditAccount($repayment) {
        $user_id = $repayment['user_id'];
        $user_credit_total = UserCreditTotal::findOne(['user_id'=>$user_id]);
        if (!$user_credit_total) {
            return 0;
        }
        $before_amount = $user_credit_total->amount;

        if($before_amount >= self::CREDIT_TOTAL_MAX) {
            return 0;
        }
        $repay_time = $repayment['true_repayment_time'];

        if(empty($user_credit_total['increase_time'])||($repay_time > $user_credit_total['increase_time'])) {
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where(['user_id'=>$user_id,'status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
//                ->andWhere(" true_repayment_time >".$user_credit_total['increase_time'])
                ->andWhere(['>', 'true_repayment_time', $user_credit_total['increase_time']])
                ->asArray()->all();
            $repay_amount = 0;//折合还款金额
            $increase_amount = 0;//增加额度
            foreach($user_loan_order_repayment as $data){
                $code = self::checkOrderRepaymentNoraml($data['order_id'],1);
                if(!isset($code['status'])){
                    return 0;
                }
                $code = $code['status'];
                if($code == self::STATUS_NORMAL) {
                    $loan_order = UserLoanOrder::find()->where(['id'=>$data['order_id']])->one();
                    $day = $loan_order['loan_term'];
                    $repay_amount += intval($data['principal']*$day/7);
                }
            }

            if($repay_amount > 0) {
                $increase_amount = min((intval($repay_amount/20000))*1000, self::CREDIT_QUOTA_MAX_AMOUNT);
                // 设置提额上线 : 每次最高是 150

//                if ($increase_amount > self::CREDIT_QUOTA_MAX_AMOUNT) {
//                    $increase_amount = self::CREDIT_QUOTA_MAX_AMOUNT;
//                }
            }
            if($increase_amount >= 1000) {
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{

                    if(($increase_amount + $before_amount) > self::CREDIT_TOTAL_MAX) {
                        $increase_amount = self::CREDIT_TOTAL_MAX - $before_amount;
                    } else if($increase_amount + $before_amount < 100000 && $before_amount - $user_credit_total->repayment_credit_add >= 50000) {
                        $increase_amount = 100000 - $before_amount;
                    }
                    //更新额度
                    $user_credit_total->amount = $user_credit_total->amount + $increase_amount;
                    $user_credit_total->repayment_credit_add = $user_credit_total->repayment_credit_add+$increase_amount;
                    $user_credit_total->updated_at = time();
                    $user_credit_total->increase_time = time();

                    /*if(!$user_credit_total->save()){
                         throw new Exception('用户信用额度更新失败');
                    }*/
                    //总额度流水表
                    $log_amount = new  UserCreditReviewLog();
                    $log_amount->user_id = $user_credit_total->user_id;
                    $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_INCREASE;
                    $log_amount->before_number = $before_amount;
                    $log_amount->operate_number = $increase_amount;
                    $log_amount->after_number = $user_credit_total->amount;
                    $log_amount->status = UserCreditReviewLog::STATUS_PASS;
                    $log_amount->creater_name = 'auto';
                    $log_amount->created_at = time();
                    //$log_amount->save();
                    $transaction->commit();

                    // ---------------------------------- start 记录提额日志 done: By Ron
                    $user_id = $user_credit_total->user_id;
                    $loan_person = LoanPerson::findOne(['id'=>$user_id]);
                    /*if ($loan_person) {
                        if ($loan_person->phone) {
                            // 处理借款消息日志
                            Yii::$container->get("financialService")->handleIncreaseMessage($loan_person->phone,$user_credit_total->amount);
                        }
                    }*/
                    // ----------------------------------end

                   // return $increase_amount;
                    return 0;
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return 0;
                }
            }
            return 0;
        } else {
            return 0;
        }
    }
}