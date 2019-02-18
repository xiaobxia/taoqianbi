<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017/7/20
 * Time: 14:13
 */

namespace common\models\mongo\wechat;

use Yii;
use yii\mongodb\ActiveRecord;

class CusomterTemplateMongo extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_log');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'msg_notice';
    }

    public function attributes()
    {
        return [
            '_id',
            'data',
            'name',
            'created_time',
        ];
    }
}