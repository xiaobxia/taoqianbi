<?php
/**
 *
 * @author Shayne Song
 * @description Basic rule data of order.
 * @date 2017-01-20
 *
 */

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class OrderBasicRule extends  ActiveRecord{
    
    public static function tableName()
    {
        return '{{%order_basic_rule}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_rcm');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function saveData($data){
        if(!isset($data['order_id']) || empty($data['order_id'])){
            return false;
        }
        $record = OrderBasicRule::find()->where(['order_id' => $data['order_id']])->one();
        if(!empty($record)){
            return false;
        }else{
            $this->order_id = $data['order_id'];
            $this->user_id = $data['user_id'];
            $this->basic_rule = $data['basic_report'];
            if(isset($data['overdue'])){
                $this->overdue = $data['overdue'];
            }
            if(isset($data['created_at']) && !empty($data['created_at'])){
                $this->created_at = $data['created_at'];
            }
            if(isset($data['updated_at']) && !empty($data['updated_at'])){
                $this->updated_at = $data['updated_at'];
            }
            return $this->save();
        }
    }

    public function saveOrUpdateData($data){
        if(!isset($data['order_id']) || empty($data['order_id'])){
            return false;
        }
        $record = OrderBasicRule::find()->where(['order_id' => $data['order_id']])->one();
        if(!empty($record)){
            $record->order_id = $data['order_id'];
            $record->user_id = $data['user_id'];
            $record->basic_rule = $data['basic_report'];
            if(isset($data['overdue'])){
                $record->overdue = $data['overdue'];
            }
            if(isset($data['created_at']) && !empty($data['created_at'])){
                $record->created_at = $data['created_at'];
            }
            if(isset($data['updated_at']) && !empty($data['updated_at'])){
                $record->updated_at = $data['updated_at'];
            }
            return $record->save();
        }else{
            $this->order_id = $data['order_id'];
            $this->user_id = $data['user_id'];
            $this->basic_rule = $data['basic_report'];
            if(isset($data['overdue'])){
                $this->overdue = $data['overdue'];
            }
            if(isset($data['created_at']) && !empty($data['created_at'])){
                $this->created_at = $data['created_at'];
            }
            if(isset($data['updated_at']) && !empty($data['updated_at'])){
                $this->updated_at = $data['updated_at'];
            }
            return $this->save();
        }
    }


    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'order_id' => '订单编号',
            'user_id' => '借款人编号',
            'basic_rule'    => '基础特征',
            'overdue' => '逾期天数',          //-1表示订单被拒绝
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

}