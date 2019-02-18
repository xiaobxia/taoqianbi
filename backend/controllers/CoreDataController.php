<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/9
 * Time: 14:21
 */

namespace backend\controllers;

use common\api\RedisQueue;
use common\helpers\ArrayHelper;
use common\models\AppKeyData;
use common\models\CreditJxlQueue;
use common\models\CreditMgLog;
use common\models\CreditTdLog;
use common\models\DailyCodeLog;
use common\models\LoanOrderSource;
use common\models\LoanPersonBadInfo;
use common\models\LoanPersonChannelRecord;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeMap;
use common\models\StatisticsLoan;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use common\models\UserCountArr;
use Yii;
use common\helpers\Util;
use common\helpers\Url;
use yii\web\Response;
use common\models\UserLoanOrderRepayment;
use common\models\LoanPerson;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;
use common\models\StatisticsVerification;
use common\models\StatisticsRegisterData;
use common\models\StatisticsDayLose;
use common\models\StatisticsDayLoseRate;
use common\models\StatisticsDayData;
use common\models\RepayRatesList;
use common\models\StatisticsMonthData;
use common\models\UserDetail;
use common\models\DailyTrade;
use common\models\fund\LoanFund;
use common\models\loan\StatisticsByMoney;
use common\models\loan\OrderStatisticsByDay;

class CoreDataController extends BaseController {


    /**
     * @name 数据分析-财务数据-每日借款数据/actionDailyData
     */
    public function actionDailyData() {
        ini_set('memory_limit', '1024M');

        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $sub_order_type = $this->request->get('sub_order_type');
        $channel = $this->request->get('channel');
        $search_date = $this->request->get('search_date');
        $source_type = $this->request->get('source_type');

        $condition = ' 1= 1 ';
        if (isset($sub_order_type) && $sub_order_type != -1) {
            $condition .= " AND sub_order_type = " . $sub_order_type;
        }
        if ($source_type) {
            $condition .= " AND app_type = {$source_type} ";
        } else {
            $condition .= " AND app_type = 0";
        }
        if ($add_start) {//开始日期
            $add_start = strtotime($add_start);
        } else {
            $add_start = strtotime(date('Y-m-d', time() - 30 * 24 * 3600));
        }
        if ($add_end) {//结束日期
            $add_end = strtotime($add_end) + 24 * 3600;
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 24 * 3600;
        }
        if($search_date == 2){//1.借款日期；2.还款日期
            $add_start -= 14*86400;
            $add_end -= 14*86400;
        }
        $condition .= ' and date_time>=' . $add_start;
        $condition .= ' and date_time<' . $add_end;
        $query = StatisticsLoan::findBySql("SELECT  date_time, SUM(loan_num) AS loan_num,SUM(loan_num_7) AS loan_num_7,SUM(loan_num_14) AS loan_num_14,
SUM(loan_num_old) AS loan_num_old,SUM(loan_num_new) AS loan_num_new,SUM(loan_money) AS loan_money,SUM(loan_money_7) as loan_money_7,SUM(loan_money_14) AS loan_money_14,
SUM(loan_money_old) AS loan_money_old,SUM(loan_money_new) AS loan_money_new
FROM tb_statistics_loan_copy where " . $condition . "  GROUP BY date_time ORDER BY date_time DESC ");
        $countQuery = clone $query;
        $totalCount = 0;
        $queryCount = $countQuery->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($queryCount as $value) {
            if (!empty($value)) {
                $totalCount++;
            }
        }

        //获取脚本最后更新时间
        $last_update_query = StatisticsLoan::findBySql("SELECT updated_at FROM tb_statistics_loan_copy ORDER BY updated_at DESC LIMIT 1")->one(Yii::$app->get('db_kdkj_rd'));
        $last_update_at = $last_update_query['updated_at'];

//        $pages = new Pagination(['totalCount' => $totalCount]);
//        var_dump($pages);
//        $pages->pageSize = 15;
        $info = $query
//            ->offset($pages->offset)->limit($pages->pageSize)
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
//        $total = $countQuery->count('*',Yii::$app->get('db_kdkj_rd'));

        $total_loan_num = 0;
        $total_loan_money = 0;
        $total_loan_num_new = 0;
        $total_loan_num_old = 0;
        $total_loan_money_new = 0;
        $total_loan_money_old = 0;

        $ret = $query->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($ret as $item) {
            $total_loan_num = $total_loan_num + $item['loan_num'];
            //$total_loan_money = $total_loan_money + $item['loan_money'];
//            $total_loan_money += $item['loan_money_14'];
            $total_loan_money += $item['loan_money'];
            $total_loan_num_new += $item['loan_num_new'];
            $total_loan_num_old += $item['loan_num_old'];
            $total_loan_money_new += $item['loan_money_new'];
            $total_loan_money_old += $item['loan_money_old'];
        }

        /* 目前没有续期，暂时注释掉
         * $delay = UserLoanOrderDelay::findBySql("SELECT COUNT(user_id) AS user_amount, user_id, FROM_UNIXTIME(created_at,'%Y-%m-%d')  AS DATE FROM tb_user_loan_order_delay GROUP BY FROM_UNIXTIME(created_at,'%Y-%m-%d') ORDER BY FROM_UNIXTIME(created_at,'%Y-%m-%d') DESC")->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($info as $key => $value) {
            foreach ($delay as $item) {
                if (date('Y-m-d', $value['date_time']) == $item['DATE']) {
                    $info[$key]['loan_num'] = $value['loan_num'] + $item['user_amount'];
                }
            }
        }*/
        //导出数据
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDailyData($info);
        }
        $app_source = [
            0 => '全部类型',
            LoanPerson::PERSON_SOURCE_MOBILE_CREDIT => APP_NAMES,
            LoanPerson::PERSON_SOURCE_KDJZ => '口袋记账',
            LoanPerson::PERSON_SOURCE_JBGJ => '记账管家',
            LoanPerson::PERSON_SOURCE_HBJB => '汇邦钱包',
            LoanPerson::PERSON_SOURCE_WZD_LOAN => '温州贷借款',
            LoanPerson::PERSON_SOURCE_SX_LOAN => '随心贷',
            LoanPerson::PERSON_SOURCE_HUAN_KA_LOAN => '还卡锦囊'
        ];
        return $this->render('daily-data', array(
            'data' => $info,
//                    'pages' => $pages,
            'total_loan_num' => $total_loan_num,
            'total_loan_money' => $total_loan_money,
            'total_loan_num_new' => $total_loan_num_new,
            'total_loan_num_old' => $total_loan_num_old,
            'total_loan_money_new' => $total_loan_money_new,
            'total_loan_money_old' => $total_loan_money_old,
            'channel' => $channel,
            'app_source' => $app_source,
            'sub_order_type' => $sub_order_type,
            'last_update_at' => $last_update_at,
        ));
    }


    /**
     * @name 数据分析-财务数据-每日公积金借款数据/actionDailyDataGjj
     */
    public function actionDailyDataGjj() {
        ini_set('memory_limit', '1024M');
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $sub_order_type = $this->request->get('sub_order_type');
        $search_date = $this->request->get('search_date');
        $channel = $this->request->get('channel');
        $condition = '1 = 1';
        if (isset($sub_order_type) && $sub_order_type != -1) {
            $condition .= " AND sub_order_type = " . $sub_order_type;
        }
        if ($add_start) {//开始日期
            $add_start = strtotime($add_start);
        } else {
            $add_start = strtotime(date('Y-m-d', time() - 30 * 24 * 3600));
        }
        if ($add_end) {//结束日期
            $add_end = strtotime($add_end) + 24 * 3600;
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 24 * 3600;
        }
        if($search_date == 2){//1.借款日期；2.还款日期
            $add_start -= 14*86400;
            $add_end -= 14*86400;
        }
        $condition .= ' and date_time>=' . $add_start;
        $condition .= ' and date_time<' . $add_end;
        $query = StatisticsLoan::findBySql("SELECT  date_time,
SUM(gjj_num_14) AS gjj_num_14,SUM(gjj_num_old_14) AS gjj_num_old_14,SUM(gjj_num_new_14) AS gjj_num_new_14,
SUM(gjj_money_14) AS gjj_money_14,SUM(gjj_money_old_14) AS gjj_money_old_14,SUM(gjj_money_new_14) AS gjj_money_new_14
FROM tb_statistics_loan_copy where " . $condition . "  GROUP BY date_time ORDER BY date_time DESC ");
        $countQuery = clone $query;
        $totalCount = 0;
        $queryCount = $countQuery->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($queryCount as $value) {
            if (!empty($value)) {
                $totalCount++;
            }
        }

        //获取脚本最后更新时间
        $last_update_query = StatisticsLoan::findBySql("SELECT updated_at FROM tb_statistics_loan_copy ORDER BY updated_at DESC LIMIT 1")->one(Yii::$app->get('db_kdkj_rd'));
        $last_update_at = $last_update_query['updated_at'];

        $info = $query->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        $total_loan_num = 0;
        $total_loan_money = 0;
        $total_loan_num_new = 0;
        $total_loan_num_old = 0;
        $total_loan_money_new = 0;
        $total_loan_money_old = 0;

        $ret = $query->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($ret as $item) {
            $total_loan_num = $total_loan_num + $item['gjj_num_14'];
            $total_loan_money = $total_loan_money + $item['gjj_money_14'];
            $total_loan_num_new += $item['gjj_num_new_14'];
            $total_loan_num_old += $item['gjj_num_old_14'];
            $total_loan_money_new += $item['gjj_money_new_14'];
            $total_loan_money_old += $item['gjj_money_old_14'];
        }

        $return_info = [];
        if(!empty($info)){
            foreach ($info as $k => $v){
                $return_info[$v['date_time']] = $v;
            }
        }

        //借款历史数据
        $history_time = strtotime(date("2017-06-28"));
        $history_data = [];
        if($add_start < $history_time) {
            $history_data = $this->_getHistoryGjjStat($add_start, $add_end);
        }
        if(!empty($history_data)){
            foreach ($history_data as $k => $v){
                if(empty($return_info[$v['statistics_at']]['gjj_num_14'])){
                    $return_info[$v['statistics_at']] = array(
                        'date_time' => $v['statistics_at'],
                        'gjj_num_14' => $v['gjj_num'],
                        'gjj_num_old_14' => $v['gjj_num_old'],
                        'gjj_num_new_14' => $v['gjj_num_new'],
                        'gjj_money_14' => $v['gjj_fk_amount'],
                        'gjj_money_old_14' => $v['gjj_fk_amount_old'],
                        'gjj_money_new_14' => $v['gjj_fk_amount_new'],
                    );
                    $total_loan_num += $v['gjj_num'];
                    $total_loan_money += $v['gjj_fk_amount'];
                }
            }
        }
        return $this->render('daily-data-gjj', array(
            'data' => $return_info,
            'channel' => $channel,
            'total_loan_num' => $total_loan_num,
            'total_loan_money' => $total_loan_money,
            'total_loan_num_new' => $total_loan_num_new,
            'total_loan_num_old' => $total_loan_num_old,
            'total_loan_money_new' => $total_loan_money_new,
            'total_loan_money_old' => $total_loan_money_old,
            'sub_order_type' => $sub_order_type,
            'last_update_at' => $last_update_at,
        ));
    }

    private function _exportDailyData($datas){
        $this->_setcsvHeader('放款数据.csv');
        foreach($datas as $value){
            $items[] = [
                '日期'=>date("Y-m-d",$value['date_time']),
                '放款单数' =>$value['loan_num'],
                '放款总额' =>sprintf("%0.2f",$value['loan_money_14']/100),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @name 数据分析-财务数据-每日放款单数/actionLoanNumView
     */
    public function actionLoanNumView(){
        $condition = $this->getLoanNumFilter();
        if ($this->getRequest()->getIsGet()) {
            $begintime = $this->request->get('time');
            $customer_type = $this->request->get('customer_type');
            $loan_term = $this->request->get('loan_term');
            $endtime = $begintime + 86400;
            if (!empty($begintime) && !empty($endtime)) {
                $condition .= " and r.`created_at` >= $begintime and r.`created_at` <= $endtime";
            } else {
                $condition .= " and 1=1";
            }
            if (isset($customer_type)) {
                $condition .= " AND b.customer_type = " . $customer_type;
            }
            if (isset($loan_term)) {
                $condition .= " AND a.loan_term = " . $loan_term;
            }
        }

        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')
            ->leftJoin(UserLoanOrderRepayment::tableName().' as r','a.id = r.order_id')
            ->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')
            ->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')
            ->leftJoin(LoanFund::tableName().' as d', 'a.fund_id=d.id')
            ->where($condition)
            ->select('a.*,b.name,b.id_number,b.phone,b.customer_type,c.company_name,d.name as fund_name')
            ->orderBy(['r.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $new_arr = [];
        foreach ($info as $key => $value) {
            $new_arr[] = $value;
        }
        return $this->render('loan-num-view', array(
            'status_data'=> UserLoanOrder::$status,
            'data_list' => $new_arr,
            'pages' => $pages,
        ));
    }

    /**
     * 放款列表过滤
     * @return string
     */
    protected function getLoanNumFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND r.user_id = " . intval($search['id']);
            }
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND r.order_id = " . intval($search['order_id']);
            }

            if (isset($search['name']) && !empty($search['name'])) {
                $find = LoanPerson::find()->where(["like", "name", $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if (!empty($find)) {
                    $condition .= " AND r.user_id = " . $find['id'];
                }
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $find = LoanPerson::find()->where(["phone" => trim($search['phone'])])->one(Yii::$app->get('db_kdkj_rd'));
                if (!empty($find)) {
                    $condition .= " AND r.user_id = " . $find['id'];
                }
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND r.created_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND r.created_at <= " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }
    /**
     * @name 数据分析->数据管理->每日放款/actionDailyData1
     */
    public function actionDailyData1() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        if (!$add_start && !$add_end) {
            $add_end = date('Y-m-d');
            $add_start = date('Y-m-d', time() - 86400 * 2);
        }
        $where = ' 1=1 ';
        if ($add_start) {
            $where = $where . ' and f.created_at >= ' . strtotime($add_start);
        }
        if ($add_end) {
            $where = $where . ' and f.created_at <= ' . (strtotime($add_end) + 86400);
        }

        $sql = "SELECT date(from_unixtime(f.created_at)) as date_time,count(*) as loan_num,sum(if(o.loan_term=7,1,0)) as loan_num_7,sum(if(o.loan_term=7,0,1)) as loan_num_14,sum(if(f.money > 200000,200000,f.money)) as loan_money
                ,sum(if(o.loan_term=7,if(f.money > 200000,200000,f.money),0)) as loan_money_7,sum(if(o.loan_term=7,0,if(f.money > 200000,200000,f.money))) as loan_money_14
                FROM kdkj.tb_financial_loan_record f left join kdkj.tb_user_loan_order o on f.business_id = o.id where {$where} and f.created_at>unix_timestamp('2016-09-19') group by date(from_unixtime(f.created_at))
                ORDER BY date_time DESC ";
        $info = Yii::$app->db_kdkj_rd->createCommand($sql)->queryAll();
        $total_loan_num = 0;
        $total_loan_money = 0;
        foreach ($info as $i) {
            $total_loan_num += $i['loan_num'];
            $total_loan_money += $i['loan_money'];
        }
        return $this->render('daily-data1', array(
            'data' => $info,
            'total_loan_num' => $total_loan_num,
            'total_loan_money' => $total_loan_money,
        ));
    }

    /**
     * @name 每日放款数据 -渠道分销/actionDailyDataChannel
     */
    public function actionDailyDataChannel() {
        $channel = Yii::$app->params['DistributionChannel'];
        $admin_user = Yii::$app->user->identity->username;
        foreach ($channel as $value) {
            foreach ($value['username'] as $item) {
                $admin_name[] = $item;
            }
            if (in_array($admin_user, $value['username'])) {
                $_GET['sub_order_type'] = $value['sub_order_type'];
            }
        }
        if (!in_array($admin_user, $admin_name)) {
            return $this->redirectMessage('请配置渠道', self::MSG_ERROR);
        }
        $_GET['channel'] = 1;
        return $this->actionDailyData();
    }


    /**
     * @name 数据分析-财务数据-每日公积金还款单数数据/actionDailyLoanDataGjj
     */
    public function actionDailyLoanDataGjj() {
        ini_set('memory_limit', '1024M');
        $all_loan_money = 0;          //到期总金额
        $all_repayment_money = 0;     //还款总金额
        $all_overdue_money = 0;       //逾期总金额
        $all_zc_money = 0;            //正常还款金额

        $old_loan_money = 0;          //老用户到期金额
        $old_repayment_money = 0;     //老用户还款金额
        $old_overdue_money = 0;       //老用户逾期金额
        $all_zc_money_old = 0;        //老用户正常还款金额

        $new_loan_money = 0;          //新用户到期金额
        $new_repayment_money = 0;     //新用户还款金额
        $new_overdue_money = 0;       //新用户逾期金额
        $all_zc_money_new = 0;        //新用户正常还款金额

        $all_loan_num = 0;
        $all_zc_num = 0;
        $all_zc_num_old = 0;
        $all_zc_num_new = 0;
        $all_repayment_num = 0;
        $all_overdue_num = 0;
        $old_loan_num = 0;
        $old_repayment_num = 0;
        $old_overdue_num = 0;
        $new_loan_num = 0;
        $new_repayment_num = 0;
        $new_overdue_num = 0;
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $sub_order_type = $this->request->get('sub_order_type', '-1');
        $channel = $this->request->get('channel');
        $search_date = $this->request->get('search_date');

        $pass_type_gjj = UserLoanOrder::PASS_TYPE_GJJ;
        $pass_type_gjj_old = UserLoanOrder::PASS_TYPE_GJJ_OLD;
        $where = " (l.pass_type= {$pass_type_gjj} OR l.pass_type = {$pass_type_gjj_old}) ";
        if ($add_start) {
            $add_start = strtotime($add_start);
        } else {
            $add_start = strtotime(date('Y-m-d', time() - 7 * 24 * 3600));
        }
        if ($add_end) {
            $add_end = strtotime($add_end) + 24 * 3600;
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 24 * 3600;
        }
        if($search_date == 2){//1.放款日期；2.还款日期
            $where .= ' and r.plan_fee_time>=' . $add_start;
            $where .= ' and r.plan_fee_time<' . $add_end;
        }else{
            $where .= ' and r.created_at>=' . $add_start;
            $where .= ' and r.created_at<' . $add_end;
        }

        if ($sub_order_type != UserLoanOrder::SUB_TYPE_ALL) {
            $where .= ' and l.sub_order_type = ' . intval($sub_order_type);
        }

        $data = [];
        $user_loan_order_repayment = UserLoanOrderRepayment::find()->from(UserLoanOrderRepayment::tableName() . 'as r')
            ->leftJoin(UserLoanOrder::tableName() . 'as l', 'r.order_id=l.id')->where($where)
            ->select('r.plan_fee_time,r.true_repayment_time,r.created_at,r.status,r.is_overdue,r.overdue_day,r.principal,l.is_first,l.loan_method,l.loan_term,l.pass_type')
            ->orderBy(['r.plan_fee_time' => SORT_DESC])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $overdue_order_num = 0;
        $overdue_zc_order_num = 0;
        $overdue_order_num_old = 0;
        $overdue_order_num_new = 0;
        $overdue_order_zc_old = 0;
        $overdue_order_zc_new = 0;
        foreach ($user_loan_order_repayment as $item) {
            $is_first = $item['is_first'];
            if ($is_first) {
                $old = 0;
            } else {
                $old = 1;
            }
            $time = date('n-j', $item['plan_fee_time']);
            $time_key = date('Y-m-d', $item['plan_fee_time']);
            $unix_time_key = strtotime($time_key);
            $create_time = date('n-j', $item['created_at']);
            if (!isset($data[$time])) {
                $data[$time] = [
                    'unix_time_key' => $unix_time_key,
                    'time_key' => $time_key,
                    'success_num' => 0,
                    'success_num_old' => 0,
                    'success_num_new' => 0,
                    'dc_num' => 0,
                    'dc_num_old' => 0,
                    'dc_num_new' => 0,
                    'dn_num' => 0,
                    'dn_num_old' => 0,
                    'dn_num_new' => 0,
                    'da_num' => 0,
                    'da_num_old' => 0,
                    'da_num_new' => 0,
                    'repay_num' => 0,
                    'repay_num_old' => 0,
                    'repay_num_new' => 0,
                    'zc_num' => 0,
                    'zc_num_old' => 0,
                    'zc_num_new' => 0,
                    'create_time' => date('Y-m-d'),

                    //金额
                    'success_money' => 0,
                    'success_money_old' => 0,
                    'success_money_new' => 0,
                    'dc_money' => 0,
                    'dc_money_old' => 0,
                    'dc_money_new' => 0,
                    'repay_money' => 0,
                    'repay_money_old' => 0,
                    'repay_money_new' => 0,
                    'zc_money' => 0,
                    'zc_money_new' => 0,
                    'zc_money_old' => 0,
                    'dn_money' => 0,
                    'dn_money_old' => 0,
                    'dn_money_new' => 0,
                ];
            }

            $data[$time]['time_key'] = $time_key;
            $data[$time]['unix_time_key'] = $unix_time_key;
            $data[$time]['create_time'] = $create_time;
            $data[$time]['success_num'] ++;//每日的到期笔数
            $all_loan_num++;//所有的到期单数
            $data[$time]['success_money'] += $item['principal'] / 100;//每日到期金额
            $all_loan_money += $item['principal'] / 100;//总的到期金额
            if ($old) {
                $data[$time]['success_num_old'] ++;  //老用户的到期单数
                $old_loan_num++;

                $data[$time]['success_money_old'] += $item['principal'] / 100;//当日老用户到期金额
                $old_loan_money += $item['principal'] / 100;//老用户到期金额
            } else {
                $data[$time]['success_num_new'] ++;    //新用户的到期单数
                $new_loan_num++;

                $data[$time]['success_money_new'] += $item['principal'] / 100;//当日新用户到期金额
                $new_loan_money += $item['principal'] / 100;//新用户到期金额
            }
            if ($item['is_overdue'] && $item['overdue_day'] > 0 && $item['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {   //逾期单数
                $data[$time]['dn_num'] ++;        //每天的到期单数中逾期的单数
                $all_overdue_num++;                //所有的到期单数中逾期的单数
                $all_overdue_money += $item['principal'] / 100;//逾期总金额
                if ($old) {
                    $data[$time]['dn_num_old'] ++;  //每天的到期单数中 老用户 逾期的单数
                    $old_overdue_num++;                //所有的到期单数中 老用户 逾期的单数
                    $old_overdue_money += $item['principal'] / 100;//老用户逾期总金额
                } else {
                    $data[$time]['dn_num_new'] ++;     //每天的到期单数中 新用户 逾期的单数
                    $new_overdue_num++;                 //所有的到期单数中 新用户 逾期的单数
                    $new_overdue_money += $item['principal'] / 100;//新用户逾期总金额
                }
            }
            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {     //到期单数中已还款的单数
                $data[$time]['repay_num'] ++;         //每天的到期单数中，已经还款的单数
                $all_repayment_num++;                  //总的到期单数中，已经还款的单数

                $data[$time]['repay_money'] += $item['principal'] / 100;//每日已还款总金额
                $all_repayment_money += $item['principal'] / 100;//还款总金额
                if ($old) {
                    $data[$time]['repay_num_old'] ++;  //每天的到期单数中，老用户的 已经还款的单数
                    $old_repayment_num++;               //总的到期单数中，老用户 已经还款的单数
                    $old_repayment_money += $item['principal'] / 100;//老用户还款总金额
                } else {
                    $data[$time]['repay_num_new'] ++;   //每天的到期单数中，新用户的 已经还款的单数
                    $new_repayment_num++;               //每天的到期单数中，新用户的 已经还款的单数
                    $new_repayment_money += $item['principal'] / 100;//新用户还款总金额
                }
                if(isset($item['is_overdue']) && $item['is_overdue'] == 0){//已还款 && 未逾期
                    if($old){
                        $all_zc_num_old ++;
                        $data[$time]['zc_num_old'] ++;

                        $all_zc_money_old +=$item['principal'] / 100;//老用户正常还款总金额
                        $data[$time]['zc_money_old'] += $item['principal'] / 100;//当日老用户正常还款总金额
                    }else{
                        $all_zc_num_new ++;
                        $data[$time]['zc_num_new'] ++;

                        $all_zc_money_new +=$item['principal'] / 100;//新用户正常还款总金额
                        $data[$time]['zc_money_new'] +=$item['principal'] / 100;//当日新用户正常还款总金额
                    }
                    $all_zc_num++;
                    $data[$time]['zc_num'] ++;//当日正常还款单数

                    $all_zc_money+=$item['principal'] / 100;//正常还款总金额
                    $data[$time]['zc_money'] += $item['principal'] / 100;//当日正常还款金额
                }
            }

            //按单数分
            if (!empty($data[$time]['success_num'])) {
                $data[$time]['repay_rate'] = empty($data[$time]['success_num']) ? '0' : sprintf("%0.2f", $data[$time]['repay_num'] / $data[$time]['success_num'] * 100) . "%";
                $data[$time]['repay_rate_old'] = empty($data[$time]['success_num_old']) ? '0' : sprintf("%0.2f", $data[$time]['repay_num_old'] / $data[$time]['success_num_old'] * 100) . "%";
                $data[$time]['repay_rate_new'] = empty($data[$time]['success_num_new']) ? '0' : sprintf("%0.2f", $data[$time]['repay_num_new'] / $data[$time]['success_num_new'] * 100) . "%";

                $num = 0;
                $num_old = 0;
                $num_new = 0;
                $num = $data[$time]['dn_num'];
                if ($data[$time]['dn_num_old'] > 0) {
                    $num_old = $data[$time]['dn_num_old'];
                }
                if ($data[$time]['dn_num_new'] > 0) {
                    $num_new = $data[$time]['dn_num_new'];
                }

                $data[$time]['dc_num'] = $num;
                $data[$time]['dc_num_old'] = $num_old;
                $data[$time]['dc_num_new'] = $num_new;
                $data[$time]['conversion_rate'] = empty($data[$time]['success_num']) ? '0%' : sprintf("%0.2f", $num / $data[$time]['success_num'] * 100) . "%";
                $data[$time]['conversion_rate_old'] = empty($data[$time]['success_num_old']) ? '0%' : sprintf("%0.2f", $num_old / $data[$time]['success_num_old'] * 100) . "%";
                $data[$time]['conversion_rate_new'] = empty($data[$time]['success_num_new']) ? '0%' : sprintf("%0.2f", $num_new / $data[$time]['success_num_new'] * 100) . "%";
            } else {
                $data[$time]['conversion_rate'] = "-%";
                $data[$time]['conversion_rate_old'] = "-%";
                $data[$time]['conversion_rate_new'] = "-%";
                $data[$time]['dc_num'] = "0";
                $data[$time]['dc_num_old'] = "0";
                $data[$time]['dc_num_new'] = "0";
            }
            //按金额分
            if (!empty($data[$time]['success_money'])) {
                $money = 0;
                $money_old = 0;
                $money_new = 0;
                if ($data[$time]['dn_money'] > 0) {
                    $money = $data[$time]['dn_money'];
                    if ($data[$time]['dn_money_old'] > 0) {
                        $money_old = $data[$time]['dn_money_old'];
                    }
                    if ($data[$time]['dn_money_new'] > 0) {
                        $money_new = $data[$time]['dn_money_new'];
                    }
                }

                if ($data[$time]['repay_money'] && $money) {
                    $money = $data[$time]['success_money'] - $data[$time]['repay_money'];
                }
                $data[$time]['dc_money'] = $money;
                $data[$time]['dc_money_old'] = $money_old;
                $data[$time]['dc_money_new'] = $money_new;

            } else {
                $data[$time]['dc_money'] = "0";
                $data[$time]['dc_money_old'] = "0";
                $data[$time]['dc_money_new'] = "0";
            }
            // 用于计算逾期的到期单数
            if ($item['plan_fee_time'] <= strtotime(date('Y-m-d', time()))) {
                $overdue_order_num ++;
                if(isset($item['is_overdue']) && $item['is_overdue'] == 0) {//已还款 && 未逾期
                    $overdue_zc_order_num ++;
                    if ($old) {
                        $overdue_order_zc_old ++;
                    } else {
                        $overdue_order_zc_new ++;
                    }
                }
                if ($old) {
                    $overdue_order_num_old ++;
                } else {
                    $overdue_order_num_new ++;
                }
            }
        }

        $total_data = [
            'all_loan_num' => $all_loan_num,
            'all_overdue_num' => $all_overdue_num,
            'all_repayment_num' => $all_repayment_num,
            'all_zc_num' => $all_zc_num,
            'all_overdue_rate' => empty($overdue_order_num) ? "0%" : sprintf("%0.2f", ($all_overdue_num) / $overdue_order_num * 100) . "%",
            'all_repayment_rate' => empty($all_loan_num) ? "0%" : sprintf("%0.2f", $all_repayment_num / $all_loan_num * 100) . "%",
            'all_rc_rate' => empty($all_zc_num) ? "0%" : sprintf("%0.2f", (($overdue_order_num - $overdue_zc_order_num) / $overdue_order_num) * 100) . "%",
            'all_rc_rate_old' => empty($all_zc_num_old) ? "0%" : sprintf("%0.2f", (($overdue_order_num_old - $overdue_order_zc_old) / $overdue_order_num_old) * 100) . "%",
            'all_rc_rate_new' => empty($all_zc_num_new) ? "0%" : sprintf("%0.2f", (($overdue_order_num_new - $overdue_order_zc_new) / $overdue_order_num_new) * 100) . "%",
            'old_loan_num' => $old_loan_num,
            'old_overdue_num' => $old_overdue_num,
            'old_overdue_rate' => empty($old_loan_num) ? "0%" : sprintf("%0.2f", $old_overdue_num / $overdue_order_num_old * 100) . "%",
            'old_repayment_rate' => empty($old_loan_num) ? "0%" : sprintf("%0.2f", $old_repayment_num / $old_loan_num * 100) . "%",
            'new_loan_num' => $new_loan_num,
            'new_overdue_num' => $new_overdue_num,
            'new_overdue_rate' => empty($new_loan_num) ? "0%" : sprintf("%0.2f", $new_overdue_num / $overdue_order_num_new * 100) . "%",
            'new_repayment_rate' => empty($new_loan_num) ? "0%" : sprintf("%0.2f", $new_repayment_num / $new_loan_num * 100) . "%",

            //金额
            'all_loan_money' => $all_loan_money,
            'all_zc_money' => $all_zc_money,
            'all_repayment_money' => $all_repayment_money,
            'new_loan_money' => $new_loan_money,
            'old_loan_money' => $old_loan_money,
        ];

        return $this->render( "daily-loan-data-gjj", [
            'data' => $data,
            'total_data' => $total_data,
            'sub_order_type' => $sub_order_type,
            'channel' => $channel,
            'add_start' => $add_start,
            'add_end' => $add_end,
        ]);
    }



    /**
     * @name 数据分析 -小钱包数据管理-每日新用户数据/actionMarketData
     */
    public function actionMarketData() {
        $condition = ' 1=1 AND source = -1 ';
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        if (!empty($add_start)) {
            $condition = $condition . " and date>='" . $add_start . "'";
        }else{
            $add_start = date('Y-m-d', time() - 5*86400);
            $condition = $condition . " and date>='" . $add_start . "'";
        }

        if (!empty($add_end)) {
            $condition = $condition . " and date<='" . $add_end . "'";
        }else{
            $add_end = date('Y-m-d');
            $condition = $condition . " and date<='" . $add_end . "'";
        }
        $info = StatisticsVerification::find($condition)->where($condition)->orderBy('date DESC')->asArray()->all(Yii::$app->get('db_kdkj_rd'));

//        $countQuery = clone $query;
//        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
//        $pages->pageSize = 10;
//        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render("market-data", [
            'info' => $info,
//            'pages'=>$pages
        ]);


    }
    public function actionMarketData2() {
        $choose_market = $this->request->get('markets_data');
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $source_id = $this->request->get('souce_key');

        $where = ' 1= 1 ';
        if ($choose_market) {
            $where = $where . ' and appMarket='."'".$choose_market."'".'';
        }
        if ($source_id) {
            $where = $where . ' and source =' . $source_id . '';
        }
        if (!empty($add_start)) {
            $where = $where . " and date>='" . $add_start . "'";
        }else{
            $add_start = date('Y-m-d', time() - 5*86400);
            $where = $where . " and date>='" . $add_start . "'";
        }

        if (!empty($add_end)) {
            $where = $where . " and date<='" . $add_end . "'";
        }else{
            $add_end = date('Y-m-d');
            $where = $where . " and date<='" . $add_end . "'";
        }
        $is_cache = true;
        $key = 'Statistics:CoreData:MarketData:Var';
        $where2 = \Yii::$app->cache->get($key . '3', $where, 300);
        if (strcmp($where, $where2) == 0) {
            $is_cache = true;
        } else {
            $is_cache = false;
            \Yii::$app->cache->set($key . '3', $where, 300);
        }

        if ($is_cache) {
            $data = \Yii::$app->cache->get($key . '1');
            $markets_data = \Yii::$app->cache->get($key . '2');
            if ($data && $markets_data) {
                goto end;
            }
        }

        $market_sql = 'SELECT DISTINCT(`appMarket`) AS market FROM `tb_user_register_info`';

        $markets = yii::$app->db_kdkj_rd->createCommand($market_sql)->queryAll();

        $markets = ArrayHelper::getColumn($markets, 'market');
        sort($markets);
        $markets_data = [];
        foreach ($markets as $market) {
            $markets_data[$market] = $market;

        }
        $date_user = [];
        $user_register_info = UserRegisterInfo::find()->where($where)->select(['user_id', 'date', 'source', 'appMarket'])->orderBy(" id desc")->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($user_register_info as $item) {
            $date_user[$item['date']][] = $item['user_id'];
        }
        $data = [];
        foreach ($date_user as $date => $users) {
            $data[$date]['reg_num'] = count($users);
            $credit_num = UserCreditTotal::find()->where(['user_id' => $users, 'amount' => 0])->count("id", Yii::$app->get('db_kdkj_rd'));
            if (isset($data[$date]['credit_num'])) {
                $data[$date]['credit_num'] = $data[$date]['credit_num'] + $credit_num;
            } else {
                $data[$date]['credit_num'] = $credit_num;
            }

            $user_verification = UserVerification::find()->where(['user_id' => $users])->select('sum(real_jxl_status) as yys_num,sum(real_alipay_status) as ali_num,sum(real_zmxy_status) as zm_num,sum(real_contact_status) as contact_num,sum(real_work_status) as work_num,sum(real_verify_status) as realname_num ,sum(real_bind_bank_card_status) as bindcard_num')->asArray()->one(Yii::$app->get('db_kdkj_rd'));

            $realname_num = $user_verification ? $user_verification['realname_num'] : 0;
            $bindcard_num = $user_verification ? $user_verification['bindcard_num'] : 0;

            $yys_num = $user_verification ? $user_verification['yys_num'] : 0;
            $ali_num = $user_verification ? $user_verification['ali_num'] : 0;
            $zm_num = $user_verification ? $user_verification['zm_num'] : 0;
            $contact_num = $user_verification ? $user_verification['contact_num'] : 0;
            $work_num = $user_verification ? $user_verification['work_num'] : 0;

            $data[$date]['realname_num'] = $realname_num;
            $data[$date]['bindcard_num'] = $bindcard_num;

            $data[$date]['yys_num'] = $yys_num;
            $data[$date]['ali_num'] = $ali_num;
            $data[$date]['zm_num'] = $zm_num;
            $data[$date]['contact_num'] = $contact_num;
            $data[$date]['work_num'] = $work_num;

            $user_loan_order = UserLoanOrder::find()->where(['user_id' => $users])->select(['user_id', 'money_amount', 'status'])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            $user_loan_order_repay_list = UserLoanOrderRepayment::find()->where(['user_id' => $users])->andWhere(['!=', 'status', '4'])->select(['user_id', 'principal'])->asArray()->all(Yii::$app->get('db_kdkj_rd'));

            $user_loan_order_data_apply = [];
            $user_loan_order_data_success = [];
            $money_total = 0;
            $applyorder_num = 0;
            $successorder_num = 0;

            foreach ($user_loan_order_repay_list as $repayValue) {
                $money_total = $money_total + $repayValue['principal'];
                if (!isset($user_loan_order_data_success[$repayValue['user_id']])) {
                    $user_loan_order_data_success[$repayValue['user_id']] = 1;
                    $successorder_num++;
                }
            }

            foreach ($user_loan_order as $item) {
                if (!isset($user_loan_order_data_apply[$item['user_id']])) {
                    $user_loan_order_data_apply[$item['user_id']] = 1;
                    $applyorder_num++;
                }

//                if (in_array($item['status'], array_keys(UserLoanOrder::$operate_status)) || $item['status'] == UserLoanOrder::STATUS_PENDING_LOAN || $item['status'] == UserLoanOrder::STATUS_PAY) {
//                    $money_total = $money_total + $item['money_amount'];
//                    if (!isset($user_loan_order_data_success[$item['user_id']])) {
//                        $user_loan_order_data_success[$item['user_id']] = 1;
//                        $successorder_num++;
//                    }
//                }
            }
            $data[$date]['applyorder_num'] = $applyorder_num;
            $data[$date]['successorder_num'] = $successorder_num;
            $data[$date]['money_total'] = $money_total;

            $credit_mg_log = CreditMgLog::find()->where(['person_id' => $users])->count('*', Yii::$app->get('db_kdkj_risk_rd'));
            $mg_money = $credit_mg_log * 1;
            $credit_td_log = CreditTdLog::find()->where(['person_id' => $users])->count('*', Yii::$app->get('db_kdkj_risk_rd'));
            $td_moeny = $credit_td_log * 1;
            $credit_jxl_queue = CreditJxlQueue::find()->where(['user_id' => $users, 'current_status' => 6])->count("id", Yii::$app->get('db_kdkj_risk_rd'));
            $jxl_money = $credit_jxl_queue * 1;

            $credit_money = $mg_money + $td_moeny + $jxl_money;
            $data[$date]['credit_money'] = $credit_money;
        }
        \Yii::$app->cache->set($key . '1', $data, 300);
        \Yii::$app->cache->set($key . '2', $markets_data, 300);

        end:
        return $this->render("market-data", [
            'markets_data' => $markets_data,
            'data' => $data,
            'source_id' => $source_id,
        ]);
    }

    /**
     * @name 数据分析 -每日借款额度-/actionDailyLoanStatistics
     */
    public function actionDailyLoanStatistics(){
        ini_set('memory_limit', '1024M');
        $db = Yii::$app->get('db_kdkj_rd');
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $where = ' 1= 1 ';
        if ($add_start) {
            $where = $where . " and date>='{$add_start}'";
        } else {
            $add_start = date('Y-m-d', time() - 7 * 24 * 3600);
            $where = $where . " and date>='{$add_start}'";
        }

        if ($add_end) {
            $where = $where . " and date<='{$add_end}'";
        } else {
            $add_end = date('Y-m-d', time()+ 24 * 3600);
            $where = $where . " and date<'{$add_end}'";
        }
        $data =[];
        $arr=[
            "loan_money"=>0,
            "loan_num"=>0,
            "loan_num_1"=>0,
            "loan_num_2"=>0,
            "loan_num_3"=>0,
            "loan_num_4"=>0,
            "loan_num_5"=>0,
            "loan_num_6"=>0,
        ];
        $loan_data = StatisticsByMoney::find()->where($where)->asArray()->orderBy("date desc")->all();
        foreach($loan_data as $value){
            $type = $value['type'];
            if($type==0){
                $data[$value['date']]['loan_money']=$value['loan_money']/100;
                $data[$value['date']]['loan_num']=$value['loan_num'];
                $arr['loan_money']+=$value['loan_money']/100;
                $arr['loan_num']+=$value['loan_num'];
            }
            if($type==1){
                $data[$value['date']]['loan_num_1']=$value['loan_num'];
                $arr['loan_num_1']+=$value['loan_num'];
            }
            if($type==2){
                $data[$value['date']]['loan_num_2']=$value['loan_num'];
                $arr['loan_num_2']+=$value['loan_num'];
            }
            if($type==3){
                $data[$value['date']]['loan_num_3']=$value['loan_num'];
                $arr['loan_num_3']+=$value['loan_num'];
            }
            if($type==4){
                $data[$value['date']]['loan_num_4']=$value['loan_num'];
                $arr['loan_num_4']+=$value['loan_num'];
            }
            if($type==5){
                $data[$value['date']]['loan_num_5']=$value['loan_num'];
                $arr['loan_num_5']+=$value['loan_num'];
            }
            if($type==6){
                $data[$value['date']]['loan_num_6']=$value['loan_num'];
                $arr['loan_num_6']+=$value['loan_num'];
            }
            unset($value);
        }
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDailyLoanStatistics($arr,$data);
        }
        return $this->render("loan-statistics", [
            'data'=>$data,
            'all'=>$arr,
        ]);
    }

    private function _exportDailyLoanStatistics($all,$data){
        $this->_setcsvHeader('每日借款额度.csv');
        $items[0] = [
            '日期'=>"总计",
            '总借款金额' =>number_format($all['loan_money']/100),
            '总单数' =>$all['loan_num'],
            '单均金额' =>empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_money']/$all['loan_num']),
            "0-1000单数"=>$all['loan_num_1'],
            "0-1000占比"=>empty($all['loan_num_1'])?0:sprintf("%0.2f",$all['loan_num_1']/$all['loan_num']*100)."%",
            "1000-2000单数"=>$all['loan_num_2'],
            "1000-2000占比"=>empty($all['loan_num_2'])?0:sprintf("%0.2f",$all['loan_num_2']/$all['loan_num']*100)."%",
            "2000-3000单数"=>$all['loan_num_3'],
            "2000-3000占比"=>empty($all['loan_num_3'])?0:sprintf("%0.2f",$all['loan_num_3']/$all['loan_num']*100)."%",
            "3000-4000单数"=>$all['loan_num_4'],
            "3000-4000占比"=>empty($all['loan_num_4'])?0:sprintf("%0.2f",$all['loan_num_4']/$all['loan_num']*100)."%",
            "4000-5000单数"=>$all['loan_num_5'],
            "4000-5000占比"=>empty($all['loan_num_5'])?0:sprintf("%0.2f",$all['loan_num_5']/$all['loan_num']*100)."%",
            "5000以上单数"=>$all['loan_num_6'],
            "5000以上占比"=>empty($all['loan_num_6'])?0:sprintf("%0.2f",$all['loan_num_6']/$all['loan_num']*100)."%",
        ];
        $i=1;
        foreach($data as $date=> $value){
            $items[$i] = [
                '日期'=>$date,
                '总借款金额' =>number_format($value['loan_money']/100),
                '总单数' =>$value['loan_num'],
                '单均金额' =>empty($value['loan_num'])?0:sprintf("%0.2f",$value['loan_money']/$value['loan_num']),
                "0-1000单数"=>$value['loan_num_1'],
                "0-1000占比"=>empty($value['loan_num_1'])?0:sprintf("%0.2f",$value['loan_num_1']/$value['loan_num']*100)."%",
                "1000-2000单数"=>$value['loan_num_2'],
                "1000-2000占比"=>empty($value['loan_num_2'])?0:sprintf("%0.2f",$value['loan_num_2']/$value['loan_num']*100)."%",
                "2000-3000单数"=>$value['loan_num_3'],
                "2000-3000占比"=>empty($value['loan_num_3'])?0:sprintf("%0.2f",$value['loan_num_3']/$value['loan_num']*100)."%",
                "3000-4000单数"=>$value['loan_num_4'],
                "3000-4000占比"=>empty($value['loan_num_4'])?0:sprintf("%0.2f",$value['loan_num_4']/$value['loan_num']*100)."%",
                "4000-5000单数"=>$value['loan_num_5'],
                "4000-5000占比"=>empty($value['loan_num_5'])?0:sprintf("%0.2f",$value['loan_num_5']/$value['loan_num']*100)."%",
                "5000以上单数"=>$value['loan_num_6'],
                "5000以上占比"=>empty($value['loan_num_6'])?0:sprintf("%0.2f",$value['loan_num_6']/$value['loan_num']*100)."%",
            ];
            $i++;
        }
        echo $this->_array2csv($items);
        exit;
    }


    /**
     * @name 数据分析 -小钱包数据管理-贷后数据统计/actionOverdueReport
     */
    public function actionOverdueReport() {
        $selected_data = $this->request->get('selected_data');
        $where = ' 1 = 1 ';

        if ($selected_data) {
            $where = $where . " and FROM_UNIXTIME(loan_time, '%Y-%m-%d')='" . $selected_data . "'";
        } else {
            $selected_data = date('Y-m-d', time() - 7 * 24 * 3600);
            $where = $where . " and FROM_UNIXTIME(loan_time, '%Y-%m-%d')='" . $selected_data . "'";
        }

        $dataList = UserLoanOrderRepayment::find()->where($where)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        $data = [
            'loan_count' => 0, //放款个数
            'loan_sum' => 0, //放款金额
            'overdue_day_sum' => 0, //逾期天数
            'col_count' => 0, //催回个数
        ];

        foreach ($dataList as $item) {
            $data['loan_count'] ++;
            $data['loan_sum'] += $item['principal'];
            $data['overdue_day_sum'] += $item['overdue_day'];

            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE && $item['is_overdue']) {
                $data['col_count'] ++;
            }
            //没有逾期
            $data[0]['loan_count'] = isset($data[0]['loan_count']) ? $data[0]['loan_count'] : 0;
            $data[0]['loan_sum'] = isset($data[0]['loan_sum']) ? $data[0]['loan_sum'] : 0;
            if (!$item['is_overdue']) {
                $data[0]['loan_count'] ++;
                $data[0]['loan_sum'] += $item['principal'];
            }
            //逾期一到七天
            for ($i = 1; $i < 8; $i++) {
                $data[$i]['loan_count'] = isset($data[$i]['loan_count']) ? $data[$i]['loan_count'] : 0;
                $data[$i]['loan_sum'] = isset($data[$i]['loan_sum']) ? $data[$i]['loan_sum'] : 0;
                $data[$i]['col_count'] = isset($data[$i]['col_count']) ? $data[$i]['col_count'] : 0;
                if ($item['is_overdue'] && $item['overdue_day'] >= $i) {
                    $data[$i]['loan_count'] ++;
                    $data[$i]['loan_sum'] += $item['principal'];
                    if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                        $data[$i]['col_count'] ++;
                    }
                }
            }
            //逾期八到三十天
            $data[8]['loan_count'] = isset($data[8]['loan_count']) ? $data[8]['loan_count'] : 0;
            $data[8]['loan_sum'] = isset($data[8]['loan_sum']) ? $data[8]['loan_sum'] : 0;
            $data[8]['col_count'] = isset($data[8]['col_count']) ? $data[8]['col_count'] : 0;
            if ($item['is_overdue'] && $item['overdue_day'] >= 8) {
                $data[8]['loan_count'] ++;
                $data[8]['loan_sum'] += $item['principal'];
                if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                    $data[8]['col_count'] ++;
                }
            }
            //逾期三十一天以上
            $data[31]['loan_count'] = isset($data[31]['loan_count']) ? $data[31]['loan_count'] : 0;
            $data[31]['loan_sum'] = isset($data[31]['loan_sum']) ? $data[31]['loan_sum'] : 0;
            $data[31]['col_count'] = isset($data[31]['col_count']) ? $data[31]['col_count'] : 0;
            if ($item['is_overdue'] && $item['overdue_day'] >= 31) {
                $data[31]['loan_count'] ++;
                $data[31]['loan_sum'] += $item['principal'];
                if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                    $data[31]['col_count'] ++;
                }
            }
        }
        $data[0]['col_count'] = 0;

        //催回率
        $data['col_rate'] = $data['loan_count'] ? sprintf("%0.2f", $data['col_count'] / $data['loan_count'] * 100) . "%" : '0.00%';
        $data[0]['col_rate'] = '0.00%';

        for ($i = 1; $i < 8; $i++) {
            $data[$i]['col_count'] = isset($data[$i]['col_count']) ? $data[$i]['col_count'] : 0;
            $data[$i]['loan_count'] = isset($data[$i]['loan_count']) ? $data[$i]['loan_count'] : 0;
            $data[$i]['col_rate'] = $data[$i]['loan_count'] ? sprintf("%0.2f", $data[$i]['col_count'] / $data[$i]['loan_count'] * 100) . "%" : '0.00%';
        }

        $data[8]['col_count'] = isset($data[8]['col_count']) ? $data[8]['col_count'] : 0;
        $data[8]['loan_count'] = isset($data[8]['loan_count']) ? $data[8]['loan_count'] : 0;
        $data[8]['col_rate'] = $data[8]['loan_count'] ? sprintf("%0.2f", $data[8]['col_count'] / $data[8]['loan_count'] * 100) . "%" : '0.00%';

        $data[31]['col_count'] = isset($data[31]['col_count']) ? $data[31]['col_count'] : 0;
        $data[31]['loan_count'] = isset($data[31]['loan_count']) ? $data[31]['loan_count'] : 0;
        $data[31]['col_rate'] = $data[31]['loan_count'] ? sprintf("%0.2f", $data[31]['col_count'] / $data[31]['loan_count'] * 100) . "%" : '0.00%';

        return $this->render("orderdue-report", [
            'data' => $data,
        ]);
    }



    /**
     * 额度清零列表过滤
     * @return string
     */
    protected function getCreditNumFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND u.id = " . intval($search['id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $find = LoanPerson::find()->where(["like", "name", $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if (!empty($find)) {
                    $condition .= " AND u.id = " . $find['id'];
                }
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $find = LoanPerson::find()->where(["phone" => trim($search['phone'])])->one(Yii::$app->get('db_kdkj_rd'));
                if (!empty($find)) {
                    $condition .= " AND u.id = " . $find['id'];
                }
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND u.created_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND u.created_at <= " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }

    /**
     * @name 申请用户数据 /actionApplicationUserData
     * @return type
     */
    public function actionApplicationUserData() {

        $sql_home = "select count(*) count,home from tb_user_count_arr WHERE status != 0 GROUP BY home;";     // 查询并且统计数据
        $query_home = UserCountArr::findBySql($sql_home)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_current_location = "select count(*) count,current_location from tb_user_count_arr WHERE status != 0 GROUP BY current_location;";
        $query_current_location = UserCountArr::findBySql($sql_current_location)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_age_group = "select count(*) count,age_group from tb_user_count_arr WHERE status != 0  GROUP BY age_group;";
        $query_age_group = UserCountArr::findBySql($sql_age_group)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_sex = "select count(*) count,sex from tb_user_count_arr WHERE status != 0 GROUP BY sex;";
        $query_sex = UserCountArr::findBySql($sql_sex)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_model = "select count(*) count,model from tb_user_count_arr WHERE status != 0 GROUP BY model;";
        $query_model = UserCountArr::findBySql($sql_model)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $array_new_sex = array(
            '0' => '女',
            '1' => '男',
            '2' => '未知性别',
        );
        if ($query_sex) {
            foreach ($query_sex as $key_a => $val_a) {
                $query_sex[$key_a]["sex"] = $array_new_sex[$val_a["sex"]];                // 一个数组与另外一个数组的值与值的对调
            }
        }
        $array_new_age = array(
            '0' => '未知年龄层',
            '1' => '0-18年龄层',
            '2' => '18-25年龄层',
            '3' => '25-30年龄层',
            '4' => '30-35年龄层',
            '5' => '35-40年龄层',
            '6' => '40-50年龄层',
            '7' => '50-60年龄层',
            '8' => '>60年龄层',
        );
        foreach ($query_age_group as $key => $val) {
            $query_age_group[$key]["age_group"] = $array_new_age[$val["age_group"]];                // 一个数组与另外一个数组的值与值的对调
        }

        $count_home = [];
        $home = [];
        $count_current_location = [];
        $current_location = [];
        $count_age_group = [];
        $age_group = [];
        $count_sex = [];
        $sex = [];
        $count_model = [];
        $model = [];
        if ($query_home) {                                                                  // 将数据中的数组两个值拆分 为了合并新数组使用
            foreach ($query_home as $k => $v) {
                $count_home[] = $v['count'];
                $home[] = $v['home'];
            }
        }
        if ($query_current_location) {
            foreach ($query_current_location as $k_a => $v_a) {
                $count_current_location[] = $v_a['count'];
                $current_location[] = $v_a['current_location'];
            }
        }
        if ($query_age_group) {
            foreach ($query_age_group as $k_b => $v_b) {
                $count_age_group[] = $v_b['count'];
                $age_group[] = $v_b['age_group'];
            }
        }
        if ($query_sex) {
            foreach ($query_sex as $k_c => $v_c) {
                $count_sex[] = $v_c['count'];
                $sex[] = $v_c['sex'];
            }
        }
        if ($query_model) {
            foreach ($query_model as $k_d => $v_d) {
                $count_model[] = $v_d['count'];
                $model[] = $v_d['model'];
            }
        }

        $array_user_register_total = array_combine($home, $count_home);
        unset($array_user_register_total['其他']);
        $array_user_register_current = array_combine($current_location, $count_current_location);
        unset($array_user_register_current['用户暂未登录']);
        $array_user_register_age = array_combine($age_group, $count_age_group);
        unset($array_user_register_age['未知年龄层']);
        $array_user_register_sex = array_combine($sex, $count_sex);
        unset($array_user_register_sex['未知性别']);
        $array_user_register_model = array_combine($model, $count_model);
        unset($array_user_register_model['设备未知']);
        arsort($array_user_register_total);
        arsort($array_user_register_current);

        $_slice = 40;
        $all_user_address_b = array_slice($array_user_register_total, 0, $_slice);
        $all_user_address_c = array_slice($array_user_register_current, 0, $_slice);
        $all_user_address_d = array_slice($array_user_register_age, 0, $_slice);
        $all_user_address_e = array_slice($array_user_register_sex, 0, $_slice);
        $all_user_address_f = array_slice($array_user_register_model, 0, $_slice);

        $all_user_address_chart_b = $this->_setBarChart('申请用户户籍地址分布', $all_user_address_b);
        $all_user_address_chart_c = $this->_setBarChart('申请用户当前地址分布', $all_user_address_c);
        $all_user_address_chart_d = $this->_setBarChart('申请用户当前年龄段', $all_user_address_d);
        $all_user_address_chart_e = $this->_setBarChart('申请用户性别比例', $all_user_address_e);
        $all_user_address_chart_f = $this->_setBarChart('申请用户设备型号比例', $all_user_address_f);


        return $this->render('user-count-application-all', [
            'menu' => Util::getBreadCrumbsMenu($this->invest_user_menu, 1),
            'array_user_register_total' => $array_user_register_total,
            'all_user_address_chart_b' => $all_user_address_chart_b,
            'array_user_register_current' => $array_user_register_current,
            'all_user_address_chart_c' => $all_user_address_chart_c,
            'array_user_register_age' => $array_user_register_age,
            'all_user_address_chart_d' => $all_user_address_chart_d,
            'array_user_register_sex' => $array_user_register_sex,
            'all_user_address_chart_e' => $all_user_address_chart_e,
            'array_user_register_model' => $array_user_register_model,
            'all_user_address_chart_f' => $all_user_address_chart_f,
        ]);
    }

    /**
     * @name 逾期用户数据 /actionDenyUserData
     */
    public function actionDenyUserData() {
        $sql_home = "select count(*) count,home from tb_user_count_arr WHERE status = 2 GROUP BY home;";     // 查询并且统计数据
        $query_home = UserCountArr::findBySql($sql_home)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_current_location = "select count(*) count,current_location from tb_user_count_arr WHERE status = 2 GROUP BY current_location;";
        $query_current_location = UserCountArr::findBySql($sql_current_location)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_age_group = "select count(*) count,age_group from tb_user_count_arr WHERE status = 2 GROUP BY age_group;";
        $query_age_group = UserCountArr::findBySql($sql_age_group)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_sex = "select count(*) count,sex from tb_user_count_arr WHERE status = 2 GROUP BY sex;";
        $query_sex = UserCountArr::findBySql($sql_sex)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $sql_model = "select count(*) count,model from tb_user_count_arr WHERE status = 2 GROUP BY model;";
        $query_model = UserCountArr::findBySql($sql_model)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $array_new_sex = array(
            '0' => '女',
            '1' => '男',
            '2' => '未知性别',
        );
        if ($query_sex) {
            foreach ($query_sex as $key_a => $val_a) {
                $query_sex[$key_a]["sex"] = $array_new_sex[$val_a["sex"]];                // 一个数组与另外一个数组的值与值的对调
            }
        }
        $array_new_age = array(
            '0' => '未知年龄层',
            '1' => '0-18年龄层',
            '2' => '18-25年龄层',
            '3' => '25-30年龄层',
            '4' => '30-35年龄层',
            '5' => '35-40年龄层',
            '6' => '40-50年龄层',
            '7' => '50-60年龄层',
            '8' => '>60年龄层',
        );
        foreach ($query_age_group as $key => $val) {
            $query_age_group[$key]["age_group"] = $array_new_age[$val["age_group"]];                // 一个数组与另外一个数组的值与值的对调
        }

        $count_home = [];
        $home = [];
        $count_current_location = [];
        $current_location = [];
        $count_age_group = [];
        $age_group = [];
        $count_sex = [];
        $sex = [];
        $count_model = [];
        $model = [];
        if ($query_home) {                                                                  // 将数据中的数组两个值拆分 为了合并新数组使用
            foreach ($query_home as $k => $v) {
                $count_home[] = $v['count'];
                $home[] = $v['home'];
            }
        }
        if ($query_current_location) {
            foreach ($query_current_location as $k_a => $v_a) {
                $count_current_location[] = $v_a['count'];
                $current_location[] = $v_a['current_location'];
            }
        }
        if ($query_age_group) {
            foreach ($query_age_group as $k_b => $v_b) {
                $count_age_group[] = $v_b['count'];
                $age_group[] = $v_b['age_group'];
            }
        }
        if ($query_sex) {
            foreach ($query_sex as $k_c => $v_c) {
                $count_sex[] = $v_c['count'];
                $sex[] = $v_c['sex'];
            }
        }
        if ($query_model) {
            foreach ($query_model as $k_d => $v_d) {
                $count_model[] = $v_d['count'];
                $model[] = $v_d['model'];
            }
        }

        $array_user_register_total = array_combine($home, $count_home);
        unset($array_user_register_total['其他']);
        $array_user_register_current = array_combine($current_location, $count_current_location);
        unset($array_user_register_current['用户暂未登录']);
        $array_user_register_age = array_combine($age_group, $count_age_group);
        unset($array_user_register_age['未知年龄层']);
        $array_user_register_sex = array_combine($sex, $count_sex);
        unset($array_user_register_sex['未知性别']);
        $array_user_register_model = array_combine($model, $count_model);
        unset($array_user_register_model['设备未知']);
        arsort($array_user_register_total);
        arsort($array_user_register_current);

        $_slice = 40;
        $all_user_address_b = array_slice($array_user_register_total, 0, $_slice);
        $all_user_address_c = array_slice($array_user_register_current, 0, $_slice);
        $all_user_address_d = array_slice($array_user_register_age, 0, $_slice);
        $all_user_address_e = array_slice($array_user_register_sex, 0, $_slice);
        $all_user_address_f = array_slice($array_user_register_model, 0, $_slice);

        $all_user_address_chart_b = $this->_setBarChart('逾期用户户籍地址分布', $all_user_address_b);
        $all_user_address_chart_c = $this->_setBarChart('逾期用户当前地址分布', $all_user_address_c);
        $all_user_address_chart_d = $this->_setBarChart('逾期用户当前年龄段', $all_user_address_d);
        $all_user_address_chart_e = $this->_setBarChart('逾期用户性别比例', $all_user_address_e);
        $all_user_address_chart_f = $this->_setBarChart('逾期用户设备型号比例', $all_user_address_f);

        return $this->render('user-count-deny-all', [
            'menu' => Util::getBreadCrumbsMenu($this->invest_user_menu, 2),
            'array_user_register_total' => $array_user_register_total,
            'all_user_address_chart_b' => $all_user_address_chart_b,
            'array_user_register_current' => $array_user_register_current,
            'all_user_address_chart_c' => $all_user_address_chart_c,
            'array_user_register_age' => $array_user_register_age,
            'all_user_address_chart_d' => $all_user_address_chart_d,
            'array_user_register_sex' => $array_user_register_sex,
            'all_user_address_chart_e' => $all_user_address_chart_e,
            'array_user_register_model' => $array_user_register_model,
            'all_user_address_chart_f' => $all_user_address_chart_f,
        ]);
    }

    /**
     * @name 组合数据 /_setBarChart
     */
    private function _setBarChart($title, $params) {
        return [
            'title' => $title,
            'data' => $params,
        ];
    }

    /**
     * @return string
     * @name 数据分析 -小钱包数据管理-每日机审项结果/actionDailyCheckItems
     */
    public function actionDailyCheckItems() {
        $items = LoanPersonBadInfo::$all_code;
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $type =  $this->request->get('type');
        $where = '1 = 1';
        if ($add_start) {
            $where = $where . ' and date>=' . strtotime($add_start);
        } else {
            $add_start = strtotime(date('Y-m-d', time() - 7 * 24 * 3600));
            $where = $where . ' and date>=' . $add_start;
        }
        if ($add_end) {
            $where = $where . ' and date<' . (strtotime($add_end) + 24 * 3600);
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 24 * 3600;
            $where = $where . ' and date<' . $add_end;
        }
        if($type){
            $where = $where . " and data = '".$type."'";
        }

        $query = DailyCodeLog::find()->where($where)->orderBy(['id' => SORT_ASC]);
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        $pageSize = 1000;
        $total_pages = ceil($totalCount / $pageSize);
        $data = [];
        $all = [
            'total' => 0,
            'num' => 0,
            'rate' => 0,
            'type' => 0,
            'created_at' => 0,
            'updated_at' => 0,
        ];
        for ($i = 1; $i <= $total_pages; $i++) {
            $start = ($i - 1) * $pageSize;
            $daily_code_log = $query->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            foreach ($daily_code_log as $item) {
                $key = $item['data'];
                $time = date("Y-m-d", $item['date']);
                if (!isset($data[$key])) {
                    $data[$key]['date'] = date('Y-m-d H:i:s');
                    $data[$key]['total'] = 0;
                    $data[$key]['num'] = 0;
                    $data[$key]['created_at'] = date('Y-m-d H:i:s');
                    $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    $data[$key]['type'] = 0;
                }
                $data[$key]['date'] = $time;
                $data[$key]['total'] += $item['total'];
                $data[$key]['num'] += $item['null_code_total'];
                $data[$key]['created_at'] = date('Y-m-d H:i:s', $item['created_at']);
                $data[$key]['updated_at'] = empty($item['updated_at'])?date('Y-m-d H:i:s', $item['created_at']):date('Y-m-d H:i:s', $item['updated_at']);
//                $_data = $item['data'];
//                if (!empty($_data)) {
//                    $_data = json_decode($_data);
//                    if (isset($_data->$type)) {
//                        $data[$key]['num'] = $_data->$type;
//                    } else {
//                        $data[$key]['num'] = 0;
//                    }
//                } else {
//                    $data[$key]['num'] = 0;
//                }

                $data[$key]['type'] = $key;

                $all['date'] = date('Y-m-d H:i:s');
                $all['total'] += $item['total'];
                $all['num'] += $item['null_code_total'];
                $all['type'] = $key;
            }

        }

        return $this->render('daily-check-data', array(
            'data' => $data,
            'all' => $all,
            'items' => $items
        ));
    }

    /**
     * @name 日期选择 /actionChannelAssociatedDataDaily
     */
    public function actionDateSelect() {

        $time_begin = strtotime($this->request->get("time_begin"));
        $time_end = strtotime($this->request->get("time_end"));
        $appKeyData = AppKeyData::findBySql("SELECT * FROM tb_app_key_data WHERE UNIX_TIMESTAMP(DATE) >= $time_begin AND UNIX_TIMESTAMP(DATE) < $time_end ORDER BY DATE DESC ")->all(Yii::$app->get('db_kdkj_rd'));
        $ret_data = [];
        foreach ($appKeyData as $key => $value) {
            $ret_data[$key] = $value;
            $ret_data[$key]['l_money_amount'] = empty($value['l_money_amount']) ? 0 : $value['l_money_amount'] / 100;
            $ret_data[$key]['r_money_amount'] = empty($value['r_money_amount']) ? 0 : $value['r_money_amount'] / 100;
        }
        $this->response->format = Response::FORMAT_JSON;
        return [
            'ret_data' => $ret_data,
        ];
    }

    /**
     * @name 数据分析 -风控数据-用户认证数据统计/actionUserVerificationReport
     */
    public function actionUserVerificationReport() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $source = $this->request->get('source_type');
        $appMarket_type = $this->request->get('appMarket_type');
        $date=date('Y-m-d');
        $condition = ' 1=1 ';
        if (!empty($add_start)) {
            $condition .= " AND date >= '{$add_start}' ";
        }
        if (!empty($add_end)) {
            $condition .= " AND date <= '{$add_end}' ";
            $date=$add_end;
        }
        if($source){
            $condition .= " and source ={$source}";
        }else{
            $condition .= " and source = -1";
        }
        if($appMarket_type){
            $condition .= " and appMarket_type ={$appMarket_type}";
        }

        $query = StatisticsVerification::find($condition)->where($condition)
                    ->select('date,
                    sum(reg_num) as reg_num,
                    sum(realname_num) as realname_num,
                    sum(zmxy_num) as zmxy_num,
                    sum(jxl_num) as jxl_num,
                    sum(bind_card_num) as bind_card_num,
                    sum(alipay_num) as alipay_num,
                    sum(contacts_list_num) as contacts_list_num,
                    sum(public_funds_num) as public_funds_num,
                    sum(all_verif_num) as all_verif_num,
                    sum(unapply_num) as unapply_num,
                    sum(apply_num) as apply_num,
                    sum(apply_success_num) as apply_success_num,
                    sum(apply_fail_num) as apply_fail_num
                    ')
                    ->groupBy('date')
                    ->orderBy('date DESC');

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 10;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        //获取脚本最后更新时间
        $last_update_query = StatisticsVerification::findBySql("SELECT updated_at FROM tb_statistics_verification where date='{$date}' order by updated_at desc LIMIT 1")->one(Yii::$app->get('db_kdkj_rd'));
        $update_time = (!empty($last_update_query['updated_at'])) ? date("Y-m-d H:i:s",$last_update_query['updated_at']) : date("Y-m-d H:i:s");
        $maps = [
            '0' => '实名(%)',
            '1' => '芝麻授信(%)',
            '2' => '运营商(%)',
            '3' => '绑卡(%)',
            '4' => '支付宝(%)',
        ];
        for ($i = 0; $i < 5; $i++) {
            $y_val[$i] = array();
        }
        $date_time = [];
        foreach (array_reverse($info) as $key => $vl) {
            $vl['reg_num']= empty($vl['reg_num'])?1:$vl['reg_num'];
            $y_val[0][] = sprintf("%0.2f", $vl['realname_num'] / $vl['reg_num'] * 100);
            $y_val[1][] = sprintf("%0.2f", $vl['zmxy_num'] / $vl['reg_num'] * 100);
            $y_val[2][] = sprintf("%0.2f", $vl['jxl_num'] / $vl['reg_num'] * 100);
            $y_val[3][] = sprintf("%0.2f", $vl['bind_card_num'] / $vl['reg_num'] * 100);
            $y_val[4][] = sprintf("%0.2f", $vl['alipay_num'] / $vl['reg_num'] * 100);
            $date_time[$key] = $vl['date'];
        }

        $legend = array_values($maps);
        $series = array();
        for ($j = 0; $j < 5; $j++) {
            $series[] = array(
                'name' => $legend[$j],
                'type' => 'line',
                'data' => $y_val[$j],
            );
        }

        return $this->render('user-verification-report', [
            'info' => $info,
            'legend' => $legend,
            'x' => $date_time,
            'series' => $series,
            'pages' => $pages,
            'update_time' => $update_time,
        ]);
    }

    /**
     * @name 数据分析 -小钱包数据管理-用户认证数据统计/actionUserVerificationReport_bak
     */
    public function actionUserVerificationReport_bak() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $today = strtotime('today');
        if (empty($add_start) && empty($add_end)) {
            $where = "created_at >= {$today} ";
        } else {
            $where = " created_at >= " . strtotime($add_start) . " and created_at< " . strtotime($add_end);
        }
        $sql = "SELECT COUNT(id) AS amount ,SUM(IF(real_verify_status,1,0)) AS real_verify_amount,SUM(IF(real_verify_status,1,0))/COUNT(id) AS real_verify_probability,SUM(IF(real_work_status,1,0)) AS real_work_amount,SUM(IF(real_work_status,1,0))/COUNT(id) AS real_work_probability,
SUM(IF(real_contact_status,1,0)) AS real_contact_amount,SUM(IF(real_contact_status,1,0))/COUNT(id) AS real_contact_probability,
SUM(IF(real_zmxy_status,1,0)) AS real_zmxy_amount, SUM(IF(real_zmxy_status,1,0))/COUNT(id) AS real_zmxy_probability,
SUM(IF(real_alipay_status,1,0)) AS real_alipay_amount,SUM(IF(real_alipay_status,1,0))/COUNT(id) AS real_alipay_probability ,
SUM(IF(real_jxl_status,1,0)) AS real_jxl_amount, SUM(IF(real_jxl_status,1,0))/COUNT(id) AS real_jxl_probability ,FROM_UNIXTIME(created_at, '%Y-%m-%d') as date FROM kdkj.tb_user_verification where " . $where . " GROUP BY FROM_UNIXTIME(created_at, '%Y-%m-%d') ORDER BY DATE DESC";

        $info = UserVerification::findBySql($sql)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $maps = [
            '0' => '身份认证(%)',
            '1' => '工作信息认证(%)',
            '2' => '紧急联系人认证(%)',
            '3' => '芝麻信用(%)',
            '4' => '支付宝(%)',
            '5' => '聚信立认证(%)',
        ];
        for ($i = 0; $i < 6; $i++) {
            $y_val[$i] = array();
        }
        $date_time = [];
        foreach ($info as $key => $vl) {
            if (is_array($vl)) {
                $y_val[0][] = $vl['real_verify_probability'] * 100;
                $y_val[1][] = $vl['real_work_probability'] * 100;
                $y_val[2][] = $vl['real_contact_probability'] * 100;
                $y_val[3][] = $vl['real_zmxy_probability'] * 100;
                $y_val[4][] = $vl['real_alipay_probability'] * 100;
                $y_val[5][] = $vl['real_jxl_probability'] * 100;
            }
            $date_time[$key] = $vl['date'];
//            array_reverse($date_time);
        }

        $legend = array_values($maps);
        $series = array();
        for ($j = 0; $j < 6; $j++) {
            $series[] = array(
                'name' => $legend[$j],
                'type' => 'line',
                'data' => $y_val[$j],
            );
        }
        return $this->render('user-verification-report', [
            'info' => $info,
            'legend' => $legend,
            'x' => $date_time,
            'series' => $series,
        ]);
    }

    /**
     * @name 数据分析 -财务数据-每日还款金额数据/actionDailyRepaymentsData
     */
    public function actionDailyRepaymentsData() {
        $all_loan_money = 0;          //到期总金额
        $all_repayment_money = 0;     //还款总金额
        $all_overdue_money = 0;       //逾期总金额
        $old_loan_money = 0;          //老用户到期金额
        $old_repayment_money = 0;     //老用户还款金额
        $old_overdue_money = 0;       //老用户逾期金额
        $new_loan_money = 0;          //新用户到期金额
        $new_loan_money_7 = 0;        //新用户7天到期金额
        $new_loan_money_14 = 0;       //新用户14天到期金额
        $new_repayment_money = 0;     //新用户还款金额
        $new_overdue_money = 0;       //新用户逾期金额
        $new_overdue_money_7 = 0;     //新用户7天逾期金额
        $new_overdue_money_14 = 0;    //新用户14天逾期金额
        $all_zc_money_old = 0;        //新用户未逾期金额
        $all_zc_money_new = 0;        //老用户未逾期金额
        $all_zc_money = 0;            //未逾期金额
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $sub_order_type = $this->request->get('sub_order_type', '-1');
        $channel = $this->request->get('channel');
        $search_date = $this->request->get('search_date');
        $where = ' 1= 1 ';
        if ($add_start) {
            $add_start = strtotime($add_start);
        } else {
            $add_start = strtotime(date('Y-m-d', time() - 7*86400));
        }
        if ($add_end) {
            $add_end = strtotime($add_end) + 86400;
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 2*86400;
        }
        if($search_date == 2){//1.放款日期；2.还款日期
            $where .= ' and r.plan_fee_time>=' . $add_start;
            $where .= ' and r.plan_fee_time<' . $add_end;
        }else{
            $where .= ' and r.created_at>=' . $add_start;
            $where .= ' and r.created_at<' . $add_end;
        }

        if ($sub_order_type != UserLoanOrder::SUB_TYPE_ALL) {
            $where .= ' and l.sub_order_type = ' . intval($sub_order_type);
        }
        $data = [];
        $user_loan_order_repayment = UserLoanOrderRepayment::find()->from(UserLoanOrderRepayment::tableName() . 'as r')
            ->leftJoin(UserLoanOrder::tableName() . 'as l', 'r.order_id=l.id')->where($where)
            ->select('r.plan_fee_time,r.status,r.is_overdue,r.overdue_day,r.principal,l.is_first,l.loan_method,l.loan_term,r.created_at')
            ->orderBy(['r.plan_fee_time' => SORT_DESC])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($user_loan_order_repayment as $item) {
            $is_first = $item['is_first'];
            if ($is_first) {
                $old = 0;
            } else {
                $old = 1;
            }
            $time = date('n-j', $item['plan_fee_time']);
            $create_time = date('n-j', $item['created_at']);
            $time_key = date('Y-m-d', $item['plan_fee_time']);
            $unix_time_key = strtotime($time_key);
            if (!isset($data[$time])) {
                $data[$time] = [
                    'unix_time_key' => 0,
                    'success_money' => 0,
                    'success_money_old' => 0,
                    'success_money_new' => 0,
                    'success_money_new_7' => 0,
                    'success_money_new_14' => 0,
                    'dc_money' => 0,
                    'dc_money_old' => 0,
                    'dc_money_new' => 0,
                    'd1_money' => 0,
                    'd1_money_old' => 0,
                    'd1_money_new' => 0,
                    'd2_money' => 0,
                    'd2_money_old' => 0,
                    'd2_money_new' => 0,
                    'd3_money' => 0,
                    'd3_money_old' => 0,
                    'd3_money_new' => 0,
                    'dn_money' => 0,
                    'dn_money_old' => 0,
                    'dn_money_new' => 0,
                    'dn_money_new_7' => 0,
                    'dn_money_new_14' => 0,
                    'da_money' => 0,
                    'da_money_old' => 0,
                    'da_money_new' => 0,
                    'repay_money' => 0,
                    'repay_money_old' => 0,
                    'repay_money_new' => 0,
                    'zc_money' => 0,
                    'zc_money_new' => 0,
                    'zc_money_old' => 0,
                    'create_time' => date('Y-m-d'),
                ];
            }
            $data[$time]['success_money'] += $item['principal'] / 100;
            $data[$time]['create_time'] =$create_time;
            $data[$time]['unix_time_key'] =$unix_time_key;
            $all_loan_money += $item['principal'] / 100;
            if ($old) {
                $data[$time]['success_money_old'] += $item['principal'] / 100;
                $old_loan_money += $item['principal'] / 100;
            } else {
                $data[$time]['success_money_new'] += $item['principal'] / 100;
                $new_loan_money += $item['principal'] / 100;
            }
            if ($item['loan_method'] == 0 && $item['loan_term'] == 14) {
                $data[$time]['success_money_new_14'] += $item['principal'] / 100;
                $new_loan_money_14 += $item['principal'] / 100;
            } elseif ($item['loan_method'] == 0 && $item['loan_term'] == 7) {
                $data[$time]['success_money_new_7'] += $item['principal'] / 100;
                $new_loan_money_7 += $item['principal'] / 100;
            }
            if ($item['is_overdue'] && $item['overdue_day'] > 0 && $item['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $data[$time]['dn_money'] += $item['principal'] / 100;
                $all_overdue_money += $item['principal'] / 100;
                if ($old) {
                    $data[$time]['dn_money_old'] += $item['principal'] / 100;
                    $old_overdue_money += $item['principal'] / 100;
                } else {
                    $data[$time]['dn_money_new'] += $item['principal'] / 100;
                    $new_overdue_money += $item['principal'] / 100;
                }

                if ($item['loan_method'] == 0 && $item['loan_term'] == 14) {
                    $data[$time]['dn_money_new_14'] += $item['principal'] / 100;
                    $new_overdue_money_14 += $item['principal'] / 100;
                } elseif ($item['loan_method'] == 0 && $item['loan_term'] == 7) {
                    $data[$time]['dn_money_new_7'] += $item['principal'] / 100;
                    $new_overdue_money_7 += $item['principal'] / 100;
                }
            }
            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $data[$time]['repay_money'] += $item['principal'] / 100;
                $all_repayment_money += $item['principal'] / 100;
                if ($old) {
                    $data[$time]['repay_money_old'] += $item['principal'] / 100;
                    $old_repayment_money += $item['principal'] / 100;
                } else {
                    $data[$time]['repay_money_new'] += $item['principal'] / 100;
                    $new_repayment_money += $item['principal'] / 100;
                }
                if(isset($item['is_overdue']) && $item['is_overdue'] == 0){//已还款 && 未逾期
                    $data[$time]['zc_money'] += $item['principal'] / 100;//正常还款
                    if($old){
                        $data[$time]['zc_money_old'] += $item['principal'] / 100;
                        $all_zc_money_old +=$item['principal'] / 100;
                    }else{
                        $data[$time]['zc_money_new'] +=$item['principal'] / 100;
                        $all_zc_money_new +=$item['principal'] / 100;
                    }
                    $all_zc_money+=$item['principal'] / 100;
                }
            }
            if (!empty($data[$time]['success_money'])) {
                $data[$time]['repay_rate'] = empty($data[$time]['success_money']) ? '0%' : bcdiv($data[$time]['repay_money'], $data[$time]['success_money'], 4) * 100 . "%";
                $data[$time]['repay_rate_old'] = empty($data[$time]['success_money_old']) ? '0%' : bcdiv($data[$time]['repay_money_old'], $data[$time]['success_money_old'], 4) * 100 . "%";
                $data[$time]['repay_rate_new'] = empty($data[$time]['success_money_new']) ? '0%' : bcdiv($data[$time]['repay_money_new'], $data[$time]['success_money_new'], 4) * 100 . "%";

                $money = 0;
                $money_old = 0;
                $money_new = 0;
                $money_new_14 = 0;
                $money_new_7 = 0;
                if ($data[$time]['dn_money'] > 0) {
                    $money = $data[$time]['dn_money'];
                    if ($data[$time]['dn_money_old'] > 0) {
                        $money_old = $data[$time]['dn_money_old'];
                    }
                    if ($data[$time]['dn_money_new'] > 0) {
                        $money_new = $data[$time]['dn_money_new'];
                    }
                    if ($data[$time]['dn_money_new_14'] > 0) {
                        $money_new_14 = $data[$time]['dn_money_new_14'];
                    }
                    if ($data[$time]['dn_money_new_7'] > 0) {
                        $money_new_7 = $data[$time]['dn_money_new_7'];
                    }
                }

                if ($data[$time]['repay_money'] && $money) {
                    $money = $data[$time]['success_money'] - $data[$time]['repay_money'];
                }
                $data[$time]['dc_money'] = $money;
                $data[$time]['dc_money_old'] = $money_old;
                $data[$time]['dc_money_new'] = $money_new;
                $data[$time]['conversion_rate'] = empty($data[$time]['success_money']) ? '0%' : bcdiv($money, $data[$time]['success_money'], 4) * 100 . "%";
                $data[$time]['conversion_rate_old'] = empty($data[$time]['success_money_old']) ? '0%' : bcdiv($money_old, $data[$time]['success_money_old'], 4) * 100 . "%";
                $data[$time]['conversion_rate_new'] = empty($data[$time]['success_money_new']) ? '0%' : bcdiv($money_new, $data[$time]['success_money_new'], 4) * 100 . "%";

                $data[$time]['conversion_rate_new_14'] = empty($data[$time]['success_money_new_14']) ? '0%' : bcdiv($money_new_14, $data[$time]['success_money_new_14'], 4) * 100 . "%";
                $data[$time]['conversion_rate_new_7'] = empty($data[$time]['success_money_new_7']) ? '0%' : bcdiv($money_new_7, $data[$time]['success_money_new_7'], 4) * 100 . "%";
            } else {
                $data[$time]['conversion_rate'] = "-%";
                $data[$time]['conversion_rate_old'] = "-%";
                $data[$time]['conversion_rate_new'] = "-%";
                $data[$time]['conversion_rate_new_14'] = "-%";
                $data[$time]['conversion_rate_new_7'] = "-%";
                $data[$time]['dc_money'] = "0";
                $data[$time]['dc_money_old'] = "0";
                $data[$time]['dc_money_new'] = "0";
            }
        }
        $total_data = [
            'all_loan_money' => $all_loan_money,
            'all_overdue_money' => $all_overdue_money,
            'all_repayment_money' => $all_repayment_money,
            'all_overdue_rate' => empty($all_loan_money) ? "0%" : bcdiv($all_overdue_money, $all_loan_money, 4) * 100 . "%",
            'all_repayment_rate' => empty($all_loan_money) ? "0%" : bcdiv($all_repayment_money, $all_loan_money, 4) * 100 . "%",
            'old_loan_money' => $old_loan_money,
            'old_overdue_money' => $old_overdue_money,
            'old_overdue_rate' => empty($old_loan_money) ? "0%" : bcdiv($old_overdue_money, $old_loan_money, 4) * 100 . "%",
            'old_repayment_rate' => empty($old_loan_money) ? "0%" : bcdiv($old_repayment_money, $old_loan_money, 4) * 100 . "%",
            'new_loan_money' => $new_loan_money,
            'new_overdue_money' => $new_overdue_money,
            'new_overdue_rate' => empty($new_loan_money) ? "0%" : bcdiv($new_overdue_money, $new_loan_money, 4) * 100 . "%",
            'new_repayment_rate' => empty($new_loan_money) ? "0%" : bcdiv($new_repayment_money, $new_loan_money, 4) * 100 . "%",
            'new_loan_money_14' => $new_loan_money_14,
            'new_overdue_money_14' => $new_overdue_money_14,
            'new_loan_money_7' => $new_loan_money_7,
            'new_overdue_money_7' => $new_overdue_money_7,
            'new_overdue_rate_14' => empty($new_loan_money_14) ? "0%" : bcdiv($new_overdue_money_14, $new_loan_money_14, 4) * 100 . "%",
            'new_overdue_rate_7' => empty($new_loan_money_7) ? "0%" : bcdiv($new_overdue_money_7, $new_loan_money_7, 4) * 100 . "%",
            'all_rc_rate' => empty($all_zc_money) ? "0%" : sprintf("%0.2f", (($all_loan_money - $all_zc_money) / $all_loan_money) * 100) . "%",
            'all_zc_money' => $all_zc_money,
            'all_zc_money_new' => $all_zc_money_new,
            'all_zc_money_old' => $all_zc_money_old,
        ];
        return $this->render("daily-repayments-data", [
            'data' => $data,
            'total_data' => $total_data,
            'channel' => $channel,
            'sub_order_type' => $sub_order_type,
        ]);
    }

    /**
     * @name 数据分析 -小钱包数据管理-每日还款金额数据2/actionDailyRepaymentsData
     *
     */
    public function actionDailyRepaymentsData2() {
        $all_loan_money = 0;          //到期总金额
        $all_repayment_money = 0;     //还款总金额
        $all_overdue_money = 0;       //逾期总金额
        $old_loan_money = 0;          //老用户到期金额
        $old_repayment_money = 0;     //老用户还款金额
        $old_overdue_money = 0;       //老用户逾期金额
        $new_loan_money = 0;          //新用户到期金额
        $new_loan_money_7 = 0;        //新用户7天到期金额
        $new_loan_money_14 = 0;       //新用户14天到期金额
        $new_repayment_money = 0;     //新用户还款金额
        $new_overdue_money = 0;       //新用户逾期金额
        $new_overdue_money_7 = 0;     //新用户7天逾期金额
        $new_overdue_money_14 = 0;     //新用户14天逾期金额
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $sub_order_type = $this->request->get('sub_order_type', '-1');
        $channel = $this->request->get('channel');
        $where = ' 1= 1 ';
        if ($add_start) {
            $where = $where . ' and r.plan_fee_time>=' . strtotime($add_start);
        } else {
            $add_start = date('Y-m-d', time() - 7*86400);
            $where = $where . ' and r.plan_fee_time>=' . strtotime($add_start);
        }
        if ($add_end) {
            $add_end = strtotime($add_end) + 86400;
            $where = $where . ' and r.plan_fee_time<' . $add_end;
        } else {
            $add_end = strtotime(date('Y-m-d', time())) + 2*86400;
            $where = $where . ' and r.plan_fee_time<' . $add_end;
        }
        if ($sub_order_type != UserLoanOrder::SUB_TYPE_ALL) {
            $where = $where . ' and l.sub_order_type = ' . intval($sub_order_type);
        }
        $data = [];
        $user_loan_order_repayment = UserLoanOrderRepayment::find()
            ->from(UserLoanOrderRepayment::tableName() . 'as r')
            ->leftJoin(UserLoanOrder::tableName() . 'as l', 'r.order_id=l.id')
            ->where($where)
            ->select('r.true_total_money,r.plan_fee_time,r.status,r.is_overdue,r.overdue_day,r.principal,l.is_first,l.loan_method,l.loan_term')
            ->orderBy(['r.plan_fee_time' => SORT_DESC])
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        foreach ($user_loan_order_repayment as $item) {
            $is_first = $item['is_first'];
            if ($is_first) {
                $old = 0;
            } else {
                $old = 1;
            }
            $time = date('Y-m-d', $item['plan_fee_time']);
            if (!isset($data[$time])) {
                $data[$time] = [
                    'success_money' => 0,
                    'success_money_old' => 0,
                    'success_money_new' => 0,
                    'success_money_new_7' => 0,
                    'success_money_new_14' => 0,
                    'dc_money' => 0,
                    'dc_money_old' => 0,
                    'dc_money_new' => 0,
                    'd1_money' => 0,
                    'd1_money_old' => 0,
                    'd1_money_new' => 0,
                    'd2_money' => 0,
                    'd2_money_old' => 0,
                    'd2_money_new' => 0,
                    'd3_money' => 0,
                    'd3_money_old' => 0,
                    'd3_money_new' => 0,
                    'dn_money' => 0,
                    'dn_money_old' => 0,
                    'dn_money_new' => 0,
                    'dn_money_new_7' => 0,
                    'dn_money_new_14' => 0,
                    'da_money' => 0,
                    'da_money_old' => 0,
                    'da_money_new' => 0,
                    'repay_money' => 0,
                    'repay_money_old' => 0,
                    'repay_money_new' => 0,
                    'true_repay_money' => 0,
                    'true_repay_money_old' => 0,
                    'true_repay_money_new' => 0,
                ];
            }
            $data[$time]['success_money'] += $item['principal'] / 100;
            $all_loan_money += $item['principal'] / 100;
            if ($old) {
                $data[$time]['success_money_old'] += $item['principal'] / 100;
                $old_loan_money += $item['principal'] / 100;
            } else {
                $data[$time]['success_money_new'] += $item['principal'] / 100;
                $new_loan_money += $item['principal'] / 100;
            }
            if ($item['loan_method'] == 0 && $item['loan_term'] == 14) {
                $data[$time]['success_money_new_14'] += $item['principal'] / 100;
                $new_loan_money_14 += $item['principal'] / 100;
            } elseif ($item['loan_method'] == 0 && $item['loan_term'] == 7) {
                $data[$time]['success_money_new_7'] += $item['principal'] / 100;
                $new_loan_money_7 += $item['principal'] / 100;
            }
            if ($item['is_overdue'] && $item['overdue_day'] > 0 && $item['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $data[$time]['dn_money'] += $item['principal'] / 100;
                $all_overdue_money += $item['principal'] / 100;
                if ($old) {
                    $data[$time]['dn_money_old'] += $item['principal'] / 100;
                    $old_overdue_money += $item['principal'] / 100;
                } else {
                    $data[$time]['dn_money_new'] += $item['principal'] / 100;
                    $new_overdue_money += $item['principal'] / 100;
                }

                if ($item['loan_method'] == 0 && $item['loan_term'] == 14) {
                    $data[$time]['dn_money_new_14'] += $item['principal'] / 100;
                    $new_overdue_money_14 += $item['principal'] / 100;
                } elseif ($item['loan_method'] == 0 && $item['loan_term'] == 7) {
                    $data[$time]['dn_money_new_7'] += $item['principal'] / 100;
                    $new_overdue_money_7 += $item['principal'] / 100;
                }
            }
            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $data[$time]['repay_money'] += $item['principal'] / 100;
                $data[$time]['true_repay_money'] += $item['true_total_money'] / 100;
                $all_repayment_money += $item['principal'] / 100;
                if ($old) {
                    $data[$time]['repay_money_old'] += $item['principal'] / 100;
                    $data[$time]['true_repay_money_old'] += $item['true_total_money'] / 100;
                    $old_repayment_money += $item['principal'] / 100;
                } else {
                    $data[$time]['repay_money_new'] += $item['principal'] / 100;
                    $data[$time]['true_repay_money_new'] += $item['true_total_money'] / 100;
                    $new_repayment_money += $item['principal'] / 100;
                }
            }
            if (!empty($data[$time]['success_money'])) {
                $data[$time]['repay_rate'] = empty($data[$time]['success_money']) ? '0%' : bcdiv($data[$time]['repay_money'], $data[$time]['success_money'], 4) * 100 >= 100 ? 100 . '%' : bcdiv($data[$time]['repay_money'], $data[$time]['success_money'], 4) * 100 . "%";
                $data[$time]['repay_rate_old'] = empty($data[$time]['success_money_old']) ? '0%' : bcdiv($data[$time]['repay_money_old'], $data[$time]['success_money_old'], 4) * 100 >= 100 ? 100 . '%' : bcdiv($data[$time]['repay_money_old'], $data[$time]['success_money_old'], 4) * 100 . "%";
                $data[$time]['repay_rate_new'] = empty($data[$time]['success_money_new']) ? '0%' : bcdiv($data[$time]['repay_money_new'], $data[$time]['success_money_new'], 4) * 100 >= 100 ? 100 . '%' : bcdiv($data[$time]['repay_money_new'], $data[$time]['success_money_new'], 4) * 100 . "%";

                $money = 0;
                $money_old = 0;
                $money_new = 0;
                $money_new_14 = 0;
                $money_new_7 = 0;
                if ($data[$time]['dn_money'] > 0) {
                    $money = $data[$time]['dn_money'];
                    if ($data[$time]['dn_money_old'] > 0) {
                        $money_old = $data[$time]['dn_money_old'];
                    }
                    if ($data[$time]['dn_money_new'] > 0) {
                        $money_new = $data[$time]['dn_money_new'];
                    }
                    if ($data[$time]['dn_money_new_14'] > 0) {
                        $money_new_14 = $data[$time]['dn_money_new_14'];
                    }
                    if ($data[$time]['dn_money_new_7'] > 0) {
                        $money_new_7 = $data[$time]['dn_money_new_7'];
                    }
                }

                if ($data[$time]['repay_money'] && $money) {
                    $money = $data[$time]['success_money'] - $data[$time]['repay_money'];
                }
                $data[$time]['dc_money'] = $money;
                $data[$time]['dc_money_old'] = $money_old;
                $data[$time]['dc_money_new'] = $money_new;
                $data[$time]['conversion_rate'] = empty($data[$time]['success_money']) ? '0%' : bcdiv($money, $data[$time]['success_money'], 4) * 100 . "%";
                $data[$time]['conversion_rate_old'] = empty($data[$time]['success_money_old']) ? '0%' : bcdiv($money_old, $data[$time]['success_money_old'], 4) * 100 . "%";
                $data[$time]['conversion_rate_new'] = empty($data[$time]['success_money_new']) ? '0%' : bcdiv($money_new, $data[$time]['success_money_new'], 4) * 100 . "%";

                $data[$time]['conversion_rate_new_14'] = empty($data[$time]['success_money_new_14']) ? '0%' : bcdiv($money_new_14, $data[$time]['success_money_new_14'], 4) * 100 . "%";
                $data[$time]['conversion_rate_new_7'] = empty($data[$time]['success_money_new_7']) ? '0%' : bcdiv($money_new_7, $data[$time]['success_money_new_7'], 4) * 100 . "%";
            } else {
                $data[$time]['conversion_rate'] = "-%";
                $data[$time]['conversion_rate_old'] = "-%";
                $data[$time]['conversion_rate_new'] = "-%";
                $data[$time]['conversion_rate_new_14'] = "-%";
                $data[$time]['conversion_rate_new_7'] = "-%";
                $data[$time]['dc_money'] = "0";
                $data[$time]['dc_money_old'] = "0";
                $data[$time]['dc_money_new'] = "0";
            }
        }
        $total_data = [
            'all_loan_money' => $all_loan_money,
            'all_overdue_money' => $all_overdue_money,
            'all_repayment_money' => $all_repayment_money,
            'all_overdue_rate' => empty($all_loan_money) ? "0%" : bcdiv($all_overdue_money, $all_loan_money, 4) * 100 . "%",
            'all_repayment_rate' => empty($all_loan_money) ? "0%" : bcdiv($all_repayment_money, $all_loan_money, 4) * 100 >= 100 . '%' ? 100 . '%' : bcdiv($all_repayment_money, $all_loan_money, 4) * 100 . "%",
            'old_loan_money' => $old_loan_money,
            'old_overdue_money' => $old_overdue_money,
            'old_overdue_rate' => empty($old_loan_money) ? "0%" : bcdiv($old_overdue_money, $old_loan_money, 4) * 100 . "%",
            'old_repayment_money' => $old_repayment_money,
            'old_repayment_rate' => empty($old_loan_money) ? "0%" : bcdiv($old_repayment_money, $old_loan_money, 4) * 100 >= 100 . '%' ? 100 . '%' : bcdiv($old_repayment_money, $old_loan_money, 4) * 100 . "%",
            'new_loan_money' => $new_loan_money,
            'new_overdue_money' => $new_overdue_money,
            'new_overdue_rate' => empty($new_loan_money) ? "0%" : bcdiv($new_overdue_money, $new_loan_money, 4) * 100 . "%",
            'new_repayment_money' => $new_repayment_money,
            'new_repayment_rate' => empty($new_loan_money) ? "0%" : bcdiv($new_repayment_money, $new_loan_money, 4) * 100 >= 100 . '%' ? 100 . '%' : bcdiv($new_repayment_money, $new_loan_money, 4) * 100 . "%",
            'new_loan_money_14' => $new_loan_money_14,
            'new_overdue_money_14' => $new_overdue_money_14,
            'new_loan_money_7' => $new_loan_money_7,
            'new_overdue_money_7' => $new_overdue_money_7,
            'new_overdue_rate_14' => empty($new_loan_money_14) ? "0%" : bcdiv($new_overdue_money_14, $new_loan_money_14, 4) * 100 . "%",
            'new_overdue_rate_7' => empty($new_loan_money_7) ? "0%" : bcdiv($new_overdue_money_7, $new_loan_money_7, 4) * 100 . "%",
        ];
        return $this->render("daily-repayments-data2", [
            'data' => $data,
            'total_data' => $total_data,
            'channel' => $channel,
            'sub_order_type' => $sub_order_type,
        ]);
    }

    /**
     * @name 数据分析 -财务数据-每日还款金额数据-渠道分销/actionDailyLoanDataChannel
     */
    public function actionDailyRepaymentsDataChannel() {
        $channel = Yii::$app->params['DistributionChannel'];
        $admin_user = Yii::$app->user->identity->username;
        foreach ($channel as $value) {
            foreach ($value['username'] as $item) {
                $admin_name[] = $item;
            }
            if (in_array($admin_user, $value['username'])) {
                $_GET['sub_order_type'] = $value['sub_order_type'];
            }
        }
        if (!in_array($admin_user, $admin_name)) {
            return $this->redirectMessage('请配置渠道', self::MSG_ERROR);
        }
        $_GET['channel'] = 1;
        return $this->actionDailyRepaymentsData();
    }


    /**
     * @name 数据分析-财务数据-每日还款单数数据/每日还款金额数据/每日到期还款续借率/actionDayDataStatistics
     * 每日到期还款续借率数据(到期金额-到期笔数-当日还款金额-当日还款笔数-续借金额-续借笔数-当日还款率-当日续借率)/actionDayDataStatistics
     */
    public function actionDayDataStatistics($type) {

        $db= \yii::$app->db_stats;
        $condition = ' 1=1 ';
        $search = $this->request->get();
        if (!empty($search['begin_created_at'])) {
            $begin_created_at = str_replace(' ', '', $search['begin_created_at']);
        }else{
            $begin_created_at =date('Y-m-d', time() - 7*86400 );
        }
        if (!empty($search['end_created_at'])) {
            $end_created_at = str_replace(' ', '', $search['end_created_at']);
        }else{
            $end_created_at = date('Y-m-d', time() + 86400*2);
        }

        if(isset($search['search_date']) && $search['search_date'] == 1){//1.借款日期；2.还款日期
            $begin_created_at = date('Y-m-d',strtotime($begin_created_at)+14*86400);
            $end_created_at =date('Y-m-d',strtotime($end_created_at)+14*86400);
        }
        if(isset($search['source_type'])&& !empty($search['source_type'])){
            $source=$search['source_type'];
//            $source = implode(',',$search['source_type']);

        }else{
//            $source = 21;
            new LoanPerson();
            $source_register_list=LoanPerson::$source_register_list;
            $source=implode(',',$source_register_list);
            if(empty($source)){
                $source=21;
            }
        }
        $condition .=" and source in ({$source})";
        $condition .= " AND date >= '{$begin_created_at}' ";
        $condition .= " AND date <= '{$end_created_at}' ";

        $info = StatisticsDayData::find()->where($condition)->orderBy('date DESC')->asArray()->all($db);
//        $countQuery = clone $query;
//        $pages = new Pagination(['totalCount' => $countQuery->count('*', $db)]);
//        $pages->pageSize = 105;
//        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
        $data = $total_data = [];
        $today_time = strtotime(date("Y-m-d", time()));
        foreach($info as $value){
            $date=$value['date'];
            if($value['user_type']==0){
                $this->_getReturnData($data, $total_data, $date, 0, $value, $today_time);
            }
            if(isset($value['user_type']) && $value['user_type']==1){
                $this->_getReturnData($data, $total_data, $date, 1, $value, $today_time);
            }
            if(isset($value['user_type']) && $value['user_type']==2){
                $this->_getReturnData($data, $total_data, $date, 2, $value, $today_time);
            }
            $data[$date]['unix_time_key'] = strtotime($value['date']);
            $data[$date]['time_key'] = $value['date'];
            $data[$date]['created_time'] = date('n-j',strtotime($value['date'])-7*86400);
        }

        if($type == 'today'){
            $views = 'day-data-statistics';
        }
        if($type == 'all'){
            $views = 'day-data-statistics2';
        }
        if($type == 'loan_num'){
            $views = 'daily-loan-data';
        }
        if($type == 'loan_money'){
            $views = 'daily-repayments-data';
        }
        if ($this->request->get('submitcsv') == 'exportnum') {
            return $this->_exportDailyLoanData($data);
        }
        if ($this->request->get('submitcsv') == 'exportmoney') {
            return $this->_exportDailyRepaymentData($data);
        }
        if($type == 'collection_list'){
            $key ='collectiondata:'.$begin_created_at.'-'.$end_created_at;
            $data = \Yii::$app->cache->get($key);
            $data = json_decode($data,true);
            $views = 'daily-collection-data';
        }
        if ($this->request->get('submitcsv') == 'exportcollection') {
            return $this->_exportCollectionData($data);
        }
        $date = date('Y-m-d');

        //获取脚本最后更新时间
        $last_update_query = StatisticsDayData::findBySql("SELECT updated_at FROM tb_statistics_day_data where date='{$date}' LIMIT 1")->one($db);
        $update_time = (!empty($last_update_query['updated_at'])) ? date("Y-m-d H:i:s",$last_update_query['updated_at']) : '';
        return $this->render($views, [
                'info' => $data,
                'total_info' => $total_data,
                'pages' => [],
                'update_time'=>$update_time
            ]
        );
    }

    /**
     * 统一组装返回数据
     * @param $data
     * @param $total_data
     * @param $date
     * @param $type
     * @param $value
     */
    private function _getReturnData(&$data, &$total_data, $date, $type, $value, $today_time){

        //按天
        $expire_num = $value['expire_num'] ?? 0;
        $expire_money = $value['expire_money'] ?? 0;
        $repay_num = $value['repay_num'] ?? 0;
        $repay_money = $value['repay_money'] ?? 0;
        $repay_xj_num = $value['repay_xj_num'] ?? 0;
        $repay_xj_money = $value['repay_xj_money'] ?? 0;
        $repay_zc_num = $value['repay_zc_num'] ?? 0;
        $repay_zc_money = $value['repay_zc_money'] ?? 0;
        $repay_zcxj_num = $value['repay_zcxj_num'] ?? 0;
        $repay_zcxj_money = $value['repay_zcxj_money'] ?? 0;
        $zcxj_rate = $value['zcxj_rate'] ?? 0;
        $xj_rate = $value['xj_rate'] ?? 0;
        if(isset($data[$date]['expire_num_'.$type])){
            $data[$date]['expire_num_'.$type] += $expire_num;
        }else{
            $data[$date]['expire_num_'.$type] = $expire_num;
        }

        if(isset($data[$date]['expire_money_'.$type])){
            $data[$date]['expire_money_'.$type] += $expire_money;
        }else{
            $data[$date]['expire_money_'.$type] = $expire_money;
        }

        if(isset($data[$date]['repay_num_'.$type])){
            $data[$date]['repay_num_'.$type] += $repay_num;
        }else{
            $data[$date]['repay_num_'.$type] = $repay_num;
        }

        if(isset($data[$date]['repay_money_'.$type])){
            $data[$date]['repay_money_'.$type] += $repay_money;
        }else{
            $data[$date]['repay_money_'.$type] = $repay_money;
        }

        if(isset($data[$date]['repay_xj_num_'.$type])){
            $data[$date]['repay_xj_num_'.$type] += $repay_xj_num;
        }else{
            $data[$date]['repay_xj_num_'.$type] = $repay_xj_num;
        }

        if(isset($data[$date]['repay_xj_money_'.$type])){
            $data[$date]['repay_xj_money_'.$type] += $repay_xj_money;
        }else{
            $data[$date]['repay_xj_money_'.$type] = $repay_xj_money;
        }

        if(isset($data[$date]['repay_zc_num_'.$type])){
            $data[$date]['repay_zc_num_'.$type] += $repay_zc_num;
        }else{
            $data[$date]['repay_zc_num_'.$type] = $repay_zc_num;
        }

        if(isset($data[$date]['repay_zc_money_'.$type])){
            $data[$date]['repay_zc_money_'.$type] += $repay_zc_money;
        }else{
            $data[$date]['repay_zc_money_'.$type] = $repay_zc_money;
        }

        if(isset($data[$date]['repay_zcxj_num_'.$type])){
            $data[$date]['repay_zcxj_num_'.$type] += $repay_zcxj_num;
        }else{
            $data[$date]['repay_zcxj_num_'.$type] = $repay_zcxj_num;
        }

        if(isset($data[$date]['repay_zcxj_money_'.$type])){
            $data[$date]['repay_zcxj_money_'.$type] += $repay_zcxj_money;
        }else{
            $data[$date]['repay_zcxj_money_'.$type] = $repay_zcxj_money;
        }

        if(isset($data[$date]['zcxj_rate_'.$type])){
            $data[$date]['zcxj_rate_'.$type] += $zcxj_rate;
        }else{
            $data[$date]['zcxj_rate_'.$type] = $zcxj_rate;
        }

        if(isset($data[$date]['xj_rate_'.$type])){
            $data[$date]['xj_rate_'.$type] += $xj_rate;
        }else{
            $data[$date]['xj_rate_'.$type] = $xj_rate;
        }

        //汇总
        $total_expire_num = $total_data['expire_num_'.$type] ?? 0;
        $total_expire_money = $total_data['expire_money_'.$type] ?? 0;
        $total_repay_num = $total_data['repay_num_'.$type] ?? 0;
        $total_repay_money = $total_data['repay_money_'.$type] ?? 0;
        $total_repay_zc_num = $total_data['repay_zc_num_'.$type] ?? 0;
        $total_repay_zc_money = $total_data['repay_zc_money_'.$type] ?? 0;
        $total_data['expire_num_'.$type] = $total_expire_num + $expire_num;
        $total_data['expire_money_'.$type] = $total_expire_money + $expire_money;
        $total_data['repay_num_'.$type] = $total_repay_num + $repay_num;
        $total_data['repay_money_'.$type] = $total_repay_money + $repay_money;
        $total_data['repay_zc_num_'.$type] = $total_repay_zc_num + $repay_zc_num;
        $total_data['repay_zc_money_'.$type] = $total_repay_zc_money + $repay_zc_money;

        //汇总（时间大于今天的不累加）
        $t_total_expire_num = $total_data['t_expire_num_'.$type] ?? 0;
        $t_total_expire_money = $total_data['t_expire_money_'.$type] ?? 0;
        $t_total_repay_num = $total_data['t_repay_num_'.$type] ?? 0;
        $t_total_repay_money = $total_data['t_repay_money_'.$type] ?? 0;
        $t_total_repay_zc_num = $total_data['t_repay_zc_num_'.$type] ?? 0;
        $t_total_repay_zc_money = $total_data['t_repay_zc_money_'.$type] ?? 0;
        if($today_time > strtotime($date)){
            $total_data['t_expire_num_'.$type] = $t_total_expire_num + $expire_num;
            $total_data['t_expire_money_'.$type] = $t_total_expire_money + $expire_money;
            $total_data['t_repay_num_'.$type] = $t_total_repay_num + $repay_num;
            $total_data['t_repay_money_'.$type] = $t_total_repay_money + $repay_money;
            $total_data['t_repay_zc_num_'.$type] = $t_total_repay_zc_num + $repay_zc_num;
            $total_data['t_repay_zc_money_'.$type] = $t_total_repay_zc_money + $repay_zc_money;
        }
        unset($data);
        unset($total_data);
    }

    private function _exportDailyLoanData($datas){
        $this->_setcsvHeader('还款数据.csv');
        foreach($datas as $key=> $value){
            $items[] = [
                '日期'=>$key,
                '到期单数' =>$value['expire_num_0'],
                '还款单数' =>$value['repay_num_0'],
                '逾期率' =>empty($value['expire_num_0'])?'0':sprintf("%0.2f",($value['expire_num_0']-$value['repay_num_0'])/$value['expire_num_0']*100)."%",
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    private function _exportDailyRepaymentData($datas){
        $this->_setcsvHeader('还款数据.csv');
        foreach($datas as $key=> $value){
            $items[] = [
                '日期'=>$key,
                '到期今日' =>$value['expire_money_0'] / 100,
                '还款金额' =>$value['repay_money_0'] / 100,
                '逾期率' =>empty($value['expire_money_0'])?'0':sprintf("%0.2f",($value['expire_money_0']-$value['repay_money_0'])/$value['expire_money_0']*100)."%",
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }
    private function _exportCollectionData($datas){
        $this->_setcsvHeader('cs_yq_data.csv');
        foreach($datas as $key=> $value){
            $items[] = [
                '日期'=>$key,
                '到期单数' =>$value['expire_num'],
                '到期金额_本金' =>$value['expire_money'],
                '逾期单数' =>$value['overdue_num'],
                '逾期金额_本金' =>$value['overdue_money'],
                '逾期已还_单数' =>$value['overdue_pay_num'],
                '逾期已还_本金' =>$value['overdue_pay_money'],
                '逾期率_单数' =>$value['overdue_rate_num'],
                '逾期率_金额' =>$value['overdue_rate_money'],
                '首日逾期单数' =>$value['overdue_first_num'],
                '首日逾期本金' =>$value['overdue_first_money'],
                '逾期当日还款单数' =>$value['overdue_repay_num'],
                '逾期当日还款本金' =>$value['overdue_repay_money'],
                '逾期当日还款总额' =>$value['overdue_repay_total_money'],
                '入催单数_人工催收' =>$value['rush_num'],
                '入催本金_人工催收' =>$value['rush_money'],
                '人工催收_催回单数' =>$value['rush_back_num'],
                '人工催收_催回本金' =>$value['rush_back_money'],
                '本金催回率_金额' =>$value['rush_rate_money'],
                '人工催收成功_总滞纳金' =>$value['late_all_fee'],
                '人工催收成功_已还滞纳金' =>$value['late_pay_fee'],
                '人工催收成功_未还滞纳金' =>$value['late_other_fee'],
                '滞纳金催回率' =>$value['late_fee_rate'],
                'S1级逾期单数' =>$value['s1_overdue_num'],
                'S1级逾期金额' =>$value['s1_overdue_money'],
                'S1级催回单数' =>$value['s1_overdue_back_num'],
                'S1级催回金额' =>$value['s1_overdue_back_money'],
                'S2级逾期单数' =>$value['s2_overdue_num'],
                'S2级逾期金额' =>$value['s2_overdue_money'],
                'S2级催回单数' =>$value['s2_overdue_back_num'],
                'S2级催回金额' =>$value['s2_overdue_back_money'],
                'M1级逾期单数' =>$value['m1_overdue_num'],
                'M1级逾期金额' =>$value['m1_overdue_money'],
                'M1级催回单数' =>$value['m1_overdue_back_num'],
                'M1级催回金额' =>$value['m1_overdue_back_money'],
                '坏账率_单数'=>$value['bad_rate_num'],
                '坏账率_金额'=>$value['bad_rate_money'],
            ];
        }
        echo $this->_array2csv($items);
        \yii::$app->end();
    }


    /**
     * @name 每日复借数据-ajax获取累计借款次数为n次的用户
     *
     */
    public function actionGetActivityUser(){
        $db = \Yii::$app->db_kdkj_rd;
        $add_start = strtotime($this->request->get("time_begin"));
        $add_end = strtotime($this->request->get("time_end"))+86400;
        $loan_num_init = $this->request->get('loan_num');

        $sql = "select t1.user_id,count(t2.user_id) as count from tb_user_loan_order as t1
                left join tb_user_loan_order_repayment as t2 on (t1.user_id = t2.user_id and t1.created_at>t2.created_at)
                where t1.created_at<{$add_end} and t1.created_at>{$add_start}
                GROUP BY t1.id HAVING  count>={$loan_num_init}";
        $data = $db->createCommand($sql)->queryAll();
        $num = count($data);

        $this->response->format = Response::FORMAT_JSON;
        return [
            'ret_data1' => $num,
        ];
    }

    /**
     * @name 数据分析 -运营数据-每月申请放款续借数据/actionMonthDataStatistics
     */
    public function actionMonthDataStatistics() {
        $condition = ' 1=1 AND apply_person_num > 0 ';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['begin_created_at'])) {
                $begin_created_at = str_replace(' ', '', $search['begin_created_at']);
                $condition .= " AND month >= '{$begin_created_at}' ";
            }
            if (!empty($search['end_created_at'])) {
                $end_created_at = str_replace(' ', '', $search['end_created_at']);
                $condition .= " AND month <= '{$end_created_at}' ";
            }
        }
        $query = StatisticsMonthData::find($condition)->where($condition)->orderBy('month DESC');

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj'))]);
        $pages->pageSize = 10;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj'));

        return $this->render('month-data-statistics', [
                'info' => $info,
                'pages' => $pages,
            ]
        );
    }

    /**
     * @name 数据分析 -运营数据-每月分段逾期数据/actionMonthDataStatistics2
     */
    public function actionMonthDataStatistics2() {
        $condition = ' 1=1 ';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['begin_created_at'])) {
                $begin_created_at = str_replace(' ', '', $search['begin_created_at']);
                $condition .= " AND month >= '{$begin_created_at}' ";
            }
            if (!empty($search['end_created_at'])) {
                $end_created_at = str_replace(' ', '', $search['end_created_at']);
                $condition .= " AND month <= '{$end_created_at}' ";
            }
        }
        $query = StatisticsMonthData::find($condition)->where($condition)->orderBy('month DESC');

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj'))]);
        $pages->pageSize = 10;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj'));

        return $this->render('month-data-statistics2', [
                'info' => $info,
                'pages' => $pages,
            ]
        );
    }

    /**
     * @name 数据分析 -用户短信内容验证/actionUserCheckMessage
     */
    public function actionUserCheckMessage($user_id) {
        $user = UserPhoneMessage::find()->where(['user_id' => $user_id])->asArray()->all();
        $rules = UserCheckMessage::find()->asArray()->all();
        foreach ($rules as $item) {
            foreach ($user as $v) {
                $res = preg_match($item['match_rule'], $v['message_content']);
                if ($res) {
                    echo "有";
                    return false;
                }
            }
        }
        echo "没有";
    }


    /**
     * 统计累计到今日凌晨的各项还款率分析数据
     * @name 数据分析 -运营数据-每日还款分析列表/actionRepayRatesList
     */
    public function actionRepayRatesList() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $condition = ' 1= 1 and type=0 AND fund_id = 0';
        if (!empty($add_start)) {
            $condition = $condition . " and date>='" . $add_start . "'";
        }else{
            $add_start = date('Y-m-d', time() - 7*86400);;
            $condition = $condition . " and date>='" . $add_start . "'";
        }

        if (!empty($add_end)) {
            $condition = $condition . " and date <'" . $add_end . "'";
        }else {
            $add_end = date('Y-m-d', time() + 86400);
            $condition = $condition . " and date <'" . $add_end . "'";
        }

        $query = RepayRatesList::find()->where($condition)->orderBy([RepayRatesList::tableName() . '.date' => SORT_DESC]);

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render("repay-rates-list", [
            'data' => $data,
            'pages' => $pages,
        ]);
    }


    function getAgeByID($id) {

//过了这年的生日才算多了1周岁
        if (empty($id))
            return '';
        $date = strtotime(substr($id, 6, 8));
//获得出生年月日的时间戳
        $today = strtotime('today');
//获得今日的时间戳
        $diff = floor(($today - $date) / 86400 / 365);
//得到两个日期相差的大体年数
//strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

        return $age;
    }


    /**
     * @name 数据统计-->数据管理-->用户数据综合统计图
     */
    public function actionUserStatChart(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $return_data = [];
        $now_date = date("Y-m-d", time());
//        $start_date = $this->request->get('start_date', date('Y-m-d', strtotime('-7 day')));
//        $end_date = $this->request->get('end_date', date('Y-m-d', strtotime('-1 day')));
        $start_date = $this->request->get('start_date', date('Y-m-d', strtotime('-7 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d', time()));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);
        $end_time_2 = $end_time + 86400;

        //1.获取用户统计信息,并重置用户统计信息
        $query_user_condition = " date>='{$start_date}' AND date <= '{$end_date}' AND source = -1";
        $user_data = StatisticsVerification::find()->where($query_user_condition)->orderBy('date DESC')->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
        //$sql = StatisticsVerification::find()->where($query_user_condition)->orderBy('date DESC')->createCommand()->getRawSql(); echo $sql;die;
        $user_data_reset = [];
        if(!empty($user_data)){
            foreach ($user_data as $key => $user){
                $k = $user['date'];
                $user_data_reset[$k] = $user;
            }
        }

        //2.获取放款信息，并重置放款信息
        $loan_order_sql = "SELECT date_time, SUM(loan_num_7) AS loan_num_7, SUM(loan_money_7) AS loan_money_7,
                                  SUM(loan_num_new) AS loan_num_new, SUM(loan_money_new) AS loan_money_new,
                                  SUM(loan_num_old) AS loan_num_old, SUM(loan_money_old) AS loan_money_old FROM tb_statistics_loan_copy";
        $loan_order_sql .= " WHERE app_type = 0 AND date_time >={$start_time} AND date_time < {$end_time_2}  GROUP BY date_time ORDER BY date_time DESC";
        //echo $loan_order_sql;die;
        $loan_order_data = StatisticsLoan::findBySql($loan_order_sql)->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
        $loan_order_data_reset = [];
        if(!empty($loan_order_data)){
            foreach ($loan_order_data as $key => $loan){
                $k = date("Y-m-d", $loan['date_time']);
                $loan_order_data_reset[$k] = $loan;
            }
        }

        //3.获取逾期信息，，并重置逾期信息
        $query_statistics_condition  = " source = 0 AND date >='{$start_date}' AND date <= '{$end_date}'";
        $delay_data = StatisticsDayData::find()
            ->where($query_statistics_condition)
            ->orderBy('date DESC')
        //$sql = $delay_data->createCommand()->getRawSql(); echo $sql;die;
            ->asArray()
            ->all(Yii::$app->get('db_stats'));
        $delay_data_reset = [];
        if(!empty($delay_data)){
            foreach ($delay_data as $key => $delay){
                $k = $delay['date'];
                //首逾：(到期单数-正常还款单数)/到期单数
                if(empty($delay_data_reset[$k])){
                    $delay_data_reset[$k] = [
                        'success_num' => 0,//总的到期单数
                        'success_num_old' => 0,//老用户的到期单数
                        'success_num_new' => 0,//新用户的到期单数
                        'zc_num' => 0,//总的正常还款单数
                        'zc_num_old' => 0,//老用户的正常还款单数
                        'zc_num_new' => 0,//新用户的正常还款单数
                    ];
                }

                //用户类型；0：所有用户，1:新用户，2：老用户
                if($delay['user_type'] == 1){//新用户
                    $delay_data_reset[$k]['success_num_new'] = $delay['expire_num'];//新用户的到期单数
                    $delay_data_reset[$k]['zc_num_new'] = $delay['repay_zc_num'] ;//新用户的正常还款单数
                }else if($delay['user_type'] == 2){//老用户
                    $delay_data_reset[$k]['success_num_old'] = $delay['expire_num'] ;//老用户的到期单数
                    $delay_data_reset[$k]['zc_num_old'] = $delay['repay_zc_num'] ;//老用户的正常还款单数
                }else{//总的用户
                    $delay_data_reset[$k]['success_num'] = $delay['expire_num'] ;//总的到期单数
                    $delay_data_reset[$k]['zc_num'] = $delay['repay_zc_num'] ;//总的正常还款单数
                }
            }
        }

        //4.每日催收
        $late_fee_data = OrderStatisticsByDay::lists($start_time, $end_time_2);
        $late_fee_data_ret = [];
        if(!empty($late_fee_data)){
            foreach ($late_fee_data as $key => $late_fee){
                $k = date("Y-m-d", $late_fee['create_at']);
                $late_fee_data_ret[$k] = $late_fee;
            }
        }

        for ($i = $end_time; $i >= $start_time; $i -= 86400){
            $key = date("Y-m-d", $i);//日期
            $x[$key] = $key;
            $reg_num = 0;//注册用户数
            $loan_num_7 = 0;//放款单数
            $loan_money_7 = 0;//放款额
            $loan_num_14_new = 0;//新客放款单数
            $loan_money_14_new = 0;//新客放款额
            $loan_num_14_old = 0;//老客放款单数
            $loan_money_14_old = 0;//老客放款额
            $delay_num = 0;//首逾
            $delay_num_old = 0;//老客首逾
            $delay_num_new = 0;//新客首逾
            $repay_late_fee = 0;//实际还款滞纳金
            if(!empty($user_data_reset[$key])){
                $reg_num = $user_data_reset[$key]['reg_num'];
            }
            if(!empty($loan_order_data_reset[$key])){
                //所有用户
                $loan_num_7 = $loan_order_data_reset[$key]['loan_num_7'] ?? 0;
                $loan_money_7 = (!empty($loan_order_data_reset[$key]['loan_money_7'])) ? sprintf("%0.2f",$loan_order_data_reset[$key]['loan_money_7']/100) : 0;

                //新客
                $loan_num_14_new = $loan_order_data_reset[$key]['loan_num_new'] ?? 0;
                $loan_money_14_new = (!empty($loan_order_data_reset[$key]['loan_money_new'])) ? sprintf("%0.2f",$loan_order_data_reset[$key]['loan_money_new']/100) : 0;

                //老客
                $loan_num_14_old = $loan_order_data_reset[$key]['loan_num_old'] ?? 0;
                $loan_money_14_old = (!empty($loan_order_data_reset[$key]['loan_money_old'])) ? sprintf("%0.2f",$loan_order_data_reset[$key]['loan_money_old']/100) : 0;
            }
            if(!empty($delay_data_reset[$key])){
                $success_num = $delay_data_reset[$key]['success_num'];
                $success_num_old = $delay_data_reset[$key]['success_num_old'];
                $success_num_new = $delay_data_reset[$key]['success_num_new'];
                $zc_num = $delay_data_reset[$key]['zc_num'];
                $zc_num_old = $delay_data_reset[$key]['zc_num_old'];
                $zc_num_new = $delay_data_reset[$key]['zc_num_new'];
                if(!empty($success_num)){//所有用户
                    if($key < $now_date){
                        $delay_num = sprintf("%0.2f",($success_num-$zc_num)/$success_num*100);
                    }
                }
                if($success_num_new){//新客首逾
                    if($key < $now_date) {
                        $delay_num_new = sprintf("%0.2f", ($success_num_new - $zc_num_new) / $success_num_new * 100);
                    }
                }
                if($success_num_old){//老客首逾
                    if($key < $now_date) {
                        $delay_num_old = sprintf("%0.2f", ($success_num_old - $zc_num_old) / $success_num_old * 100);
                    }
                }
            }
            if(!empty($late_fee_data_ret[$key])){
                $repay_late_fee = sprintf("%0.2f",$late_fee_data_ret[$key]['repay_late_fee']/100);
            }
            $return_data[$i] = [
                'date' => $key,
                'date_2' => date("n-j", $i),
                'reg_num' => $reg_num,
                'loan_num_7' => $loan_num_7,
                'loan_money_7' => $loan_money_7,
                'loan_num_14_new' => $loan_num_14_new,
                'loan_money_14_new' => $loan_money_14_new,
                'loan_num_14_old' => $loan_num_14_old,
                'loan_money_14_old' => $loan_money_14_old,
                'delay_num' => $delay_num,
                'delay_num_old' => $delay_num_old,
                'delay_num_new' => $delay_num_new,
                'repay_late_fee' => $repay_late_fee,
            ];
        }
        unset($loan_order_data_reset);
        unset($user_data_reset);
        unset($delay_data_reset);
        unset($late_fee_data_ret);
        //组合chart数据
        //放款单数
        $loan_num_maps = [
            '0' => '放款单数',
            '1' => '新客放款单数',
            '2' => '老客放款单数',
        ];
        for ($i = 0; $i < 3; $i++) {
            $loan_num_vals[$i] = array();
        }

        //放款金额
        $loan_money_maps = [
            '0' => '放款金额(元)',
            '1' => '新客放款金额(元)',
            '2' => '老客放款金额(元)',
        ];
        for ($i = 0; $i < 3; $i++) {
            $loan_money_vals[$i] = array();
        }

        //注册用户数
        $reg_num_maps = [
            '0' => '注册用户数',
        ];
        for ($i = 0; $i < 1; $i++) {
            $reg_num_vals[$i] = array();
        }

        //逾期率
        $delay_maps = [
            '0' => '首逾(%)',
            '1' => '新客首逾(%)',
            '2' => '老客首逾(%)',
        ];
        for ($i = 0; $i < 3; $i++) {
            $delay_vals[$i] = array();
        }

        //滞纳金
        $late_fee_maps = [
            '0' => '滞纳金回收（元）',
        ];
        for ($i = 0; $i < 1; $i++) {
            $late_fee_vals[$i] = array();
        }

        $x = [];//图标x坐标轴
        if(!empty($return_data)){
            foreach (array_reverse($return_data) as $key => $vl) {
                $loan_num_vals[0][] = $vl['loan_num_7'];
                $loan_num_vals[1][] = $vl['loan_num_14_new'];
                $loan_num_vals[2][] = $vl['loan_num_14_old'];
                $loan_money_vals[0][] = $vl['loan_money_7'];
                $loan_money_vals[1][] = $vl['loan_money_14_new'];
                $loan_money_vals[2][] = $vl['loan_money_14_old'];
                $reg_num_vals[0][] = $vl['reg_num'];
                $delay_vals[0][] = $vl['delay_num'];
                $delay_vals[1][] = $vl['delay_num_new'];
                $delay_vals[2][] = $vl['delay_num_old'];
                $late_fee_vals[0][] = $vl['repay_late_fee'];
                $x[$key] = date("n月j日",strtotime($vl['date']));
            }
        }

        $loan_num_config = $this->_getLegendAndSeries($loan_num_vals, $loan_num_maps);
        $loan_money_config = $this->_getLegendAndSeries($loan_money_vals, $loan_money_maps);
        $reg_num_config = $this->_getLegendAndSeries($reg_num_vals, $reg_num_maps);
        $delay_config = $this->_getLegendAndSeries($delay_vals, $delay_maps);
        $late_fee_config = $this->_getLegendAndSeries($late_fee_vals, $late_fee_maps);

        return $this->render('user-stat-chart',
            [
                'x' => $x,
                'now_date' => $now_date,
                'list' => $return_data,
                'loan_num_config' => $loan_num_config,
                'loan_money_config' => $loan_money_config,
                'reg_num_config' => $reg_num_config,
                'delay_config' => $delay_config,
                'late_fee_config' => $late_fee_config,
            ]
        );
    }

    private function _getLegendAndSeries($vals, $maps){
        $legend = array_values($maps);
        $series = [];
        for ($j = 0; $j < count($maps); $j++) {
            $series[] = array(
                'name' => $legend[$j],
                'type' => 'line',
                'smooth'=>false,
                'itemStyle' => [
                    'normal'=>[
                        'label'=>[
                            'show'=>true
                        ]
                    ]
                ],
                'data' => $vals[$j],
            );
        }
        return [
            'series' => $series,
            'legend' => $legend,
        ];
    }

    /**
     * 获取公积金订单历史统计数据
     */
    private function _getHistoryGjjStat($add_start, $add_end){
        $history_data = [];
        $history_sql = "SELECT * FROM tb_gjj_order_statistics WHERE statistics_at >= {$add_start} AND statistics_at <= {$add_end}";//历史记录不到100条,直接查询所有的历史记录。
        $history_ret = Yii::$app->db_kdkj->createCommand($history_sql)->queryall();
        if(!empty($history_ret)){
            foreach ($history_ret as $key => $history){
                $date_key = $history['statistics_at'];
                $history_data[$date_key] = $history;
            }
        }
        return $history_data;
    }
}
