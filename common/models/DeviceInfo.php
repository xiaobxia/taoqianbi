<?php

namespace common\models;

use Yii;

/**
 * 设备信息
 *
 * @property integer $id
 * @property string $device_id
 * @property string $device_info
 * @property string $os_type
 * @property string $os_version
 * @property string $app_type
 * @property string $app_version
 * @property string $source_tag
 * @property integer $installed_time
 * @property string $last_login_user
 * @property integer $last_login_time
 * @property string $reserved
 * @property integer $created_at
 * @property integer $updated_at
 */
class DeviceInfo extends \yii\db\ActiveRecord {

    static $attribute_labels = [
        'id' => 'ID',
        'device_id' => '设备标识',
        'idfa' => 'IDFA标识',
        'device_info' => '设备名称',
        'os_type' => '设备类型',
        'os_version' => '系统版本号',
        'app_type' => 'app类型',
        'app_version' => 'app版本号',
        'source_tag' => '来源渠道标识',
        'installed_time' => '安装时间',
        'last_login_user' => '最后登录用户',
        'last_login_time' => '最后登录时间',
        'reserved' => '预留字符',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ];

    public function attributeLabels() {
        return self::$attribute_labels;
    }

    public static function tableName() {
        return '{{%device_info}}';
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
