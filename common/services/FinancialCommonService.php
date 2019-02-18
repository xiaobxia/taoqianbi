<?php
namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\AutoDebitLog;
use common\models\FinancialDebitRecord;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoanOrder;
use common\models\FinancialLoanRecord;
use common\models\asset\AssetOrder;

class FinancialCommonService extends Component
{
    /**
     * 放款对账
     * @param $business_id 业务订单ID
     * @param $type 业务类型
     * @return array
     */
    public function checkLoanOrder($business_id, $type) {
        $back_result = "";
        $data = "";

        if ($type == FinancialLoanRecord::TYPE_JUPEI ) { /*拒就赔*/
            $service = Yii::$container->get('orderService');
            $back_result = $service->withdrawCheckRedPacket($business_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        }elseif($type == FinancialLoanRecord::TYPE_INVITE_REBATES) { /*邀请返现金*/
            $service = Yii::$container->get('InviteRebatesService');
            $back_result = $service->withdrawCheckInviteRebatesRecord($business_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        } elseif (in_array($type, FinancialLoanRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->withdrawCheckLoanOrder($business_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        } elseif (in_array($type, FinancialLoanRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->withdrawCheckLoanOrder($business_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        }

        return [
            'code' => $back_result['code'],
            'message' => $back_result['message'],
            'data' => $data,
        ];
    }

    /**
     * 放款驳回通知业务方
     * @param $business_id 业务订单ID
     * @param string $remark 驳回备注
     * @param string $username 管理员名称
     * @param $type 业务类型
     * @return array
     */
    public function rejectLoanOrder($business_id, $remark = '', $username = ' ', $type) {
        $back_result = "";

        if ($type == FinancialLoanRecord::TYPE_JUPEI ) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->debitRejectLoanOrder($business_id);
        }elseif($type == FinancialLoanRecord::TYPE_INVITE_REBATES) { /*邀请返现金*/
            $service = Yii::$container->get('InviteRebatesService');
            $back_result = $service->debitRejectInviteRebatesRecord($business_id);
        }elseif (in_array($type, FinancialLoanRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->rejectLoan($business_id,$remark,$username);
        } elseif (in_array($type, FinancialLoanRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->loadCallbackPayMoney($business_id,0,$username,$remark);
        }

        return [
            'code' => $back_result['code'],
            'message' => $back_result['message'],
        ];

    }

    /**
     * 放款成功通知业务方
     * @param $business_id 业务订单ID
     * @param string $username 备注信息
     * @param string $username 管理员名称
     * @param $type 业务类型
     * @param  int $loanTime 放款时间
     * @return array
     */
    public function successLoanOrder($business_id, $remark, $username = '', $type,$loanTime= null) {
        $back_result = "";

        if ($type == FinancialLoanRecord::TYPE_JUPEI ) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->callbackRedPacketPayMoney($business_id);
        } elseif($type == FinancialLoanRecord::TYPE_INVITE_REBATES) { /*邀请返现金*/
            $service = Yii::$container->get('InviteRebatesService');
            $back_result = $service->callbackInviteRebatesRecord($business_id);
        }elseif (in_array($type, FinancialLoanRecord::$kd_platform_type)) {
            $repay_order = UserLoanOrderRepayment::find()->where('order_id = '.$business_id)->one();
            if(!empty($repay_order)) {
                return [
                        'code'=> 0,
                        'message'=> '回调成功',
                ];
            } else {
                $service = Yii::$container->get('orderService');
                $back_result = $service->callbackPayMoney($business_id,$remark,$username,$loanTime);
            }
        } elseif (in_array($type, FinancialLoanRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->loadCallbackPayMoney($business_id,1,$username,$remark,['loanTime'=>$loanTime]);
        }

        return [
            'code'=>$back_result['code'],
            'message'=>$back_result['message'],
        ];
    }


    /**
     * 扣款对账
     * @param $debit_loan_record_id 扣款业务订单表ID
     * @param $repayment_id 扣款总还款表ID
     * @param $repayment_period_id 扣款分期还款表ID
     * @param $type 业务类型
     * @return array
     */
    public function checkDebitOrder($debit_loan_record_id, $repayment_id, $repayment_period_id, $type) {
        $back_result = "";
        $data = "";

        if (in_array($type, FinancialDebitRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->withdrawCheckDebitOrder($debit_loan_record_id,$repayment_id,$repayment_period_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        } elseif (in_array($type, FinancialDebitRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->withdrawCheckDebitOrder($debit_loan_record_id,$repayment_id,$repayment_period_id);
            if(isset($back_result['data'])) {
                $data = $back_result['data'];
            }
        }

        return [
            'code' => $back_result['code'],
            'message' => $back_result['message'],
            'data' => $data,
        ];
    }

    /**
     * 扣款驳回通知业务方
     * @param $debit 扣款数据
     * @param $type_remark 类型和备注
     * @param string $username 管理员名称
     * @return array
     */
    public function rejectDebitOrder($debit, $type_remark, $username = ''){
        $type = intval($debit->type);//业务类型
        $loan_record_id = $debit->loan_record_id;//原订单表ID
        $repayment_id = $debit->repayment_id;//总还款表ID
        $repayment_peroid_id = $debit->repayment_peroid_id;//还款计划ID
        $amount = $debit->plan_repayment_money;//扣款金额
        $false_type = $type_remark['type'];//失败类型
        $false_remark = $type_remark['message'];//失败信息
        $back_result = "";

        if (in_array($type, FinancialDebitRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->debitReject($loan_record_id,$repayment_id,$repayment_peroid_id,$false_type,$false_remark,$username);
        }
//        elseif (in_array($type, FinancialDebitRecord::$other_platform_type)) {
//            $service = Yii::$container->get('assetService');
//            $back_result = $service->debitCallbackPayMoney($loan_record_id, $repayment_id, $repayment_peroid_id,3,$false_remark, $username,['false_type'=>$false_type,'false_remark'=>$false_remark]);
//        }

        return [
            'code'=>$back_result['code'],
            'message'=>$back_result['message'],
        ];
    }

    /**
     * 扣款失败通知业务方
     * @param $debit 扣款数据
     * @param $type_remark 类型和备注
     * @param string $username 管理员名称
     * @return array
     */
    public function falseDebitOrder($debit, $type_remark, $username = '') {
        $type = intval($debit->type);//业务类型
        $loan_record_id = $debit->loan_record_id;//原订单表ID
        $repayment_id = $debit->repayment_id;//总还款表ID
        $repayment_peroid_id = $debit->repayment_peroid_id;//还款计划ID
        $amount = $debit->plan_repayment_money;//扣款金额
        $false_type = $type_remark['type'];//失败类型
        $false_remark = $type_remark['message'];//失败信息
        $back_result = "";

        if (in_array($type, FinancialDebitRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->debitFailed($loan_record_id, $repayment_id, $repayment_peroid_id,"", $false_remark, $username);
        } elseif (in_array($type, FinancialDebitRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->debitCallbackPayMoney($loan_record_id, $repayment_id, $repayment_peroid_id,2,$false_remark, $username,['false_type'=>$false_type,'false_remark'=>$false_remark]);
        }

        return [
            'code'=>$back_result['code'],
            'message'=>$back_result['message'],
        ];
    }

    /**
     * 扣款成功通知业务方
     * @param $debit 扣款数据
     * @param string $remark 备注
     * @param string $username 管理员名称
     * @return array
     */
    public function successDebitOrder($debit, $remark = '', $username = '',$params=[]) {
        $type = intval($debit['type']);//业务类型
        $loan_record_id = $debit['loan_record_id'];//原订单表ID
        $repayment_id = $debit['repayment_id'];//总还款表ID
        $repayment_peroid_id = $debit['repayment_peroid_id'];//还款计划ID
        $amount = $type == FinancialDebitRecord::TYPE_YGB_LQB  ? $debit['true_repayment_money'] : $debit['plan_repayment_money'];//扣款金额
        $back_result = "";
        if (in_array($type, FinancialDebitRecord::$kd_platform_type)) {
            $service = Yii::$container->get('orderService');
            $back_result = $service->callbackDebitMoney($loan_record_id, $repayment_id, $repayment_peroid_id,$amount, $remark, $username,
                    [
                            'pay_order_id'=>$params['pay_order_id'],
                            'debit_channel'=>$params['third_platform'],
                            'order_uuid'=>$debit['order_id'],
                            'card_id'=>$debit['debit_card_id'],
                            'debit_account'=>isset($params['debit_account']) ? $params['debit_account'] : ''
                    ]);
        } elseif (in_array($type, FinancialDebitRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->debitCallbackPayMoney($loan_record_id, $repayment_id, $repayment_peroid_id,1,$remark, $username,['amount'=>$amount]);
        }

        return [
            'code' => $back_result['code'],
            'message' => $back_result['message'],
        ];
    }

    public function successCallbackDebitOrder($debit, $remark = '', $username = '',$params=[]) {
        $loan_record_id = $debit['order_id'];//原订单表ID
        $repayment_id = isset($params['repayment_id'])?$params['repayment_id']:'';//总还款表ID
        $amount = $debit['money'];//扣款金额
        try {
            $service = Yii::$container->get('orderService');
            $back_result = $service -> optimizedCallbackDebitMoney($loan_record_id, $repayment_id,$amount, $remark, $username,
                [
                    'pay_order_id'=>$params['pay_order_id'],
                    'repayment_type'=>$params['repayment_type'],
                    'debit_channel'=>$params['third_platform'],
                    'order_uuid'=>isset($debit['order_uuid'])?$debit['order_uuid']:$params['order_uuid'],
                    'card_id'=>$debit['card_id'],
                    'debit_account'=> isset($params['debit_account']) ? $params['debit_account'] : ''
                ]);
        } catch (\Exception $ex) {
            $back_result = [ 'code' => $ex->getCode(),'message' => $ex->getMessage()];
        }
        return [ 'code' => $back_result['code'], 'message' => $back_result['message']];
    }

    public function successCallbackDebitOrderModify($debit, $remark = '', $username = '',$params=[]){
        $loan_record_id = $debit['order_id'];//原订单表ID
        $repayment_id = isset($params['repayment_id'])?$params['repayment_id']:'';//总还款表ID
        $amount = $debit['money'];//扣款金额
        try {
            $service = Yii::$container->get('orderService');
            $back_result = $service -> optimizedCallbackDebitMoneyModify($loan_record_id, $repayment_id,$amount, $remark, $username,
                [
                    'pay_order_id'=>$params['pay_order_id'],
                    'repayment_type'=>$params['repayment_type'],
                    'debit_channel'=>$params['third_platform'],
                    'order_uuid'=>isset($debit['order_uuid'])?$debit['order_uuid']:$params['order_uuid'],
                    'card_id'=>$debit['card_id'],
                    'debit_account'=> isset($params['debit_account']) ? $params['debit_account'] : ''
                ]);
        } catch (\Exception $ex) {
            $back_result = [ 'code' => $ex->getCode(),'message' => $ex->getMessage()];
        }
        return [ 'code' => $back_result['code'], 'message' => $back_result['message']];
    }
    /**
     * 扣款失败发给业务方统计
     * @param $debit 扣款数据
     * @param $type_remark 类型和备注
     * @return array
     */
    public function falseDebitOrderSt($debit, $type_remark) {
        $type = intval($debit->type);//业务类型
        $loan_record_id = $debit->loan_record_id;//原订单表ID
        $repayment_id = $debit->repayment_id;//总还款表ID
        $repayment_peroid_id = $debit->repayment_peroid_id;//还款计划ID

        if (in_array($type, FinancialDebitRecord::$other_platform_type)) {
            $service = Yii::$container->get('assetService');
            $back_result = $service->debitFalseCallbackSt($loan_record_id, $repayment_id, $repayment_peroid_id,$type_remark);
        }
        return [
                'code'=>0,
                'message'=>'',
        ];
    }
}
