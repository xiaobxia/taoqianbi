<?php
namespace console\controllers;

use common\base\LogChannel;
use common\helpers\CommonHelper;
use common\models\LoanOverdueLog;
use common\models\UserInterestLog;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Exception;
use common\models\CardInfo;
use common\models\UserLoanOrderRepayment;
use common\models\LoanPerson;
use common\models\fund\LoanFund;
use common\helpers\GlobalHelper;



class LoanCollectionController extends BaseController
{

    /**
     * 计算零钱包利息或者违约金,并自动提交订单
     */
    public function actionCalculationInterest($mod_base = 0,$mod_left = 0){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $creditChannelService = \Yii::$app->creditChannelService;
        $today = strtotime(date("Y-m-d",time())); // 今天

        $error_ids = '';
        $start_id = 0;

        $query = UserLoanOrderRepayment::find()
            ->select('id')
            ->where(['!=', 'status', UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
            ->andWhere(['<', 'interest_time', $today])
            ->andWhere(['<=', 'plan_repayment_time', time()]);


        if ($mod_base > 0) {
            $query->andWhere(" id % {$mod_base} = {$mod_left} ");
        }

        $all_ids = $query->andWhere(['>', 'id', $start_id])->orderBy('id asc')->asArray()->limit(5000)->all();

        if(!$all_ids){
            CommonHelper::stdout('无可执行数据');
            return 0;
        }

        $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
        $count = count($all_ids);
        while($all_ids){
            foreach($all_ids as $id){
                $id = $id['id'];
                CommonHelper::stdout( sprintf("[%s] repayment_id %s start\n", date('ymd H:i:s'),$id) );
                $item = UserLoanOrderRepayment::findOne(['id'=>$id]);
                $status = $item->status;
                if($status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                    CommonHelper::stdout( sprintf("[%s] repayment_id %s status repay complete , process continue.\n", date('ymd H:i:s'),$id) );
                    continue;
                }
                if($today == strtotime(date('Y-m-d',$item->interest_time))){//该利息今天已经更新过，不需要更新
                    CommonHelper::stdout( sprintf("[%s] repayment_id %s interest time is updated , process continue.\n", date('ymd H:i:s'),$id) );
                    continue;
                }

                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    $user_interest_log = new UserInterestLog();
                    $before_late_fee = $item->late_fee;
                    $before_interests = $item->interests;
                    $order_id = $item->order_id;
                    $repayment_id = $item->id;
                    $plan_repayment_time = strtotime(date('Y-m-d',$item->plan_repayment_time));
                    if(strtotime(date('Y-m-d',$plan_repayment_time+24*3600)) < $today){ //逾期计息
                        CommonHelper::stdout( sprintf("[%s] repayment_id %s is overdue , calc overdue info.\n", date('ymd H:i:s'),$id) );


                        //插入逾期流水
                        $loan_overdue_log = new LoanOverdueLog();
                        $loan_overdue_log->user_id = $item->user_id;
                        $loan_overdue_log->order_id = $item->order_id;
                        $loan_overdue_log->repayment_id = $item->id;
                        $loan_overdue_log->overdue_total = $item->principal+$item->interests+$item->late_fee-$item->true_total_money;
                        $loan_overdue_log->overdue_principal = $item->principal;
                        $loan_overdue_log->overdue_interests = $item->interests;
                        $loan_overdue_log->overdue_late_fee = $item->late_fee;
                        $loan_overdue_log->already_money = $item->true_total_money;
                        $loan_overdue_log->created_at = time();
                        $loan_overdue_log->updated_at = time();
                        $loan_overdue_log->operator_name = "shell auto";

                        //表示已经逾期了，要开始计算违约金
                        $user_interest_log->type = UserInterestLog::TRADE_TYPE_LQD_LATE_FEE;
                        $cacl_money = min($item->principal, ($item->principal +$item->interests + $item->late_fee - $item->true_total_money + $item->coupon_money));
                        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
                        $late_fee = $cacl_money*$user_loan_order->late_fee_apr/100;
                        $operate_money = $late_fee;
                        $loan_overdue_log->produce_overdue_late_fee = $late_fee;
                        if(!$loan_overdue_log->save()){
                            $message = sprintf("[%s] repayment_id %s loan_overdue_log save failed.\n", date('ymd H:i:s'),$id);
                            CommonHelper::stdout($message);
                            throw new Exception($message);
                        }
                        //逾期天数40天封顶
                        if($item->overdue_day < 40){
                            $item->late_fee = $item->late_fee +$late_fee;
                            $item->total_money = $item->total_money + $late_fee;
                        }
                        $item->late_day = $item->late_day+1;
                        $item->interest_time = time();
                        $item->updated_at = time();
                        $item->is_overdue = UserLoanOrderRepayment::OVERDUE_YES;
                        $item->overdue_day = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-d',$item->plan_repayment_time)))/24/3600 - 1;
                        $fund = $user_loan_order->fund_id ? $user_loan_order->loanFund : $fund_koudai;
                        /* @var $fund LoanFund */
                        if($user_loan_order->orderFundInfo) {
                            $user_loan_order->orderFundInfo->cacl_overdue_interest += $fund->getOverdueInterest($cacl_money, 1);
                            $user_loan_order->orderFundInfo->updateAttributes(['cacl_overdue_interest']);
                        }

                        if(!$item->save()){
                            $message = sprintf("[%s] repayment_id %s user_loan_order_repayment save failed.\n", date('ymd H:i:s'),$id);
                            CommonHelper::stdout($message);
                            throw new Exception($message);
                        }



                        //自动提交还款
//                        if($item->overdue_day <= 40){
//                            $ret = self::actionLqbRepayment($order_id,$repayment_id);
//                            if( !isset($ret['code']) || (0!=$ret['code']) ){
//                                $message = sprintf("[%s] repayment_id %s exec func  self::actionLqbRepayment failed, error info : <%s>\n", date('ymd H:i:s'),$id,json_encode($ret,JSON_UNESCAPED_UNICODE));
//                                CommonHelper::stdout($message);
//                                throw new Exception($message);
//                            }else{
//                                CommonHelper::stdout( sprintf("[%s] repayment_id %s process success\n", date('ymd H:i:s'),$id) );
//                            }
//                        }else{
//                            //逾期40天以上也需要小额50元扣款 2018-10-16 17:43 begin
//                            $ret = self::actionLqbRepayment($order_id,$repayment_id);
//                            if( !isset($ret['code']) || (0!=$ret['code']) ){
//                                $message = sprintf("[%s] repayment_id %s exec func  self::actionLqbRepayment failed, error info : <%s>\n", date('ymd H:i:s'),$id,json_encode($ret,JSON_UNESCAPED_UNICODE));
//                                CommonHelper::stdout($message);
//                                throw new Exception($message);
//                            }else{
//                                CommonHelper::stdout( sprintf("[%s] repayment_id %s process success\n", date('ymd H:i:s'),$id) );
//                            }
//                            //逾期40天以上也需要小额50元扣款 2018-10-16 17:43 end
//                        }

                    }else if(strtotime(date('Y-m-d',$plan_repayment_time+24*3600)) == $today){  //还款当天
                        CommonHelper::stdout( sprintf("[%s] repayment_id %s is overdue , calc overdue info.\n", date('ymd H:i:s'),$id) );

                        //自动提交还款
//                        $ret = self::actionLqbRepayment($order_id,$repayment_id);
//                        if( !isset($ret['code']) || (0!=$ret['code']) ){
//                            $message = sprintf("[%s] repayment_id %s exec func self::actionLqbRepayment failed.\n", date('ymd H:i:s'),$id);
//                            CommonHelper::stdout($message);
//                            Yii::error($message,LogChannel::FINANCIAL_DEBIT);
//                            $error_ids .= $id.',';
//                        } else{
//                            CommonHelper::stdout( sprintf("[%s] repayment_id %s process success\n", date('ymd H:i:s'),$id) );
//                        }
                        $operate_money = 0;
                    }

                    $pocket_credit = $creditChannelService->getCreditTotalByUserAndOrder($item->user_id, $order_id);
                    if(false == $pocket_credit){
                        $message = sprintf("[%s] repayment_id %s get user_credit_total failed.\n", date('ymd H:i:s'),$id);
                        CommonHelper::stdout($message);
                        throw new Exception($message);
                    }

                    //第三步：利息流水
                    $user_interest_log->user_id=$item->user_id;
                    $user_interest_log->type_second = UserInterestLog::TRADE_TYPE_SECOND_NORMAL;
                    $user_interest_log->operate_money = $operate_money??0;
                    $user_interest_log->remark = "每天自动生成利息";
                    $user_interest_log->created_at = time();
                    $user_interest_log->total_money = $pocket_credit->amount;
                    $user_interest_log->used_money = $pocket_credit->used_amount;
                    $user_interest_log->unabled_money = $pocket_credit->locked_amount;
                    $user_interest_log->order_id = $item->order_id;
                    $user_interest_log->repayment_id = $item->id;
                    $user_interest_log->repayment_period_id = 0;
                    $user_interest_log->before_interests = $before_interests;
                    $user_interest_log->before_late_fee = $before_late_fee;
                    if(!$user_interest_log->save()){
                        $message = sprintf("[%s] repayment_id %s get user_interest_log save failed.\n", date('ymd H:i:s'),$id);
                        CommonHelper::stdout($message);
                        throw new Exception($message);
                    }

                    $transaction->commit();
                    CommonHelper::stdout( sprintf("[%s] repayment_id %s process success\n", date('ymd H:i:s'),$id) );
                }catch (\Exception $e){
                    $transaction->rollBack();
                    $message= $e->getTraceAsString();
                    $message = "计算利息错误，错误信息：{$e->getFile()}第{$e->getLine()}行错误：{$e->getMessage()} 详细信息 {$message}";
                    CommonHelper::stdout($message);
                    Yii::error($message ,LogChannel::FINANCIAL_DEBIT);
                    $error_ids .= $id.',';
                }
            }
            $start_id = $id;
            $all_ids = $query->andWhere(['>', 'id', $start_id])->orderBy('id asc')->asArray()->limit(5000)->all();
            $count += count($all_ids);
            GlobalHelper::connectDb('db_kdkj');
        }

        if (YII_ENV_PROD) {
            $warning_reg_emails = [
                NOTICE_MAIL2,
                NOTICE_MAIL3
            ];
            $log = date('Y-m-d H:i:s')." 计算利息结果 计算利息订单总数:".$count.",失败ID:".$error_ids;
            foreach ($warning_reg_emails as $email) {
                \common\helpers\MailHelper::send($email, \sprintf('[%s] '.APP_NAMES.'每日计息脚本状态报告', date('Y-m-d')), $log);
            }
        }
    }

    /**
     * 零钱包自动还款
     */
    public function actionLqbRepayment($order_id,$repayment_id){
        $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'获取数据失败'
            ];
        }
       $card_info =  CardInfo::findOne(['user_id'=>$user_loan_order->user_id,'type'=>CardInfo::TYPE_DEBIT_CARD]);
        $card_id = $card_info ? $card_info->id : 0;

        $ret = self::actionPaymentLqd($user_loan_order,$repayment_id,$card_id);

        return [
            'code'=>$ret['code'],
            'message'=>$ret['message'],
            'data'=>[
                'item'=>[],
            ],
        ];

    }

    private function actionPaymentLqd($user_loan_order,$repayment_id,$card_id){
        $status = $user_loan_order->status;
        switch($status){
            case UserLoanOrder::STATUS_BAD_DEBT :
//            case UserLoanOrder::STATUS_LOAN_COMPLING :
            case UserLoanOrder::STATUS_APPLY_REPAY :
            case UserLoanOrder::STATUS_REPAY_TRAIL :
            case UserLoanOrder::STATUS_APPLY_RETRAIL :
            case UserLoanOrder::STATUS_REPAYING :
            case UserLoanOrder::STATUS_PARTIALREPAYMENT :
            case UserLoanOrder::STATUS_OVERDUE :
            case UserLoanOrder::STATUS_REPAYING_CANCEL :
            case UserLoanOrder::STATUS_DEBIT_FALSE :
            case UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL :
            case UserLoanOrder::STATUS_REPAY_CANCEL :
            case UserLoanOrder::STATUS_LOAN_COMPLETE:
                //正常还款
                $user_loan_order_repayment =  UserLoanOrderRepayment::findOne(['user_id'=>$user_loan_order->user_id,'id'=>$repayment_id]);
                if(false == $user_loan_order_repayment){
                    return [
                        'code'=>1,
                        'message'=>'该还款单不存在，请确认',
                    ];
                }
                $loan_person = LoanPerson::find()->select(['source_id'])->where(['id'=>$user_loan_order->user_id])->one();

                $user_loan_order_repayment->updated_at = time();
                $user_loan_order_repayment->operator_name = $user_loan_order->user_id;
                //只有可扣款时，才可以修改 status（如果is_cuishou_do=-1,表示不能代扣，银行卡四要素失效）
                if($user_loan_order_repayment->is_cuishou_do >= 0){
                    $user_loan_order_repayment->status= UserLoanOrderRepayment::STATUS_CHECK;
                }
                $user_loan_order_repayment->card_id = $user_loan_order->card_id;
                $user_loan_order_repayment->apply_repayment_time = time();

                //口袋记账和加班管家前三天不扣滞纳金

                $user_loan_order_repayment->current_debit_money = $user_loan_order_repayment->principal
                    + $user_loan_order_repayment->interests
                    + $user_loan_order_repayment->late_fee
                    - $user_loan_order_repayment->true_total_money;


                $user_loan_order->status = UserLoanOrder::STATUS_APPLY_REPAY;
                $user_loan_order->updated_at = time();
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    if(!$user_loan_order_repayment->update()){
                        return [
                            'code'=>1,
                            'message'=>'还款失败，请稍后再试',
                        ];
                    }
                    if(!$user_loan_order->update()){
                        $transaction->rollBack();
                        return [
                            'code'=>1,
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
                        'code'=>1,
                        'message'=>'还款失败，请稍后再试',
                    ];
                }
                break;
            case UserLoanOrder::STATUS_LOAN_COMPLING:
                return [
                    'code'=>1,
                    'message'=>'该单处于申请还款中，请不要重复申请',
                ];
                break;
            case UserLoanOrder::STATUS_REPAY_COMPLETE:
                return [
                    'code'=>1,
                    'message'=>'该单已经还款，请不要重复申请',
                ];
                break;
            default:
                return [
                    'code'=>1,
                    'message'=>'还款失败，请稍后再试',
                ];
                break;
        }
    }


}
