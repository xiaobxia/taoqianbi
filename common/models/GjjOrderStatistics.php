<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%tb_gjj_order_statistics}}".
 */
class GjjOrderStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%gjj_order_statistics}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}