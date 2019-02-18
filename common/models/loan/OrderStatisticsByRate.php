<?php

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\UserSchedule;
use common\models\UserLoanOrderRepayment;

class OrderStatisticsByRate extends \yii\db\ActiveRecord 
{
    const REPLACE = 0;//覆盖数据
    const APPEND = 1;//累加数据

    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }
    public static function tableName()
    {
        return '{{%order_overview_statistics_byrate}}';
    }
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_assist');
    }

    //要统计催回率的天数：
    public static $rate_days = array(1,2,3,4,5,6,7,"8-10","11-30","31-60","61-90","91-999");

    /**
     *返回指定逾期天数时对应的入催订单额
     */
    public static function collectionAmount_of_overdueDay($overdueDay = 1){
        $overdueDay--; 
        $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0', strtotime("-{$overdueDay} day"))). " AND `create_at` < ".strtotime(date('Y-m-d 23:59:59', strtotime("-{$overdueDay} day")));
        return self::find()->select('collection_amount')->where($condition)->scalar();
    }

    /**
     *返回今天当前催回本金
     */
    public static function rate_now(){
        $overdue_days = self::$rate_days;

        $cuiTime = time();
        
        foreach ($overdue_days as $key => $day) {
            if(!is_int($day)){
                $range = explode('-', $day);
                $day = $range[1];
            }
            $index = 'repay_'.$day."_amount";
            $deadlineTime = strtotime(date('Y-m-d')) - ($day * 24 * 3600);
            $query = "SELECT SUM(IF(true_total_money > principal , principal ,true_total_money)) AS total FROM ".UserLoanOrderRepayment::tableName()." WHERE `status`=".UserLoanOrderRepayment::STATUS_REPAY_COMPLETE." AND overdue_day = ".$day." AND plan_fee_time >  ".strtotime(date('Y-m-d 0:0:0', $deadlineTime))." AND plan_fee_time < ".strtotime(date('Y-m-d 23:59:59', $deadlineTime));
            
            $res = Yii::$app->db_kdkj_rd->createCommand($query)->queryAll();
            $orders_amount[$index] = $res[0]['total'];//逾期指定天数且还款成功的订单额
        }
               
        return $orders_amount;
        // print_r($orders_amount);exit;
        // return $res[0]['total'];//逾期指定天数且还款成功的订单额
    }




   /**
    *统计更新3(催回率部分)
    */
   public static function rate_amount($input_orders = array(), $type = self::REPLACE){
        try{
            if(empty($input_orders)) return false;
            $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));
            $record = self::find()->where($condition)->one();
            if(empty($record)){
                $record = new self();

            }else{
                unset($input_orders['create_at']);//防止覆盖最初信息
            }
            
            if($type == self::APPEND){
                foreach ($input_orders as $key => $value) {
                    $record->$key = ($value + $record->$key);
                }

            }else{
                foreach ($input_orders as $key => $value) {
                    $record->$key = $value;
                }
            }
            
            
            $record->save();
            
        }catch(Exception $e){
            // throw new Exception($e->getMessage());
            Yii::error($e->getMessage());
            return false;
        
        }
        return true;
   }
    /**
     *统计更新3(催回率部分)
     */
    public static function rate_amount_mhk($input_orders = array(), $type = self::REPLACE){
        try{
            if(empty($input_orders)) return false;
            $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));
            $record = self::find()->where($condition)->one(Yii::$app->get('db_mhk_assist'));
            if(empty($record)){
                $record = new self('db_mhk_assist');

            }else{
                unset($input_orders['create_at']);//防止覆盖最初信息
                $record::$connect_name = 'db_mhk_assist';
            }

            if($type == self::APPEND){
                foreach ($input_orders as $key => $value) {
                    $record->$key = ($value + $record->$key);
                }

            }else{
                foreach ($input_orders as $key => $value) {
                    $record->$key = $value;
                }
            }


            $record->save();

        }catch(Exception $e){
            // throw new Exception($e->getMessage());
            Yii::error($e->getMessage());
            return false;

        }
        return true;
    }
   /**
    *返回指定逾期天数的指定列信息
    *@param int $day 逾期天数（相对于今天来说）
    *@return array time:记录时间， info:指定列信息
    */
   public static function overdueDay_collection($day = 0, $column, $offset ='' ){
        if(empty($offset))  $offset = time();
        $collection_day =  $offset + $day * 86400;
        $collection_day_start = strtotime(date("Y-m-d", $collection_day));
        $collection_day_end = strtotime(date("Y-m-d 23:59:59", $collection_day));
        $principal = self::find()->select($column)->where("`create_at` >= ".$collection_day_start." AND `create_at` <= ".$collection_day_end)->scalar();
        return array('time'=> $collection_day, 'info'=>$principal);
   }

   

   /**
    *返回催收统计信息
    *注意：其中，应还订单数为前一天数据
    *
    */
    public static function lists($start=0, $end=0){
        try{
            if(empty($start)) $start=strtotime(date('Y-m-d 0:0:0'));
            if(empty($end)) $end=strtotime(date('Y-m-d 23:59:59'));
            $condition = " `create_at` >= ".$start." AND `create_at` < ".$end;
            $lists = self::find()->select('*')->where($condition)->asArray()->orderBy(['create_at'=>SORT_DESC])->all();
            if(!empty($lists)){

                $tmp = array();
                foreach ($lists as $key => $item) {

                    $condition2 = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0', ($item['create_at'] - 24*3600)))." AND `create_at` < ".strtotime(date('Y-m-d 0:0:0', $item['create_at']));
                    $lists[$key]['deadline_amount'] = self::find()->select('deadline_amount')->where($condition2)->orderBy(['create_at'=>SORT_DESC])->scalar();//使用前一天的应还总额，用于计算入催率
                }
            }
            return $lists;
            
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            
        }
   }

    public function beforeSave($insert)  
    {  
        if(parent::beforeSave($insert)){  
            if($this->isNewRecord){  
                if(empty($this->create_at)) $this->create_at = time();

            }else{  
               $this->update_at = time();
            }  
            return true;  
        }else{  
            return false;  
        }  
    }  



    
}
