<?php
namespace backend\helpers\repayment;

use Yii;
use backend\helpers\repayment\abstractRepayment;
use common\models\FinancialDetail;
use common\models\Financial;

class yearRepayment extends abstractRepayment{
    private $total;//每一年还款总额

    public function getMoney(){
        $this->total = $this->platform_revenue / 100; //每一年还款总额
    }

    public function insertData(){
        $result = [];
        $peroid = $this->peroid;
        $now = time();
        for($i = 1; $i <= $peroid; $i++){
            $w = $i * 12;
            $result[$i]['done_principal'] = 0;
            $result[$i]['done_interest'] = bcmul(round($this->total / $peroid, 2), 100);
            $result[$i]['date'] = (date('Y-m-d',  strtotime('+'.$w.' month', strtotime($this->sign_repayment_time))));
        }
        $result[$peroid]['done_principal'] = $this->total_amount_financing;
        $rows = [];
        foreach($result as $k => $v){
            $rows[] = [$this->financial_id, $v['date'], $v['done_principal'], $v['done_interest'], Financial::TYPE_REPAYMENT_YEAR, FinancialDetail::STATUS_VALID, FinancialDetail::ADD_TYPE_SYSTEM, Yii::$app->user->identity->username,$now, $now];
        }
        Yii::$app->db_kdkj->createCommand()->batchInsert(
            FinancialDetail::tableName(),
            ['financial_id', 'date', 'plan_done_principal', 'plan_done_interest', 'repayment_type', 'status', 'add_type', 'admin_username', 'created_at', 'updated_at'],
            $rows
        )->execute();
    }
}