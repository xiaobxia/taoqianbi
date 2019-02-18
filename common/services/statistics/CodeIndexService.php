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
use common\models\StatsCodeIndex;
use common\models\CreditTd;
use common\models\CreditJy;
use common\models\CreditZmop;
use common\models\TmpStatsCodeIndex;
use common\models\LoanPerson;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\Rule;
use common\models\StatsUserMinOrder;
use common\models\UserCreditTotal;
use common\models\UserDetail;
use common\models\StatsDailyReport;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderDelayLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\StatsGreyAnalysis;

class CodeIndexService extends Component
{

    const AUTO = [
        'auto shell','shell auto','机审'
    ];

    const range_0 = 0;
    const range_1 = 1;
    const range_2 = 2;
    const range_3 = 3;
    const range_4 = 4;
    const range_5 = 5;
    const range_6 = 6;
    const range_7 = 7;

    public $range_arr = [
        self::range_0=>[1,1000],
        self::range_1=>[1000,1500],
        self::range_2=>[1500,2000],
        self::range_3=>[2000,2500],
        self::range_4=>[2500,3000],
        self::range_5=>[3000,'max'],
    ];

    public $range_name = [
        self::range_0=>'range_1_to_1000',
        self::range_1=>'range_1000_to_1500',
        self::range_2=>'range_1500_to_2000',
        self::range_3=>'range_2000_to_2500',
        self::range_4=>'range_2500_to_3000',
        self::range_5=>'range_3000_to_max',
    ];

    const STATS_ALL = 0;
    const STATS_NEW_USER = 1;
    const STATS_OLD_USER = 2;
    const STATS_IS_ACTIVE = 3;
    const STATS_NO_ACTIVE = 4;

    public $stats_arr_name = [
        self::STATS_ALL=>'全部',
        self::STATS_NEW_USER=>'新用户',
        self::STATS_OLD_USER=>'老用户',
        self::STATS_IS_ACTIVE=>'活跃用户',
        self::STATS_NO_ACTIVE=>'非活跃用户',
    ];

    public $stats_arr = [
        self::STATS_ALL=>'stats_all',
        self::STATS_NEW_USER=>'stats_new_user',
        self::STATS_OLD_USER=>'stats_old_user',
        self::STATS_IS_ACTIVE=>'stats_is_active',
        self::STATS_NO_ACTIVE=>'stats_no_active',
    ];

    public $stats_arr_all = [
        self::STATS_ALL=>'all_stats_all',
        self::STATS_NEW_USER=>'all_stats_new_user',
        self::STATS_OLD_USER=>'all_stats_old_user',
        self::STATS_IS_ACTIVE=>'all_stats_is_active',
        self::STATS_NO_ACTIVE=>'all_stats_no_active',
    ];

    const TYPE_ALL = 0;//全部
    const TYPE_USER = 1;//新老用户订单
    const TYPE_ACTIVE = 2;//活跃非活跃用户订单

    public $order_type = [
        self::TYPE_ALL=>0,
        self::TYPE_USER=>1,
        self::TYPE_ACTIVE=>2,
    ];

    public $num = 60;
    const NUM_DAY = 40;

    /**
     * @param $day
     * @return array
     * @throws yii\base\InvalidConfigException
     * 获取当天的订单
     */
    public function get_check_pass_order($day){
        $arr_total = [];
        $arr_pass = [];
        $result_arr = [];
        $arr = [];
        $count_check = UserOrderLoanCheckLog::find()
            ->select('order_id')
            ->where(['>=','created_at',strtotime($day)])
            ->andWhere(['<','created_at',strtotime($day)+86400])
            ->andWhere(['type'=>1])
            ->andWhere(['before_status'=>0])
            ->andWhere(['after_status'=>[7,-3]])
            ->asArray()->count('id',Yii::$app->get('db_kdkj_rd'));
        $page_start = 0;
        $page_size = 10000;
        //$this->printMessage('get_check_pass_order_before');
        while($count_check>$page_start){
            $arr_check = UserOrderLoanCheckLog::find()
                ->select('order_id')
                ->where(['>=','created_at',strtotime($day)])
                ->andWhere(['<','created_at',strtotime($day)+86400])
                ->andWhere(['type'=>1])
                ->andWhere(['before_status'=>0])
                ->andWhere(['after_status'=>[7,-3]])
                ->limit($page_size)->offset($page_start)
                ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            $order_arr = $this->get_order_details($arr_check);
            $result_arr = $this->sum_value($order_arr,$result_arr);
            $page_start += $page_size;
        }
        //var_dump($result_arr);
        $this->printMessage('get_check_pass_order_after');
        $arr = ['arr_total'=>$arr_total];//$stats_by:统计类型 $arr_total:总订单  $arr_pass:通过的订单
        unset($arr_total);
        unset($arr_pass);

        $result = self::getOverallCalculeData($result_arr,$day);
    }

    /**
     * @param $input_arr
     * @return array
     * @throws yii\base\InvalidConfigException
     * 获取order表中的字段信息
     */
    public function get_order_details($input_arr){
        $arr_total = [];
        if($input_arr){
            foreach($input_arr as $item){
                $arr_check_tmp[] = $item['order_id'];
            }
            if($arr_check_tmp){
                $arr_tmp = UserLoanOrder::find()
                    ->select('id,user_id,`status`,money_amount,tree,sub_order_type,counter_fee,is_first')
                    ->where(['id'=>$arr_check_tmp])
                    ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                if($arr_tmp){
                    foreach($arr_tmp as $item){
                        $arr_total[$item['id']] = [
                            'order_id'=>$item['id'],
                            'user_id'=>$item['user_id'],
                            'money_amount'=>$item['money_amount'],
                            'tree'=>$item['tree'],
                            'status'=>$item['status'],
                            'sub_order_type'=>$item['sub_order_type'],
                            'counter_fee'=>$item['counter_fee'],
                            'is_first'=>$item['is_first']
                        ];//订单ID  用户ID  申请金额(通过金额)
                    }
                    unset($arr_tmp);
                }
            }
        }
        return $arr_total;
    }

    /**
     * @param $input_arr
     * @return array
     * 分离所有来源的订单
     */
    public function get_valid_arr($input_arr){
        $arr = [];
        if($input_arr){
            foreach($input_arr as $item){
                foreach(UserLoanOrder::$sel_order_type as $key=>$sel_type){
                    if($item['sub_order_type']==$key){
                        $arr[$sel_type][] = [
                            'order_id'=>$item['order_id'],
                            'user_id'=>$item['user_id'],
                            'status'=>$item['status'],
                            'money_amount'=>$item['money_amount'],
                            'tree'=>$item['tree'],
                            'counter_fee'=>$item['counter_fee'],
                            'is_first'=>$item['is_first']
                        ];
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * @param $input_arr
     * @return array
     * 分离新老用户、活跃非活跃用户
     */
    public function order_group_by_scoure($input_arr){
        $arr = [];
        if($input_arr){
            foreach($input_arr as $key=>$content){
                //新老用户分离
                $arr_total_old[$key] = [];
                $arr_total_old_pass[$key] = [];
                $arr_total_new[$key] = [];
                $arr_total_new_pass[$key] = [];
                $arr_total_is_active[$key] = [];
                $arr_total_is_active_pass[$key] = [];
                $arr_total_no_active[$key] = [];
                $arr_total_no_active_pass[$key] = [];
                foreach($content as $item){
                    if(!$item['is_first']){//是首单  则为新用户订单
                        //是老用户
                        $arr_total_old[$key][] = $item['order_id'];
                        if($item['status']>0){
                            $arr_total_old_pass[$key][] = $item['order_id'];
                        }
                    }else{
                        //是新用户
                        $arr_total_new[$key][] = $item['order_id'];
                        if($item['status']>0){
                            $arr_total_new_pass[$key][] = $item['order_id'];
                        }
                    }
                }
                //活跃非活跃用户分离
                $arr_total_is_active[$key] = [];
                $arr_total_no_active[$key] = [];
                foreach($content as $item){
                    if($item['tree']=='normal'){//活跃
                        $arr_total_is_active[$key][] = $item['order_id'];
                        if($item['status']>0){
                            $arr_total_is_active_pass[$key][] = $item['order_id'];
                        }
                    }else{//非活跃
                        $arr_total_no_active[$key][] = $item['order_id'];
                        if($item['status']>0){
                            $arr_total_no_active_pass[$key][] = $item['order_id'];
                        }
                    }
                }
            }
            $arr = [
                'all_stats_old_user'=>$arr_total_old,
                'stats_old_user'=>$arr_total_old_pass,
                'all_stats_new_user'=>$arr_total_new,
                'stats_new_user'=>$arr_total_new_pass,
                'all_stats_is_active'=>$arr_total_is_active,
                'stats_is_active'=>$arr_total_is_active_pass,
                'all_stats_no_active'=>$arr_total_no_active,
                'stats_no_active'=>$arr_total_no_active_pass,
            ];
        }
        return $arr;
    }

    /**
     * @param $compare_arr
     * @param $compare_arr_all
     * @param $input_arr 审批通过的订单
     * @return array
     * 获得平均借款额度
     * $input_arr 审批通过的订单
     * $value 平均借款额度
     * $sum_money_amount 审批通过的订单总金额
     */
    public function get_agv_loan_money($compare_arr,$compare_arr_all,$input_arr){
        $value = 0;
        $sum_money_amount = 0;
        $pass_money_amount = 0;
        if($compare_arr) {
            foreach ($compare_arr as $item) {
                $pass_money_amount += $input_arr[$item]['money_amount']/100;//审批通过的订单总额
            }
            $value = sprintf("%0.2f",$pass_money_amount/count($compare_arr));//审批通过的订单总额/审批通过的总数
        }
        if($compare_arr_all){
            foreach ($compare_arr_all as $item) {
                $sum_money_amount += $input_arr[$item]['money_amount'] / 100;//审批的全部金额
            }
        }
        //var_dump(count($compare_arr));
        //var_dump(count($compare_arr_all));
        //$this->printMessage('get_agv_loan_money');
        $arr = ['value'=>$value,'pass_money_amount'=>$pass_money_amount,'sum_money_amount'=>$sum_money_amount];
        return $arr;
    }

    /**
     * @param $compare_arr
     * @param $input_arr
     * @param $arr
     * @return mixed
     * 获取制定额度内的借款金额
     */
    public function getRangeMoney($compare_arr,$input_arr,$arr){
        if($compare_arr){
            foreach($compare_arr as $item){//审批通过的订单
                foreach($this->range_arr as $key=>$range){
                    if($range[1]!='max'){
                        if(($input_arr[$item]['money_amount']/100>=$range[0])&&($input_arr[$item]['money_amount']/100<$range[1])){
                            $arr[$key] += $input_arr[$item]['money_amount'];
                        }
                    }else{
                        if(($input_arr[$item]['money_amount']/100>=$range[0])){
                            $arr[$key] += $input_arr[$item]['money_amount'];
                        }
                    }
                }
            }

        }
        return $arr;
    }

    /**
     * @param $compare_arr
     * @param $input_arr
     * @param $sum_money_amount  审批通过的订单总额
     * @return int
     * 获取指定额度范围内的借款占比
     */
    public function get_range_money_percent($compare_arr,$sum_money_amount,$input_arr,$arr){
        foreach($this->range_arr as $key=>$value){
            $range_percent[$key] = 0;
        }
        if($compare_arr){
            foreach($this->range_arr as $key=>$value){
                $order_hit_money[$key] = 0;
            }
            foreach($compare_arr as $item){//审批通过的订单
                foreach($this->range_arr as $key=>$range){
                    if($range[1]!='max'){
                        if(($input_arr[$item]['money_amount']/100>=$range[0])&&($input_arr[$item]['money_amount']/100<$range[1])){
                            $order_hit_money[$key] += $input_arr[$item]['money_amount']/100;
                        }
                    }else{
                        if(($input_arr[$item]['money_amount']/100>=$range[0])){
                            $order_hit_money[$key] += $input_arr[$item]['money_amount']/100;
                        }
                    }
                }
            }
            foreach($order_hit_money as $key=>$value){
                if($sum_money_amount){
                    $range_percent[$key] = sprintf("%0.2f",$order_hit_money[$key]/$sum_money_amount*100).'%';
                }else{
                    $range_percent[$key] = 0;
                }
            }
            unset($money_amount);
            //$this->printMessage('get_range_money_percent');
        }
        return $range_percent;
    }

    /**
     * @param $pass_money
     * @param $deal_money
     * @return array
     * @throws yii\base\InvalidConfigException
     * 通过率
     */
    public function get_pass_rate($pass_money,$deal_money){
        $arr = [];
        $pass_rate = 0;
        if($pass_money&&$deal_money){
            $pass_rate = sprintf("%0.2f",$pass_money/$deal_money*100).'%';
        }
        //$this->printMessage('get_pass_rate');
        $arr = ['pass_rate'=>$pass_rate];
        return $arr;
    }


    /**
     * @param $data
     * @param $source
     * @param string $stats_time
     * @param $type
     * @return mixed
     */
    public function inputData($data,$stats_time = '0',$type,$source){
        //有log_id则进行更新操作   无log_id则新建一条记录
        if($stats_time&&$log = StatsCodeIndex::find()->where(['stats_date'=>$stats_time,'stats_by'=>$type,'stats_source'=>$source])->one()){
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            return $log->save();
        }else{
            $log = new StatsCodeIndex();
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            return $log->save();
        }
    }


    public function getBasicReport(){//获取直接的统计数据
        $num = $this->num;
        $today = date('Y-m-d');
        try{
            //$data_from_db = $this->dataFromGrayReport();//从灰度报表中获取数据
            while($num>0){
                $day = date('Y-m-d',strtotime('-'.$num.' day'));
                var_dump($day);
                //统计起始时间
                $num--;
                //样本数组
                $this->printMessage('main');
                $returt_arr = $this->get_check_pass_order($day);
                unset($order_arr);
                unset($pass_order);
                unset($group_arr);
                $this->printMessage('unset');
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
            //file_put_contents('/tmp/lfj/err_daily_report.txt',$e->getTraceAsString());
        }
    }

    public function getNewBasicReport(){//获取直接的统计数据
        $num = self::NUM_DAY;
        try{
            while($num>0){
                $day = date('Y-m-d',strtotime('-'.$num.' day'));
                var_dump($day);
                //统计起始时间
                $num--;
                foreach(UserLoanOrder::$sub_order_type as $key_source=>$stats_source){
                    var_dump($stats_source);
                    foreach($this->stats_arr_name as $key_by=>$stats_by){
                        $condition = $this->buildCondition($key_source,$key_by);//来源条件 统计方式条件
                        $result = $this->getResult($condition,$day);
                        $result_xdata = json_encode($result);
                        $result_arr = [
                            'stats_date' => $day,
                            'stats_by' => $stats_by,
                            'stats_source' => $stats_source,
                            'stats_xdata' => $result_xdata,
                        ];
                        $this->inputData($result_arr,$day,$stats_by,$stats_source);
                    }
                }
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
            exit;
        }
    }

    public function buildCondition($stats_source,$stats_by){
        switch($stats_source){
            case UserLoanOrder::SUB_TYPE_ALL : $str1 = '1 = 1';break;
            default : $str1 = 'sub_order_type = '.$stats_source;break;
        }
        switch($stats_by){
            case self::STATS_ALL : $str2 = '1 = 1';break;
            case self::STATS_OLD_USER : $str2 = 'is_first = 0';break;
            case self::STATS_NEW_USER : $str2 = 'is_first = 1';break;
            case self::STATS_IS_ACTIVE : $str2 = 'tree = \'normal\'';break;
            case self::STATS_NO_ACTIVE : $str2 = 'tree != \'normal\'';break;
            default : $str2 = '1 = 1';break;
        }
        return $str = $str1.' AND '.$str2;
    }
    public function getResult($condition,$day){

        //入催
        $arr = $this->getOverdueDay($condition,$day,1);//D1
        $overdue_total_D1 = isset($arr['overdue_total'])?$arr['overdue_total']:0;
        $overdue_percent_D1 = isset($arr['overdue_percent'])?$arr['overdue_percent']:0;
        $arr = $this->getOverdueDay($condition,$day,10);//S1
        $overdue_total_S1 = isset($arr['overdue_total'])?$arr['overdue_total']:0;
        $overdue_percent_S1 = isset($arr['overdue_percent'])?$arr['overdue_percent']:0;
        $arr = $this->getOverdueDay($condition,$day,30);//M1
        $overdue_total_M1 = isset($arr['overdue_total'])?$arr['overdue_total']:0;
        $overdue_percent_M1 = isset($arr['overdue_percent'])?$arr['overdue_percent']:0;

        //平均借款额度
        $getArr = $this->getAgvMoney($condition,$day);
        $agv_loan_money = $getArr['avg'];

        //借款额度占比
        $der = $getArr['pass_money'];
        $range_1_to_1000 = $this->getLoanMoney($condition,$day,1,1000,$der);
        $range_1000_to_1500 = $this->getLoanMoney($condition,$day,1000,1500,$der);
        $range_1500_to_2000 = $this->getLoanMoney($condition,$day,1500,2000,$der);
        $range_2000_to_2500 = $this->getLoanMoney($condition,$day,2000,2500,$der);
        $range_2500_to_3000 = $this->getLoanMoney($condition,$day,2500,3000,$der);
        $range_3000_to_max = $this->getLoanMoney($condition,$day,3000,'max',$der);

        //通过率
        $pass_rate = $this->getPassRate($condition,$day,$getArr['pass_money']);

        //毛利润
        $profitMoney = $this->getProfitMoney($condition,$day);

        //人均毛利润
        $perPassPersonProfit = $this->getPerPassPersonProfit($profitMoney,$getArr['pass_count']);

        //申请人均毛利润
        $perApplyPersonProfit = $this->getPerApplyPersonProfit($condition,$day,$profitMoney);

        $result_arr = [
            'overdue_total_D1' => $overdue_total_D1,
            'overdue_percent_D1' => $overdue_percent_D1,
            'overdue_total_S1' => $overdue_total_S1,
            'overdue_percent_S1' => $overdue_percent_S1,
            'overdue_total_M1' => $overdue_total_M1,
            'overdue_percent_M1' => $overdue_percent_M1,
            'range_1_to_1000' => $range_1_to_1000,
            'range_1000_to_1500' => $range_1000_to_1500,
            'range_1500_to_2000' => $range_1500_to_2000,
            'range_2000_to_2500' => $range_2000_to_2500,
            'range_2500_to_3000' => $range_2500_to_3000,
            'range_3000_to_max' => $range_3000_to_max,
            'agv_loan_money' => $agv_loan_money,
            'pass_rate' => $pass_rate,
            'profitMoney' => $profitMoney,
            'perPassPersonProfit' => $perPassPersonProfit,
            'perApplyPersonProfit' => $perApplyPersonProfit,
        ];
        return $result_arr;
    }

    public function getOverdueDay($condition,$day,$num){
        $overdue_total = TmpStatsCodeIndex::find()
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere('overdue_day > '.$num)
            ->andWhere($condition)
            ->count();//某一天审批通过的逾期$num天以上的订单总数
        $overdue_total_money_tmp = TmpStatsCodeIndex::find()->select('SUM(principal) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere('overdue_day > '.$num)
            ->andWhere($condition)
            ->asArray()->one();
        $overdue_total_money = $overdue_total_money_tmp['money'];//某一天审批通过的逾期$num天以上的订单总金额
        $overdue_total_money_der_tmp = TmpStatsCodeIndex::find()->select('SUM(principal) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere('plan_fee_time < '.(strtotime(date('Y-m-d'))-86400*$num))
            ->andWhere('plan_fee_time > 0')
            ->andWhere($condition)
            ->asArray()->one();
        $overdue_total_money_der = $overdue_total_money_der_tmp['money'];//该时段内审批通过的所有订单总额-未放款的订单总额-未到还款日订单总额
        if($overdue_total_money_der){
            $overdue_percent = sprintf('%0.2f',$overdue_total_money/$overdue_total_money_der*100).'%';
        }else{
            $overdue_percent = 0;
        }
        $arr = [
            'overdue_total'=>$overdue_total,
            'overdue_percent'=>$overdue_percent
        ];
        return $arr;
    }

    public function getLoanMoney($condition,$day,$num_min,$num_max,$der){
        if($der){
            if($num_max!='max'){
                $num_min *= 100;
                $num_max *= 100;
                $match_count = TmpStatsCodeIndex::find()
                    ->select('SUM(money_amount) as money')
                    ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
                    ->andWhere('money_amount >= '.$num_min.' AND money_amount < '.$num_max)
                    ->andWhere(['after_status'=>7])
                    ->andWhere($condition)
                    ->asArray()->one();
            }else{
                $num_min *= 100;
                $match_count = TmpStatsCodeIndex::find()
                    ->select('SUM(money_amount) as money')
                    ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
                    ->andWhere('money_amount >= '.$num_min)
                    ->andWhere(['after_status'=>7])
                    ->andWhere($condition)
                    ->asArray()->one();
            }
            $percent = sprintf('%0.2f',$match_count['money']/$der*100).'%';
        }else{
            $percent = 0;
        }
        return $percent;
    }
    public function getAgvMoney($condition,$day){
        $order_count = TmpStatsCodeIndex::find()
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere(['after_status'=>7])
            ->andWhere($condition)
            ->count();//通过订单数
        $order_money = TmpStatsCodeIndex::find()
            ->select('SUM(money_amount) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere(['after_status'=>7])
            ->andWhere($condition)
            ->asArray()->one();//通过的订单金额 通过量 金额
        if($order_count&&isset($order_money['money'])&&$order_money['money']){
            $agv = sprintf('%0.2f',$order_money['money']/$order_count/100);//单位元
        }else{
            $agv = 0;
        }
        if($order_count){
            $pass_count = $order_count;
        }else{
            $pass_count = 0;
        }
        if($order_money){
            $pass_money = $order_money['money'];
        }else{
            $pass_money = 0;
        }
        return $retult = ['avg'=>$agv,'pass_count'=>$pass_count,'pass_money'=>$pass_money];
    }
    public function getPassRate($condition,$day,$pass_money){
        $total_money_tmp = TmpStatsCodeIndex::find()
            ->select('SUM(money_amount) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere($condition)
            ->asArray()->one();
        $total_money = $total_money_tmp['money'];//申请量 金额
        if($total_money){
            $pass_rate = sprintf('%0.2f',$pass_money/$total_money*100).'%';
        }else{
            $pass_rate = 0;
        }
        return $pass_rate;
    }

    public function getProfitMoney($condition,$day){
        //毛利润：M1收回金额-放出金额=某天审批通过的逾期30天内收回的总金额-该天放出总金额
        //某天审批通过的逾期30天内收回的总金额（包括逾期0天、30天）
        $M1_take_back_moeny_tmp = TmpStatsCodeIndex::find()
            ->select('SUM(true_total_money) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere(['after_status'=>7])
            ->andWhere('overdue_day <=30')
            ->andWhere(['status'=>4])
            ->andWhere($condition)
            ->asArray()->one();
        $M1_take_back_moeny = $M1_take_back_moeny_tmp['money'];

        //该天放出总金额
        $loan_amount_tmp = TmpStatsCodeIndex::find()
            ->select('SUM(principal) as money')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere(['after_status'=>7])
            ->andWhere($condition)
            ->asArray()->one();
        $loan_amount = $loan_amount_tmp['money'];
        $ProfitMoney = ($M1_take_back_moeny-$loan_amount)/100;//单位元
        return $ProfitMoney;
    }

    public function getPerPassPersonProfit($profitMoney,$pass_count){
        //人均毛利润：M1毛利润/通过人数
        if($pass_count){
            $perPassPersonProfit = sprintf('%0.2f',$profitMoney/$pass_count);
        }else{
            $perPassPersonProfit = 0;
        }
        return $perPassPersonProfit;
    }

    public function getPerApplyPersonProfit($condition,$day,$profitMoney){
        //M1毛利润/申请人数
        $apply_count = TmpStatsCodeIndex::find()
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->andWhere($condition)
            ->count();
        if($apply_count){
            $perApplyPersonProfit = sprintf('%0.2f',$profitMoney/$apply_count);
        }else{
            $perApplyPersonProfit = 0;
        }
        return $perApplyPersonProfit;
    }
    /**
     * @param $order_arr
     * @return array
     * 累加
     */
    public function sum_value($order_arr,$arr){
        $result_arr = [];
        //$arr   之前累加留下来的数组
        if($order_arr) {
            $pass_order = $this->get_valid_arr($order_arr);//分离来源
            $group_arr = $this->order_group_by_scoure($pass_order);//分离统计维度
//            echo 'order_arr';var_dump($order_arr);
//            echo 'group_arr';var_dump($group_arr);exit;
            foreach ($order_arr as $item) {
                $order_arr['arr_total'][$item['order_id']] = [
                    'order_id' => $item['order_id'],
                    'status' => $item['status'],
                    'money_amount' => $item['money_amount'],
                    'counter_fee' => $item['counter_fee'],
                ];
            }
            //$this->printMessage('减少后');
            $count_type = count($this->stats_arr) - 1;
            while ($count_type >= 0) {//用户类型  计算用户类型全部
                if ($count_type) {
                    foreach (UserLoanOrder::$sel_order_type as $key => $item) {
                        if (isset($group_arr[$this->stats_arr[$count_type]][$item]) && isset($group_arr[$this->stats_arr_all[$count_type]][$item])) {
                            $compare_arr = $group_arr[$this->stats_arr[$count_type]][$item];//满足条件的 通过的订单
                            $compare_arr_all = $group_arr[$this->stats_arr_all[$count_type]][$item];//满足条件的订单
                            //用于比较的数组  全是通过的
                            $this->printMessage('calculate_data');
                            if(!isset($arr[$this->stats_arr_name[$count_type]][$item])){
                                $arr[$this->stats_arr_name[$count_type]][$item] = [];
                            }
                            $result_arr[$this->stats_arr_name[$count_type]][$item] = $this->calculate_data($compare_arr, $compare_arr_all, $order_arr,$arr[$this->stats_arr_name[$count_type]][$item]);
                        }
                    }
                } elseif ($count_type == 0) {
                    foreach (UserLoanOrder::$sel_order_type as $key => $user_item) {
                        $one_group_pass = [];
                        $one_group_all = [];
                        $this->printMessage('全部start');
                        if(isset($pass_order[$user_item])){
                            foreach ($pass_order[$user_item] as $content) {
                                if ($content['status'] > 0) {//单来源 通过的订单
                                    $one_group_pass[] = $content['order_id'];
                                }
                                $one_group_all[] = $content['order_id'];
                            }
                            if(!isset($arr[$this->stats_arr_name[$count_type]][$item])){
                                $arr[$this->stats_arr_name[$count_type]][$item] = [];
                            }
                            $result_arr[$this->stats_arr_name[$count_type]][$user_item] = $this->calculate_data($one_group_pass, $one_group_all, $order_arr,$arr[$this->stats_arr_name[$count_type]][$item]);
                        }
                        $this->printMessage('全部');
                    }
                }
                $count_type--;
            }
        }
        return $result_arr;
    }

    public function getOverallCalculeData($arr,$day){
        if($arr){
            foreach ($arr as $k1 =>$item){
                foreach ($item as $k2=>$item2){


                    //申请人数
                    $applyPerson = self::getPassPersonAmount($item2['applyPersonAmountArr']);
                    //通过人数
                    $passPerson = self::getPassPersonAmount($item2['passPersonAmountArr']);

                    $order_total = count($item2['passPersonAmountArr']);
                    $overdue_total_D1 = $item2['overdue_total_D1'];
                    $overdue_total_S1 = $item2['overdue_total_S1'];
                    $overdue_total_M1 = $item2['overdue_total_M1'];

                    $overdue_percent_D1 = !empty($item2['repayment_money_amount']-$item2['spareMoney'])?sprintf("%0.2f", $item2['overdue_money_D1']/($item2['repayment_money_amount']-$item2['spareMoney'])*100).'%':0;
                    $overdue_percent_S1 = !empty($item2['repayment_money_amount']-$item2['spareMoney'])?sprintf("%0.2f", $item2['overdue_money_S1']/($item2['repayment_money_amount']-$item2['spareMoney'])*100).'%':0;
                    $overdue_percent_M1 = !empty($item2['repayment_money_amount']-$item2['spareMoney'])?sprintf("%0.2f", $item2['overdue_money_M1']/($item2['repayment_money_amount']-$item2['spareMoney'])*100).'%':0;

                    $agv_loan_money = number_format(!empty($order_total)?($item2['successOrderMoney']/$order_total/100):0);
                    foreach($this->range_arr as $key=>$value){
                        $range_percent[$key] = !empty($item2['successOrderMoney'])?sprintf("%0.2f", $item2['order_hit_money'][$key]/$item2['successOrderMoney']*100).'%':0;
                    }
                    $pass_rate = !empty($item2['sum_money_amount'])?sprintf("%0.2f",($item2['successOrderMoney']/$item2['sum_money_amount'])*100).'%':0;

                    $profitMoney = number_format(($item2['repaymentOrderMoney']+$item2['delayCounterMoney']+$item2['delayServiceMoney']+$item2['loanOrderCharge']-$item2['repayment_money_amount'])/100);
//                    $perPassPersonProfit = number_format(empty($passPerson) ? 0 :$profitMoney/$passPerson/100);
//                    $perApplyPersonProfit = number_format(empty($applyPerson) ? 0 :$profitMoney/$applyPerson/100);

                    $perPassPersonProfit = 0;
                    $perApplyPersonProfit = 0;
                    $arr = [
                        'agv_loan_money' => isset($agv_loan_money) ?$agv_loan_money : 0,
                        $this->range_name[0] => isset($range_percent[0]) ? $range_percent[0] : 0,
                        $this->range_name[1] => isset($range_percent[1]) ? $range_percent[1] : 0,
                        $this->range_name[2] => isset($range_percent[2]) ? $range_percent[2] : 0,
                        $this->range_name[3] => isset($range_percent[3]) ? $range_percent[3] : 0,
                        $this->range_name[4] => isset($range_percent[4]) ? $range_percent[4] : 0,
                        $this->range_name[5] => isset($range_percent[5]) ? $range_percent[5] : 0,
                        'pass_rate' => isset($pass_rate) ? $pass_rate : 0,
                        'overdue_total_D1' => isset($overdue_total_D1) ? $overdue_total_D1 : 0,
                        'overdue_total_S1' => isset($overdue_total_S1) ? $overdue_total_S1 : 0,
                        'overdue_total_M1' => isset($overdue_total_M1) ? $overdue_total_M1 : 0,
                        'overdue_percent_D1' => isset($overdue_percent_D1) ? $overdue_percent_D1 : 0,
                        'overdue_percent_S1' => isset($overdue_percent_S1) ? $overdue_percent_S1 : 0,
                        'overdue_percent_M1' => isset($overdue_percent_M1) ? $overdue_percent_M1 : 0,
                        'profitMoney' => isset($profitMoney) ? $profitMoney : 0,
                        'perPassPersonProfit' => isset($perPassPersonProfit) ? $perPassPersonProfit : 0,
                        'perApplyPersonProfit' => isset($perApplyPersonProfit) ? $perApplyPersonProfit : 0,
                    ];
                    //数据整合后写入数据库
                    $new_arr = [
                        'stats_date' => $day,
                        'stats_xdata' => json_encode($arr),
                        'stats_by' => $k1,
                        'stats_source' => $k2,
                    ];
                    $this->inputData($new_arr, $new_arr['stats_date'], $new_arr['stats_by'], $new_arr['stats_source']);
                }
            }
        }
    }

    /**
     *
     * @param $arr
     * @param $compare_arr
     * @param $compare_arr_all
     * @param $input_arr
     * @return array
     * 数据运算
     */
    public function calculate_data($compare_arr,$compare_arr_all,$input_arr,$arr){
        //D1入催量
        if(!isset($arr['overdue_total_D1'])){
            $arr['overdue_total_D1'] = 0;
        }
        //S1入催量
        if(!isset($arr['overdue_total_S1'])){
            $arr['overdue_total_S1'] = 0;
        }
        //M1入催量
        if(!isset($arr['overdue_total_M1'])){
            $arr['overdue_total_M1'] = 0;
        }


        //逾期订单金额
        if(!isset($arr['overdue_money_D1'])){
            $arr['overdue_money_D1'] = 0;
        }
        if(!isset($arr['overdue_money_S1'])){
            $arr['overdue_money_S1'] = 0;
        }
        if(!isset($arr['overdue_money_M1'])){
            $arr['overdue_money_M1'] = 0;
        }

        //审核通过订单金额
        if(!isset($arr['successOrderMoney'])){
            $arr['successOrderMoney'] = 0;
        }

        if($compare_arr){
            foreach ($compare_arr as $item) {
                $arr['successOrderMoney'] += $input_arr[$item]['money_amount'];//审批的全部金额
            }
        }
        //审批的全部金额
        if(!isset($arr['sum_money_amount'])){
            $arr['sum_money_amount'] = 0;
        }


        if($compare_arr_all){
            foreach ($compare_arr_all as $item) {
                $arr['sum_money_amount'] += $input_arr[$item]['money_amount'];//审批的全部金额
            }
        }

        //放款订单金额 = 该时段内审批通过的所有订单总额-未放款的订单总额*
        if(!isset($arr['repayment_money_amount'])){
            $arr['repayment_money_amount'] = 0;
        }


        //未到还款日订单金额*
        if(!isset($arr['spareMoney'])){
            $arr['spareMoney'] = 0;
        }

        //订单已到还款期的申请订单金额*
        if(!isset($arr['payableMoney'])){
            $arr['payableMoney'] = 0;
        }

        //已还款订单金额*
        if(!isset($arr['repaymentOrderMoney'])){
            $arr['repaymentOrderMoney'] = 0;
        }


        $OrderRepaymentData= self::getOrderRepaymentData($compare_arr);
//        echo 'compare_arr'.count($compare_arr).'||';
//        echo 'OrderRepaymentData'.count($OrderRepaymentData).'\t';
//        sleep(2);
        //获取入催量，逾期订单金额总数
        if($OrderRepaymentData){
            $repayment_id_arr = [];
            foreach ($OrderRepaymentData as $item1){
                $repayment_id_arr[] =  $item1['order_id'];
                $arr['repayment_money_amount'] += $item1['principal'];
                //
                if($item1['plan_fee_time'] >= strtotime(date('Y-m-d'))){
                    $arr['spareMoney'] +=$item1['principal'];
                }else{
                    $arr['payableMoney'] +=$item1['principal'];
                    if($item1['overdue_day'] <= 30){
                        $arr['repaymentOrderMoney'] +=$item1['true_total_money'];
                    }
                }
                //
                if($item1['overdue_day'] > 1){
                    $arr['overdue_total_D1']++;
                    $arr['overdue_money_D1'] +=$item1['principal'];
                }
                if($item1['overdue_day'] > 10){
                    $arr['overdue_total_S1']++;
                    $arr['overdue_money_S1'] +=$item1['principal'];
                }
                if($item1['overdue_day'] > 30){
                    $arr['overdue_total_M1']++;
                    $arr['overdue_money_M1'] +=$item1['principal'];
                }
            }
            //echo 'overdue_total_D1'.$arr['overdue_total_D1'].' ||';
        }

        //放款订单手续费*
        if(!isset($arr['loanOrderCharge'])){
            $arr['loanOrderCharge'] = 0;
        }

        //放款订单手续费
        if(isset($repayment_id_arr)){
            foreach($repayment_id_arr as $item){
                $arr['loanOrderCharge'] +=$input_arr[$item]['counter_fee'];
            }
        }

        //展期订单手续费*
        if(!isset($arr['delayCounterMoney'])){
            $arr['delayCounterMoney'] = 0;
        }

        //展期订单服务费*
        if(!isset($arr['delayServiceMoney'])){
            $arr['delayServiceMoney'] = 0;
        }

        $OrderDelayLogData = self::getOrderDelayLogData($compare_arr);
        if($OrderDelayLogData){
            foreach ($OrderDelayLogData as $item2){
                $arr['delayCounterMoney'] += $item2['counter_fee'];
                $arr['delayServiceMoney'] += $item2['service_fee'];
            }
        }

        //通过人数
        if(!isset($arr['passPersonAmountArr'])){
            $arr['passPersonAmountArr'] = [];
        }

        //申请人
        if(!isset($arr['applyPersonAmountArr'])){
            $arr['applyPersonAmountArr'] = [];
        }
        $arr['passPersonAmountArr'] = array_merge($arr['passPersonAmountArr'], $compare_arr);
        $arr['applyPersonAmountArr'] = array_merge($arr['applyPersonAmountArr'], $compare_arr_all);

        foreach($this->range_arr as $key=>$value){
            if(!isset($arr['order_hit_money'][$key])){
                $arr['order_hit_money'][$key] = 0;
            }
        }

        $arr['order_hit_money'] = $this->getRangeMoney($compare_arr,$input_arr,$arr['order_hit_money']);
        return $arr;
    }


    /**
     * @param array $arr
     * @return array
     * @throws yii\base\InvalidConfigException
     * 获取还款表中的相关数据
     */
    public function getOrderRepaymentData($arr = []){
        $page_size = 10000;
        $offset = 0;
        $count = UserLoanOrderRepayment::find()
            ->select('order_id')
            ->where(['order_id'=>$arr])
            ->andWhere('status = 4')
            ->count('order_id',Yii::$app->get('db_kdkj_rd'));
        $data_arr = [];
        while($count>$offset){
            $result = UserLoanOrderRepayment::find()
                ->select('order_id,overdue_day,true_total_money,principal,plan_fee_time')
                ->where(['order_id'=>$arr])
                ->andWhere('status = 4')
                ->limit($page_size)->offset($offset)
                ->orderBy('order_id asc')
                ->asArray()
                ->all(Yii::$app->get('db_kdkj_rd'));

            foreach ($result as $item){
                $data_arr[$item['order_id']] = $item;
            }
            $offset += $page_size;
        }
        return $data_arr;
    }


    /**
     * @param $arr
     * @return array
     * @throws yii\base\InvalidConfigException
     * 获取展期表中相关数据
     */
    public function getOrderDelayLogData($arr = []){
        $page_size = 10000;
        $offset = 0;
        $count = UserLoanOrderDelayLog::find()
            ->select('order_id')
            ->where(['order_id'=>$arr])
            ->andWhere('status = 1')
            ->count('order_id',Yii::$app->get('db_kdkj_rd'));
        $data_arr = [];
        while($count>$offset) {
            $result = UserLoanOrderDelayLog::find()
                ->select('order_id,counter_fee,service_fee')
                ->where(['order_id' => $arr])
                ->andWhere('status = 1')
                ->limit($page_size)->offset($offset)
                ->orderBy('order_id asc')
                ->asArray()
                ->all(Yii::$app->get('db_kdkj_rd'));
            foreach ($result as $item){
                $data_arr[$item['order_id']] = $item;
            }
            $offset += $page_size;
        }
        return $data_arr;
    }

    /**
     * @param $arr
     * @return int|string
     * @throws yii\base\InvalidConfigException
     * 获取申请借款用户人数
     */
    public function getApplyPersonAmount($arr){
        $apply_person = UserLoanOrder::find()
            ->select('user_id')
            ->distinct()
            ->where(['id'=>$arr])
            ->count('user_id',Yii::$app->get('db_kdkj_rd'));
        return $apply_person;
    }

    /**
     * @param $arr
     * @return int|string
     * @throws yii\base\InvalidConfigException
     * 获取借款通过用户人数
     */
    public function getPassPersonAmount($arr){
        $pass_person = UserLoanOrderRepayment::find()
            ->select('user_id')
            ->distinct()
            ->where(['order_id'=>$arr])
            ->count('user_id',Yii::$app->get('db_kdkj_rd'));
        return $pass_person;
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

    protected function printMessage($message){
        $pid = posix_getpid();
        $date = date('Y-m-d H:i:s');
        $mem = floor(memory_get_usage(true)/1024/1024) . 'MB';
        //时间 进程号 内存使用量 日志内容
        //echo "{$date} {$pid} $mem {$message} \n";
    }

    public function initResultArr(){
        $content = [
            'overdue_total_D1'=>0,
            'overdue_D1_money'=>0,
            'overdue_D1_money_der'=>0,
            'overdue_total_S1'=>0,
            'overdue_S1_money'=>0,
            'overdue_S1_money_der'=>0,
            'overdue_total_M1'=>0,
            'overdue_M1_money'=>0,
            'overdue_M1_money_der'=>0,
            'check_pass_money'=>0,
            'check_pass_order'=>0,
            'range_1_to_1000'=>0,
            'range_1000_to_1500'=>0,
            'range_1500_to_2000'=>0,
            'range_2000_to_2500'=>0,
            'range_2500_to_3000'=>0,
            'range_3000_to_max'=>0,
            'apply_money'=>0,
            'take_back_money'=>0,
            'loan_out_money'=>0,
            'apply_order'=>0,
            'pass_people'=>0,
            'apply_people'=>0,
        ];
        $arr = [];
        $user_arr = [];
        foreach(UserLoanOrder::$sub_order_type as $key_sub=>$item_sub){
            foreach(StatsCodeIndex::$stats_by as $key_by=>$item_by){
                switch($key_by){
                    //case StatsCodeIndex::STATS_ALL : $arr[$key_sub]['all'] = $content;break;
                    case StatsCodeIndex::STATS_NEW_USER : $arr[$key_sub][StatsCodeIndex::STATS_NEW_USER] = $content;break;
                    case StatsCodeIndex::STATS_OLD_USER : $arr[$key_sub][StatsCodeIndex::STATS_OLD_USER] = $content;break;
                    case StatsCodeIndex::STATS_IS_ACTIVE : $arr[$key_sub][StatsCodeIndex::STATS_IS_ACTIVE] = $content;break;
                    case StatsCodeIndex::STATS_NO_ACTIVE : $arr[$key_sub][StatsCodeIndex::STATS_NO_ACTIVE] = $content;break;
                }
            }
        }
        foreach(UserLoanOrder::$sub_order_type as $key_sub=>$item_sub){
            foreach(StatsCodeIndex::$stats_by as $key_by=>$item_by){
                switch($key_by){
                    //case StatsCodeIndex::STATS_ALL : $user_arr[$key_sub]['all'] = $content;break;
                    case StatsCodeIndex::STATS_NEW_USER : $user_arr[$key_sub][StatsCodeIndex::STATS_NEW_USER] = [0=>[],7=>[]];break;
                    case StatsCodeIndex::STATS_OLD_USER : $user_arr[$key_sub][StatsCodeIndex::STATS_OLD_USER] = [0=>[],7=>[]];break;
                    case StatsCodeIndex::STATS_IS_ACTIVE : $user_arr[$key_sub][StatsCodeIndex::STATS_IS_ACTIVE] = [0=>[],7=>[]];break;
                    case StatsCodeIndex::STATS_NO_ACTIVE : $user_arr[$key_sub][StatsCodeIndex::STATS_NO_ACTIVE] = [0=>[],7=>[]];break;
                }
            }
        }
        return $re = ['content'=>$arr,'user_id'=>$user_arr];
    }
    public function initNewResultArr(){
        $content = [
            'overdue_total_D1'=>0,
            'overdue_total_S1'=>0,
            'overdue_total_M1'=>0,
            'overdue_percent_D1'=>0,
            'overdue_percent_S1'=>0,
            'overdue_percent_M1'=>0,
            'range_1_to_1000'=>0,
            'range_1000_to_1500'=>0,
            'range_1500_to_2000'=>0,
            'range_2000_to_2500'=>0,
            'range_2500_to_3000'=>0,
            'range_3000_to_max'=>0,
            'agv_loan_money'=>0,
            'pass_rate' =>0,
            'profitMoney' =>0,
            'perPassPersonProfit' =>0,
            'perApplyPersonProfit' =>0,
        ];
        $arr = [];
        foreach(UserLoanOrder::$sub_order_type as $key_sub=>$item_sub){
            foreach(StatsCodeIndex::$stats_by as $key_by=>$item_by){
                switch($key_by){
                    //case StatsCodeIndex::STATS_ALL : $arr[$key_sub]['all'] = $content;break;
                    case StatsCodeIndex::STATS_NEW_USER : $arr[$key_sub][StatsCodeIndex::STATS_NEW_USER] = $content;break;
                    case StatsCodeIndex::STATS_OLD_USER : $arr[$key_sub][StatsCodeIndex::STATS_OLD_USER] = $content;break;
                    case StatsCodeIndex::STATS_IS_ACTIVE : $arr[$key_sub][StatsCodeIndex::STATS_IS_ACTIVE] = $content;break;
                    case StatsCodeIndex::STATS_NO_ACTIVE : $arr[$key_sub][StatsCodeIndex::STATS_NO_ACTIVE] = $content;break;
                }
            }
        }
        return $arr;
    }

    /**
     *
     */
    public function getRangeDate(){
        $num = 60;
        while($num){
            $day = date('Y-m-d',strtotime(date('Y-m-d'))-$num*86400);
            var_dump($day);
            $this->getDate($day);
            $num--;
        }
    }
    /**
     * @param $day
     * @return array|yii\db\ActiveRecord[]
     */
    public function getDate($day){
        $result_arr_tmp = $this->initResultArr();
        $result_arr = $result_arr_tmp['content'];
        $user_id_arr = $result_arr_tmp['user_id'];
        $new_result = $this->initNewResultArr();
        //$user_id_tmp = $this->getUserIdArr($day);
        $total = TmpStatsCodeIndex::find()
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
            ->count();
        $page_start = 0;
        $page_size = 5000;
        while($page_start<$total){
            $data = TmpStatsCodeIndex::find()
                ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+86400))
                ->limit($page_size)->offset($page_start)
                ->asArray()
                ->all();
            $result_arr_tmp = $this->statsData($data,$result_arr,$user_id_arr);
            $result_arr = $result_arr_tmp['content'];
            $user_id_arr = $result_arr_tmp['user_id'];
            $page_start+=$page_size;
        }
        $user_id_arr_tmp = $this->countDistinct($user_id_arr);//去重人数统计
        $result_arr = $this->statsTotal($result_arr,$user_id_arr_tmp);//处理 ‘全部’条件下的统计数值
        $user_id_arr = [];
        $new_result = $this->dealData($result_arr,$new_result);
        foreach($new_result as $key_sub=>$item_sub){
            foreach($item_sub as $key_by=>$item_by){
                $data = json_encode($new_result[$key_sub][$key_by]);
                $new_arr = [
                    'stats_date' => $day,
                    'stats_xdata' => $data,
                    'stats_by' => $key_by,
                    'stats_source' => $key_sub,
                ];
                $this->inputData($new_arr,$day,$new_arr['stats_by'],$new_arr['stats_source']);
            }
        }
    }

    public function getUserIdArr($day){
        $arr = TmpStatsCodeIndex::find()
            ->select('order_id,user_id')
            ->where('checked_at >= '.strtotime($day).' AND checked_at < '.(strtotime($day)+3*86400))
            ->asArray()->all();
//        $new_arr = json_encode($arr);
//        var_dump($new_arr);
//        exit;
        return $arr;
    }

    public function countDistinct($user_id_arr){
        $retult = [];
        foreach($user_id_arr as $key_sub=>$item_sub){
            foreach($item_sub as $key_by=>$item_by) {
                $retult[$key_sub][$key_by][0] = count($user_id_arr[$key_sub][$key_by][0]);
                $retult[$key_sub][$key_by][7] = count($user_id_arr[$key_sub][$key_by][7]);
            }
        }
        return $retult;
    }

    public function statsData($arr,$result,$user_id_arr){
        if($arr){
            foreach($arr as $item){
                if(($item['order_id']>$item['is_first'])&&($item['is_first']>0)){
                    $aa = 2;//老用户
                }else{
                    $aa = 1;
                }
                if($aa == 1){//新老用户
                    $result[$item['sub_order_type']][StatsCodeIndex::STATS_NEW_USER] = $this->sumData($result[$item['sub_order_type']][StatsCodeIndex::STATS_NEW_USER],$item);
                    if($item['after_status']>0) {
                        $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_NEW_USER][7][$item['user_id']] = $item['order_id'];
                    }
                    $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_NEW_USER][0][$item['user_id']] = $item['order_id'];
                }else{
                    $result[$item['sub_order_type']][StatsCodeIndex::STATS_OLD_USER] = $this->sumData($result[$item['sub_order_type']][StatsCodeIndex::STATS_OLD_USER],$item);
                    if($item['after_status']>0) {
                        $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_OLD_USER][7][$item['user_id']] = $item['order_id'];
                    }
                    $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_OLD_USER][0][$item['user_id']] = $item['order_id'];
                }
                if($item['tree']=='normal'){//活跃非活跃
                    $result[$item['sub_order_type']][StatsCodeIndex::STATS_IS_ACTIVE] = $this->sumData($result[$item['sub_order_type']][StatsCodeIndex::STATS_IS_ACTIVE],$item);
                    if($item['after_status']>0) {
                        $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_IS_ACTIVE][7][$item['user_id']] = $item['order_id'];
                    }
                    $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_IS_ACTIVE][0][$item['user_id']] = $item['order_id'];
                }else{
                    $result[$item['sub_order_type']][StatsCodeIndex::STATS_NO_ACTIVE] = $this->sumData($result[$item['sub_order_type']][StatsCodeIndex::STATS_NO_ACTIVE],$item);
                    if($item['after_status']>0) {
                        $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_NO_ACTIVE][7][$item['user_id']] = $item['order_id'];
                    }
                    $user_id_arr[$item['sub_order_type']][StatsCodeIndex::STATS_NO_ACTIVE][0][$item['user_id']] = $item['order_id'];
                }
            }
            return $re = [
                'content' => $result,
                'user_id' => $user_id_arr,
            ];
        }
    }
    public function sumData($result,$arr){
        $day = date('Y-m-d');
        if($arr['plan_fee_time']>0){
            if($arr['plan_fee_time']<(strtotime($day)-1*86400)) {
                if ($arr['overdue_day'] > 1) {
                    $result['overdue_total_D1']++;
                    $result['overdue_D1_money'] += $arr['principal'];
                }
                $result['overdue_D1_money_der'] += $arr['principal'];
                if ($arr['plan_fee_time'] < (strtotime($day) - 10 * 86400)) {
                    if ($arr['overdue_day'] > 10) {
                        $result['overdue_total_S1']++;
                        $result['overdue_S1_money'] += $arr['principal'];
                    }
                    $result['overdue_S1_money_der'] += $arr['principal'];
                    if ($arr['plan_fee_time'] < (strtotime($day) - 30 * 86400)) {
                        if ($arr['overdue_day'] > 30 && ($arr['plan_fee_time'] < (strtotime($day) - 30 * 86400))) {
                            $result['overdue_total_M1']++;
                            $result['overdue_M1_money'] += $arr['principal'];
                        }
                        $result['overdue_M1_money_der'] += $arr['principal'];
                    }
                }
            }
        }


        if($arr['after_status']==7){//审核通过的订单数  和审核通过的订单金额
            $result['check_pass_money']+=$arr['money_amount'];
            $result['check_pass_order']++;
            if($arr['money_amount']<100000){
                $result['range_1_to_1000'] += $arr['money_amount'];
            }elseif($arr['money_amount']<150000){
                $result['range_1000_to_1500'] += $arr['money_amount'];
            }elseif($arr['money_amount']<200000){
                $result['range_1500_to_2000'] += $arr['money_amount'];
            }elseif($arr['money_amount']<250000){
                $result['range_2000_to_2500'] += $arr['money_amount'];
            }elseif($arr['money_amount']<300000){
                $result['range_2500_to_3000'] += $arr['money_amount'];
            }else{
                $result['range_3000_to_max'] += $arr['money_amount'];
            }
            $result['apply_money']+=$arr['money_amount'];
            if($arr['overdue_day']<=30){
                $result['take_back_money'] += $arr['true_total_money']+$arr['delay_service_fee']+$arr['delay_counter_fee'];
            }
            $result['loan_out_money'] += ($arr['principal']-$arr['counter_fee']);
        }else{
            $result['apply_money']+=$arr['money_amount'];
        }
        $result['apply_order']++;
        return $result;
    }
    public function statsTotal($result_arr,$user_id_arr){
        foreach($user_id_arr as $key_sub=>$item_sub){
            foreach($item_sub as $key_by=>$item_by) {
                $result_arr[$key_sub][$key_by]['pass_people'] = $user_id_arr[$key_sub][$key_by][7];
                $result_arr[$key_sub][$key_by]['apply_people'] = $user_id_arr[$key_sub][$key_by][0];
            }
        }
        //‘全部’  维度的求和统计
        //来源的全部   以及全部的全部
        foreach(UserLoanOrder::$sub_order_type as $key_sub=>$item_sub){
            if($key_sub != UserLoanOrder::SUB_TYPE_ALL){
                foreach($result_arr[$key_sub][StatsCodeIndex::STATS_IS_ACTIVE] as $content_key=>$content_item){
                    $result_arr[$key_sub][StatsCodeIndex::STATS_ALL][$content_key] += $content_item;
                    $result_arr[UserLoanOrder::SUB_TYPE_ALL][StatsCodeIndex::STATS_ALL][$content_key] += $content_item;
                }
                foreach($result_arr[$key_sub][StatsCodeIndex::STATS_NO_ACTIVE] as $content_key=>$content_item){
                    $result_arr[$key_sub][StatsCodeIndex::STATS_ALL][$content_key] += $content_item;
                    $result_arr[UserLoanOrder::SUB_TYPE_ALL][StatsCodeIndex::STATS_ALL][$content_key] += $content_item;
                }
            }
        }
        //统计维度的全部
        foreach(StatsCodeIndex::$stats_by as $key_by=>$item_by){
            if($key_by != StatsCodeIndex::STATS_ALL){
                foreach($result_arr as $key_sub=>$item_sub){//***有问题 9号来解决/
                    foreach($result_arr[$key_by] as $content_key=>$content_item){
                        $result_arr[UserLoanOrder::SUB_TYPE_ALL][$key_by][$content_key] += $content_item;
                    }
                    foreach($result_arr[$key_by] as $content_key=>$content_item){
                        $result_arr[UserLoanOrder::SUB_TYPE_ALL][$key_by][$content_key] += $content_item;
                    }
                }
            }
        }
        return $result_arr;
    }
    public function dealData($result_arr,$new_result){
        foreach($result_arr as $key_sub=>$item_sub){
            foreach($item_sub as $key_by=>$item_by){
                $new_result[$key_sub][$key_by]['overdue_total_D1'] = $result_arr[$key_sub][$key_by]['overdue_total_D1'];
                $new_result[$key_sub][$key_by]['overdue_total_S1'] = $result_arr[$key_sub][$key_by]['overdue_total_S1'];
                $new_result[$key_sub][$key_by]['overdue_total_M1'] = $result_arr[$key_sub][$key_by]['overdue_total_M1'];
                $new_result[$key_sub][$key_by]['overdue_percent_D1'] = !empty($result_arr[$key_sub][$key_by]['overdue_D1_money_der'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['overdue_D1_money']/$result_arr[$key_sub][$key_by]['overdue_D1_money_der']*100).'%':0;
                $new_result[$key_sub][$key_by]['overdue_percent_S1'] = !empty($result_arr[$key_sub][$key_by]['overdue_S1_money_der'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['overdue_S1_money']/$result_arr[$key_sub][$key_by]['overdue_D1_money_der']*100).'%':0;
                $new_result[$key_sub][$key_by]['overdue_percent_M1'] = !empty($result_arr[$key_sub][$key_by]['overdue_M1_money_der'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['overdue_M1_money']/$result_arr[$key_sub][$key_by]['overdue_D1_money_der']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_1_to_1000'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_1_to_1000']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_1000_to_1500'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_1000_to_1500']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_1500_to_2000'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_1500_to_2000']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_2000_to_2500'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_2000_to_2500']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_2500_to_3000'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_2500_to_3000']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['range_3000_to_max'] = !empty($result_arr[$key_sub][$key_by]['check_pass_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['range_3000_to_max']/$result_arr[$key_sub][$key_by]['check_pass_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['agv_loan_money'] = !empty($result_arr[$key_sub][$key_by]['check_pass_order'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['check_pass_money']/$result_arr[$key_sub][$key_by]['check_pass_order']/100):0;
                $new_result[$key_sub][$key_by]['pass_rate'] = !empty($result_arr[$key_sub][$key_by]['apply_money'])?sprintf('%0.2f',$result_arr[$key_sub][$key_by]['check_pass_money']/$result_arr[$key_sub][$key_by]['apply_money']*100).'%':0;
                $new_result[$key_sub][$key_by]['profitMoney'] = !empty($result_arr[$key_sub][$key_by]['take_back_money'])?sprintf('%0.2f',($result_arr[$key_sub][$key_by]['take_back_money']-$result_arr[$key_sub][$key_by]['loan_out_money'])/100):0;
                $new_result[$key_sub][$key_by]['perPassPersonProfit'] = !empty($result_arr[$key_sub][$key_by]['pass_people'])?sprintf('%0.2f',($new_result[$key_sub][$key_by]['profitMoney']/$result_arr[$key_sub][$key_by]['pass_people'])):0;
                $new_result[$key_sub][$key_by]['perApplyPersonProfit'] = !empty($result_arr[$key_sub][$key_by]['apply_people'])?sprintf('%0.2f',($new_result[$key_sub][$key_by]['profitMoney']/$result_arr[$key_sub][$key_by]['apply_people'])):0;
            }
        }
        return $new_result;
    }
}
?>