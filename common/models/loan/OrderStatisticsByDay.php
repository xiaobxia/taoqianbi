<?php

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\UserSchedule;


class OrderStatisticsByDay extends \yii\db\ActiveRecord 
{

    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }

    public static function tableName()
    {
        return '{{%order_overview_statistics_byday}}';
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
    *统计更新1(入催部分)
    */
   public static function collection_input_statistics($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            if(!is_integer($input_orders['create_at'])){
              $input_orders['create_at'] = strtotime($input_orders['create_at']);
            }
            $start = strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']));
            $end = strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));
           
            $condition = " `create_at` >= ".$start." AND `create_at` < ".$end." ORDER BY `create_at` DESC";
            $record = self::find()->where($condition)->one();
             if(empty($record)){
                $record = new self();
            }else{
                unset($input_orders['create_at']);//防止覆盖最初信息
            }
            foreach ($input_orders as $key => $value) {
                $record->$key = $value;
            }
            if(!$record->save())    throw new Exception("更新入催统计记录失败, function:".__FUNCTION__);
            
            
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            return false;
        
        }
        return true;

   }

    public static function collection_input_statistics_mhk($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            if(!is_integer($input_orders['create_at'])){
                $input_orders['create_at'] = strtotime($input_orders['create_at']);
            }
            $start = strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']));
            $end = strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));

            $condition = " `create_at` >= ".$start." AND `create_at` < ".$end." ORDER BY `create_at` DESC";
            $record = self::find()->where($condition)->one(Yii::$app->get('db_mhk_assist'));
            if(empty($record)){
                $record = new self('db_mhk_assist');
            }else{
                unset($input_orders['create_at']);//防止覆盖最初信息
                $record::$connect_name = 'db_mhk_assist';
            }
            foreach ($input_orders as $key => $value) {
                $record->$key = $value;
            }
            if(!$record->save())    throw new Exception("更新入催统计记录失败, function:".__FUNCTION__);


        }catch(Exception $e){
            throw new Exception($e->getMessage());
            return false;

        }
        return true;

    }

   /**
    *统计更新2(还款部分)
    */
   public static function repay_records($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
            $condition = " `create_at` >= ".strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']))." AND `create_at` < ".strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));
            $record = self::find()->where($condition)->one();
            if(empty($record))  $record = new self();
            $record->repay_amount = $input_orders['repay_amount'];
            $record->repay_principal = $input_orders['repay_principal'];
            $record->repay_late_fee = $input_orders['repay_late_fee'];
            if(!$record->save())    throw new Exception("更新还款统计记录失败, function:".__FUNCTION__);
            
            
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            return false;
        
        }
        return true;
   }

   

   

   /**
    *返回催收统计信息
    *
    */
   public static function lists($start=0, $end=0,$sub_order_type = LoanCollectionOrder::SUB_TYPE_BT){
        try{
            $db = $sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK?Yii::$app->get('db_mhk_assist'):self::getDb();
            if(empty($start)) $start=strtotime(date('Y-m-d 0:0:0'));
            if(empty($end)) $end=strtotime(date('Y-m-d 23:59:59'));
            $condition = " `create_at` >= ".$start." AND `create_at` < ".$end;
            return self::find()->where($condition)->asArray()->orderBy(['create_at'=>SORT_DESC])->all($db);
            
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
