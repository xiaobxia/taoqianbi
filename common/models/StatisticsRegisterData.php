<?php
/**
 * 用户流失率记录
 */
namespace common\models;
use Yii;
use yii\db\ActiveRecord;
class StatisticsRegisterData extends \yii\db\ActiveRecord
{
    public static function tableName(){
        return '{{%statistics_register_data}}';
    }
}
