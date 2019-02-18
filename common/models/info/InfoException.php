<?php

namespace common\models\info;

use Yii;
use yii\mongodb\ActiveRecord;

class InfoException extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_info_capture');
    }

    public static function collectionName(){
        return 'info_capture_exception';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'created_time', 
            'info',
            'html',
            'product',
        ];
    }

}