<?php

namespace common\models;
use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_phone_message}}".
 */
class UserPhoneMessage extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_phone_message}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}