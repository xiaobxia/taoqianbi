<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 16:40
 */
namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;


class UserQuotaMoreInfo extends ActiveRecord
{/**
 * @inheritdoc
 */
    public static function tableName()
    {
        return '{{%user_quota_more_info}}';
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