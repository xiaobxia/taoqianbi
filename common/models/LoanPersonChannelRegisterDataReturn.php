<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
class LoanPersonChannelRegisterDataReturn extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%loan_person_channel_register_data_return}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}