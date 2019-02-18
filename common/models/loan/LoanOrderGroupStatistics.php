<?php

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;


class LoanOrderGroupStatistics extends \yii\db\ActiveRecord 
{
    public static function tableName()
    {
        return '{{%loan_collection_group_statistic}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    

   /**
    *今日还款订单统计信息：
    */
   public static function today(){
        try{
            $condition = " `created_at` >= ".strtotime(date('Y-m-d 0:0:0'))." AND `created_at` < ".strtotime(date('Y-m-d 23:59:59'));
           return self::find()->where($condition)->asArray()->all();
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            
        }
   }
   public static function yesterday_rd(){
        try{
            $condition = " `created_at` >= ".(strtotime(date('Y-m-d 0:0:0'))-24*3600)." AND `created_at` < ".(strtotime(date('Y-m-d 23:59:59'))-24*3600);
           return self::find()->where($condition)->asArray()->all(self::getDb_rd());
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            
        }
   }

    public function beforeSave($insert)  
    {  
        if(parent::beforeSave($insert)){  
            if($this->isNewRecord){  
                if(empty($this->created_at)) $this->created_at = time();

            }else{  
               $this->updated_at = time();
            }  
            return true;  
        }else{  
            return false;  
        }  
    }  



    
}
