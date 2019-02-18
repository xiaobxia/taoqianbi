<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/15 0015
 * Time: 下午 5:49
 * 平台每日流失率记录
 */

namespace common\models;
use Yii;
use yii\db\ActiveRecord;
class StatisticsDayLoseRate extends \yii\db\ActiveRecord
{
    public static function tableName(){
        return '{{%statistics_day_lose_rate}}';
    }
}
