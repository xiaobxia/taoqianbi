<?php

namespace backend\models;
use Yii;
/**
 * This is the model class for table "{{%user_device}}".
 */
class UserClickCount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_click_count}}';
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
     * @inheritdoc
     */
    public function rules() {
        return [
            [['user_id','title', 'created_at','updated_at'], 'safe'],
        ];
    }
    public function getStatus(){
        return $this->hasOne( tb_user_loan_order::className(),['id'=>'order_id']);
    }

}