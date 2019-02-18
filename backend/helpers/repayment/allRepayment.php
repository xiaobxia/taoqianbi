<?php
namespace backend\helpers\repayment;

use Yii;
use backend\helpers\repayment\abstractRepayment;
use common\models\FinancialDetail;
use common\models\Financial;


class allRepayment extends abstractRepayment{

    /**
     * 获得每次应还金额
     * @return mixed|void
     */
    public function getMoney(){
        $this->done_principal = $this->total_amount_financing; //本次所还本金
        $this->done_interest = $this->platform_revenue; //本次所还利息
    }

    /**
     * 插入分期表数据
     * @return mixed|void
     */
    public function insertData(){
        $query = new FinancialDetail();
        $query->financial_id = $this->financial_id;
        $query->date = $this->borrow_repayment_time;
        $query->plan_done_principal = $this->done_principal;
        $query->plan_done_interest = $this->done_interest;
        $query->repayment_type = Financial::TYPE_ALL_REPAYMENT;
        $query->status = FinancialDetail::STATUS_VALID;
        $query->add_type = FinancialDetail::ADD_TYPE_SYSTEM;
        $query->admin_username = Yii::$app->user->identity->username;
        $query->save();
    }
}