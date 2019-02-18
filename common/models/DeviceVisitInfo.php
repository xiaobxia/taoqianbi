<?php

namespace common\models;

use Yii;

/**
 * 设备启动记录
 *
 * @property integer $id
 * @property string $device_id
 * @property integer $uid
 * @property string $username
 * @property integer $visit_time
 * @property string $net_type
 * @property string $reserved
 * @property integer $created_at
 * @property integer $updated_at
 */
class DeviceVisitInfo extends \yii\db\ActiveRecord {

    static $attribute_labels = [
        'id' => 'ID',
        'device_id' => '设备标识',
        'idfa' => 'IDFA标识',
        'uid' => '用户id',
        'username' => '用户名',
        'visit_time' => '启动时间',
        'net_type' => '网络类型',
        'reserved' => '预留字符',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ];

    public function attributeLabels() {
        return self::$attribute_labels;
    }

    public static function tableName() {
        return '{{%device_visit_info}}';
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function rules() {
        return [
            [['device_id'], 'required', 'message' => '不能为空'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }
}
