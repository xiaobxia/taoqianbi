<?php

namespace console\controllers;

use common\models\DailyRegisterAndLoanData;
use common\services\DailyDateService;
use yii;
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
use common\models\stats\LoanStatistics;
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
use common\models\stats\OverdueDataDistribution;


class DailyController extends BaseController {
    /**
     * 资方id列表
     * @var array
     */
    private $_fund_id_list = [
        LoanFund::ID_KOUDAI,//口袋资方ID
        LoanFund::ID_WZDAI, //温州贷资方
    ];

    /**
     * 处理提额错误的脚本
     */
    public function actionHandleRepaymentAmount(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $db = \Yii::$app->db_kdkj_rd_new;
        $condition = " amount>300000 and card_type<=1 ";
        $limit = 300;
        $order_special = UserCreditTotal::find()->where($condition)->orderBy("id desc")->limit($limit)->all($db);

        foreach ($order_special as $item) {
            $user_condition = sprintf(" status=4 AND user_id=%d",$item->user_id);
            $user_repayment = UserLoanOrderRepayment::find()->where($user_condition)->orderBy("id desc")->limit(1)->one($db);
            if ($user_repayment) {
                $item->amount = $user_repayment->principal;
                if($item->save()){
                    echo sprintf("user_id:%s,amount:%s",$item->user_id,$user_repayment->principal).PHP_EOL;
                }
            }else{
                echo sprintf("__|||user_id:%s,amount:%s",$item->user_id,$item->amount).PHP_EOL;
            }
        }

        echo "SUCCESS_".PHP_EOL;
    }

    /**
     * 处理临时图片
     */
    public function actionHandleCreditOther(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $db = \Yii::$app->db_kdkj_rd;
        $condition = "status=15 and sub_order_type<>10 and order_time >= 1483804800 and order_time <= 1484323200";
        $order_special = UserLoanOrder::find()->where($condition)->limit(300)->orderBy("id desc")->all($db);

        foreach ($order_special as $item) {
            $item->status = UserLoanOrder::STATUS_CHECK;
            $item->save();
            RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX, $item->id]);
            echo sprintf("order_id:%s,user_id:%s",$item->id,$item->user_id).PHP_EOL;
        }
        echo "SUCCESS_".PHP_EOL;
    }

    /**
     * @name 重写每日日报数据，统计tb_daily_data 表里面需要的数据，保存到数据库中
     */
    public function actionDailyData($type = 1) {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $end_date = date("Y-m-d");
        if ($type == 2) { //重新统计全部数据
            $start_date = '2017-03-28';
            $countDate = (strtotime($end_date)-strtotime($start_date)) / 86400;
            for($datei = 0; $datei < $countDate; $datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $date = date('Y-m-d',$dateNum);
                $_save = $this->sumData($date);
                if ($_save) {
                    $this->message("[{$date}] daily_data_save_success.");
                }
                else {
                    $this->error("[{$date}] daily_data_save_failed.");
                }
            }
        }
        else {
            $_save = $this->sumData($end_date);
            if ($_save) {
                $this->message("[{$end_date}] daily_data_save_success.");
            }
            else {
                $this->error("[{$end_date}] daily_data_save_failed.");
            }
        }

        return self::EXIT_CODE_NORMAL;
    }

    public function sumData($date) {
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $db = \Yii::$app->db_kdkj_rd;
        $db_assist = \Yii::$app->db_assist;
        $read_db = \Yii::$app->db_kdkj_rd_new;

        $UserLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserLoanOrderTableName = UserLoanOrder::tableName();
        $status_repay_complete =  UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;              //已经还款状态
        $is_no_overdue = UserLoanOrderRepayment::OVERDUE_NO;                         //未逾期状态
        $is_overdue = UserLoanOrderRepayment::OVERDUE_YES;                           //逾期状态
        $is_bad_debt = UserLoanOrderRepayment::STATUS_BAD_DEBT;                       //已坏账
        $start_time = strtotime($date);                                                  //开始时间
        $end_time = strtotime($date) + 86400;                                                  //截止时间
        $start_time_rel = strtotime($date) - 86400 * 15;                                                  //放款开始时间
        $end_time_rel = strtotime($date) - 86400 * 14;                                                  //放款截至时间

        $s1_day_start = 1;              //s1逾期时间  1-10
        $s1_day_end = 10;
        $s2_day_start = 11;              //s2逾期时间  11-30
        $s2_day_end = 30;
        $s3_day_start = 31;              //s3逾期时间  31-60
        $s3_day_end = 60;

        //查询用户总数
        $UserLoanTableName = LoanPerson::tableName();
        $user_total_sql = "SELECT COUNT(*) AS user_total 
                             FROM {$UserLoanTableName} 
                            WHERE id >= 0 and status =1
                              AND created_at <= {$end_time}" ;
        $user_total = $read_db->createCommand($user_total_sql)->queryScalar();

        //查询当前借款的总单数是（指所有的借款，包括还款等等)
        $loan_total_sql = "SELECT COUNT(id) AS loan_total 
                             FROM {$UserLoanOrderRepaymentTableName}
                            WHERE id >= 0 
                              AND loan_time <= {$end_time}";
        $loan_total = $read_db->createCommand($loan_total_sql)->queryScalar();

        //查询当前借款的总额(是指所有的借款，包括还款等等)
        $loan_total_money_sql = "SELECT SUM(principal) AS total_principal
                                   FROM {$UserLoanOrderRepaymentTableName}
                                  WHERE id >= 0 
                                    AND loan_time <= {$end_time}";
        $loan_total_money = $read_db->createCommand($loan_total_money_sql)->queryScalar();

        //待收总单数(应该包括逾期数据，生息单数)
        $live_total_sql = "SELECT COUNT(id) AS live_total 
                             FROM {$UserLoanOrderRepaymentTableName}
                            WHERE id >= 0
                              AND status != {$status_repay_complete}
                              AND loan_time <= {$end_time}";
        $live_total = $read_db->createCommand($live_total_sql)->queryScalar();

        //待收总金额 (应该包括逾期数据，包括逾期的金额)
        $live_total_money_sql = "SELECT SUM(principal) AS live_total_money
                                   FROM {$UserLoanOrderRepaymentTableName}
                                  WHERE id >= 0 
                                    AND status != {$status_repay_complete}
                                    AND loan_time <= {$end_time}";
        $live_total_money = $read_db->createCommand($live_total_money_sql)->queryScalar();

        //当前已经还款总笔数
        $finish_total_sql = "SELECT COUNT(id) AS finish_total 
                               FROM {$UserLoanOrderRepaymentTableName}
                              WHERE id >= 0 
                                AND status = {$status_repay_complete} 
                                AND true_repayment_time<={$end_time}";
        $finish_total = $read_db->createCommand($finish_total_sql)->queryScalar();

//        echo '当前已经还款总笔数'.$finish_total_sql."\n";

        //当前已经还款总额
        $finish_total_money_sql = "SELECT SUM(principal) AS finish_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status={$status_repay_complete}  AND true_repayment_time<={$end_time}";
        $finish_total_money = $read_db->createCommand($finish_total_money_sql)->queryScalar();

        //s1级逾期总单数  (未还款逾期单数)
        $overdue_s1_total_sql = "SELECT COUNT(id) AS overdue_s1_total FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}  AND is_overdue={$is_overdue} AND overdue_day>={$s1_day_start} AND overdue_day<={$s1_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s1_total = $read_db->createCommand($overdue_s1_total_sql)->queryScalar();

        //s1级逾期总金额 (应该包括已经还款逾期金额+未还款逾期金额)
        $overdue_s1_total_money_sql = "SELECT SUM(principal) AS overdue_s1_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}  AND is_overdue={$is_overdue} AND overdue_day>={$s1_day_start} AND overdue_day<={$s1_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s1_total_money = $read_db->createCommand($overdue_s1_total_money_sql)->queryScalar();

        //s2级逾期总单数
        $overdue_s2_total_sql = "SELECT COUNT(id) AS overdue_s2_total FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0  AND status!={$status_repay_complete} AND is_overdue={$is_overdue} AND overdue_day>={$s2_day_start} AND overdue_day<={$s2_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s2_total = $read_db->createCommand($overdue_s2_total_sql)->queryScalar();

        //s2级逾期总金额
        $overdue_s2_total_money_sql = "SELECT SUM(principal) AS overdue_s2_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}   AND is_overdue={$is_overdue} AND overdue_day>={$s2_day_start} AND overdue_day<={$s2_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s2_total_money = $read_db->createCommand($overdue_s2_total_money_sql)->queryScalar();

        //s3级逾期总单数
        $overdue_s3_total_sql = "SELECT COUNT(id) AS overdue_s3_total FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0  AND status!={$status_repay_complete} AND is_overdue={$is_overdue} AND overdue_day>={$s3_day_start} AND overdue_day<={$s3_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s3_total = $read_db->createCommand($overdue_s3_total_sql)->queryScalar();

        //s3级逾期总金额
        $overdue_s3_total_money_sql = "SELECT SUM(principal) AS overdue_s3_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}  AND is_overdue={$is_overdue} AND overdue_day>={$s3_day_start} AND overdue_day<={$s3_day_end}  AND plan_fee_time<={$end_time}";
        $overdue_s3_total_money = $read_db->createCommand($overdue_s3_total_money_sql)->queryScalar();

        //已经到还款日总单数(就是总逾期总单数)
        $plan_repayment_total_sql = "SELECT  COUNT(id) AS plan_repayment_total FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete} AND is_overdue={$is_overdue}  AND plan_fee_time<={$end_time}";
        $plan_repayment_total = $read_db->createCommand($plan_repayment_total_sql)->queryScalar();

        //已经到还款日的总金额(就是总逾期金额)
        $plan_repayment_total_money_sql = "SELECT SUM(principal) AS plan_repayment_total_principal FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}  AND is_overdue={$is_overdue}  AND plan_fee_time<={$end_time}";
        $plan_repayment_total_money = $read_db->createCommand($plan_repayment_total_money_sql)->queryScalar();

        //m1逾期总单数 金额
        $m1_group = 3;
        $repayment_ids = LoanCollectionOrder::find()->where(['current_overdue_group' => $m1_group])->column();
        // $overdue_m1_total_sql = "SELECT user_loan_order_repayment_id FROM tb_loan_collection_order WHERE current_overdue_group = {$m1_group}";
        // $repayment_ids = $db_assist->createCommand($overdue_m1_total_sql)->queryAll();
        $repayment_ids = implode(',', $repayment_ids);
        if ($repayment_ids){
            $overdue_m1_total_sql = "SELECT SUM(principal) AS overdue_m1_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}   AND is_overdue={$is_overdue} AND plan_fee_time<={$end_time} AND id in ({$repayment_ids})";
            $overdue_m1_total_money = $read_db->createCommand($overdue_m1_total_sql)->queryScalar();
            $overdue_m1_total_sql = "SELECT COUNT(id) AS overdue_s2_total FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0  AND status!={$status_repay_complete} AND is_overdue={$is_overdue} AND plan_fee_time<={$end_time} AND id in ({$repayment_ids})";
            $overdue_m1_total = $read_db->createCommand($overdue_m1_total_sql)->queryScalar();
        }else{
            $overdue_m1_total_money = 0;
            $overdue_m1_total = 0;
        }


        //今日入催金额  当日逾期订单数 / 当日到期订单数
        $overdue_now_total_money_sql = "SELECT SUM(principal) AS overdue_now_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND status!={$status_repay_complete}   AND is_overdue={$is_overdue} AND overdue_day=1 AND plan_fee_time<={$end_time}";
        $loan_collection_total = $read_db->createCommand($overdue_now_total_money_sql)->queryScalar();
        //昨日应还本金
        $overdueyes_total_money_sql = "SELECT SUM(principal) AS overdue_yes_total_money FROM  {$UserLoanOrderRepaymentTableName} WHERE id>=0 AND created_at>={$start_time_rel} AND created_at<={$end_time_rel}";
        $loan_collection_principal = $read_db->createCommand($overdueyes_total_money_sql)->queryScalar();

        //今日逾期订单数

        //今日到期订单数

        //今日老用户通过订单数 trail_time审核时间
        $sql = "select count(id)as pas_old_check_num from {$UserLoanOrderRepaymentTableName} where user_id in(SELECT user_id FROM {$UserLoanOrderTableName} where trail_time>={$start_time} and trail_time<{$end_time} and user_id in(SELECT id from tb_loan_person where  customer_type = 1)) and created_at>={$start_time} and created_at<{$end_time} ";
        $pas_old_check_num = $read_db->createCommand($sql)->queryScalar();
        //今日新用户通过订单数
        $sql = "select count(id)as pas_new_check_num from {$UserLoanOrderRepaymentTableName} where user_id in(SELECT user_id FROM {$UserLoanOrderTableName} where user_id in(SELECT id from tb_loan_person where  customer_type = 0) and trail_time>={$start_time} and trail_time<{$end_time}) and created_at>={$start_time} and created_at<{$end_time}";
        $pas_new_check_num = $read_db->createCommand($sql)->queryScalar();

        $daily_data = DailyData::findOne(['date_time' => $date]);
        if (empty($daily_data)) {
            $daily_data = new DailyData();
            $daily_data->date_time = $date;
            $daily_data->created_at = time();
            $daily_data->user_total = $user_total;
            $daily_data->loan_total = $loan_total;
            $daily_data->loan_total_money = $loan_total_money;
            $daily_data->live_total = $live_total;
            $daily_data->live_total_money = $live_total_money;
            $daily_data->finish_total = $finish_total;
            $daily_data->finish_total_money = $finish_total_money;
            $daily_data->overdue_s1_total = $overdue_s1_total;
            $daily_data->overdue_s2_total = $overdue_s2_total;
            $daily_data->overdue_s3_total = $overdue_s3_total;
            $daily_data->overdue_s1_total_money = $overdue_s1_total_money;
            $daily_data->overdue_s2_total_money = $overdue_s2_total_money;
            $daily_data->overdue_s3_total_money = $overdue_s3_total_money;
            $daily_data->plan_repayment_total = $plan_repayment_total;
            $daily_data->plan_repayment_total_money = $plan_repayment_total_money;
            $daily_data->overdue_m1_total = $overdue_m1_total;
            $daily_data->overdue_m1_total_money = $overdue_m1_total_money;
            $daily_data->loan_collection_total = $loan_collection_total;
            $daily_data->loan_collection_principal = $loan_collection_principal;
        }else{
            $daily_data->user_total = $user_total;
            $daily_data->loan_total = $loan_total;
            $daily_data->loan_total_money = $loan_total_money;
            $daily_data->live_total = $live_total;
            $daily_data->live_total_money = $live_total_money;
            $daily_data->finish_total = $finish_total;
            $daily_data->finish_total_money = $finish_total_money;
            $daily_data->overdue_s1_total = $overdue_s1_total;
            $daily_data->overdue_s2_total = $overdue_s2_total;
            $daily_data->overdue_s3_total = $overdue_s3_total;
            $daily_data->overdue_s1_total_money = $overdue_s1_total_money;
            $daily_data->overdue_s2_total_money = $overdue_s2_total_money;
            $daily_data->overdue_s3_total_money = $overdue_s3_total_money;
            $daily_data->plan_repayment_total = $plan_repayment_total;
            $daily_data->plan_repayment_total_money = $plan_repayment_total_money;
            $daily_data->overdue_m1_total = $overdue_m1_total;
            $daily_data->overdue_m1_total_money = $overdue_m1_total_money;
            $daily_data->loan_collection_total = $loan_collection_total;
            $daily_data->loan_collection_principal = $loan_collection_principal;
            // $daily_data->pas_old_check_num = $pas_old_check_num;
            // $daily_data->pas_new_check_num = $pas_new_check_num;

        }
        return $daily_data->save();
    }


    /**
     * @name 分资方财务数据
     * @param $field_name 需要处理的字段
     * @param $repay_total_num 需要处理的数组
     * @param $type 时间类型
     * @param $data 数组
     */
    private function getFundDetails($field_name,$repay_total_num,&$data){
        foreach($repay_total_num as $value){
            $fund_id=$value['fund_id']??1000;
            foreach($field_name as $_k => $_v) {
                if(!isset($data[$fund_id][$_v])) {
                    $data[$fund_id][$_v] = 0;
                    $data[$fund_id][$_v] = $value[$_v];
                }else{
                    $data[$fund_id][$_v] += $value[$_v];
                }
            }
            $fund_id=0;
            foreach($field_name as $_k => $_v) {
                if(!isset($data[$fund_id][$_v])) {
                    $data[$fund_id][$_v] = 0;
                    $data[$fund_id][$_v] = $value[$_v];
                }else{
                    $data[$fund_id][$_v] += $value[$_v];
                }
            }
        }
    }
    /**
     * @name 逾期数据分布
     * @user chenlu
     * @date 2017-10
     * @param $countDate 默认为3 跑近三个月数据 $start_time 单个起始时间
     */
    public function actionOverdueList($countDate=2,$start_time='') {
        Util::cliLimitChange(2048);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        if(!empty($start_time)){
            $this->getOverdueData(strtotime($start_time), strtotime("+1 month",strtotime($start_time)));
        }else{
            $countDate = $countDate;
            for($datei = 0;$datei<=$countDate;$datei++){
                $dateNum = date('Y-m-01',strtotime("-$datei month"));
                $end_time =date('Y-m-01',strtotime("+1 month",strtotime($dateNum)));
                $this->getOverdueData(strtotime($dateNum), strtotime($end_time));
            }
        }
    }
    private function getOverdueData($start_time, $end_time){
        $date=date('Y-m-d',$start_time);
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $db_stats= \Yii::$app->db_stats;
        $data=[];
        $is_success = FinancialLoanRecord::UMP_PAY_SUCCESS;                //打款成功
        $u_status = UserCreditMoneyLog::STATUS_SUCCESS;                    //log还款成功
        $o_status = UserLoanOrder::STATUS_REPAY_COMPLETE;                   //order还款成功
        //逾期数据（月）
        //累计在S1期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=1, $day2=10,$field_name='overdue_s1_money');
        $field_name=array("overdue_s1_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        //累计在S2期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=11,$day2=20,$field_name='overdue_s2_money');
        $field_name=array("overdue_s2_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        //累计在S3期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=21,$day2=30,$field_name='overdue_s3_money');
        $field_name=array("overdue_s3_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        //累计在M1期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=31,$day2=60,$field_name='overdue_m1_money');
        $field_name=array("overdue_m1_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        //累计在M2期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=61,$day2=90,$field_name='overdue_m2_money');
        $field_name=array("overdue_m2_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        //累计在M3期内的逾期数据
        $overdue_data=$this->getOverdueRes($start_time,$end_time,$day1=91,$day2='',$field_name='overdue_m3_money');
        $field_name=array("overdue_m3_money");
        $this->getFundDetails($field_name,$overdue_data,$data);
        echo $date."loan_money_start";
        //借款金额
        $finan_sql ="select
                          if(sum(f.money),sum(f.money),0) as loan_money,
                          o.fund_id
                        from tb_financial_loan_record as f
                        left join tb_user_loan_order as o on f.business_id=o.id
                        where f.id > 0
                        and f.status = {$is_success}
                        and f.success_time >= {$start_time}
                        and f.success_time < {$end_time}
                        GROUP by o.fund_id";
        $finan_data = $read_db->createCommand($finan_sql)->queryAll();
        $field_name=array('loan_money');
        $this->getFundDetails($field_name,$finan_data,$data);

        //续期金额
        $rollover_money = 0;
        $data[0]['rollover_money']=$rollover_money;
        //当天流水
        $sql="select
              o.order_id as order_id,
              o.user_id,
              sum(o.operator_money) as operator_money,
              lo.fund_id
              from tb_user_credit_money_log as o
              LEFT JOIN tb_user_loan_order as lo on lo.id=o.order_id
              LEFT JOIN tb_financial_loan_record as f on f.business_id=lo.id
              where o.id > 0
              and o.status = {$u_status}
              and f.status = {$is_success}
              and o.success_repayment_time >0
              and f.success_time >= {$start_time}
              and f.success_time < {$end_time}
              GROUP by o.order_id";
        $late_fee_data=$read_db->createCommand($sql)->queryAll();
        $this->getLatefee($late_fee_data,$u_status,$end_time,$read_db,$datas);

        $all=[];
        $field_name=array('sub_true_total_money','principal','_late_fee','coupon_money');
        $this->getFundDetails($field_name,$datas,$all);
        foreach($all as $fund_id=>$value){
            foreach($value as $item){
                if(isset($all[$fund_id])){
                    $all[$fund_id]['sub_true_total_money']+=$item['sub_true_total_money']??0;
                    $all[$fund_id]['principal']+=$item['principal']??0;
                    $all[$fund_id]['_late_fee']+=$item['_late_fee']??0;
                    $all[$fund_id]['coupon_money']+=$item['coupon_money']??0;
                }else{
                    $all[$fund_id]['sub_true_total_money']=$item['sub_true_total_money']??0;
                    $all[$fund_id]['principal']=$item['principal']??0;
                    $all[$fund_id]['_late_fee']=$item['_late_fee']??0;
                    $all[$fund_id]['coupon_money']=$item['coupon_money']??0;
                }
                $fund_id=0;
                if(isset($all[$fund_id])){
                    $all[$fund_id]['sub_true_total_money']+=$item['sub_true_total_money']??0;
                    $all[$fund_id]['principal']+=$item['principal']??0;
                    $all[$fund_id]['_late_fee']+=$item['_late_fee']??0;
                    $all[$fund_id]['coupon_money']+=$item['coupon_money']??0;
                }else{
                    $all[$fund_id]['sub_true_total_money']=$item['sub_true_total_money']??0;
                    $all[$fund_id]['principal']=$item['principal']??0;
                    $all[$fund_id]['_late_fee']=$item['_late_fee']??0;
                    $all[$fund_id]['coupon_money']=$item['coupon_money']??0;
                }
            }
        }
        foreach($all as $fund_id=>$item){
            $data[$fund_id]['true_total_money']=$item['sub_true_total_money'];
            $data[$fund_id]['principal']=$item['principal'];
            $data[$fund_id]['repay_late_fee']=$item['_late_fee'];
            $data[$fund_id]['coupon_money']=$item['coupon_money'];
        }

        echo $date."datasave_start";
        $field_name = array('overdue_s1_money','overdue_s2_money','overdue_s3_money','overdue_m1_money','overdue_m2_money','overdue_m3_money', 'rollover_money','repay_late_fee','coupon_money','true_total_money');
        foreach($data as $fund_id =>$value){
            foreach( $field_name as $_k => $_v) {
                if(!isset($value[$_v])) {
                    $value[$_v] = 0;
                }
            }
            $Overdue_List =  OverdueDataDistribution::find()->where(['date'=>$date,'fund_id'=>$fund_id])->one($db_stats);
            if (!empty($Overdue_List)) {
                $Overdue_List->updated_at = time();
            }else{
                $Overdue_List = new OverdueDataDistribution();
                $Overdue_List->date = $date;
                $Overdue_List->created_at = time();
                $Overdue_List->fund_id = $fund_id;
            }
            //逾期数据
            $Overdue_List->overdue_s1_money=$value['overdue_s1_money'];
            $Overdue_List->overdue_s2_money=$value['overdue_s2_money'];
            $Overdue_List->overdue_s3_money=$value['overdue_s3_money'];
            $Overdue_List->overdue_m1_money=$value['overdue_m1_money'];
            $Overdue_List->overdue_m2_money=$value['overdue_m2_money'];
            $Overdue_List->overdue_m3_money=$value['overdue_m3_money'];
            $Overdue_List->loan_money=$value['loan_money'];
            $Overdue_List->rollover_money=$value['rollover_money'];
            $Overdue_List->repay_money=$value['true_total_money'];
            $Overdue_List->overdue_money=$value['overdue_s1_money']+$value['overdue_s2_money']+$value['overdue_s3_money']+$value['overdue_m1_money']+$value['overdue_m2_money']+$value['overdue_m3_money'];
            $Overdue_List->repay_late_fee=$value['repay_late_fee'];
            $Overdue_List->coupon_money=$value['coupon_money'];

            echo $date."\n";
            if(!$Overdue_List->save()){
                echo '保存错误';
                $this->error('逾期数据保存失败');
            };
        }
    }
    //处理滞纳金逻辑
    private function getLatefee($late_fee_data,$u_status,$end_time,$read_db,&$datas){
        foreach($late_fee_data as $value){
            $order_id=$value['order_id'];
            //该订单资方
            $fund_id=$value['fund_id']??1000;
            //echo "订单：{$order_id}\t渠道：$fund_id".PHP_EOL;
            //当天的订单流水金额
            $datas[$order_id]['fund_id']=$fund_id;
            $datas[$order_id]['sub_true_total_money']=$value['operator_money'] ?? 0;
            //该订单总流水截止今天
            $sql="select
                  sum(operator_money) AS operator_money
                  from tb_user_credit_money_log
                  where order_id = '{$order_id}'
                  and operator_money > 0
                  and status ={$u_status}";
            $op_data=$read_db->createCommand($sql)->queryOne();
            $datas[$order_id]['sub_operator_money']=$op_data['operator_money'] ?? 0;

            //该订单本金
            $sql="select
                    principal,
                    coupon_money,
                    status,
                    true_repayment_time
                    from tb_user_loan_order_repayment
                    where order_id = {$order_id}";
            $pr_data=$read_db->createCommand($sql)->queryOne();
            $datas[$order_id]['sub_principal']=$pr_data['principal'] ?? 0;
            $datas[$order_id]['diff_money'] = $datas[$order_id]['sub_operator_money'] - $datas[$order_id]['sub_principal'];
            $datas[$order_id]['_late_fee'] = max(0,min($datas[$order_id]['sub_true_total_money'], $datas[$order_id]['diff_money']));

            //计算本金
            if($datas[$order_id]['sub_operator_money'] <= $datas[$order_id]['sub_principal']){//历史还款流水总额小于等于本金
                $datas[$order_id]['principal'] = $datas[$order_id]['sub_true_total_money'];
            }else{//历史还款流水总额大于本金
                $before_today_money = $datas[$order_id]['sub_operator_money'] - $datas[$order_id]['sub_true_total_money'];//今日之前已还金额
                $diff_money2 = $datas[$order_id]['sub_principal'] - $before_today_money;
                $datas[$order_id]['principal'] = max(0, min($diff_money2, $datas[$order_id]['sub_true_total_money']));
            }
            //优惠券金额
            $datas[$order_id]['coupon_money']=$pr_data['coupon_money']??0;
        }
    }
    //处理逾期sql
    private function getOverdueRes($start_time,$end_time,$day1,$day2,$field){
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $status_repay_complete =  UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;     //已经还款状态
        $is_overdue = UserLoanOrderRepayment::OVERDUE_YES;                           //逾期状态
        $is_success = FinancialLoanRecord::UMP_PAY_SUCCESS;                         //打款成功

        $select_sql = "sum(money) as {$field},
                        o.fund_id";
        $from = "tb_financial_loan_record as f";
        $left_join = "tb_user_loan_order_repayment as r on r.order_id=f.business_id";
        $left_join.= " left join tb_user_loan_order as o on r.order_id=o.id";
        $where_sql="f.id > 0
                and f.status = {$is_success}
                and f.success_time >= {$start_time}
                and f.success_time < {$end_time}
                and overdue_day >= {$day1}";
        if(empty($day2)){
            $where_sql .=" and r.is_overdue = {$is_overdue}
                         and r.status != {$status_repay_complete}";
        }else{
            $where_sql .= " and  overdue_day <= {$day2}
                        and r.is_overdue = {$is_overdue}
                        and r.status != {$status_repay_complete}";
        }
        $group_by = "o.fund_id";
        $sql = "SELECT ".$select_sql;
        $sql .= " FROM ". $from;
        $sql .= " LEFT JOIN ". $left_join;
        $sql .= " WHERE " . $where_sql;
        $sql .= " GROUP BY " . $group_by;

        $overdue_num_all = $read_db->createCommand($sql)->queryAll();
        return $overdue_num_all;
    }

    /**
     * @name 每日还款率统计
     * @param int $type 1为每天 2为每月
     * @author chenlu
     * @date 2017-08
     */
    public function actionRepayRatesList($type=1) {
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        //如果$type = 2 循环跑每月的数据
        if ($type == 2) {
            $end_date = date("Y-m-d");
            $start_date = '2017-03-28';
            $countDate = (strtotime($end_date) - strtotime($start_date)) / 86400;
            for ($datei = 0; $datei < $countDate; $datei++) {
                $dateNum = strtotime($end_date) - $datei * 86400;
                $end_time = $dateNum + 86400;
                $this->getRatesListData($dateNum, $end_time, $dtype = 0);
            }
        } elseif ($type == 3) {
            $countDate = 3;
            for($datei = 0;$datei<=$countDate;$datei++){
                $dateNum = date('Y-m-01',strtotime("-$datei month"));
                $end_time = date('Y-m-01',strtotime("+1 month",strtotime($dateNum)));
                $this->getRatesListData(strtotime($dateNum), strtotime($end_time), $dtype = 0);
            }
        } else {
            $start_time = strtotime("today"); //今天零点
            $end_time = $start_time + 86400;
            $now_time = time();
            $_hour = date('H', $now_time);//当前的小时数
            //如果当前时间为24点，则计算前一天所有的注册量等数据,显示日期为前一天的24时
            if ($_hour == 0) {
                $end_time = $start_time;
                $start_time = $end_time - 86400;
            }
            $this->getRatesListData($start_time, $end_time, $dtype = 0);
        }
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $type
     */
    private function getRatesListData($start_time, $end_time, $type){
        echo date('Y-m-d',$start_time)."\n";
        $s1_day_start = 1;              //s1逾期时间  1-10
        $s1_day_end = 10;
        $s2_day_start = 11;              //s2逾期时间  11-20
        $s2_day_end = 20;
        $s3_day_start = 21;              //s3逾期时间  21-30
        $s3_day_end = 30;
        $s4_day_start = 31;              //s4逾期时间  31-60
        $s4_day_end = 60;
        $s5_day_start = 61;              //s5逾期时间  61-90
        $s5_day_end = 90;
        $s6_day_start =91;               //s6逾期时间  >90

        $status_repay_complete = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;      //已经还款状态
        $is_no_overdue = UserLoanOrderRepayment::OVERDUE_NO;                         //未逾期状态
        $is_overdue = UserLoanOrderRepayment::OVERDUE_YES;                           //逾期状态
        $is_success = FinancialLoanRecord::UMP_PAY_SUCCESS;                         //打款成功
        $renew_reject =  LoanCollectionOrder::RENEW_REJECT;                           //建议拒绝
        $u_status = UserCreditMoneyLog::STATUS_SUCCESS;//还款成功
        $date = date("Y-m-d",$start_time);
        echo "{$date}\n";
        $two_time = 3600*24*2;                                                        //计算0-2天还款统计
        $data=$datas=[];
        $db = \Yii::$app->db_kdkj_rd;
        $read_db = \Yii::$app->db_kdkj_rd_new;
        if($type==0){
            //当前已经还款总笔数、金额
            $repay_total_num_sql = "SELECT
                                        COUNT(r.id) AS repay_total_num,
                                        SUM(r.total_money) AS repay_total_money,
                                        o.fund_id
                                        FROM tb_user_loan_order_repayment as r
                                        left join tb_user_loan_order as o on r.order_id=o.id
                                        WHERE r.id >= 0
                                        AND r.status = {$status_repay_complete}
                                        AND r.true_repayment_time <= {$end_time}
                                        GROUP BY o.fund_id";
            $repay_total_num = $read_db->createCommand($repay_total_num_sql)->queryAll();
            $field_name=array('repay_total_num','repay_total_money');
            $this->getFundDetails($field_name,$repay_total_num,$data);

            //提前0-2天的还款单数,和还款的金额
            $repay_twoday_num_sql = "SELECT
                                        SUM(IF(true_repayment_time-r.created_at<{$two_time},1,0)) AS repay_twoday_num,
                                        SUM(IF(true_repayment_time-r.created_at<{$two_time},true_total_money,0)) AS repay_twoday_money,
                                        o.fund_id
                                        FROM tb_user_loan_order_repayment as r
                                        left join tb_user_loan_order as o on r.order_id=o.id
                                        WHERE r.id >= 0
                                        AND r.status = {$status_repay_complete}
                                        AND r.is_overdue = {$is_no_overdue}
                                        AND r.true_repayment_time <= {$end_time}
                                        GROUP BY o.fund_id";
            $repay_twoday_num_all = $read_db->createCommand($repay_twoday_num_sql)->queryAll();
            $field_name=array('repay_twoday_num','repay_twoday_money');
            $this->getFundDetails($field_name,$repay_twoday_num_all,$data);

            //提前还款但是不在0-2天的还款单数,和还款的金额
            $repay_someday_num_sql = "SELECT
                                          SUM(IF(r.plan_fee_time-true_repayment_time>86400 && r.true_repayment_time-r.created_at>{$two_time},1,0)) AS repay_someday_num,
                                          SUM(IF(r.plan_fee_time-true_repayment_time>86400 && r.true_repayment_time-r.created_at>{$two_time},true_total_money,0)) AS repay_someday_money,
                                          o.fund_id
                                          FROM tb_user_loan_order_repayment as r
                                          left join tb_user_loan_order as o on r.order_id=o.id
                                          WHERE r.id >=0
                                          AND r.status = {$status_repay_complete}
                                          AND r.is_overdue = {$is_no_overdue}
                                          AND r.true_repayment_time <= {$end_time}
                                          and o.fund_id = 2";
            $repay_someday_num_all = $read_db->createCommand($repay_someday_num_sql)->queryAll();
            $field_name=array('repay_someday_num','repay_someday_money');
            $this->getFundDetails($field_name,$repay_someday_num_all,$data);

            //正常还款的单数，和还款的金额
            $repay_normal_num_sql = "SELECT
                                        SUM(IF(r.true_repayment_time-r.created_at>{$two_time} && r.true_repayment_time>=plan_repayment_time ,1,0)) AS repay_normal_num,
                                        SUM(IF(r.true_repayment_time-r.created_at>{$two_time} && r.true_repayment_time>=plan_repayment_time,true_total_money,0)) AS repay_normal_money,
                                        o.fund_id
                                        FROM tb_user_loan_order_repayment as r
                                        left join tb_user_loan_order as o on r.order_id=o.id
                                        WHERE r.id >= 0
                                        AND r.status = {$status_repay_complete}
                                        AND r.is_overdue = {$is_no_overdue}
                                        AND r.true_repayment_time <= {$end_time}
                                        GROUP by o.fund_id";
            $repay_normal_num_all = $read_db->createCommand($repay_normal_num_sql)->queryAll();
            $field_name=array('repay_normal_num','repay_normal_money');
            $this->getFundDetails($field_name,$repay_normal_num_all,$data);

            //累计在S1期内的还款笔数
            $repay_s1_num_sql = "SELECT
                                  SUM(IF(r.overdue_day>={$s1_day_start} && r.overdue_day<={$s1_day_end},1,0)) AS repay_s1_num,
                                  SUM(IF(r.overdue_day>={$s1_day_start} && r.overdue_day<={$s1_day_end},true_total_money,0)) AS repay_s1_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND r.is_overdue = {$is_overdue}
                                  AND r.true_repayment_time <= {$end_time}
                                  GROUP BY o.fund_id";
            $repay_s1_num_all = $read_db->createCommand($repay_s1_num_sql)->queryAll();
            $field_name=array('repay_s1_num','repay_s1_money');
            $this->getFundDetails($field_name,$repay_s1_num_all,$data);

            //累计在S2期内的还款笔数
            $repay_s2_num_sql = "SELECT
                                  SUM(IF(r.overdue_day>={$s2_day_start} && r.overdue_day<={$s2_day_end},1,0)) AS repay_s2_num,
                                  SUM(IF(r.overdue_day>={$s2_day_start} && r.overdue_day<={$s2_day_end},true_total_money,0)) AS repay_s2_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND r.is_overdue = {$is_overdue}
                                  AND r.true_repayment_time <= {$end_time}
                                  GROUP by o.fund_id";
            $repay_s2_num_all = $read_db->createCommand($repay_s2_num_sql)->queryAll();
            $field_name=array('repay_s2_num','repay_s2_money');
            $this->getFundDetails($field_name,$repay_s2_num_all,$data);

            //累计在S3期内的还款笔数
            $repay_s3_num_sql = "SELECT
                                  SUM(IF(overdue_day>={$s3_day_start} && overdue_day<={$s3_day_end},1,0)) AS repay_s3_num,
                                  SUM(IF(overdue_day>={$s3_day_start} && overdue_day<={$s3_day_end},true_total_money,0)) AS repay_s3_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND is_overdue = {$is_overdue}
                                  AND true_repayment_time <= {$end_time}
                                  GROUP BY o.fund_id";
            $repay_s3_num_all = $read_db->createCommand($repay_s3_num_sql)->queryAll();
            //var_dump($repay_s3_num_all);var_dump($data[$fund_id][$_v]);
            $field_name=array('repay_s3_num','repay_s3_money');
            $this->getFundDetails($field_name,$repay_s3_num_all,$data);


            //累计在S4期内的还款笔数
            $repay_s4_num_sql = "SELECT
                                  SUM(IF(overdue_day>={$s4_day_start} && overdue_day<={$s4_day_end},1,0)) AS repay_s4_num,
                                  SUM(IF(overdue_day>={$s4_day_start} && overdue_day<={$s4_day_end},true_total_money,0)) AS repay_s4_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND is_overdue = {$is_overdue}
                                  AND true_repayment_time <= {$end_time}
                                  GROUP BY o.fund_id";
            $repay_s4_num_all = $read_db->createCommand($repay_s4_num_sql)->queryAll();
            $field_name=array('repay_s4_num','repay_s4_money');
            $this->getFundDetails($field_name,$repay_s4_num_all,$data);

            //累计在S5期内的还款笔数
            $repay_s5_num_sql = "SELECT
                                  SUM(IF(overdue_day>={$s5_day_start} && overdue_day<={$s5_day_end},1,0)) AS repay_s5_num,
                                  SUM(IF(overdue_day>={$s5_day_start} && overdue_day<={$s5_day_end},true_total_money,0)) AS repay_s5_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND is_overdue = {$is_overdue}
                                  AND true_repayment_time <= {$end_time}
                                  GROUP BY o.fund_id";
            $repay_s5_num_all = $read_db->createCommand($repay_s5_num_sql)->queryAll();
            $field_name=array('repay_s5_num','repay_s5_money');
            $this->getFundDetails($field_name,$repay_s5_num_all,$data);
            //累计在S6期内的还款笔数
            $repay_s6_num_sql = "SELECT
                                  SUM(IF(overdue_day>={$s6_day_start},1,0)) AS repay_s6_num,
                                  SUM(IF(overdue_day>={$s6_day_start},true_total_money,0)) AS repay_s6_money,
                                  o.fund_id
                                  FROM tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  WHERE r.id >= 0
                                  AND r.status = {$status_repay_complete}
                                  AND is_overdue = {$is_overdue}
                                  AND true_repayment_time <= {$end_time}
                                  GROUP BY o.fund_id";
            $repay_s6_num_all = $read_db->createCommand($repay_s6_num_sql)->queryAll();
            $field_name=array('repay_s6_num','repay_s6_money');
            $this->getFundDetails($field_name,$repay_s6_num_all,$data);
            //建议拒绝单数
            $db_assist = Yii::$app->db_assist;
            $repay_s3_num_sql = "SELECT user_loan_order_id
                                  FROM  tb_loan_collection_order
                                  WHERE id >= 0 
                                  AND created_at <= {$end_time} 
                                  AND next_loan_advice = {$renew_reject}";
            $repay_s3_num_all = $db_assist->createCommand($repay_s3_num_sql)->queryAll();
            if(!empty($repay_s3_num_all)){
                $order_ids= '';   //获取对应的借款order_id
                foreach($repay_s3_num_all as $v){
                    $order_ids .= $v['user_loan_order_id'] . ',';
                }
                $order_ids = substr($order_ids, 0, -1); //拼接用户order_id
                //从还款表中或取对应用户的order_id
                $repay_refuse_num_sql = "SELECT
                                          COUNT(r.id) AS repay_refuse_num,
                                          SUM(r.true_total_money) AS repay_refuse_money,
                                          o.fund_id
                                         FROM tb_user_loan_order_repayment as r
                                         left join tb_user_loan_order as o on r.order_id=o.id
                                         WHERE r.id > 0
                                         AND r.order_id in ($order_ids)
                                         AND r.status = {$status_repay_complete}
                                         GROUP by o.fund_id";
                $repay_s3_num_all = $read_db->createCommand($repay_refuse_num_sql)->queryAll();
                $field_name=array('repay_refuse_num','repay_refuse_money');
                $this->getFundDetails($field_name,$repay_s3_num_all,$data);
            }

            //贷款余额统计
            $loan_balance_sql="select
                                sum(principal) as loan_balance,
                                is_overdue,
                                o.fund_id
                                from tb_user_loan_order_repayment as r
                                left join tb_user_loan_order as o on r.order_id=o.id
                                where r.id > 0
                                and r.status !=4
                                and r.loan_time < {$end_time}
                                GROUP BY is_overdue,o.fund_id";
            $loan_balance = $read_db->createCommand($loan_balance_sql)->queryAll();
            $field_name=array('loan_balance','prematurity_balance','overdue_balance');
            foreach($loan_balance as $value){
                $fund_id=$value['fund_id'];
                if(isset($data[$fund_id])) {
                    foreach($field_name as $_k => $_v) {
                        if(!isset($data[$fund_id][$_v])) {
                            $data[$fund_id][$_v] = 0;
                        }
                    }
                    //贷款余额
                    $data[$fund_id]['loan_balance'] +=$value['loan_balance'];
                    if( $value['is_overdue'] == 0){
                        //未到期余额
                        $data[$fund_id]['prematurity_balance'] += $value['loan_balance'];
                    }else{
                        //逾期余额
                        $data[$fund_id]['overdue_balance'] += $value['loan_balance'];
                    }
                }else{
                    $data[$fund_id]['loan_balance'] = $value['loan_balance'];
                    $data[$fund_id]['prematurity_balance'] = $value['loan_balance'];
                    $data[$fund_id]['overdue_balance'] = $value['loan_balance'];
                }
                $fund_id=0;
                if(isset($data[$fund_id])) {
                    foreach($field_name as $_k => $_v) {
                        if(!isset($data[$fund_id][$_v])) {
                            $data[$fund_id][$_v] = 0;
                        }
                    }
                    //贷款余额
                    $data[$fund_id]['loan_balance'] +=$value['loan_balance'];
                    if( $value['is_overdue'] == 0){
                        //未到期余额
                        $data[$fund_id]['prematurity_balance'] += $value['loan_balance'];
                    }else{
                        //逾期余额
                        $data[$fund_id]['overdue_balance'] += $value['loan_balance'];
                    }
                }else{
                    $data[$fund_id]['loan_balance'] = $value['loan_balance'];
                    $data[$fund_id]['prematurity_balance'] = $value['loan_balance'];
                    $data[$fund_id]['overdue_balance'] = $value['loan_balance'];
                }
            }

            //续期余额
            $data[0]['rollover_balance'] =0;
            //M0逾期1-30
            $overdue_moneym0_sql="select
                                      sum(principal) as overdue_moneym0,
                                      o.fund_id
                                      from tb_user_loan_order_repayment as r
                                      left join tb_user_loan_order as o on r.order_id=o.id
                                      where r.id > 0
                                      and r.is_overdue = 1
                                      and r.loan_time < {$end_time}
                                      and r.overdue_day >= 1
                                      and r.overdue_day <= 30
                                      and r.status != 4
                                      GROUP by o.fund_id";
            $overdue_moneym0 = $read_db->createCommand($overdue_moneym0_sql)->queryAll();
            $field_name=array('overdue_moneym0');
            $this->getFundDetails($field_name,$overdue_moneym0,$data);

            //M1逾期31-60
            $overdue_moneym1_sql="select
                                    sum(principal) as overdue_moneym1,
                                    o.fund_id
                                    from tb_user_loan_order_repayment as r
                                    left join tb_user_loan_order as o on r.order_id=o.id
                                    where r.id > 0
                                    and r.is_overdue = 1
                                    and r.status != 4
                                    and r.overdue_day >= 31
                                    and r.overdue_day <= 60
                                    and r.loan_time < {$end_time}
                                    GROUP by o.fund_id";
            $overdue_moneym1 = $read_db->createCommand($overdue_moneym1_sql)->queryAll();
            $field_name=array('overdue_moneym1');
            $this->getFundDetails($field_name,$overdue_moneym1,$data);

            //M2逾期61-90
            $overdue_moneym2_sql="select
                                      sum(principal) as overdue_moneym2,
                                      o.fund_id
                                  from tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  where r.id > 0
                                  and r.is_overdue = 1
                                  and r.status != 4
                                  and r.overdue_day >= 61
                                  and r.overdue_day <= 90
                                  and r.loan_time < {$end_time}
                                  GROUP by o.fund_id";
            $overdue_moneym2 = $read_db->createCommand($overdue_moneym2_sql)->queryAll();
            $field_name=array('overdue_moneym2');
            $this->getFundDetails($field_name,$overdue_moneym2,$data);

            //M3逾期91及以上
            $overdue_moneym3_sql="select
                                      sum(principal) as overdue_moneym3,
                                      o.fund_id
                                  from tb_user_loan_order_repayment as r
                                  left join tb_user_loan_order as o on r.order_id=o.id
                                  where r.id > 0
                                  and r.is_overdue = 1
                                  and r.overdue_day >= 91
                                  and r.status != 4
                                  and r.loan_time<{$end_time}
                                  GROUP by o.fund_id";
            $overdue_moneym3 = $read_db->createCommand($overdue_moneym3_sql)->queryAll();
            $field_name=array('overdue_moneym3');
            $this->getFundDetails($field_name,$overdue_moneym3,$data);

            //应收滞纳金
            $late_fee_sql="select
                              sum(r.late_fee) as late_fee,
                              o.fund_id,
                              if(sum(true_total_money),sum(true_total_money),0) as true_total_money,
                              if(sum(principal),sum(principal),0) as principal
                              from tb_user_loan_order_repayment as r
                              left join tb_user_loan_order as o on r.order_id=o.id
                              where r.id > 0
                              and r.loan_time < {$end_time}
                              and is_overdue = 1 
                              and r.status = 4 
                              GROUP BY o.fund_id";
            $late_fee = $read_db->createCommand($late_fee_sql)->queryAll();
            $field_name=array('late_fee','net_late_fee','derate_late_fee');
            foreach($late_fee as $value){
                $fund_id=$value['fund_id'];
                if(isset($data[$fund_id])) {
                    foreach($field_name as $_k => $_v) {
                        if(!isset($data[$fund_id][$_v])) {
                            $data[$fund_id][$_v] = 0;
                        }
                    }
                    $data[$fund_id]['late_fee'] += $value['late_fee'];
                    $data[$fund_id]['net_late_fee'] += $value['true_total_money']-$value['principal'];
                    $data[$fund_id]['derate_late_fee'] += $data[$fund_id]['late_fee']-$data[$fund_id]['net_late_fee'];//减免滞纳金
                }else{
                    $data[$fund_id]['late_fee'] = $value['late_fee'];
                    $data[$fund_id]['net_late_fee'] = $value['true_total_money']-$value['principal'];
                    $data[$fund_id]['derate_late_fee'] = $data[$fund_id]['late_fee']-$data[$fund_id]['net_late_fee'];//减免滞纳金
                }
                $fund_id=0;
                if(isset($data[$fund_id])) {
                    foreach($field_name as $_k => $_v) {
                        if(!isset($data[$fund_id][$_v])) {
                            $data[$fund_id][$_v] = 0;
                        }
                    }
                    $data[$fund_id]['late_fee'] += $value['late_fee'];
                    $data[$fund_id]['net_late_fee'] += $value['true_total_money']-$value['principal'];
                    $data[$fund_id]['derate_late_fee'] += $data[$fund_id]['late_fee']-$data[$fund_id]['net_late_fee'];//减免滞纳金
                }else{
                    $data[$fund_id]['late_fee'] = $value['late_fee'];
                    $data[$fund_id]['net_late_fee'] = $value['true_total_money']-$value['principal'];
                    $data[$fund_id]['derate_late_fee'] = $data[$fund_id]['late_fee']-$data[$fund_id]['net_late_fee'];//减免滞纳金
                }
            }
        }


        $field_name = array('repay_total_num','repay_total_money','repay_twoday_num','repay_twoday_money','repay_someday_num','repay_someday_money','repay_normal_num','repay_normal_money',
            'repay_s1_num','repay_s1_money','repay_s2_num','repay_s2_money','repay_s3_num','repay_s3_money','repay_s4_num','repay_s4_money','repay_s5_num','repay_s5_money','repay_s6_num','repay_s6_money','repay_refuse_num','repay_refuse_money','loan_balance','prematurity_balance','prematurity_balance',
            'overdue_balance','rollover_balance','overdue_moneym0','overdue_moneym1','overdue_moneym2','overdue_moneym3','late_fee','net_late_fee','derate_late_fee','overdue_s1_money','overdue_s2_money',
            'overdue_s3_money','overdue_m1_money','overdue_m2_money','overdue_m3_money','loan_money','rollover_money','repay_money','overdue_money','repay_late_fee','coupon_money','principal','loan_s1_money',
            'loan_s2_money','loan_s3_money','loan_s4_money','loan_s5_money','loan_s6_money','true_total_money');

        foreach($data as $fund_id =>$value){
            foreach( $field_name as $_k => $_v) {
                if(!isset($value[$_v])) {
                    $value[$_v] = 0;
                }
            }
            $Repay_Rates_List =  \common\models\RepayRatesList::find()->where(['date'=>$date,'fund_id'=>$fund_id])->one($db);
            if (!empty($Repay_Rates_List)) {
                $Repay_Rates_List->updated_at = time();
            }else{
                $Repay_Rates_List = new RepayRateslist();
                $Repay_Rates_List->date = $date;
                $Repay_Rates_List->created_at = time();
                $Repay_Rates_List->fund_id = $fund_id;
            }
            $Repay_Rates_List->repay_total_num = $value['repay_total_num'];
            $Repay_Rates_List->repay_total_money=$value['repay_total_money'];
            $Repay_Rates_List->repay_twoday_num=$value['repay_twoday_num'];
            $Repay_Rates_List->repay_twoday_money=$value['repay_twoday_money'];
            $Repay_Rates_List->repay_someday_num=$value['repay_someday_num'];
            $Repay_Rates_List->repay_someday_money=$value['repay_someday_money'];
            $Repay_Rates_List->repay_normal_num=$value['repay_normal_num'];
            $Repay_Rates_List->repay_normal_money=$value['repay_normal_money'];
            $Repay_Rates_List->repay_s1_num=$value['repay_s1_num'];
            $Repay_Rates_List->repay_s1_money=$value['repay_s1_money'];
            $Repay_Rates_List->repay_s2_num=$value['repay_s2_num'];
            $Repay_Rates_List->repay_s2_money=$value['repay_s2_money'];
            $Repay_Rates_List->repay_s3_num=$value['repay_s3_num'];
            $Repay_Rates_List->repay_s3_money=$value['repay_s3_money'];
            $Repay_Rates_List->repay_s4_num=$value['repay_s4_num'];
            $Repay_Rates_List->repay_s4_money=$value['repay_s4_money'];
            $Repay_Rates_List->repay_s5_num=$value['repay_s5_num'];
            $Repay_Rates_List->repay_s5_money=$value['repay_s5_money'];
            $Repay_Rates_List->repay_s6_num=$value['repay_s6_num'];
            $Repay_Rates_List->repay_s6_money=$value['repay_s6_money'];
            $Repay_Rates_List->repay_refuse_num=isset($value['repay_refuse_num'])?$value['repay_refuse_num']:0;
            $Repay_Rates_List->repay_refuse_money=isset($value['repay_refuse_money'])?$value['repay_refuse_money']:0;
            //贷款余额
            $Repay_Rates_List->loan_balance = isset($value['loan_balance'])?$value['loan_balance']:0;
            $Repay_Rates_List->prematurity_balance = isset($value['prematurity_balance'])?$value['prematurity_balance']:0;
            $Repay_Rates_List->overdue_balance = isset($value['overdue_balance'])?$value['overdue_balance']:0;
            $Repay_Rates_List->rollover_balance = $value['rollover_balance'];
            $Repay_Rates_List->overdue_moneym0 = $value['overdue_moneym0'];
            $Repay_Rates_List->overdue_moneym1 = $value['overdue_moneym1'];
            $Repay_Rates_List->overdue_moneym2 = $value['overdue_moneym2'];
            $Repay_Rates_List->overdue_moneym3 = $value['overdue_moneym3'];
            $Repay_Rates_List->late_fee = $value['late_fee'];
            $Repay_Rates_List->net_late_fee = $value['net_late_fee'];
            $Repay_Rates_List->derate_late_fee = $value['derate_late_fee'];


            echo $date."\n";
            if(!$Repay_Rates_List->save()){
                echo '保存错误';
                $this->error('还款率数据保存失败');
            };
        }

    }


    /**
     * @name 每日借还款统计/actionLoanRepayList
     * @param int $type
     * @return int
     * @throws \Exception
     */
    public function actionLoanRepayList($type =1){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        //如果$type = 2 循环跑每月的数据
        if($type==2){
            $end_date =date("Y-m-d");
            $start_date = '2017-03-28';
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<=$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getTradeData($dateNum,$end_time);
            }
        }else{
            $start_time = strtotime("today"); //今天零点
            $end_time = $start_time+86400;
            $now_time = time();
            $_hour = date('H',$now_time);//当前的小时数
            $_day = date('d',$now_time);//当前的日期
            //如果当前时间为24点，则计算前一天所有的注册量等数据,显示日期为前一天的24时
            if( $_hour == 0 ){
                $end_time = $start_time;
                $start_time = $end_time-86400;
            }
            $this->getTradeData($start_time,$end_time);
        }
    }
    private function  getTradeData($start_time,$end_time){
        echo date('Y-m-d',$start_time)."\n";
        $data =[];
        $key = date('Y-m-d',$start_time);
        $db = \Yii::$app->db_kdkj_rd;
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $UserLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserLoanOrderTableName = UserLoanOrder::tableName();
        $LoanPersonTableName = LoanPerson::tableName();
        $UserCreditMoneyLogTableName = UserCreditMoneyLog::tableName();
        //所有用户申请人数，申请金额
        $all_apply_sql = "select
                          count(DISTINCT o.user_id) as countnum,
                          sum(o.money_amount) as money,
                          l.customer_type,
                          substr(FROM_UNIXTIME(o.created_at,'%Y-%m-%d %H:%i:%s'),12,2) as hours
                          from {$UserLoanOrderTableName} as o
                          JOIN {$LoanPersonTableName} as l on o.user_id = l.id
                          where o.created_at>={$start_time} and o.created_at<{$end_time}
                          GROUP BY l.customer_type,substr(FROM_UNIXTIME(o.created_at,'%Y-%m-%d %H:%i:%s'),12,2) 
                          order by substr(FROM_UNIXTIME(o.created_at,'%Y-%m-%d %H:%i:%s'),12,2) asc";
        $all_apply = $read_db->createCommand($all_apply_sql)->queryAll();
        foreach($all_apply as $value){
            $hour =sprintf ( "%02d",$value['hours']+1);
            if($value['customer_type'] ==0 ){
                $user_type = 1;
            }else{
                $user_type = 2;
            }
            if(isset($data[$key][$hour][$user_type])){
                $data[$key][$hour][$user_type]['apply_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['apply_num'] = $value['countnum'];
            }else{
                $data[$key][$hour][$user_type]['apply_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['apply_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] =0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['repayment_money'] = 0;
                $data[$key][$hour][$user_type]['repayment_num'] = 0;
                $data[$key][$hour][$user_type]['all_repay_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
            $user_type = 0;
            if(isset($data[$key][$hour][$user_type])){
                $data[$key][$hour][$user_type]['apply_money'] += $value['money'];
                $data[$key][$hour][$user_type]['apply_num'] += $value['countnum'];
            }else{
                $data[$key][$hour][$user_type]['apply_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['apply_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] =0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['repayment_money'] = 0;
                $data[$key][$hour][$user_type]['repayment_num'] = 0;
                $data[$key][$hour][$user_type]['all_repay_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
        }

        //所有用户放款单数，放款金额
        $all_loan_sql = "select count(DISTINCT r.user_id) as countnum,
                        sum(r.principal) as money,
                        l.customer_type,
                        substr(FROM_UNIXTIME(r.created_at,'%Y-%m-%d %H:%i:%s'),12,2) as hours
                        from {$UserLoanOrderRepaymentTableName} as r
                        LEFT join  {$LoanPersonTableName} as l on r.user_id = l.id
                        where r.loan_time>={$start_time} 
                        and r.loan_time<{$end_time}
                        GROUP BY l.customer_type,substr(FROM_UNIXTIME(r.loan_time,'%Y-%m-%d %H:%i:%s'),12,2) 
                        order by substr(FROM_UNIXTIME(r.loan_time,'%Y-%m-%d %H:%i:%s'),12,2) asc";
        $all_loan = $read_db->createCommand($all_loan_sql)->queryAll();
        foreach($all_loan as $value){
            $hour =sprintf ( "%02d",$value['hours']+1);
            if($value['customer_type'] ==0 ){
                $user_type = 1;
            }else{
                $user_type = 2;
            }
            if(isset($data[$key][$hour][$user_type])){
                $data[$key][$hour][$user_type]['loan_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['loan_num'] = $value['countnum'];
            }else{
                $data[$key][$hour][$user_type]['loan_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['loan_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['repayment_money'] = 0;
                $data[$key][$hour][$user_type]['repayment_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
                $data[$key][$hour][$user_type]['all_repay_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
            $user_type = 0;
            if(isset($data[$key][$hour][$user_type])){
                $data[$key][$hour][$user_type]['loan_money'] += $value['money'];
                $data[$key][$hour][$user_type]['loan_num'] += $value['countnum'];
            }else{
                $data[$key][$hour][$user_type]['loan_money'] = $value['money']?$value['money']:0;
                $data[$key][$hour][$user_type]['loan_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['repayment_money'] = 0;
                $data[$key][$hour][$user_type]['repayment_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
                $data[$key][$hour][$user_type]['all_repay_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
        }

        //今日到期单数 到期金额
        $repays =[];
        $all_repay_sql="select count(r.order_id) as countnum,sum(principal) as money,o.is_first from {$UserLoanOrderRepaymentTableName} as r
                    LEFT join {$UserLoanOrderTableName} as o on r.order_id=o.id
                    where r.plan_fee_time>={$start_time} and r.plan_fee_time<{$end_time} GROUP BY o.is_first";
        $all_repays = $read_db->createCommand($all_repay_sql)->queryAll();
        foreach($all_repays as $value){
            if($value['is_first'] == 1){
                $user_type = 1;
            }else{
                $user_type = 2;
            }
            if(isset($repays[$user_type])) {
                $repays[$user_type]['repays'] = $value['countnum'];
            }else{
                $repays[$user_type]['repays'] = $value['countnum'];
            }
            $user_type = 0;
            if(isset($repays[$user_type])){
                $repays[$user_type]['repays']  += $value['countnum'];
            }else{
                $repays[$user_type]['repays']  = $value['countnum'];
            }
        }

        //所有提前还款单数 金额
        $all_repay_up_sql="select count(r.id) as countnum,sum(principal) as money,o.is_first,payment_type from {$UserLoanOrderRepaymentTableName} as r
        LEFT join {$UserLoanOrderTableName} as o on r.order_id=o.id
        LEFT join {$UserCreditMoneyLogTableName} as m on r.order_id=m.order_id
        where r.plan_fee_time>={$start_time} and r.plan_fee_time<{$end_time} and r.true_repayment_time<{$start_time} and r.status=4 and m.status=1 GROUP BY o.is_first,m.payment_type";
        $all_repay_up = $read_db->createCommand($all_repay_up_sql)->queryAll();
        foreach($all_repay_up as $value){
            $hour = '00';
            if($value['is_first'] ==1 ){
                $user_type = 1;
            }else{
                $user_type = 2;
            }
            if(isset($data[$key][$hour][$user_type])){
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] += $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] += $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] += $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] += $value['countnum'];
            }else{
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] = $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] = $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] = 0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
            $user_type = 0;
            if(isset($data[$key][$hour][$user_type])){
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] += $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] += $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] += $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] += $value['countnum'];
            }else{
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] = $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] = $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] = 0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
                $data[$key][$hour][$user_type]['active_repayment']=0;
            }
        }

        //所有到期还款单数 还款金额
        $all_repay_sql = "select count(r.order_id) as countnum,sum(principal) as money,o.is_first,substr(FROM_UNIXTIME(r.true_repayment_time,'%Y-%m-%d %H:%i:%s'),12,2) as hours,payment_type
            from {$UserLoanOrderRepaymentTableName} as r
            LEFT join {$UserLoanOrderTableName} as o on r.order_id=o.id
          LEFT join {$UserCreditMoneyLogTableName} as m on r.order_id=m.order_id
            where r.plan_fee_time>={$start_time} and r.plan_fee_time<{$end_time} and r.status=4 and true_repayment_time>={$start_time} and true_repayment_time<{$end_time} and m.status=1  GROUP BY o.is_first,payment_type,substr(FROM_UNIXTIME(r.true_repayment_time,'%Y-%m-%d %H:%i:%s'),12,2) ORDER by substr(FROM_UNIXTIME(r.true_repayment_time,'%Y-%m-%d %H:%i:%s'),12,2) asc";
        $all_repay = $read_db->createCommand($all_repay_sql)->queryAll();
        foreach($all_repay as $value){
            $hour =sprintf ( "%02d",$value['hours']+1);
            if($value['is_first'] ==1 ){
                $user_type = 1;
            }else{
                $user_type = 2;
            }
            if(isset($data[$key][$hour][$user_type])){
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] += $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] += $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] += $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] += $value['countnum'];
            }else{
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] = $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] = $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] = 0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
            }
            $user_type = 0;
            if(isset($data[$key][$hour][$user_type])){
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] += $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] += $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] += $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] += $value['countnum'];
            }else{
                if($value['payment_type'] !=1){
                    $data[$key][$hour][$user_type]['active_repayment'] = $value['countnum'];
                }
                $data[$key][$hour][$user_type]['repayment_money'] = $value['money'];
                $data[$key][$hour][$user_type]['repayment_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['all_repay_num'] = $value['countnum'];
                $data[$key][$hour][$user_type]['loan_money'] = 0;
                $data[$key][$hour][$user_type]['loan_num'] = 0;
                $data[$key][$hour][$user_type]['apply_money'] = 0;
                $data[$key][$hour][$user_type]['apply_num'] = 0;
            }
        }

        //截止当时
        foreach($data as $date => $value){
            foreach($value as $hour => $item){
                foreach($item as $user_type => $val){
                    $DailyTrade = DailyTrade::find()->where(['date'=>$date,'hour'=>$hour,'user_type'=>$user_type])->one($db);
                    if(!empty($DailyTrade)){
                        $DailyTrade->apply_num =$val['apply_num'];
                        $DailyTrade->apply_money =$val['apply_money'];
                        $DailyTrade->loan_num =$val['loan_num'];
                        $DailyTrade->loan_money =$val['loan_money'];
                        $DailyTrade->repayment_num =$val['repayment_num'];
                        $DailyTrade->active_repayment =isset($val['active_repayment'])?$val['active_repayment']:0;
                        $DailyTrade->repayment_money =$val['repayment_money'];
                        $DailyTrade->pass_rate =empty($val['apply_num'])?0:sprintf("%0.4f",$val['loan_num']/$val['apply_num']);
                        $DailyTrade->repay_rate =empty($repays[$user_type]['repays'])?0:sprintf("%0.4f",$val['repayment_num']/$repays[$user_type]['repays']);
                        $DailyTrade->updated_at =time();
                    }else{
                        $DailyTrade = new DailyTrade();
                        $DailyTrade->date =$date;
                        $DailyTrade->hour =$hour;
                        $DailyTrade->user_type =$user_type;
                        $DailyTrade->apply_num =$val['apply_num'];
                        $DailyTrade->apply_money =$val['apply_money'];
                        $DailyTrade->loan_num =$val['loan_num'];
                        $DailyTrade->loan_money =$val['loan_money'];
                        $DailyTrade->repayment_num =$val['repayment_num'];
                        $DailyTrade->active_repayment = isset($val['active_repayment'])?$val['active_repayment']:0;
                        $DailyTrade->repayment_money =$val['repayment_money'];
                        $DailyTrade->pass_rate =empty($val['apply_num'])?0:sprintf("%0.4f",$val['loan_num']/$val['apply_num']);
                        $DailyTrade->repay_rate =empty($repays[$user_type]['repays'])?0:sprintf("%0.4f",$val['repayment_num']/$repays[$user_type]['repays']);
                        $DailyTrade->created_at =time();
                    }
                    if (!$DailyTrade->save()) {
                        $err_msg = $date. "的借还款数据保存失败：" . json_encode($value);
                        Yii::error($err_msg);
                        $stat_email_users = Yii::$app->params['stat_email_users'];
                        MailHelper::send($stat_email_users, "getTradeData save error !", $err_msg);
                    }
                    unset($val);
                }
                unset($item);
            }
            unset($value);
        }
    }

    /**
     * 每日拒绝理由排行/actionOrderRejectReasonRank
     * @param string $date
     * @return int
     */
    public function actionOrderRejectReasonRank($date = '') {
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        Util::cliLimitChange(1024);

        if (empty($date)) {
            $diff = 0;
            if (intval(date("Hi")) < 30) {
                $diff = -86400;
            }

            $date = date('Y-m-d', time() + $diff);
        }

        CommonHelper::stdout("$date start\n");

        $start_time = strtotime($date);
        $end_time = $start_time + 86399;
        $rd_db = Yii::$app->get('db_kdkj_rd');
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $rule_info = Rule::find()->asArray()->all($read_db);
        $rules = [];
        foreach ($rule_info as $item) {
            $rules[$item['id']] = $item['name'];
        }

        $admin_mods = AdminUser::findAll(['callcenter' => 0]);
        $admin_names = ArrayHelper::getColumn($admin_mods, 'username');

        $statistics_result = [];
        $category_count = [];
        $order_count = [];

        $order_list = UserLoanOrder::find()
            ->from(UserLoanOrder::tableName() . " as order")
            ->leftJoin('tb_loan_person as user', 'order.user_id=user.id')
            ->leftJoin('tb_rong360_loan_order as rong_order', 'rong_order.order_id=order.id')
            ->where(['>=', 'order.created_at', $start_time])
            ->andWhere(['<=', 'order.created_at', $end_time])
            ->select(['rong_order.status', 'order.is_first', 'user.source_id', 'user.customer_type'])
            ->asArray()
            ->all($read_db);

        $check_log = UserLoanOrder::find()
            ->from(UserLoanOrder::tableName() . " as order")
            ->leftJoin('tb_loan_person as user', 'order.user_id=user.id')
            ->leftJoin('tb_user_order_loan_check_log as log', 'order.id=log.order_id')
            ->leftJoin('tb_rong360_loan_order as rong_order', 'rong_order.order_id=order.id')
            ->where(['>=', 'order.created_at', $start_time])
            ->andWhere(['<=', 'order.created_at', $end_time])
            ->andWhere(['log.after_status' => [UserLoanOrder::STATUS_REPEAT_CANCEL, UserLoanOrder::STATUS_CANCEL], 'log.type' => 1])
            ->select(['rong_order.status', 'order.is_first', 'user.source_id', 'user.customer_type', 'order.created_at', 'log.after_status', 'log.remark', 'log.reason_remark', 'log.head_code', 'log.back_code', 'log.operator_name'])
            ->asArray()
            ->all($read_db);

        // 初审、复审拒绝
        if ($check_log) {
            foreach ($check_log as $item) {
                $source_id = !empty($item['status']) ? -1 : $item['source_id']; // status 存在为融360订单
                $is_first = $item['is_first'] == 0 && $item['customer_type'] == 1 ? 0 : 1;

                if (empty($item['after_status'])) {
                    continue;
                }

                if (!empty($item['remark'])) {
                    $keys = $item['remark'];
                } else if (!empty($item['reason_remark'])) {
                    $keys = $item['reason_remark'];
                } else {
                    $keys = $item['head_code'] . "-" . $item['back_code'];
                }
                $keys = trim($keys);

                $status = -2; // 其他
                if (in_array($item['operator_name'], $admin_names)) {
                    $status = 2; // 人工审核
                } else if ($item['after_status'] == UserLoanOrder::STATUS_REPEAT_CANCEL) {
                    $status = 1; // 复审
                } else if ($item['head_code'] == 'D2' && $item['back_code'] == '04') {
                    $status = 3;  // 数据采集
                } else if ($item['after_status'] == UserLoanOrder::STATUS_CANCEL) {
                    $status = 0; // 决策树
                }

                if (!isset($statistics_result[$source_id][$is_first][$keys . ":::" . $status])) {
                    $statistics_result[$source_id][$is_first][$keys . ":::" . $status] = 0;
                }
                if (!isset($category_count[$source_id][$is_first][$status + 1])) {
                    $category_count[$source_id][$is_first][$status + 1] = 0;
                }
                if (!isset($category_count[$source_id][$is_first][0])) {
                    $category_count[$source_id][$is_first][0] = 0;
                }
                $category_count[$source_id][$is_first][0]++;
                $statistics_result[$source_id][$is_first][$keys . ":::" . $status]++;
                $category_count[$source_id][$is_first][$status + 1]++;
            }
        }

        // 总订单统计
        if ($order_list) {
            foreach ($order_list as $item) {
                $source_id = !empty($item['status']) ? -1 : $item['source_id']; // status 存在为融360订单
                $is_first = $item['is_first'] == 0 && $item['customer_type'] == 1 ? 0 : 1;
                if (!empty($item['status']) && $item['status'] == 80) { // 融360
                    continue;
                }

                if (!isset($order_count[$source_id][$is_first])) {
                    $order_count[$source_id][$is_first] = 0;
                }

                $order_count[$source_id][$is_first]++;
            }
        }

        CommonHelper::stdout("$date audit_reject_order done\n");

        OrderRejectRank::deleteAll(['date' => $date]);

        foreach ($statistics_result as $source_id => $items) {
            foreach ($items as $is_first => $list) {
                $count_info = $category_count[$source_id][$is_first];
                ksort($count_info);
                arsort($list);
                $list = $count_info + $list;
                $i = 0;
                foreach ($list as $keys => $value) {
                    $key = $keys;
                    $status = 0;
                    if (!is_numeric($key)) {
                        $info = explode(":::", $keys);
                        $key = $info[0];
                        $status = $info[1];
                        $i++;
                    }

                    $reject_info = new OrderRejectRank();
                    $reject_info->date = $date;
                    $reject_info->key = $key;
                    $percent = isset($count_info[0]) ? sprintf("%.2f", $value / $count_info[0] * 100) : 0;
                    $reject_info->value = $value;
                    $reject_info->rank = $key === 0 && isset($order_count[$source_id][$is_first]) ? $order_count[$source_id][$is_first] : $i;
                    $reject_info->percent = $percent;
                    $reject_info->status = $status;
                    $reject_info->source_id = $source_id;
                    $reject_info->is_first = $is_first;
                    if (!$reject_info->save()) {
                        CommonHelper::error("order_reject_rank save failed. data: {date: $date, key: $key, value: $value, rank: $i, percent: $percent, count: $category_count[0]}", "order_reject_rank");
                    }
                }
            }
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @name 收付款统计列表/actionTradeList
     * @param $start_date string 2017-10-01
     * @use 财务管理
     */
    public function actionTradeList($type=1, $start_date = null, $countDate = 3){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        //如果$type = 2 循环跑近一个月数据
        if($type==2){
            $end_date = date("Y-m-d") ;
            $start_date = date("Y-m-d", strtotime("-1 month")) ;
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getTradeListFundData($dateNum,$end_time,$dtype=0);
            }
        }elseif($type ==3){
            $end_date = date("Y-m-d", strtotime("-1 month"));
            $start_date = date("Y-m-d", strtotime("-2 month"));
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getTradeListFundData($dateNum,$end_time,$dtype=0);
            }
        }elseif($type ==4){
            $end_date = date("Y-m-d", strtotime("-2 month"));
            $start_date = date("Y-m-d", strtotime("-3 month"));
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getTradeListFundData($dateNum,$end_time,$dtype=0);
            }
        }else{
            $start_time =strtotime("today"); //今天零点
            $end_time = $start_time+86400;
            $now_time = time();
            $_hour = date('H',$now_time);//当前的小时数
            //如果当前时间为24点，则计算前一天所有的注册量等数据,显示日期为前一天的24时
            if( $_hour == 0 ){
                $end_time = $start_time;
                $start_time = $end_time-86400;
            }
            $this->getTradeListFundData($start_time,$end_time,$dtype=0);
        }
    }
    //收付款分资方统计
    private function getTradeListFundData($start_time,$end_time,$type){
        echo date('Y-m-d',$start_time)."-".date('Y-m-d',$end_time)."\n";
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $UserLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserLoanOrderTableName = UserLoanOrder::tableName();
        $UserCreditMoneyLogTableName = UserCreditMoneyLog::tableName();
        $FinancialLoanRecordTableName = FinancialLoanRecord::tableName();
        $u_status = UserCreditMoneyLog::STATUS_SUCCESS;//还款成功
        $status = FinancialLoanRecord::UMP_PAY_SUCCESS;//打款成功状态
        $datas =[];
        $all =[];
        $key = date('Y-m-d',$start_time);
        //当天流水
        echo "查询流水表中每笔订单的还款金额".PHP_EOL;
        $sql="select o.order_id as order_id ,o.user_id,sum(o.operator_money) as operator_money,lo.fund_id 
              from {$UserCreditMoneyLogTableName} as o
              LEFT JOIN {$UserLoanOrderTableName} as lo on lo.id=o.order_id
              where o.id >0
              and o.success_repayment_time >= {$start_time}
              and o.success_repayment_time < {$end_time}
              and o.status={$u_status}
              GROUP by o.order_id";
        $late_fee_data=$read_db->createCommand($sql)->queryAll();

        echo "开始统计流水表中各资方的还款金额".PHP_EOL;
        foreach($late_fee_data as $value){
            $order_id=$value['order_id'];
            //该订单资方
            $fund_id=$value['fund_id'];
            //echo "订单：{$order_id}\t渠道：$fund_id".PHP_EOL;
            //当天的订单流水金额
            $datas[$fund_id][$order_id]['sub_true_total_money']=$value['operator_money'] ?? 0;

            //该订单总流水截止今天
            $sql="select sum(operator_money) AS operator_money from {$UserCreditMoneyLogTableName}
                  where order_id = '{$order_id}'
                  and operator_money > 0
                  and status ={$u_status}
                  and success_repayment_time<{$end_time}";
            $op_data=$read_db->createCommand($sql)->queryOne();
            $datas[$fund_id][$order_id]['sub_operator_money']=$op_data['operator_money'] ?? 0;

            echo "订单号：{$order_id}截止今天的已还金额:{$datas[$fund_id][$order_id]['sub_operator_money']}".PHP_EOL;
            //该订单本金
            $sql="select principal,coupon_money,status,true_repayment_time from {$UserLoanOrderRepaymentTableName}
                  where order_id = {$order_id}";
            $pr_data=$read_db->createCommand($sql)->queryOne();
            $datas[$fund_id][$order_id]['sub_principal']=$pr_data['principal'] ?? 0;
            $datas[$fund_id][$order_id]['diff_money'] = $datas[$fund_id][$order_id]['sub_operator_money'] - $datas[$fund_id][$order_id]['sub_principal'];
            $datas[$fund_id][$order_id]['_late_fee'] = max(0,min($datas[$fund_id][$order_id]['sub_true_total_money'], $datas[$fund_id][$order_id]['diff_money']));

            //计算本金
            if($datas[$fund_id][$order_id]['sub_operator_money'] <= $datas[$fund_id][$order_id]['sub_principal']){//历史还款流水总额小于等于本金
                $datas[$fund_id][$order_id]['principal'] = $datas[$fund_id][$order_id]['sub_true_total_money'];
            }else{//历史还款流水总额大于本金
                $before_today_money = $datas[$fund_id][$order_id]['sub_operator_money'] - $datas[$fund_id][$order_id]['sub_true_total_money'];//今日之前已还金额
                $diff_money2 = $datas[$fund_id][$order_id]['sub_principal'] - $before_today_money;
                $datas[$fund_id][$order_id]['principal'] = max(0, min($diff_money2, $datas[$fund_id][$order_id]['sub_true_total_money']));
            }
            //优惠券金额
            $datas[$fund_id][$order_id]['coupon_money']=$pr_data['coupon_money']??0;
        }

//        var_dump($datas);
        foreach($datas as $fund_id=>$order){
            foreach($order as $item){
                if(isset($all[$fund_id])){
                    $all[$fund_id]['true_total_money']+=isset($item['sub_true_total_money'])?$item['sub_true_total_money']:0;
                    $all[$fund_id]['principal']+=isset($item['principal'])?$item['principal']:0;
                    $all[$fund_id]['late_fee']+=isset($item['_late_fee'])?$item['_late_fee']:0;
                    $all[$fund_id]['coupon_money']+=isset($item['coupon_money'])?$item['coupon_money']:0;
                    $all[$fund_id]['countnum']+=isset($item['countnum'])?$item['countnum']:0;
                    $all[$fund_id]['money']+=isset($item['money'])?$item['money']:0;
                    $all[$fund_id]['counter_fee']+=isset($item['counter_fee'])?$item['counter_fee']:0;
                }else{
                    $all[$fund_id]['true_total_money']=isset($item['sub_true_total_money'])?$item['sub_true_total_money']:0;
                    $all[$fund_id]['principal']=isset($item['principal'])?$item['principal']:0;
                    $all[$fund_id]['late_fee']=isset($item['_late_fee'])?$item['_late_fee']:0;
                    $all[$fund_id]['coupon_money']=isset($item['coupon_money'])?$item['coupon_money']:0;
                    $all[$fund_id]['countnum']=isset($item['countnum'])?$item['countnum']:0;
                    $all[$fund_id]['money']=isset($item['money'])?$item['money']:0;
                    $all[$fund_id]['counter_fee']=isset($item['counter_fee'])?$item['counter_fee']:0;
                }
            }
        }

//        var_dump($all);
        echo "开始统计借款单数".PHP_EOL;
        //借款单数 借款金额
        $finan_sql ="select if(sum(f.money),sum(f.money),0) as money, count(f.id) as countnum,o.fund_id,
                    if(sum(f.counter_fee),sum(f.counter_fee) ,0) as counter_fee
                    from {$FinancialLoanRecordTableName} as f
                    LEFT JOIN {$UserLoanOrderTableName} as o on o.id=f.business_id
                    where f.success_time>={$start_time} and f.success_time<{$end_time} and f.status={$status}  and o.id>0 GROUP by o.id";
        $finan_data = $read_db->createCommand($finan_sql)->queryAll();

        foreach($finan_data as $value){
            //该订单资方
            $fund_id=$value['fund_id'];
            if(isset($all[$fund_id])){
                $all[$fund_id]['countnum']++;
                $all[$fund_id]['money']+=$value['money'];
                $all[$fund_id]['counter_fee']+=$value['counter_fee'];
            }else{
                $all[$fund_id]['countnum']=1;
                $all[$fund_id]['money']=$value['money'];
                $all[$fund_id]['counter_fee']=$value['counter_fee'];
                $all[$fund_id]['true_total_money']=0;
                $all[$fund_id]['principal']=0;
                $all[$fund_id]['late_fee']=0;
                $all[$fund_id]['coupon_money']=0;
            }
        }
        foreach($all as $item){
            $fund_id=0;
            if(isset($all[$fund_id])){
                $all[$fund_id]['true_total_money']+=$item['true_total_money'];
                $all[$fund_id]['principal']+=$item['principal'];
                $all[$fund_id]['late_fee']+=$item['late_fee'];
                $all[$fund_id]['coupon_money']+=$item['coupon_money'];
                $all[$fund_id]['countnum']+=$item['countnum'];
                $all[$fund_id]['money']+=$item['money'];
                $all[$fund_id]['counter_fee']+=$item['counter_fee'];
            }else{
                $all[$fund_id]['true_total_money']=$item['true_total_money'];
                $all[$fund_id]['principal']=$item['principal'];
                $all[$fund_id]['late_fee']=$item['late_fee'];
                $all[$fund_id]['coupon_money']=$item['coupon_money'];
                $all[$fund_id]['countnum']=$item['countnum'];
                $all[$fund_id]['money']=$item['money'];
                $all[$fund_id]['counter_fee']=$item['counter_fee'];
            }
        }

        echo "开始保存记录".PHP_EOL;
        foreach($all as $fund_id=>$value){
            $finialy_data = FinancialSubsidiaryLedger::find()->where(['date'=>$key,'type'=>$type,'fund_id'=>$fund_id])->one();

            if(!empty($finialy_data)){
                $finialy_data->updated_at =time();
            }else {
                $finialy_data = new FinancialSubsidiaryLedger();
                $finialy_data->date = $key;
                $finialy_data->type = $type;
                $finialy_data->created_at =time();
            }
            $finialy_data->loan_num =$value['countnum'] ?? 0;
            $finialy_data->loan_money =$value['money'] ?? 0;
            $finialy_data->true_loan_money = max(0,$value['money']-$value['counter_fee']);
            $finialy_data->counter_fee =$value['counter_fee'] ?? 0;
            $finialy_data->true_total_principal =$value['true_total_money'] ?? 0;
            $finialy_data->true_total_money =$value['principal'] ?? 0;
            $finialy_data->late_fee =$value['late_fee'] ?? 0;
            $finialy_data->coupon_money =$value['coupon_money'] ?? 0;
            $finialy_data->rollover_money =0;
            $finialy_data->true_rollover_handlefee =0;
            $finialy_data->true_rollover_counterfee =0;
            $finialy_data->true_rollover_apr =0;
            $finialy_data->fund_id =$fund_id;
            if (!$finialy_data->save()) {
               echo $key. "的借还款数据保存失败：" . $finialy_data.PHP_EOL;
            }
        }
    }

    /**
     * @name 运营成本统计/actionOperatingCosts
     * @use 财务管理
     */
    public function actionOperatingCosts($type=1){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        //如果$type = 2 循环跑每月的数据
        if($type==2){
            $end_date = date("Y-m-d");
            $start_date = '2017-03-28';
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getOperatingListData($dateNum,$end_time);
            }
        }else{
            $start_time = strtotime("today"); //今天零点
            $end_time = $start_time+86400;
            $now_time = time();
            $_hour = date('H',$now_time);//当前的小时数
            //如果当前时间为24点，则计算前一天所有的注册量等数据,显示日期为前一天的24时
            if( $_hour == 0 ){
                $end_time = $start_time;
                $start_time = $end_time-86400;
            }

            $this->getOperatingListData($start_time,$end_time);
        }
    }

    /**
     * 运营成本统计私有方法/getOperatingListData
     * @param $start_time
     * @param $end_time
     */
    private function getOperatingListData($start_time,$end_time){
        $db = \Yii::$app->db_kdkj_rd;
        $read_db = \Yii::$app->db_kdkj_rd_new;
        $UserLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserLoanOrderTableName = UserLoanOrder::tableName();
        $FinancialLoanRecordTableName = FinancialLoanRecord::tableName();
        $data = $reset_use_data = $reset_derate_data = [];
        $key = date('Y-m-d',$start_time);

        //优惠券使用金额
        $coupon_use_sql="select if(sum(r.coupon_money),sum(r.coupon_money),0) as coupon_money,
                        o.fund_id as fund_id
                        from {$UserLoanOrderRepaymentTableName} as r
                        left join {$UserLoanOrderTableName} as o on r.order_id=o.id
                        where r.created_at>={$start_time} 
                        and r.created_at<{$end_time} 
                        and r.status !=4
                        group by o.fund_id";
        $coupon_use_data = $read_db->createCommand($coupon_use_sql)->queryAll();
        if(!empty($coupon_use_data)){
            foreach ($coupon_use_data as $k => $v){
                $fund_id = $v['fund_id'] ?? LoanFund::ID_KOUDAI;
                $coupon_money = $v['coupon_money'] ?? 0;
                $reset_use_data[$fund_id] = $coupon_money;
            }
        }

        //优惠券减免金额
        $coupon_derate_sql="select if(sum(r.coupon_money),sum(r.coupon_money),0) as coupon_money,
                            o.fund_id as fund_id
                            from {$UserLoanOrderRepaymentTableName} as r
                            left join {$UserLoanOrderTableName} as o on r.order_id=o.id
                            where r.true_repayment_time>={$start_time} 
                            and r.true_repayment_time<{$end_time} 
                            and r.status =4
                            group by o.fund_id";
        $coupon_derate_data = $read_db->createCommand($coupon_derate_sql)->queryAll();
        if(!empty($coupon_derate_data)){
            foreach ($coupon_derate_data as $k => $v){
                $fund_id = $v['fund_id'] ?? LoanFund::ID_KOUDAI;
                $coupon_money = $v['coupon_money'] ?? 0;
                $reset_derate_data[$fund_id] = $coupon_money;
            }
        }

        //按资方id统计
        $all_coupon_usemoney = $all_coupon_deratemoney = 0;
        foreach ($this->_fund_id_list as $k => $fund_id){
            if(!empty($reset_use_data[$fund_id]) || !empty($reset_derate_data[$fund_id])){
                $data[$fund_id]['coupon_usemoney'] = $reset_use_data[$fund_id] ?? 0;
                $data[$fund_id]['coupon_deratemoney'] = $reset_derate_data[$fund_id] ?? 0;
                $all_coupon_usemoney += $data[$fund_id]['coupon_usemoney'];
                $all_coupon_deratemoney += $data[$fund_id]['coupon_deratemoney'];
            }
        }

        if($all_coupon_usemoney > 0 || $all_coupon_deratemoney > 0){
            //全部渠道
            $data[0]['coupon_usemoney'] = $all_coupon_usemoney;
            $data[0]['coupon_deratemoney'] = $all_coupon_deratemoney;
            //数据写入库
            foreach ($data as $fund_id => $v){
                $where = [
                    'and',
                    ['=', 'date', $key],
                    ['=', 'fund_id', $fund_id],
                ];
                $FinancialExpense = FinancialExpense::find()->where($where)->one();
                if(empty($FinancialExpense)){
                    $FinancialExpense = new FinancialExpense();
                    $FinancialExpense->date=$key;
                    $FinancialExpense->created_at=time();
                }else{
                    $FinancialExpense->updated_at=time();
                }
                $FinancialExpense->refuse_num = 0;
                $FinancialExpense->refuse_money = 0;
                $FinancialExpense->redapply_money = 0;
                $FinancialExpense->redloan_money = 0;
                $FinancialExpense->fund_id = $fund_id;
                $FinancialExpense->coupaon_usemoney = $v['coupon_usemoney'];
                $FinancialExpense->coupaon_deratemoney = $v['coupon_deratemoney'];
                if (!$FinancialExpense->save()) {
                    Yii::error($key. "的运营数据保存失败：" . $v);
                }
            }
        }
        CommonHelper::stdout(sprintf("getOperatingListData %s end.\n", $key));
    }

    /**
     * @name 每日到期还款续借率/actionDayDataStatisticsRun
     * @use 产品
     * @author chenlu
     */
    public function actionDayDataStatisticsRun(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $end_date =date("Y-m-d",strtotime("+14 day"));
        $start_date = date("Y-m-d",strtotime("-1 day"));
        $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
        for($datei = 0;$datei<$countDate;$datei++){
            $dateNum = strtotime($end_date)-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $this->runDayDataStatistics($date);
            $this->runDayDataAppTypeStatistics($date);
        }
    }
    public function actionDayDataStatisticsRun1(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $end_date =date("Y-m-d",strtotime("-1 day"));
        $start_date = date("Y-m-d",strtotime("-7 day"));
        $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
        for($datei = 0;$datei<$countDate;$datei++){
            $dateNum = strtotime($end_date)-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $this->runDayDataStatistics($date);
            $this->runDayDataAppTypeStatistics($date);
        }
    }
    public function actionDayDataStatisticsRun2(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $end_date =date("Y-m-d",strtotime("-7 day"));
        $start_date = date("Y-m-d",strtotime("-30 day"));
        $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
        for($datei = 0;$datei<$countDate;$datei++){
            $dateNum = strtotime($end_date)-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $this->runDayDataStatistics($date);
            $this->runDayDataAppTypeStatistics($date);
        }

    }
    public function actionDayDataStatisticsRun3(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $end_date =date("Y-m-d",strtotime("-30 day"));
        $start_date = date("Y-m-d",strtotime("-120 day"));
        $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
        for($datei = 0;$datei<$countDate;$datei++){
            $dateNum = strtotime($end_date)-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $this->runDayDataStatistics($date);
        }
    }


    private function runDayDataStatistics($pre_date){
        Util::cliLimitChange(1024);

        $db = \Yii::$app->db_kdkj_rd_new;
        $save_db = \Yii::$app->db_stats;
        echo "date:{$pre_date}\n";
        $pre_time = strtotime($pre_date);
        $end_time = $pre_time + 86400;

        $expire=[];
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserRegisterInfoTableName = UserRegisterInfo::tableName();

        // 到期笔数/到期金额
        $user_all_num=0;
        $user_all_money=0;
        $user_new_num=0;
        $user_new_money=0;
        $user_old_num=0;
        $user_old_money=0;
        $repay_all_num=0;
        $repay_all_money=0;
        $repay_old_num=0;
        $repay_old_money=0;
        $repay_new_num=0;
        $repay_new_money=0;
        $repay_zcall_num=0;
        $repay_zcall_money=0;
        $repay_zcold_num=0;
        $repay_zcold_money=0;
        $repay_zcnew_num=0;
        $repay_zcnew_money=0;
        $lj_fj_all=0;
        $lj_fj_all_money=0;
        $lj_fj_new=0;
        $lj_fj_new_money=0;
        $lj_fj_old=0;
        $lj_fj_old_money=0;
        $zc_fj_new=0;
        $zc_fj_new_money=0;
        $zc_fj_all=0;
        $zc_fj_all_money=0;
        $zc_fj_old=0;
        $zc_fj_old_money=0;

        $expire_sql = "SELECT r.user_id, r.order_id, r.true_repayment_time,principal,r.status,r.is_overdue, p.customer_type,o.is_first
                        FROM tb_user_loan_order_repayment as r
                        LEFT JOIN tb_user_loan_order as o ON r.order_id = o.id
                        LEFT JOIN tb_loan_person as p ON p.id = r.user_id
                        WHERE r.plan_fee_time >= {$pre_time}
                        AND r.plan_fee_time <{$end_time} ";

        $expire_res = $db->createCommand($expire_sql)->queryall();
        $expire_uid_time = ArrayHelper::map($expire_res, 'user_id', 'true_repayment_time');

        foreach ($expire_res as $val) {
            $_uid = $val['user_id'];
            $_oid = $val['order_id'];
            $fj_sql = "select min(r.order_id) as order_id,principal,source,r.created_at as created_at from {$userLoanOrderRepaymentTableName} as r
             LEFT JOIN {$UserRegisterInfoTableName} as ur on ur.user_id=r.user_id
             where r.user_id = {$_uid} and r.order_id > {$_oid} ";
            $fj_oid = $db->createCommand($fj_sql)->queryOne();
            $user_all_num++;
            $user_all_money+=$val['principal'];
            if($val['status'] == 4){
                $repay_all_num++;
                $repay_all_money+=$val['principal'];
                if($val['is_overdue']==0){
                    $repay_zcall_num++;
                    $repay_zcall_money+=$val['principal'];

                    if ($fj_oid['order_id'] > 0 && $fj_oid['created_at']<$end_time) {
                        $zc_fj_all++;
                        $zc_fj_all_money+=$fj_oid['principal'];
                    }
                    //正常还款复借老用户
                    if($val['customer_type']==1 && $val['is_first']==0 && $fj_oid['created_at']<$end_time){
                        if ($fj_oid['order_id'] > 0) {
                            $zc_fj_old++;
                            $zc_fj_old_money+=$fj_oid['principal'];
                        }
                    }
                    // 正常还款复借新用户
                    elseif(($val['customer_type']==0 || $val['is_first']==1)&&$fj_oid['created_at']<$end_time ){
                        if ($fj_oid['order_id'] > 0) {
                            $zc_fj_new++;
                            $zc_fj_new_money+=$fj_oid['principal'];
                        }
                    }
                }

                $fj_sql = "select min(r.order_id) as order_id,principal,source from {$userLoanOrderRepaymentTableName} as r LEFT JOIN {$UserRegisterInfoTableName} as ur on ur.user_id=r.user_id where r.user_id = {$_uid} and r.order_id > {$_oid} ";
                $fj_oid = $db->createCommand($fj_sql)->queryOne();
                if ($fj_oid['order_id'] > 0) {
                    $lj_fj_all++;
                    $lj_fj_all_money+=$fj_oid['principal'];
                }
                // 累计还款复借新用户
                if ($val['customer_type']==0 || $val['is_first']==1) {
                    if ($fj_oid['order_id'] > 0) {
                        $lj_fj_new++;
                        $lj_fj_new_money+=$fj_oid['principal'];
                    }
                    //累计还款复借老用户
                } else if ($val['customer_type']==1 && $val['is_first']==0 ) {
                    if ($fj_oid['order_id'] > 0) {
                        $lj_fj_old++;
                        $lj_fj_old_money+=$fj_oid['principal'];
                    }
                }
            }

            //正常新用户还款
            if (isset($expire_uid_time[$_uid]) && ($val['customer_type']==0 || $val['is_first']==1)) {
                $user_new[] = $_uid;
                $user_new_num++;
                $user_new_money+=$val['principal'];
                if($val['status'] == 4){
                    $repay_new_num++;
                    $repay_new_money+=$val['principal'];
                    if($val['is_overdue']==0){
                        $repay_zcnew_num++;
                        $repay_zcnew_money+=$val['principal'];
                    }
                }
            }
            //正常老用户还款
            else if (isset($expire_uid_time[$_uid]) && ($val['customer_type']==1 && $val['is_first']==0)) {
                $user_old[] = $_uid;
                $user_old_num++;
                $user_old_money+=$val['principal'];
                if($val['status'] == 4){
                    $repay_old_num++;
                    $repay_old_money+=$val['principal'];
                    if($val['is_overdue']==0){
                        $repay_zcold_num++;
                        $repay_zcold_money+=$val['principal'];
                    }
                }
            } else {
                // 查询是新老用户
                if ($val['customer_type']==1 && $val['is_first']==0) {
                    $user_old[] = $_uid;
                    $user_old_num++;
                    $user_old_money+=$val['principal'];
                    if($val['status'] == 4){
                        $repay_old_num++;
                        $repay_old_money+=$val['principal'];
                        if($val['is_overdue']==0){
                            $repay_zcold_num++;
                            $repay_zcold_money+=$val['principal'];
                        }
                    }
                } else {
                    $user_new[] = $_uid;
                    $user_new_num++;
                    $user_new_money+=$val['principal'];
                    if($val['status'] == 4){
                        $repay_new_num++;
                        $repay_new_money+=$val['principal'];
                        if($val['is_overdue']==0){
                            $repay_zcnew_num++;
                            $repay_zcnew_money+=$val['principal'];
                        }
                    }
                }
            }
        }

        $user_type=0;
        $expire[$user_type]['expire_num'] = $user_all_num;
        $expire[$user_type]['expire_money'] = $user_all_money;
        $expire[$user_type]['repay_num'] = $repay_all_num;
        $expire[$user_type]['repay_money'] = $repay_all_money;
        $expire[$user_type]['repay_xj_num'] = $lj_fj_all;
        $expire[$user_type]['repay_xj_money'] = $lj_fj_all_money;
        $expire[$user_type]['repay_zc_num'] = $repay_zcall_num;
        $expire[$user_type]['repay_zc_money'] = $repay_zcall_money;
        $expire[$user_type]['repay_zcxj_num'] = $zc_fj_all;
        $expire[$user_type]['repay_zcxj_money'] =$zc_fj_all_money;
        $user_type=1;
        $expire[$user_type]['expire_num'] = $user_new_num;
        $expire[$user_type]['expire_money'] = $user_new_money;
        $expire[$user_type]['repay_num'] = $repay_new_num;
        $expire[$user_type]['repay_money'] = $repay_new_money;
        $expire[$user_type]['repay_xj_num'] = $lj_fj_new;
        $expire[$user_type]['repay_xj_money'] = $lj_fj_new_money;
        $expire[$user_type]['repay_zc_num'] = $repay_zcnew_num;
        $expire[$user_type]['repay_zc_money'] = $repay_zcnew_money;
        $expire[$user_type]['repay_zcxj_num'] = $zc_fj_new;
        $expire[$user_type]['repay_zcxj_money'] = $zc_fj_new_money;
        $user_type=2;
        $expire[$user_type]['expire_num'] = $user_old_num;
        $expire[$user_type]['expire_money'] = $user_old_money;
        $expire[$user_type]['repay_num'] = $repay_old_num;
        $expire[$user_type]['repay_money'] = $repay_old_money;
        $expire[$user_type]['repay_xj_num'] = $lj_fj_old;
        $expire[$user_type]['repay_xj_money'] = $lj_fj_old_money;
        $expire[$user_type]['repay_zc_num'] = $repay_zcold_num;
        $expire[$user_type]['repay_zc_money'] = $repay_zcold_money;
        $expire[$user_type]['repay_zcxj_num'] = $zc_fj_old;
        $expire[$user_type]['repay_zcxj_money'] = $zc_fj_old_money;

        foreach($expire as $key=> $value){
            $data = StatisticsDayData::find()->where(['date'=>$pre_date,'user_type'=>$key,'source'=>0])->one($save_db);
            if(!empty($data)){
                $data->updated_at = time();
            }else{
                $data = new StatisticsDayData();
                $data->date=$pre_date;
                $data->user_type=$key;
                $data->created_at=time();
            }
            $data->source = 0;
            $data->expire_num = $value['expire_num'];
            $data->expire_money = $value['expire_money'];
            $data->repay_num = $value['repay_num'];
            $data->repay_money = $value['repay_money'];
            $data->repay_xj_num = $value['repay_xj_num'];
            $data->repay_xj_money = $value['repay_xj_money'];
            $data->repay_zc_num = $value['repay_zc_num'];
            $data->repay_zc_money = $value['repay_zc_money'];
            $data->repay_zcxj_num = $value['repay_zcxj_num'];
            $data->repay_zcxj_money = $value['repay_zcxj_money'];
            $data->zcxj_rate =empty($value['repay_zc_num'])?0:sprintf("%0.4f",$value['repay_zcxj_num']/$value['repay_zc_num']);
            $data->xj_rate =empty($value['repay_num'])?0:sprintf("%0.4f",$value['repay_xj_num']/$value['repay_num']);
            if (!$data->save()) {
                MailHelper::send("chenlu@wzdai.com", '还款续借率',Yii::error("统计" . $pre_date . "的当天数据保存失败：" ));
            }
            unset($value);
        }
    }

    private function runDayDataAppTypeStatistics($pre_date){
        Util::cliLimitChange(1024);
        $db = \Yii::$app->db_kdkj_rd_new;
        $save_db = \Yii::$app->db_stats;
        $pre_time = strtotime($pre_date);
        $end_time = $pre_time + 86400;
        $expire=[];
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $UserRegisterInfoTableName = UserRegisterInfo::tableName();

        // 到期笔数/到期金额
        $user_all_num=0;
        $user_all_money=0;
        $user_new_num=0;
        $user_new_money=0;
        $user_old_num=0;
        $user_old_money=0;
        $repay_all_num=0;
        $repay_all_money=0;
        $repay_old_num=0;
        $repay_old_money=0;
        $repay_new_num=0;
        $repay_new_money=0;
        $repay_zcall_num=0;
        $repay_zcall_money=0;
        $repay_zcold_num=0;
        $repay_zcold_money=0;
        $repay_zcnew_num=0;
        $repay_zcnew_money=0;
        $lj_fj_all=0;
        $lj_fj_all_money=0;
        $lj_fj_new=0;
        $lj_fj_new_money=0;
        $lj_fj_old=0;
        $lj_fj_old_money=0;
        $zc_fj_new=0;
        $zc_fj_new_money=0;
        $zc_fj_all=0;
        $zc_fj_all_money=0;
        $zc_fj_old=0;
        $zc_fj_old_money=0;

        $expire_sql = "SELECT r.user_id, r.order_id, r.true_repayment_time,principal,r.status,r.is_overdue, p.customer_type,o.is_first,r.is_overdue,source
                        FROM tb_user_loan_order_repayment as r
                        LEFT JOIN  tb_user_register_info as ur on ur.user_id=r.user_id
                        LEFT JOIN tb_user_loan_order as o ON r.order_id = o.id
                        LEFT JOIN tb_loan_person as p ON p.id = r.user_id
                        WHERE r.plan_fee_time >= {$pre_time}
                        AND r.plan_fee_time <{$end_time}
                        GROUP BY r.order_id
                        ";

        $expire_res = $db->createCommand($expire_sql)->queryall();
        $expire_uid_time = ArrayHelper::map($expire_res, 'user_id', 'true_repayment_time');

        foreach ($expire_res as $val) {
            $_uid = $val['user_id'];
            $_oid = $val['order_id'];
            $source = $val['source'];
            if($source == 0 ||empty($source)){
                $source=21;
            }

            $fj_sql = "select min(r.order_id) as order_id,principal,source,r.created_at as created_at from {$userLoanOrderRepaymentTableName} as r
             LEFT JOIN {$UserRegisterInfoTableName} as ur on ur.user_id=r.user_id
             where r.user_id = {$_uid} and r.order_id > {$_oid} and r.plan_fee_time >= {$pre_time} and r.plan_fee_time <{$end_time}";
            $fj_oid = $db->createCommand($fj_sql)->queryOne();

            $user_type=0;
            if(isset( $expire[$user_type][$source])){
                $expire[$user_type][$source]['expire_num'] ++;
                $expire[$user_type][$source]['expire_money'] +=$val['principal'];
            }else{
                $expire[$user_type][$source]['expire_num'] =1;
                $expire[$user_type][$source]['expire_money'] =$val['principal'];
                $expire[$user_type][$source]['repay_num'] = $repay_all_num;
                $expire[$user_type][$source]['repay_money'] = $repay_all_money;
                $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_all;
                $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_all_money;
                $expire[$user_type][$source]['repay_zc_num'] = $repay_zcall_num;
                $expire[$user_type][$source]['repay_zc_money'] = $repay_zcall_money;
                $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_all;
                $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_all_money;
            }

            if($val['status'] == 4){
                $user_type=0;
                if(isset($expire[$user_type][$source])){
                    $expire[$user_type][$source]['repay_num'] ++;
                    $expire[$user_type][$source]['repay_money'] += $val['principal'];
                }else{
                    $expire[$user_type][$source]['repay_num'] = 1;
                    $expire[$user_type][$source]['repay_money'] = $val['principal'];
                    $expire[$user_type][$source]['expire_num'] = $user_all_num;
                    $expire[$user_type][$source]['expire_money'] = $user_all_money;
                    $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_all;
                    $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_all_money;
                    $expire[$user_type][$source]['repay_zc_num'] = $repay_zcall_num;
                    $expire[$user_type][$source]['repay_zc_money'] = $repay_zcall_money;
                    $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_all;
                    $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_all_money;
                }
                if($val['is_overdue']==0){
                    $user_type=0;
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['repay_zc_num'] ++;
                        $expire[$user_type][$source]['repay_zc_money'] += $val['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] =$user_all_num;
                        $expire[$user_type][$source]['expire_money'] =$user_all_money;
                        $expire[$user_type][$source]['repay_num'] = $repay_all_num;
                        $expire[$user_type][$source]['repay_money'] = $repay_all_money;
                        $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_all;
                        $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_all_money;
                        $expire[$user_type][$source]['repay_zc_num'] = 1;
                        $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_all;
                        $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_all_money;
                    }
                    if ($fj_oid['order_id'] > 0 && $fj_oid['created_at']<$end_time) {
                        $user_type=0;
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_zcxj_num']++;
                            $expire[$user_type][$source]['repay_zcxj_money'] +=$fj_oid['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] =$user_all_num;
                            $expire[$user_type][$source]['expire_money'] =$user_all_money;
                            $expire[$user_type][$source]['repay_num'] = $repay_all_num;
                            $expire[$user_type][$source]['repay_money'] = $repay_all_money;
                            $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_all;
                            $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_all_money;
                            $expire[$user_type][$source]['repay_zc_num'] = 1;
                            $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_all;
                            $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_all_money;
                        }
                    }
                    // 正常还款复借新用户
                    if (($val['customer_type']==0 || $val['is_first']==1) && $fj_oid['created_at']<$end_time) {
                        if ($fj_oid['order_id'] > 0) {
                            $user_type=1;
                            if(isset($expire[$user_type][$source])){
                                $expire[$user_type][$source]['repay_zcxj_num']++;
                                $expire[$user_type][$source]['repay_zcxj_money'] +=$fj_oid['principal'];
                            }else{
                                $expire[$user_type][$source]['expire_num'] = $user_new_num;
                                $expire[$user_type][$source]['expire_money'] = $user_new_money;
                                $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                                $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                                $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                                $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                                $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                                $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                                $expire[$user_type][$source]['repay_zcxj_num'] = 1;
                                $expire[$user_type][$source]['repay_zcxj_money'] =$fj_oid['principal'];
                            }
                        }
                        //正常还款复借老用户
                    } else if ($val['customer_type']==1 && $val['is_first']==0 && $fj_oid['created_at']<$end_time) {
                        if ($fj_oid['order_id'] > 0) {
                            $user_type=2;
                            if(isset($expire[$user_type][$source])){
                                $expire[$user_type][$source]['repay_zcxj_num'] ++;
                                $expire[$user_type][$source]['repay_zcxj_money'] +=$fj_oid['principal'];
                            }else{
                                $expire[$user_type][$source]['expire_num'] = $user_old_num;
                                $expire[$user_type][$source]['expire_money'] = $user_old_money;
                                $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                                $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                                $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                                $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                                $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                                $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                                $expire[$user_type][$source]['repay_zcxj_num'] = 1;
                                $expire[$user_type][$source]['repay_zcxj_money'] =$fj_oid['principal'];
                            }

                        }
                    }
                }

                $fj_sql = "select min(r.order_id) as order_id,principal,source from {$userLoanOrderRepaymentTableName} as r LEFT JOIN {$UserRegisterInfoTableName} as ur on ur.user_id=r.user_id where r.user_id = {$_uid} and r.order_id > {$_oid} ";
                $fj_oid = $db->createCommand($fj_sql)->queryOne();
                if ($fj_oid['order_id'] > 0) {
                    $user_type=0;
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['repay_xj_num']++;
                        $expire[$user_type][$source]['repay_xj_money'] +=$fj_oid['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] =$user_all_num;
                        $expire[$user_type][$source]['expire_money'] =$user_all_money;
                        $expire[$user_type][$source]['repay_num'] = $repay_all_num;
                        $expire[$user_type][$source]['repay_money'] = $repay_all_money;
                        $expire[$user_type][$source]['repay_xj_num'] = 1;
                        $expire[$user_type][$source]['repay_xj_money'] = $fj_oid['principal'];
                        $expire[$user_type][$source]['repay_zc_num'] = $repay_zcall_num;
                        $expire[$user_type][$source]['repay_zc_money'] = $repay_zcall_money;
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_all;
                        $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_all_money;
                    }
                }
                // 累计还款复借新用户
                if ($val['customer_type']==0 || $val['is_first']==1) {
                    if ($fj_oid['order_id'] > 0) {
                        $user_type=1;
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_xj_num']++;
                            $expire[$user_type][$source]['repay_xj_money'] +=$fj_oid['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] = $user_new_num;
                            $expire[$user_type][$source]['expire_money'] = $user_new_money;
                            $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                            $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                            $expire[$user_type][$source]['repay_xj_num'] = 1;
                            $expire[$user_type][$source]['repay_xj_money'] = $fj_oid['principal'];
                            $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                            $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                            $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_new_money;
                        }
                    }
                    //累计还款复借老用户
                } else if ($val['customer_type']==1 && $val['is_first']==0) {
                    if ($fj_oid['order_id'] > 0) {
                        $user_type=2;
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_xj_num'] ++;
                            $expire[$user_type][$source]['repay_xj_money'] +=$fj_oid['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] = $user_old_num;
                            $expire[$user_type][$source]['expire_money'] = $user_old_money;
                            $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                            $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                            $expire[$user_type][$source]['repay_xj_num'] = 1;
                            $expire[$user_type][$source]['repay_xj_money'] = $fj_oid['principal'];
                            $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                            $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                            $expire[$user_type][$source]['repay_zcxj_money'] =$zc_fj_old_money;
                        }
                    }
                }
            }
            //正常新用户还款
            if (isset($expire_uid_time[$_uid]) && ($val['customer_type']==0 || $val['is_first']==1)) {
                $user_type=1;
                if(isset($expire[$user_type][$source])){
                    $expire[$user_type][$source]['expire_num'] ++;
                    $expire[$user_type][$source]['expire_money'] +=$val['principal'];
                }else{
                    $expire[$user_type][$source]['expire_num'] = 1;
                    $expire[$user_type][$source]['expire_money'] = $val['principal'];
                    $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                    $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                    $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                    $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                    $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                    $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                    $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                    $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                }
                if($val['status'] == 4){
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['repay_num'] ++;
                        $expire[$user_type][$source]['repay_money'] +=$val['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] = $user_new_num;
                        $expire[$user_type][$source]['expire_money'] = $user_new_money;
                        $expire[$user_type][$source]['repay_num'] = 1;
                        $expire[$user_type][$source]['repay_money'] = $val['principal'];
                        $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                        $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                        $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                        $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                        $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                    }
                    if($val['is_overdue']==0){
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_zc_num'] ++;
                            $expire[$user_type][$source]['repay_zc_money'] +=$val['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] = $user_new_num;
                            $expire[$user_type][$source]['expire_money'] = $user_new_money;
                            $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                            $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                            $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                            $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                            $expire[$user_type][$source]['repay_zc_num'] = 1;
                            $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                            $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                        }
                    }
                }
            }
            //正常老用户还款
            else if ( isset($expire_uid_time[$_uid]) && $val['customer_type']==1 && $val['is_first']==0) {
                $user_type=2;
                if(isset($expire[$user_type][$source])){
                    $expire[$user_type][$source]['expire_num'] ++;
                    $expire[$user_type][$source]['expire_money'] +=$val['principal'];
                }else{
                    $expire[$user_type][$source]['expire_num'] = 1;
                    $expire[$user_type][$source]['expire_money'] = $val['principal'];
                    $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                    $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                    $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                    $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                    $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                    $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                    $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                    $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                }
                if($val['status'] == 4){
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['repay_num'] ++;
                        $expire[$user_type][$source]['repay_money'] +=$val['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] = $user_old_num;
                        $expire[$user_type][$source]['expire_money'] = $user_old_money;
                        $expire[$user_type][$source]['repay_num'] = 1;
                        $expire[$user_type][$source]['repay_money'] = $val['principal'];
                        $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                        $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                        $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                        $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                        $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                    }
                    if($val['is_overdue']==0){
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_zc_num'] ++;
                            $expire[$user_type][$source]['repay_zc_money'] +=$val['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] = $user_old_num;
                            $expire[$user_type][$source]['expire_money'] = $user_old_money;
                            $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                            $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                            $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                            $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                            $expire[$user_type][$source]['repay_zc_num'] = 1;
                            $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                            $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                        }
                    }
                }
            } else {
                // 查询是新老用户
                if ($val['customer_type']==1 && $val['is_first']==0) {
                    $user_type=2;
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['expire_num'] ++;
                        $expire[$user_type][$source]['expire_money'] +=$val['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] = 1;
                        $expire[$user_type][$source]['expire_money'] = $val['principal'];
                        $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                        $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                        $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                        $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                        $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                        $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                        $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                    }
                    if($val['status'] == 4){
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_num'] ++;
                            $expire[$user_type][$source]['repay_money'] +=$val['principal'];
                        }else{
                            $expire[$user_type][$source]['expire_num'] = $user_old_num;
                            $expire[$user_type][$source]['expire_money'] = $user_old_money;
                            $expire[$user_type][$source]['repay_num'] = 1;
                            $expire[$user_type][$source]['repay_money'] = $val['principal'];
                            $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                            $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                            $expire[$user_type][$source]['repay_zc_num'] = $repay_zcold_num;
                            $expire[$user_type][$source]['repay_zc_money'] = $repay_zcold_money;
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                            $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                        }
                        if($val['is_overdue']==0){
                            if(isset($expire[$user_type][$source])){
                                $expire[$user_type][$source]['repay_zc_num'] ++;
                                $expire[$user_type][$source]['repay_zc_money'] +=$val['principal'];
                            }else{
                                $expire[$user_type][$source]['expire_num'] = $user_old_num;
                                $expire[$user_type][$source]['expire_money'] = $user_old_money;
                                $expire[$user_type][$source]['repay_num'] = $repay_old_num;
                                $expire[$user_type][$source]['repay_money'] = $repay_old_money;
                                $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_old;
                                $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_old_money;
                                $expire[$user_type][$source]['repay_zc_num'] = 1;
                                $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                                $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_old;
                                $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_old_money;
                            }
                        }
                    }

                } else {

                    $user_type=1;
                    if(isset($expire[$user_type][$source])){
                        $expire[$user_type][$source]['expire_num'] ++;
                        $expire[$user_type][$source]['expire_money'] +=$val['principal'];
                    }else{
                        $expire[$user_type][$source]['expire_num'] = 1;
                        $expire[$user_type][$source]['expire_money'] = $val['principal'];
                        $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                        $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                        $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                        $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                        $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                        $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                        $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                        $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                    }
                    if($val['status'] == 4){
                        if(isset($expire[$user_type][$source])){
                            $expire[$user_type][$source]['repay_num'] ++;
                            $expire[$user_type][$source]['expire_money'] +=$val['principal'];
                        }else{
                            $expire[$user_type][$source]['repay_num'] = 1;
                            $expire[$user_type][$source]['expire_money'] = $val['principal'];
                            $expire[$user_type][$source]['expire_num'] = $repay_new_num;
                            $expire[$user_type][$source]['expire_money'] = $repay_new_money;
                            $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                            $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                            $expire[$user_type][$source]['repay_zc_num'] = $repay_zcnew_num;
                            $expire[$user_type][$source]['repay_zc_money'] = $repay_zcnew_money;
                            $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                            $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                        }
                        if($val['is_overdue']==0){
                            if(isset($expire[$user_type][$source])){
                                $expire[$user_type][$source]['repay_zc_num'] ++;
                                $expire[$user_type][$source]['repay_zc_money'] +=$val['principal'];
                            }else{
                                $expire[$user_type][$source]['expire_num'] = $user_new_num;
                                $expire[$user_type][$source]['expire_money'] = $user_new_money;
                                $expire[$user_type][$source]['repay_num'] = $repay_new_num;
                                $expire[$user_type][$source]['repay_money'] = $repay_new_money;
                                $expire[$user_type][$source]['repay_xj_num'] = $lj_fj_new;
                                $expire[$user_type][$source]['repay_xj_money'] = $lj_fj_new_money;
                                $expire[$user_type][$source]['repay_zc_num'] = 1;
                                $expire[$user_type][$source]['repay_zc_money'] = $val['principal'];
                                $expire[$user_type][$source]['repay_zcxj_num'] = $zc_fj_new;
                                $expire[$user_type][$source]['repay_zcxj_money'] = $zc_fj_new_money;
                            }
                        }
                    }

                }
            }

        }

        foreach($expire as $key=> $app){
            foreach($app as $source=> $value){
                $data = StatisticsDayData::find()->where(['date'=>$pre_date,'user_type'=>$key,'source'=>$source])->one($save_db);
                if(!empty($data)){
                    $data->updated_at = time();
                }else{
                    $data = new StatisticsDayData();
                    $data->date=$pre_date;
                    $data->user_type=$key;
                    $data->created_at=time();
                }
                $data->source = $source;
                $data->expire_num = $value['expire_num'];
                $data->expire_money = $value['expire_money'];
                $data->repay_num = $value['repay_num'];
                $data->repay_money = $value['repay_money'];
                $data->repay_xj_num = $value['repay_xj_num'];
                $data->repay_xj_money = $value['repay_xj_money'];
                $data->repay_zc_num = $value['repay_zc_num'];
                $data->repay_zc_money = $value['repay_zc_money'];
                $data->repay_zcxj_num = $value['repay_zcxj_num'];
                $data->repay_zcxj_money = $value['repay_zcxj_money'];
                $data->zcxj_rate =empty($value['repay_zc_num'])?0:sprintf("%0.4f",$value['repay_zcxj_num']/$value['repay_zc_num']);
                $data->xj_rate =empty($value['repay_num'])?0:sprintf("%0.4f",$value['repay_xj_num']/$value['repay_num']);
                if (!$data->save()) {
                    MailHelper::send("chenlu@wzdai.com", '还款续借率',Yii::error("统计" . $pre_date . "的当天数据保存失败：" ));
                }
                unset($value);
            }
        }
    }


    public  function actionLoseDatas(){
        $end_date = '2017-06-30';
        $start_date = '2017-05-05';
        $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
        for($datei = 0;$datei<=$countDate;$datei++){
            $dateNum = strtotime($end_date)-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $data = $this->actionRepayDataDetail(strtotime($date));
            $arr[]=$data;
        }
        \Yii::$app->cache->set("RepaymentdataFujie", json_encode($arr), 3*86400);
    }
    //还款数据
    public  function actionRepayDataDetail($date){
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        Util::cliLimitChange(1024);
        $start_time = $date;
        $end_time = $date+86400;
        $t_end_time = $date+86400;
        $date =date('Y-m-d',$date);
        $db = \Yii::$app->db_kdkj_rd_new;
        //正常还款
        for($i=0;$i<=16;$i++){
            $apply_user_num=[];
            $goods=0;
            $bad=0;

            if($i==14){
                $t_end_time+=15000;
            }
            $user_id=[];
            $sql="select DISTINCT user_id,order_id,true_repayment_time,overdue_day from tb_user_loan_order_repayment where created_at >=$start_time and created_at<$end_time
                  and true_repayment_time >=$start_time+$i*86400 and true_repayment_time<$t_end_time+$i*86400 GROUP by user_id";
            if($i==15){
                $sql="select  DISTINCT user_id,order_id,true_repayment_time,overdue_day from tb_user_loan_order_repayment where created_at >=$start_time and created_at<$end_time and status=4 and is_overdue=1 GROUP by user_id;";
            }
            if($i==16){
                $sql="select  DISTINCT user_id,order_id,true_repayment_time,overdue_day from tb_user_loan_order_repayment where created_at >=$start_time and created_at<$end_time and status!=4 and is_overdue=1 GROUP by user_id;";
            }
            $repay_user = $db->createCommand($sql)->queryAll();
            foreach($repay_user as $value){
                $user_id[$value['user_id']]=$value['user_id'];
                $order_id[$value['user_id']]['user_id']=$value['user_id'];
                $order_id[$value['user_id']]['order_id']=$value['order_id'];
            }
            $user_ids = empty($user_id)?"''":implode(',',$user_id);
            $data[$date][$i]['all_num']['all_num']=count($repay_user);
            $sql_over="select DISTINCT user_id ,order_id,max(overdue_day) as overdue_day  from tb_user_loan_order_repayment where user_id
                in({$user_ids})GROUP BY user_id";
            $over_user = $db->createCommand($sql_over)->queryAll();
            foreach($over_user as $value){
                if($value['overdue_day']<10){
                    $goods++;
                }else{
                    $bad++;
                }
            }
            $data[$date][$i]['all_num']['goods']=$goods;
            $data[$date][$i]['all_num']['bad']=$bad;
            for($j=0;$j<9;$j++){
                $goods_apply=0;
                $bad_apply=0;
                $k=$j;
                if($j==7){
                    $k=15;
                }
                if($j==8){
                    $k=31;
                }
                $sqls ="select user_id,order_id,overdue_day  from tb_user_loan_order_repayment where user_id
                in({$user_ids}) and created_at>$start_time+($k+$i)*86400 and created_at<$end_time+($k+$i)*86400 ";
                $apply_user = $db->createCommand($sqls)->queryAll();
                foreach($apply_user as $ks){
                    if(!isset($apply_user_num[$ks['user_id']])){
                        if(isset($order_id[$ks['user_id']])&& $ks['order_id']>$order_id[$ks['user_id']]['order_id']){
                            $apply_user_num[$ks['user_id']]=$ks['user_id'];
                        }

                    }
                }
                $user_ido = empty($apply_user_num)?"''":implode(',',$apply_user_num);
                $data[$date][$i][$k]['all_num']=count($apply_user_num);
                $sql_over="select DISTINCT user_id ,order_id,max(overdue_day) as overdue_day  from tb_user_loan_order_repayment where user_id
                in({$user_ido})GROUP BY user_id";
                $over_users = $db->createCommand($sql_over)->queryAll();
                foreach($over_users as $value){
                    if($value['overdue_day']<10){
                        $goods_apply++;
                    }else{
                        $bad_apply++;
                    }
                }
                $data[$date][$i][$k]['goods']=$goods_apply;
                $data[$date][$i][$k]['bad']=$bad_apply;
            }
        }
        return $data;
    }

    /**
     * @name 更新日统计 --更新日统计/changeRegisgerLoadData
     **/
    public function actionChangeRegisgerLoadData($date=null){
        if($date){
            $date=strtotime(date('Y-m-d',$date));
        }else{
            $date=strtotime(date('Y-m-d',time()));
        }
        //查询今日统计数据
        $todaytime=$date;
        $sql="select aa.mydate,register,IFNULL(register_white,0) as register_white,IFNULL(loan_white,0) as loan_white,IFNULL(payment_white,0) as payment_white,IFNULL(loan,0) as loan from (
            select mydate,count(1) as register from (
             select FROM_UNIXTIME(created_at,'%Y-%m-%d') as mydate from tb_user_register_info where created_at>{$todaytime}
             ) aa GROUP BY mydate
            ) aa 
            
            left join (
            select count(1) as register_white,mydate from (
            SELECT FROM_UNIXTIME(a.created_at,'%Y-%m-%d') as mydate  FROM `tb_user_register_info` as a LEFT JOIN `tb_credit_jsqb` as b on a.`user_id` = b.`person_id`  where b.`is_white`  = 1 and a.created_at>{$todaytime}
            ) aa group by mydate
            ) bb on aa.mydate=bb.mydate 
            
            left join (
            
            select count(1) as loan_white,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o LEFT JOIN `tb_credit_jsqb` as j on o.`user_id` = j.`person_id`  where j.`is_white`  = 1 and o.`created_at`>{$todaytime} GROUP BY o.`user_id`
            ) aa GROUP BY mydate
            
            
            ) cc on aa.mydate=cc.mydate
            
            left join (
            
            select count(1) as payment_white,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o LEFT JOIN `tb_credit_jsqb` as j on o.`user_id` = j.`person_id`  where j.`is_white`  = 1 and o.`status` > 1 and o.`created_at`>{$todaytime}
            ) aa group by mydate
            
            ) dd on aa.mydate=dd.mydate 
            
            left join (
             
            select count(1) as loan,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o where o.`created_at` > {$todaytime} GROUP BY o.`user_id`
            ) aa GROUP BY mydate 
            
            ) ee on aa.mydate=ee.mydate";
        $connection = Yii::$app->db;
        $mydata = $connection->createCommand($sql)->queryAll();
        if(!empty($mydata)){
            $register=$mydata[0]['register'];
            $register_white=$mydata[0]['register_white'];
            $loan_white=$mydata[0]['loan_white'];
            $payment_white=$mydata[0]['payment_white'];
            $loan=$mydata[0]['loan'];
            //判断当日有没有导入数据
            $sql="select * from tb_daily_register_loan_data where date='".date('Y-m-d',$todaytime)."';";
            $data = $connection->createCommand($sql)->queryAll();
            $short_date=date('Y-m-d',$todaytime);
            if(!empty($data)){
                $sql="update tb_daily_register_loan_data set register={$register},register_white={$register_white},loan_white={$loan_white},payment_white={$payment_white},   ";
                $sql.="loan={$loan} where date='{$short_date}' ";
                echo "已经成功修改{$short_date}统计数据...\n";
            }else{
                $sql="insert into tb_daily_register_loan_data (`date`,register,register_white,loan_white,payment_white,loan) select ";
                $sql.=" '{$short_date}',{$register},{$register_white},{$loan_white},{$payment_white},{$loan} ";
                echo "已经成功插入{$short_date}统计数据...\n";
            }
            $connection->createCommand($sql)->query();
        }
        unset($mydata);
    }

    /**
     * @name每日借款额度统计/actionLoanMoneyList
     * @param int $type 1每日数据 2每月数据
     * @return int
     * @throws \Exception
     */
    public function  actionLoanMoneyList($type =1){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        //如果$type = 2 循环跑每月的数据
        if($type==2){
            $end_date = date("Y-m-d");
            $start_date = '2018-12-12';
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $end_time = $dateNum+86400;
                $this->getLoanData($dateNum,$end_time);
            }
        }else{
            $start_time = strtotime("today"); //今天零点
            $end_time = $start_time+86400;
            $now_time = time();
            $_hour = date('H',$now_time);//当前的小时数
            $_day = date('d',$now_time);//当前的日期
            //如果当前时间为24点，则计算前一天所有的注册量等数据,显示日期为前一天的24时
            if( $_hour == 0 ){
                $end_time = $start_time;
                $start_time = $end_time-86400;
            }
            $this->getLoanData($start_time,$end_time);
        }
    }

    private function _getResult($principal,$loan_method,$loan_term,$type,&$data){
        if(isset($data[$loan_term][$type])){
            $data[$loan_term][$type]['loan_money']+=$principal;
            $data[$loan_term][$type]['loan_num']++;
        }else{
            $data[$loan_term][$type]['loan_money']=$principal;
            $data[$loan_term][$type]['loan_num']=1;
            $data[$loan_term][$type]['loan_type']=$loan_method;
        }
        $type=0;
        if(isset($data[$loan_term][$type])){
            $data[$loan_term][$type]['loan_money']+=$principal;
            $data[$loan_term][$type]['loan_num']++;
        }else{
            $data[$loan_term][$type]['loan_money']=$principal;
            $data[$loan_term][$type]['loan_num']=1;
            $data[$loan_term][$type]['loan_type']=$loan_method;
        }
    }
    public function getLoanData($start_time,$end_time){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        echo date('Y-m-d',$start_time)."\n";
        $data =[];
        $key = date('Y-m-d',$start_time);
//        $db = \Yii::$app->db_stats;
        $read_db = \Yii::$app->db;
        $sql="select money_amount,id,loan_term,loan_method from tb_user_loan_order where id>0 and loan_time>={$start_time} and loan_time<{$end_time}";
        $loan = $read_db->createCommand($sql)->queryAll();
        foreach($loan as $value){
            $principal=$value['money_amount'];
            $loan_term=$value['loan_term'];
            $loan_method=$value['loan_method'];
            if($principal>0 && $principal<=100000){
                $type=1;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>100000 && $principal<=200000){
                $type=2;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>200000 && $principal<=300000){
                $type=3;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>300000 && $principal<=400000){
                $type=4;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>400000 && $principal<=500000){
                $type=5;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>500000 && $principal<=600000){
                $type=6;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>600000 && $principal<=700000){
                $type=7;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>700000 && $principal<=800000){
                $type=8;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
            if($principal>800000){
                $type=9;
                $this->_getResult($principal,$loan_method,$loan_term,$type,$data);
            }
        }

        foreach($data as $loan_term =>$val){
            foreach($val as $type =>$value){
                $loan_data = LoanStatistics::find()->where(["date"=>$key,"loan_type"=>$value['loan_type'],"loan_term"=>$loan_term,"type"=>$type])->one($read_db);
                if(!empty($loan_data)){
                    $loan_data->loan_money = $value['loan_money'];
                    $loan_data->loan_num = $value['loan_num'];
                    $loan_data->updated_at =time();
                }else{
                    $loan_data = new LoanStatistics();
                    $loan_data->date = $key;
                    $loan_data->loan_term = $loan_term;
                    $loan_data->type = $type;
                    $loan_data->loan_money = $value['loan_money'];
                    $loan_data->loan_num = $value['loan_num'];
                    $loan_data->loan_type = $value['loan_type'];
                    $loan_data->created_at = time();
                }
                if (!$loan_data->save()) {
                    MailHelper::send("563977434@qq.com", '借款额度统计数据保',Yii::error("借款额度" . $key . "的统计数据保存失败：" . $value));
                }
                unset($value);
            }
        }
    }

    /**
     * @name 每日借款額度統計
     * @param type int 统计的类型
     */
    public function actionDailyLoanInfo(){
        Util::cliLimitChange(1024);
        $script_lock = CommonHelper::lock();
        if (! $script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $start_time  = time();
        echo date('Y-m-d',$start_time)."\n";

        $today_start = strtotime(date('Y-m-d'.' 00:00:00',time()));
        $today_end = strtotime(date('Y-m-d'.' 23:59:59',time()));
        //全部注册 白/非名单
        $data_res = DailyRegisterAndLoanData::find()->where(['between','credit_at',$today_start,$today_end])->one();
        if(!$data_res){
            $data_res = new DailyRegisterAndLoanData();
            $data_res->credit_at = time();
        }
        $data =  new DailyDateService();
        $data_register = $data->getRegisterInfo();
        $data_res['register_all'] = $data_register['all'];
        $data_res['register_white'] = $data_register['white'];
        $data_res['register_no_white'] = $data_register['no_white'];
        //全部申请 白/非名单 新老客
        $data_loan_all = $data->getLoanOrderInfo();
        $data_res['with_new_all'] = $data_loan_all['all'];//全部申请
        $data_res['with_new_order'] = $data_loan_all['white_loan_new'];//白名单新客户申请、
        $data_res['with_old_order'] = $data_loan_all['white_loan_old'];//白名单老客户申请、
        $data_res['no_with_new_order'] = $data_loan_all['no_white_loan_new'];//非白名单新客户申请、
        $data_res['no_with_old_order'] = $data_loan_all['no_white_loan_old'];//非白名单老客户申请、
        //全部通过 白/非名单 新老客
        $data_loan_pass_all = $data->getLoanPassInfo();
        $data_res['with_new_pass_all'] = $data_loan_pass_all['all'];//全部申请
        $data_res['with_new_pass_order'] = $data_loan_pass_all['white_loan_new'];//白名单新客户申请、
        $data_res['with_old_pass_order'] = $data_loan_pass_all['white_loan_old'];//白名单老客户申请、
        $data_res['no_with_new_pass_order'] = $data_loan_pass_all['no_white_loan_new'];//非白名单新客户申请、
        $data_res['no_with_old_pass_order'] = $data_loan_pass_all['no_white_loan_old'];//非白名单老客户申请、
        //总通过率、
        $data_res['all_pass_rate'] = $data_res['with_new_all'] != 0?sprintf("%0.2f",$data_res['with_new_pass_all']/$data_res['with_new_all'])*100:0;
        //新客户总通过率、
        $data_res['all_new_pass_rate'] = $data_res['with_new_order']+$data_res['no_with_new_order'] != 0?sprintf("%0.2f",($data_res['with_new_pass_order']+$data_res['no_with_new_pass_order'])/($data_res['with_new_order']+$data_res['no_with_new_order']))*100:0;
        //白名单新客户通过率、
        $data_res['all_new_withe_pass_rate'] = $data_res['with_new_order'] != 0?sprintf("%0.2f",($data_res['with_new_pass_order'])/$data_res['with_new_order'])*100:0;
        //非白名单新客户通过率、
        $data_res['all_new_no_withe_pass_rate'] = $data_res['no_with_new_order'] != 0?sprintf("%0.2f",($data_res['no_with_new_pass_order'])/$data_res['no_with_new_order'])*100:0;

        //老客户总通过率、
        $data_res['all_old_pass_rate'] = $data_res['with_old_order']+$data_res['no_with_old_order'] != 0?sprintf("%0.2f",($data_res['with_old_pass_order']+$data_res['no_with_old_pass_order'])/($data_res['with_old_order']+$data_res['no_with_old_order']))*100:0;
        //白名单老客户通过率、
        $data_res['all_old_withe_pass_rate'] = $data_res['with_old_order'] != 0?sprintf("%0.2f",($data_res['with_old_pass_order'])/$data_res['with_old_order'])*100:0;
        //非白名单老客户通过率。
        $data_res['all_old_no_withe_pass_rate'] = $data_res['no_with_old_order'] != 0?sprintf("%0.2f",($data_res['no_with_old_pass_order'])/$data_res['no_with_old_order'])*100:0;
        if(!$data_res->save()){
            MailHelper::send("563977434@qq.com", '每日借款額度統計保存失败');
            echo "每日借款額度統計保存失败\n";
        }
        echo "每日借款額度統計保存成功\n";
    }
}
