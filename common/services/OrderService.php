<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\base\LogChannel;
use common\helpers\MessageHelper;
use common\models\LoanPerson;
use common\models\CardInfo;
use common\models\FinancialDebitRecord;
use common\models\UserCreditLog;
use common\models\UserInterestLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserVerification;
use common\helpers\StringHelper;
use common\models\UserOrderLoanCheckLog;
use common\models\UserContractInfo;
use common\models\UserCreditMoneyLog;
use common\api\RedisQueue;
use common\models\UserCreditData;
use common\models\fund\LoanFund;
use common\models\fund\OrderFundInfo;
use common\models\fund\OrderFundLog;
use common\models\fund\FundAccount;
use common\helpers\Lock;
use common\base\ErrCode;
use common\models\fund\OrderFundRepaymentLog;
use common\models\RidOverdueLog;

class OrderService extends Component
{
    /**
     * 是否首单可用的优惠券模板id
     * @var array
     */
    public static $first_coupon_list = [3];

    private function _withdrawCheckDebitOrderLqd($user_loan_order,$repayment_id){
        $user_loan_order_repayment = UserLoanOrderRepayment::findOne([
            'id'=>$repayment_id,
            'status'=>UserLoanOrderRepayment::STATUS_WAIT,
        ]);
        if (false == $user_loan_order_repayment) {
            return [
                'code'=>-1,
                'message'=>'获取分期总表数据失败',
            ];
        }

        $data = array();
        if($user_loan_order_repayment->current_debit_money > 0){
            $data['plan_repayment_money']=$user_loan_order_repayment->current_debit_money;
        }else{
            $data['plan_repayment_money']=$user_loan_order_repayment->principal+$user_loan_order_repayment->interests+$user_loan_order_repayment->late_fee;
        }
        $data['plan_repayment_time']=$user_loan_order_repayment->plan_repayment_time;
        $data['user_id'] = $user_loan_order_repayment->user_id;
        $card_id = $user_loan_order_repayment->card_id;

        $card_info = CardInfo::findOne(['user_id'=>$data['user_id'],'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-2,
                'message'=>'获取银行卡信息失败',
            ];
        }


        $data['debit_card_id']=$card_info->card_no;

        $data['user_id']=$user_loan_order_repayment->user_id;
        $data['repayment_id']=$user_loan_order_repayment->id;
        if($user_loan_order_repayment->current_debit_money > 0){
            $data['repayment_peroid_id']=$user_loan_order_repayment->debit_times;
        }else{
            $data['repayment_peroid_id']=0;
        }
        $data['type']=FinancialDebitRecord::TYPE_YGB_LQB;
        $data['status']=$user_loan_order_repayment->status;
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];

    }

    private function _withdrawCheckDebitOrderFzd($user_loan_order,$repayment_id,$repayment_period_id){
        $user_repayment = UserRepayment::findOne(['id'=>$repayment_id]);
        if(false == $user_repayment){
            return [
                'code'=>-2,
                'message'=>'获取分期计划总表数据失败',
            ];
        }

        $user_repayment_period = UserRepaymentPeriod::findOne([
            'id'=>$repayment_period_id,
            'status'=>UserRepaymentPeriod::STATUS_LOAN_COMPLING,
        ]);
        if(false == $user_repayment_period){
            return [
                'code'=>-3,
                'message'=>'获取分期计划分期表数据失败',
            ];
        }

        $data = array();
        $data['plan_repayment_money']=$user_repayment_period->plan_repayment_money;
        $data['plan_repayment_time']=$user_repayment_period->plan_repayment_time;

        $card_id = $user_repayment_period->card_id;

        $card_info = CardInfo::findOne(['user_id'=>$user_loan_order['user_id'],'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-2,
                'message'=>'获取银行卡信息失败',
            ];
        }


        $data['debit_card_id']=$card_info->card_no;

        $data['user_id']=$user_repayment_period->user_id;
        $data['repayment_id']=$user_repayment_period->repayment_id;
        $data['repayment_peroid_id']=$user_repayment_period->id;
        $data['type']=FinancialDebitRecord::TYPE_YGB;
        $data['status']=$user_repayment_period->status;

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];
    }

    /**
     * 扣款对账
     * @param $user_loan_order
     */
    public function withdrawCheckDebitOrder($user_loan_order, $repayment_id, $repayment_period_id){
        $user_loan_order = UserLoanOrder::findOne(['id'=>$user_loan_order]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'获取订单数据失败',
            ];
        }

        $order_type = $user_loan_order->order_type;
        switch($order_type){
            case UserLoanOrder::LOAN_TYPE_LQD:
                $ret = self::_withdrawCheckDebitOrderLqd($user_loan_order,$repayment_id);

                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                    'data'=>isset($ret['data'])?$ret['data']:[],
                ];
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $ret = self::_withdrawCheckDebitOrderFzd($user_loan_order,$repayment_id,$repayment_period_id);

                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                    'data'=>isset($ret['data'])?$ret['data']:[],
                ];
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $ret = self::_withdrawCheckDebitOrderFzd($user_loan_order,$repayment_id,$repayment_period_id);

                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                    'data'=>isset($ret['data'])?$ret['data']:[],
                ];
                break;
            default:
                return [
                    'code'=>-1,
                    'message'=>'订单类型错误',
                ];
                break;
        }
    }

    /**
     * 打款对账
     * @param $user_loan_order
     */
    public function withdrawCheckLoanOrder($user_loan_order){

        $user_loan_order = UserLoanOrder::findOne(['id'=>$user_loan_order]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'获取订单数据失败',
            ];
        }
        /*if($user_loan_order->created_at <= strtotime('2017-01-18 02:00:00')){
            return [
                'code'=>-1,
                'message'=>'暂停打款',
            ];
        }*/
        $data = array();
        $data['money']= $user_loan_order->money_amount;
        $data['counter_fee']=$user_loan_order->counter_fee;
        $data['status']= $user_loan_order->status;
        $data['user_id'] = $user_loan_order->user_id;
        $card_id = $user_loan_order->card_id;

        $card_info = CardInfo::findOne(['user_id'=>$data['user_id'],'id'=>$card_id]);
        if(false == $card_info){
            return [
                'code'=>-2,
                'message'=>'获取银行卡信息失败',
            ];
        }
        $data['bank_id']=$card_info->bank_id;
        $data['card_no']=$card_info->card_no;

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];

    }

    /**
     * 零钱贷操作
     * @param $user_loan_order
     * @param integer $loan_time 放款时间 默认为当前时间
     */
    public function operatorLqd($user_loan_order, $loan_time=null){
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if(empty($loan_time)){
                $loan_time = time();
            }
            //更新订单状态为已打款
            $user_loan_order->status = UserLoanOrder::STATUS_LOAN_COMPLETE;
            $user_loan_order->updated_at = time();
            $user_loan_order->operator_name= Yii::$app->has('user') &&  isset(Yii::$app->user->identity->username) ? Yii::$app->user->identity->username : "backsql";
            $user_loan_order->loan_time = $loan_time;
            if($user_loan_order->loan_method == '0'){ //大客户 计算  逾期垫付时间
                if($user_loan_order->loanFund->type == LoanFund::TYPE_BIG_CLIENT) {//处理大客户的额度
                    $days =  $user_loan_order->loan_term+6;
                    $user_loan_order->orderFundInfo->plan_payment_time = strtotime(date("Y-m-d 00:00:00",strtotime("+ ". $days."days")));
                    $user_loan_order->orderFundInfo->updateAttributes(['plan_payment_time']);
                }
            }
            //判断是否是首单 重置为非新手
            $user_verification = UserVerification::findOne(['user_id'=>$user_loan_order->user_id]);
            if($user_verification){
                if(UserVerification::IS_FIRST_LOAN_NEW == $user_verification->is_first_loan){
                    $user_loan_order->is_first = UserLoanOrder::FIRST_LOAN;
                }
                $user_verification->is_first_loan = UserVerification::IS_FIRST_LOAN_NO;
                // $user_verification->operator_name = "auto";
                $user_verification->updated_at = time();
                if(!$user_verification->save()){
                    $transaction->rollBack();
                    return [
                        'code'=>-1,
                        'message'=>'更新是否新手数据失败',
                    ];
                }
            }else{
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取认证表数据失败',
                ];
            }
            if(!$user_loan_order->update()){
                return [
                    'code'=>-1,
                    'message'=>'订单更新失败',
                ];
            }
            //零钱贷插入分期总表
            $user_loan_order_repayment = new UserLoanOrderRepayment();
            if(!$user_loan_order_repayment){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'零钱贷分期表创建失败',
                ];
            }
            $user_loan_order_repayment->user_id=$user_loan_order->user_id;
            $user_loan_order_repayment->order_id = $user_loan_order->id;
            $user_loan_order_repayment->principal=$user_loan_order->money_amount;
            //判断订单是否是首单
            if(UserLoanOrder::FIRST_LOAN == $user_loan_order->is_first){
                $user_loan_order_repayment->interests=0;
            }else{
                $user_loan_order_repayment->interests=0;//$user_loan_order->money_amount*$user_loan_order->apr/10000;//计算一天利息
            }
            //利息
            $user_loan_order_repayment->interests=$user_loan_order->loan_interests;
            //判断订单是否是新网银行的订单
            if(UserLoanOrder::SUB_TYPE_XINWANG == $user_loan_order->sub_order_type){
                $user_loan_order_repayment->fee = intval(bcmul($user_loan_order->money_amount,0.145));
                $user_loan_order_repayment->interests = intval(bcmul($user_loan_order->money_amount,0.005));
            }
            $user_loan_order_repayment->interest_day = 1;//借款当天算一天利息
            $user_loan_order_repayment->interest_time = strtotime(date('Y-m-d',$loan_time));//利息计算到当天
            $user_loan_order_repayment->late_fee=0;
            $user_loan_order_repayment->plan_repayment_time= $loan_time+($user_loan_order->loan_term-1)*24*3600;
            $user_loan_order_repayment->plan_fee_time= $user_loan_order_repayment->plan_repayment_time+86400;
            $user_loan_order_repayment->operator_name=Yii::$app->has('user') && isset(Yii::$app->user->identity->username) ? Yii::$app->user->identity->username : "backsql";
            $user_loan_order_repayment->status=UserLoanOrderRepayment::STATUS_NORAML;
            $user_loan_order_repayment->created_at=time();
            $user_loan_order_repayment->updated_at=time();
            $user_loan_order_repayment->total_money=$user_loan_order_repayment->principal+$user_loan_order_repayment->interests;
            $user_loan_order_repayment->true_total_money = 0;
            $user_loan_order_repayment->card_id=$user_loan_order->card_id;
            $user_loan_order_repayment->loan_time=$loan_time;
            $user_loan_order_repayment->apr=$user_loan_order->apr;
            if(!$user_loan_order_repayment->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'插入零钱贷分期表失败',
                ];
            }
            //更新用户额度表
            //$user_credit = UserCreditTotal::findOne(['user_id'=>$user_loan_order->user_id]);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_loan_order->user_id, $user_loan_order->id);
            if(false == $user_credit){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取零钱贷额度表失败',
                ];
            }
            $user_credit->updated_at = time();
            $user_credit->locked_amount = $user_credit->locked_amount - $user_loan_order_repayment->principal;
            $user_credit->used_amount = $user_credit->used_amount + $user_loan_order_repayment->principal;
            if(!$user_credit->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新零钱贷额度失败',
                ];
            }
            //更新用户认证表，置为非新手
            $user_verification = UserVerification::findOne(['user_id'=>$user_loan_order->user_id]);
            if(false == $user_verification){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取认证表失败',
                ];
            }
            $user_verification->is_quota_novice = UserVerification::VERIFICATION_QUOTA_NOVICE;
            $user_verification->updated_at = time();
            if(!$user_verification->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新新手用户失败',
                ];
            }
            //更新账号流水表
            //第三步：资金流水
            $user_credit_log = new  UserCreditLog();
            $user_credit_log->user_id = $user_loan_order->user_id;
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_FK;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $user_loan_order_repayment->principal;
            $user_credit_log->apr = $user_loan_order_repayment->apr;
            $user_credit_log->interests = "";
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money=$user_credit->amount;
            $user_credit_log->used_money=$user_credit->used_amount;
            $user_credit_log->unabled_money=$user_credit->locked_amount;
            if(!$user_credit_log->save()) {
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'操作资金流水表失败',
                ];
            }
            //更新利息日志表
            $user_interest_log = new UserInterestLog();
            $user_interest_log->user_id=$user_loan_order->user_id;
            $user_interest_log->type = UserInterestLog::TRADE_TYPE_LQD_INTEREST;
            $user_interest_log->type_second = UserInterestLog::TRADE_TYPE_SECOND_NORMAL;
            $user_interest_log->operate_money = $user_loan_order_repayment->interests;
            $user_interest_log->remark="放款之后生成当天利息";
            $user_interest_log->created_at = time();
            $user_interest_log->total_money=$user_credit->amount;
            $user_interest_log->used_money=$user_credit->used_amount;
            $user_interest_log->unabled_money=$user_credit->locked_amount;
            $user_interest_log->order_id=$user_loan_order->id;
            $user_interest_log->repayment_id=$user_loan_order_repayment->id;
            $user_interest_log->repayment_period_id=0;
            $user_interest_log->before_interests=$user_loan_order_repayment->interests;
            $user_interest_log->before_late_fee=$user_loan_order_repayment->late_fee;
            if (!$user_interest_log->save()) {
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'利息流水表插入失败',
                ];
            }
            //生成用户签约信息
            $result = $this->createUserContractInfo($user_loan_order);
            if(!$result){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'生成用户签约信息失败',
                ];
            }
            $transaction->commit();
            $message_service = new MessageService();
            $ret = $message_service->sendMessageLoanYgbArrival($user_loan_order->user_id,$user_loan_order->id);
            return [
                'code'=>0,
                'message'=>'操作成功',
            ];
        }
        catch(\Exception $e){
            $transaction->rollBack();
            $error = $e->getTraceAsString();
            Yii::error($error);
            return [
                'code'=>$e->getCode(),
                'message'=>$error,
            ];
        }

    }

    /**
     * 放款驳回
     * @param $loan_record_id
     * @param $data
     * @return array
     */
    public function rejectLoan($loan_record_id,$remark='',$username=''){
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if(empty($loan_record_id)){
                throw new Exception("loan_record_id 参数不能为空！");
            }
            $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
            if(false == $user_loan_order){
                throw new Exception("获取订单数据失败！");
            }
            $status = $user_loan_order->status;
            if(!in_array((int)$status, [UserLoanOrder::STATUS_PAY, UserLoanOrder::STATUS_PENDING_LOAN, UserLoanOrder::STATUS_REVIEW_PASS, UserLoanOrder::STATUS_FUND_CONTRACT])){
                throw new Exception("该订单不处于待放款或打款中，不能驳回！");
            }

            //给资方添加额度 及删除该资方订单记录
            if($user_loan_order->fund_id) {
                $order_fund_info = OrderFundInfo::findOne(['fund_id'=>(int)$user_loan_order->fund_id, 'order_id'=>$user_loan_order->id]);

                $user_loan_order->loanFund->increaseQuota($user_loan_order->money_amount, date('Y-m-d', $order_fund_info->created_at), $user_loan_order->money_amount);
                $order_fund_info->changeStatus(OrderFundInfo::STATUS_REMOVED, '放款失败，删除记录');
            }

            //解除用户该订单锁定额度
            //$user_credit_total = UserCreditTotal::findOne(['user_id'=>$user_loan_order['user_id']]);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit_total = $creditChannelService->getCreditTotalByUserAndOrder($user_loan_order['user_id'], $user_loan_order['id']);

            $user_credit_total->locked_amount = $user_credit_total->locked_amount - $user_loan_order['money_amount'];
            if(!$user_credit_total->save()){
                throw new Exception("保存失败！");
            }

            $log = new UserOrderLoanCheckLog();
            $log->order_id = $loan_record_id;
            $log->repayment_id = 0;
            $log->repayment_type = 0;
            $log->before_status = $user_loan_order['status'];
            $log->after_status = UserLoanOrder::STATUS_PENDING_CANCEL;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
            $log->remark = $remark;
            $log->operation_type = UserOrderLoanCheckLog::LOAN_FK;
            if(!$log->save()){
                throw new Exception("日志保存失败！");
            }

            $user_loan_order->remark = $remark;
            $user_loan_order->reason_remark = $remark;
            $user_loan_order->status = UserLoanOrder::STATUS_PENDING_CANCEL;
            $user_loan_order->updated_at = time();
            if(!$user_loan_order->save()){
                throw new Exception("驳回失败！");
            }
            $transaction->commit();

            $loan_person = LoanPerson::findOne(['id'=>$user_loan_order->user_id]);
            if($loan_person){
                $send_message = "尊敬的".$loan_person->name."，您申请的".($user_loan_order->money_amount/100)."元打款失败，请至APP内确认银行卡信息或重新绑卡";
                UserLoanOrder::sendSyncSms($loan_person->phone,$send_message,$user_loan_order);
            }

            //触发订单的放款拒绝事件 自定义的数据可添加到custom_data里
            $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_PAY_REJECTED, new \common\base\Event(['custom_data'=>[]]));

            return[
                'code'=>0,
                'message'=>'驳回成功',
            ];


        }catch(\Exception $e){
            $transaction->rollBack();
            return [
                'code' => 1,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * 用户借款流程，打款之后回调
     * @param integer $loan_record_id  订单ID
     * @param string $remark 备注
     * @param string $username 操作人
     * @param integer $loan_time 放款时间 默认为当前时间
     */
    public function callbackPayMoney($loan_record_id,$remark='',$username='', $loan_time=null){
        $lock_name = 'callbackPayMoney:'.$loan_record_id;
        try {
            if(!($lock = Lock::get($lock_name, 30))) {
                return [
                        'code'=>-1,
                        'message'=>'重复打款回调'
                ];
            }
//            if (empty(Yii::$app->user->identity)) {
//                throw new Exception("抱歉，请先登录！");
//            }
            if(empty($loan_record_id)){
                throw new Exception("loan_record_id 参数不能为空！");
            }

            $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
            if(false == $user_loan_order){
                throw new Exception("该订单不存在！");
            }
            if($user_loan_order['status'] != UserLoanOrder::STATUS_PAY){
                throw new Exception("该订单非打款中状态！");
            }
            $order_type = $user_loan_order->order_type;

            $log = new UserOrderLoanCheckLog();
            $log->order_id = $loan_record_id;
            $log->repayment_id = 0;
            $log->repayment_type = 0;
            $log->before_status = $user_loan_order['status'];
            $log->after_status = UserLoanOrder::STATUS_LOAN_COMPLETE;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
            $log->remark = $remark;
            $log->operation_type = UserOrderLoanCheckLog::LOAN_FK;
            $log->save();

            switch($order_type){
                case UserLoanOrder::LOAN_TYPE_LQD:
                    $ret = self::operatorLqd($user_loan_order, $loan_time);
                    break;
                case UserLoanOrder::LOAN_TYPE_FQSC:
                    break;
                default:
                    throw new Exception("订单类型错误！");
                    break;
            }
            Lock::del($lock_name);
            //统一处理 零钱包 房租宝 的结果
            if(isset($ret['code'])){
                //触发订单的放款成功事件 自定义的数据可添加到custom_data里
                $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_PAY_SUCCESS, new \common\base\Event(['custom_data'=>[]]));
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message']
                ];
            }else{
                return [
                    'code'=>-1,
                    'message'=>'操作失败'
                ];
            }
        } catch (Exception $e) {
            Lock::del($lock_name);
            return [
                'code' => 1,
                'message' => $e->getMessage(),
            ];
        }
    }

    /*
     * 用户借款流程，扣款之后回调
     */
    private  function _debitOrderLqd($user_loan_order, $repayment_id, $money, $remark, $username, $params = []) {
        $boolForceFinish = $params && isset($params['boolForceFinish']) && $params['boolForceFinish'] ? true : false; //是否直接置为已还款
        $operationType = $params && isset($params['operationType']) ? $params['operationType'] : UserOrderLoanCheckLog::REPAY_KK;//是否直接置为已还款

        try {
            $time = time();
            //还款优先 进行垫付（避免计划还款时间 临界点问题）
//            if ( ($user_loan_order->fund_id != LoanFund::ID_KOUDAI)
//                && $user_loan_order->orderFundInfo
//                && ($time >= $user_loan_order->orderFundInfo->plan_payment_time)
//                && ($user_loan_order->orderFundInfo->plan_payment_time > 0)
//            ) {
//                $this->orderPrepay($user_loan_order);
//            }

            $user_id = $user_loan_order->user_id;
            $order_id = $user_loan_order->id;
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id,'id'=>$repayment_id]);
            if(false == $user_loan_order_repayment){
                return [
                    'code'=>-1,
                    'message'=>'获取零钱贷分期总表数据失败',
                ];
            }
            $loan_person = LoanPerson::findOne(['id'=>$user_loan_order->user_id]);
            //口袋记账和加班管家前三天不扣滞纳金
            if(in_array($loan_person->source_id,[LoanPerson::PERSON_SOURCE_KDJZ,LoanPerson::PERSON_SOURCE_JBGJ]) && ($user_loan_order_repayment->late_day <= 3)){
                $total = $user_loan_order_repayment->principal;
            }else{
                $total = $user_loan_order_repayment->principal + $user_loan_order_repayment->interests + $user_loan_order_repayment->late_fee;
            }
//            $total = $user_loan_order_repayment->principal + $user_loan_order_repayment->interests + $user_loan_order_repayment->late_fee - $user_loan_order_repayment->coupon_money;

            $user_loan_order_repayment->updated_at = time();
            $user_loan_order_repayment->true_total_money =  $user_loan_order_repayment->true_total_money+$money;
            if($boolForceFinish || $user_loan_order_repayment->true_total_money >= $total){
                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                $user_loan_order_repayment->true_repayment_time = time();
            }else{
                $user_loan_order_repayment->status = $user_loan_order_repayment->is_overdue && $user_loan_order_repayment->overdue_day > 0 ? UserLoanOrderRepayment::STATUS_OVERDUE : UserLoanOrderRepayment::STATUS_NORAML;
            }
            $user_loan_order_repayment->current_debit_money = max(($user_loan_order_repayment->current_debit_money - $money),0);
            if(!$user_loan_order_repayment->save()){
                return [
                        'code'=>-1,
                        'message'=>'操作零钱贷分期还款表失败',
                ];
            }

            if($operationType == UserOrderLoanCheckLog::REPAY_KKJM ) {  //减免添加log
                $operator = ['id' => $params['operator_id'], 'username' => $username];
                $add_rid_log = $this->setRidOverdueMoneyLog($user_loan_order_repayment, $operator, $params['rid_type'], $remark);
                if (!$add_rid_log) {
                    $transaction->rollBack();
                    return [
                        'code' => -1,
                        'message' => '减免日志添加失败',
                    ];
                }
            }else{
                if ($params && !isset($params['addUserCreditMoneyLog'])) {
                    $order_uuid = isset($params['order_uuid']) ? $params['order_uuid'] : null;
                    $attrs = [
                        'type' => UserCreditMoneyLog::TYPE_DEBIT,
                        'payment_type' => isset($params['repayment_type']) ? $params['repayment_type'] : UserCreditMoneyLog::PAYMENT_TYPE_AUTO,
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'order_uuid' => $order_uuid,
                        'pay_order_id' => isset($params['pay_order_id']) ? $params['pay_order_id'] : '',
                        'success_repayment_time' => $time,
                        'img_url' => isset($params['img_url']) ? $params['img_url'] : '',
                        'remark' => $remark,
                        'status' => UserCreditMoneyLog::STATUS_SUCCESS,
                        'operator_money' => $money,
                        'operator_name' => $username,
                        'card_id' => isset($params['card_id']) ? $params['card_id'] : 0,
                        'debit_channel' => isset($params['debit_channel']) ? $params['debit_channel'] : 0,
                        'debit_account' => isset($params['debit_account']) ? $params['debit_account'] : '',
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                    $credit_money_log = UserCreditMoneyLog::saveRecord($attrs);

                    $credit_money_log_id = $credit_money_log->id;
                } else {
                    $credit_money_log_id = isset($params['UserCreditMoneyLogId']) ? $params['UserCreditMoneyLogId'] : 0;
                }
            }

            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_id = $repayment_id;
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
            $log->before_status = $user_loan_order_repayment['status'];
            $log->after_status = $user_loan_order_repayment->status;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = $remark;
            $log->operation_type = $operationType;
            if(!$log->save()){
                return [
                    'code'=>-1,
                    'message'=>'生成日志表失败',
                ];
            }

            $user_loan_order->updated_at = time();
            if($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
                $collectionService = new CollectionService();
                @$collectionService->collectionPaybackOut($user_loan_order_repayment['order_id'], 'self');
            }else{
                $user_loan_order->status = UserLoanOrder::STATUS_PARTIALREPAYMENT;
            }
            if(!$user_loan_order->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'操作零钱贷订单表还款表失败',
                ];
            }

            //更新用户额度表
            //$user_credit = UserCreditTotal::findOne(['user_id'=>$user_id);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_id, $order_id);

            if(false == $user_credit){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取客户额度表数据失败',
                ];
            }

            $user_credit->updated_at = time();
            if($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE){
                $user_credit->used_amount = $user_credit->used_amount - $user_loan_order_repayment->principal;
                $user_credit->locked_amount = 0;
                if(!$user_credit->save()){
                    $transaction->rollBack();
                    return [
                            'code'=>-1,
                            'message'=>'更新客户额度表失败',
                    ];
                }
            }

            //如果有资方 需要处理资方相关数据 order_fund_info添加分账数据
            if($user_loan_order->fund_id && $operationType != UserOrderLoanCheckLog::REPAY_KKJM) {
                try {
                    $this->orderRepayFundOperation($user_loan_order, $money, $username,$credit_money_log_id);
                } catch (\Exception $ex) {
                    Yii::error("还款操作异常 ： {$ex->getFile()} 第 {$ex->getLine()} 行错误：{$ex->getMessage()}");
                }
            }

            $transaction->commit();
            $transaction = null;
            //更新账号流水表
            //第三步：资金流水
            if($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){//完全还款
                $user_credit_log = new  UserCreditLog();
                $user_credit_log->user_id = $user_id;
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_KK;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $money;
                $user_credit_log->apr = $user_loan_order_repayment->apr;
                $user_credit_log->interests = "";
                $user_credit_log->to_card = "";
                $user_credit_log->remark = "";
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = "";
                $user_credit_log->total_money=$user_credit->amount;
                $user_credit_log->used_money=$user_credit->used_amount;
                $user_credit_log->unabled_money=$user_credit->locked_amount;
                $user_credit_log->save();

                //不做提额
                //$creditChannelService = \Yii::$app->creditChannelService;
                //$increase_credit = $creditChannelService->increaseUserCreditAccount($user_loan_order_repayment);//逻辑提额
                $increase_credit = 0;
                // todo:发送通知短信 -- done by ron
                if($loan_person){
                    //扣款成功，重置为老用户
                    $name  = $loan_person->name;
                    $phone = $loan_person->phone;
                    $customer_type = $loan_person->customer_type;
                    $sourceId = $loan_person->source_id;
                    if(!empty($name)&&!empty($phone)){
                        $date = date('m月d日',time());
                        $plan_repayment_money = $user_loan_order_repayment->principal;
                        if(!empty($plan_repayment_money)){
                            $plan_repayment_money = $plan_repayment_money/100;
                            $send_message = "尊敬的".$name."，您于".$date."成功还款".$plan_repayment_money."元，".($increase_credit > 0 ? "同时获得".($increase_credit/100)."元额度提升！" : "良好的信用积累有机会获得更高的额度！");

                            if(YII_ENV_PROD){
                                @MessageHelper::sendSMSCS($phone, $send_message,'smsService_TianChang_HY',$sourceId); // 暂用，成功一条发一条
                            }

                        }
                    }

                    //还款成功，不是老用户的话重置为老用户
                    if(LoanPerson::CUSTOMER_TYPE_OLD != $customer_type){
                        $loan_person->customer_type = LoanPerson::CUSTOMER_TYPE_OLD;
                        $loan_person->save();

                        $user_loan_order = UserLoanOrder::findOne(['id'=>$user_loan_order->id]);
                        if($user_loan_order){
                            $user_loan_order->is_first = UserLoanOrder::FIRST_LOAN;
                            $user_loan_order->save();
                        }
                    }
                }

                //触发订单的还款成功事件 自定义的数据可添加到custom_data里
                try{
                    $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_REPAY_SUCCESS, new \common\base\Event(['custom_data'=>[]]));
                }catch (\Exception $e){

                }
                //事件处理队列  还款成功
                RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                    'event_name' => AppEventService::EVENT_SUCCESS_REPAY,
                    'params' => ['user_id' => $user_id, 'order_id' => $order_id],
                ])]);
            }
            else{ //部分扣款成功
                if($loan_person){

                    $name  = $loan_person->name;
                    $phone = $loan_person->phone;
                    $sourceId = $loan_person->source_id;
                    if(!empty($name)&&!empty($phone)){
                        $date = date('m月d日',time());
                        if(!empty($money)){
                            $plan_repayment_money = $user_loan_order_repayment->principal/100;
                            $money = $money/100;
                            $send_message = "尊敬的".$name."，您的".$plan_repayment_money."元借款已于".$date."成功还款".$money."元，如有疑问请联系客服。";
                            // UserLoanOrder::sendSyncSms($phone,$send_message,$user_loan_order); // 后期脚本形式发送
                            if(YII_ENV_PROD){
                                if($money != 50){//100改成50
//                                    @MessageHelper::sendSMS($phone, $send_message,'smsService_TianChang_HY',$sourceId); // 暂用，成功一条发一条
                                }
                            }
                        }
                    }
                }
            }

            return [
                'code'=>0,
                'message'=>'操作成功',
            ];

        }catch(\Exception $e){
            if(!empty($transaction)){
                $transaction->rollBack();
            }
            if(YII_ENV_PROD){
                UserLoanOrder::sendSMS(NOTICE_MOBILE, $e->getMessage());
            }
            return [
                'code'=>$e->getCode() ? $e->getCode() : -1,
                'message'=>$e->getMessage(),
            ];
        }
    }

    private  function _debitOrderFzd($user_loan_order,$repayment_id,$repayment_period_id,$money,$remark,$username){

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $total_period = $user_loan_order->loan_term;
            $user_repayment = UserRepayment::findOne(['user_id'=>$user_loan_order->user_id,'loan_order_id'=>$user_loan_order->id,'id'=>$repayment_id]);

            if(false == $user_repayment){
                return [
                    'code'=>-1,
                    'message'=>'获取房租贷分期总表数据失败',
                ];
            }
            $user_repayment_period = UserRepaymentPeriod::findOne(['user_id'=>$user_loan_order->user_id,'loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);

            if(false == $user_repayment_period){
                return [
                    'code'=>-1,
                    'message'=>'获取房租贷分期计划表数据失败',
                ];
            }

            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_id = $repayment_period_id;
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
            $log->before_status = $user_repayment_period['status'];
            $log->after_status = UserRepaymentPeriod::STATUS_REPAY_COMPLETE;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = $remark;
            $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
            if(!$log->save()){
                return [
                    'code'=>-1,
                    'message'=>'生成日志表失败',
                ];
            }
            $period = $user_repayment_period->period;

            $user_repayment_period->updated_at = time();
            $user_repayment_period->status = UserRepaymentPeriod::STATUS_REPAY_COMPLETE;
            $user_repayment_period->true_repayment_money = $money;
            $user_repayment_period->true_repayment_time = time();
            $user_repayment_period->true_repayment_principal = $user_repayment_period->plan_repayment_principal;
            $user_repayment_period->true_repayment_interest = $user_repayment_period->plan_repayment_interest;
            $user_repayment_period->true_late_fee = $user_repayment_period->plan_late_fee;

            if(!$user_repayment_period->save()){
                return [
                    'code'=>-1,
                    'message'=>'更新房租贷分期计划表数据失败',
                ];
            }

            $user_repayment->updated_at = time();
            $user_repayment->repaymented_amount = $user_repayment->repaymented_amount+$money;
            $user_repayment->next_period_repayment_id = $period+1;
            if($total_period == $period){
                $user_repayment->next_period_repayment_id = 0;
                $user_repayment->status= UserRepayment::STATUS_REPAY_COMPLETE;
                $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
            }


            if(!$user_repayment->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新房租贷分期总表数据失败',
                ];
            }

            $user_loan_order->updated_at = time();

            if(!$user_loan_order->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新房租贷订单总表数据失败',
                ];
            }


            //更新用户额度表
            //$user_rent_credit = UserCreditTotal::findOne(['user_id'=>$user_loan_order->user_id]);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_rent_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_loan_order->user_id, $user_loan_order->id);

            if(false == $user_rent_credit){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取用户额度表失败',
                ];
            }

            $user_rent_credit->updated_at = time();

            $user_rent_credit->used_amount = $user_rent_credit->used_amount - $user_repayment_period->plan_repayment_principal;
            if(!$user_rent_credit->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新用户额度表失败',
                ];
            }

            //更新账号流水表
            //第三步：资金流水

            $user_credit_log = new  UserCreditLog();

            $user_credit_log->user_id = $user_loan_order->user_id;
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_FZD_KK;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $user_repayment_period->plan_repayment_principal;
            $user_credit_log->apr = $user_loan_order->apr;
            $user_credit_log->interests = "";
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money=$user_rent_credit->amount;
            $user_credit_log->used_money=$user_rent_credit->used_amount;
            $user_credit_log->unabled_money=$user_rent_credit->locked_amount;
            if(!$user_credit_log->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'操作用户资金流水失败',
                ];
            }


            $transaction->commit();

            return [
                'code'=>0,
                'message'=>'操作成功',
            ];

        }catch(\Exception $e){
            $transaction->rollBack();

            return [
                'code'=>-1,
                'message'=>'操作失败',
            ];
        }


    }

    private  function _debitOrderFqsc($user_loan_order,$repayment_id,$repayment_period_id,$money,$remark,$username){

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $total_period = $user_loan_order->loan_term;
            $user_repayment = UserRepayment::findOne(['user_id'=>$user_loan_order->user_id,'loan_order_id'=>$user_loan_order->id,'id'=>$repayment_id]);

            if(false == $user_repayment){
                return [
                    'code'=>-1,
                    'message'=>'获取分期购分期总表数据失败',
                ];
            }
            $user_repayment_period = UserRepaymentPeriod::findOne(['user_id'=>$user_loan_order->user_id,'loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);

            if(false == $user_repayment_period){
                return [
                    'code'=>-1,
                    'message'=>'获取分期购分期计划表数据失败',
                ];
            }

            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_id = $repayment_period_id;
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC;
            $log->before_status = $user_repayment_period['status'];
            $log->after_status = UserRepaymentPeriod::STATUS_REPAY_COMPLETE;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = $remark;
            $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
            if(!$log->save()){
                return [
                    'code'=>-1,
                    'message'=>'生成日志表失败',
                ];
            }
            $period = $user_repayment_period->period;

            $user_repayment_period->updated_at = time();
            $user_repayment_period->status = UserRepaymentPeriod::STATUS_REPAY_COMPLETE;
            $user_repayment_period->true_repayment_money = $money;
            $user_repayment_period->true_repayment_time = time();
            $user_repayment_period->true_repayment_principal = $user_repayment_period->plan_repayment_principal;
            $user_repayment_period->true_repayment_interest = $user_repayment_period->plan_repayment_interest;
            $user_repayment_period->true_late_fee = $user_repayment_period->plan_late_fee;

            if(!$user_repayment_period->save()){
                return [
                    'code'=>-1,
                    'message'=>'更新分期购分期计划表数据失败',
                ];
            }

            $user_repayment->updated_at = time();
            $user_repayment->repaymented_amount = $user_repayment->repaymented_amount+$money;
            $user_repayment->next_period_repayment_id = $period+1;
            if($total_period == $period){
                $user_repayment->next_period_repayment_id = 0;
                $user_repayment->status= UserRepayment::STATUS_REPAY_COMPLETE;
                $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
            }


            if(!$user_repayment->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新分期购分期总表数据失败',
                ];
            }

            $user_loan_order->updated_at = time();

            if(!$user_loan_order->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新分期购订单总表数据失败',
                ];
            }


            //更新用户额度表
            //$user_rent_credit = UserCreditTotal::findOne(['user_id'=>$user_loan_order->user_id]);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_rent_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_loan_order->user_id, $user_loan_order->id);

            if(false == $user_rent_credit){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取用户额度表失败',
                ];
            }

            $user_rent_credit->updated_at = time();

            $user_rent_credit->used_amount = $user_rent_credit->used_amount - $user_repayment_period->plan_repayment_principal;
            if(!$user_rent_credit->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新用户额度表失败',
                ];
            }

            //更新账号流水表
            //第三步：资金流水

            $user_credit_log = new  UserCreditLog();

            $user_credit_log->user_id = $user_loan_order->user_id;
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_FQSC_KK;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $user_repayment_period->plan_repayment_principal;
            $user_credit_log->apr = $user_loan_order->apr;
            $user_credit_log->interests = "";
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money=$user_rent_credit->amount;
            $user_credit_log->used_money=$user_rent_credit->used_amount;
            $user_credit_log->unabled_money=$user_rent_credit->locked_amount;
            if(!$user_credit_log->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'操作用户资金流水失败',
                ];
            }


            $transaction->commit();

            return [
                'code'=>0,
                'message'=>'操作成功',
            ];

        }catch(\Exception $e){
            $transaction->rollBack();

            return [
                'code'=>-1,
                'message'=>'操作失败',
            ];
        }
    }

    /**
     * 用户借款流程，扣款之后回调
     * @param $loan_record_id  订单ID
     * @param $money 扣款金额
     */
    public function callbackDebitMoney($loan_record_id,$repayment_id,$repayment_period_id,$money,$remark='',$username='',$params=[]){
        $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
        if (false == $user_loan_order) {
            \yii::warning("callbackDebitMoney_1565 {$loan_record_id} not_exists.");
            return [
                'code'=>-1,
                'message'=>'该订单不存在',
            ];
        }
        if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
            \yii::warning("callbackDebitMoney_1572 {$loan_record_id} repay_complete.");
            return [
                'code'=>-1,
                'message'=>'该订单已还款了',
            ];
        }

        $order_type = $user_loan_order->order_type;
        switch($order_type) {
            case UserLoanOrder::LOAN_TYPE_LQD:
                $ret = self::_debitOrderLqd($user_loan_order, $repayment_id, $money, $remark, $username, $params);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $ret = self::_debitOrderFzd($user_loan_order, $repayment_id, $repayment_period_id, $money, $remark, $username);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $ret = self::_debitOrderFqsc($user_loan_order, $repayment_id, $repayment_period_id, $money, $remark, $username);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            default:
                return [
                    'code'=>-1,
                    'message'=>'订单类型不存在',
                ];
                break;
        }
    }

    /**
     * [zhangyuliang]
     * 用户借款流程，扣款之后回调 优化版
     */

    public function optimizedCallbackDebitMoney($loan_record_id,$repayment_id,$money,$remark='',$username='',$params=[]){
        $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
        if (false == $user_loan_order) {
            \yii::warning("callbackDebitMoney_1565 {$loan_record_id} not_exists.");
            return [
                'code'=>-1,
                'message'=>'该订单不存在',
            ];
        }
        if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
            \yii::warning("callbackDebitMoney_1572 {$loan_record_id} repay_complete.");
            return [
                'code'=>-1,
                'message'=>'该订单已还款了',
            ];
        }
        $ret = self::_optimizedDebitOrderLqd($user_loan_order, $repayment_id, $money, $remark, $username, $params);
        return [
            'code'=>$ret['code'],
            'message'=>$ret['message'],
        ];
    }

    /**
     * [zhangyuliang]
     * 用户借款流程，扣款之后回调 优化版
     * 此方法处理还款成功后的结果
     */
    private  function _optimizedDebitOrderLqd($user_loan_order, $repayment_id, $money, $remark, $username, $params = []){
        $time = time();
        $boolForceFinish = $params && isset($params['boolForceFinish']) && $params['boolForceFinish'] ? true : false; //是否直接置为已还款
        $operationType = $params && isset($params['operationType']) ? $params['operationType'] : UserOrderLoanCheckLog::REPAY_KK;//是否直接置为已还款
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $user_id = $user_loan_order->user_id;
            $order_id = $user_loan_order->id;
            $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id,'id'=>$repayment_id]);
            if (false == $user_loan_order_repayment) {
                throw new \Exception('获取零钱贷分期总表数据失败',-1);
            }
            $loan_person = LoanPerson::findOne(['id'=>$user_loan_order->user_id]);
            //口袋记账和加班管家前三天不扣滞纳金
            if (in_array($loan_person->source_id,[LoanPerson::PERSON_SOURCE_KDJZ,LoanPerson::PERSON_SOURCE_JBGJ]) && ($user_loan_order_repayment->late_day <= 3)) {
                $total = $user_loan_order_repayment->principal;
            } else {
                $total = $user_loan_order_repayment->principal + $user_loan_order_repayment->interests + $user_loan_order_repayment->late_fee;
            }
            $user_loan_order_repayment->updated_at = time();
            $user_loan_order_repayment->true_total_money =  $user_loan_order_repayment->true_total_money+$money;
            if ($boolForceFinish || $user_loan_order_repayment->true_total_money >= $total) {
                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                $user_loan_order_repayment->true_repayment_time = time();
            } else {
                $user_loan_order_repayment->status = $user_loan_order_repayment->is_overdue && $user_loan_order_repayment->overdue_day > 0 ? UserLoanOrderRepayment::STATUS_OVERDUE : UserLoanOrderRepayment::STATUS_NORAML;
            }
            $user_loan_order_repayment->current_debit_money = max(($user_loan_order_repayment->current_debit_money - $money),0);
            if (!$user_loan_order_repayment->save()) {
                throw new \Exception('操作零钱贷分期还款表失败',-1);
            }
            //扣款成功回调添加还款日志列表
            $order_uuid = isset($params['order_uuid']) ? $params['order_uuid'] : null;
            $attrs = [
                'type' => UserCreditMoneyLog::TYPE_DEBIT,
                'payment_type' => isset($params['repayment_type']) ? $params['repayment_type'] : UserCreditMoneyLog::PAYMENT_TYPE_AUTO,
                'user_id' => $user_id,
                'order_id' => $order_id,
                'order_uuid' => $order_uuid,
                'pay_order_id' => isset($params['pay_order_id']) ? $params['pay_order_id'] : '',
                'success_repayment_time' => $time,
                'img_url' => isset($params['img_url']) ? $params['img_url'] : '',
                'remark' => $remark,
                'status' => UserCreditMoneyLog::STATUS_SUCCESS,
                'operator_money' => $money,
                'operator_name' => $username,
                'card_id' => isset($params['card_id']) ? $params['card_id'] : 0,
                'debit_channel' => isset($params['debit_channel']) ? $params['debit_channel'] : 0,
                'debit_account' => isset($params['debit_account']) ? $params['debit_account'] : '',
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $credit_money_log = UserCreditMoneyLog::saveRecord($attrs);
            if ($credit_money_log->id) {
                $credit_money_log_id = $credit_money_log->id;
            } else {
                $credit_money_log_id = isset($params['UserCreditMoneyLogId']) ? $params['UserCreditMoneyLogId'] : 0;
            }
            //添加订单审核记录
            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_id = $repayment_id;
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
            $log->before_status = $user_loan_order_repayment['status'];
            $log->after_status = $user_loan_order_repayment->status;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = $remark;
            $log->operation_type = $operationType;
            if (!$log->save()) {
                throw new \Exception('生成日志表失败',-1);
            }
            //更新借款订单表
            $user_loan_order->updated_at = time();
            if ($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
                $collectionService = new CollectionService();
                @$collectionService->collectionPaybackOut($user_loan_order_repayment['order_id'], 'self');
            } else {
                $user_loan_order->status = UserLoanOrder::STATUS_PARTIALREPAYMENT;
            }
            if(!$user_loan_order->save()){
                throw new \Exception("操作零钱贷订单表还款表失败",-1);
            }
            //更新用户额度表
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_id, $order_id);
            if (false == $user_credit) {
                throw new \Exception('获取客户额度表数据失败',-1);
            }
            $user_credit->updated_at = time();
            if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
                $user_credit->used_amount = $user_credit->used_amount - $user_loan_order_repayment->principal;
                $user_credit->locked_amount = 0;
                if (!$user_credit->save()) {
                    throw new \Exception('更新客户额度表失败',-1);
                }
            }
            $transaction->commit();
            $transaction = null;
            //更新账号流水表
            //第三步：资金流水
            if ($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {//完全还款
                $user_credit_log = new  UserCreditLog();
                $user_credit_log->user_id = $user_id;
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_KK;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $money;
                $user_credit_log->apr = $user_loan_order_repayment->apr;
                $user_credit_log->interests = "";
                $user_credit_log->to_card = "";
                $user_credit_log->remark = "";
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = "";
                $user_credit_log->total_money=$user_credit->amount;
                $user_credit_log->used_money=$user_credit->used_amount;
                $user_credit_log->unabled_money=$user_credit->locked_amount;
                if (!$user_credit_log->save()) {
                    throw new \Exception("UserCreditLog 记录更新失败",-1);
                }
                //还款完成后相关处理
                $this->afterRepayComplete($user_loan_order,$user_loan_order_repayment,$loan_person);
            } else { //部分扣款成功
                $this->afterRepaySection($user_loan_order,$user_loan_order_repayment,$loan_person,['money'=>$money]);
            }
            return [ 'code'=>0, 'message'=>'操作成功'];
        } catch (\Exception $e){
            if (!empty($transaction)) {
                $transaction->rollBack();
            }
            if (YII_ENV_PROD) {
                UserLoanOrder::sendSMS(NOTICE_MOBILE, $e->getMessage());
//                UserLoanOrder::sendSMS(NOTICE_MOBILE2, $e->getMessage());
            }
            return [
                'code'=>$e->getCode() ? $e->getCode() : -1,
                'message'=>$e->getMessage(),
            ];
        }
    }

    /**
     * 还款处理完成后相关处理
     */
    private function afterRepayComplete($user_loan_order,$user_loan_order_repayment,$loan_person,$ext=[]) {
        //用户提额处理
        //$creditChannelService = \Yii::$app->creditChannelService;
        //$increase_credit = $creditChannelService->increaseUserCreditAccount($user_loan_order_repayment);不做提额

        //用户提额处理（20180808）
        $creditChannelService = new UserCreditChannelService();
        $increase_credit = $creditChannelService->increaseUserCreditAccountNew($user_loan_order_repayment);

        //扣款成功，重置为老用户
        $name  = $loan_person->name;
        $phone = $loan_person->phone;
        $customer_type = $loan_person->customer_type;
        $sourceId = $loan_person->source_id;
        if (!empty($name)&&!empty($phone)) {
            $date = date('m月d日',time());
            $plan_repayment_money = $user_loan_order_repayment->principal;
            if(!empty($plan_repayment_money)){
                $plan_repayment_money = StringHelper::safeConvertIntToCent($plan_repayment_money);
                $send_message = "尊敬的".$name."，您于".$date."成功还款".$plan_repayment_money."元，".($increase_credit > 0 ? "同时获得".($increase_credit/100)."元额度提升！" : "良好的信用积累有机会获得更高的额度！");
                if(YII_ENV_PROD){
                    @MessageHelper::sendSMSCS($phone, $send_message,'smsService_TianChang_HY',$sourceId); // 暂用，成功一条发一条
                } else {
                    @MessageHelper::sendSMS(NOTICE_MOBILE,$send_message,'smsService_TianChang_HY',$sourceId);
                }
            }
        }

        //还款成功，不是老用户的话重置为老用户
        if (LoanPerson::CUSTOMER_TYPE_OLD != $customer_type) {
            //更新用户表 置为老客户
            $loan_person->customer_type = LoanPerson::CUSTOMER_TYPE_OLD;
            $loan_person->save();
            //该订单设置为第一单
            $user_loan_order->is_first = UserLoanOrder::FIRST_LOAN;
            $user_loan_order->save();
        }
        //触发订单的还款成功事件 自定义的数据可添加到custom_data里
        try {
            $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_REPAY_SUCCESS, new \common\base\Event(['custom_data'=>[]]));
        } catch (\Exception $e) {}
        //事件处理队列  还款成功
        RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([ 'event_name' => AppEventService::EVENT_SUCCESS_REPAY,'params' => ['user_id' => $user_loan_order->user_id, 'order_id' => $user_loan_order->user_id]])]);
    }

    private function afterRepaySection($user_loan_order,$user_loan_order_repayment,$loan_person,$ext=[]) {
        $date = date('m月d日',time());
        $name  = $loan_person->name;
        $phone = $loan_person->phone;
        $sourceId = $loan_person->source_id;
        $money = isset($ext['money']) ? $ext['money'] : 0;
        if (!empty($name) && !empty($phone)) {
            $plan_repayment_money = StringHelper::safeConvertIntToCent($user_loan_order_repayment->principal);
            $money = StringHelper::safeConvertIntToCent($money);
            $send_message = "尊敬的".$name."，您的".$plan_repayment_money."元借款已于".$date."成功还款".$money."元，如有疑问请联系客服。";
            if(YII_ENV_PROD && $money != 100){
//                @MessageHelper::sendSMS($phone, $send_message,'smsService_TianChang_HY',$sourceId); // 暂用，成功一条发一条
            }
        }
    }

    public function callbackDebitMoney_mhk($loan_record_id,$repayment_id,$repayment_period_id,$money,$remark='',$username='',$params=[]){
        $user_loan_order = UserLoanOrder::find()->where(['id'=>$loan_record_id])->one(Yii::$app->get('db_mhk_assist'));
        if (false == $user_loan_order) {
            \yii::warning("callbackDebitMoney_1565 {$loan_record_id} not_exists.");
            return [
                'code'=>-1,
                'message'=>'该订单不存在',
            ];
        }
        if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
            \yii::warning("callbackDebitMoney_1572 {$loan_record_id} repay_complete.");
            return [
                'code'=>-1,
                'message'=>'该订单已还款了',
            ];
        }

        $order_type = $user_loan_order->order_type;
        switch($order_type) {
            case UserLoanOrder::LOAN_TYPE_LQD:
                $ret = self::_debitOrderLqd($user_loan_order, $repayment_id, $money, $remark, $username, $params);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $ret = self::_debitOrderFzd($user_loan_order, $repayment_id, $repayment_period_id, $money, $remark, $username);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $ret = self::_debitOrderFqsc($user_loan_order, $repayment_id, $repayment_period_id, $money, $remark, $username);
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['message'],
                ];
                break;
            default:
                return [
                    'code'=>-1,
                    'message'=>'订单类型不存在',
                ];
                break;
        }
    }
    /**
     * 扣款驳回
     * @param $loan_record_id
     * @param $repayment_id
     * @param $repayment_period_id
     * @param $repayment_period_id
     * @param $data
     * @return array
     */
    public function debitReject($loan_record_id,$repayment_id,$repayment_period_id,$false_type,$remark='',$username=''){
        $transaction = Yii::$app->db_kdkj->beginTransaction();

        try{
            if(empty($loan_record_id)||empty($repayment_id)||empty($false_type)){
                throw new Exception("参数丢失！");
            }
            $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
            if(false == $user_loan_order){
                throw new Exception("获取订单数据失败！");
            }
            if($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE){
                throw new Exception("该订单已还款了！");
            }

            $order_type = $user_loan_order->order_type;
            switch($order_type){
                case UserLoanOrder::LOAN_TYPE_LQD:
                    $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$user_loan_order->id,'id'=>$repayment_id]);
                    if(!$user_loan_order_repayment){
                        throw new Exception("获取分期数据失败！");
                    }
                    if($user_loan_order_repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                        throw new Exception("该订单已还款了！");
                    }
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $loan_record_id;
                    $log->repayment_id = $repayment_id;
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                    $log->before_status = $user_loan_order_repayment['status'];
                    $log->operator_name = $username;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = $remark;
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;

                    if(isset($false_type)){

                        switch($false_type){
                            case 1:
                                //客户要求驳回，那么重置为待扣款
                                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
                                $user_loan_order_repayment->updated_at = time();
                                if(!$user_loan_order_repayment->save()){
                                    throw new Exception("操作失败！");
                                }
                                $log->after_status = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
                                if(!$log->save()){
                                    throw new Exception("日志保存失败！");
                                }
                                break;
                            case 2:
                                //订单异常
                                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
                                $user_loan_order_repayment->updated_at = time();
                                if(!$user_loan_order_repayment->save()){
                                    throw new Exception("操作失败！");
                                }
                                $log->after_status = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
                                if(!$log->save()){
                                    throw new Exception("日志保存失败！");
                                }
                                break;
                            default:
                                throw new Exception("驳回类型有误！");
                                break;
                        }

                    }else{
                        throw new Exception("参数丢失！");
                    }

                    break;
//                case UserLoanOrder::LOAN_TYPR_FZD:
//                    if(empty($repayment_period_id)){
//                        throw new Exception("参数丢失！");
//                    }
//
//                    $user_repayment_period = UserRepaymentPeriod::findOne(['loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
//                    if(false == $user_repayment_period){
//                        throw new Exception("获取分期数据失败！");
//                    }
//                    $log = new UserOrderLoanCheckLog();
//                    $log->order_id = $loan_record_id;
//                    $log->repayment_id = $repayment_period_id;
//                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
//                    $log->before_status = $user_repayment_period['status'];
//                    $log->operator_name = $username;
//                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
//                    $log->remark = $remark;
//                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
//                    if(isset($false_type)){
//                        switch($false_type){
//                            case 1:
//                                //客户要求驳回，那么重置为生息中
//                                $user_repayment_period->status = UserRepaymentPeriod::STATUS_WAIT;
//                                $user_repayment_period->updated_at = time();
//                                if(!$user_repayment_period->save()){
//                                    throw new Exception("操作失败！");
//                                }
//                                $log->after_status = UserRepaymentPeriod::STATUS_WAIT;
//                                if(!$log->save()){
//                                    throw new Exception("日志保存失败！");
//                                }
//                                break;
//                            case 2:
//                                //订单异常
//                                $user_repayment_period->status = UserRepaymentPeriod::STATUS_WAIT;
//                                $user_repayment_period->updated_at = time();
//                                if(!$user_repayment_period->save()){
//                                    throw new Exception("操作失败！");
//                                }
//                                $log->after_status = UserRepaymentPeriod::STATUS_WAIT;
//                                if(!$log->save()){
//                                    throw new Exception("日志保存失败！");
//                                }
//                                break;
//                            default:
//                                throw new Exception("驳回类型有误！");
//                                break;
//                        }
//
//                    }else{
//                        throw new Exception("参数丢失！");
//                    }
//                    break;

//                case UserLoanOrder::LOAN_TYPE_FQSC:
//                    if(empty($repayment_period_id)){
//                        throw new Exception("参数丢失！");
//                    }
//
//                    $user_repayment_period = UserRepaymentPeriod::findOne(['loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
//                    if(false == $user_repayment_period){
//                        throw new Exception("获取分期数据失败！");
//                    }
//                    $log = new UserOrderLoanCheckLog();
//                    $log->order_id = $loan_record_id;
//                    $log->repayment_id = $repayment_period_id;
//                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC;
//                    $log->before_status = $user_repayment_period['status'];
//                    $log->operator_name = $username;
//                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
//                    $log->remark = $remark;
//                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
//                    if(isset($false_type)){
//                        switch($false_type){
//                            case 1:
//                                //客户要求驳回，那么重置为生息中
//                                $user_repayment_period->status = UserRepaymentPeriod::STATUS_WAIT;
//                                $user_repayment_period->updated_at = time();
//                                if(!$user_repayment_period->save()){
//                                    throw new Exception("操作失败！");
//                                }
//                                $log->after_status = UserRepaymentPeriod::STATUS_WAIT;
//                                if(!$log->save()){
//                                    throw new Exception("日志保存失败！");
//                                }
//                                break;
//                            case 2:
//                                //订单异常
//                                $user_repayment_period->status = UserRepaymentPeriod::STATUS_WAIT;
//                                $user_repayment_period->updated_at = time();
//                                if(!$user_repayment_period->save()){
//                                    throw new Exception("操作失败！");
//                                }
//                                $log->after_status = UserRepaymentPeriod::STATUS_WAIT;
//                                if(!$log->save()){
//                                    throw new Exception("日志保存失败！");
//                                }
//                                break;
//                            default:
//                                throw new Exception("驳回类型有误！");
//                                break;
//                        }
//
//                    }else{
//                        throw new Exception("参数丢失！");
//                    }
//                    break;
                default:
                    throw new Exception("订单类型错误！");
                    break;
            }

            $transaction->commit();
            return [
                'code'=>0,
                'message'=>'操作成功',
            ];


        }catch(\Exception $e){
            $transaction->rollBack();
            return [
                'code'=>-1,
                'message'=>$e->getMessage(),
            ];
        }

    }

    /**
     * 扣款失败
     * @param $loan_record_id
     * @param $repayment_id
     * @param $repayment_period_id
     * @param $data
     * @return array
     */
    public function debitFailed($loan_record_id,$repayment_id,$repayment_period_id,$data,$remark='',$username=''){

        $transaction = Yii::$app->db_kdkj->beginTransaction();

        try{
            if(empty($loan_record_id)||empty($repayment_id)){
                throw new Exception("参数丢失！");
            }
            $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
            if(false == $user_loan_order){
                throw new Exception("获取订单数据失败！");
            }
            if($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE){
                throw new Exception("该订单已还款了！");
            }

            $order_type = $user_loan_order->order_type;
            switch($order_type){
                case UserLoanOrder::LOAN_TYPE_LQD:
                    $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$user_loan_order->id,'id'=>$repayment_id]);
                    if(false == $user_loan_order_repayment){
                        throw new Exception("获取分期数据失败！");
                    }
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $loan_record_id;
                    $log->repayment_id = $repayment_id;
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                    $log->before_status = $user_loan_order_repayment['status'];
                    $log->after_status = UserLoanOrderRepayment::STATUS_DEBIT_FALSE;
                    $log->operator_name = $username;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = $remark;
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
                    if(!$log->save()){
                        throw new Exception("日志保存失败！");
                    }

                    $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_DEBIT_FALSE;
                    $user_loan_order_repayment->updated_at = time();
                    if(!$user_loan_order_repayment->save()){
                        throw new Exception("操作失败！");
                    }


                    break;
                case UserLoanOrder::LOAN_TYPR_FZD:
                    if(empty($repayment_period_id)){
                        throw new Exception("参数丢失！");
                    }

                    $user_repayment_period = UserRepaymentPeriod::findOne(['loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
                    if(false == $user_repayment_period){
                        throw new Exception("获取分期数据失败！");
                    }
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $loan_record_id;
                    $log->repayment_id = $repayment_period_id;
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
                    $log->before_status = $user_repayment_period['status'];
                    $log->after_status = UserRepaymentPeriod::STATUS_REPAY_COMPLEING;
                    $log->operator_name = $username;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = $remark;
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
                    if(!$log->save()){
                        throw new Exception("日志保存失败！");
                    }
                    $user_repayment_period->status = UserRepaymentPeriod::STATUS_REPAY_COMPLEING;
                    $user_repayment_period->updated_at = time();
                    if(!$user_repayment_period->save()){
                        throw new Exception("操作失败！");
                    }

                    break;

                case UserLoanOrder::LOAN_TYPE_FQSC:
                    if(empty($repayment_period_id)){
                        throw new Exception("参数丢失！");
                    }

                    $user_repayment_period = UserRepaymentPeriod::findOne(['loan_order_id'=>$user_loan_order->id,'repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
                    if(false == $user_repayment_period){
                        throw new Exception("获取分期数据失败！");
                    }
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $loan_record_id;
                    $log->repayment_id = $repayment_period_id;
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC;
                    $log->before_status = $user_repayment_period['status'];
                    $log->after_status = UserRepaymentPeriod::STATUS_REPAY_COMPLEING;
                    $log->operator_name = $username;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = $remark;
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_KK;
                    if(!$log->save()){
                        throw new Exception("日志保存失败！");
                    }
                    $user_repayment_period->status = UserRepaymentPeriod::STATUS_REPAY_COMPLEING;
                    $user_repayment_period->updated_at = time();
                    if(!$user_repayment_period->save()){
                        throw new Exception("操作失败！");
                    }

                    break;
                default:
                    throw new Exception("订单类型错误！");
                    break;
            }

            $transaction->commit();

            return [
                'code'=>0,
                'message'=>'操作成功',
            ];


        }catch(\Exception $e){
            $transaction->rollBack();
            return [
                'code'=>-1,
                'message'=>$e->getMessage(),
            ];
        }


    }

    public function createUserContractInfo(UserLoanOrder $user_loan_order){
        $userContractInfo = new UserContractInfo();
        $userContractInfo->user_id = $user_loan_order['user_id'];
        $userContractInfo->order_id = $user_loan_order['id'];
        $userContractInfo->contract_type = $user_loan_order['order_type'];
        $userContractInfo->contract_status = 0;
        $userContractInfo->mail_status = 0;
        if($userContractInfo->save()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 财务发起扣款
     * @param unknown $order_id
     * @param unknown $operator_name
     * @throws \Exception
     */
    public function sendFinancialDebit($order_id,$operator_name){
        try {
			$transaction = Yii::$app->db_kdkj->beginTransaction();
            $user_loan_order = UserLoanOrder::find()->where(['id'=>$order_id])->one();
            $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id'=>$order_id])->one();
            if($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE
                    || $user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                throw new \Exception("");
            }
            $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
            $user_loan_order->operator_name = $operator_name;
            $user_loan_order->updated_at = time();
            if(!$user_loan_order->save()){
                $transaction->rollback();
                throw new \Exception("");
            }
            $user_loan_order_repayment->debit_times += 1;
            $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
            $user_loan_order_repayment->operator_name = $operator_name;
            $user_loan_order_repayment->updated_at = time();
            $user_loan_order_repayment->current_debit_money = $user_loan_order_repayment->principal+$user_loan_order_repayment->interests+$user_loan_order_repayment->late_fee-$user_loan_order_repayment->true_total_money;
            if(!$user_loan_order_repayment->save()){
                $transaction->rollback();
                throw new \Exception("");
            }
            $result = $this->getLqRepayInfo($user_loan_order_repayment['id']);
            if(!$result){
                $transaction->rollback();
                throw new \Exception("");
            }
            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
            $log->repayment_id = $user_loan_order_repayment->id;
            $log->before_status = $user_loan_order_repayment->status;
            $log->after_status = UserLoanOrderRepayment::STATUS_WAIT;
            $log->operator_name = $operator_name;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = "一键审核";
            $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;
            if(!$log->save()){
                $transaction->rollback();
                throw new \Exception("");
            }
            $transaction->commit();
            return true;
        } catch(\Exception $e){
            $transaction->rollback();
        }
        return false;
    }

    //获取零钱贷扣款所需的信息
    public function getLqRepayInfo($user_loan_order_repayment_id){

        $info = UserLoanOrderRepayment::findOne($user_loan_order_repayment_id);
        $total = $info['principal']+$info['interests']+$info['late_fee'];
        $repayment_money = $info['current_debit_money'];

        if($repayment_money > $total-$info['true_total_money']){
            return false;
        }
        $data = [];
        $data['user_id'] = $info['user_id'];
        $data['debit_card_id'] = $info['card_id'];
        $data['type'] = FinancialDebitRecord::TYPE_YGB_LQB;
        $data['repayment_id'] = $info['id'];
        $data['repayment_peroid_id'] = $info['debit_times'];
        $data['loan_record_id'] = $info['order_id'];
        if($repayment_money > 0){
            $data['plan_repayment_money'] = $repayment_money;
        }else{
            $data['plan_repayment_money'] = $total-$info['true_total_money'] ;
        }
        $data['plan_repayment_principal'] = $info['principal'];
        $data['plan_repayment_interest'] = $info['interests'];
        $data['plan_repayment_late_fee'] = $info['late_fee'];
        $data['plan_repayment_time'] = $info['plan_repayment_time'];
        $service = Yii::$container->get('financialService');
        $result = $service->createFinancialDebitRecord($data);
        if($result['code'] != 0){
            Yii::error([
                'line' => __LINE__,
                'method' => __METHOD__,
                'message' => $result['message'],
                'data'=>$data
            ],LogChannel::FINANCIAL_DEBIT);
            return false;
        }
        return true;
    }

    /**
     * 零钱包续期
     * @param integer $delay_log_id 延期日志ID
     * @param [] $repayment 还款记录
     * @param string $remark 备注
     */
    public function delayLqb($delay_log_id,$repayment,$remark=null){
        $where = ['id'=>$delay_log_id,'user_id'=>$repayment['user_id'],'order_id'=>$repayment['order_id'],'status'=>UserLoanOrderDelayLog::STATUS_DEFAULT];
        $delay_log = UserLoanOrderDelayLog::findOne($where);
        $ret = ['code'=>-1,'msg' => '续期失败'];
        if(!$delay_log){
            $ret['msg'] = '找不到对应记录';
            return $ret;
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $order = UserLoanOrder::findOne($repayment['order_id']);

            $time = time();
            //还款优先 进行垫付（避免计划还款时间 临界点问题）
            if(($order->fund_id != LoanFund::ID_KOUDAI) && $order->orderFundInfo && ($time >= $order->orderFundInfo->plan_payment_time) && ($order->orderFundInfo->plan_payment_time>0) ){
                $this->orderPrepay($order);
            }
            $flag = UserLoanOrderDelayLog::updateAll(['status'=>UserLoanOrderDelayLog::STATUS_SUCCESS,'updated_at'=>time()],$where);
            if($flag){
                $now = time();
                $day = $delay_log['delay_day'];
                $day_time = $day*86400;
                $sql = 'update '.UserLoanOrderRepayment::tableName().' set late_fee=0,overdue_day = 0,is_overdue=0,plan_fee_time=if(plan_fee_time>:now,plan_fee_time+:day_time,:plan_fee_time),status=:status,plan_repayment_time=plan_fee_time-86400,updated_at=:now where id=:id and status<>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                $flag = UserLoanOrderRepayment::getDb()->createCommand($sql,[':plan_fee_time'=>($now+$day_time),':plan_repayment_time'=>($now+$day_time-86400),
                        ':status'=>UserLoanOrderRepayment::STATUS_NORAML,':now'=>$now,':day_time'=>$day_time,
                        ':id'=>$repayment['id']])->execute();
                if($flag){
                    if(UserLoanOrderDelay::updateInfos($delay_log)){
                        $delay_log->remark = $remark !== null ? $remark : json_encode($repayment);
                        $delay_log->updated_at = time();
                        // 添加还款 主体 id
                        if($order->orderFundInfo){
                            if(($order->orderFundInfo->settlement_status==OrderFundInfo::SETTLEMENT_STATUS_FINISH && $order->orderFundInfo->settlement_type == OrderFundInfo::SETTLEMENT_TYPE_PREPAY) || (time() > $order->orderFundInfo->plan_payment_time))
                            {
                                $delay_log->repay_account_id =FundAccount::ID_REPAY_ACCOUNT_QIANCHENG;
                            }else{
                                $delay_log->repay_account_id = $order->loanFund->repay_account_id;
                            }
                        }else{//老数据  无资方订单 默认口袋
                            $delay_log->repay_account_id = FundAccount::ID_REPAY_ACCOUNT_DEFAULT;
                        }

                        $delay_log->save();
                        UserLoanOrder::updateAll(['status'=>UserLoanOrder::STATUS_LOAN_COMPLETE],'id='.$repayment['order_id'].' and status <>'.UserLoanOrder::STATUS_REPAY_COMPLETE);

                        $order_fund_info = OrderFundInfo::find()->where(['order_id'=>(int)$repayment['order_id']])->andWhere('`status`>=0')->orderBy('`id` DESC')->one();
                        if($order_fund_info) {
                            /* @var $order_fund_info OrderFundInfo */
                            $log = "发生续借，";
                            $order_fund_info->renew_fee += $delay_log->service_fee;
                            $order_fund_info->renew_service_fee += $delay_log->counter_fee;
                            $attributes = ['renew_fee', 'renew_service_fee'];
                            $log .= "更新属性：". json_encode($order_fund_info->getDirtyAttributes($attributes),JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);
                            $order_fund_info->updateAttributes($attributes);

                            OrderFundLog::add($order_fund_info->fund_id, $order_fund_info->order_id, $log);
                        }

                        $transaction->commit();
                        $collectionService = new CollectionService();
                        @$collectionService->collectionRenewOut($repayment['order_id'], 'self');
                        //调用零钱包续期成功发送提醒短信方法发送提醒短信
                        $delay_time = $now+$day_time;
                        $this->sendDelayLqbSuccessMessage($repayment,$day,$delay_time);
                        $ret['code'] = 0;
                        $ret['msg'] = '续期成功';
                        //触发展期成功事件

                        //$order = UserLoanOrder::findOne($repayment['order_id']);
                        $order->trigger(UserLoanOrder::EVENT_AFTER_DELAY_LQB, new \common\base\Event(['custom_data'=>[]]));
                        return $ret;
                    }
                }
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['msg'] = $e->getMessage();
        }
        return $ret;
    }

    /**
     * 零钱包续期成功发送提醒短信
     */
    private function sendDelayLqbSuccessMessage($repayment,$day){
        //增加续期成功短信提醒
        //查出发送短信来源（小钱包、极速荷包）
        try{
            $user_loan_order = UserLoanOrder::find()->where(['id'=>$repayment['order_id']])->one();
            $pre_word = '【'.UserLoanOrder::DistinguishFrom($user_loan_order->from_app).'】';
            //获取新的还款订单信息
            $repayment_order = UserLoanOrderRepayment::find()->where(['id'=>$repayment['id']])->one();
            $repayment_time = $repayment_order->plan_fee_time;
            $money = $user_loan_order->money_amount;
            $money = $money/100;  //分变成元
            $smsContent = $pre_word."恭喜，您的".sprintf('%1.2f',$money)."元借款已成功续期，续期天数：{$day}天，新的还款日为：".date('Y年m月d日',$repayment_time)."。";
            $loan_person = LoanPerson::findOne(['id'=>$repayment['user_id']]);
            $contact_phone = $loan_person->phone;
            MessageHelper::sendSMS($contact_phone, $smsContent);
        }catch(\Exception $e){
            yii::error($e->getMessage());
        }
    }

    /**
     * 事件处理
     * @param \common\base\Event $event 事件
     */
    public static function orderEventHandler($event) {
        $order = $event->sender;

        /* @var $order UserLoanOrder */
        $fromChannel = $order->getFromChannel();
		Yii::info("Order {$order->id} from channel:{$fromChannel}  handler event ,event name : {$event->name}");

        switch ($event->name) {
            case UserLoanOrder::EVENT_AFTER_APPLY_ORDER://申请订单后
                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX, $order->id]);
                $queueEvent = 'APPLY_SUCCESS';
                $order_status = '01';
                break;
            case UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED://审核拒绝
                $queueEvent = 'REVIEW_REJECTED';
                $order_status = '02';
                break;
            case UserLoanOrder::EVENT_AFTER_REVIEW_PASS://审核通过
                $queueEvent = 'REVIEW_PASS';
                break;
            case UserLoanOrder::EVENT_AFTER_PAY_SUCCESS://放款成功
                $queueEvent = 'PAY_SUCCESS';
//                RedisQueue::push([RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>$event->name])]);
                $order_status = '04';
                break;
            case UserLoanOrder::EVENT_AFTER_DELAY_LQB://展期成功
                $queueEvent = 'DELAY_SUCCESS';
                break;
            case UserLoanOrder::EVENT_AFTER_PAY_REJECTED://放款失败
                $queueEvent = 'PAY_REJECTED';
                $order_status = '03';
                break;
           case UserLoanOrder::EVENT_AFTER_REPAY_FAIL://还款失败
               $queueEvent = 'REPAY_FAIL';
               break;
           case UserLoanOrder::EVENT_AFTER_REPAY_OVERDUE://已逾期
               $queueEvent = 'REPAY_OVERDUE';
               break;
            case UserLoanOrder::EVENT_AFTER_REPAY_SUCCESS://还款成功
                $queueEvent = 'REPAY_SUCCESS';
                $order_status = '04';
                // RedisQueue::push([RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>$event->name])]);
                break;
//            case UserLoanOrder::EVENT_AFTER_SET_FUND://设置了资方
//                RedisQueue::push([RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>$event->name])]);
//                break;
//            case UserLoanOrder::EVENT_AFTER_CHANGE_FUND://改变了资方
//                RedisQueue::push([RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>$event->name])]);
//                break;
            default:
                break;
        }

        if($fromChannel && !empty($queueEvent)) {
            RedisQueue::push([RedisQueue::LIST_CHANNEL_FEEDBACK, json_encode([
                'channel'=>$fromChannel,//渠道
                'event'=>$queueEvent,//队列的事件
                'order_id'=>$order->id,//极速荷包订单ID
                'data'=>$event->custom_data
            ])]);
        }
    }

        /**
     * 处理慢就赔红包逻辑
     */
    public static function orderCouponEventHandler($event) {
    }

    /**
     * 审核通过
     * @param UserLoanOrder $order 订单模型
     * @param [] $update_order_attributes 更新的订单资料 可使用 格式：['attr1','attr2'] 或 ['attr1'=>'attrVal1','attr2'=>'attrVal2']
     * @param UserOrderLoanCheckLog $log 需要插入的日志记录
     * @param string $operator 操作人
     * @return [] 成功：["code"=>0]  失败示例:["code"=>-1,"message"=>"找不到对应用户"]
     */
    public function reviewPass(UserLoanOrder $order, $update_order_attributes, $log, $operator) {
        if ($order->card_id) { //有银行卡ID
            if(!$order->cardInfo) {
                return [
                    'code' => -1,
                    'message' => '订单存在card_id 但银行卡不存在',
                ];
            }

            /* @var $fund_service \common\services\FundService */
            $fund_service = Yii::$container->get('fundService');
            $ret = $fund_service->orderAutoDispatch($order, $update_order_attributes, $log, $operator);
        }
        else { //没有银行卡ID
           return [
               'code' => -1,
               'message' => '未绑定银行卡',
           ];
        }

        if ($ret['code'] == 0) {
            //触发审核通过事件
            $order->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_PASS, new \common\base\Event(['custom_data'=>['remark'=>$log->remark]]));
            // 触发慢就赔事件
//             $order->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_PASS_SLOW, new \common\base\Event());
        }
        else {
            \yii::warning( sprintf('orderservie_reviewpass_2598 %s, %s', json_encode([
                'order_id' => $order->id,
                'update_attributes' => $update_order_attributes,
            ]), json_encode($ret) ), LogChannel::FINANCIAL_PAYMENT);
        }

        return $ret;
    }

    /**
     * 订单垫付
     * @param UserLoanOrder $order 订单模型
     * @param integer $amount 垫付金额
     * @param integer $time 垫付时间
     * @return []
     * $amount 含 逾期 服务费
     *
     */
    public function orderPrepay($order, $amount=null, $time=null) {
        $ret=[
            'code'=>0
        ];
        if(!$order->orderFundInfo) {
            $ret = ['code'=> ErrCode::ORDER_FUND_INFO_NOT_FOUND, 'message'=>'找不到订单对应的资方信息数据'];
            goto RETURN_RET;
        }

        $now = time();
        $prepay_time = $time ? $time : $now;

        if( ($order->orderFundInfo->settlement_status==OrderFundInfo::SETTLEMENT_STATUS_FINISH) && ($order->orderFundInfo->settlement_time<$prepay_time) ) {
            $ret = ['code'=> ErrCode::ORDER_FUND_SETTLEMENT_FINISH, 'message'=>"订单资方信息数据状态为 已结算, 调用垫付失败"];
            goto RETURN_RET;
        }

        //未还的本金
        $unpay_amount = $order->userLoanOrderRepayment->principal + $order->userLoanOrderRepayment->late_fee + $order->userLoanOrderRepayment->interests + $order->userLoanOrderRepayment->coupon_money - $order->userLoanOrderRepayment->true_total_money;

        //垫付 时间 至  计划垫付 时间段内 的  还款    不加入 垫付 金额

        $log = "发生垫付， ";

        $order->orderFundInfo->settlement_type = OrderFundInfo::SETTLEMENT_TYPE_PREPAY;
        $order->orderFundInfo->prepay_time = $prepay_time;
        $order->orderFundInfo->settlement_time = $prepay_time;
        $order->orderFundInfo->settlement_status = OrderFundInfo::SETTLEMENT_STATUS_FINISH;
        $order->orderFundInfo->repay_account_id = FundAccount::ID_REPAY_ACCOUNT_QIANCHENG; //还款  主体变更

        //真实垫付金额
        $repay_after_plan_prepay_amount = intval(OrderFundRepaymentLog::find()->select('SUM(repay_principal)')->where('`order_id`='.(int)$order->id.' AND `fund_id`='.(int)$order->fund_id.' AND created_at>'.$prepay_time.' AND `status`>=0')->scalar());
        $order->orderFundInfo->prepay_amount = $amount ? $amount : ( $order->userLoanOrderRepayment->principal -(($order->userLoanOrderRepayment->principal - $unpay_amount) - $repay_after_plan_prepay_amount ) );
        if($order->orderFundInfo->prepay_amount<=0) {
            $ret = ['code'=> ErrCode::ORDER_FUND_PREPAY_AMOUNT_INVALID, 'message'=>"垫付金额为 {$order->orderFundInfo->prepay_amount} 未付金额为 {$unpay_amount} 超过期限还款金额为 {$repay_after_plan_prepay_amount} 不能小于或等于0"];
            goto RETURN_RET;
        }
        $attributes = ['prepay_amount','prepay_time','settlement_status','settlement_time','overdue_interest','repay_account_id','settlement_type'];
        $dirty_attributes = $order->orderFundInfo->getDirtyAttributes($attributes);
        $log .= "更新属性：".json_encode($dirty_attributes,JSON_UNESCAPED_UNICODE);

        $order->orderFundInfo->updateAttributes($attributes);

        //大客户 回滚 当天额度
        if($order->loanFund->type == LoanFund::TYPE_BIG_CLIENT) {//处理大客户的额度
            $log .= 'T+1 到 T+6 时间段 垫付订单,大客户 回滚 额度金额'.$order->orderFundInfo->prepay_amount;
            //回滚当天额度
            $order->loanFund->increaseQuota($order->orderFundInfo->prepay_amount, date('Y-m-d'), 0);
        }

        OrderFundLog::add($order->fund_id, $order->id, $log);

        RETURN_RET:
        return $ret;
    }

    /**
     * 订单还款 部分还款、全部还款
     *
     *
     * @param UserLoanOrder $order 订单模型
     * @param integer $amount 还款金额
     * @param string $operator 操作人
     * @param integer $creditId 还款流水id
     *
     */
    private function orderRepayFundOperation($order, $amount, $operator,$credit_money_log_id=null) {
        if($order->orderFundInfo) {
            try {
                $order->orderFundInfo->user_repay_amount += $amount;
                $attributes = ['user_repay_amount'];
                if($order->status==UserLoanOrder::STATUS_REPAY_COMPLETE) {//完整还款
                    $log = "发生全部还款，操作人：{$operator} ";
                    if($order->orderFundInfo->settlement_status != OrderFundInfo::SETTLEMENT_STATUS_FINISH) {
                        $order->orderFundInfo->settlement_status = OrderFundInfo::SETTLEMENT_STATUS_FINISH;
                        $order->orderFundInfo->status = OrderFundInfo::STATUS_SETTLEMENT_FINISH;
                        $order->orderFundInfo->settlement_time = time();
                        $order->orderFundInfo->settlement_type = OrderFundInfo::SETTLEMENT_TYPE_USER_REPAY;
                        $attributes = array_merge($attributes,['settlement_status', 'settlement_time', 'status', 'settlement_type']);
                    }
                } else {//部分还款
                    $log = "发生部分还款，操作人：{$operator}";
                }

                $order->orderFundInfo->discount = $order->userLoanOrderRepayment->coupon_money;

                // 逾期利息>逾期服务费>本金 优先级  start  @auther czd
                $interestRedu = $order->orderFundInfo->cacl_overdue_interest-$order->orderFundInfo->overdue_interest;// 判断 应收逾期利息是否  已还清

                if($interestRedu >0){//未还清
                    if($amount>$interestRedu){//够还
                        $order->orderFundInfo->overdue_interest = $order->orderFundInfo->cacl_overdue_interest;
                        $can_overdue_interest = $amount-$interestRedu;
                    }else{ //不够还
                        $order->orderFundInfo->overdue_interest +=$amount;
                        $can_overdue_interest = $amount - $order->orderFundInfo->overdue_interest;  //剩余可还的金额
                    }
                }else{
                    $can_overdue_interest = $amount;
                }

                $show_overdue_interest = ($order->userLoanOrderRepayment->late_fee - $order->orderFundInfo->overdue_interest) - $order->orderFundInfo->overdue_fee;//代还逾期服务费

                if($show_overdue_interest > 0){//未还清
                    if($can_overdue_interest >$show_overdue_interest) {//够还
                        $order->orderFundInfo->overdue_fee = $order->userLoanOrderRepayment->late_fee - $order->orderFundInfo->overdue_interest;
                    } else {
                        $order->orderFundInfo->overdue_fee +=$can_overdue_interest;
                    }
                }// 逾期利息>逾期服务费>本金 优先级 end

                $attributes = array_merge($attributes,['discount', 'overdue_interest', 'overdue_fee']);

                $log_attributes = [];
                foreach($attributes as $attribute) {
                    $log_attributes[$attribute] = $order->orderFundInfo->getOldAttribute($attribute);
                }

                $dirty_attributes =  $order->orderFundInfo->getDirtyAttributes($attributes);
                $log .= "原属性：".json_encode($log_attributes,JSON_UNESCAPED_UNICODE)
                    ." 更新的属性：". json_encode($dirty_attributes,JSON_UNESCAPED_UNICODE);

                $order->orderFundInfo->updateAttributes($attributes);

                OrderFundLog::add($order->fund_id, $order->id, $log);

                $order_fund_repayment_log = OrderFundRepaymentLog::add($order, $amount, $credit_money_log_id);

                if($order->loanFund->type == LoanFund::TYPE_BIG_CLIENT) {//处理大客户的额度
                    $order->loanFund->getService()->onOrderRepay($order, $order_fund_repayment_log, $operator);
                }

            } catch (\Exception $ex) {
                Yii::error("还款异常：{$ex->getFile()} {$ex->getLine()}错误：{$ex->getMessage()}");
            }
        }
    }

    /**
     * 取消续借（自动加锁）
     * @param UserLoanOrder $order_id 订单ID
     * @param integer $times 取消续期次数
     * @return []
     */
    public function cancelRenewUseLock($order_id, $times) {
        $lock_name = 'orderRenew'.$order_id;
        if(!($lock = Lock::get($lock_name, 30))) {
            $ret = [
                'code'=> ErrCode::ORDER_LOCK,
                'message'=>'该订单已被锁定，不能同时进行续期操作'
            ];
            goto RETURN_RET;
        }

        $order = UserLoanOrder::findOne((int)$order_id);
        $ret = $this->cancelRenew($order, $times);

        RETURN_RET:
        if(!empty($lock_name) && !empty($lock)) {
            Lock::del($lock_name);
        }
        return $ret;
    }

    /**
     * 取消续借 需要在外层加锁 避免同时进行取消续期的操作
     * @param UserLoanOrder $order 订单模型
     * @param integer $times 取消续期次数
     */
    public function cancelRenew($order, $times) {
        //待补充状态的判断数据
        if(!$order || !($delay = UserLoanOrderDelay::findOne(['order_id'=>$order->id])) || !($repayment = $order->userLoanOrderRepayment) || in_array($repayment->status, [UserLoanOrderRepayment::STATUS_REPAY_COMPLETE]) ) {
            $ret = [
                'code'=> ErrCode::INVALID_PARAMS,
                'message'=>'订单不存在或不可取消续期'
            ];
            goto RETURN_RET;
        }

        $logs = UserLoanOrderDelayLog::find()->where(['user_id'=>(int)$order->user_id,'order_id'=>(int)$order->id,'status'=>UserLoanOrderDelayLog::STATUS_SUCCESS])->orderBy('`id` DESC')->limit($times)->all();//updateAll(['status'=>UserLoanOrderDelayLog::STATUS_SUCCESS,'updated_at'=>time()],$where);
        if(count($logs)<$times || $times<=0) {
            $ret = [
                'code'=> ErrCode::ORDER_CANCEL_RENEW_TIMES_INVALID,
                'message'=>"该订单最少取消续期 1 次，最多取消续期 ".count($logs)." 次"
            ];
            goto RETURN_RET;
        }

        $order_fund_info = $order->orderFundInfo;

        //取消续期 SQL
        //update kdkj.tb_user_loan_order_repayment set plan_repayment_time=plan_repayment_time-7*86400,plan_fee_time=plan_fee_time-7*86400 where order_id=xxx;
        $db = UserLoanOrderDelayLog::getDb();
        $transaction = $db->beginTransaction();
        try {
            foreach($logs as $log) {
                /* @var $log UserLoanOrderDelayLog */
                $log->updateAttributes(['status'=> UserLoanOrderDelayLog::STATUS_REMOVED]);
                $repayment->plan_repayment_time -= $log->delay_day*86400;
                $repayment->plan_fee_time -= $log->delay_day*86400;
                if( $repayment->plan_fee_time<($order->loan_time+$order->loan_term*86400) ) {
                    throw new \Exception('取消续期过多（计划结息时间小于订单本来结息时间）', ErrCode::ORDER_CANCEL_RENEW_TIMES_INVALID);
                }

                if($order_fund_info) {
                    $order_fund_info->renew_fee -=  $log->service_fee;
                    $order_fund_info->renew_service_fee -=  $log->counter_fee;
                    if( $order_fund_info->renew_fee<0 || $order_fund_info->renew_service_fee<0 ) {
                        throw new \Exception("取消续期过多（资方分账续期手续费为{$order_fund_info->renew_fee}，服务费为 {$order_fund_info->renew_service_fee} 金额小于0）", ErrCode::ORDER_CANCEL_RENEW_TIMES_INVALID);
                    }
                }

                $delay->service_fee -=  $log->service_fee;
                $delay->counter_fee -=  $log->counter_fee;
                $delay->delay_day -=  $log->delay_day;
                $delay->delay_times--;
                if($delay->service_fee<0 || $delay->counter_fee<0 || $delay->delay_day<0 || $delay->delay_times<0) {
                    throw new \Exception("取消续期过多（续期记录 手续费{$delay->service_fee} 服务费{$delay->counter_fee} 延期天数{$delay->delay_day} 延期次数{$delay->times} 值小于0 ");
                }
                $delay->updateAttributes(['service_fee','counter_fee','delay_day','delay_times']);

            }
            if($order_fund_info) {
                $order_fund_info->updateAttributes(['renew_service_fee', 'renew_fee']);
            }

            $repayment->updateAttributes(['plan_repayment_time','plan_fee_time']);

            $transaction->commit();
            $ret = ['code'=>0];
        } catch (\Exception $ex) {
            $transaction->rollBack();
            $ret = [
                'code'=>$ex->getCode() ? $ex->getCode() : -1,
                'message'=>$ex->getMessage()
            ];
        }

        RETURN_RET:
        return $ret;
    }

    /**
     * 将订单置为生息中
     * @param int $id
     * @return array
     */
    public static function resetOrderInterest(int $id)
    {
        $db = UserLoanOrderDelayLog::getDb();
        $transaction = $db->beginTransaction();

        try {
            /*订单*/
            $order = UserLoanOrder::findOne(['id'=>$id,'status'=>UserLoanOrder::STATUS_REPAY_COMPLETE]);
            if(!$order)
                throw new Exception('订单找不到',-1);

            $up = UserLoanOrder::updateAll(['status' => UserLoanOrder::STATUS_LOAN_COMPLING], 'id = '.$id.' AND status='.UserLoanOrder::STATUS_REPAY_COMPLETE);
            if(!$up)
                throw new Exception('状态更新失败',-2);

            /*还款记录*/
            $repay = UserLoanOrderRepayment::findOne(['order_id'=>$order->id]);
            if(!$repay)
                throw new Exception('还款记录找不到',-1);

            $save = UserLoanOrderRepayment::updateAll(
                [
                    'status' => UserLoanOrderRepayment::STATUS_NORAML,
                    'true_total_money' =>  $repay->coupon_money,
                    'true_repayment_time' =>  0,
                ], 'order_id = '.$order->id);

            if(!$save) {
                throw new Exception('还款记录状态更新失败',-2);
            }

            if($order->orderFundInfo && ($order->orderFundInfo->settlement_status==OrderFundInfo::SETTLEMENT_STATUS_FINISH) &&
                ($order->orderFundInfo->settlement_type == OrderFundInfo::SETTLEMENT_TYPE_USER_REPAY) ) {

                $order->orderFundInfo->updateAttributes([
                    'settlement_status'=> OrderFundInfo::SETTLEMENT_STATUS_NO,
                    'settlement_time'=> 0,
                    'settlement_type'=>OrderFundInfo::SETTLEMENT_TYPE_NO,
                    'user_repay_amount'=>0,
                ]);
                OrderFundLog::add($order->fund_id, $order->id, "重置订单为生息中，修改状态为未结算");
            }

            $transaction->commit();

            return ['code'=>0, 'message'=>'保存成功'];

        } catch (Exception $e) {

            $transaction->rollBack();

            $ret = [
                'code'=>$e->getCode() ? $e->getCode() : -1,
                'message'=>$e->getMessage()
            ];

            return $ret;
        }
    }


    /**
     * 取消部分还款
     * @param int $id
     * @return array
     */
    public static function cancelPartRepay(int $id)
    {
        $db = UserLoanOrderDelayLog::getDb();
        $transaction = $db->beginTransaction();

        try {
            /*订单*/
            $order = UserLoanOrder::findOne($id);
            /*还款记录*/
            if(!$order || !($repay = $order->userLoanOrderRepayment))
                throw new Exception('订单或还款记录找不到',-1);


            $save = UserLoanOrderRepayment::updateAll(['true_total_money' =>  $repay->coupon_money], 'order_id = '.$id);

            if(!$save) {
                throw new Exception('状态更新失败',-2);
            }

            if($order->orderFundInfo ) {
                $order->orderFundInfo->updateAttributes([
                    'user_repay_amount'=> 0,
                    'overdue_fee'=>0,
                    'overdue_interest'=>0
                ]);
                OrderFundLog::add($order->fund_id, $order->id, "取消部分还款，修改用户还款值为0");
                OrderFundRepaymentLog::updateAll(['status'=> OrderFundRepaymentLog::STATUS_REMOVED],'order_id='.(int)$order->id.' AND fund_id='.(int)$order->fund_id);
            }

            $transaction->commit();

            return ['code'=>0, 'message'=>'保存成功'];

        } catch (Exception $e) {

            $transaction->rollBack();

            $ret = [
                'code'=>$e->getCode() ? $e->getCode() : -1,
                'message'=>$e->getMessage()
            ];

            return $ret;
        }

    }


     /**
     * 添加后台用户置为已还款
     * ForceFinishDebit
     */
    public function lockAdminForceFinishDebit($order_id){
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"admin:force:finish:debit",$order_id);
        $lock_time = 24 * 60 * 60;
        if (1 == RedisQueue::inc([$lock_key,1])) {
            RedisQueue::expire([$lock_key,$lock_time]);
            return true;
        }else{
            RedisQueue::expire([$lock_key,$lock_time]);
        }
        return false;
    }

    /**
     * 释放后台用户置为已还款
     */
    public function releaseAdminForceFinishDebitLock($order_id){
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"admin:force:finish:debit",$order_id);
        RedisQueue::del(["key"=>$lock_key]);
    }

    /**
     * 判断用户是否首单
     * @param $user_id 用户id
     * @param $type 1:申请成功并且放款成功，2：申请成功
     */
    public static function checkFirstOrderByUid($user_id, $type = 1){
        $flag = true;
        $now = time();
        $conditions = sprintf("user_id=%s AND order_time < %s ",$user_id, $now);
        if($type == 1){
            $conditions .= " AND `status` >= 3";
        }
        $total = UserLoanOrder::find()->where($conditions)->count();
        if ($total > 1) {
            $flag = false;
        }
        return $flag;

    }

    /**
     * 判断用户是否有完整的订单记录（借款还款的成功订单的）
     * @param $user_id
     * @return mixed
     */
    public static function checkHasSuccessOrderByUid($user_id){
        return  UserLoanOrderRepayment::find()->where(['user_id'=>$user_id,'status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])->select('id')->one();
    }

    /** 添加减免滞纳金日志
     * @param $repayment
     * @param $money
     * @param $operator [id  username]
     * @return bool
     */
    public function setRidOverdueMoneyLog($repayment, $operator, $type = 1 ,$remark){
        try {
            if($repayment['principal'] > $repayment['true_total_money']){
                throw new Exception('实际还款金额不能小于借款本金');
            }
            $type = isset($type) ? $type : 1;
            $money = max($repayment['principal']+$repayment['interests']+$repayment['late_fee'] - $repayment['true_total_money'], 0);
            $rid_overdue_log = RidOverdueLog::find()->where(['repayment_id'=>$repayment['id']])->one();
            if(!$rid_overdue_log){
                $rid_overdue_log = new RidOverdueLog;
            }
            $rid_overdue_log->repayment_id = $repayment['id'];
            $rid_overdue_log->type = $type;
            $rid_overdue_log->order_id = $repayment['order_id'];
            $rid_overdue_log->rid_money = $money;
            $rid_overdue_log->operator_id = $operator['id'] ? $operator['id'] : 0;
            $rid_overdue_log->operator_name = $operator['username'] ? $operator['username'] : '';
            $rid_overdue_log->remark = $remark;
            if(!$rid_overdue_log->save()){
                throw new Exception('保存或更新失败');
            }
            return true;
        } catch(\Exception $e){

        }
        return false;
    }

    public function optimizedCallbackDebitMoneyModify($loan_record_id,$repayment_id,$money,$remark='',$username='',$params=[]){
        $user_loan_order = UserLoanOrder::findOne(['id'=>$loan_record_id]);
        if (false == $user_loan_order) {
            \yii::warning("callbackDebitMoney_1565 {$loan_record_id} not_exists.");
            return [
                'code'=>-1,
                'message'=>'该订单不存在',
            ];
        }
        if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
            \yii::warning("callbackDebitMoney_1572 {$loan_record_id} repay_complete.");
            return [
                'code'=>-1,
                'message'=>'该订单已还款了',
            ];
        }
        $ret = self::_optimizedDebitOrderLqdModify($user_loan_order, $repayment_id, $money, $remark, $username, $params);
        return [
            'code'=>$ret['code'],
            'message'=>$ret['message'],
        ];
    }
    private function _optimizedDebitOrderLqdModify($user_loan_order, $repayment_id, $money, $remark, $username, $params = []){
        $time = time();
        $boolForceFinish = $params && isset($params['boolForceFinish']) && $params['boolForceFinish'] ? true : false; //是否直接置为已还款
        $operationType = $params && isset($params['operationType']) ? $params['operationType'] : UserOrderLoanCheckLog::REPAY_KK;//是否直接置为已还款
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $user_id = $user_loan_order->user_id;
            $order_id = $user_loan_order->id;
            $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id,'id'=>$repayment_id]);
            if (false == $user_loan_order_repayment) {
                throw new \Exception('获取零钱贷分期总表数据失败',-1);
            }
            $loan_person = LoanPerson::findOne(['id'=>$user_loan_order->user_id]);
            //口袋记账和加班管家前三天不扣滞纳金
            if (in_array($loan_person->source_id,[LoanPerson::PERSON_SOURCE_KDJZ,LoanPerson::PERSON_SOURCE_JBGJ]) && ($user_loan_order_repayment->late_day <= 3)) {
                $total = $user_loan_order_repayment->principal;
            } else {
                $total = $user_loan_order_repayment->principal + $user_loan_order_repayment->interests + $user_loan_order_repayment->late_fee;
            }
            $user_loan_order_repayment->updated_at = time();
            $user_loan_order_repayment->true_total_money =  $user_loan_order_repayment->true_total_money+$money;
            if ($boolForceFinish || $user_loan_order_repayment->true_total_money >= $total) {
                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                $user_loan_order_repayment->true_repayment_time = time();
            } else {
                $user_loan_order_repayment->status = $user_loan_order_repayment->is_overdue && $user_loan_order_repayment->overdue_day > 0 ? UserLoanOrderRepayment::STATUS_OVERDUE : UserLoanOrderRepayment::STATUS_NORAML;
            }
            $user_loan_order_repayment->current_debit_money = max(($user_loan_order_repayment->current_debit_money - $money),0);
            if (!$user_loan_order_repayment->save()) {
                throw new \Exception('操作零钱贷分期还款表失败',-1);
            }
            //扣款成功回调添加还款日志列表
            $order_uuid = isset($params['order_uuid']) ? $params['order_uuid'] : null;
            $attrs = [
                'type' => UserCreditMoneyLog::TYPE_DEBIT,
                'payment_type' => isset($params['repayment_type']) ? $params['repayment_type'] : UserCreditMoneyLog::PAYMENT_TYPE_AUTO,
                'user_id' => $user_id,
                'order_id' => $order_id,
                'order_uuid' => $order_uuid,
                'pay_order_id' => isset($params['pay_order_id']) ? $params['pay_order_id'] : '',
                'success_repayment_time' => $time,
                'img_url' => isset($params['img_url']) ? $params['img_url'] : '',
                'remark' => $remark,
                'status' => UserCreditMoneyLog::STATUS_SUCCESS,
                'operator_money' => $money,
                'operator_name' => $username,
                'card_id' => isset($params['card_id']) ? $params['card_id'] : 0,
                'debit_channel' => isset($params['debit_channel']) ? $params['debit_channel'] : 0,
                'debit_account' => isset($params['debit_account']) ? $params['debit_account'] : '',
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $credit_money_log = UserCreditMoneyLog::saveRecord($attrs);
            if ($credit_money_log->id) {
                $credit_money_log_id = $credit_money_log->id;
            } else {
                $credit_money_log_id = isset($params['UserCreditMoneyLogId']) ? $params['UserCreditMoneyLogId'] : 0;
            }
            //添加订单审核记录
            $log = new UserOrderLoanCheckLog();
            $log->order_id = $user_loan_order['id'];
            $log->repayment_id = $repayment_id;
            $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
            $log->before_status = $user_loan_order_repayment['status'];
            $log->after_status = $user_loan_order_repayment->status;
            $log->operator_name = $username;
            $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
            $log->remark = $remark;
            $log->operation_type = $operationType;
            if (!$log->save()) {
                throw new \Exception('生成日志表失败',-1);
            }
            //更新借款订单表
            $user_loan_order->updated_at = time();
            if ($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
                $collectionService = new CollectionService();
                @$collectionService->collectionPaybackOut($user_loan_order_repayment['order_id'], 'self');
            } else {
                $user_loan_order->status = UserLoanOrder::STATUS_PARTIALREPAYMENT;
            }
            if(!$user_loan_order->save()){
                throw new \Exception("操作零钱贷订单表还款表失败",-1);
            }
            //更新用户额度表
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit = $creditChannelService->getCreditTotalByUserAndOrder($user_id, $order_id);
            if (false == $user_credit) {
                throw new \Exception('获取客户额度表数据失败',-1);
            }
            $user_credit->updated_at = time();
            if ($user_loan_order->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
                $user_credit->used_amount = $user_credit->used_amount - $user_loan_order_repayment->principal;
                $user_credit->locked_amount = 0;
                if (!$user_credit->save()) {
                    throw new \Exception('更新客户额度表失败',-1);
                }
            }
            $transaction->commit();
            $transaction = null;
            //更新账号流水表
            //第三步：资金流水
            if ($user_loan_order_repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {//完全还款
                $user_credit_log = new  UserCreditLog();
                $user_credit_log->user_id = $user_id;
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_KK;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $money;
                $user_credit_log->apr = $user_loan_order_repayment->apr;
                $user_credit_log->interests = "";
                $user_credit_log->to_card = "";
                $user_credit_log->remark = "";
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = "";
                $user_credit_log->total_money=$user_credit->amount;
                $user_credit_log->used_money=$user_credit->used_amount;
                $user_credit_log->unabled_money=$user_credit->locked_amount;
                if (!$user_credit_log->save()) {
                    throw new \Exception("UserCreditLog 记录更新失败",-1);
                }
            }
            return [ 'code'=>0, 'message'=>'操作成功'];
        } catch (\Exception $e){
            if (!empty($transaction)) {
                $transaction->rollBack();
            }
            if (YII_ENV_PROD) {
                UserLoanOrder::sendSMS(NOTICE_MOBILE, $e->getMessage());
//                UserLoanOrder::sendSMS(NOTICE_MOBILE2, $e->getMessage());
            }
            return [
                'code'=>$e->getCode() ? $e->getCode() : -1,
                'message'=>$e->getMessage(),
            ];
        }
    }

    /**
     * @name 借款记录明细
     * return loan_time申请时间
     * return apply_money申请金额
     * return loan_time借款期限
     * return loan_money到账金额
     * return buy_购买征信报告支付
     * return 购物后到账
     * return 优惠卷减免金额
     * return 放款日期
     * return 合约还款日期
     * return 实际还款日
     * return 应还金额
     */
    public static function actionLoanInfoDetail($order_info,$order_repayment_info){
        //借款信息
        $data['detail_loan_time'] = sprintf("%.2f",$order_info['created_at']/100);
        $data['detail_loan_money'] = sprintf("%.2f",$order_info['money_amount']/100);
        $data['detail_loan_time'] = '1期（共'.$order_info['loan_term'].'天）';
        $data['detail_all_loan_money'] = sprintf("%.2f",$order_info['money_amount']/100);
        $data['detail_buy_loan_money'] = sprintf("%.2f",$order_info['counter_fee']/100);
        $data['detail_buy_true_loan_money'] = sprintf("%.2f",($order_info['money_amount']-$order_info['counter_fee'])/100);
        $data['detail_free_money'] = sprintf("%.2f",0);
        //还款信息
        $data['order_loan_time'] = date('Y-m-d',$order_repayment_info['loan_time']);
        $data['order_repayment_time'] = date('Y-m-d',$order_repayment_info['plan_fee_time']);
        $data['order_true_repayment_time'] = date('Y-m-d',$order_repayment_info['true_repayment_time']);
        $data['order_pay_all_money'] = sprintf("%.2f",($order_repayment_info['principal']+$order_repayment_info['interests']+$order_repayment_info['late_fee'])/100);//本金+滞纳金+利息
        return $data;
    }
}
