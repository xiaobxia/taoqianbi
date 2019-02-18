<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%message_log}}".
 */
class MessageLog extends \yii\db\ActiveRecord
{

    /**
     * 短信类型
     */
    const TYPE_YGD_LQB_NOTICE = 1;
    const TYPE_YGD_KK_NOTICE = 2;
    const TYPE_ACTIVITE_NOTICE = 3;

    public static $type =[
        self::TYPE_YGD_LQB_NOTICE=>'小钱包零钱包还款提前一天提醒',
        self::TYPE_YGD_KK_NOTICE=>'小钱包还款成功通知',
        self::TYPE_ACTIVITE_NOTICE=>'活动通知',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%message_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}