<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%daily_register_loan_data}}".
 *
 * @property string $id
 * @property string $date
 * @property integer $register
 * @property integer $register_white
 * @property integer $loan_white
 * @property integer $payment_white
 * @property integer $loan
 */
class DailyRegisterAndLoanData extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%daily_register_and_loan_data}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}