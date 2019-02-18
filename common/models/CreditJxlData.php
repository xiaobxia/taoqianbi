<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

class CreditJxlData extends  ActiveRecord
{

    public static function getDb(){
        // return Yii::$app->get('mongodb_info_capture');
        return Yii::$app->get('mongodb_new');
    }

    public static function collectionName(){
        return 'credit_jxl_data';
    }

    public function attributes()
    {
        return [
            '_id',
            'data',
            'person_id',
            'created_time',
            'updated_time',
        ];
    }
}