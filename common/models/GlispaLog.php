<?php
namespace common\models;

use yii;
class GlispaLog extends BaseActiveRecord{

    const STATUS_TRIAL = 0;
    const STATUS_PASS = 1;
    const STATUS_EDIT =2;
    public static function tableName() {
        return '{{%glispa_log}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }
}