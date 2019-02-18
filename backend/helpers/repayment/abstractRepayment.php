<?php
namespace backend\helpers\repayment;

use yii\base\Exception;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-8-27
 * Time: 下午6:32
 */
abstract class abstractRepayment{

    protected $total_amount_financing;
    protected $platform_revenue;
    protected $peroid;
    protected $borrower_rate;
    protected $borrow_repayment_time;
    protected $sign_repayment_time;
    protected $done_principal;
    protected $done_interest;
    protected $financial_id;


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
     * 初始化赋值
     * @param $total_amount_financing
     * @param $platform_revenue
     * @param $peroid
     * @param $borrower_rate
     */
    public function setProperty($total_amount_financing, $platform_revenue, $peroid, $borrower_rate, $borrow_repayment_time, $sign_repayment_time, $financial_id){
        if(($peroid <= 0) || empty($sign_repayment_time)){
            throw new Exception("您好，还款总期限或者签约日期不能为空！");
        }
        $this->total_amount_financing = $total_amount_financing;
        $this->platform_revenue = $platform_revenue;
        $this->peroid = $peroid;
        $this->borrower_rate = $borrower_rate;
        $this->borrow_repayment_time = $borrow_repayment_time;
        $this->sign_repayment_time = $sign_repayment_time;
        $this->financial_id = $financial_id;
    }
}