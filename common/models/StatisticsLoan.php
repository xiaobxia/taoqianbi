<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%statistics_loan}}".
 */
class StatisticsLoan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%statistics_loan_copy}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}