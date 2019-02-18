<?php
namespace backend\helpers\loanrepayment;

use yii;
use yii\base\Exception;
use common\models\LoanRepayment;
use common\models\LoanRepaymentPeriod;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-8-27
 * Time: 下午6:32
 */
abstract class abstractRepayment{

    protected $repay_count_num;//还款总次数
    protected $repayment_type;//还款类型
    protected $loan_record_period;//分期订单表ID
    protected $period;//期限
    protected $repayment_amount;//放款金额
    protected $credit_repayment_time;//放款时间
    protected $sign_repayment_time;//签约时间
    protected $repay_start_time;//首次还款时间
    protected $operation;//操作方式(前置、后置)

    /**
     * 每月应还金额
     * @return mixed
     */
    abstract protected function getMoney();

    /**
     * 插入还款表数据
     * @return mixed
     */
    abstract protected function insertData();

    /**
     * 得到总还款额
     * @return int
     */
    public function getTotalMoney($fee_amount = 0){
        $total_money = intval($this->repayment_amount + $fee_amount + $this->repayment_amount * $this->loan_record_period->apr / 100 / 12 * $this->period);
        return $total_money;
    }

    /**
     * 初始化赋值
     * @param $repay_count_num
     * @param $loan_record_period
     * @param $peroid
     * @param $repayment_amount
     * @param $credit_repayment_time
     * @param $sign_repayment_time
     * @param $repay_start_time
     * @param $operation
     */
    public function setProperty( $repayment_type, $loan_record_period, $period, $repayment_amount, $credit_repayment_time, $sign_repayment_time, $repay_start_time, $operation){
        $this->repayment_type = $repayment_type;
        $this->loan_record_period = $loan_record_period;
        $this->period = $period;
        $this->repayment_amount = $repayment_amount;
        $this->credit_repayment_time = $credit_repayment_time;
        $this->sign_repayment_time = $sign_repayment_time;
        $this->repay_start_time = $repay_start_time;
        $this->operation = $operation;
    }

    /**
     * 获取每期还款时间
     * @param string $sign_time
     * @param string $period
     * @return array
     */
    public  function getPeriodRepayDate($sign_time = "", $period = ""){
        $days = intval(date('d', $sign_time));
        $time = strtotime(date('Y-m-01', $sign_time) . '+1 month');
        $repay_time = [];
        for($i = 1; $i <= $period; $i++){
            if ($days >= date('t', $time)) {
                $repay_date = date('Y-m-d', strtotime(date('Y-m-d', $time) . '+1 month -1 day'));
            } else {
                $repay_date =  date('Y-m-'.$days, $time);
            }
            array_push($repay_time, $repay_date);
            $time = strtotime(date('Y-m-01', $time) . '+1 month');
        }
        return $repay_time;
    }

    /**
     * 插入分期还款计划表
     */
    public function insertLoanRepaymentPeriod($credit_money, $loan_repayment_id, $repay_date, $peroid, $loan_record_period, $loan_repayment_period_money){
        if(empty($credit_money) || empty($repay_date) || empty($peroid) || empty($loan_record_period) || empty($loan_repayment_period_money)){
            throw new Exception("插入分期还款计划表参数为空！");
        }
        $result[0]['plan_will_repayment_amount'] = $credit_money;
        for($i = 1; $i <= $peroid; $i++){
            $result[$i]['period'] = $i;
            $result[$i]['plan_repayment_money'] = $loan_repayment_period_money;
            $result[$i]['loan_person_id'] = $loan_record_period->loan_person_id;
            $result[$i]['user_id'] = $loan_record_period->user_id;
            $result[$i]['loan_record_id'] = $loan_record_period->id;
            $result[$i]['repayment_id'] = $loan_repayment_id;
            $result[$i]['plan_will_repayment_amount'] = $result[$i - 1]['plan_will_repayment_amount'] - $loan_repayment_period_money;
            $result[$i]['status'] = LoanRepayment::STATUS_REPAYING;
            $result[$i]['plan_repayment_time'] = strtotime($repay_date[$i - 1]);
            if($i == ($peroid)){
                $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$peroid - 1]);
            }else{
                $result[$i]['plan_next_repayment_time'] = strtotime($repay_date[$i]);
            }
        }
        unset($result[0]);
        $rows = [];
        foreach($result as $k => $v){
            $rows[] = [
                $v['loan_record_id'], $v['repayment_id'], $v['loan_person_id'], $v['user_id'], $v['period'], $v['plan_repayment_money'],
                $v['plan_repayment_time'], $v['plan_next_repayment_time'], $v['plan_will_repayment_amount'], 0, 0,
                LoanRepayment::STATUS_REPAYING, '', '',  time(), time()
            ];
        }
        $affectedRows = Yii::$app->db_kdkj->createCommand()->batchInsert(
            LoanRepaymentPeriod::tableName(),
            [
                'loan_record_id', 'repayment_id', 'loan_person_id','user_id', 'period', 'plan_repayment_money',
                'plan_repayment_time', 'plan_next_repayment_time', 'plan_will_repayment_amount', 'true_repayment_money', 'true_repayment_time',
                'status', 'admin_username', 'remark','created_at', 'updated_at'
            ],
            $rows
        )->execute();
        if($affectedRows != $peroid){
            throw new \Exception('插入分期还款记录表失败');
        }
        return $affectedRows;
    }


}