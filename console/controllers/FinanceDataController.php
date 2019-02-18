<?php


namespace console\controllers;

use backend\models\AdminUser;
use common\helpers\CommonHelper;
use common\helpers\Util;
use common\models\DailyData;
use common\models\FinancialExpense;
use common\models\FinancialLoanRecord;
use common\models\UserCreditMoneyLog;
use common\models\AlipayRepaymentLog;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\LoanCollection;
use common\models\LoanPerson;
use common\models\StatisticsDayData;
use common\models\DailyTrade;
use common\models\OrderRejectRank;
use common\models\risk\Rule;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\fund\LoanFund;
use common\models\stats\DayNotYetPrincipalStatistics;
use yii;
use yii\base\Exception;
use common\models\FinancialSubsidiaryLedger;
use common\models\RepayRateslist;
use common\api\RedisQueue;
use common\models\UserRegisterInfo;
use common\models\UserLoanOrder;
use common\models\UserCreditData;
use common\models\UserCreditTotal;
use common\models\loan\StatisticsByMoney;
use common\helpers\MailHelper;
use common\helpers\ArrayHelper;


class FinanceDataController extends BaseController {
    /**
     * 每日未还本金列表统计（finance-data/day-not-yet-principal 1）
     * @param int $type 1:当日数据，2：历史数据
     * @return int
     */
    public function actionDayNotYetPrincipal($type = 1){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $db = \Yii::$app->db_kdkj_rd_new;
        if($type == 2){
            $end_time = strtotime(date("Y-m-d", time()));
            $start_time = strtotime('2018-12-12');
        }else{
            $end_time = strtotime(date("Y-m-d", time()));
            $start_time = $end_time - (120*86400);//超过120天的历史数据不再更新
        }

        $sql = "SELECT
                    FROM_UNIXTIME(r.loan_time,'%Y-%m-%d') AS loandate,
                    l.fund_id AS fund_id,
                    SUM(l.money_amount) AS total_principal,
                    SUM(l.counter_fee) AS counter_fee,
                    SUM(r.true_total_money) as true_total_money,
                    SUM(IF(r.is_overdue=0,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS normal_principal,
                    SUM(IF(r.is_overdue>0 AND r.overdue_day>=1 AND r.overdue_day<=10,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s1_principal,
                    SUM(IF(r.is_overdue>0 AND r.overdue_day>=11 AND r.overdue_day<=30,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s2_principal,
                    SUM(IF(r.is_overdue>0 AND r.overdue_day>=31 AND r.overdue_day<=60,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS m1_principal,
                    SUM(IF(r.is_overdue>0 AND r.overdue_day>=61 AND r.overdue_day<=90,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) m2_principal,
                    SUM(IF(r.is_overdue>0 AND r.overdue_day>=91 AND r.overdue_day<=120,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS m3_principal
                FROM tb_user_loan_order_repayment AS r
                LEFT JOIN tb_user_loan_order AS l ON r.order_id = l.id
                LEFT JOIN tb_loan_person AS p ON r.user_id = p.id
                WHERE l.id > 0
                AND r.id > 0
                AND l.order_type = 1
                AND r.loan_time >= {$start_time}
                AND r.loan_time < {$end_time}
                GROUP BY 
                loandate,
                l.fund_id
                ORDER BY r.id DESC";
        $ret = $db->createCommand($sql)->queryAll();
        if(empty($ret)){
            \yii::error("actionDayNotYetPrincipal error sql:{$sql}");
        }else{
            foreach ($ret  as $k => $v){
                $loandate = $v['loandate'];
                $loantime = strtotime($loandate);
                $fund_id = $v['fund_id'];
                $where = [
                    'and',
                    ['=', 'loan_time', $loantime],
                    ['=', 'fund_id', $fund_id],
                ];
                CommonHelper::stdout(sprintf("%s\t%s\n", $loandate, $fund_id));
                $DayNotYetPrincipalStatistics = DayNotYetPrincipalStatistics::find()->where($where)->one();
                if(empty($DayNotYetPrincipalStatistics)){
                    $DayNotYetPrincipalStatistics = new DayNotYetPrincipalStatistics();

                    $DayNotYetPrincipalStatistics->created_at = time();
                }else{
                    $DayNotYetPrincipalStatistics->updated_at=time();
                }
                $DayNotYetPrincipalStatistics->loan_time = $loantime;
                $DayNotYetPrincipalStatistics->fund_id = $fund_id;
                $DayNotYetPrincipalStatistics->loan_principal = $v['total_principal'];
                $DayNotYetPrincipalStatistics->counter_fee = $v['counter_fee'];
                $DayNotYetPrincipalStatistics->true_total_money = $v['true_total_money'];
                $DayNotYetPrincipalStatistics->normal_principal = $v['normal_principal'];
                $DayNotYetPrincipalStatistics->s1_principal = $v['s1_principal'];
                $DayNotYetPrincipalStatistics->s2_principal = $v['s2_principal'];
                $DayNotYetPrincipalStatistics->m1_principal = $v['m1_principal'];
                $DayNotYetPrincipalStatistics->m2_principal = $v['m2_principal'];
                $DayNotYetPrincipalStatistics->m3_principal = $v['m3_principal'];
                if (!$DayNotYetPrincipalStatistics->save()) {
                    Yii::error("{$loantime}-{}每日未还本金列表统计数据保存失败：" . json_encode($v));
                }
            }
        }
        CommonHelper::stdout(sprintf("actionDayNotYetPrincipal %s-%s-%s end.\n", $start_time, $end_time, $type));
    }
}
