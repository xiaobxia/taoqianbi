<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%daily_data}}".
 */
class DailyData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%daily_data}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}