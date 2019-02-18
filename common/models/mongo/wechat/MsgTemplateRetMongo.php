<?php
/**
 * Created by PhpStorm.
 * User: guoxiaoyong
 * Date: 2017/7/15
 * Time: 上午11:36
 * 模板发送返回错误记录日志
 */

namespace common\models\mongo\wechat;

use yii\mongodb\ActiveRecord;

class MsgTemplateRetMongo extends ActiveRecord
{
    public static function getDb()
    {
        return \Yii::$app->get('mongodb_new');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'wechat_template_ret_data';
    }


    public function attributes()
    {
        return [
            '_id',
            'openid',
            'errcode',
            'errcode',
            'errmsg',
            'created_at'
        ];
    }




}