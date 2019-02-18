<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditFacePlus extends  ActiveRecord
{
    const STATUS_FAIL = -1;
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;

    //对应单个用户每天允许调用face++接口次数
    const FACE_PLUS_DAY = 5;
    const FACE_PLUE_REDIS = 'face_plue_redis_';

    public static $status = [
        self::STATUS_FAIL => '审核失败',
        self::STATUS_PENDING => '未审核',
        self::STATUS_SUCCESS => '审核通过',
    ];

    public static function tableName()
    {
        return '{{%credit_face_plus}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
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
}