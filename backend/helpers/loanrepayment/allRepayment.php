<?php
namespace backend\helpers\loanrepayment;

use common\models\LoanRecordPeriod;
use common\models\LoanRepayment;
use common\models\LoanRepaymentPeriod;
use Yii;


class allRepayment extends abstractRepayment{

    /**
     * 获得每次应还金额
     * @return mixed|void
     */
    public function getMoney(){
        $period_amount = intval($this->repayment_amount + $this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $this->period);
        return $period_amount;
    }

    /**
     * 插入分期表数据
     * @return mixed|void
     */
    public function insertData(){
        $operation = $this->operation;//操作
        $fee_amount = $this->loan_record_period->fee_amount;//服务费
        $urgent_amount = $this->loan_record_period->urgent_amount;//加急费
        $fee_amount = intval($fee_amount + $urgent_amount);
        $repayment_start_time = $this->repay_start_time;//首次还款时间
        $period_amount = $this->getMoney();
        $this->repay_count_num = 1;
        $total_repay_num = $this->repay_count_num;
        //插入还款总表数据
        $loan_repayment = new LoanRepayment();
        $loan_repayment->user_id = $this->loan_record_period->user_id;
        $loan_repayment->loan_person_id = $this->loan_record_period->loan_person_id;
        $loan_repayment->loan_record_id = $this->loan_record_period->id;
        $loan_repayment->loan_project_id = $this->loan_record_period->loan_project_id;
        $loan_repayment->shop_id = $this->loan_record_period->shop_id;
        $loan_repayment->repayment_principal = $this->repayment_amount;   //总还款本金
        $loan_repayment->repayment_amount = $this->getTotalMoney(0);
        $loan_repayment->repayment_interest = $loan_repayment->repayment_amount - $loan_repayment->repayment_principal;   //总还款利息
        $loan_repayment->period_repayment_amount = $period_amount;
        $loan_repayment->credit_repayment_time = $this->credit_repayment_time;
        $loan_repayment->sign_repayment_time = $this->sign_repayment_time;
        $loan_repayment->repay_start_time = $this->repay_start_time;
        $loan_repayment->status = LoanRepayment::STATUS_REPAYING;
        $loan_repayment->period = $this->period;
        if(!empty($fee_amount)){
            $loan_repayment->repayment_amount = $this->getTotalMoney($fee_amount);
            $loan_repayment->repayment_interest = $loan_repayment->repayment_amount - $loan_repayment->repayment_principal;   //总还款利息
        }
        if(!$loan_repayment->save()){
            throw new \Exception('抱歉，保存还款记录总表失败！');
        }
        //插入分期还款表数据
        $loan_repayment_id = $loan_repayment->attributes['id'];
        if($operation == 1){
            $repayment_start_time = strtotime(date('Y-m-d', $this->repay_start_time) . '-1 month');
        }elseif($operation == 2){
            $repayment_start_time = $this->repay_start_time;
        }
        if(!empty($fee_amount)){
            $loan_fee_repayment_period = new LoanRepaymentPeriod();
            $loan_fee_repayment_period->loan_record_id = $this->loan_record_period->id;
            $loan_fee_repayment_period->repayment_id = $loan_repayment_id;
            $loan_fee_repayment_period->loan_person_id = $this->loan_record_period->loan_person_id;
            $loan_fee_repayment_period->user_id = $this->loan_record_period->user_id;
            $loan_fee_repayment_period->period = 0;
            $loan_fee_repayment_period->plan_repayment_money = $fee_amount;
            $loan_fee_repayment_period->plan_repayment_interest = $fee_amount;      //预期还款利息（服务费 + 加急费）
            $loan_fee_repayment_period->plan_will_repayment_amount = $period_amount;
            $loan_fee_repayment_period->plan_repayment_time = time();
            $loan_fee_repayment_period->plan_next_repayment_time = $this->repay_start_time;
            $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
            if(!$loan_fee_repayment_period->save()){
                throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
            }
        }
        //1.根据首次还款日期，获取每月还款时间
        $repay_date = [];
        $repay_date = $this->getPeriodRepayDate($repayment_start_time, $this->repay_count_num);
        if(empty($repay_date)){
            throw new \Exception('抱歉，每月还款日期不能为空！');
        }
        //2.插入分期还款计划表
        //获得每月应还款总额
        if($operation == 1){
            //利息前置 插入利息
            $apr_amount = intval($this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $this->period);
            $next_repay_time = strtotime(date('Y-m-d', $this->repay_start_time) . '+'.$this->period.' month');
            if(!empty($apr_amount)){
                $loan_fee_repayment_period = new LoanRepaymentPeriod();
                $loan_fee_repayment_period->loan_record_id = $this->loan_record_period->id;
                $loan_fee_repayment_period->repayment_id = $loan_repayment_id;
                $loan_fee_repayment_period->loan_person_id = $this->loan_record_period->loan_person_id;
                $loan_fee_repayment_period->user_id = $this->loan_record_period->user_id;
                $loan_fee_repayment_period->period = 1;
                $loan_fee_repayment_period->plan_repayment_money = $apr_amount;
                $loan_fee_repayment_period->plan_will_repayment_amount = $this->repayment_amount;
                $loan_fee_repayment_period->plan_repayment_time = $this->repay_start_time;
                $loan_fee_repayment_period->plan_next_repayment_time = $next_repay_time;
                $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
                $loan_fee_repayment_period->plan_repayment_interest = $apr_amount;
                if(!$loan_fee_repayment_period->save()){
                    throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
                }
            }
            //利息前置  插入本金
            $loan_fee_repayment_period = new LoanRepaymentPeriod();
            $loan_fee_repayment_period->loan_record_id = $this->loan_record_period->id;
            $loan_fee_repayment_period->repayment_id = $loan_repayment_id;
            $loan_fee_repayment_period->loan_person_id = $this->loan_record_period->loan_person_id;
            $loan_fee_repayment_period->user_id = $this->loan_record_period->user_id;
            $loan_fee_repayment_period->period = 2;
            $loan_fee_repayment_period->plan_repayment_money = $this->repayment_amount;
            $loan_fee_repayment_period->plan_will_repayment_amount = 0;
            $loan_fee_repayment_period->plan_repayment_time = $next_repay_time;
            $loan_fee_repayment_period->plan_next_repayment_time = $next_repay_time;
            $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
            $loan_fee_repayment_period->plan_repayment_principal = $this->repayment_amount;
            if(!$loan_fee_repayment_period->save()){
                throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
            }
            $total_repay_num = 2;//还款总次数
        }elseif($operation == 2){
            //利息后置  插入本金和利息
            $loan_fee_repayment_period = new LoanRepaymentPeriod();
            $loan_fee_repayment_period->loan_record_id = $this->loan_record_period->id;
            $loan_fee_repayment_period->repayment_id = $loan_repayment_id;
            $loan_fee_repayment_period->loan_person_id = $this->loan_record_period->loan_person_id;
            $loan_fee_repayment_period->user_id = $this->loan_record_period->user_id;
            $loan_fee_repayment_period->period = 1;
            $loan_fee_repayment_period->plan_repayment_money = $period_amount;
            $loan_fee_repayment_period->plan_will_repayment_amount = $period_amount;
            $loan_fee_repayment_period->plan_repayment_time = $this->repay_start_time;
            $loan_fee_repayment_period->plan_next_repayment_time = $this->repay_start_time;
            $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
            $loan_fee_repayment_period->plan_repayment_principal = $this->repayment_amount;         //每期还款本金
            $loan_fee_repayment_period->plan_repayment_interest = $period_amount - $this->repayment_amount;      //每期还款利息
            if(!$loan_fee_repayment_period->save()){
                throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
            }
        }
        //3.更新总还款记录表下一还款ID
        $loan_repayment = LoanRepayment::findOne(['loan_record_id' => $this->loan_record_period->id]);
        $loan_repayment_period = LoanRepaymentPeriod::find()->where(['loan_record_id' => $this->loan_record_period->id, 'period' => 1])->one();
        $loan_repayment->next_period_repayment_id = $loan_repayment_period->id;
//        $loan_repayment->repayment_time = strtotime($repay_date[0]);
        $loan_repayment->repayment_time = $repayment_start_time;

        if(!$loan_repayment->save()){
            throw new \Exception('抱歉，更新总还款记录表下一还款ID失败！');
        }
        //更新分期订单表总还款表ID
        $loan_record_period = LoanRecordPeriod::findOne($this->loan_record_period->id);
        $loan_record_period->loan_repayment_id = $loan_repayment_id;
        $loan_record_period->repay_count_num = $total_repay_num;
        if(!$loan_record_period->save()){
            throw new \Exception('抱歉，更新分期订单表总还款记录表ID失败！');
        }
    }


}