<?php

namespace common\models\message;
use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
/**
 * This is the model class for table "{{%message_status_log}}".
 * 短信发送状态查询
 */
class MessageStatusLog extends ActiveRecord
{
    const TYPE_XIAO = 1;
    const TYPE_CONGYU = 2;
    const TYPE_CHUANGLAN = 3;

    //短信通道
    public static $type_all = [
        self::TYPE_XIAO => '希奥',
        self::TYPE_CONGYU => '聪裕',
        self::TYPE_CHUANGLAN => '创蓝',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%message_status_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
}
