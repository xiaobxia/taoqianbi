<?php

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\UserSchedule;


class OrderStatisticsByGroup extends \yii\db\ActiveRecord 
{
    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }
    public static function tableName()
    {
        return '{{%order_overview_statistics_bygroup}}';
    }
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_assist');
    }


    /**
     *统计更新
     *@param array $overdue_orders 要处理的催收订单（来自催收订单表）
     *@param array $groups 要被转派入的催收组数组
     */
   public static function collection_input_statistics($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            $transaction = self::getDb()->beginTransaction();
            // foreach ($input_orders as $status => $each) {
                self::deleteAll("`order_status` = ".$input_orders['status']." AND `create_at` >= ".strtotime(date('Y-m-d 0:0:0')) ." AND `create_at` <= ".strtotime(date('Y-m-d 23:59:59')));
            
                foreach ($input_orders['groups'] as $p => $group) {
                    $item = new self();
                    $item->order_status = $input_orders['status'];
                    $item->amount = $group['amount'];//订单数
                    $item->principal = $group['principal'];//本金
                    $item->group = $group['id'];
                    if(!$item->save())  throw new Exception("统计更新失败：录入分布数据表失败");
                }
               
            // }
            $transaction->commit();
            // return true;
        }catch(Exception $e){
            $transaction->rollBack();
            // throw new Exception($e->getMessage());
            Yii::error($e->getMessage());
            return false;
        
        }
        return true;

   }
    public static function collection_input_statistics_mhk($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            $transaction = Yii::$app->db_mhk_assist->beginTransaction();
            // foreach ($input_orders as $status => $each) {
            self::$connect_name = 'db_mhk_assist';
            self::deleteAll("`order_status` = ".$input_orders['status']." AND `create_at` >= ".strtotime(date('Y-m-d 0:0:0')) ." AND `create_at` <= ".strtotime(date('Y-m-d 23:59:59')));
            foreach ($input_orders['groups'] as $p => $group) {
                $item = new self('db_mhk_assist');
                $item->order_status = $input_orders['status'];
                $item->amount = $group['amount'];//订单数
                $item->principal = $group['principal'];//本金
                $item->group = $group['id'];
                if(!$item->save())  throw new Exception("统计更新失败：录入分布数据表失败");
            }
            $transaction->commit();
        }catch(Exception $e){
            $transaction->rollBack();
            // throw new Exception($e->getMessage());
            Yii::error($e->getMessage());
            return false;

        }
        return true;

    }
   /**
    *昨日订单概览
    *【催收中】、【承诺还款】取当天更新的最新两条数据
    *【催收成功】则取所有数据的总和
    */
   public static function total($time =null,$sub_order_type = LoanCollectionOrder::SUB_TYPE_BT){
        try{
            $db = $sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK?Yii::$app->get('db_mhk_assist'):self::getDb();
            if(!empty($time))
            {
                $y= substr($time,0,4);
                $m = substr($time,5,2);
                $d = substr($time,8,2);
                $startTime = mktime(0,0,0,$m,$d,$y);
                $endTime = mktime(23,59,59,$m,$d,$y);
                $condition = " `create_at` >= ".$startTime." AND `create_at` < ".$endTime ." AND `order_status` IN (".implode(',', array( LoanCollectionOrder::STATUS_COLLECTION_PROGRESS, LoanCollectionOrder::STATUS_COLLECTION_PROMISE, LoanCollectionOrder::STATUS_COLLECTION_FINISH)).")";
            }
            else
            {
                $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0'))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59')) ." AND `order_status` IN (".implode(',', array( LoanCollectionOrder::STATUS_COLLECTION_PROGRESS, LoanCollectionOrder::STATUS_COLLECTION_PROMISE, LoanCollectionOrder::STATUS_COLLECTION_FINISH)).")";
            }
            // echo self::find()->where($condition)->orderBy(['create_at'=>SORT_DESC])->createCommand()->getRawSql();exit;
            $arr1 = self::find()->where($condition)->asArray()->orderBy(['create_at'=>SORT_DESC])->all($db);
            $res1 = array();
            foreach ($arr1 as $key => $item) {
                $res1[$item['group']][$item['order_status']]['amount'] = $item['amount'];
                $res1[$item['group']][$item['order_status']]['principal'] = $item['principal'];
            }
           
            return $res1;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            
        }
   }

   public static function total_2($sub_order_type = LoanCollectionOrder::SUB_TYPE_BT){
        try{
            $db = $sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK?Yii::$app->get('db_mhk_assist'):self::getDb();
            $condition = " `order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_FINISH;
            $arr2 = self::find()->select("SUM(amount) AS amount, SUM(principal) AS principal, order_status, group")->where($condition)->groupBy('group')->asArray()->all($db);
            $res2 = array();
            foreach ($arr2 as $key => $item) {
                $res2[$item['group']][$item['order_status']]['amount'] = $item['amount'];
                $res2[$item['group']][$item['order_status']]['principal'] = $item['principal'];

            }
            
            return $res2;
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
