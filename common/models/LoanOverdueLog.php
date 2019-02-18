<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%loan_overdue_log}}".
 */
class LoanOverdueLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_overdue_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}