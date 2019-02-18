<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_credit_data}}".
 */
class UserCreditData extends \yii\db\ActiveRecord
{

    const STATUS_NORAML = 0;
    const STATUS_FINISH = 1;

    //数据采集队列名
    const CREDIT_GET_DATA_SOURCE_PREFIX = 'credit_get_data_source';
    // 数据采集延迟队列名
    const CREDIT_GET_DATA_SOURCE_DELAY = 'credit_get_data_source_delay';
    // 缩水版数据采集队列名
    const CREDIT_GET_DATA_SOURCE_SIMPLE_PREFIX = 'credit_get_data_source_simple';
    // 缩水版数据采集延迟队列名
    const CREDIT_GET_DATA_SOURCE_SIMPLE_DELAY = 'credit_get_data_source_simple_delay';
    
    public static $status = [
        self::STATUS_NORAML=>'未完成',
        self::STATUS_FINISH=>'已完成',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_credit_data}}';
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