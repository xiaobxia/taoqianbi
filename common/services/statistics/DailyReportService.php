<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/1
 * Time: 13:32
 */
namespace common\services\statistics;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use common\models\CreditMg;
use common\models\CreditJxl;
use common\models\CreditTd;
use common\models\CreditJy;
use common\models\CreditZmop;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\Rule;
use common\models\UserCreditTotal;
use common\models\UserDetail;
use common\models\StatsDailyReport;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderDelayLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\StatsGreyAnalysis;

class DailyReportService extends Component
{

    const AUTO = [
        'auto shell','shell auto','机审'
    ];

    public $num = 60;

    /**
     * @param $data
     * @param string $stats_time
     * @param $type
     * @return mixed
     */
    public function inputData($data,$stats_time = '0',$type){
        //有log_id则进行更新操作   无log_id则新建一条记录
        if($stats_time&&$log = StatsDailyReport::find()->where(['stats_date'=>$stats_time,'stats_by'=>$type])->one()){
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->stats_date;
        }else{
            $log = new StatsDailyReport();
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->stats_date;
        }
    }


//    public function getBasicReport(){//获取直接的统计数据
//        $num = $this->num;
//        $today = strtotime(date('Y-m-d'));
//        try{
//            $data_from_db = $this->dataFromGrayReport();//从灰度报表中获取数据
//            while($num>=0){
//                //统计起始时间
//                $datetime_start = strtotime(date('Y-m-d',strtotime('-'.$num.' day')));
//                $datetime_end = strtotime(date('Y-m-d',strtotime('-'.($num+1).' day')));
//                $day = date('Y-m-d',strtotime('-'.($num+1).' day'));
//                $num--;
//                var_dump($day);
//                if(!isset($data_from_db['total'][$day])){//灰度记录不存在则无法统计当天数据
//                    continue;
//                }
//                /***/
//                //机审
//                $order_check_arr = $this->order_check_stats_all($datetime_end,$datetime_start,$data_from_db['money'][$day]['profit']);
//                $order_check_total = $data_from_db['total'][$day]['apply_auto'];//机审量 按笔数
//                $order_check_total_money = $data_from_db['money'][$day]['apply_auto'];
//                $order_check_pass_total = $data_from_db['total'][$day]['pass_auto'];//机审通过量 按笔数
//                $order_check_pass_total_money = $data_from_db['money'][$day]['pass_auto'];
//                if($order_check_total){
//                    $order_check_pass_total_percent = sprintf("%0.2f", $order_check_pass_total / $order_check_total*100).'%';
//                }else{$order_check_pass_total_percent = 0;}
//                if($order_check_total_money){
//                    $order_check_pass_total_money_percent = sprintf("%0.2f", $order_check_pass_total_money / $order_check_total_money*100).'%';
//                }else{$order_check_pass_total_money_percent = 0;}
//                $order_check_people_total = $data_from_db['total'][$day]['apply_man'];//人工审核量 按笔数
//                $order_check_people_total_money = $data_from_db['money'][$day]['apply_man'];//人工审核量 按金额
//                $order_check_people_pass_total = $data_from_db['total'][$day]['pass_man'];//人工通过量 按笔数
//                $order_check_people_pass_total_money = $data_from_db['money'][$day]['pass_man'];//人工通过量 金额
//                if($order_check_people_total){
//                    $order_check_people_pass_total_percent = sprintf("%0.2f", $order_check_people_pass_total / $order_check_people_total*100).'%';//人工通过率 按笔数
//                }else{$order_check_people_pass_total_percent = 0;}
//                if($order_check_people_total_money){
//                    $order_check_people_pass_total_money_percent = sprintf("%0.2f", $order_check_people_pass_total_money / $order_check_people_total_money*100).'%';//人工通过率 按金额
//                }else{$order_check_people_pass_total_money_percent = 0;}
//
//                $order_total = $order_check_total+$order_check_people_total;
//                $order_total_money = $order_check_total_money+$order_check_people_total_money;
//
//                //通过量
//                $pass_total = $order_check_pass_total+$order_check_people_pass_total;//通过量 处理的订单量
//                $pass_total_money = $order_check_pass_total_money+$order_check_people_pass_total_money;//通过量 处理的订单的金额数
//                if($order_total){
//                    $order_pass_total_percent = sprintf("%0.2f", $pass_total / ($order_check_total+$order_check_people_total)*100).'%';//通过率 按笔数
//                }else{
//                    $order_pass_total_percent = 0;
//                }
//                if($order_total_money){
//                    $order_pass_total_money_percent = sprintf("%0.2f", $pass_total_money /($order_check_total_money+$order_check_people_total_money)*100).'%';//通过率 按金额
//                }else{
//                    $order_pass_total_money_percent = 0;
//                }
//
//                //待还总量 按笔数 还款时间为今天的 未还款的单子 (理解为还款时间为A号 逾期天数为一天的)
//                $stay_repayment_total = UserLoanOrderRepayment::find()
//                    ->where('plan_fee_time >= '.$datetime_end.' AND plan_fee_time < '.$datetime_start)
//                    ->andWhere('overdue_day>=1')
//                    ->count('order_id',Yii::$app->get('db_kdkj_rd'));
//                //待还总量 按金额
//                $stay_repayment_total_money_tmp = UserLoanOrderRepayment::find()->select('SUM(principal) as total_money')->where('plan_fee_time >= '.$datetime_end.' AND plan_fee_time < '.$datetime_start)->andWhere('overdue_day>=1')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
//                $stay_repayment_total_money = $stay_repayment_total_money_tmp['total_money']/100;
//
//                //逾期
//                $overdue_day_3_total_percent = $order_check_arr['overdue_3_day_total_percent'];//逾期率3+ 按笔数
//                $overdue_day_3_total_money_percent = $order_check_arr['overdue_3_day_total_money_percent'];//逾期率3+ 按金额
//
//                $overdue_day_10_total_percent = $order_check_arr['overdue_10_day_total_percent'];//逾期率10+ 按笔数
//                $overdue_day_10_total_money_percent = $order_check_arr['overdue_10_day_total_money_percent'];//逾期率10+ 按金额
//
//                $overdue_day_30_total_percent = $order_check_arr['overdue_30_day_total_percent'];//逾期率30+ 按笔数
//                $overdue_day_30_total_money_percent = $order_check_arr['overdue_30_day_total_money_percent'];//逾期率30+ 按金额
//
//                //纯利润
//                $pure_profit = $data_from_db['money'][$day]['profit'];
//                $today_loan_money = $order_check_arr['today_loan_money'];
//                //风控系数 只有金额时计算
//                if(!empty($today_loan_money)){
//                    $risk_control = sprintf("%0.4f", $pure_profit / $today_loan_money);
//                }else{
//                    $risk_control = 0;
//                }
//
//                $collection_arr_total = [];
//                $collection_arr_money = [];
//
//                if($order_total){//总订单数 按笔数
//                    $collection_arr_total['order_total'] = $order_total;
//                }else{
//                    $collection_arr_total['order_total'] = 0;
//                }
//                if($order_total_money){//总订单 申请金额
//                    $collection_arr_money['order_total_money'] = $order_total_money;
//                }else{
//                    $collection_arr_money['order_total_money'] = 0;
//                }
//                if($order_check_total){//机审量 按笔数
//                    $collection_arr_total['order_check_total'] = $order_check_total;
//                }else{
//                    $collection_arr_total['order_check_total'] = 0;
//                }
//                if($order_check_total_money){//机审量 按金额
//                    $collection_arr_money['order_check_total_money'] = $order_check_total_money;
//                }else{
//                    $collection_arr_money['order_check_total_money'] = 0;
//                }
//                if($pass_total){//通过量 还款表当日创建的订单
//                    $collection_arr_total['pass_total'] = $pass_total;
//                }else{
//                    $collection_arr_total['pass_total'] = 0;
//                }
//                if($pass_total_money){//通过量 还款表当日金额数
//                    $collection_arr_money['pass_total_money'] = $pass_total_money;
//                }else{
//                    $collection_arr_money['pass_total_money'] = 0;
//                }
//                if($order_check_people_total){//人工审核量 按笔数
//                    $collection_arr_total['order_check_people_total'] = $order_check_people_total;
//                }else{
//                    $collection_arr_total['order_check_people_total'] = 0;
//                }
//                if($order_check_people_total_money){//人工审核量 按金额
//                    $collection_arr_money['order_check_people_total_money'] = $order_check_people_total_money;
//                }else{
//                    $collection_arr_money['order_check_people_total_money'] = 0;
//                }
//                if($order_check_pass_total){//机审通过量 按笔数
//                    $collection_arr_total['order_check_pass_total'] = $order_check_pass_total;
//                }else{
//                    $collection_arr_total['order_check_pass_total'] = 0;
//                }
//                if($order_check_pass_total_money){//机审通过量 按金额
//                    $collection_arr_money['order_check_pass_total_money'] = $order_check_pass_total_money;
//                }else{
//                    $collection_arr_money['order_check_pass_total_money'] = 0;
//                }
//                if($order_check_pass_total_percent){//机审通过率 按笔数
//                    $collection_arr_total['order_check_pass_total_percent'] = $order_check_pass_total_percent;
//                }else{
//                    $collection_arr_total['order_check_pass_total_percent'] = 0;
//                }
//                if($order_check_pass_total_money_percent){//机审通过率 按金额
//                    $collection_arr_money['order_check_pass_total_money_percent'] = $order_check_pass_total_money_percent;
//                }else{
//                    $collection_arr_money['order_check_pass_total_money_percent'] = 0;
//                }
//                if($overdue_day_3_total_percent){//逾期率3+ 按笔数
//                    $collection_arr_total['overdue_day_3_total_percent'] = $overdue_day_3_total_percent;
//                }else{
//                    $collection_arr_total['overdue_day_3_total_percent'] = 0;
//                }
//                if($overdue_day_3_total_money_percent){//逾期率3+ 按金额
//                    $collection_arr_money['overdue_day_3_total_money_percent'] = $overdue_day_3_total_money_percent;
//                }else{
//                    $collection_arr_money['overdue_day_3_total_money_percent'] = 0;
//                }
//                if($overdue_day_10_total_percent){//逾期率10+ 按笔数
//                    $collection_arr_total['overdue_day_10_total_percent'] = $overdue_day_10_total_percent;
//                }else{
//                    $collection_arr_total['overdue_day_10_total_percent'] = 0;
//                }
//                if($overdue_day_10_total_money_percent){//逾期率10+ 按金额
//                    $collection_arr_money['overdue_day_10_total_money_percent'] = $overdue_day_10_total_money_percent;
//                }else{
//                    $collection_arr_money['overdue_day_10_total_money_percent'] = 0;
//                }
//                if($overdue_day_30_total_percent){//逾期率30+ 按笔数
//                    $collection_arr_total['overdue_day_30_total_percent'] = $overdue_day_30_total_percent;
//                }else{
//                    $collection_arr_total['overdue_day_30_total_percent'] = 0;
//                }
//                if($overdue_day_30_total_money_percent){//逾期率30+ 按金额
//                    $collection_arr_money['overdue_day_30_total_money_percent'] = $overdue_day_30_total_money_percent;
//                }else{
//                    $collection_arr_money['overdue_day_30_total_money_percent'] = 0;
//                }
//                if($order_check_people_pass_total){//人工通过量 按笔数
//                    $collection_arr_total['order_check_people_pass_total'] = $order_check_people_pass_total;
//                }else{
//                    $collection_arr_total['order_check_people_pass_total'] = 0;
//                }
//                if($order_check_people_pass_total_money){//人工通过量 按金额
//                    $collection_arr_money['order_check_people_pass_total_money'] = $order_check_people_pass_total_money;
//                }else{
//                    $collection_arr_money['order_check_people_pass_total_money'] = 0;
//                }
//                if($order_check_people_pass_total_percent){//人工通过率 按笔数
//                    $collection_arr_total['order_check_people_pass_total_percent'] = $order_check_people_pass_total_percent;
//                }else{
//                    $collection_arr_total['order_check_people_pass_total_percent'] = 0;
//                }
//                if($order_check_people_pass_total_money_percent){//人工通过率 按金额
//                    $collection_arr_money['order_check_people_pass_total_money_percent'] = $order_check_people_pass_total_money_percent;
//                }else{
//                    $collection_arr_money['order_check_people_pass_total_money_percent'] = 0;
//                }
//                if($stay_repayment_total){//待还总量 按笔数
//                    $collection_arr_total['stay_repayment_total'] = $stay_repayment_total;
//                }else{
//                    $collection_arr_total['stay_repayment_total'] = 0;
//                }
//                if($stay_repayment_total_money){//待还总量 按金额
//                    $collection_arr_money['stay_repayment_total_money'] = $stay_repayment_total_money;
//                }else{
//                    $collection_arr_money['stay_repayment_total_money'] = 0;
//                }
//                if($order_pass_total_percent){//通过率 按笔数
//                    $collection_arr_total['order_pass_total_percent'] = $order_pass_total_percent;
//                }else{
//                    $collection_arr_total['order_pass_total_percent'] = 0;
//                }
//                if($order_pass_total_money_percent){//通过率 按金额
//                    $collection_arr_money['order_pass_total_money_percent'] = $order_pass_total_money_percent;
//                }else{
//                    $collection_arr_money['order_pass_total_money_percent'] = 0;
//                }
//                if($pure_profit){//纯利润 只有金额时计算
//                    $collection_arr_money['pure_profit'] = $pure_profit;
//                }else{
//                    $collection_arr_money['pure_profit'] = 0;
//                }
//                if($risk_control){//风控系数 只有金额时计算
//                    $collection_arr_money['risk_control'] = $risk_control;
//                }else{
//                    $collection_arr_money['risk_control'] = 0;
//                }
//                $json_data_total = json_encode($collection_arr_total);
//                $json_data_money = json_encode($collection_arr_money);
//
//                //按笔数统计的数据写入数据库
//                $data_insert_total = [
//                    'stats_date'=>$day,
//                    'stats_xdata'=>$json_data_total,
//                    'stats_by'=>StatsDailyReport::TYPE_ORDER,
//                ];
//                $this->inputData($data_insert_total,$day,StatsDailyReport::TYPE_ORDER);
//
//                //按金额统计的数据写入数据库
//                $data_insert_money = [
//                    'stats_date'=>$day,
//                    'stats_xdata'=>$json_data_money,
//                    'stats_by'=>StatsDailyReport::TYPE_MONEY,
//                ];
//                $this->inputData($data_insert_money,$day,StatsDailyReport::TYPE_MONEY);
//            }
//        }catch (\Exception $e){
//            var_dump($e->getMessage());
//            var_dump($e->getTraceAsString());
//            //file_put_contents('/tmp/lfj/err_daily_report.txt',$e->getTraceAsString());
//        }
//    }

    public function getBasicReport(){//获取直接的统计数据
        $num = $this->num;
        $today = strtotime(date('Y-m-d'));
        try{
            $data_from_db = $this->dataFromGrayReport();//从灰度报表中获取数据
            while($num>=0){
                //统计起始时间
                $datetime_start = strtotime(date('Y-m-d',strtotime('-'.$num.' day')));
                $datetime_end = strtotime(date('Y-m-d',strtotime('-'.($num+1).' day')));
                $day = date('Y-m-d',strtotime('-'.($num+1).' day'));
                $num--;
                var_dump($day);
                if(!isset($data_from_db['total'][$day])){//灰度记录不存在则无法统计当天数据
                    continue;
                }
                /***/
                //机审
                $order_check_arr = $this->order_check_stats_all($datetime_end,$datetime_start,$data_from_db['money'][$day]['profit']);
                $order_check_total = $data_from_db['total'][$day]['apply_auto'];//机审量 按笔数
                $order_check_total_money = $data_from_db['money'][$day]['apply_auto'];
                $order_check_pass_total = $data_from_db['total'][$day]['pass_auto'];//机审通过量 按笔数
                $order_check_pass_total_money = $data_from_db['money'][$day]['pass_auto'];
                if($order_check_total){
                    $order_check_pass_total_percent = sprintf("%0.2f", $order_check_pass_total / $order_check_total*100).'%';
                }else{$order_check_pass_total_percent = 0;}
                if($order_check_total_money){
                    $order_check_pass_total_money_percent = sprintf("%0.2f", $order_check_pass_total_money / $order_check_total_money*100).'%';
                }else{$order_check_pass_total_money_percent = 0;}
                $order_check_people_total = $data_from_db['total'][$day]['apply_man'];//人工审核量 按笔数
                $order_check_people_total_money = $data_from_db['money'][$day]['apply_man'];//人工审核量 按金额
                $order_check_people_pass_total = $data_from_db['total'][$day]['pass_man'];//人工通过量 按笔数
                $order_check_people_pass_total_money = $data_from_db['money'][$day]['pass_man'];//人工通过量 金额
                if($order_check_people_total){
                    $order_check_people_pass_total_percent = sprintf("%0.2f", $order_check_people_pass_total / $order_check_people_total*100).'%';//人工通过率 按笔数
                }else{$order_check_people_pass_total_percent = 0;}
                if($order_check_people_total_money){
                    $order_check_people_pass_total_money_percent = sprintf("%0.2f", $order_check_people_pass_total_money / $order_check_people_total_money*100).'%';//人工通过率 按金额
                }else{$order_check_people_pass_total_money_percent = 0;}

                $order_total = $order_check_total+$order_check_people_total;
                $order_total_money = $order_check_total_money+$order_check_people_total_money;

                //通过量
                $pass_total = $order_check_pass_total+$order_check_people_pass_total;//通过量 处理的订单量
                $pass_total_money = $order_check_pass_total_money+$order_check_people_pass_total_money;//通过量 处理的订单的金额数
                if($order_total){
                    $order_pass_total_percent = sprintf("%0.2f", $pass_total / ($order_check_total+$order_check_people_total)*100).'%';//通过率 按笔数
                }else{
                    $order_pass_total_percent = 0;
                }
                if($order_total_money){
                    $order_pass_total_money_percent = sprintf("%0.2f", $pass_total_money /($order_check_total_money+$order_check_people_total_money)*100).'%';//通过率 按金额
                }else{
                    $order_pass_total_money_percent = 0;
                }

                //待还总量 按笔数 还款时间为今天的 未还款的单子 (理解为还款时间为A号 逾期天数为一天的)
                $stay_repayment_total = UserLoanOrderRepayment::find()
                    ->where('plan_fee_time >= '.$datetime_end.' AND plan_fee_time < '.$datetime_start)
                    ->andWhere('overdue_day>=1')
                    ->count('order_id',Yii::$app->get('db_kdkj_rd'));
                //待还总量 按金额
                $stay_repayment_total_money_tmp = UserLoanOrderRepayment::find()->select('SUM(principal) as total_money')->where('plan_fee_time >= '.$datetime_end.' AND plan_fee_time < '.$datetime_start)->andWhere('overdue_day>=1')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
                $stay_repayment_total_money = $stay_repayment_total_money_tmp['total_money']/100;

                //逾期
                $overdue_day_3_total_percent = $order_check_arr['overdue_3_day_total_percent'];//逾期率3+ 按笔数
                $overdue_day_3_total_money_percent = $order_check_arr['overdue_3_day_total_money_percent'];//逾期率3+ 按金额
                $overdue_day_3_total_nur = $order_check_arr['overdue_3_day_total_nur'];
                $overdue_day_3_total_der = $order_check_arr['overdue_3_day_total_der'];
                $overdue_day_3_total_money_nur = $order_check_arr['overdue_3_day_total_money_nur'];
                $overdue_day_3_total_money_der = $order_check_arr['overdue_3_day_total_money_der'];

                $overdue_day_10_total_percent = $order_check_arr['overdue_10_day_total_percent'];//逾期率10+ 按笔数
                $overdue_day_10_total_money_percent = $order_check_arr['overdue_10_day_total_money_percent'];//逾期率10+ 按金额
                $overdue_day_10_total_nur = $order_check_arr['overdue_10_day_total_nur'];
                $overdue_day_10_total_der = $order_check_arr['overdue_10_day_total_der'];
                $overdue_day_10_total_money_nur = $order_check_arr['overdue_10_day_total_money_nur'];
                $overdue_day_10_total_money_der = $order_check_arr['overdue_10_day_total_money_der'];

                $overdue_day_30_total_percent = $order_check_arr['overdue_30_day_total_percent'];//逾期率30+ 按笔数
                $overdue_day_30_total_money_percent = $order_check_arr['overdue_30_day_total_money_percent'];//逾期率30+ 按金额
                $overdue_day_30_total_nur = $order_check_arr['overdue_30_day_total_nur'];
                $overdue_day_30_total_der = $order_check_arr['overdue_30_day_total_der'];
                $overdue_day_30_total_money_nur = $order_check_arr['overdue_30_day_total_money_nur'];
                $overdue_day_30_total_money_der = $order_check_arr['overdue_30_day_total_money_der'];
                //纯利润
                $pure_profit = $data_from_db['money'][$day]['profit'];
                $today_loan_money = $order_check_arr['today_loan_money'];
                //风控系数 只有金额时计算
                if(!empty($today_loan_money)){
                    $risk_control = sprintf("%0.4f", $pure_profit / $today_loan_money);
                }else{
                    $risk_control = 0;
                }

                $collection_arr_total = [];
                $collection_arr_money = [];

                if($order_total){//总订单数 按笔数
                    $collection_arr_total['order_total'] = $order_total;
                }else{
                    $collection_arr_total['order_total'] = 0;
                }
                if($order_total_money){//总订单 申请金额
                    $collection_arr_money['order_total_money'] = $order_total_money;
                }else{
                    $collection_arr_money['order_total_money'] = 0;
                }
                if($order_check_total){//机审量 按笔数
                    $collection_arr_total['order_check_total'] = $order_check_total;
                }else{
                    $collection_arr_total['order_check_total'] = 0;
                }
                if($order_check_total_money){//机审量 按金额
                    $collection_arr_money['order_check_total_money'] = $order_check_total_money;
                }else{
                    $collection_arr_money['order_check_total_money'] = 0;
                }
                if($pass_total){//通过量 还款表当日创建的订单
                    $collection_arr_total['pass_total'] = $pass_total;
                }else{
                    $collection_arr_total['pass_total'] = 0;
                }
                if($pass_total_money){//通过量 还款表当日金额数
                    $collection_arr_money['pass_total_money'] = $pass_total_money;
                }else{
                    $collection_arr_money['pass_total_money'] = 0;
                }
                if($order_check_people_total){//人工审核量 按笔数
                    $collection_arr_total['order_check_people_total'] = $order_check_people_total;
                }else{
                    $collection_arr_total['order_check_people_total'] = 0;
                }
                if($order_check_people_total_money){//人工审核量 按金额
                    $collection_arr_money['order_check_people_total_money'] = $order_check_people_total_money;
                }else{
                    $collection_arr_money['order_check_people_total_money'] = 0;
                }
                if($order_check_pass_total){//机审通过量 按笔数
                    $collection_arr_total['order_check_pass_total'] = $order_check_pass_total;
                }else{
                    $collection_arr_total['order_check_pass_total'] = 0;
                }
                if($order_check_pass_total_money){//机审通过量 按金额
                    $collection_arr_money['order_check_pass_total_money'] = $order_check_pass_total_money;
                }else{
                    $collection_arr_money['order_check_pass_total_money'] = 0;
                }
                if($order_check_pass_total_percent){//机审通过率 按笔数
                    $collection_arr_total['order_check_pass_total_percent'] = $order_check_pass_total_percent;
                }else{
                    $collection_arr_total['order_check_pass_total_percent'] = 0;
                }
                if($order_check_pass_total_money_percent){//机审通过率 按金额
                    $collection_arr_money['order_check_pass_total_money_percent'] = $order_check_pass_total_money_percent;
                    $collection_arr_money['order_check_pass_total_money_percent_nur'] = $order_check_pass_total_money;
                    $collection_arr_money['order_check_pass_total_money_percent_der'] = $order_check_total_money;
                }else{
                    $collection_arr_money['order_check_pass_total_money_percent'] = 0;
                    $collection_arr_money['order_check_pass_total_money_percent_nur'] = 0;
                    $collection_arr_money['order_check_pass_total_money_percent_der'] = 0;
                }
                if($overdue_day_3_total_percent){//逾期率3+ 按笔数
                    $collection_arr_total['overdue_day_3_total_percent'] = $overdue_day_3_total_percent;
                }else{
                    $collection_arr_total['overdue_day_3_total_percent'] = 0;
                }
                $collection_arr_total['overdue_day_3_total_nur'] = $overdue_day_3_total_nur;
                $collection_arr_total['overdue_day_3_total_der'] = $overdue_day_3_total_der;

                if($overdue_day_3_total_money_percent){//逾期率3+ 按金额
                    $collection_arr_money['overdue_day_3_total_money_percent'] = $overdue_day_3_total_money_percent;
                }else{
                    $collection_arr_money['overdue_day_3_total_money_percent'] = 0;
                }
                $collection_arr_money['overdue_day_3_total_money_nur'] = $overdue_day_3_total_money_nur;
                $collection_arr_money['overdue_day_3_total_money_der'] = $overdue_day_3_total_money_der;

                if($overdue_day_10_total_percent){//逾期率10+ 按笔数
                    $collection_arr_total['overdue_day_10_total_percent'] = $overdue_day_10_total_percent;
                }else{
                    $collection_arr_total['overdue_day_10_total_percent'] = 0;
                }
                $collection_arr_total['overdue_day_10_total_nur'] = $overdue_day_10_total_nur;
                $collection_arr_total['overdue_day_10_total_der'] = $overdue_day_10_total_der;

                if($overdue_day_10_total_money_percent){//逾期率10+ 按金额
                    $collection_arr_money['overdue_day_10_total_money_percent'] = $overdue_day_10_total_money_percent;
                }else{
                    $collection_arr_money['overdue_day_10_total_money_percent'] = 0;
                }
                $collection_arr_money['overdue_day_10_total_money_money_nur'] = $overdue_day_10_total_money_nur;
                $collection_arr_money['overdue_day_10_total_money_money_der'] = $overdue_day_10_total_money_der;

                if($overdue_day_30_total_percent){//逾期率30+ 按笔数
                    $collection_arr_total['overdue_day_30_total_percent'] = $overdue_day_30_total_percent;
                }else{
                    $collection_arr_total['overdue_day_30_total_percent'] = 0;
                }
                $collection_arr_total['overdue_day_30_total_nur'] = $overdue_day_30_total_nur;
                $collection_arr_total['overdue_day_30_total_der'] = $overdue_day_30_total_der;

                if($overdue_day_30_total_money_percent){//逾期率30+ 10金额
                    $collection_arr_money['overdue_day_30_total_money_percent'] = $overdue_day_30_total_money_percent;
                }else{
                    $collection_arr_money['overdue_day_30_total_money_percent'] = 0;
                }
                $collection_arr_money['overdue_day_30_total_money_nur'] = $overdue_day_30_total_money_nur;
                $collection_arr_money['overdue_day_30_total_money_der'] = $overdue_day_30_total_money_der;

                if($order_check_people_pass_total){//人工通过量 按笔数
                    $collection_arr_total['order_check_people_pass_total'] = $order_check_people_pass_total;
                }else{
                    $collection_arr_total['order_check_people_pass_total'] = 0;
                }
                if($order_check_people_pass_total_money){//人工通过量 按金额
                    $collection_arr_money['order_check_people_pass_total_money'] = $order_check_people_pass_total_money;
                }else{
                    $collection_arr_money['order_check_people_pass_total_money'] = 0;
                }
                if($order_check_people_pass_total_percent){//人工通过率 按笔数
                    $collection_arr_total['order_check_people_pass_total_percent'] = $order_check_people_pass_total_percent;
                }else{
                    $collection_arr_total['order_check_people_pass_total_percent'] = 0;
                }
                if($order_check_people_pass_total_money_percent){//人工通过率 按金额
                    $collection_arr_money['order_check_people_pass_total_money_percent'] = $order_check_people_pass_total_money_percent;
                }else{
                    $collection_arr_money['order_check_people_pass_total_money_percent'] = 0;
                }
                if($stay_repayment_total){//待还总量 按笔数
                    $collection_arr_total['stay_repayment_total'] = $stay_repayment_total;
                }else{
                    $collection_arr_total['stay_repayment_total'] = 0;
                }
                if($stay_repayment_total_money){//待还总量 按金额
                    $collection_arr_money['stay_repayment_total_money'] = $stay_repayment_total_money;
                }else{
                    $collection_arr_money['stay_repayment_total_money'] = 0;
                }
                if($order_pass_total_percent){//通过率 按笔数
                    $collection_arr_total['order_pass_total_percent'] = $order_pass_total_percent;
                }else{
                    $collection_arr_total['order_pass_total_percent'] = 0;
                }
                if($order_pass_total_money_percent){//通过率 按金额
                    $collection_arr_money['order_pass_total_money_percent'] = $order_pass_total_money_percent;
                }else{
                    $collection_arr_money['order_pass_total_money_percent'] = 0;
                }
                if($pure_profit){//纯利润 只有金额时计算
                    $collection_arr_money['pure_profit'] = $pure_profit;
                }else{
                    $collection_arr_money['pure_profit'] = 0;
                }
                if($risk_control){//风控系数 只有金额时计算
                    $collection_arr_money['risk_control'] = $risk_control;
                }else{
                    $collection_arr_money['risk_control'] = 0;
                }
                $json_data_total = json_encode($collection_arr_total);
                $json_data_money = json_encode($collection_arr_money);

                //按笔数统计的数据写入数据库
                $data_insert_total = [
                    'stats_date'=>$day,
                    'stats_xdata'=>$json_data_total,
                    'stats_by'=>StatsDailyReport::TYPE_ORDER,
                ];
                $this->inputData($data_insert_total,$day,StatsDailyReport::TYPE_ORDER);

                //按金额统计的数据写入数据库
                $data_insert_money = [
                    'stats_date'=>$day,
                    'stats_xdata'=>$json_data_money,
                    'stats_by'=>StatsDailyReport::TYPE_MONEY,
                ];
                $this->inputData($data_insert_money,$day,StatsDailyReport::TYPE_MONEY);
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            //file_put_contents('/tmp/lfj/err_daily_report.txt',$e->getTraceAsString());
        }
    }
    /**
     * @param $day_num
     * @param $today
     * @param $order_id_arr
     * @return array
     * @throws yii\base\InvalidConfigException
     * 逾期率计算
     */
    public function overdue_stats($day_num,$today,$order_id_arr){
        $arr = [];
        //逾期率$num+ 按笔数
        $day_end = $today-86400*$day_num;
        $overdue_day_total_count = count($order_id_arr);
        $page_start = 0;
        $page_size = 10000;
        $overdue_day_total_all_count = 0;
        $overdue_day_total_hit = 0;
        $overdue_day_total_money_all = 0;
        $overdue_day_total_money_hit = 0;
        while($overdue_day_total_count>$page_start){
            $order_id_tmp = [];
            for($i = $page_start; $i < ($page_start+$page_size);$i++){
                if(isset($order_id_arr[$i])){
                    $order_id_tmp[] = $order_id_arr[$i];
                }
            }
            if($order_id_tmp){
                $overdue_day_total_all = UserLoanOrderRepayment::find()
                    ->select('order_id,overdue_day,principal')
                    ->where(['order_id'=>$order_id_tmp])
                    ->andWhere('plan_fee_time<'.$day_end)
                    ->orderBy('order_id asc')
                    ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                if($overdue_day_total_all){
                    foreach($overdue_day_total_all as $item){
                        if($item['overdue_day']>$day_num){
                            $overdue_day_total_hit++;//逾期满足$day_num天的总单数
                            $overdue_day_total_money_hit += $item['principal']/100;//金额
                        }
                        $overdue_day_total_all_count++;//逾期总单数
                        $overdue_day_total_money_all += $item['principal']/100;//金额
                    }
                }
            }
            $page_start += $page_size;
        }
        unset($overdue_day_total_all);
        //逾期率$num+ 按笔数
        if(!empty($overdue_day_total_all_count)){
            $overdue_day_total_percent = sprintf("%0.2f", (int)$overdue_day_total_hit / $overdue_day_total_all_count*100).'%';
        }else{
            $overdue_day_total_percent = 0;
        }
        $arr['overdue_day_total_nur'] = $overdue_day_total_hit;
        $arr['overdue_day_total_der'] = $overdue_day_total_all_count;
        $arr['overdue_day_total_percent'] = $overdue_day_total_percent;
        //逾期率$num+ 按金额
        if(!empty($overdue_day_total_money_all)){
            $overdue_day_total_money_percent = sprintf("%0.2f", $overdue_day_total_money_hit / $overdue_day_total_money_all*100).'%';
        }else{
            $overdue_day_total_money_percent = 0;
        }
        $arr['overdue_day_total_money_nur'] = $overdue_day_total_money_hit;
        $arr['overdue_day_total_money_der'] = $overdue_day_total_money_all;
        $arr['overdue_day_total_money_percent'] = $overdue_day_total_money_percent;
        return $arr;
    }

    /**
     * @param $datetime_end
     * @param $datetime_start
     * @param $profit
     * @return mixed
     * @throws yii\base\InvalidConfigException
     * 逾期计算 风控系数计算
     */
    public function order_check_stats_all($datetime_end,$datetime_start,$profit){
        $order_total = UserOrderLoanCheckLog::find()
            ->select('order_id')->distinct()
            ->where('created_at>='.$datetime_end.' AND created_at<'.$datetime_start)
            ->andWhere('before_status = 0')
            ->andWhere('`type` = 1')
            ->andWhere(['after_status'=>7])
            ->count('order_id',Yii::$app->get('db_kdkj_rd'));
        $page_size = 10000;
        $page_start = 0;
        $today_order_check_total_order_id = [];//A号机审通过的全部订单
        while($order_total>$page_start){
            $order_total_arr = UserOrderLoanCheckLog::find()
                ->select('order_id')->distinct()
                ->where('created_at>='.$datetime_end.' AND created_at<'.$datetime_start)
                ->andWhere('before_status = 0')
                ->andWhere('`type` = 1')
                ->andWhere(['after_status'=>7])
                ->orderBy('order_id asc')
                ->limit($page_size)->offset($page_start)
                ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            foreach($order_total_arr as $item){
                $today_order_check_total_order_id[] = $item['order_id'];
            }
            $page_start += $page_size;
        }

        //逾期计算
        $today = strtotime(date('Y-m-d'));
        $overdue_day_3_arr = $this->overdue_stats(3,$today,$today_order_check_total_order_id);
        $arr['overdue_3_day_total_percent'] = $overdue_day_3_arr['overdue_day_total_percent'];//逾期率3+ 按笔数
        $arr['overdue_3_day_total_money_percent'] = $overdue_day_3_arr['overdue_day_total_money_percent'];//逾期率3+ 按金额
        $arr['overdue_3_day_total_nur'] = $overdue_day_3_arr['overdue_day_total_nur'];
        $arr['overdue_3_day_total_der'] = $overdue_day_3_arr['overdue_day_total_der'];
        $arr['overdue_3_day_total_money_nur'] = $overdue_day_3_arr['overdue_day_total_money_nur'];
        $arr['overdue_3_day_total_money_der'] = $overdue_day_3_arr['overdue_day_total_money_der'];


        $overdue_day_10_arr = $this->overdue_stats(10,$today,$today_order_check_total_order_id);
        $arr['overdue_10_day_total_percent'] = $overdue_day_10_arr['overdue_day_total_percent'];//逾期率10+ 按笔数
        $arr['overdue_10_day_total_money_percent'] = $overdue_day_10_arr['overdue_day_total_money_percent'];//逾期率10+ 按金额
        $arr['overdue_10_day_total_nur'] = $overdue_day_10_arr['overdue_day_total_nur'];
        $arr['overdue_10_day_total_der'] = $overdue_day_10_arr['overdue_day_total_der'];
        $arr['overdue_10_day_total_money_nur'] = $overdue_day_10_arr['overdue_day_total_money_nur'];
        $arr['overdue_10_day_total_money_der'] = $overdue_day_10_arr['overdue_day_total_money_der'];

        $overdue_day_30_arr = $this->overdue_stats(30,$today,$today_order_check_total_order_id);
        $arr['overdue_30_day_total_percent'] = $overdue_day_30_arr['overdue_day_total_percent'];//逾期率30+ 按笔数
        $arr['overdue_30_day_total_money_percent'] = $overdue_day_30_arr['overdue_day_total_money_percent'];//逾期率30+ 按金额
        $arr['overdue_30_day_total_nur'] = $overdue_day_30_arr['overdue_day_total_nur'];
        $arr['overdue_30_day_total_der'] = $overdue_day_30_arr['overdue_day_total_der'];
        $arr['overdue_30_day_total_money_nur'] = $overdue_day_30_arr['overdue_day_total_money_nur'];
        $arr['overdue_30_day_total_money_der'] = $overdue_day_30_arr['overdue_day_total_money_der'];

        $pure_profit_arr = $this->pure_profit_stats($today_order_check_total_order_id,$profit);//风控系数计算
        $arr['today_loan_money'] = $pure_profit_arr['today_loan_money'];

        return $arr;
    }

    /**
     * @param $order_id_arr
     * @param $profit
     * @return array
     * @throws yii\base\InvalidConfigException
     * 放款金额计算
     */
    public function pure_profit_stats($order_id_arr,$profit){
        $arr = [];
        //放款金额计算
        $get_repayment_order_id_total = count($order_id_arr);//A号通过的订单总数
        $page_start = 0;
        $page_size = 10000;
        $today_push_money = 0;
        while($get_repayment_order_id_total>$page_start) {
            $order_id_tmp = [];
            for($i = $page_start; $i < ($page_start+$page_size);$i++){
                if(isset($order_id_arr[$i])){
                    $order_id_tmp[] = $order_id_arr[$i];
                }
            }
            $in_repayment_arr = [];
            //Yii::$app->get('db_kdkj_rd')
            //通过的订单 已放款的订单的放款金额（申请金额-手续费）
            //成功放款的订单
            $in_repayment_arr_tmp = UserLoanOrderRepayment::find()->select('order_id')
                ->where(['order_id'=>$order_id_tmp])
                ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            if($in_repayment_arr_tmp){
                foreach($in_repayment_arr_tmp as $item){
                    $in_repayment_arr[] = $item['order_id'];
                }
                $count_fee_and_money_amount = UserLoanOrder::find()->select('counter_fee,money_amount')
                    ->where(['id'=>$in_repayment_arr])
                    ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                foreach($count_fee_and_money_amount as $item){
                    $today_push_money += ($item['money_amount']-$item['counter_fee'])/100;//放款金额
                }
            }
            $page_start += $page_size;
        }
        $arr['today_loan_money'] = $today_push_money;
        unset($get_repayment_order_id_arr);
        return $arr;
    }

    /**
     * @return array
     * 从灰度分析报表中获取数据
     */
    public function dataFromGrayReport(){
        $day_num = ($this->num)+1;
        $arr = [];
        $flag = 0;
        while($day_num>=0){
            $sql_date = date('Y-m-d',strtotime('-'.$day_num.' day'));
            $data_from_db = StatsGreyAnalysis::find()->where(['stats_date'=>$sql_date])->asArray()->all();
            foreach($data_from_db as $item){
                $arr[$sql_date][$item['version']] = ['stats_amount'=>$item['stats_amount'],'stats_number'=>$item['stats_number']];//决策树 金额统计 订单统计
            }
            $day_num--;
        }
        $new_data_total = [];
        $new_data_money = [];
        foreach($arr as $key_day=>$day_content){//$key_day 日期
            $new_data_total_tmp = [];
            $new_data_money_tmp = [];
            foreach($day_content as $v=>$item){//$v 决策树版本
                foreach(json_decode($item['stats_number']) as $key=>$obj_content){
                    $arr_tmp[$key] = $obj_content;
                }
                $new_data_total_tmp[] = $arr_tmp;
                $arr_tmp = [];
                foreach(json_decode($item['stats_amount']) as $key=>$obj_content){
                    $arr_tmp[$key] = $obj_content;
                }
                $new_data_money_tmp[] = $arr_tmp;
                $arr_tmp = [];
            }
            foreach($new_data_total_tmp as $item){//订单数据
                if(isset($new_data_total[$key_day]['apply_auto'])){
                    $new_data_total[$key_day]['apply_auto'] += $item['apply_auto'];
                }else{
                    $new_data_total[$key_day]['apply_auto'] = $item['apply_auto'];
                }

                if(isset($new_data_total[$key_day]['apply_man'])){
                    $new_data_total[$key_day]['apply_man'] += $item['apply_man'];
                }else{
                    $new_data_total[$key_day]['apply_man'] = $item['apply_man'];
                }

                if(isset($new_data_total[$key_day]['apply_total'])){
                    $new_data_total[$key_day]['apply_total'] += $item['apply_total'];
                }else{
                    $new_data_total[$key_day]['apply_total'] = $item['apply_total'];
                }

                if(isset($new_data_total[$key_day]['pass_auto'])){
                    $new_data_total[$key_day]['pass_auto'] += $item['pass_auto'];
                }else{
                    $new_data_total[$key_day]['pass_auto'] = $item['pass_auto'];
                }

                if(isset($new_data_total[$key_day]['pass_man'])){
                    $new_data_total[$key_day]['pass_man'] += $item['pass_man'];
                }else{
                    $new_data_total[$key_day]['pass_man'] = $item['pass_man'];
                }

                if(isset($new_data_total[$key_day]['pass_total'])){
                    $new_data_total[$key_day]['pass_total'] += $item['pass_total'];
                }else{
                    $new_data_total[$key_day]['pass_total'] = $item['pass_total'];
                }
            }
            foreach($new_data_money_tmp as $item){//金额数据
                if(isset($new_data_money[$key_day]['apply_auto'])){
                    $new_data_money[$key_day]['apply_auto'] += $item['apply_auto'];
                }else{
                    $new_data_money[$key_day]['apply_auto'] = $item['apply_auto'];
                }

                if(isset($new_data_money[$key_day]['apply_man'])){
                    $new_data_money[$key_day]['apply_man'] += $item['apply_man'];
                }else{
                    $new_data_money[$key_day]['apply_man'] = $item['apply_man'];
                }

                if(isset($new_data_money[$key_day]['apply_total'])){
                    $new_data_money[$key_day]['apply_total'] += $item['apply_total'];
                }else{
                    $new_data_money[$key_day]['apply_total'] = $item['apply_total'];
                }

                if(isset($new_data_money[$key_day]['pass_auto'])){
                    $new_data_money[$key_day]['pass_auto'] += $item['pass_auto'];
                }else{
                    $new_data_money[$key_day]['pass_auto'] = $item['pass_auto'];
                }

                if(isset($new_data_money[$key_day]['pass_man'])){
                    $new_data_money[$key_day]['pass_man'] += $item['pass_man'];
                }else{
                    $new_data_money[$key_day]['pass_man'] = $item['pass_man'];
                }

                if(isset($new_data_money[$key_day]['pass_total'])){
                    $new_data_money[$key_day]['pass_total'] += $item['pass_total'];
                }else{
                    $new_data_money[$key_day]['pass_total'] = $item['pass_total'];
                }

                if(isset($new_data_money[$key_day]['profit'])){
                    $new_data_money[$key_day]['profit'] += $item['profit'];
                }else{
                    $new_data_money[$key_day]['profit'] = $item['profit'];
                }

                if(isset($new_data_money[$key_day]['pass_total'])){
                    $new_data_money[$key_day]['pass_total'] += $item['pass_total'];
                }else{
                    $new_data_money[$key_day]['pass_total'] = $item['pass_total'];
                }
            }
        }
        $return_arr = ['total'=>$new_data_total,'money'=>$new_data_money];
        return $return_arr;
    }
}
?>