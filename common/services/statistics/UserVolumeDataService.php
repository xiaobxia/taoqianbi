<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/12
 * Time: 13:32
 */
namespace common\services\statistics;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use common\models\LoanPerson;
use common\models\UserLoanOrderDelayLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\StatsUserVolumeData;
use common\models\UserLoanOrder;

class UserVolumeDataService extends Component
{
    public function getUserVolumeData(){//获取用户体量数据
        $num = 62;
        try{
            while($num-- > 0){
                $datetime_start = date('Y-m-d',time()-($num+1)*86400);
                $datetime_end = date('Y-m-d',time()-$num*86400);
                echo '日期：';var_dump($datetime_start);

                $loan_order_amount = UserOrderLoanCheckLog::find()
                    ->select('order_id','user_id')
                    ->distinct()
                    ->where('created_at > '.strtotime($datetime_start) . ' and created_at <='.strtotime($datetime_end))
                    ->andWhere('`before_status` = 0')
                    ->andWhere('`after_status` = 7')
                    ->andWhere('`type` = 1')
                    ->count('order_id',Yii::$app->get('db_kdkj_rd'));

                $page_size = 10000;
                $page_start = 0;

                $loan_order_id = [];
                $loan_person_id = [];
                while($loan_order_amount>$page_start) {
                    $order_total_arr = UserOrderLoanCheckLog::find()
                        ->select('order_id,user_id')
                        ->distinct()
                        ->where('created_at>'.strtotime($datetime_start).' AND created_at<='.strtotime($datetime_end))
                        ->andWhere('`before_status` = 0')
                        ->andWhere('`type` = 1')
                        ->andWhere('`after_status` = 7')
                        ->limit($page_size)->offset($page_start)
                        ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                    if($order_total_arr){
                        foreach ($order_total_arr as $item){
                            $loan_order_id[] = $item['order_id'];
                            $loan_person_id[] = $item['user_id'];
                        }
                    }

                    $page_start += $page_size;
                }

                //新老用户区分
                $loan_person_data = self::getCustomerTypeData($loan_person_id);
                $new_loan_person = 0;
                $old_loan_person = 0;
                $new_person_id_arr = [];
                $old_person_id_arr = [];
                if($loan_person_data){
                    foreach ($loan_person_data as $item1){
                        if($item1['customer_type']==0){
                            $new_loan_person++;
                            $new_person_id_arr[] = $item1['id'];
                        }elseif($item1['customer_type']==1){
                            $old_loan_person++;
                            $old_person_id_arr[] = $item1['id'];
                        }
                    }
                }
                //借款期限区分
                $loan_term_data = self::getLoanTermData($loan_order_id);
                $loan_term_7 = 0;
                $loan_term_14 = 0;
                $loan_term_21 = 0;
                $loan_term_7_id_arr = [];
                $loan_term_14_id_arr = [];
                $loan_term_21_id_arr = [];
                if($loan_term_data){
                    foreach ($loan_term_data as $item2){
                        if($item2['loan_term']==7){
                            $loan_term_7++;
                            $loan_term_7_id_arr[] = $item2['id'];
                        }elseif($item2['loan_term']==14){
                            $loan_term_14++;
                            $loan_term_14_id_arr[] = $item2['id'];
                        }elseif($item2['loan_term']==21){
                            $loan_term_21++;
                            $loan_term_21_id_arr[] = $item2['id'];
                        }
                    }
                }
                //展期次数区分
                $delay_times_data = self::getDelayTimesData($loan_order_id);
                $delay_once = 0;
                $delay_twice = 0;
                $delay_three = 0;
                $delay_once_id_arr = [];
                $delay_twice_id_arr = [];
                $delay_three_id_arr = [];
                if($delay_times_data){
                    foreach ($delay_times_data as $item3){
                        if($item3['times']==1){
                            $delay_once++;
                            $delay_once_id_arr[] = $item3['order_id'];
                        }elseif($item3['times']==2){
                            $delay_twice++;
                            $delay_twice_id_arr[] = $item3['order_id'];
                        }elseif($item3['times']==3){
                            $delay_three++;
                            $delay_three_id_arr[] = $item3['order_id'];
                        }
                    }
                }

                //************统计单数********************
                $data['loan_order_total'] = count($loan_order_id);
                $data['new_loan_person_order_total'] = $new_loan_person;
                $data['old_loan_person_order_total'] = $old_loan_person;
                $data['loan_term_7_order_total'] = $loan_term_7;
                $data['loan_term_14_order_total'] = $loan_term_14;
                $data['loan_term_21_order_total'] = $loan_term_21;
                $data['delay_order_total'] = $delay_once + $delay_twice + $delay_three;
                $data['delay_once_order_total'] = $delay_once;
                $data['delay_twice_order_total'] = $delay_twice;
                $data['delay_three_order_total'] = $delay_three;
                $data['new_loan_person_order_perc'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $new_loan_person / $data['loan_order_total']*100).'%':0;
                $data['old_loan_person_order_perc'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $old_loan_person / $data['loan_order_total']*100).'%':0;
                $data['loan_term_7_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $loan_term_7 / $data['loan_order_total']*100).'%':0;
                $data['loan_term_14_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $loan_term_14 / $data['loan_order_total']*100).'%':0;
                $data['loan_term_21_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $loan_term_21 / $data['loan_order_total']*100).'%':0;
                $data['delay_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $data['delay_order_total'] / $data['loan_order_total']*100).'%':0;
                $data['delay_once_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $data['delay_once_order_total'] / $data['loan_order_total']*100).'%':0;
                $data['delay_twice_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $data['delay_twice_order_total'] / $data['loan_order_total']*100).'%' :0;
                $data['delay_three_order_prec'] = $data['loan_order_total'] > 0 ?sprintf("%0.2f", $data['delay_three_order_total'] / $data['loan_order_total']*100).'%' :0;


                //************统计金额********************
                $data1['loan_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($loan_order_id)) ? 0 : self::getTotalMoneyData($loan_order_id)/100);
                $data1['new_loan_person_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($new_person_id_arr)) ? 0 : self::getTotalMoneyData($new_person_id_arr)/100);
                $data1['old_loan_person_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($old_person_id_arr)) ? 0 : self::getTotalMoneyData($old_person_id_arr)/100);
                $data1['loan_term_7_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($loan_term_7_id_arr)) ? 0 : self::getTotalMoneyData($loan_term_7_id_arr)/100);
                $data1['loan_term_14_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($loan_term_14_id_arr)) ? 0 : self::getTotalMoneyData($loan_term_14_id_arr)/100);
                $data1['loan_term_21_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($loan_term_21_id_arr)) ? 0 : self::getTotalMoneyData($loan_term_21_id_arr)/100);
                $data1['delay_once_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($delay_once_id_arr)) ? 0 : self::getTotalMoneyData($delay_once_id_arr)/100);
                $data1['delay_twice_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($delay_twice_id_arr)) ? 0 : self::getTotalMoneyData($delay_twice_id_arr)/100);
                $data1['delay_three_order_total_money'] = sprintf("%0.2f", empty(self::getTotalMoneyData($delay_three_id_arr)) ? 0 : self::getTotalMoneyData($delay_three_id_arr)/100);
                $data1['delay_order_total_money'] =$data1['delay_once_order_total_money'] + $data1['delay_twice_order_total_money'] + $data1['delay_three_order_total_money'];
                $data1['new_loan_person_money_perc'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['new_loan_person_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['old_loan_person_money_perc'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['old_loan_person_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['loan_term_7_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['loan_term_7_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['loan_term_14_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['loan_term_14_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['loan_term_21_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['loan_term_21_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['delay_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['delay_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['delay_once_money_prec'] = $data1['loan_order_total_money'] > 0 ? sprintf("%0.2f", $data1['delay_once_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['delay_twice_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['delay_twice_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;
                $data1['delay_three_money_prec'] = $data1['loan_order_total_money'] > 0 ?sprintf("%0.2f", $data1['delay_three_order_total_money'] / $data1['loan_order_total_money']*100).'%':0;

                $json_data_total = json_encode($data);
                $json_data_money = json_encode($data1);

                //按笔数统计的数据写入数据库
                $data_insert_total = [
                    'stats_date'=>$datetime_start,
                    'stats_xdata'=>$json_data_total,
                    'stats_by'=>StatsUserVolumeData::TYPE_ORDER,
                ];
                $this->inputData($data_insert_total,$datetime_start,StatsUserVolumeData::TYPE_ORDER);

                //按金额统计的数据写入数据库
                $data_insert_money = [
                    'stats_date'=>$datetime_start,
                    'stats_xdata'=>$json_data_money,
                    'stats_by'=>StatsUserVolumeData::TYPE_MONEY,
                ];

                $this->inputData($data_insert_money,$datetime_start,StatsUserVolumeData::TYPE_MONEY);
                //echo '单数统计：';var_dump($data_insert_total);
                //echo '金额统计：';var_dump($data_insert_money);

            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            exit;
        }
    }

    public function getCustomerTypeData($loan_person_id){
        $person_result = LoanPerson::find()
            ->select('id,customer_type')
            ->where(['id'=>$loan_person_id])
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $person_result;
    }

    public function  getLoanTermData($loan_order_id){
        $term_result = UserLoanOrder::find()
            ->select('id,loan_term')
            ->where(['id'=>$loan_order_id])
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $term_result;
    }

    public function  getDelayTimesData($loan_order_id){
        $delay_times_result = UserLoanOrderDelayLog::find()
            ->select('order_id,count(order_id) as times ')
            ->where(['order_id'=>$loan_order_id])
            ->andWhere('status = 1')
            ->groupBy('order_id')
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $delay_times_result;
    }

    public function getTotalMoneyData($arr){
        $total_money = UserLoanOrderRepayment::find()
            ->select('sum(principal) as money')
            ->where(['order_id'=>$arr])
            ->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        return $total_money['money'];
    }

    /**
     * @param $data
     * @param string $stats_time
     * @param $type
     * @return mixed
     */
    public function inputData($data,$stats_time = '0',$type){
        //有log_id则进行更新操作   无log_id则新建一条记录
        if($stats_time&&$log = StatsUserVolumeData::find()->where(['stats_date'=>$stats_time,'stats_by'=>$type])->one()){
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->stats_date;
        }else{
            $log = new StatsUserVolumeData();
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->stats_date;
        }
    }
}
?>