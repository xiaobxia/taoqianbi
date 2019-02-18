<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%channel_rates}}".
 */
class RepayRatesList  extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%repay_rates_list}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}