<?php

namespace common\models\stats;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%tb_day_not_yet_principal_statistics}}".
 */
class DayNotYetPrincipalStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%day_not_yet_principal_statistics}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_stats');
    }

}