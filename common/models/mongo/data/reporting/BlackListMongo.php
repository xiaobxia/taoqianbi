<?php
/**
 * Created by PhpStorm.
 * User: guoxiaoyong
 * Date: 2017/8/16
 * Time: 下午2:45
 *
 * 上报集团数据到mongodb的返回日志
 */


namespace common\models\mongo\data\reporting;

use yii\mongodb\ActiveRecord;
use yii;

class BlackListMongo extends ActiveRecord
{
    public static function getDb()
    {
        return yii::$app->get('mongodb');
    }
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'data_reporting_blacklist';
    }

    public function attributes() {
        return [
            '_id',
            'code',
            'msg',  // 数盟设备标识
            'data',  // 包名
            'created_at'
        ];
    }
}