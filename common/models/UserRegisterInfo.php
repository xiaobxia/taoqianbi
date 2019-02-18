<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_login_upload_log}}".
 */
class UserRegisterInfo extends ActiveRecord
{

    const PLATFORM_IOS = 'ios';
    const PLATFORM_ANDROID = 'android';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_register_info}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 添加数据
     * @param integer $user_id
     * @param array $attrs
     * @return \static
     */
    public static function addData($user_id, $attrs) {
        /**
         * `user_id` int(11) DEFAULT '0' COMMENT '用户ID',
         * `clientType` varchar(100) DEFAULT NULL,
         * `osVersion` varchar(100) DEFAULT NULL,
         * `appVersion` varchar(100) DEFAULT NULL,
         * `deviceName` varchar(100) DEFAULT NULL,
         * `created_at` int(11) DEFAULT '0',
         * `appMarket` varchar(100) DEFAULT '' COMMENT '应用市场',
         * `deviceId` varchar(100) DEFAULT '' COMMENT '设备码',
         * `date` date DEFAULT NULL COMMENT '日期',
         * `source` int(11) DEFAULT '0' COMMENT '来源：1、小钱包，2、闪电荷包',
         */

        $model = new static();
        $model->user_id = (int)$user_id;
        $model->created_at = time();

        $allow_set_attrs = [
            'clientType', 'osVersion', 'appVersion', 'deviceName', 'appMarket','deviceId','date','source'
        ];
        foreach($attrs as $key=>$val) {
            if(in_array($key, $allow_set_attrs)) {
                $model->$key = $val;
            }
        }

        $model->save(false);
        return $model;
    }
}
