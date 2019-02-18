<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 选人平台短信推送任务表
 * @author lujingfeng
 */
class SensitiveDictUserLogMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'dict_censor';
        //return 'channel_rate';
    }
    public function attributes(){
        return [
            '_id',
            'level',
            'category',
            'log_time',
            'prefix',
            'message'
        ];
    }

}
