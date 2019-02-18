<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%loan_out_del}}".
 */
class LoanOutDel extends \yii\db\ActiveRecord
{
    const STATUS_YES = 1;
    const STATUS_NO = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_out_del}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}