<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 19:34
 */
namespace common\models;
use yii;
class StatisticsDayData extends BaseActiveRecord{


    public static function tableName(){
        return '{{%statistics_day_data}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_stats');
    }

}