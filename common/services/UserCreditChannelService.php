<?php
/*
 * +----------------------------------------------------------------------
 * | 口袋理财
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: lujingfeng <lujingfeng@xinjincard.com>
 * +----------------------------------------------------------------------
 * | 额度渠道服务类
 */
namespace common\services;

use yii\db\Transaction;
use common\api\RedisQueue;
use common\base\LogChannel;
use common\models\RepaymentIncreaseCreditLog;
use common\models\UserLoanOrderRepayment;
use common\models\loan\LoanCollectionOrder;
use common\models\UserCreditReviewLog;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserCreditLog;
use common\models\UserCreditTotal;
use common\models\BaseUserCreditTotalChannel;
use common\helpers\Util;
use common\helpers\CommonHelper;

class UserCreditChannelService extends BaseCreditChannelService
{
    /**
     * 构造函数
     */
    public function __construct(){
        parent::__construct();
    }

    const STATUS_NORMAL= 1;
    const STATUS_NO_NORMAL = 0;
    const STATUS_EXCEPTION = -1;
    const CREDIT_TOTAL_MAX = 500000;
    const CREDIT_TOTAL_MAX_NEW = 170000;

    //单次最大提额限制
    const CREDIT_QUOTA_MAX_AMOUNT = 15000;
    const CREDIT_TOTAL_ADD = 100000;
    /**
     * 初始化额度记录
     * @param int $user_id 用户id
     */
    public function initUserCreditTotal($user_id) {
        $db = \Yii::$app->db_kdkj;
        $transaction = $db->beginTransaction();  //开启事务

        try {
            if (!$this->addLock($user_id)) return false;

            $channel_user_credit_total = $this->creditDBInstance;
            $channel_user_credit_total_info = $this->creditDBInstance->find()->where(['user_id'=>$user_id])->limit(1)->one();

            if (!$channel_user_credit_total_info) {
                $channel_user_credit_total->card_type = BaseUserCreditTotalChannel::CARD_TYPE_ONE;
                $channel_user_credit_total->card_title = BaseUserCreditTotalChannel::$normal_card_info[BaseUserCreditTotalChannel::CARD_TYPE_ONE]['card_title'];
                $channel_user_credit_total->card_subtitle = BaseUserCreditTotalChannel::$normal_card_info[BaseUserCreditTotalChannel::CARD_TYPE_ONE]['card_subtitle'];
                $channel_user_credit_total->card_no = \common\helpers\IdGeneratorHelper::genertaor_card_no();
                $channel_user_credit_total->user_id = $user_id;
                //$channel_user_credit_total->amount = BaseUserCreditTotalChannel::$normal_card_info[BaseUserCreditTotalChannel::CARD_TYPE_ONE]['card_amount'];
                // $card_amount = intval(Util::t('card_amount'));
                $channel_user_credit_total->amount = BaseUserCreditTotalChannel::AMOUNT;
                $channel_user_credit_total->used_amount = 0;
                $channel_user_credit_total->locked_amount = 0;
                $channel_user_credit_total->updated_at = time();
                $channel_user_credit_total->created_at = time();
                $channel_user_credit_total->operator_name = $user_id;
                $channel_user_credit_total->pocket_apr = BaseUserCreditTotalChannel::$normal_card_info[BaseUserCreditTotalChannel::CARD_TYPE_ONE]['card_apr'];
                $channel_user_credit_total->house_apr = BaseUserCreditTotalChannel::HOUSE_APR;
                $channel_user_credit_total->installment_apr = BaseUserCreditTotalChannel::INSTALLMENT_APR;
                $channel_user_credit_total->pocket_late_apr = BaseUserCreditTotalChannel::$normal_card_info[BaseUserCreditTotalChannel::CARD_TYPE_ONE]['card_late_apr'];
                $channel_user_credit_total->house_late_apr = BaseUserCreditTotalChannel::HOUSE_LATE_APR;
                $channel_user_credit_total->installment_late_apr = BaseUserCreditTotalChannel::INSTALLMENT_LATE_APR;
                $channel_user_credit_total->pocket_min = BaseUserCreditTotalChannel::POCKET_MIN;
                $channel_user_credit_total->pocket_max = BaseUserCreditTotalChannel::POCKET_MAX;
                $channel_user_credit_total->house_min = BaseUserCreditTotalChannel::HOUSE_MIN;
                $channel_user_credit_total->house_max = BaseUserCreditTotalChannel::HOUSE_MAX;
                $channel_user_credit_total->installment_min = BaseUserCreditTotalChannel::INSTALLMENT_MIN;
                $channel_user_credit_total->installment_max = BaseUserCreditTotalChannel::INSTALLMENT_MAX;
                $channel_user_credit_total->counter_fee_rate = BaseUserCreditTotalChannel::COUNTER_FEE_RATE;

                $res_channel = $channel_user_credit_total->save();
                if (!$res_channel) {
                    throw new \Exception("新增渠道额度表{$this->creditTableName}失败");
                }
            }

            $transaction->commit();
            $this->clearLock($user_id);
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->clearLock($user_id);

            \yii::warning(sprintf('initUserCreditTotal_error, uid(%s). %s', $user_id, $e), LogChannel::USER_CREDIT);
            return false;
        }
    }

    /**
     * 调节用户额度方法
     * @param int $amount        额度
     * @param int $user_id      用户id
     * @param int $order_id     订单id  默认为0
     * @param number $type      操作类型  取日志表常量
     * @param array $params
     * @return boolean
     */
    public function addCreditAmount($amount, $user_id, $order_id = 0, $type = 0, $params = []) {
        if (!$type) {
            $type = UserCreditLog::TRADE_TYPE_LQD_ADMIN; #还款
        }

        if ($order_id > 0) {
            $channelModel = $this->getCreditTotalByUserAndOrder($user_id, $order_id);
        }
        else {
            $channelModel = $this->getCreditTotalByUserId($user_id);
        }

        if (empty($channelModel)) {
            $_msg = \sprintf('%s 额度信息不存在.', $user_id);
            CommonHelper::error( $_msg );
            throw new \Exception( $_msg );
        }

        $before_amount = $channelModel->amount;
        $channelModel->amount += $amount;
        $res = $channelModel->save();
        if (!$res) {
            $_msg = \sprintf('%s 额度保存失败.', $user_id);
            CommonHelper::error( $_msg );
            throw new \Exception( $_msg );
        }

        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $user_id;
        $user_credit_log->type = $type;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $amount;
        $user_credit_log->remark = isset($params['remark']) ? $params['remark'] : '';
        $user_credit_log->total_money = $channelModel->amount;
        $user_credit_log->used_money = $channelModel->used_amount;
        $user_credit_log->unabled_money = $channelModel->locked_amount;
        $user_credit_log->created_at = time();
        $res = $user_credit_log->save();

        $log_amount = new UserCreditReviewLog();
        $log_amount->user_id = $user_id;
        $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
        $log_amount->before_number = $before_amount;
        $log_amount->operate_number = $amount;
        $log_amount->after_number = $channelModel->amount;
        $log_amount->status = UserCreditReviewLog::STATUS_PASS;
        $log_amount->created_at = time();
        $log_amount->remark = "授信提额";
        $log_amount_res = $log_amount->save();

        if (!$res || !$log_amount_res) {
            CommonHelper::error( \sprintf('%s 调整额度日志保存失败！', $user_id) );
        }

        return true;
    }

    /**
     * 设置用户额度方法
     * @param int $amount        额度
     * @param int $user_id      用户id
     * @param int $order_id     订单id  默认为0
     * @param number $type      操作类型  取日志表常量
     * @param array $params
     * @return boolean
     */
    public function setCreditAmount($amount, $user_id, $order_id = 0, $type = 0, $params = []) {
        if (!$type) {
            $type = UserCreditLog::TRADE_TYPE_SET;
        }

        if ($order_id > 0) {
            $channelModel = $this->getCreditTotalByUserAndOrder($user_id, $order_id);
        }
        else {
            $channelModel = $this->getCreditTotalByUserId($user_id);
        }

        if (empty($channelModel)) {
            $_msg = \sprintf('%s 额度信息不存在.', $user_id);
            CommonHelper::error( $_msg );
            throw new \Exception( $_msg );
        }

        $channelModel->amount += $amount;
        $res = $channelModel->save();
        if (!$res) {
            $_msg = \sprintf('%s 额度保存失败.', $user_id);
            CommonHelper::error( $_msg );
            throw new \Exception( $_msg );
        }

        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $user_id;
        $user_credit_log->type = $type;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $amount;
        $user_credit_log->remark = isset($params['remark']) ? $params['remark'] : "";
        $user_credit_log->total_money = $channelModel->amount;
        $user_credit_log->used_money = $channelModel->used_amount;
        $user_credit_log->unabled_money = $channelModel->locked_amount;
        $user_credit_log->created_at = time();
        $res = $user_credit_log->save();
        if (!$res) {
            CommonHelper::error( \sprintf('%s 设置额度日志保存失败！', $user_id) );
        }

        return true;
    }

    /**
     * 判断一笔订单是否是正常还款
     * @param  integer $order_id 订单ID
     * @param  integer $day 逾期天数分割线
     * @return integer $status 1:正常 0：非正常 -1：异常
     */
    public function checkOrderRepaymentNoraml($order_id,$day=3){
        //查询订单是否存在
        $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$order_id]);
        if (false == $user_loan_order_repayment) {
            return [
                'status'=>self::STATUS_EXCEPTION
            ];
        }

        $status = $user_loan_order_repayment->status;
        $overdue_day = $user_loan_order_repayment->overdue_day;
        if(UserLoanOrderRepayment::STATUS_REPAY_COMPLETE == $user_loan_order_repayment->status){
            // 修改 借款后2天（含）内还款，不予提额  2=》4
            if($user_loan_order_repayment['true_repayment_time'] > 0 && ($user_loan_order_repayment['true_repayment_time'] - $user_loan_order_repayment['loan_time']) < 86400*5){
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
    public function increaseUserCreditAccount($repayment) {
        $user_id = $repayment['user_id'];
        $order_id = $repayment['order_id'];
        $user_credit_total = $this->getCreditTotalByUserAndOrder($user_id, $order_id);
        if (!$user_credit_total) {
            return 0;
        }
        $before_amount = $user_credit_total->amount;

        //额度超过3000元，不做还款提额逻辑
        if($before_amount >= self::CREDIT_TOTAL_MAX) {
            return 0;
        }
        $repay_time = $repayment['true_repayment_time'];

        if(empty($user_credit_total['increase_time'])||($repay_time > $user_credit_total['increase_time'])) {
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where(['user_id'=>$user_id,'status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
                ->andWhere(['>', 'true_repayment_time', $user_credit_total['increase_time']])
                ->asArray()->all();
            $repay_amount = 0;//折合还款金额
            $increase_amount = 0;//增加额度
            foreach($user_loan_order_repayment as $data){
                $code = $this->checkOrderRepaymentNoraml($data['order_id'],1);
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
                // 设置提额上线 : 每次最高是 150
                $increase_amount = min((intval($repay_amount/20000))*1000, self::CREDIT_QUOTA_MAX_AMOUNT);
            }
            if($increase_amount >= 1000) {
                $transaction = \Yii::$app->db_kdkj->beginTransaction();
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

                   /* if(!$user_credit_total->save()){
                        throw new \Exception('用户信用额度更新失败');
                    }*/

                    //总额度流水表
                    $log_amount = new UserCreditReviewLog();
                    $log_amount->user_id = $user_credit_total->user_id;
                    $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_INCREASE;
                    $log_amount->before_number = $before_amount;
                    $log_amount->operate_number = $increase_amount;
                    $log_amount->after_number = $user_credit_total->amount;
                    $log_amount->status = UserCreditReviewLog::STATUS_PASS;
                    $log_amount->creater_name = 'auto';
                    $log_amount->created_at = time();
                   /* if(!$log_amount->save()){
                        throw new \Exception('总额度流水表保存失败');
                    }*/


                    //生成还款提额日志
                    $repayment_increase_credit_log = new RepaymentIncreaseCreditLog();
                    $repayment_increase_credit_log->user_id = $user_id;
                    $repayment_increase_credit_log->order_id = $order_id;
                    $repayment_increase_credit_log->repayment_id = $repayment['id'];
                    $repayment_increase_credit_log->money =  $increase_amount;
                    /*if(!$repayment_increase_credit_log->save()){
                        throw new \Exception('还款提额日志表保存失败');
                    }*/

                    $transaction->commit();

                    // ---------------------------------- start 用户提额 提醒队列
                    try{
                        $user_id = $user_credit_total->user_id;
                        RedisQueue::set(['expire'=>86400*3,'key'=>"up_credit_line_{$user_id}",'value'=>$increase_amount]);
                        $show_msg = "您申请的" .  $repayment->principal / 100 .  "元借款还款成功";
                        RedisQueue::set(['expire'=>86400*3,'key'=>"user_info_show_message_{$user_id}",'value'=>$show_msg]);
                    }catch (\Exception $e){

                    }
                    // ---------------------------------- end 用户提额 提醒队列
                    // ---------------------------------- start 记录提额日志 done: By Ron
//                    $user_id = $user_credit_total->user_id;
//                    $loan_person = LoanPerson::findOne(['id'=>$user_id]);
//                    if ($loan_person) {
//                        if ($loan_person->phone) {
//                            // 处理借款消息日志
//                            \Yii::$container->get("financialService")->handleIncreaseMessage($loan_person->phone,$user_credit_total->amount);
//                        }
//                    }
                    // ----------------------------------end

                    return $increase_amount;
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

    /**
     * 还款后提的额
     * @return int
    **/
    public function increaseUserCreditAccountNew($repayment){
        //提额规则，最近2次提前还款，提额100元，最近3次还款200，即（n-1）* 100元，最高提额1500元
        $user_id = $repayment['user_id'];
        $order_id = $repayment['order_id'];
        $user_credit_total = $this->getCreditTotalByUserAndOrder($user_id, $order_id);
        if (!$user_credit_total) {
            return 0;
        }
        $before_amount = $user_credit_total->amount;

        $is_overdue = $repayment['is_overdue'];
        $overdue_day = $repayment['overdue_day'];
        if($is_overdue==1 || $overdue_day > 0) {
            //现有的额度，如果不大于默认额度，将不在处理
            if($before_amount <= UserCreditTotal::AMOUNT){
                return 0;
            }
            //判断是否逾期，如果逾期，额度降为原始的1000元
            $transaction = \YII::$app->db_kdkj->beginTransaction();
            try {

                //变化的额度
                $increase_amount = $before_amount - UserCreditTotal::AMOUNT;

                //更新额度
                $user_credit_total->amount = UserCreditTotal::AMOUNT;
                $user_credit_total->repayment_credit_add = 0;
                //如果出现逾期，从闪电荷包迁移过来用户的默认额度为0
                if($user_credit_total->sdhb_default_amount>0){
                    $user_credit_total->sdhb_default_amount = 0;
                }
                $user_credit_total->updated_at = time();
                $user_credit_total->increase_time = time();
                $user_credit_total->save();

                //总额度流水表
                $log_amount = new UserCreditReviewLog();
                $log_amount->user_id = $user_credit_total->user_id;
                $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
                $log_amount->before_number = $before_amount;
                $log_amount->operate_number = $increase_amount;
                $log_amount->after_number = UserCreditTotal::AMOUNT;
                $log_amount->status = UserCreditReviewLog::STATUS_PASS;
                $log_amount->creater_name = 'auto';
                $log_amount->created_at = time();
                $log_amount->save();

                //生成还款提额日志
                $repayment_increase_credit_log = new RepaymentIncreaseCreditLog();
                $repayment_increase_credit_log->user_id = $user_id;
                $repayment_increase_credit_log->order_id = $order_id;
                $repayment_increase_credit_log->repayment_id = $repayment['id'];
                $repayment_increase_credit_log->money = $increase_amount;
                $repayment_increase_credit_log->save();

                $transaction->commit();
                $transaction = null;
            } catch (\Exception $e) {
                $transaction->rollBack();
                \YII::error('用户：'.$user_id.'还款逾期，修改提额失败，原因：'.$e->getMessage());
            }
            return 0;
        }
        //正常还款，额度超过1700元，不做还款提额逻辑
        if($before_amount >= self::CREDIT_TOTAL_MAX_NEW) {
            return 0;
        }

        //计算最近正常还款笔数
        $user_loan_order_repayment = UserLoanOrderRepayment::find()
            ->where(['user_id'=>$user_id,'status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
            ->orderBy('id desc')
            ->asArray()->all();
        $repayment_quantity=0;
        foreach($user_loan_order_repayment as $data){
            //如果出现逾期，则不在计算
            if($data['is_overdue']==1 || $data['overdue_day']>0){
                break;
            }
            $repayment_quantity=$repayment_quantity+1;
        }
        if($repayment_quantity<2){
            return 0;
        }
        $repayment_credit_add=$user_credit_total->repayment_credit_add;
        if($repayment_credit_add>(self::CREDIT_TOTAL_MAX_NEW-UserCreditTotal::AMOUNT)){
            $repayment_credit_add=self::CREDIT_TOTAL_MAX_NEW-UserCreditTotal::AMOUNT;
        }
        $increase_amount=($repayment_quantity-1)*10000 - $repayment_credit_add;
        $amount = UserCreditTotal::AMOUNT + ($repayment_quantity-1)*10000;
        if($user_credit_total->sdhb_default_amount>0){
            $amount+=$user_credit_total->sdhb_default_amount;
            $increase_amount+=$user_credit_total->sdhb_default_amount;
        }
        //提额的额度不能超过1700元
        if($amount > self::CREDIT_TOTAL_MAX_NEW){
            $amount = self::CREDIT_TOTAL_MAX_NEW;
        }
        $transaction = \YII::$app->db_kdkj->beginTransaction();
        try {
            //更新额度
            $user_credit_total->amount = $amount;
            $user_credit_total->repayment_credit_add = ($repayment_quantity-1)*10000+($user_credit_total->sdhb_default_amount);
            $user_credit_total->updated_at = time();
            $user_credit_total->increase_time = time();
            $user_credit_total->save();

            //总额度流水表
            $log_amount = new UserCreditReviewLog();
            $log_amount->user_id = $user_credit_total->user_id;
            $log_amount->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_INCREASE;
            $log_amount->before_number = $before_amount;
            $log_amount->operate_number = $increase_amount;
            $log_amount->after_number = $amount;
            $log_amount->status = UserCreditReviewLog::STATUS_PASS;
            $log_amount->creater_name = 'auto';
            $log_amount->created_at = time();
            $log_amount->save();

            //生成还款提额日志
            $repayment_increase_credit_log = new RepaymentIncreaseCreditLog();
            $repayment_increase_credit_log->user_id = $user_id;
            $repayment_increase_credit_log->order_id = $order_id;
            $repayment_increase_credit_log->repayment_id = $repayment['id'];
            $repayment_increase_credit_log->money = $increase_amount;
            $repayment_increase_credit_log->save();

            $transaction->commit();
            $transaction = null;
        } catch (\Exception $e) {
            $transaction->rollBack();
            \YII::error('用户：'.$user_id.'正常还款，提额'.$increase_amount.'元失败，原因：'.$e->getMessage(),'repaymentamount');
        }

        return 0;
    }

    /**
     * 获取用户某渠道订单额度记录
     * @param int $user_id
     * @param int $orer_id
     */
    public function getCreditTotalByUserAndOrder($user_id, $order_id){
        $class = $this->getCreditTotalChannelByOrderId($order_id);
        $model = $class::find()->where(['user_id'=>$user_id])->limit(1)->one();
        if ($model && $model instanceof UserCreditTotal) {
            return $model;
        }

        if (!$model) {
            $this->initUserCreditTotal($user_id);
            $model = $class::find()->where(['user_id'=>$user_id])->limit(1)->one();
        }

        return $model;
    }

    /**
     * 获取用户某渠道额度记录
     * @param int $user_id
     */
    public function getCreditTotalByUserId($user_id){
        $class = $this->creditDBInstance;
        $model = $class::findOne(['user_id' => $user_id]);
        if ($model && $model instanceof UserCreditTotal){
            return $model;
        }
        if (!$model) {
            $this->initUserCreditTotal($user_id);
            $model = $class::findOne(['user_id' => $user_id]);
        }

        return $model;
    }

    public function addLock($id){
        $key = "initUserCreditTotal_lock_$id";
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 2]);
            return true;
        }else{
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 2]);
        }
        return false;
    }

    public function clearLock($id){
        $key = "initUserCreditTotal_lock_$id";
        \Yii::$app->redis->executeCommand('DEL', [$key]);
        return true;
    }

    /**
     * 设置用户手续费率
     * @param int $user_id
     * @param int $rate
     * @throws \Exception
     * @return boolean
     */
    public function setCounterFeeRate($user_id, $rate) {
        $channelModel = $this->getCreditTotalByUserId($user_id);
        if (empty($channelModel)) {
            throw new \Exception( \sprintf('用户[%s]费率信息不存在.', $user_id) );
        }

        $channelModel->counter_fee_rate = \Yii::$app->params['counter_fee_rate'] - $rate;
        if ($channelModel->counter_fee_rate <= 0) {
            throw new \Exception( \sprintf('用户[%s]费率降至0.', $user_id) );
        }

        $res = $channelModel->save();
        if (!$res) {
            throw new \Exception( \sprintf('用户[%s]费率保存失败.', $user_id) );
        }

        return TRUE;
    }
}
