<?php
namespace backend\helpers\loanrepayment;

use common\models\LoanRecordPeriod;
use common\models\LoanRepayment;
use common\models\LoanRepaymentPeriod;
use Yii;


class monthRepayment extends abstractRepayment{

    /**
     * 获得每次应还金额
     * @return mixed|void
     */
    public function getMoney(){
        $month_repay = intval($this->repayment_amount * $this->loan_record_period->apr / 100 / 12);//每月还款利息
        return $month_repay;
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
        $repay_num = intval($this->period / 1);
        $total_repay_num = $repay_num;
        $this->repay_count_num =$repay_num;
        //获得每月应还款总额
        $period_amount = $this->getMoney();
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
            $repayment_start_time = $this->repay_start_time;
        }elseif($operation == 2){
            $repayment_start_time = strtotime(date('Y-m-d', $this->repay_start_time) . '-1 month');
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
            $loan_fee_repayment_period->plan_will_repayment_amount = intval($this->repayment_amount + intval($this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $repay_num));
            $loan_fee_repayment_period->plan_repayment_time = time();
            $loan_fee_repayment_period->plan_next_repayment_time = $this->repay_start_time;
            $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
            if(!$loan_fee_repayment_period->save()){
                throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
            }
        }
        //1.根据首次还款日期，获取每月还款时间
        $repay_date = [];
        $repay_date = $this->getPeriodRepayDate($repayment_start_time, $repay_num);
        if(empty($repay_date)){
            throw new \Exception('抱歉，每月还款日期不能为空！');
        }
        $result[0]['plan_will_repayment_amount'] = intval($this->repayment_amount + intval($this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $this->period));
        if($operation == 1){
            $next_repay_time = strtotime(date('Y-m-d', $this->repay_start_time) . '+1 month');
            $loan_fee_repayment_period = new LoanRepaymentPeriod();
            $loan_fee_repayment_period->loan_record_id = $this->loan_record_period->id;
            $loan_fee_repayment_period->repayment_id = $loan_repayment_id;
            $loan_fee_repayment_period->loan_person_id = $this->loan_record_period->loan_person_id;
            $loan_fee_repayment_period->user_id = $this->loan_record_period->user_id;
            $loan_fee_repayment_period->period = 1;
            $loan_fee_repayment_period->plan_repayment_money = $period_amount;
            $loan_fee_repayment_period->plan_will_repayment_amount = $result[0]['plan_will_repayment_amount'] - $period_amount;
            $loan_fee_repayment_period->plan_repayment_time = $this->repay_start_time;
            $loan_fee_repayment_period->plan_next_repayment_time = $next_repay_time;
            $loan_fee_repayment_period->status = LoanRepayment::STATUS_REPAYING;
            $loan_fee_repayment_period->plan_repayment_interest = $period_amount;
            if(!$loan_fee_repayment_period->save()){
                throw new \Exception('抱歉，分期还款表服务费插入记录失败！');
            }
            $result[0]['plan_will_repayment_amount'] = intval($this->repayment_amount + intval($this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $this->period) - $period_amount);
            //2.插入分期还款计划表
            for($i = 1; $i <= $repay_num; $i++){
                $result[$i]['period'] = $i + 1;
                $result[$i]['plan_repayment_money'] = $period_amount;
                $result[$i]['loan_person_id'] = $this->loan_record_period->loan_person_id;
                $result[$i]['user_id'] = $this->loan_record_period->user_id;
                $result[$i]['loan_record_id'] = $this->loan_record_period->id;
                $result[$i]['repayment_id'] = $loan_repayment_id;
                $result[$i]['plan_will_repayment_amount'] = $result[$i - 1]['plan_will_repayment_amount'] - $period_amount;
                $result[$i]['status'] = LoanRepayment::STATUS_REPAYING;
                $result[$i]['plan_repayment_time'] = strtotime($repay_date[$i - 1]);
                $result[$i]['plan_repayment_interest'] = $period_amount;
                $result[$i]['plan_repayment_principal'] = 0;
                if($i == ($repay_num)){
                    $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$repay_num - 1]);
                    $result[$i]['plan_repayment_money'] = intval($this->repayment_amount);
                    $result[$i]['plan_repayment_principal'] = intval($this->repayment_amount);
                    $result[$i]['plan_repayment_interest'] = 0;
                    $result[$i]['plan_will_repayment_amount'] = 0;
                }else{
                    $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$i]);
                }
            }
            $total_repay_num = $this->repay_count_num + 1;
        }elseif($operation == 2){
            //利息后置
            //2.插入分期还款计划表
            for($i = 1; $i <= $repay_num; $i++){
                $result[$i]['period'] = $i;
                $result[$i]['plan_repayment_money'] = $period_amount;
                $result[$i]['loan_person_id'] = $this->loan_record_period->loan_person_id;
                $result[$i]['user_id'] = $this->loan_record_period->user_id;
                $result[$i]['loan_record_id'] = $this->loan_record_period->id;
                $result[$i]['repayment_id'] = $loan_repayment_id;
                $result[$i]['plan_will_repayment_amount'] = $result[$i - 1]['plan_will_repayment_amount'] - $period_amount;
                $result[$i]['status'] = LoanRepayment::STATUS_REPAYING;
                $result[$i]['plan_repayment_time'] = strtotime($repay_date[$i - 1]);
                $result[$i]['plan_repayment_interest'] = $period_amount;
                $result[$i]['plan_repayment_principal'] = 0;
                if($i == ($repay_num)){
                    $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$repay_num - 1]);
                    $result[$i]['plan_repayment_money'] = intval($this->repayment_amount + $period_amount);
                    $result[$i]['plan_repayment_principal'] = intval($this->repayment_amount);
                    $result[$i]['plan_repayment_interest'] = $period_amount;
                    $result[$i]['plan_will_repayment_amount'] = 0;
                }else{
                    $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$i]);
                }
            }
        }
        unset($result[0]);
        $rows = [];
        foreach($result as $k => $v){
            $rows[] = [
                $v['loan_record_id'], $v['repayment_id'], $v['loan_person_id'], $v['user_id'], $v['period'], $v['plan_repayment_money'],
                $v['plan_repayment_time'], $v['plan_next_repayment_time'], $v['plan_will_repayment_amount'], 0, 0,
                LoanRepayment::STATUS_REPAYING, '', '',  time(), time(),$v['plan_repayment_principal'],$v['plan_repayment_interest']
            ];
        }
        $affectedRows = Yii::$app->db_kdkj->createCommand()->batchInsert(
            LoanRepaymentPeriod::tableName(),
            [
                'loan_record_id', 'repayment_id', 'loan_person_id','user_id', 'period', 'plan_repayment_money',
                'plan_repayment_time', 'plan_next_repayment_time', 'plan_will_repayment_amount', 'true_repayment_money', 'true_repayment_time',
                'status', 'admin_username', 'remark','created_at', 'updated_at','plan_repayment_principal','plan_repayment_interest'
            ],
            $rows
        )->execute();
        if($affectedRows != $repay_num){
            throw new \Exception('插入分期还款记录表失败');
        }
        //3.更新总还款记录表下一还款ID
        $loan_repayment = LoanRepayment::findOne(['loan_record_id' => $this->loan_record_period->id]);
        $loan_repayment_period = LoanRepaymentPeriod::find()->where(['loan_record_id' => $this->loan_record_period->id, 'period' => 1])->one();
        $loan_repayment->next_period_repayment_id = $loan_repayment_period->id;
        $loan_repayment->repayment_time = strtotime($repay_date[$repay_num - 1]);
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