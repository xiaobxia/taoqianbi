<?php
namespace backend\helpers\repayment;

use Yii;
use yii\base\Exception;
use backend\helpers\repayment\abstractRepayment;
use common\models\FinancialDetail;
use common\models\Financial;

class debxRepayment extends abstractRepayment{

    private $total;//每月还款总额



    public function getMoney(){
        if($this->peroid <= 0){
            throw new Exception("期限不允许为空");
        }
        $this->total = round($this->total_amount_financing / 100 *($this->borrower_rate/100/12)*pow((1+($this->borrower_rate/100/12)),$this->peroid)/(pow((1+($this->borrower_rate/100/12)),$this->peroid)-1),2); //每月还款总额
    }

    public function getProfits(){
        if($this->peroid <= 0){
            throw new Exception("期限不允许为空");
        }
        $this->getMoney();
        return ($this->total * 100 * $this->peroid) - $this->total_amount_financing;
    }

    public function insertData(){
        $result = [];
        $peroid = $this->peroid;
        $now = time();
        if($peroid <= 0){
            throw new Exception("期限不允许为空");
        }
        for($i = 1; $i <= $peroid; $i++){
            $result[$i]['done_interest'] = bcmul(sprintf("%.2f", ($this->total_amount_financing / 100 * ($this->borrower_rate/100/12) - $this->total) * (pow((1+($this->borrower_rate/100/12)), ($i - 1)))  +  $this->total), 100);
            $result[$i]['done_principal'] = bcmul(($this->total - sprintf("%.2f", ($this->total_amount_financing / 100 * ($this->borrower_rate/100/12) - $this->total) * (pow((1+($this->borrower_rate/100/12)), ($i - 1)))  +  $this->total)), 100);
            $result[$i]['date'] = (date('Y-m-d',  strtotime('+'.$i.' month', strtotime($this->sign_repayment_time))));
        }
        $rows = [];
        foreach($result as $k => $v){
            $rows[] = [$this->financial_id, $v['date'], $v['done_principal'], $v['done_interest'], Financial::TYPE_REPAYMENT_DEBX, FinancialDetail::STATUS_VALID, FinancialDetail::ADD_TYPE_SYSTEM, Yii::$app->user->identity->username, $now, $now];
        }
        Yii::$app->db_kdkj->createCommand()->batchInsert(
            FinancialDetail::tableName(),
            ['financial_id', 'date', 'plan_done_principal', 'plan_done_interest', 'repayment_type', 'status', 'add_type', 'admin_username', 'created_at', 'updated_at'],
            $rows
        )->execute();
        $principal = bcmul($peroid * $this->total, 100)  - $this->total_amount_financing;//还款总利息
        $financial_query = Financial::findOne($this->financial_id);
        $financial_query->platform_revenue = $principal;//预期借款收益
        $financial_query->total_revenue = $principal - $financial_query->investor_revenue;//预期净收益
        if(!$financial_query->save()){
            throw new Exception("更改预期借款收益失败！");
        }
    }
}