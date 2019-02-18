<?php

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\UserSchedule;


class OrderStatisticsByStatus extends \yii\db\ActiveRecord 
{
    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }

    public static function tableName()
    {
        return '{{%order_overview_statistics_bystatus}}';
    }
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_assist');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     *返回最近一次更新时间
     */
    public static function last_update_time($status = ''){
        // return self::find()->select('create_at')->orderBy(['create_at'=>SORT_DESC])->limit(1)->createCommand()->getRawSql();
        $query = self::find()->select('create_at')->orderBy(['create_at'=>SORT_DESC])->limit(1);
        if(!empty($status) && array_key_exists($status, LoanCollectionOrder::$status))  $query->where(['order_status'=>$status]);
        return $query->scalar();
    }

    public static function last_update_time_rd($status = ''){
        // return self::find()->select('create_at')->orderBy(['create_at'=>SORT_DESC])->limit(1)->createCommand()->getRawSql();
        $query = self::find()->select('create_at')->orderBy(['create_at'=>SORT_DESC])->limit(1);
        if(!empty($status) && array_key_exists($status, LoanCollectionOrder::$status))  $query->where(['order_status'=>$status]);
        return $query->scalar(self::getDb_rd());
    }


    /**
     *
     */
   public static function collection_input_statistics($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            $transaction= self::getDb()->beginTransaction();//创建事务
            foreach ($input_orders as $key => $each) {
                // $condition = "`create_at` >= ".strtotime(date("Y-m-d 0:0:0"))." AND `create_at` < ".strtotime(date("Y-m-d 23:59:59"))." AND `order_status` = ".$each['status'];
                // $item = self::find()->where($condition)->one();
                $item = new self();
                $item->order_status = $each['status'];
                $item->amount = $each['amount'];//订单数
                $item->principal = $each['principal'];//本金
                $item->late_fee = $each['late_fee'];//
                $item->true_late_fee = $each['true_late_fee'];//
                if(!$item->save())  throw new Exception("入催统计更新失败：录入数据表失败，录入内容：".json_encode($input_orders));
            }
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
            foreach ($input_orders as $key => $each) {
                // $condition = "`create_at` >= ".strtotime(date("Y-m-d 0:0:0"))." AND `create_at` < ".strtotime(date("Y-m-d 23:59:59"))." AND `order_status` = ".$each['status'];
                // $item = self::find()->where($condition)->one();
                $item = new self('db_mhk_assist');
                $item->order_status = $each['status'];
                $item->amount = $each['amount'];//订单数
                $item->principal = $each['principal'];//本金
                $item->late_fee = $each['late_fee'];//
                $item->true_late_fee = $each['true_late_fee'];//
                if(!$item->save())  throw new Exception("入催统计更新失败：录入数据表失败，录入内容：".json_encode($input_orders));
            }
            // return true;
        }catch(Exception $e){
            // throw new Exception($e->getMessage());
            Yii::error($e->getMessage());
            return false;

        }
        return true;

    }
   /**
    *昨日订单概览
    *【催收中】、【承诺还款】、【催收成功】取更新的最新数据
    */
   public static function total($time = null,$sub_order_type = LoanCollectionOrder::SUB_TYPE_BT){
        try{
            // $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0'))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59')) ." AND `order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROGRESS;
            $db = $sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK?Yii::$app->get('db_mhk_assist'):self::getDb();
            if(!empty($time))
            {
                $y= substr($time,0,4);
                $m = substr($time,5,2);
                $d = substr($time,8,2);
                $startTime = mktime(0,0,0,$m,$d,$y);
                $endTime = mktime(23,59,59,$m,$d,$y);
                if($endTime<time())
                {
                    $endTime += 3600;
                }
                $arr1 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROGRESS)->andFilterWhere(['between','create_at',$startTime,$endTime])->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);
                $arr2 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROMISE)->andFilterWhere(['between','create_at',$startTime,$endTime])->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);
                $arr3 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_FINISH)->andFilterWhere(['between','create_at',$startTime,$endTime])->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);
            }
            else
            {
                $arr1 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROGRESS)->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);
                /* echo '<pre>';
                 var_dump($arr1);die;*/
                // $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0'))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59')) ." AND `order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROMISE;
                $arr2 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_PROMISE)->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);

                //old:
                // $condition = " `order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                // $arr3 = self::find()->select("SUM(amount) AS amount, SUM(principal) AS principal, SUM(true_late_fee) AS true_late_fee, SUM(late_fee) AS late_fee, order_status")->where($condition)->asArray()->indexBy('order_status')->all();
                //new:
                // $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0'))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59')) ." AND `order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                $arr3 = self::find()->where("`order_status` = ".LoanCollectionOrder::STATUS_COLLECTION_FINISH)->asArray()->indexBy('order_status')->orderBy(['create_at'=>SORT_DESC])->limit(1)->all($db);
            }
             // echo '<pre>'; print_r($arr2);echo '</pre>';
            $arr = ($arr1+$arr2+$arr3);
              //echo '<pre>'; print_r($arr);echo '</pre>';exit;
            return $arr;
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
