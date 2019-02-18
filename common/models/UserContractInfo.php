<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class UserContractInfo extends ActiveRecord
{

    const TYPE_LQB = 1;
    const TYPE_FZB = 2;

    public static $type_list = [
        self::TYPE_LQB => 'lqb.txt',
        self::TYPE_FZB => 'fzb.txt'
    ];
    public static function tableName()
    {
        return '{{%user_contract_info}}';
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