<?php
namespace console\controllers;

use common\helpers\MessageHelper;
use common\models\AutoDebitLog;
use common\models\CardInfo;
use common\models\FinancialDebitRecord;
use common\models\FinancialLoanRecord;
use common\models\fund\LoanFund;
use common\models\LoanBlackList;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\OrderManualCancelLog;
use common\models\UserCreditLog;
use common\models\UserCreditTotal;
use common\models\UserOrderLoanCheckLog;
use common\models\AlipayRepaymentLog;
use common\models\WeixinRepaymentLog;
use common\models\Setting;
use common\models\UserCreditMoneyLog;
use common\services\MessageService;
use Yii;
use yii\base\Exception;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\helpers\Util;
use common\api\RedisQueue;
use common\services\AppEventService;
use common\models\UserLoanOrderDelay;
use common\models\UserLoanOrderDelayLog;
use common\helpers\Lock;
use common\helpers\CommonHelper;
use common\base\LogChannel;
class YgdCheckController extends BaseController {
    static $phones = [
        NOTICE_MOBILE, // 刘小龙
        NOTICE_MOBILE2, // 李格
    ];

    /**
     * 资产放款自动审核、财务自动审核
     */
    public function actionYgdZcCwCheck($id = null) {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $hour = date('H',time());

        //资产一键审核
        $failed_num = 0;
        $failed_ids = '';
        $min_id = 0;

        $query = UserLoanOrder::find()
            ->where(['status' => UserLoanOrder::STATUS_PENDING_LOAN])
            ->andWhere(['order_type'=>UserLoanOrder::LOAN_TYPE_LQD])
            ->andWhere('card_id > 0 and id >='.$min_id)
            ->andWhere(['fund_id'=> LoanFund::getAllowPayIds()]);
        if(!is_null($id)){
            $query->andWhere(['>=','id',$id]);
        }
        $all =$query->orderBy(['id' => SORT_DESC])->all();

        $white_amount = Setting::getCardWarnQuota(1);
        $golden_amount = Setting::getCardWarnQuota(2);
        foreach ($all as $model) {
            echo "开始处理借款订单，id:{$model['id']}";
          //  $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if (!FinancialLoanRecord::addLock($model['id'])) { //避免重复申请打款
                    CommonHelper::error(\sprintf('[%s][%s] FinancialLoanRecord::addLock failed [id:%s]', __CLASS__, __FUNCTION__,$model['id']));
                    unset($model);
                    continue;
                }

                //删除上一个状态改变的锁
                if (!empty($lock) && !empty($lock_name)) {
                    Lock::del($lock_name);
                }

                //添加状态改变锁 防止操作过程中状态被其他人改变
                $lock_name = UserLoanOrder::getChangeStatusLockName($model['id']);
                if (! ($lock = Lock::get($lock_name, 30)) ) {
                    CommonHelper::error( \sprintf('%s lock failed.', $model['id']), LogChannel::FINANCIAL_PAYMENT);
                    continue;
                }

                $model_id = $model['id'];
                unset($model);
                $model = UserLoanOrder::findOne($model_id);
                $order_id = $model['id'];
                $user_id = $model['user_id'];
                $money_amount = $model['money_amount'];

                //检查是否有取消借款请求
                $order_manual_chancel_log = OrderManualCancelLog::find()->where(['order_id'=>$order_id])->one();
                if( $order_manual_chancel_log && ($order_manual_chancel_log->status == 0) ){
                    $transaction_1 = Yii::$app->db_kdkj->beginTransaction();
                    try{
                        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
                        $creditChannelService = \Yii::$app->creditChannelService;
                        $user_credit_total = $creditChannelService->getCreditTotalByUserAndOrder($user_loan_order['user_id'], $user_loan_order['id']);
                        $user_credit_total->locked_amount = max(0, ($user_credit_total->locked_amount - $user_loan_order['money_amount']));
                        if(!$user_credit_total->save()){
                            throw new Exception("用户额度表保存失败！");
                        }

                        $log = new UserOrderLoanCheckLog();
                        $log->order_id = $order_id;
                        $log->repayment_id = 0;
                        $log->repayment_type = 0;
                        $log->before_status = $user_loan_order['status'];
                        $log->after_status = UserLoanOrder::STATUS_PENDING_CANCEL;
                        $log->operator_name = 'auto shell';
                        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                        $log->remark = '管理员手动取消订单';
                        $log->operation_type = UserOrderLoanCheckLog::LOAN_FK;
                        if(!$log->save()){
                            throw new Exception("审核日志保存失败！");
                        }

                        $user_loan_order->remark = '管理员手动取消订单';
                        $user_loan_order->reason_remark = '管理员手动取消订单';
                        $user_loan_order->status = UserLoanOrder::STATUS_PENDING_CANCEL;
                        $user_loan_order->updated_at = time();
                        if(!$user_loan_order->save()){
                            throw new Exception("借款订单保存失败！");
                        }
                        $order_manual_chancel_log->status = 1;
                        $order_manual_chancel_log->method_handle = __METHOD__;
                        if(!$order_manual_chancel_log->save()){
                            throw new Exception("手动取消日志保存失败！");
                        }
                        $transaction_1->commit();
                        //删除状态锁
                        Lock::del($lock_name);
                        CommonHelper::error(\sprintf('[%s][%s] %s manual chancel success.', __CLASS__, __FUNCTION__, $order_id));

                        unset($model);
                        continue;
                    }catch(\Exception $e){
                        $transaction_1->rollBack();
                        CommonHelper::error(\sprintf('[%s][%s] %s  manual chancel failed.', __CLASS__, __FUNCTION__, $order_id));
                        unset($model);
                        continue;
                    }


                }

                //校验数据
                //1.订单状态为待放款
                if (UserLoanOrder::STATUS_PENDING_LOAN != $model['status']) {
                    CommonHelper::error(\sprintf('[%s][%s] %s status error.', __CLASS__, __FUNCTION__, $order_id));
                    unset($model);
                    continue;
                }

                //2.机审状态字段auto_risk_status为审核通过
                $auto_risk_check_status = $model['auto_risk_check_status'];
                $is_hit_risk_rule = $model['is_hit_risk_rule'];
                if ( (1 != $auto_risk_check_status) || (0 != $is_hit_risk_rule) ) {
                    CommonHelper::error(\sprintf('[%s][%s] %s $auto_risk_check_status || $is_hit_risk_rule error.', __CLASS__, __FUNCTION__, $order_id));
                    unset($model);
                    continue;
                }

                //3.订单流水表，初审记录和复审记录都存在
//                $user_order_loan_check_log_one = UserOrderLoanCheckLog::findOne([
//                    'order_id'=>$order_id,
//                    'before_status'=>UserLoanOrder::STATUS_CHECK,
//                    'after_status'=>UserLoanOrder::STATUS_REPEAT_TRAIL,
//                ]);
//                $user_order_loan_check_log_two = UserOrderLoanCheckLog::findOne([
//                    'order_id'=>$order_id,
//                    'before_status'=>UserLoanOrder::STATUS_REPEAT_TRAIL,
//                    'after_status'=>UserLoanOrder::STATUS_PENDING_LOAN,
//                ]);
//                if ( (false == $user_order_loan_check_log_one) || (false == $user_order_loan_check_log_two) ){
//                    // continue;
//                }

                //4.借款金额=锁定额度，锁定额度+已使用额度 <= 总额度，用户总额度 <= 1500
                //$user_credit_total = UserCreditTotal::findOne(['user_id'=>$user_id]);
                $creditChannelService = \Yii::$app->creditChannelService;
                $user_credit_total = $creditChannelService->getCreditTotalByUserAndOrder($user_id, $model['id']);
                if (false == $user_credit_total) {
                    unset($model);
                    unset($creditChannelService);
                    unset($user_credit_total);
                    continue;
                }

//                $amount = $user_credit_total->amount;
//                $used_amount = $user_credit_total->used_amount;
//                $locked_amount = $user_credit_total->locked_amount;
//                if ($money_amount != $locked_amount) {
                    //continue;
//                }
//                if ($used_amount+$locked_amount > $amount) {
                    //continue;
//                }

                if ($model['card_type'] == \common\models\BaseUserCreditTotalChannel::CARD_TYPE_TWO) {
                    if ($golden_amount < $money_amount) {
                        unset($model);
                        unset($creditChannelService);
                        unset($user_credit_total);
                        continue;
                    }
                }
                else{
                    if($white_amount < $money_amount){
                        unset($model);
                        unset($creditChannelService);
                        unset($user_credit_total);
                        continue;
                    }
                }

                $code[0] = "A1";
                $code[1] = "01";
                $log = new UserOrderLoanCheckLog();
                $log->order_id = $model['id'];
                $log->repayment_id = 0;
                $log->repayment_type = 0;
                $log->before_status = $model['status'];
                $log->after_status = UserLoanOrder::STATUS_PAY;
                $log->operator_name = Util::short(__CLASS__, __FUNCTION__); //auto shell
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->operation_type = UserOrderLoanCheckLog::LOAN_DFK;
                $log->head_code = "A1";
                $log->back_code = "01";

                $card = CardInfo::findOne(['id'=>$model->card_id]);
                $data = [
                    'user_id' => $model['user_id'],
                    'bind_card_id' => $model['card_id'],
                    'business_id' => $model['id'],
                    'type' => $model['order_type'],
                    'payment_type' => FinancialLoanRecord::PAYMENT_TYPE_CMB,
                    'money' => $model['money_amount'],
                    'bank_id' => $card['bank_id'],
                    'bank_name' => $card['bank_name'],
                    'card_no' => $card['card_no'],
                    'counter_fee' => $model['counter_fee'],
                ];
                $financial = Yii::$container->get("financialService")->createFinancialLoanRecord($data);
                $model->status = UserLoanOrder::STATUS_PAY;
                $model->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $model->operator_name = Util::short(__CLASS__, __FUNCTION__); //"auto shell";
                if ($financial['code'] == 0) {
                    if ($model->validate()) {
                        if ($model->save()) {
                            if ($log->save()) {
                                //事件处理队列    放款成功
                                // RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                                //     'event_name' => AppEventService::EVENT_SUCCESS_POCKET,
                                //     'params' => ['user_id' => $model['user_id'], 'order_id' => $model['id']],
                                // ])]);
                                CommonHelper::info("model {$model['id']} finish.");
                                unset($model);
                                unset($creditChannelService);
                                unset($user_credit_total);
                                unset($log);
                            }
                            else {
                                CommonHelper::error("model_log {$model['id']} save failed.");
                            }
                        }
                        else {
                            CommonHelper::error("model {$model['id']} update failed.");
                        }
                    }
                    else {
                        CommonHelper::error("model {$model['id']} invalid.");
                    }
                }
                else {
                    CommonHelper::error("model {$model['id']} financial_service error.");
                }
            }
            catch (\Exception $e) {
               // $transaction->rollback();
                \Yii::error($e, LogChannel::FINANCIAL_PAYMENT);

                $failed_num ++;
                $failed_ids = $failed_ids.$model['id'].',';
            }


        }

        //删除上一个MODEL的锁
        if (!empty($lock) && !empty($lock_name)) {
            Lock::del($lock_name);
        }

        if ($failed_ids && ($hour >= 8 && $hour <= 20)){
            foreach(self::$phones as $phone){
                $message="订单ID:".$failed_ids."资产放款订单审核失败,请关注";
                MessageHelper::sendInternalSms($phone,$message);
            }
        }

        //财务审核
        $condition = " 1=1 AND review_result = 0 and payment_type=".FinancialLoanRecord::PAYMENT_TYPE_CMB.' and money<='.$golden_amount;
        $financial_loan_record = FinancialLoanRecord::find()->where(['in', 'type', FinancialLoanRecord::$kd_platform_type])->andwhere($condition)->select(['id'])->asArray()->all();
        $id_array = [];
        if($financial_loan_record){
            foreach($financial_loan_record as $item){
                $id_array[] = $item['id'];
            }
        }
        $err_num = 0;
        $failed_ids = '';
        if($id_array){
            foreach($id_array as $v){
                //审核通过之前根据业务类型先对账
                $withdraw = FinancialLoanRecord::find()->where(['id' => $v])->one();
                if ($withdraw->review_result != 0 || $withdraw->status != 1) {
                    $false_arr[] = $v."状态不在未审核提现中";
                    continue;
                }
                try {
                    Yii::$container->get("financialService")->withdrawCheckLoanOrder($withdraw);
                } catch (Exception $e) {
                    Yii::error('ygdcheckController1 >>'.$e->getMessage(),'temp_debit_log');
                    $err_num++;
                    $failed_ids = $failed_ids.$v.',';
                    continue;
                }
                try {
                    Yii::$container->get("financialService")->newWithdrawApprove($v, $withdraw->payment_type, "直连打款批量审核通过", "auto shell");
                } catch (Exception $e) {
                    $err_num++;
                    $failed_ids = $failed_ids.$v.',';
                    Yii::error('ygdcheckController>>'.$e->getMessage(),'temp_debit_log');
                    continue;
                }
            }
        }

        if ($failed_ids && ($hour >= 8 && $hour <= 20)){
            foreach(self::$phones as $phone){
                $message="订单ID:".$failed_ids."财务审核订单审核失败,请关注";
                MessageHelper::sendInternalSms($phone,$message);
            }
        }
    }

    private static function actionCheckLqb($order_id,$force=false){
        //检查是否是江浙沪的，如果不是，加入黑名单

        $information = Yii::$container->get("loanPersonInfoService")->getPocketInfo($order_id);

        $info = $information['info'];
        $user_id = $info['user_id'];
        if(!$force){
            $loan_person = LoanPerson::findOne(['id'=>$user_id,'source_id'=>LoanPerson::PERSON_SOURCE_YGB]);
            if($loan_person){

                    $id_number = $loan_person->id_number;
                    $id_number = substr($id_number,0,3);
                    if(("320" == $id_number)||("330" == $id_number)||("321" == $id_number)||("310" == $id_number)){
                        return true;
                    }else{
                        //加入黑名单
                        $loan_black_list = LoanBlackList::findOne(['user_id'=>$user_id]);
                        if(false === $loan_black_list){
                            return true;
                        }
                        if(empty($loan_black_list)){
                            $loan_black_list = new LoanBlackList();
                            $loan_black_list->user_id = $user_id;
                            $loan_black_list->created_at = time();

                        }
                        $loan_black_list->black_status = LoanBlackList::STATUS_YES;
                        $loan_black_list->black_remark= "后台脚本自动添加";
                        $loan_black_list->black_admin_user = "atuo shell";
                        $loan_black_list->black_count = $loan_black_list->black_count+1;
                        $loan_black_list->save();
                    }

            }else{
                return true;
            }
        }

        $log = new UserOrderLoanCheckLog();

        $credit = $information['credit'];
        $log->order_id = $order_id;
        $log->before_status = $info->status;
        $log->after_status = UserLoanOrder::STATUS_CANCEL;
        $log->operator_name = "auto shell";
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['backend_name'];
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = 'D1';
        $log->back_code = '01';

        $info->status = UserLoanOrder::STATUS_CANCEL;
        $info->reason_remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['frontedn_name'];
        $info->operator_name = 'auto shell';
        //解除用户该订单锁定额度
        $credit->locked_amount -= $info['money_amount'];
        //资金流水
        $interests = sprintf('%.2f', $info['money_amount'] / 100 * $info['loan_term'] * $credit->pocket_apr / 10000);
        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $info['user_id'];
        $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $info['money_amount'];
        $user_credit_log->apr = $credit->pocket_apr;
        $user_credit_log->interests = $interests * 100;
        $user_credit_log->to_card = "";
        $user_credit_log->remark = "";
        $user_credit_log->created_at = time();
        $user_credit_log->created_ip = "";
        $user_credit_log->total_money=$credit->amount;
        $user_credit_log->used_money=$credit->used_amount;
        $user_credit_log->unabled_money=$credit->locked_amount;

        try {
            if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {

                //    $message_service = new MessageService();
                //    $ret = $message_service->sendMessageLoanYgbReject($info['user_id'],$order_id);
                }
            } else {
                throw new Exception;
            }
        } catch (\Exception $e) {
        }

        return true;



    }

    private static function actionCheckFzd($order_id,$force=false){

        $information = Yii::$container->get("loanPersonInfoService")->getHouseRentInfo($order_id);
        $info = $information['info'];
        $user_id = $info['user_id'];
        $loan_person = LoanPerson::findOne(['id'=>$user_id,'source_id'=>LoanPerson::PERSON_SOURCE_YGB]);
        if(!$force){
            if($loan_person){

                    $id_number = $loan_person->id_number;
                    $id_number = substr($id_number,0,3);
                    if(("320" == $id_number)||("330" == $id_number)||("321" == $id_number)||("310" == $id_number)){
                        return true;
                    }else{
                        //加入黑名单
                        $loan_black_list = LoanBlackList::findOne(['user_id'=>$user_id]);
                        if(false === $loan_black_list){
                            return true;
                        }
                        if(empty($loan_black_list)){
                            $loan_black_list = new LoanBlackList();
                            $loan_black_list->user_id = $user_id;
                            $loan_black_list->created_at = time();

                        }
                        $loan_black_list->black_status = LoanBlackList::STATUS_YES;
                        $loan_black_list->black_remark= "后台脚本自动添加";
                        $loan_black_list->black_admin_user = "atuo shell";
                        $loan_black_list->black_count = $loan_black_list->black_count+1;
                        $loan_black_list->save();
                    }


            }else{
                return true;
            }
        }

        $log = new UserOrderLoanCheckLog();

        $credit = $information['credit'];
        $log->order_id = $order_id;
        $log->before_status = $info->status;
        $log->after_status = UserLoanOrder::STATUS_CANCEL;
        $log->operator_name = "auto shell";
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['backend_name'];
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = 'D1';
        $log->back_code = '01';

        $info->status = UserLoanOrder::STATUS_CANCEL;
        $info->reason_remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['frontedn_name'];
        $info->operator_name = 'auto shell';

        //解除用户该订单锁定额度
        $credit->locked_amount -= $info['money_amount'];
        //资金流水
        $interests = sprintf('%.2f', $info['money_amount'] / 100 * $info['loan_term'] * $credit->house_apr / 10000);
        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $info['user_id'];
        $user_credit_log->type = UserCreditLog::TRADE_TYPE_FZD_CS_CANCEL;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $info['money_amount'];
        $user_credit_log->apr = $credit->house_apr;
        $user_credit_log->interests = $interests * 100;
        $user_credit_log->to_card = "";
        $user_credit_log->remark = "";
        $user_credit_log->created_at = time();
        $user_credit_log->created_ip = "";
        $user_credit_log->total_money=$credit->amount;
        $user_credit_log->used_money=$credit->used_amount;
        $user_credit_log->unabled_money=$credit->locked_amount;

        try {
            if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {
                   // $message_service = new MessageService();
                   // $ret = $message_service->sendMessageLoanYgbReject($info['user_id'],$order_id);
                }
            } else {
                throw new Exception;
            }
        } catch (\Exception $e) {
        }

        return true;



    }

    //查询待审核的订单，判断非江浙沪的直接拒绝并且加入黑名单
    public static function actionCheckOrder(){

        $user_loan_order  = UserLoanOrder::find()->where(" status =".UserLoanOrder::STATUS_CHECK)->select(['id','user_id','order_type'])->asArray()->all();
        foreach($user_loan_order as $item){
            $order_type = $item['order_type'];
            switch($order_type){
                case UserLoanOrder::LOAN_TYPE_LQD:
                    echo "零钱贷：{$item['id']}";
                    self::actionCheckLqb($item['id']);
                    break;
                case UserLoanOrder::LOAN_TYPR_FZD:
                    echo "房租贷：{$item['id']}";
                    self::actionCheckFzd($item['id']);
                    break;
                case UserLoanOrder::LOAN_TYPE_FQSC:
                    echo "分期购：{$item['id']}";
                    self::actionCheckFqsc($item['id']);
                    break;
                default:
                    break;
            }
        }



    }

    private static function actionCheckFqsc($order_id,$force=false){
        try{
            $information = Yii::$container->get("loanPersonInfoService")->getInstallmentShopInfo($order_id);
            $info = $information['info'];
            $user_id = $info['user_id'];
            if(!$force){
                $loan_person = LoanPerson::findOne(['id'=>$user_id,'source_id'=>LoanPerson::PERSON_SOURCE_YGB]);
                if($loan_person){

                        $id_number = $loan_person->id_number;
                        $id_number = substr($id_number,0,3);
                        if(("320" == $id_number)||("330" == $id_number)||("321" == $id_number)||("310" == $id_number)){
                            return true;
                        }else{
                            //加入黑名单
                            $loan_black_list = LoanBlackList::findOne(['user_id'=>$user_id]);
                            if(false === $loan_black_list){
                                return true;
                            }
                            if(empty($loan_black_list)){
                                $loan_black_list = new LoanBlackList();
                                $loan_black_list->user_id = $user_id;
                                $loan_black_list->created_at = time();

                            }
                            $loan_black_list->black_status = LoanBlackList::STATUS_YES;
                            $loan_black_list->black_remark= "后台脚本自动添加";
                            $loan_black_list->black_admin_user = "atuo shell";
                            $loan_black_list->black_count = $loan_black_list->black_count+1;
                            $loan_black_list->save();
                        }

                }else{
                    return true;
                }
            }

            $log = new UserOrderLoanCheckLog();

            $credit = $information['credit'];
            $log->order_id = $order_id;
            $log->before_status = $info->status;
            $log->after_status = UserLoanOrder::STATUS_CANCEL;
            $log->operator_name = "auto shell";
            $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
            $log->remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['backend_name'];
            $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
            $log->head_code = 'D1';
            $log->back_code = '01';

            $info->status = UserLoanOrder::STATUS_CANCEL;
            $info->reason_remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['frontedn_name'];
            $info->operator_name = 'auto shell';


            //解除用户该订单锁定额度
            $credit->locked_amount -= $info['money_amount'];
            $interests = sprintf('%.2f', $info['money_amount'] / 100 * $info['loan_term'] * $credit->installment_apr / 10000);
            $user_credit_log = new UserCreditLog();
            $user_credit_log->user_id = $info['user_id'];
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_FZD_CS_CANCEL;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $info['money_amount'];
            $user_credit_log->apr = $credit->installment_apr;
            $user_credit_log->interests = $interests * 100;
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money=$credit->amount;
            $user_credit_log->used_money=$credit->used_amount;
            $user_credit_log->unabled_money=$credit->locked_amount;

            try {
                if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                    if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {
                        // $message_service = new MessageService();
                        // $ret = $message_service->sendMessageLoanYgbReject($info['user_id'],$order_id);
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
            }

            return true;

        }catch(\Exception $e){
            echo $e->getMessage();
        }

    }


    public function actionEveryDayOrderReject(){
        $time = strtotime('today')+(3600*8);
        echo date('Y-m-d H:i:s',$time)."\n";
        $user_loan_order  = UserLoanOrder::find()->where(" status =".UserLoanOrder::STATUS_CHECK)->andWhere(['<','created_at',$time])->select(['id','user_id','order_type'])->asArray()->all();
        foreach($user_loan_order as $item){
            $order_type = $item['order_type'];
            switch($order_type){
                case UserLoanOrder::LOAN_TYPE_LQD:
                    echo "零钱贷：{$item['id']}";
                    $this->actionCheckLqb($item['id'],true);
                    break;
                case UserLoanOrder::LOAN_TYPR_FZD:
                    echo "房租贷：{$item['id']}";
                    $this->actionCheckFzd($item['id'],true);
                    break;
                case UserLoanOrder::LOAN_TYPE_FQSC:
                    echo "分期购：{$item['id']}";
                    $this->actionCheckFqsc($item['id'],true);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 定时更新支付宝还款
     */
    public function actionAliPayRepayment($start_id = null){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $id = 0;
        $type = 0;
        $limit = 1000;
        $error_count = 0;
        $db = AlipayRepaymentLog::getDb();
        $service = Yii::$container->get('orderService');
        if(is_null($start_id)){
            $sql = 'select * from '.AlipayRepaymentLog::tableName().' where id>:id and 
                status in ('.AlipayRepaymentLog::STATUS_ING.','.AlipayRepaymentLog::STATUS_WAIT.') order by id asc limit '.$limit;
        }else{
            $sql = 'select * from '.AlipayRepaymentLog::tableName().' where id>:id and 
                id = '.$start_id .' and status in ('.AlipayRepaymentLog::STATUS_ING.','.AlipayRepaymentLog::STATUS_WAIT.') order by id asc limit '.$limit;
        }

        $logs = $db->createCommand($sql,[':id'=>$id])->queryAll();
        while($logs){
            foreach($logs as $log){
                $status = false;
                try {
                    $user_id = $this->_getUserIdByAliPayInfo($log);
                    if(!$user_id || !$log['money']){
                        echo 'alipay_id:'.$log['id'] . "用户获取失败\n";
                        $type = AlipayRepaymentLog::TYPE_2;
                        $status = false;
                        throw new \Exception('');
                    }
                    echo 'alipay_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "\n";
                    $repayment = $this->_getUnRepaymentLog($user_id, $log['money'],strtotime($log['alipay_date']));
                    if(!$repayment || $repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                        $type = AlipayRepaymentLog::TYPE_3;
                        throw new \Exception('');
                    }

                    //是否续借
                    if($log['is_extend']==1){
                        //修改借款订单标记未续借订单
                        $payment_order_id=$repayment['order_id'];
                        $user_loan_order=UserLoanOrder::findOne($payment_order_id);
                        if(!$user_loan_order){
                            echo 'alipay_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "，未找到借款订单\n";
                            $type = AlipayRepaymentLog::TYPE_3;
                            throw new \Exception('');
                        }else{
                            $user_loan_order->is_extend_loan=1;
                            if(!$user_loan_order->save()){
                                echo 'alipay_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "，order_id：{$payment_order_id}，修改订单未展期失败\n";
                                $type = AlipayRepaymentLog::TYPE_3;
                                throw new \Exception('');
                            }
                        }
                    }

                    echo  'alipay_id:'.$log['id'] . ' get repayment_id:'.$repayment->id . "\n";
                    $delay = null;
                    $get_lock = AlipayRepaymentLog::updateAll(['status'=>AlipayRepaymentLog::STATUS_LOCK,'updated_at'=>time(),'operator_user'=>'auto shell'],['id'=>$log['id'],'status'=>[AlipayRepaymentLog::STATUS_ING,AlipayRepaymentLog::STATUS_WAIT]]);
                    if(!$get_lock){
                        $type = AlipayRepaymentLog::TYPE_4;
                        echo  'alipay_id:'.$log['id'] . ' get alipay_repayment_log save failed:'. "\n";
                        throw new \Exception('');
                    }
                    $back_result = null;
                    if($delay){ // 续期
                        if($delay->save()){
                            $service = \Yii::$container->get('orderService');
                            $remark = ['remark'=>$log['remark'],'operator_name'=>'auto shell'];
                            $back_result = $service->delayLqb($delay->id,$repayment,json_encode($remark));
                        }
                    }else{ // 还款
                        $params = [
                                'operationType' => UserOrderLoanCheckLog::REPAY_XXKK,
                                'repayment_type' => UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS,
                                'debit_channel' =>UserCreditMoneyLog::Platformzfbsapy,
                                'pay_order_id' => $log['alipay_order_id'],
                        ];
                        //==============
                        if(!FinancialDebitRecord::addDebitLock($repayment['order_id'])){
                            $status = true;
                            $type = AlipayRepaymentLog::TYPE_5;
                            echo  'alipay_id:'.$log['id'] . ' get 扣款锁冲突'."\n";
                            throw new \Exception('');
                        }
                        //添加还款日志列表判断
                        $autoDebitLog = AutoDebitLog::find()->where(['order_id'=>$repayment['order_id']])->andWhere(['status'=>[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT]])->orderBy('id desc')->one();
                        if($autoDebitLog && ($autoDebitLog->status == AutoDebitLog::STATUS_DEFAULT || $autoDebitLog->status == AutoDebitLog::STATUS_WAIT)){
                            $status = true;
                            $type = AlipayRepaymentLog::TYPE_9;
                            echo  'alipay_id:'.$log['id'] . ' 扣款进行中 auto_debit_log_id:'.$autoDebitLog ->id."\n";
                            throw new \Exception('');
                        }
                        $FinancialDebitRecord = FinancialDebitRecord::find()->where(['loan_record_id'=>$repayment['order_id']])->andWhere(['status' => FinancialDebitRecord::STATUS_RECALL])->one();
                        if($FinancialDebitRecord){
                            $status = true;
                            $type = AlipayRepaymentLog::TYPE_6;
                            echo  'alipay_id:'.$log['id'] . ' get 代扣冲突，代扣订单ID: '.$FinancialDebitRecord->id."\n";
                            throw new \Exception('');
                        }

                        //处理支付流水号重复情况
                        $havedUserCreditMoneyLog = UserCreditMoneyLog::find()->where(['pay_order_id'=>$log['alipay_order_id']])->one();
                        if($havedUserCreditMoneyLog){
                            $status = true;
                            $type = AlipayRepaymentLog::TYPE_8;
                            echo  'alipay_id:'.$log['id'] . ' get 支付流水重复 '."\n";
                            throw new \Exception('');
                        }

                        $UserCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$repayment['user_id'],'order_id'=>$repayment['order_id']])->orderBy('id desc')->one();
                        if($UserCreditMoneyLog && ($UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_ING || $UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_NORMAL)){
                            $status = true;
                            $type = AlipayRepaymentLog::TYPE_7;
                            echo  'alipay_id:'.$log['id'] . ' 主动还款冲突 money_log_id:'.$UserCreditMoneyLog->id."\n";
                            throw new \Exception('');
                        }
                        //$back_result = $service->callbackDebitMoney($repayment['order_id'], $repayment['id'], $repayment['debit_times'],$log['money'], $log['remark'], 'auto shell',$params);
                        $back_result = $service->optimizedCallbackDebitMoney($repayment['order_id'], $repayment['id'],$log['money'], $log['remark'], 'auto shell',$params);
                    }
                    if($back_result && $back_result['code'] == 0){
                        echo  'alipay_id:'.$log['id'] . '处理成功 ' . "\n";
                        if($FinancialDebitRecord && $FinancialDebitRecord->status != FinancialDebitRecord::STATUS_RECALL){
                            $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_REFUSE;
                            $FinancialDebitRecord->updated_at = time();
                            $FinancialDebitRecord->true_repayment_time = time();
                            $FinancialDebitRecord->save();
                        }
                        FinancialDebitRecord::clearDebitLock($repayment['order_id']);
                        AlipayRepaymentLog::updateAll(['status'=>AlipayRepaymentLog::STATUS_FINISH,'updated_at'=>time(),'type'=>AlipayRepaymentLog::TYPE_1,'operator_user'=>'auto shell'],['id'=>$log['id'],'status'=>AlipayRepaymentLog::STATUS_LOCK]);

                        //用户借款展期还款，2018-08-10
                        $loan_service = Yii::$container->get('loanService');
                        @$loan_service->extendApplyLoan($repayment['order_id'],$repayment['user_id']);

                        continue;
                    }
                } catch (\Exception $e) {
                    if(isset($repayment)){
                        FinancialDebitRecord::clearDebitLock($repayment['order_id']);
                    }
                    if($e->getMessage()){
                        CommonHelper::info('YgdcheckcontrollerAliPayRepayment'.$e->getMessage());
                    }
                }
                if($status){
                    AlipayRepaymentLog::updateAll(['status'=>AlipayRepaymentLog::STATUS_WAIT,'type'=>$type,'updated_at'=>time(),'operator_user'=>'auto shell'],'id='.$log['id'].' and status<>'.AlipayRepaymentLog::STATUS_FINISH);
                }else{
                    $error_count++;
                    AlipayRepaymentLog::updateAll(['status'=>AlipayRepaymentLog::STATUS_FAILED,'type'=>$type,'updated_at'=>time(),'operator_user'=>'auto shell'],'id='.$log['id'].' and status<>'.AlipayRepaymentLog::STATUS_FINISH);
                }

            }
            $id = isset($log) ? $log['id'] : $id;
            $logs = $db->createCommand($sql,[':id'=>$id])->queryAll();
        }
        $hour = date('H');
        if($error_count > 0 && YII_ENV_PROD && $hour>=9 && $hour <= 20){
            MessageHelper::sendInternalSms(NOTICE_MOBILE, '主人，有'.$error_count.'条支付宝还款记录需要人工处理！');   //王成
        }
    }

    /**
     * 定时更新微信还款
     */
    public function actionWeiXinRepayment($start_id = null){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $id = 0;
        $type = 0;
        $limit = 1000;
        $error_count = 0;
        $db = WeixinRepaymentLog::getDb();
        $service = Yii::$container->get('orderService');
        if(is_null($start_id)){
            $sql = 'select * from '.WeixinRepaymentLog::tableName().' where id>:id and 
                status in ('.WeixinRepaymentLog::STATUS_ING.','.WeixinRepaymentLog::STATUS_WAIT.') order by id asc limit '.$limit;
        }else{
            $sql = 'select * from '.WeixinRepaymentLog::tableName().' where id>:id and 
                id = '.$start_id .' and status in ('.WeixinRepaymentLog::STATUS_ING.','.WeixinRepaymentLog::STATUS_WAIT.') order by id asc limit '.$limit;
        }

        $logs = $db->createCommand($sql,[':id'=>$id])->queryAll();
        while($logs){
            foreach($logs as $log){
                $status = false;
                try {
                    $user_id = $this->_getUserIdByAliPayInfo($log,true);//微信方式,第二个参数为true区别
                    if(!$user_id || !$log['money']){
                        echo 'weixin_id:'.$log['id'] . "用户获取失败\n";
                        $type = WeixinRepaymentLog::TYPE_2;
                        $status = false;
                        throw new \Exception('');
                    }
                    echo 'weixin_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "\n";
                    $repayment = $this->_getUnRepaymentLog($user_id, $log['money'],strtotime($log['pay_date']));
                    if(!$repayment || $repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                        echo 'status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE.' and created_at < '. strtotime($log['pay_date']);
                        echo 'weixin_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "，未找到还款记录\n";
                        $type = WeixinRepaymentLog::TYPE_3;
                        throw new \Exception('');
                    }

                    //是否续借
                    if($log['is_extend']==1){
                        //修改借款订单标记未续借订单
                        $payment_order_id=$repayment['order_id'];
                        $user_loan_order=UserLoanOrder::findOne($payment_order_id);
                        if(!$user_loan_order){
                            echo 'weixin_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "，未找到借款订单\n";
                            $type = WeixinRepaymentLog::TYPE_3;
                            throw new \Exception('');
                        }else{
                            $user_loan_order->is_extend_loan=1;
                            if(!$user_loan_order->save()){
                                echo 'weixin_id:'.$log['id'] . ' 用户获取成功 user_id:'.$user_id . "，order_id：{$payment_order_id}，修改订单未展期失败\n";
                                $type = WeixinRepaymentLog::TYPE_3;
                                throw new \Exception('');
                            }
                        }
                    }

                    echo  'weixin_id:'.$log['id'] . ' get repayment_id:'.$repayment->id . "\n";
                    $delay = null;
                    $get_lock = WeixinRepaymentLog::updateAll(['status'=>WeixinRepaymentLog::STATUS_LOCK,'updated_at'=>time(),'operator_user'=>'auto shell'],['id'=>$log['id'],'status'=>[WeixinRepaymentLog::STATUS_ING,WeixinRepaymentLog::STATUS_WAIT]]);
                    if(!$get_lock){
                        $type = WeixinRepaymentLog::TYPE_4;
                        echo  'weixin_id:'.$log['id'] . ' get weixin_repayment_log save failed:'. "\n";
                        throw new \Exception('');
                    }
                    $back_result = null;
                    if($delay){ // 续期
                        if($delay->save()){
                            $service = \Yii::$container->get('orderService');
                            $remark = ['remark'=>$log['remark'],'operator_name'=>'auto shell'];
                            $back_result = $service->delayLqb($delay->id,$repayment,json_encode($remark));
                        }
                    }else{ // 还款
                        $params = [
                            'operationType' => UserOrderLoanCheckLog::REPAY_XXKK,
                            'repayment_type' => UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_WEIXIN_TRANS,
                            'debit_channel' =>UserCreditMoneyLog::Platformwxsapy,
                            'pay_order_id' => $log['weixin_order_id'],
                        ];
                        //==============
                        if(!FinancialDebitRecord::addDebitLock($repayment['order_id'])){
                            $status = true;
                            $type = WeixinRepaymentLog::TYPE_5;
                            echo  'weixin_id:'.$log['id'] . ' get 扣款锁冲突'."\n";
                            throw new \Exception('');
                        }
                        //添加还款日志列表判断
                        $autoDebitLog = AutoDebitLog::find()->where(['order_id'=>$repayment['order_id']])->andWhere(['status'=>[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT]])->orderBy('id desc')->one();
                        if($autoDebitLog && ($autoDebitLog->status == AutoDebitLog::STATUS_DEFAULT || $autoDebitLog->status == AutoDebitLog::STATUS_WAIT)){
                            $status = true;
                            $type = WeixinRepaymentLog::TYPE_9;
                            echo  'weixin_id:'.$log['id'] . ' 扣款进行中 auto_debit_log_id:'.$autoDebitLog ->id."\n";
                            throw new \Exception('');
                        }
                        $FinancialDebitRecord = FinancialDebitRecord::find()->where(['loan_record_id'=>$repayment['order_id']])->andWhere(['status' => FinancialDebitRecord::STATUS_RECALL])->one();
                        if($FinancialDebitRecord){
                            $status = true;
                            $type = WeixinRepaymentLog::TYPE_6;
                            echo  'weixin_id:'.$log['id'] . ' get 代扣冲突，代扣订单ID: '.$FinancialDebitRecord->id."\n";
                            throw new \Exception('');
                        }

                        //处理交易单号重复情况
                        $havedUserCreditMoneyLog = UserCreditMoneyLog::find()->where(['pay_order_id'=>$log['weixin_order_id']])->one();
                        if($havedUserCreditMoneyLog){
                            $status = true;
                            $type = WeixinRepaymentLog::TYPE_8;
                            echo  'weixin_id:'.$log['id'] . ' get 支付流水重复 '."\n";
                            throw new \Exception('');
                        }

                        $UserCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$repayment['user_id'],'order_id'=>$repayment['order_id']])->orderBy('id desc')->one();
                        if($UserCreditMoneyLog && ($UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_ING || $UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_NORMAL)){
                            $status = true;
                            $type = WeixinRepaymentLog::TYPE_7;
                            echo  'weixin_id:'.$log['id'] . ' 主动还款冲突 money_log_id:'.$UserCreditMoneyLog->id."\n";
                            throw new \Exception('');
                        }
                        //$back_result = $service->callbackDebitMoney($repayment['order_id'], $repayment['id'], $repayment['debit_times'],$log['money'], $log['remark'], 'auto shell',$params);
                        $back_result = $service->optimizedCallbackDebitMoney($repayment['order_id'], $repayment['id'],$log['money'], $log['remark'], 'auto shell',$params);
                    }
                    if($back_result && $back_result['code'] == 0){
                        echo  'weixin_id:'.$log['id'] . '处理成功 ' . "\n";
                        if($FinancialDebitRecord && $FinancialDebitRecord->status != FinancialDebitRecord::STATUS_RECALL){
                            $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_REFUSE;
                            $FinancialDebitRecord->updated_at = time();
                            $FinancialDebitRecord->true_repayment_time = time();
                            $FinancialDebitRecord->save();
                        }
                        FinancialDebitRecord::clearDebitLock($repayment['order_id']);
                        WeixinRepaymentLog::updateAll(['status'=>WeixinRepaymentLog::STATUS_FINISH,'updated_at'=>time(),'type'=>WeixinRepaymentLog::TYPE_1,'operator_user'=>'auto shell'],['id'=>$log['id'],'status'=>WeixinRepaymentLog::STATUS_LOCK]);

                        //用户借款展期还款，2018-08-10
                        $loan_service = Yii::$container->get('loanService');
                        @$loan_service->extendApplyLoan($repayment['order_id'],$repayment['user_id']);

                        continue;
                    }
                } catch (\Exception $e) {
                    if(isset($repayment)){
                        FinancialDebitRecord::clearDebitLock($repayment['order_id']);
                    }
                    if($e->getMessage()){
                        CommonHelper::info('YgdcheckcontrollerWeixinRepayment'.$e->getMessage());
                    }
                }
                if($status){
                    WeixinRepaymentLog::updateAll(['status'=>WeixinRepaymentLog::STATUS_WAIT,'type'=>$type,'updated_at'=>time(),'operator_user'=>'auto shell'],'id='.$log['id'].' and status<>'.WeixinRepaymentLog::STATUS_FINISH);
                }else{
                    $error_count++;
                    WeixinRepaymentLog::updateAll(['status'=>WeixinRepaymentLog::STATUS_FAILED,'type'=>$type,'updated_at'=>time(),'operator_user'=>'auto shell'],'id='.$log['id'].' and status<>'.WeixinRepaymentLog::STATUS_FINISH);
                }

            }
            $id = isset($log) ? $log['id'] : $id;
            $logs = $db->createCommand($sql,[':id'=>$id])->queryAll();
        }
        $hour = date('H');
        if($error_count > 0 && YII_ENV_PROD && $hour>=9 && $hour <= 20){
            MessageHelper::sendInternalSms(NOTICE_MOBILE, '主人，有'.$error_count.'条微信还款记录需要人工处理！');   //王成
        }
    }

    /**
     * 检测是否还款动作
     * @param unknown $log
     * @param unknown $repayment
     */
    private function _isRepay($log,$repayment){
        $remain = $repayment['principal']+$repayment['interests']+$repayment['late_fee']-$repayment['true_total_money'];
        if($log['money'] >= $remain){
            return true;
        }
        if($log['remark'] && preg_match('/续/u', $log['remark'])){
            return false;
        }
        if($log['remark'] && preg_match('/还/u', $log['remark'])){
            return true;
        }
        if($repayment['is_overdue'] || $log['money'] >= 50000 || ($log['money'] <= 3000 && $log['money'] >= 1000)){
            return true;
        }
        return false;
    }

    private function _isDelay($log,$repay){
        if($log['remark'] && preg_match('/续/u', $log['remark'])){
            $infos = UserLoanOrder::getOrderRepaymentCard($repay['order_id']);
            $repayment = $infos['repayment'];
            $order = $infos['order'];
            $creditChannelService = \Yii::$app->creditChannelService;
            $quota = $creditChannelService->getCreditTotalByUserId($repayment['user_id']);
            $delay_info = UserLoanOrderDelay::findOne(['order_id' => $repayment['order_id']]);
            $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'], $delay_info ? $delay_info['delay_times'] : 0,$order['card_type']);
            // foreach (UserLoanOrderDelay::$delay_days as $idx => $day) {
            foreach (UserLoanOrderDelay::getDalayDays() as $idx => $day) {
                $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'],$delay_info ? $delay_info['delay_times']:0,$order['card_type'],$day);
                $fee = Util::calcLqbLoanInfo($day, $repayment['remain_principal'], $quota->pocket_apr,$order['card_type']);
                if(($service_fee + $fee + $repayment['late_fee']) == $log['money']){
                    $delay = new UserLoanOrderDelayLog();
                    $delay->user_id = $repayment['user_id'];
                    $delay->order_id = $repayment['order_id'];
                    $delay->service_fee = $service_fee;
                    $delay->counter_fee = $fee;
                    $delay->late_fee = $repayment['late_fee'];
                    $delay->delay_day = $day;
                    $delay->principal = $repayment['principal'];
                    return $delay;
                }
            }
        }
        return false;
    }

    private function _getUserIdByAliPayInfo($log, $channel = false){
        $phone = '';
        $name = '';
        if(preg_match('/([\d]{11})/', $log['remark'],$match)){
            $phone = $match[1];
        }

        if($log['remark'] && $phone){
//             $pattern = ['/[\d\s\/\w\.]/','/(还款)|(还贷)|(转账)|(借款)|(极速钱包)|(极速钱包)|(电话)|(姓名)|(转账)|(小钱包)|(手机号)|(手机)|(号码)|(账号)|(注册手机号)/u','/[。、！？：；﹑，＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）]/u'];
        	$pattern = ['/[\d\s\/\w\.]/','/(还款)|(还贷)|(转账)|(借款)|('.APP_NAMES.')|(电话)|(姓名)|(转账)|(小钱包)|(手机号)|(手机)|(号码)|(账号)|(注册手机号)/u','/[。、！？：；﹑，＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）]/u'];
            $name = trim(preg_replace($pattern, '', $log['remark']));
            if($name){
                $users = LoanPerson::findAll(['phone'=>$phone,'name'=>$name,'status'=>LoanPerson::PERSON_STATUS_PASS]);
                if($users && count($users) == 1){
                    return $users[0]->id;
                }elseif(count($users) > 1){
                    foreach($users as $user){
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()
                            ->where(['user_id'=>$user['id']])
                            ->andWhere(['<>','status',UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
                            ->one();
                        if($user_loan_order_repayment){
                            return $user['id'];
                        }
                    }
                }
            }
        }

        $name = $log['alipay_name'];
        $account = $log['alipay_account'];
        if ($channel){
            $name = $log['weixin_name'];
            $account = $log['weixin_account'] ?? '';
        }
        if(!$phone && Util::verifyPhone($account)){
            $phone = $account;
            if($name){
                $users = LoanPerson::findAll(['phone'=>$phone,'name'=>$name]);
                if($users && count($users) == 1){
                    return $users[0]->id;
                }elseif(count($users) > 1){
                    foreach($users as $user){
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()
                            ->where(['user_id'=>$user['id']])
                            ->andWhere(['<>','status',UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
                            ->one();
                        if($user_loan_order_repayment){
                            return $user['id'];
                        }
                    }
                }
            }
        }
        if(preg_match('/([\d]{11})/', $log['remark'],$match)){
            $phone = $match[1];
        }
        if($phone && $name){
            $users = LoanPerson::findAll(['phone'=>$phone,'name'=>$name]);
            if($users && count($users) == 1){
                return $users[0]->id;
            }elseif(count($users) > 1){
                foreach($users as $user){
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()
                        ->where(['user_id'=>$user['id']])
                        ->andWhere(['<>','status',UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
                        ->one();
                    if($user_loan_order_repayment){
                        return $user['id'];
                    }
                }
            }
        }
        return 0;
    }
    private function _getUnRepaymentLog($user_id,$money,$alipay_time){
        //$repayment = UserLoanOrderRepayment::find()->where(['user_id'=>$user_id])->andWhere('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE.' and (principal+interests+late_fee-true_total_money='.$money.')')->orderBy('id asc')->limit(1)->one();
        $repayment = UserLoanOrderRepayment::find()->where(['user_id'=>$user_id])->andWhere('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE.' and created_at < '. $alipay_time)->orderBy('id asc')->limit(1)->one();
        return $repayment;
    }
}
