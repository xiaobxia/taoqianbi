<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%tb_gjj_repayment_order_statistics}}".
 */
class GjjRepaymentOrderStatistics extends \yii\db\ActiveRecord
{

    //数据统计类型：1：所有用户；2：新用户；3：老用户
    const STAT_ALL_TYPE = 1; //所有用户
    const STAT_NEW_TYPE = 2;//新用户
    const STAT_OLD_TYPE = 3;//老用户

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%gjj_repayment_order_statistics}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}