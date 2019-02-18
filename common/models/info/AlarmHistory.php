<?php

namespace common\models\info;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 *


 *
 */

class AlarmHistory extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_info_capture');
    }

    public static function collectionName(){
        return 'alarm_history';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'product',
            'total_number',
            'error_number',
            'error_rate',
            'start_time',
            'end_time',
            'worst_error',
        ];
    }

}
