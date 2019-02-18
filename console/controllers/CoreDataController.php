<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/19
 * Time: 15:40
 */
namespace console\controllers;

use common\models\AppKeyData;
use common\models\LoanPerson;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\Shop;
use common\models\StatisticsLoan;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderCount;
use common\models\UserOrderLoanCheckLog;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use Helper\Unit;
use Yii;
use yii\base\Exception;
use common\models\UserLoanOrderRepayment;
use common\models\FinancialLoanRecord;
use common\models\StatisticsVerification;
use common\models\StatisticsInoutcome;
use common\models\StatisticsLoseRate;
use common\models\StatisticsRegisterData;
use common\models\UserLoanOrderDelay;
use common\models\UserLoanOrderDelayLog;
use common\models\DailyCodeLog;
use common\services\SendMailAndMassageService;
use common\helpers\MailHelper;
use common\models\StatisticsDayData;
use common\models\StatisticsMonthData;
use common\models\StatisticsDayLose;
use common\models\StatisticsDayLoseRate;
use common\models\loan\LoanCollectionOrder;
use common\models\LoanPersonInvite;
use common\models\LoanPersonInviteRebateDetail;
use common\models\LoanPersonInviteCash;
use common\models\AccumulationFund;
use common\helpers\Util;
use common\helpers\CommonHelper;
use common\base\LogChannel;
use common\models\Channel;

class CoreDataController extends BaseController{
    //复借最大次数
    private $again_max_count = 25;

    public function actionPocketList(){
        $db = \Yii::$app->db_kdkj_rd;
        $userLoanOrderTableName = UserLoanOrder::tableName();
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $time_start =1493222400;// strtotime("today"); //今天零点
        $end_time =1494259200;//$time_start + 86400;
        $_hour = date('H',time());//当前的小时数
        $where = "created_at >= {$time_start} AND created_at < {$end_time} and id>0";
        //如果当前时间为24点，则计算前一天所有的放款数据,显示日期为前一天的24时
        if( $_hour == 0 ){
            $end_time = $time_start;
            $time_start = $end_time-86400;
            $_hour = 24;
        }
        Util::cliLimitChange(1024);
        $date_user = '';
        $user_register_info = UserRegisterInfo::find()->where($where)->select(['user_id', 'date'])->orderBy(" id desc")->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($user_register_info as $item) {
            $date_user .= $item['user_id'] . ',';
        }
        $date_user = substr($date_user, 0, -1);
        $data = [];
        $operator_name=[];
        $sql = "SELECT o.money_amount as money,  o.user_id,o.operator_name,o.created_at from {$userLoanOrderTableName} AS o
            WHERE o.user_id in ({$date_user}) and o.operator_name in ('auto shell','RiskControlController::_csReject','RiskControlController::_fsReject') and o.id>0 ";
        $financial_loan = $db->createCommand($sql)->queryAll();

        foreach($financial_loan as $item)
        {
            $date = date('Y-m-d',$item['created_at']);
            $operator_name[$date][$item['operator_name']][]=$item;
        }

        foreach($operator_name as $date => $items){
            foreach($items as $key =>$item){
                $users_str ='';
                $apply_money = 0;
                $loan_num = 0;
                $loan_num_new = 0;
                $loan_num_old = 0;
                $loan_money = 0;
                $loan_money_new = 0;
                $loan_money_old = 0;
                foreach($item as $value) {
                    $apply_money+=$value['money'];
                    $user_loan_order=LoanPerson::findById($value['user_id']);
                    if($user_loan_order['customer_type']==LoanPerson::CUSTOMER_TYPE_OLD)
                    {
                        $loan_num_old++;
                        $loan_money_old+=$value['money'];
                    }else{
                        $loan_num_new++;
                        $loan_money_new+=$value['money'];
                    }
                    $users_str .= $item['id'] . ',';

                }
                $users_str = substr($users_str, 0, -1);
//                $loan_info = UserLoanOrderRepayment::find()->where(['user_id' => $users_str])->select('sum(principal) as loan_money,count(user_id) as loan_num')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
//                $loan_money = $loan_info['loan_money'];
//                $loan_num = $loan_info['loan_num'];

                $data[$date][$key]['apply_num'] = count($item);//申请单数
                $data[$date][$key]['apply_money']=$apply_money;//申请金额
//                $data[$date][$key]['loan_num']=$loan_num;//放款单数
//                $data[$date][$key]['loan_money']=$loan_money;//放款金额|{$data[$date][$key]['loan_num']}|{$data[$date][$key]['loan_money']}
                $data[$date][$key]['loan_money_new'] = $loan_money_new;//新用户金额
                $data[$date][$key]['loan_num_new'] = $loan_num_new;//新用户数量
                $data[$date][$key]['loan_money_old'] = $loan_money_old;//老用户金额
                $data[$date][$key]['loan_num_old'] = $loan_num_old;//老用户数量

                echo "{$date}|{$key}|{$data[$date][$key]['apply_num']}|{$data[$date][$key]['apply_money']}|{$data[$date][$key]['loan_num_old']}|{$data[$date][$key]['loan_money_old']}|{$data[$date][$key]['loan_num_new']}|{$data[$date][$key]['loan_money_new']}\n";
            }
        }

    }
    /**
     * 统计每日放款
     */
    public  function actionDailyLoan(){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $this->actionDailyLoans(strtotime("today"));
        $this->actionDailyLoanHistory(strtotime("today"));//每日放款区分app类型

    }

    /**
     * 更新银行通知延期导致的放款数据变更
     */
    public  function actionDailyLoan3(){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $start_time = strtotime(date('Y-m-d', time()));
        $countDate = 1; // 2天
        for($datei = 0;$datei<=$countDate;$datei++){
            $dateNum = $start_time-$datei*86400;
            $date = date('Y-m-d',$dateNum);
            $this->actionDailyLoans(strtotime($date));
            $this->actionDailyLoanHistory(strtotime($date));//每日放款区分app类型
        }
    }

    public function actionDailyLoans($time){
        Util::cliLimitChange(1024);
        $db = \Yii::$app->db_kdkj_rd_new;
        $userLoanOrderTableName = UserLoanOrder::tableName();
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $time_start =empty($time)?strtotime("today"):$time; //今天零点
        $end_time =$time_start + 86400;
        $_hour = date('H',time());//当前的小时数

        //如果当前时间为24点，则计算前一天所有的放款数据,显示日期为前一天的24时
        if( $_hour == 0 && date('i',time())<=10 ){
            $end_time = $time_start;
            $time_start = $end_time-86400;
            $_hour = 24;
        }
        $sql = "SELECT o.money_amount as money, o.loan_term, o.loan_method, o.sub_order_type, o.pass_type, o.user_id, o.id,o.is_first FROM {$userLoanOrderTableName} AS o
                WHERE o.loan_time >= {$time_start} AND o.loan_time < {$end_time} and o.id>0 ";
//echo $sql ."\n";
        $financial_loan = $db->createCommand($sql)->queryAll();
        $sub_order_type=[];
        foreach($financial_loan as $item)
        {
            $sub_order_type[$item['sub_order_type']][]=$item;
        }
        $data=[];
        foreach($sub_order_type as $key => $item)
        {
            $data[$key]['loan_num'] = count($item);
            $data[$key]['loan_money']=0;
            $data[$key]['loan_num_old']=0;
            $data[$key]['loan_money_old']=0;
            $data[$key]['loan_num_new']=0;
            $data[$key]['loan_money_new']=0;
            $data[$key]['loan_num_7']=0;
            $data[$key]['loan_money_7']=0;
            $data[$key]['loan_num_14']=0;
            $data[$key]['loan_money_14']=0;
            $data[$key]['gjj_num_14']=0;
            $data[$key]['gjj_num_old_14']=0;
            $data[$key]['gjj_num_new_14']=0;
            $data[$key]['gjj_money_14']=0;
            $data[$key]['gjj_money_old_14']=0;
            $data[$key]['gjj_money_new_14']=0;
            foreach($item as $value) {
                $data[$key]['loan_money']+=$value['money'];
                $loan_person = LoanPerson::find()->where(['id'=>$value['user_id']])->one(\Yii::$app->db_kdkj_rd_new);
                if(empty($loan_person)){
                    \yii::error("LoanPerson is empty", LogChannel::GJJ_ORDER_SCHEDULE);
                    return false;
                }
                //公积金认证判断
                $check = (!empty($value['pass_type']) && ($value['pass_type'] == UserLoanOrder::PASS_TYPE_GJJ || $value['pass_type'] == UserLoanOrder::PASS_TYPE_GJJ_OLD)) ? true : false;
                if($loan_person->customer_type == LoanPerson::CUSTOMER_TYPE_OLD&&$value['is_first']==0)
                {
                    $data[$key]['loan_num_old']++;
                    $data[$key]['loan_money_old']+=$value['money'];
                    if($check){
                        $data[$key]['gjj_num_old_14']++;
                        $data[$key]['gjj_money_old_14']+=$value['money'];
                    }
                }else{
                    $data[$key]['loan_num_new']++;
                    $data[$key]['loan_money_new']+=$value['money'];
                    if($check){
                        $data[$key]['gjj_num_new_14']++;
                        $data[$key]['gjj_money_new_14']+=$value['money'];
                    }
                }
                if($value['loan_term']==7 && $value['loan_method']==0)
                {
                    $data[$key]['loan_num_7']++;
                    $data[$key]['loan_money_7']+=$value['money'];
                }
                if($value['loan_term']==14 && $value['loan_method']==0)
                {
                    $data[$key]['loan_num_14']++;
                    $data[$key]['loan_money_14']+=$value['money'];
                    if($check){
                        $data[$key]['gjj_num_14']++;
                        $data[$key]['gjj_money_14']+=$value['money'];
                    }
                }
            }
        }

        foreach($data as $k=>$v) {
            $statistics_loan = StatisticsLoan::find()
                ->where(['app_type' => 0])
                ->andWhere(['sub_order_type' => $k])
                ->andWhere(['date_time' => $time_start])
                ->one();
            if(empty($statistics_loan)){
                $statistics_loan = new StatisticsLoan();
                $statistics_loan->created_at = time();//创建时间
                $statistics_loan->date_time = $time_start; //日期
            }
            $statistics_loan->loan_num = $v['loan_num'];             //放款单数
            $statistics_loan->loan_num_7 = $v['loan_num_7'];         //7天期限放款单数
            $statistics_loan->loan_num_14 = $v['loan_num_14'];       //14天期限放款单数
            $statistics_loan->loan_num_old = $v['loan_num_old'];     //老用户放款单数
            $statistics_loan->loan_num_new = $v['loan_num_new'];     //新用户放款单数
            $statistics_loan->loan_money = $v['loan_money'];         //放款金额
            $statistics_loan->loan_money_7 = $v['loan_money_7'];     //7天期限放款金额
            $statistics_loan->loan_money_14 = $v['loan_money_14'];   //14天期限放款金额
            $statistics_loan->loan_money_old = $v['loan_money_old']; //老用户放款金额
            $statistics_loan->loan_money_new = $v['loan_money_new']; //新用户放款金额
            $statistics_loan->gjj_num_14 = $v['gjj_num_14'];//公积金放款单数
            $statistics_loan->gjj_num_old_14 = $v['gjj_num_old_14'];//老用户公积金放款单数
            $statistics_loan->gjj_num_new_14 = $v['gjj_num_new_14'];//新用户公积金放款单数
            $statistics_loan->gjj_money_14 = $v['gjj_money_14'];//公积金放款金额
            $statistics_loan->gjj_money_old_14 = $v['gjj_money_old_14'];//老用户公积金放款金额
            $statistics_loan->gjj_money_new_14 = $v['gjj_money_new_14'];//新用户公积金放款金额
            $statistics_loan->app_type = 0;//全部
            $statistics_loan->updated_at = time();
            $statistics_loan->sub_order_type = $k;
            $res = $statistics_loan->save();
            if (!$res) {
                \yii::error("放款数据保存失败[{$time_start}]", LogChannel::GJJ_ORDER_SCHEDULE);
                //Yii::error($time_start . "放款数据保存失败");
            }else{

            }
        }
    }

    /**
     * 统计每日放款多种app来源历史数据
     * @param $start_date
     * @param $end_date
     * @return int
     * @throws \Exception
     */
    public function actionDailyLoanHistory($time)
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        Util::cliLimitChange(1024);
        $db = \Yii::$app->db_kdkj_rd_new;
        $userLoanOrderTableName = UserLoanOrder::tableName();
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();

        new LoanPerson();
        $app_source = LoanPerson::$app_loan_source;
        unset($app_source[0]);

        $time_start = empty($time)?strtotime("today"):$time; //今天零点
        $end_time =$time_start + 86400;
        $_hour = date('H',time());//当前的小时数

        //如果当前时间为24点，则计算前一天所有的放款数据,显示日期为前一天的24时
        if( $_hour == 0 && date('i',time())<=10 ){
            $end_time = $time_start;
            $time_start = $end_time-86400;
            $_hour = 24;
        }
        $sql = "SELECT o.money_amount as money, o.loan_term, o.loan_method, o.sub_order_type, o.pass_type, o.user_id, o.id,o.is_first FROM {$userLoanOrderTableName} AS o
            WHERE o.loan_time >= {$time_start} AND o.loan_time < {$end_time} and o.id>0 ";
        $financial_loan = $db->createCommand($sql)->queryAll();

        if ($financial_loan) {
            // app类型部分开始
            $str_uid = '';
            $uid_list = array_column($financial_loan, 'user_id');
            $str_uid = implode(',', $uid_list);
            // 查询人员source
            $sql = "SELECT user_id, source FROM tb_user_register_info WHERE user_id IN ({$str_uid})";
            $source_res = $db->createCommand($sql)->queryAll();
            $uid_source = [];
            foreach ($source_res as $val) {
                $uid_source[$val['user_id']] = $val['source'];
            }

            $sub_order_type = [];
            foreach ($financial_loan as $item) {
                $sub_order_type[$item['sub_order_type']][] = $item;
            }
            $data = [];
            foreach ($sub_order_type as $key => $item) {
                foreach ($app_source as $k => $val) {
                    $data[$key][$k] = [
                        'loan_num' => 0,
                        'loan_money' => 0,
                        'loan_num_old' => 0,
                        'loan_money_old' => 0,
                        'loan_num_new' => 0,
                        'loan_money_new' => 0,
                        'loan_num_7' => 0,
                        'loan_money_7' => 0,
                        'loan_num_14' => 0,
                        'loan_money_14' => 0,
                        'gjj_num_14' => 0,
                        'gjj_num_old_14' => 0,
                        'gjj_num_new_14' => 0,
                        'gjj_money_14' => 0,
                        'gjj_money_old_14' => 0,
                        'gjj_money_new_14' => 0,
                    ];
                }
                foreach ($item as $value) {
                    $loan_person = LoanPerson::find()->where(['id' => $value['user_id']])->one($db);
                    if (empty($loan_person)) {
                        \yii::error("LoanPerson is empty", LogChannel::GJJ_ORDER_SCHEDULE);
                        return false;
                    }
                    //公积金认证判断
                    $check = AccumulationFund::validateAccumulationStatus($loan_person);
                    // 其他类型app统计部分
                    $user_source = $uid_source[$value['user_id']];

                    if (!$user_source) {
                        $user_source = 21;
                    }
                    $data[$key][$user_source]['loan_num'] ++;
                    $data[$key][$user_source]['loan_money'] += $value['money'];
                    if ($loan_person->customer_type == LoanPerson::CUSTOMER_TYPE_OLD&&$value['is_first']==0) {
                        $data[$key][$user_source]['loan_num_old']++;
                        $data[$key][$user_source]['loan_money_old'] += $value['money'];
                        if ($check) {
                            $data[$key][$user_source]['gjj_num_old_14']++;
                            $data[$key][$user_source]['gjj_money_old_14'] += $value['money'];
                        }
                    } else {
                        $data[$key][$user_source]['loan_num_new']++;
                        $data[$key][$user_source]['loan_money_new'] += $value['money'];
                        if ($check) {
                            $data[$key][$user_source]['gjj_num_new_14']++;
                            $data[$key][$user_source]['gjj_money_new_14'] += $value['money'];
                        }
                    }
                    if ($value['loan_term'] == 7 && $value['loan_method'] == 0) {
                        $data[$key][$user_source]['loan_num_7']++;
                        $data[$key][$user_source]['loan_money_7'] += $value['money'];
                    }
                    if ($value['loan_term'] == 14 && $value['loan_method'] == 0) {
                        $data[$key][$user_source]['loan_num_14']++;
                        $data[$key][$user_source]['loan_money_14'] += $value['money'];
                        if ($check) {
                            $data[$key][$user_source]['gjj_num_14']++;
                            $data[$key][$user_source]['gjj_money_14'] += $value['money'];
                        }
                    }
                }
            }
        } else {
            foreach ($app_source as $k => $val) {
                $data[1][$k] = [
                    'loan_num' => 0,
                    'loan_money' => 0,
                    'loan_num_old' => 0,
                    'loan_money_old' => 0,
                    'loan_num_new' => 0,
                    'loan_money_new' => 0,
                    'loan_num_7' => 0,
                    'loan_money_7' => 0,
                    'loan_num_14' => 0,
                    'loan_money_14' => 0,
                    'gjj_num_14' => 0,
                    'gjj_num_old_14' => 0,
                    'gjj_num_new_14' => 0,
                    'gjj_money_14' => 0,
                    'gjj_money_old_14' => 0,
                    'gjj_money_new_14' => 0,
                ];
            }
        }
        foreach ($data as $k => $v) {
            // 其他app
            foreach ($app_source as $app_id => $app_type) {
                $val = $v[$app_id];
                $statistics_loan = StatisticsLoan::find()->where(['date_time' => $time_start])->andWhere(['app_type' => $app_id])->andWhere(['sub_order_type' => $k])->one();

                if (empty($statistics_loan)) {
                    $statistics_loan = new StatisticsLoan();
                    $statistics_loan->created_at = time();//创建时间
                    $statistics_loan->date_time = $time_start; //日期
                }
                $statistics_loan->loan_num = $val['loan_num'];             //放款单数
                $statistics_loan->loan_num_7 = $val['loan_num_7'];         //7天期限放款单数
                $statistics_loan->loan_num_14 = $val['loan_num_14'];       //14天期限放款单数
                $statistics_loan->loan_num_old = $val['loan_num_old'];     //老用户放款单数
                $statistics_loan->loan_num_new = $val['loan_num_new'];     //新用户放款单数
                $statistics_loan->loan_money = $val['loan_money'];         //放款金额
                $statistics_loan->loan_money_7 = $val['loan_money_7'];     //7天期限放款金额
                $statistics_loan->loan_money_14 = $val['loan_money_14'];   //14天期限放款金额
                $statistics_loan->loan_money_old = $val['loan_money_old']; //老用户放款金额
                $statistics_loan->loan_money_new = $val['loan_money_new']; //新用户放款金额
                $statistics_loan->gjj_num_14 = $val['gjj_num_14'];//公积金放款单数
                $statistics_loan->gjj_num_old_14 = $val['gjj_num_old_14'];//老用户公积金放款单数
                $statistics_loan->gjj_num_new_14 = $val['gjj_num_new_14'];//新用户公积金放款单数
                $statistics_loan->gjj_money_14 = $val['gjj_money_14'];//公积金放款金额
                $statistics_loan->gjj_money_old_14 = $val['gjj_money_old_14'];//老用户公积金放款金额
                $statistics_loan->gjj_money_new_14 = $val['gjj_money_new_14'];//新用户公积金放款金额
                $statistics_loan->app_type = $app_id;
                $statistics_loan->updated_at = time();
                $statistics_loan->sub_order_type = $k;
                $res = $statistics_loan->save();
                if (!$res) {
                    \yii::error("放款数据保存失败[{$time_start}]", LogChannel::GJJ_ORDER_SCHEDULE);
                    //Yii::error($time_start . "放款数据保存失败");
                } else {

                }
            }
        }
    }

    public function actionDailyLoanCopy(){

        $time = date('Y-m-d',time());
        $time = strtotime($time);
        $s_time=strtotime('2016-9-19');
        $i=0;
        while(1)
        {
            $time_start = $time-86400*($i+1);
            $end_start = $time-86400*$i;
            if($end_start < $s_time)
            {
                break;
            }
            //放款单数
            $financial_loan=FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')->where(['l.type'=> FinancialLoanRecord::TYPE_LQD])->select(['l.money','l.user_id','u.loan_term','u.loan_method','u.sub_order_type'])->andWhere(" l.success_time >=".$time_start." and l.success_time <".$end_start)
                ->leftJoin(UserLoanOrder::tableName().' as u','l.business_id=u.id')->orderBy(['l.id'=>SORT_DESC])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            $sub_order_type=[];
            foreach($financial_loan as $item)
            {
                $sub_order_type[$item['sub_order_type']][]=$item;
            }
            $data=[];
            foreach($sub_order_type as $key => $item)
            {
                foreach($item as $value)
                {
                    if(!isset($data[$key]))
                    {
                        $data[$key]['loan_num']=0;
                        $data[$key]['loan_money']=0;
                        $data[$key]['loan_num_old']=0;
                        $data[$key]['loan_money_old']=0;
                        $data[$key]['loan_num_new']=0;
                        $data[$key]['loan_money_new']=0;
                        $data[$key]['loan_num_7']=0;
                        $data[$key]['loan_money_7']=0;
                        $data[$key]['loan_num_14']=0;
                        $data[$key]['loan_money_14']=0;
                    }else{
                        $data[$key]['loan_num']++;
                        $data[$key]['loan_money']+=$value['money'];
                        $user_loan_order=LoanPerson::findById($value['user_id']);
                        if($user_loan_order['customer_type']==LoanPerson::CUSTOMER_TYPE_OLD)
                        {
                            $data[$key]['loan_num_old']++;
                            $data[$key]['loan_money_old']+=$value['money'];
                        }else{
                            $data[$key]['loan_num_new']++;
                            $data[$key]['loan_money_new']+=$value['money'];
                        }
                        if($value['loan_term']==7 && $value['loan_method']==0)
                        {
                            $data[$key]['loan_num_7']++;
                            $data[$key]['loan_money_7']+=$value['money'];
                        }
                        if($value['loan_term']==14 && $value['loan_method']==0)
                        {
                            $data[$key]['loan_num_14']++;
                            $data[$key]['loan_money_14']+=$value['money'];
                        }
                    }
                }

            }
            foreach($data as $k=>$v)
            {
                $statistics_loan=StatisticsLoan::find()->where(['date_time'=>$time_start])->andWhere(['sub_order_type'=>$k])->one(Yii::$app->get('db_kdkj_rd'));
                if(!empty($statistics_loan))
                {
                    $statistics_loan->loan_num = $v['loan_num'];
                    $statistics_loan->loan_num_7 = $v['loan_num_7'];
                    $statistics_loan->loan_num_14 = $v['loan_num_14'];
                    $statistics_loan->loan_num_old = $v['loan_num_old'];
                    $statistics_loan->loan_num_new = $v['loan_num_new'];
                    $statistics_loan->loan_money = $v['loan_money'];
                    $statistics_loan->loan_money_7 = $v['loan_money_7'];
                    $statistics_loan->loan_money_14 = $v['loan_money_14'];
                    $statistics_loan->loan_money_old = $v['loan_money_old'];
                    $statistics_loan->loan_money_new = $v['loan_money_new'];
                    $statistics_loan->sub_order_type = $k;
                }else{
                    $statistics_loan = new StatisticsLoan();
                    $statistics_loan->date_time = $time_start;          //日期
                    $statistics_loan->loan_num = $v['loan_num'];             //放款单数
                    $statistics_loan->loan_num_7 = $v['loan_num_7'];         //7天期限放款单数
                    $statistics_loan->loan_num_14 = $v['loan_num_14'];       //14天期限放款单数
                    $statistics_loan->loan_num_old = $v['loan_num_old'];     //老用户放款单数
                    $statistics_loan->loan_num_new = $v['loan_num_new'];     //新用户放款单数
                    $statistics_loan->loan_money = $v['loan_money'];         //放款金额
                    $statistics_loan->loan_money_7 = $v['loan_money_7'];     //7天期限放款金额
                    $statistics_loan->loan_money_14 = $v['loan_money_14'];   //14天期限放款金额
                    $statistics_loan->loan_money_old = $v['loan_money_old']; //老用户放款金额
                    $statistics_loan->loan_money_new = $v['loan_money_new']; //新用户放款金额
                    $statistics_loan->created_at = time();
                    $statistics_loan->sub_order_type = $k;
                }
                if(!$statistics_loan->save()){
                    Yii::error($time_start."放款数据保存失败");
                }
            }
            $i++;
        }
    }



    /**
     * @name 每日统计(到期金额-到期笔数-当日还款金额-当日还款笔数-续借金额-续借笔数-当日还款率-当日续借率)
     */
    public function actionDayDataStatisticsRun($type = 1){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        if ($type == 1) {
            $date =date('Y-m-d');
            $this->runDayDataStatistics($date);
        } else if ($type == 2) {
            $end_date =date("Y-m-d");
            $start_date = '2017-03-28';
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $date = date('Y-m-d',$dateNum);
                $this->runDayDataStatistics($date);
            }
        }
    }
    private function runDayDataStatistics($pre_date){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        ini_set('memory_limit', '512M');
        $db = \Yii::$app->db_kdkj_rd_new;
        echo "date:{$pre_date}\n";
        $pre_time = strtotime($pre_date);
        $today_time = $pre_time + 86400;
        $today_date = date('Y-m-d', $today_time);
        $_hour = date('H',time());//当前的小时数
        //如果当前时间为24点，则计算前一天所有的放款数据,显示日期为前一天的24时
        if( $_hour == 0 ){
            $today_time = $pre_time;
            $pre_time = $today_time-86400;
            $_hour = 24;
        }

        $expire_num = 0; //到期笔数
        $expire_money = 0; //到期金额
        $loan_num= 0;//当日放款总人数
        $repay_num = 0; //当日还款笔数
        $repay_money = 0; //当日还款金额
        $delay_num = 0; //续借笔数
        $delay_money = 0; //续借金额
        $repay_rate = 0; //当日还款率
        $delay_rate = 0; //当日续借率

        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $userLoanOrderDelayLogTableName = UserLoanOrderDelayLog::tableName();
        $UserLoanOrderTableName = UserLoanOrder::tableName();
        /**
         * 以到期单为维度的计算
         */
        //今日到期笔数
        $expire_num_sql = "SELECT order_id,user_id FROM {$userLoanOrderRepaymentTableName}
                            WHERE plan_fee_time >= {$pre_time} AND plan_fee_time < {$today_time}";
        $expire_num_data = $db->createCommand($expire_num_sql)->queryAll();
        $expire_num = count($expire_num_data);
//echo "expire_num：{$expire_num}\n";
        $order_ids = '';
        $user_ids = '';
        if ($expire_num > 0) {
            foreach ($expire_num_data as $item) {
                $order_ids .= $item['order_id'] . ',';
                $user_ids .= $item['user_id'] . ',';
            }
            $order_ids = substr($order_ids, 0, -1);
            $user_ids = substr($user_ids, 0, -1);
        }
//到期金额
        $expire_money_sql = "SELECT sum(total_money) as total FROM {$userLoanOrderRepaymentTableName}
                            WHERE plan_fee_time >= {$pre_time} AND plan_fee_time < {$today_time}";
        $expire_money_data = $db->createCommand($expire_money_sql)->queryAll();
        $expire_money = $expire_money_data[0]['total'];
//echo "expire_money:{$expire_money}\n";

        if ($expire_num > 0) {
            //当天放款总人数
            $loan_num_sql = "SELECT user_id FROM {$userLoanOrderRepaymentTableName} WHERE created_at >= {$pre_time} AND created_at < {$today_time} group by user_id";
            $loan_num_data = $db->createCommand($loan_num_sql)->queryAll();
            $loan_num = count($loan_num_data);

            //当日还款笔数
            $repay_num_sql = "SELECT count(id) as count FROM {$userLoanOrderRepaymentTableName}
                                WHERE true_repayment_time > 0 AND order_id IN({$order_ids})";
            $repay_num_data = $db->createCommand($repay_num_sql)->queryAll();
            $repay_num = intval($repay_num_data[0]['count']);
//echo "repay_num:{$repay_num}\n";

            //当日还款金额
            $repay_money_sql = "SELECT sum(total_money) as total FROM {$userLoanOrderRepaymentTableName}
                                WHERE true_repayment_time > 0 AND order_id IN({$order_ids})";
            $repay_money_data = $db->createCommand($repay_money_sql)->queryAll();
            $repay_money = $repay_money_data[0]['total'];
//echo "repay_money:{$repay_money}\n";

            //续借笔数
            $delay_num_sql = "SELECT count(id) as count FROM {$userLoanOrderRepaymentTableName}
                                WHERE created_at>={$pre_time} and created_at<{$today_time} AND user_id IN({$user_ids})";
            $delay_num_data = $db->createCommand($delay_num_sql)->queryAll();
            $delay_num = intval($delay_num_data[0]['count']);
//echo "delay_num:{$delay_num}\n";

            //续借金额
            $delay_money_sql = "SELECT sum(principal) as total FROM {$userLoanOrderRepaymentTableName}
                                WHERE  created_at>={$pre_time} and created_at<{$today_time}  AND user_id IN({$user_ids})";
            $delay_money_data = $db->createCommand($delay_money_sql)->queryAll();
            $delay_money = $delay_money_data[0]['total'];
//echo "delay_money:{$delay_money}\n";
        }

        //当日还款率
        $repay_rate = $expire_num > 0 ? $repay_num / $expire_num : 0;
//echo "repay_rate:{$repay_rate}\n";

        //当日续借率
        $delay_rate = $expire_num > 0 ? $delay_num / $expire_num : 0;
//echo "delay_rate:{$delay_rate}\n";

        /**
         * 以还款单为维度的计算
         */
        $main_repay_num = $main_repay_money = $main_repay_delay_num = $main_repay_delay_money = $main_repay_rate = $main_repay_delay_rate = 0;
        //还款笔数
        $main_repay_num_sql = "SELECT order_id FROM {$userLoanOrderRepaymentTableName}
                               WHERE true_repayment_time > 0 AND `status` = 4
                               AND true_repayment_time >= {$pre_time} AND true_repayment_time < {$today_time}";
        $main_repay_num_data = $db->createCommand($main_repay_num_sql)->queryAll();
        $main_repay_num = count($main_repay_num_data);
//echo "main_repay_num:{$main_repay_num}\n";
        $order_ids = '';
        if ($main_repay_num > 0) {
            foreach ($main_repay_num_data as $item) {
                $order_ids .= $item['order_id'] . ',';
            }
            $order_ids = substr($order_ids, 0, -1);
        }
        unset($main_repay_num_data);
        //还款金额
        $main_repay_money_sql = "SELECT sum(total_money) as total FROM {$userLoanOrderRepaymentTableName}
                                WHERE true_repayment_time > 0 AND `status` = 4
                                AND true_repayment_time >= {$pre_time} AND true_repayment_time < {$today_time}";
        $main_repay_money_data = $db->createCommand($main_repay_money_sql)->queryOne();
        $main_repay_money = $main_repay_money_data['total'];
//echo "main_repay_money:{$main_repay_money}\n";
        //续借笔数
        $main_repay_delay_num_sql = "SELECT count(id) as count FROM {$userLoanOrderRepaymentTableName}
                                     WHERE  created_at>={$pre_time}  AND user_id IN({$user_ids})";
        $main_repay_delay_num_data = $db->createCommand($main_repay_delay_num_sql)->queryOne();
        $main_repay_delay_num = $main_repay_delay_num_data['count'];
//echo "main_repay_delay_num:{$main_repay_delay_num}\n";
        //续借金额
        $main_repay_delay_money_sql = "SELECT sum(principal) as total FROM {$userLoanOrderRepaymentTableName}
                                WHERE  created_at>={$pre_time}  AND user_id IN({$user_ids})";
        $main_repay_delay_money_data = $db->createCommand($main_repay_delay_money_sql)->queryOne();
        $main_repay_delay_money = $main_repay_delay_money_data['total'];
//echo "main_repay_delay_money:{$main_repay_delay_money}\n";
        //还款率
        $main_repay_rate = $expire_num > 0 ? $main_repay_num / $expire_num : 0;
//echo "main_repay_rate:{$main_repay_rate}\n";
        //续借率
        $main_repay_delay_rate = $expire_num > 0 ? $main_repay_delay_num / $expire_num : 0;
//echo "main_repay_delay_rate:{$main_repay_delay_rate}\n";

        /**
         * 复借相关的计算
         */
        //复借率
        $again_fenmu = $again_fenzi1 = $again_fenzi2 = 0;
        //分母  累计还款大于等于1次的
        $sql = "SELECT user_id FROM tb_user_loan_order_repayment
                WHERE true_repayment_time > 0 AND true_repayment_time < {$today_time}
                GROUP BY user_id;";
        $data = $db->createCommand($sql)->queryAll();
        $again_fenmu = count($data);
        unset($data);

        //分子 累计放款大于等于2次的
        //分子1 累计放款等于1次的
        $sql = "SELECT count(user_id) as count FROM tb_user_loan_order_repayment
                WHERE created_at < {$today_time}
                GROUP BY user_id;";
        $data = $db->createCommand($sql)->queryAll();
        foreach ($data as $item) {
            if ($item['count'] >= 1) {
                $again_fenzi1++;
            }
            if ($item['count'] >= 2) {
                $again_fenzi2++;
            }
        }
        unset($data);

        //复借次数人数分布统计
        $again_person_count = [];
        if ($this->again_max_count > 0) {
            for ($i=1; $i<=$this->again_max_count; $i++) {
                $again_person_count[$i] = $this->getPersonCountByAgainNumber($today_time, $i);
            }
        }


        //当天还款的客户中复借次数人数分布统计
        $again_person_count_while = array();
        if ($this->again_max_count > 0) {
            for ($i=1; $i<=$this->again_max_count; $i++) {
                $again_person_count_while[$i] = $this->getPersonCountByAgainNumberWhile($today_time, $i);
            }
        }

        $day_data = StatisticsDayData::find()->where(['date' => $pre_date])->limit(1)->one(Yii::$app->get('db_kdkj'));
        if (!empty($day_data)) {
            $day_data->expire_num = $expire_num;
            $day_data->expire_money = $expire_money;
            $day_data->loan_num = $loan_num;
            $day_data->repay_num = $repay_num;
            $day_data->repay_money = $repay_money;
            $day_data->delay_num = $delay_num;
            $day_data->delay_money = $delay_money;
            $day_data->main_repay_num = $main_repay_num;
            $day_data->main_repay_money = $main_repay_money;
            $day_data->main_repay_delay_num = $main_repay_delay_num;
            $day_data->main_repay_delay_money = $main_repay_delay_money;
            $day_data->again_repay_1 = $again_fenmu;
            $day_data->again_pocket_1 = $again_fenzi1;
            $day_data->again_pocket_2 = $again_fenzi2;
            $day_data->again_person_count = json_encode($again_person_count);
            $day_data->again_person_count_while = json_encode($again_person_count_while);
            $day_data->updated_at = time();
        } else {
            $day_data = new StatisticsDayData();
            $day_data->date = $pre_date;
            $day_data->expire_num = $expire_num;
            $day_data->expire_money = $expire_money;
            $day_data->loan_num = $loan_num;
            $day_data->repay_num = $repay_num;
            $day_data->repay_money = $repay_money;
            $day_data->delay_num = $delay_num;
            $day_data->delay_money = $delay_money;
            $day_data->main_repay_num = $main_repay_num;
            $day_data->main_repay_money = $main_repay_money;
            $day_data->main_repay_delay_num = $main_repay_delay_num;
            $day_data->main_repay_delay_money = $main_repay_delay_money;
            $day_data->again_repay_1 = $again_fenmu;
            $day_data->again_pocket_1 = $again_fenzi1;
            $day_data->again_pocket_2 = $again_fenzi2;
            $day_data->again_person_count = json_encode($again_person_count);
            $day_data->again_person_count_while = json_encode($again_person_count_while);
            $day_data->created_at = time();
        }
        if (!$day_data->save()) {
//            Yii::error("统计" . $pre_date . "的当天数据保存失败：" );
            MailHelper::send(NOTICE_MAIL, '还款续借率',Yii::error("统计" . $pre_date . "的当天数据保存失败：" ));
        }
    }
    /**
     * 按照复借次数来计算当天0点前对应的人数统计
     * @param int $today_time   日期
     * @param int $num          数量
     */
    private function getPersonCountByAgainNumber($today_time, $num){
        $db = \Yii::$app->db_kdkj_rd_new;

        $sql = "SELECT user_id, count(user_id) as count FROM tb_user_loan_order_repayment
                WHERE created_at < {$today_time} GROUP BY user_id";
        $data = $db->createCommand($sql)->queryAll();
        $count = 0;
        foreach ($data as $item) {
            if ($item['count'] == $num) {
                $count++;
            }
        }
        unset($data);
        return $count;
    }

    /**
     * 按照复借次数来计算当天在还款人数中对应的人数统计
     * @param int $today_time   日期
     * @param int $num          数量
     */
    private function getPersonCountByAgainNumberWhile($today_time, $num){
        $db = \Yii::$app->db_kdkj_rd_new;
        $pre_time = $today_time- 86400;
        $sql = "SELECT user_id, count(user_id) as count FROM tb_user_loan_order_repayment
                WHERE created_at < {$today_time}
                and user_id in(SELECT user_id FROM tb_user_loan_order_repayment WHERE created_at >= {$pre_time} AND created_at < {$today_time} group by user_id) GROUP BY user_id";
        $data = $db->createCommand($sql)->queryAll();
        $count = 0;
        foreach ($data as $item) {
            if ($item['count'] == $num) {
                $count++;
            }
        }
        unset($data);
        return $count;
    }


    /**
     * @name 每日复借率情况，每日零点执行，获取前一天的数据
     */
    public function actionDayLoseStatistics($type = 1){
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        if ($type == 1) {
            $date = date('Y-m-d', (time() - 86400));
            $this->actionDayLoseRun($date);
        } else if ($type == 2) {
            $end_date = '2017-07-31';
            $start_date = '2017-07-01';
            $countDate = (strtotime($end_date)-strtotime($start_date))/86400;
            for($datei = 0;$datei<=$countDate;$datei++){
                $dateNum = strtotime($end_date)-$datei*86400;
                $date = date('Y-m-d',$dateNum);
                $data = $this->actionDayLoseRun($date);
                $arr[]=$data;
            }
            \Yii::$app->cache->set("RepaymentFujieData", json_encode($arr), 3*86400);
        }
    }

    private function actionDayLoseRun($date){
        $pre_date = $date;
        $pre_time = strtotime($pre_date);
        $today_time = $pre_time + 86400;
        $today_date = date('Y-m-d', $today_time);
        echo $pre_date . "\n";

        $db = \Yii::$app->db_kdkj_rd_new;

        $today_repay_num = 0; //当天还款人数
        $today_fujie_num = 0; //当天复借人数
        $today_fujie_success_num = 0; //当天复借成功人数
        $today_fujie_num2 = 0; //7天内复借人数
        $today_fujie_success_num2 = 0; //7天内复借成功人数
        $today_fujie_num3 = 0; //14天内复借人数
        $today_fujie_success_num3 = 0; //14天内复借成功人数
        $today_fujie_num4 = 0; //30天内复借人数
        $today_fujie_success_num4 = 0; //30天内复借成功人数
        $today_fujie_num5 = 0; //30天以上复借人数
        $today_fujie_success_num5 = 0; //30天以上复借成功人数

        //按用户第一次还款和离第一次还款之后的最近的一次借款来计算复借数据
        //成功借款的用户
        $sql = "SELECT
                    (o.created_at - max(r.true_repayment_time))/86400 as duration_time,
                    if(o.created_at - max(r.true_repayment_time) <= 86400,1,0) as today_fujie_success_num,
                    if((o.created_at - max(r.true_repayment_time)<= 86400*7),1,0) as today_fujie_success_num2,
                    if((o.created_at - max(r.true_repayment_time)<= 86400*14),1,0) as today_fujie_success_num3,
                    if((o.created_at - max(r.true_repayment_time)<= 86400*30),1,0) as today_fujie_success_num4,
                    if((o.created_at - max(r.true_repayment_time) > 86400*30),1,0) as today_fujie_success_num5
                FROM
                    tb_user_loan_order AS o
                    LEFT JOIN tb_user_loan_order_repayment as r on (r.user_id = o.user_id and r.`status` = 4 and o.created_at > r.true_repayment_time)
                WHERE
                    o.created_at >= {$pre_time}
                AND o.created_at < {$today_time}
                AND o.`status` >= 3
                GROUP BY o.user_id";
        $data = $db->createCommand($sql)->queryAll();
        foreach($data as $rows){
            $today_repay_num +=1;
            $today_fujie_success_num += $rows['today_fujie_success_num'];
            $today_fujie_success_num2 += $rows['today_fujie_success_num2'];
            $today_fujie_success_num3 += $rows['today_fujie_success_num3'];
            $today_fujie_success_num4 += $rows['today_fujie_success_num4'];
            $today_fujie_success_num5 += $rows['today_fujie_success_num5'];
        }

        $day_lose_data = \common\models\StatisticsDayLose::find()->where(array('date_time'=>$pre_date))->limit(1)->one(Yii::$app->get('db_kdkj'));
        if (!empty($day_lose_data)) {
            $day_lose_data->repay_num = $today_repay_num;
            $day_lose_data->loan_again_num_0 = $today_fujie_num;
            $day_lose_data->loan_again_success_num_0 = $today_fujie_success_num;
            $day_lose_data->loan_again_num_5 = $today_fujie_num2;
            $day_lose_data->loan_again_success_num_5 = $today_fujie_success_num2;
            $day_lose_data->loan_again_num_10 = $today_fujie_num3;
            $day_lose_data->loan_again_success_num_10 = $today_fujie_success_num3;
            $day_lose_data->loan_again_num_15 = $today_fujie_num4;
            $day_lose_data->loan_again_success_num_15 = $today_fujie_success_num4;
            $day_lose_data->loan_again_num_40 = $today_fujie_num5;
            $day_lose_data->loan_again_success_num_40 = $today_fujie_success_num5;
            $day_lose_data->updated_at = time();
        } else {
            $day_lose_data = new \common\models\StatisticsDayLose();
            $day_lose_data->date_time = $pre_date;
            $day_lose_data->repay_num = $today_repay_num;
            $day_lose_data->loan_again_num_0 = $today_fujie_num;
            $day_lose_data->loan_again_success_num_0 = $today_fujie_success_num;
            $day_lose_data->loan_again_num_5 = $today_fujie_num2;
            $day_lose_data->loan_again_success_num_5 = $today_fujie_success_num2;
            $day_lose_data->loan_again_num_10 = $today_fujie_num3;
            $day_lose_data->loan_again_success_num_10 = $today_fujie_success_num3;
            $day_lose_data->loan_again_num_15 = $today_fujie_num4;
            $day_lose_data->loan_again_success_num_15 = $today_fujie_success_num4;
            $day_lose_data->loan_again_num_40 = $today_fujie_num5;
            $day_lose_data->loan_again_success_num_40 = $today_fujie_success_num5;
            $day_lose_data->created_at = time();
        }
        if (!$day_lose_data->save()) {
            Yii::error("统计" . $pre_date . "的每日流失率数据保存失败：" );
        }

    }
}