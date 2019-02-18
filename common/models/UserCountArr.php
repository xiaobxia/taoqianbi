<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_login_upload_log}}".
 */
class UserCountArr extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_count_arr}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }


}