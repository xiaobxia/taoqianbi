<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class OrderAutoRejectLog extends  ActiveRecord
{
    

    public static function tableName()
    {
        return '{{%order_auto_reject_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
}