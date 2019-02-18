<?php
/**
 *本金的分段统计
 *逾期等级：S1，S2，S3
 *金额分段：(0~1000], (1000~1500], (1500~2000], (2000~2500], (2500~3000], (3000~~), 
 */

namespace common\models\loan;

use Yii;
use common\models\BaseActiveRecord;
use yii\db\Exception;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use common\models\loan\UserSchedule;


class OrderStatisticsByStage extends \yii\db\ActiveRecord 
{
    public static function tableName()
    {
        return '{{%order_overview_statistics_bystage}}';
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
     *
     */
   public static function collection_input_statistics($input_orders = array()){
        try{
            if(empty($input_orders)) return false;
           
            $start = strtotime(date('Y-m-d 0:0:0', $input_orders['create_at']));
            $end = strtotime(date('Y-m-d 23:59:59', $input_orders['create_at']));

            foreach ($input_orders['data'] as $level => $item) {
                $condition = " `create_at` >= ".$start." AND `create_at` <= ".$end." AND `overdue_level` = ".$level." ORDER BY `create_at` DESC";
                $record = self::find()->where($condition)->one();
                if(empty($record)){
                    $record = new self();
                    $record->create_at = $input_orders['create_at'];
                }
                
                foreach ($item as $key => $value) {
                    $record->$key = $value;
                }
                
                $record->overdue_level = $level;
                // print_r($record);exit;
                if(!$record->save())    throw new Exception("更新本金分段统计记录失败, function:".__FUNCTION__, 'collection');
            
            }
            
            
        }catch(Exception $e){
            throw new Exception($e->getMessage());
            return false;
        
        }
        return true;


   }

   /**
    *返回催收统计信息
    */
    public static function lists($start=0, $end=0){
        try{
            $result = array();
            if(empty($start)) $start=strtotime(date('Y-m-d 0:0:0'));
            if(empty($end)) $end=strtotime(date('Y-m-d 23:59:59'));
            $condition = " `create_at` >= ".$start." AND `create_at` < ".$end;
            $lists =  self::find()->select('*')->where($condition)->asArray()->orderBy(['create_at'=>SORT_DESC])->all();
            if(!empty($lists)){
                foreach ($lists as $key => $item) {
                    $day = date("Y-m-d", $item['create_at']);
                    $result[$day][$item['overdue_level']] = $item;
                }
            }
            return $result;
            
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
