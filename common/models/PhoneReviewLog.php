<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class PhoneReviewLog extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%phone_review_ygb_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

}
