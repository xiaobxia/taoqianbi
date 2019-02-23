<?php

namespace frontend\controllers;

use common\api\RedisQueue;
use common\api\RedisXLock;
use common\base\LogChannel;
use common\models\AutoDebitLog;
use common\models\LoseDebitOrder;
use common\models\SuspectDebitLostRecord;
use Yii;
use common\models\Order;
use common\models\LoanPerson;
use yii\base\Exception;
use common\models\Setting;
use common\helpers\MessageHelper;
use common\models\UserCreditMoneyLog;
use common\models\FinancialDebitRecord;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;

/**
 * FinancialRecordController
 */
class NotifyController extends BaseController
{
    /**
     * 获取直连提现打款开关
     */
    public function actionGetSendWithdrawConfig(){
        $key = $this->request->get('key');
        $query = Setting::find()->where(['skey' => $key])->one();
        return [
            'code' => 0,
            'value'  => empty($query) ? 0 : $query->svalue,
        ];
    }


    private function getLoanCreatedWhere(){
        return ' and w.created_at >= '.(time()-7*86400);
    }

    private function getFundWhere(){
        $account_id = \common\models\fund\FundAccount::ID_PAY_ACCOUNT_KOUDAI;
        $fund_ids = \common\models\fund\FundAccount::getAllFundIds($account_id, 'pay');
        return ' and o.fund_id in('.implode(',', $fund_ids).')';
    }

    /**
     * @name xybt-获得打款成功时间
     * @date 2017-10-26
     * @author 张玉亮
     * @use 获得打款成功时间
     * @param null
     */
    private function getSuccessTime(){
        $opr_dat = $this->request->post('opr_dat','');
        $time = time();
        if($opr_dat){
            $opr_dat = strtotime($opr_dat);
            if($opr_dat > 0){
                $time = strtotime(date('Y-m-d',$opr_dat).' '.date('H:i:s'));
                if(date('Y-m-d',$time) != date('Y-m-d')){
                    $time = $opr_dat+86400-1;
                }
            }
        }
        return $time;
    }


    /**
     * @name xybt-汇潮支付宝支付回调
     * @date 2017-10-26
     * @author 张玉亮
     * @use 用于汇潮支付宝支付结果通知(回调)
     * @param integer $id 订单ID
     */
    public function actionHcAliypayDebitCallback() {
        try{
            $contentType = $this->request->getContentType();
            if ($contentType == 'application/json') {
                $jsonParam = $this->request->getRawBody();
                $params = json_decode($jsonParam,1);
                $sign = isset($params['sign'])?$params['sign']:'';
            } else {
                $params = $this->request->post();
                $sign = $this->request->post('sign');
            }
            if (YII_ENV_PROD)
            {
                if ((!Order::validateHcSign($params, $sign))) return [ 'code' => "-2",'err_msg' => "Failed To Verify Sign"];
            }
            $order_id = isset($params['merchantOutOrderNo'])?$params['merchantOutOrderNo']:'';
            $key = 'PayDebitCheck'.$order_id;
            if (!RedisXLock::lock($key,10))
            {
                return [ 'code' => '-2','err_msg' => '请求正在处理中.'];
            }
            $modifyParams = [
                'order_id' => $order_id,
                'err_msg' => isset($params['msg'])?$params['msg']:'',
                'state' => $params['payResult'] === 1 ? 2 : 0,
                'third_platform' => UserCreditMoneyLog::Platformhc,
                'pay_order_id' => $params['noncestr'],
            ];
            return $this -> _debitMerge($modifyParams);
        }catch(Exception $e) {
            return [
                'code' => "-1",
                'err_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * @name jshb-扣款回调结果处理
     * @date 2017-10-26
     * @author
     * @use 第三方扣款结果回调相关消息处理
     * 目前暂停使用
     * @param integer $id 订单ID
     */
    private function _debitMerge($params) {
        $result = '';
        if (isset($params['err_msg'])) {
            $result = $params['err_msg'];
        }
        $auto_debit_log  = AutoDebitLog::findOne(['order_uuid'=> $params['order_id']]);
        if (!$auto_debit_log) return [ 'code' => "-1", 'err_msg' => "auto_debit_log 不存在"];
        $userLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id' => $auto_debit_log -> order_id]);
        if (!$userLoanOrderRepayment) return [ 'code' => "-1", 'err_msg' => "UserLoanOrderRepayment 不存在"];
        //初始化参数
        $pay_order = [
            'user_id' => $auto_debit_log->user_id,
            'order_id' => $auto_debit_log->order_id,
            'money' => $params['money'],
            'order_uuid' => $auto_debit_log -> order_uuid,
            'card_id' => $auto_debit_log -> card_id,
        ];
        $operator = 'auto shell	';
        $debit_ret = isset($params['err_msg']) ? ($params['err_msg']) : '';
        $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
        switch ($auto_debit_log->debit_type) {
            case AutoDebitLog::DEBIT_TYPE_COLLECTION:
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_COLLECTION;
                break;
            case AutoDebitLog::DEBIT_TYPE_BACKEND:
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_BACKEND;
                break;
            case AutoDebitLog::DEBIT_TYPE_LITTLE:
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                break;
            case AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT:
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP;
                break;
            case AutoDebitLog::DEBIT_TYPE_ACTIVE_HC:
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_HC;
                break;
            case AutoDebitLog::DEBIT_TYPE_ACTIVE:
                $operator = $auto_debit_log->user_id;
                $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT;
                break;
        }
        if (intval($params['state']) === 2) {//处理扣款成功回调
            try {
                if (!FinancialDebitRecord::addCallBackDebitLock($params['order_id'])) {
                    return [ 'code' => "-1", 'err_msg' => "订单正在被脚本处理"];
                }

                $isRepaymented = false;
                if ($auto_debit_log -> status == AutoDebitLog::STATUS_SUCCESS) {
                    return [ 'code' => "-1", 'err_msg' => "该订单已处理成功!"];
                }
                $currentStatus = $auto_debit_log -> status;
                $order_service = Yii::$container->get('financialCommonService');
                $user_loan_order = UserLoanOrder::findOne(['id' => $auto_debit_log -> order_id]);
                $third_platform = isset($params['third_platform']) ? $params['third_platform'] : $pay_order['platform'];
                $pay_order_id = isset($params['pay_order_id']) ? $params['pay_order_id']:$auto_debit_log -> pay_order_id;
                if ($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE || $user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE ) {
                    $isRepaymented = true;
                    $order_result = [ 'code' => 3, 'message' => '该订单已还款' ];
                } else {
                    $order_result = $order_service->successCallbackDebitOrder($pay_order, '扣款成功', $operator,[
                        'debit_account' => '',
                        'repayment_id' => $userLoanOrderRepayment -> id,
                        'repayment_type' => $repaymentType,
                        'pay_order_id' => $pay_order_id,
                        'third_platform' => $third_platform
                    ]);
                    if($order_result['code'] != 0){
                        return $order_result;
                    }
                }
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try {
                    //第一步 更新扣款日志列表
                    $callback_remark = $params;
                    if (isset($callback_remark['sign'])) unset($callback_remark['sign']);
                    $callback_remark = json_encode($callback_remark,JSON_UNESCAPED_UNICODE);
                    $auto_debit_log -> status = AutoDebitLog::STATUS_SUCCESS;
                    $auto_debit_log -> callback_at = time();
                    $auto_debit_log -> callback_remark = $callback_remark;
                    $auto_debit_log -> platform = isset($params['third_platform']) ? $params['third_platform'] : $auto_debit_log->platform;
                    $auto_debit_log -> pay_order_id = isset($params['pay_order_id']) ? $params['pay_order_id'] : $auto_debit_log->pay_order_id;
                    if (!$auto_debit_log -> save()) {
                        throw new Exception("自动扣款日志列表,更新失败!");
                    }

                    //第二步 如果扣款表中不能找到相关记录则更新
                    $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $params['order_id'], 'user_id' => $pay_order['user_id']]);
                    if ($financialDebitRecord) {
                        $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_SUCCESS;
                        $financialDebitRecord -> pay_result  = json_encode($params,JSON_UNESCAPED_UNICODE);
                        $financialDebitRecord -> true_repayment_money  = $params['money'];
                        $financialDebitRecord -> platform  = isset($params['third_platform']) ? $params['third_platform'] : 0;
                        $financialDebitRecord -> third_platform_order_id  = isset($params['pay_order_id']) ? $params['pay_order_id'] : '';
                        $financialDebitRecord -> true_repayment_time  = time();
                        $financialDebitRecord -> callback_result  = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                        $financialDebitRecord -> updated_at  = time();
                        if (!$financialDebitRecord->save()) {
                            $msg = "直连扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'];
                            MessageHelper::sendSMS(NOTICE_MOBILE2, $msg);
                            throw new Exception("FinancialDebitRecord 记录更新失败!");
                        }
                    }
                    //第三步 如果订单在观察列表中则更新
                    $suspectDebitLostRecord = SuspectDebitLostRecord::findOne([ 'order_uuid' => $params['order_id'], 'user_id' => $auto_debit_log['user_id'],'order_id' => $auto_debit_log['order_id']]);
                    if ($suspectDebitLostRecord) {
                        $suspectDebitLostRecord -> money = $params['money'];
                        $suspectDebitLostRecord -> status = $isRepaymented ? SuspectDebitLostRecord::STATUS_SUCCESS_REPAYMENTED : SuspectDebitLostRecord::STATUS_SUCCESS_UNREPAYMENT;
                        $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_CALLBACK;
                        $suspectDebitLostRecord -> debit_type = (strlen($params['order_id']) > 14)?SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM:SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                        $suspectDebitLostRecord -> platform = isset($params['third_platform']) ? $params['third_platform'] : $auto_debit_log->platform;
                        $suspectDebitLostRecord -> remark .= $debit_ret.'<br/>';
                        $suspectDebitLostRecord -> operator .= 'callback'.'<br/>';
                        $suspectDebitLostRecord -> updated_at = time();
                        if (!$suspectDebitLostRecord -> save()) {
                            throw new Exception("SuspectDebitLostRecord 记录更新失败!");
                        }
                    }
                    //第四步 如果借款订单是已还款状态则将该订单回调记录保存
                    if ($isRepaymented) {
                        $money_log = UserCreditMoneyLog::findOne(['user_id' => $auto_debit_log['user_id'], 'order_uuid' => $auto_debit_log['order_uuid'], 'order_id' => $auto_debit_log['order_id']]);
                        if (is_null($money_log)) {
                            if (isset($params['pay_date'])) {
                                $pay_date = $params['pay_date'] . 235959;
                                $pay_time = min(strtotime($pay_date), time());
                            }else{
                                $pay_time = time();
                            }
                            $money_log = new UserCreditMoneyLog();
                            $money_log->type = 2;
                            $money_log->payment_type = $repaymentType;
                            $money_log->status = UserCreditMoneyLog::STATUS_SUCCESS;
                            $money_log->user_id = $auto_debit_log->user_id;
                            $money_log->order_id = $auto_debit_log->order_id;
                            $money_log->order_uuid = $auto_debit_log->order_uuid;
                            $money_log->operator_money = $auto_debit_log->money;
                            $money_log->operator_name = 'auto shell';
                            $money_log->pay_order_id = $auto_debit_log->pay_order_id;
                            $money_log->success_repayment_time = $pay_time;
                            $money_log->card_id = 'auto shell';
                            $money_log->debit_channel = $auto_debit_log->platform;
                            $money_log->created_at = time();
                            $money_log->updated_at = time();
                            if (!$money_log->save()) {
                                throw new Exception('UserCreditMoneyLog 保存失败');
                            }
                            $loseDebitOrder = LoseDebitOrder::findOne(['user_id' => $auto_debit_log['user_id'],'order_id'=> $auto_debit_log['order_id'],'order_uuid' =>$params['order_id']]);
                            if (!$loseDebitOrder) {
                                $loseDebitOrder = new LoseDebitOrder();
                                $loseDebitOrder->order_id = $auto_debit_log->order_id;
                                $loseDebitOrder->user_id = $auto_debit_log->user_id;
                                $loseDebitOrder->order_uuid = $auto_debit_log->order_uuid;
                                $loseDebitOrder->pay_order_id = $auto_debit_log->pay_order_id;
                                $loseDebitOrder->pre_status = $currentStatus;
                                $loseDebitOrder->status = $auto_debit_log->status;
                                $loseDebitOrder->callback_result = json_encode($callback_remark, JSON_UNESCAPED_UNICODE);
                                $loseDebitOrder->type = (strlen($params['order_id']) > 14) ? LoseDebitOrder::TYPE_DEBIT : LoseDebitOrder::TYPE_PAY;
                                $loseDebitOrder->debit_channel = $auto_debit_log->platform;
                                $loseDebitOrder->remark = date('Ymd') . '订单已还款';
                                $loseDebitOrder->staff_type = LoseDebitOrder::STAFF_TYPE_1;
                                $loseDebitOrder->updated_at = time();
                                $loseDebitOrder->created_at = time();
                                if (!$loseDebitOrder->save()) {
                                    throw new Exception("LoseDebitOrder 记录添加失败!");
                                }
                            } else {
                                $loseDebitOrder -> pre_status =  $loseDebitOrder -> status;
                                $loseDebitOrder -> status =  $auto_debit_log -> status;
                                $loseDebitOrder -> callback_result .= json_encode($callback_remark,JSON_UNESCAPED_UNICODE);
                                $loseDebitOrder -> remark .= date('Ymd').'订单还款成功时回调';
                                $loseDebitOrder -> updated_at = time();
                                if (!$loseDebitOrder -> save()) {
                                    throw new Exception("LoseDebitOrder 记录修改失败!");
                                }
                            }
                        }
                    }
                    $transaction->commit();
                } catch (Exception $ex) {
                    $transaction->rollback();
                    MessageHelper::sendSMS('18616932561',$ex->getMessage());
                    FinancialDebitRecord::clearCallBackDebitLock($params['order_id']);
                    return [ 'code' => "0", 'err_msg' => $ex->getMessage()];
                }

                if($auto_debit_log->debit_type == AutoDebitLog::DEBIT_TYPE_ACTIVE){
                    $key = "user_money_log_status_for_".$auto_debit_log->user_id;;
                    RedisQueue::set(['expire'=>60,'key'=>$key,'value'=>json_encode(['code'=>0,'err_code'=>0])]);
                    //主动还款成功时添加到队列
                    RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                        'code' => 1001,
                        'user_id' => $auto_debit_log->user_id,
                        'order_id' => $auto_debit_log->order_id,
                        'loan_money' => $pay_order['money'],
                        'success' =>[
                            'pay_person' => COMPANY_NAME,
                            'pay_type' => '1'
                        ]
                    ])]);
                }
                FinancialDebitRecord::clearDebitLock($auto_debit_log['order_id']);
                //非主动还款时 生成剩余扣款记录
                if (!in_array($auto_debit_log->debit_type,[AutoDebitLog::DEBIT_TYPE_ACTIVE,AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT,AutoDebitLog::DEBIT_TYPE_MK])) {
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['id'=>$userLoanOrderRepayment['id']])->one();
                    if ($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try{
                            $user_loan_order = UserLoanOrder::find()->where(['id'=>$auto_debit_log['order_id']])->one();
                            $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                            $user_loan_order->operator_name = 'auto shell';
                            $user_loan_order->updated_at = time();
                            if(!$user_loan_order->save()){
                                throw new \Exception('UserLoanOrder保存失败');
                            }
                            $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                            $user_loan_order_repayment->operator_name =  'auto shell';
                            $user_loan_order_repayment->updated_at = time();
                            if(!$user_loan_order_repayment->save()){
                                throw new \Exception('UserLoanOrderRepayment保存失败');
                            }
                            $orders_service = Yii::$container->get('orderService');
                            $result = $orders_service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                            if(!$result){
                                throw new \Exception('生成扣款记录失败');
                            }
                            $transaction->commit();
                        }catch(\Exception $e){
                            $transaction->rollback();
                            Yii::error('生成扣款记录失败：错误信息'.$e->getMessage(),LogChannel::FINANCIAL_DEBIT);
                        }
                    }
                }
                return [ 'code' => "0", 'err_msg' => "success"];
            } catch (Exception $e) {
                $mgs = '扣款回调异常,原因:'.$e->getMessage();
                MessageHelper::sendSMS('18616932561',$mgs);
                return [ 'code' => "-1", 'err_msg' => $e->getMessage()];
            }
        } else {
            $redis_key = 'kd_debit'.$auto_debit_log->id;
            $redis_ret = \Yii::$app->redis->executeCommand('GET', [$redis_key]);
            if($redis_ret == 1){
                \Yii::$app->redis->executeCommand('SET', [$redis_key,2]);
            }
            if($redis_ret == 2){
                UserCreditMoneyLog::setDebitStatusDay($auto_debit_log->user_id,$auto_debit_log->card_id,UserCreditMoneyLog::STATUS_FAILED);
                \Yii::$app->redis->executeCommand('DEL', [$redis_key]);
            }
            if(!$redis_ret){
                \Yii::$app->redis->executeCommand('SET', [$redis_key,1]);
            }
            //如果该订单号已被置为失败 不作处理
            if (!FinancialDebitRecord::addCallBackDebitLock($params['order_id'])) {
                $conflictMsg = '系统代扣 order_uuid:'.$params['order_id'].' 主动回调与查询相冲突!';
                MessageHelper::sendSMS('18616932561', $conflictMsg);
                return [ 'code' => "-1", 'err_msg' => "订单正在被脚本处理"];
            }
            $UserLoanOrder = UserLoanOrder::findOne($auto_debit_log['order_id']);
            $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id'=>$UserLoanOrder['id']]);
            if($UserLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE || $UserLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                $alterStatus = true;
            } else {
                $alterStatus = UserLoanOrderRepayment::alterOrderStatus($UserLoanOrder['id'],$UserLoanOrderRepayment['id']);
            }
            if ($alterStatus) {
                if(preg_match('/余额不足/',$result)){
                    $msg = '您刚才操作的还款，因银行卡余额不足导致还款失败，请保证银行卡余额充足的情况下，再进入APP操作还款。';
                }else{
                    $msg = '您发起的主动还款失败，为避免因逾期而产生滞纳金，请更换银行卡操作还款，或使用支付宝还款。';
                }
                if($auto_debit_log->debit_type == AutoDebitLog::DEBIT_TYPE_ACTIVE){
                    $loanPerson = LoanPerson::findOne(['id'=>$auto_debit_log->user_id]);
                    if (YII_ENV_PROD) {
                        if ($loanPerson) {
//                            MessageHelper::sendSMS($loanPerson->phone , $msg,'smsService_TianChang_HY',$loanPerson->source_id);
                        }
                    } else {
                        MessageHelper::sendSMS(NOTICE_MOBILE, $msg,'smsService_TianChang_HY',$loanPerson->source_id);
                    }
                }//$2y$08$Je.4CZpss4LvakS5HNXc7O5l2O1wop/gNUbS.XJ7kOYkL.dUlfsFi
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try {
                    //第一步 更新日志列表
                    $auto_debit_log -> status = AutoDebitLog::STATUS_FAILED;
                    $auto_debit_log -> callback_at = time();
                    $auto_debit_log -> callback_remark = $debit_ret;
                    $auto_debit_log -> platform = isset($params['third_platform']) ? $params['third_platform'] : $auto_debit_log->platform;
                    $auto_debit_log -> pay_order_id = isset($params['pay_order_id']) ? $params['pay_order_id'] : $auto_debit_log->pay_order_id;
                    $auto_debit_log -> error_code = isset($params['err_code']) ? $params['err_code'] : 0;
                    if (!$auto_debit_log -> save()) throw new Exception('AutoDebitLog 记录更新失败');
                    //第二步 更新suspectDebitLostRecord 记录
                    $suspectDebitLostRecord = SuspectDebitLostRecord::findOne([ 'order_uuid' => $params['order_id'], 'user_id' => $pay_order['user_id'],'order_id' => $pay_order['order_id']]);
                    if ($suspectDebitLostRecord) {
                        $suspectDebitLostRecord -> money = $params['money'];
                        $suspectDebitLostRecord -> status = SuspectDebitLostRecord::STATUS_FAILED_CALLBACK;
                        $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_CALLBACK;
                        $suspectDebitLostRecord -> debit_type = (strlen($params['order_id']) > 14)?SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM:SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                        $suspectDebitLostRecord -> platform = isset($params['third_platform']) ? $params['third_platform'] : $auto_debit_log->platform;
                        $suspectDebitLostRecord -> remark .= $debit_ret.'<br/>';
                        $suspectDebitLostRecord -> operator .= 'callback <br/>';
                        $suspectDebitLostRecord -> updated_at = time();
                        if (!$suspectDebitLostRecord -> save()) {
                            throw new Exception("SuspectDebitLostRecord 记录更新失败!");
                        }
                    }
                    //第三步 更新扣款记录表
                    $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $params['order_id'], 'user_id' => $auto_debit_log['user_id']]);
                    if ($financialDebitRecord) {
                        $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_FALSE;
                        $financialDebitRecord -> pay_result  = json_encode($params,JSON_UNESCAPED_UNICODE);
                        $financialDebitRecord -> true_repayment_money  = $params['money'];
                        $financialDebitRecord -> platform  = isset($params['third_platform']) ? $params['third_platform'] : 0;
                        $financialDebitRecord -> third_platform_order_id  = isset($params['pay_order_id']) ? $params['pay_order_id'] : '';
                        $financialDebitRecord -> true_repayment_time  = time();
                        $financialDebitRecord -> updated_at  = time();
                        if (!$financialDebitRecord->save()) {
                            throw new Exception("FinancialDebitRecord 记录更新失败!");
                        }
                    }
                    //第四步 如果是秒扣回调
                    //第五步 如果是新网秒扣数据,则更新秒扣数据
//                    if ($auto_debit_log->debit_type == AutoDebitLog::DEBIT_TYPE_MK) {
//                        $debitIntimeOrder = IntimeDebitRecord::findOne(['order_id'=>$auto_debit_log->order_id,'user_id' => $auto_debit_log->user_id]);
//                        if ($debitIntimeOrder) {
//                            $debitIntimeOrder -> status = IntimeDebitRecord::STATUS_FAILED;
//                            $debitIntimeOrder -> updated_at = time();
//                            $debitIntimeOrder -> save();
//                        }
//                    }
                    try {
                        $user_loan_order = UserLoanOrder::findOne($pay_order['order_id']);
                        $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_REPAY_FAIL, new \common\base\Event(['custom_data'=>[]]));
                    } catch (Exception $e) {}
                    $transaction -> commit();
                    if($auto_debit_log->debit_type == AutoDebitLog::DEBIT_TYPE_ACTIVE){
                        $key = "user_money_log_status_for_".$auto_debit_log->user_id;
                        RedisQueue::set(['expire'=>60,'key'=>$key,'value'=>json_encode(['code'=>-1,'err_code'=>isset($params['err_code']) ? $params['err_code'] : 0])]);
                        //主动还款失败时添加到队列
                        RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                            'code' => 1002,
                            'user_id' => $auto_debit_log->user_id,
                            'order_id' => $auto_debit_log->order_id,
                            'loan_money' => $pay_order['money'],
                            'error' =>[
                                'error_info' => $msg,
                                'pay_type' => $repaymentType
                            ]
                        ])]);
                    }
                } catch (Exception $ex) {
                    $transaction -> rollback();
                    $errMsg = $ex -> getMessage();
                    FinancialDebitRecord::clearCallBackDebitLock($params['order_id']);
                    return [ 'code' => "0", 'err_msg' => "扣款失败处理失败,原因:{$errMsg}"];
                }
                FinancialDebitRecord::clearDebitLock($pay_order['order_id']);
                return [ 'code' => "0", 'err_msg' => $auto_debit_log->order_id." 扣款失败已处理"];
            }else{
                return [ 'code' => "-1", 'err_msg' => "两表不存在"];
            }
        }
    }

    /**
     * 汇潮支付异步通知
    **/
    public function actionHcAliypayDebitCallbackNew(){
        echo 'success';
    }
}
