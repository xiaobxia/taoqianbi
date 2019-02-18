<?php
namespace common\services;

use common\models\LoanBlacklistDetail;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\TreeTestResult;
use common\models\UserCreditLog;
use common\models\UserCreditReviewLog;
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\helpers\CommonHelper;
use common\models\mongo\risk\OrderReportMongo;

class AutoCheckService extends Component
{
    /**
     * 机审订单, 根据决策树结果对订单进行处理。
     * @param UserLoanOrder $order
     * @param int $creditLines
     * @param string $message
     * @param string $result
     * @param string $tree
     * @return
     */
    public function check(UserLoanOrder $order, $creditLines, $message, $result, $tree = "") {
        $creditLines = \intval($creditLines);
        CommonHelper::stdout(\sprintf("order_id:%s, credit_line:%s, message:%s, result:%s\n", $order->id, $creditLines, $message, print_r($result, true)));

        if ($result == '1') {
            //老用户用户借款，不需要转人工初审；新用户借口需要转人工初审
            $loan_person = LoanPerson::findOne($order->user_id);
            if ($loan_person->customer_type == LoanPerson::CUSTOMER_TYPE_OLD) {
//                return $this->pass($order, $creditLines, $tree);
                //机审通过转人工初审
                return $this->manual($order, $tree);
            } else {
                //机审通过转人工初审
                return $this->manual($order, $tree);
            }
//            return $this->pass($order, $creditLines, $tree);
        }
        elseif ($result == '2') {
            return $this->manual($order, $tree);
        }
        else {
            if(!is_array($result)){
                $result=json_decode($result,true);
            }
            $interval=0;
            if(isset($result['interval'])){
                $interval=$result['interval'];
            }
            $head_code='';
            if(isset($result['head_code'])){
                $head_code=$result['head_code'];
            }
            $back_code='';
            if(isset($result['back_code'])){
                $back_code=$result['back_code'];
            }
            $txt='';
            if(isset($result['txt'])){
                $txt=$result['txt'];
            }

            $is_reject=true;
            /**
             * 处理跑风控398没有任何结果数据，进入分工审核
            **/
            if($head_code!='' && $back_code!='' && $txt==''){
                if(strtoupper($head_code)=='D2' && $back_code=='14'){
                    $reject_detail='';
                    $order_id=$order->id;
                    $order_reports = OrderReportMongo::find()
                        ->where([ 'order_id' => $order_id])
                        ->select([ 'reject_detail'])
                        ->asArray()
                        ->one();
                    if($order_reports){
                        if(isset($order_reports['reject_detail'])){
                            $reject_detail=trim($order_reports['reject_detail']);
                        }
                    }
                    unset($order_reports);
                    if($reject_detail=='' || empty($reject_detail)){
                        $is_reject=false;
                        //机审通过转人工初审
                        return $this->manual($order, $tree);
                    }
                }
            }

            if($is_reject){
                $this->reject($order, $creditLines, $interval, $head_code, $back_code, isset($result['keep_limit']) ? $result['keep_limit'] : false, $tree, $txt ?? '');
//                $this->sendSms($order, $message);
            }
        }
    }


    //通过
    private function pass($order, $creditLines, $tree)
    {
        $log = new UserOrderLoanCheckLog();
        $log->order_id = $order->id;
        $log->before_status = $order->status;
        $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
        $log->operator_name = "auto shell";
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['backend_name'];
        $log->remark = '';
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = 'A3';
        $log->back_code = '01';
        $log->tree = $tree;
        $log->rule_version = '';
        $log->save();

        $order->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
        $order->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['frontedn_name'];
        $order->operator_name = 'auto shell';
        $order->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;
        $order->is_hit_risk_rule = 0;
        $order->tree = $tree;
        $order->save();

        // $credit = UserCreditTotal::find()->where(['user_id' => $order['user_id']])->one();

        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($order['user_id'], $order->id);

        if ($creditLines * 100 > $credit->amount && $order->from_app == UserLoanOrder::FROM_APP_XJK) {
            $log_amount = new  UserCreditReviewLog();
            $log_amount->user_id = $order['user_id'];
            $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
            $log_amount->before_number = $credit->amount;
            $log_amount->operate_number = $creditLines * 100 - $credit->amount;
            $log_amount->after_number = $creditLines * 100;
            $log_amount->status = UserCreditReviewLog::STATUS_PASS;
            $log_amount->created_at = time();
            $log_amount->remark = "授信提额";
            $log_amount->save();

            $credit->amount = $creditLines * 100;
            $credit->save();

            echo "提额成功\n";

        }

    }

    private function sendSms($order, $message)
    {
        if (empty($message) || $message === '0' || $message === 0) {
            return;
        }
        $loanPerson = LoanPerson::findOne($order->user_id);
        if (UserLoanOrder::sendSms($loanPerson->phone, $message, $order)) {
            echo "短信发送成功\n";
        } else {
            echo "短信发送失败\n";
        }
    }


    //拒绝
    private function reject($order, $creditLines, $interval, $firstIndex, $secondIndex, $keep_limit = false, $tree, $remark = '') {
        $log = new UserOrderLoanCheckLog();

        $log->order_id = $order->id;
        $log->before_status = $order->status;
        $log->after_status = UserLoanOrder::STATUS_CANCEL;
        $log->operator_name = "auto shell";
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $reason_remark='';
        if(array_key_exists($firstIndex,LoanPersonBadInfo::$reject_code)){
            if(array_key_exists($secondIndex,LoanPersonBadInfo::$reject_code[$firstIndex]['child'])){
                $reason_remark=LoanPersonBadInfo::$reject_code[$firstIndex]['child'][$secondIndex]['backend_name'];
            }
        }
        $log->reason_remark = $reason_remark;
        $log->remark = $remark;
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = $firstIndex;
        $log->back_code = $secondIndex;
        $log->tree = $tree;
        $log->rule_version = '';
        $info = $order;

        //$credit = UserCreditTotal::find()->where(['user_id' => $info['user_id']])->one();
        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($info['user_id'], $order->id);

        $user_credit_log_type = '';
        $user_credit_log_apr = '';

        switch ($order->order_type) {

            case UserLoanOrder::LOAN_TYPE_LQD:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
                $user_credit_log_apr = $credit->pocket_apr;
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_FZD_CS_CANCEL;
                $user_credit_log_apr = $credit->house_apr;
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_FQSC_CS_CANCEL;
                $user_credit_log_apr = $credit->installment_apr;
                break;
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();

        try {
            //添加黑名单用户信息
            if ($firstIndex == 'D1' && $secondIndex == '01') {

                $loanblack = new LoanBlacklistDetail();
                $loanblack->user_id = $order->user_id;
                $loanblack->type = 1;
                $loanblack->content = '';
                $loanblack->source = 1;
                $loanblack->admin_username = $tree;
                $loanblack->created_at = time();
                $loanblack->updated_at = time();
                $loanblack->save();
            }

            if ($interval == 0) {
                $log->can_loan_type = UserOrderLoanCheckLog::CAN_LOAN;
            }
            else {
                $log->can_loan_type = UserOrderLoanCheckLog::MONTH_LOAN;
                $loanPerson = LoanPerson::findOne($order->user_id);
                $loanPerson->can_loan_time = time() + 86400 * 30 * $interval;
                if (!$loanPerson->save()) {
                    throw new Exception('loanPerson save_failed.');
                }
            }

            $info->status = UserLoanOrder::STATUS_CANCEL;
            $info->status_type = UserLoanOrder::STATUS_MACHINE;
            $reason_remark='';
            if(array_key_exists($firstIndex,LoanPersonBadInfo::$reject_code)){
                if(array_key_exists($secondIndex,LoanPersonBadInfo::$reject_code[$firstIndex]['child'])){
                    $reason_remark=LoanPersonBadInfo::$reject_code[$firstIndex]['child'][$secondIndex]['frontedn_name'];
                }
            }

            $info->reason_remark = $reason_remark;
            $info->operator_name = 'auto shell';
            $info->auto_risk_check_status = 1;
            $info->is_hit_risk_rule = 1;
            $info->trail_time = time();
            $info->tree = $tree;
            //$info->coupon_id = 0;//优惠券id重置

            //解除用户该订单锁定额度
            $credit->locked_amount = (($credit->locked_amount - $info['money_amount']) >= 0) ? $credit->locked_amount - $info['money_amount'] : 0;

            //资金流水
            $interests = sprintf('%.2f', $info['money_amount'] / 100 * $info['loan_term'] * $credit->pocket_apr / 10000);
            $user_credit_log = new UserCreditLog();
            $user_credit_log->user_id = $info['user_id'];
            $user_credit_log->type = $user_credit_log_type;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $info['money_amount'];
            $user_credit_log->apr = $user_credit_log_apr;
            $user_credit_log->interests = $interests * 100;
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money = $credit->amount;
            $user_credit_log->used_money = $credit->used_amount;
            $user_credit_log->unabled_money = $credit->locked_amount;

            if (!$keep_limit && $creditLines * 100 < $credit->amount) {
                //总额度流水表
                $log_amount = new  UserCreditReviewLog();
                $log_amount->user_id = $info['user_id'];
                $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
                $log_amount->before_number = $credit->amount;
                $log_amount->operate_number = $credit->amount - $creditLines * 100;
                $log_amount->after_number = $creditLines * 100;
                $log_amount->status = UserCreditReviewLog::STATUS_PASS;
                $log_amount->remark = '机审拒绝 390';
                $log_amount->created_at = time();

                if (!$log_amount->save()) {
                    throw new Exception('额度流水保存失败');
                }

                $credit->amount = $creditLines * 100;
            }

            $order->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data' => ['remark' => '机审拒绝']]));

            if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {
                $transaction->commit();
                return true;
            } else {
                throw new Exception('');
            }

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'auto_check');
            return false;
        }
    }


    //转人工
    private function manual($order, $tree) {
        //平台准入
        $log = new UserOrderLoanCheckLog();
        $log->order_id = $order->id;
        $log->before_status = $order->status;
        $log->after_status = $order->status;
        $log->operator_name = "auto shell";
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['03']['backend_name'];
        $log->remark = '';
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = 'A3';
        $log->back_code = '03';
        $log->tree = $tree;
        $log->rule_version = '';
        $log->save();

        $order->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;
        $order->is_hit_risk_rule = 0;
        $order->trail_time = time();
        $order->tree = $tree;
        //$order->coupon_id = 0;//优惠券id重置
        $order->save();
    }

    public function saveRuleResult($result, $tree_name, $order) {
        if (!is_null($result)) {
            $treeTestResult = new TreeTestResult();
            $treeTestResult->tree_name = $tree_name;
            $treeTestResult->result = $result;
            $treeTestResult->order_id = $order->id;
            $treeTestResult->created_at = time();
            $treeTestResult->save();
        }
    }
}
