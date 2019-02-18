<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
class LoanPersonChannelRegisterData extends ActiveRecord
{
    const PRE_REGISTER = 1;//预注册用户
    const ALL_REGISTER = 2;//所有用户
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%loan_person_channel_register_data}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}
