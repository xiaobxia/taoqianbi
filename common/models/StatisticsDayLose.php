<?php
/**
 * 用户每日流失率记录
 */
namespace common\models;
use Yii;
use yii\db\ActiveRecord;
class StatisticsDayLose extends \yii\db\ActiveRecord
{
    public static function tableName(){
        return '{{%statistics_day_lose}}';
    }
}
