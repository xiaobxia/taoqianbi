<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
class LoanPersonChannelLoanData extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%loan_person_channel_loan_data}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}