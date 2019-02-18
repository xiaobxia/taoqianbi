<?php
namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

class IcekreditAlipayData extends  ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_new');
    }

    public static function collectionName(){
        return 'icekredit_alipay_data';
    }

    public function attributes()
    {
        return [
            '_id',
            'data',
            'user_id',
            'rid',
            'created_at',
            'updated_at',
        ];
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }
}