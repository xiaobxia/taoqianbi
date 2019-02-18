<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\models\LoanPersonBadInfoLog;
use common\models\PhoneReviewLog;
use common\models\CardInfo;
use common\models\LoanPersonBadInfo;
use common\models\UserContact;
use common\models\InstallmentShopOrder;
use common\models\InstallmentShop;
use common\models\UserInstallmentCredit;
use common\models\LoanPerson;
use common\models\UserCredit;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use common\models\UserQuotaPersonInfo;
use common\models\UserQuotaWorkInfo;
use common\models\UserRealnameVerify;
use common\models\UserRentCredit;
use yii\web\NotFoundHttpException;
use common\models\UserLoanOrderRepayment;

class RepaymentService extends Component
{
    public $message = '';

    public function repaymentCommonView(UserLoanOrder $loan_order,$card_id,$last_repayment_time){
        $loanPerson = LoanPerson::findOne($loan_order['user_id']);
        if(is_null($loanPerson)) {
            $this->message = '借款人不存在';
            return false;
        }
        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id'=>$loan_order['id']])->asArray()->all();
        $card_info = CardInfo::findOne($card_id);
        $equipment = UserDetail::find()->where(['user_id' => $loanPerson['id']])->one();
        return [
            'loanPerson' => $loanPerson,
            'trail_log' => $trail_log,
            'card_info' => $card_info,
            'equipment' => $equipment,
            'loanOrder' => $loan_order,
            'last_repayment_time' => $last_repayment_time
        ];
    }
    public function repaymentCommonView_mhk(UserLoanOrder $loan_order,$card_id,$last_repayment_time){
        $loanPerson = LoanPerson::find()->where(['id'=>$loan_order['user_id']])->one(Yii::$app->get('db_mhk'));
        if(is_null($loanPerson)) {
            $this->message = '借款人不存在';
            return false;
        }
        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id'=>$loan_order['id']])->asArray()->all(Yii::$app->get('db_mhk'));
        $card_info = CardInfo::find()->where(['id'=>$card_id])->one(Yii::$app->get('db_mhk'));
        $equipment = UserDetail::find()->where(['user_id' => $loanPerson['id']])->one(Yii::$app->get('db_mhk'));
        return [
            'loanPerson' => $loanPerson,
            'trail_log' => $trail_log,
            'card_info' => $card_info,
            'equipment' => $equipment,
            'loanOrder' => $loan_order,
            'last_repayment_time' => $last_repayment_time
        ];
    }

    /**
     * 发起还款申请
     * @param UserLoanOrder $user_loan_order 订单模型
     * @param UserLoanOrderRepayment $user_loan_order_repayment 还款模型
     * @param string $operator_name 操作人
     * @return array
     */
    public function commitRepaymentApply($user_loan_order, $user_loan_order_repayment, $operator_name, $repayment_money=''){
        if(!$user_loan_order->card_id){
            return [
                'code'=>-1,
                'message'=>'获取银行卡号失败',
            ];
        }
        if($user_loan_order_repayment->order_id!=$user_loan_order->id) {
            return [
                'code'=>-1,
                'message'=>'还款订单ID和订单ID不一致',
            ];
        }
        $status = $user_loan_order->status;
        switch($status){
            case UserLoanOrder::STATUS_BAD_DEBT :
            case UserLoanOrder::STATUS_OVERDUE :
            case UserLoanOrder::STATUS_REPAYING_CANCEL :
            case UserLoanOrder::STATUS_DEBIT_FALSE :
            case UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL :
            case UserLoanOrder::STATUS_REPAY_CANCEL :
            case UserLoanOrder::STATUS_PARTIALREPAYMENT :
            case UserLoanOrder::STATUS_LOAN_COMPLETE:
                //正常还款
                if($repayment_money > 0){
                    $user_loan_order_repayment->current_debit_money = $repayment_money;
                }else{
                    $user_loan_order_repayment->current_debit_money = $user_loan_order_repayment->principal+$user_loan_order_repayment->interests+$user_loan_order_repayment->late_fee-$user_loan_order_repayment->true_total_money;
                }
                $user_loan_order_repayment->debit_times = $user_loan_order_repayment->debit_times+1;
                $user_loan_order_repayment->updated_at = time();
                $user_loan_order_repayment->operator_name = $operator_name;//Yii::$app->user->identity->getId();
                $user_loan_order_repayment->status= UserLoanOrderRepayment::STATUS_CHECK;
                $user_loan_order_repayment->card_id = $user_loan_order->card_id;
                $user_loan_order_repayment->apply_repayment_time = time();
                $user_loan_order->status = UserLoanOrder::STATUS_APPLY_REPAY;
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    if(!$user_loan_order_repayment->update()){
                        return [
                            'code'=>-1,
                            'message'=>'还款失败，请稍后再试',
                        ];

                    }
                    if(!$user_loan_order->update()){
                        $transaction->rollBack();
                        return [
                            'code'=>-1,
                            'message'=>'还款失败，请稍后再试',
                        ];
                     }
                    $transaction->commit();
                    return [
                        'code'=>0,
                        'message'=>'还款申请已提交，请等待审核',
                    ];
                }catch(\Exception $e){
                    $transaction->rollBack();
                    return [
                        'code'=>-1,
                        'message'=>'还款失败，请稍后再试',
                    ];
                }
                break;
            case UserLoanOrder::STATUS_LOAN_COMPLING:
                return [
                    'code'=>-1,
                    'message'=>'该单处于申请还款中，请不要重复申请',
                ];
                break;
            case UserLoanOrder::STATUS_REPAY_COMPLETE;
                return [
                    'code'=>-1,
                    'message'=>'该单已经还款，请不要重复申请',
                ];
                break;
            default:
                return [
                    'code'=>-1,
                    'message'=>'还款失败，请稍后再试',
                ];

                break;
        }
    }
}