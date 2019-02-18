<?php

namespace common\models\message;
use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
/**
 * This is the model class for table "{{%push_log}}".
 */
class PushLog extends ActiveRecord
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    const TYPE_REPAYMENT = 1;
    const TYPE_ACTIVITY = 2;

    //短信通道
    public static $status_all = [
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAILED => '失败',
    ];

    public static $type_all = [
        self::TYPE_REPAYMENT => '还款推送',
        self::TYPE_ACTIVITY => '活动推送',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%push_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
}
