<?php

namespace common\models\encrypt;

use Yii;

/**
 * This is the model class for table "tb_encrypt_keys_device".
 *
 * @property integer $id
 * @property string $device_imei
 * @property integer $encrypt_key_id
 * @property string $start_time
 * @property string $create_time
 * @property integer $state
 * @property integer $status
 */
class DeviceMappingKeys extends EncryptActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%encrypt_keys_device}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['device_imei', 'encrypt_key_id'], 'required'],
            [['encrypt_key_id', 'state', 'status'], 'integer'],
            [['start_time', 'create_time'], 'safe'],
            [['device_imei'], 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'device_imei' => Yii::t('app', '设备号'),
            'encrypt_key_id' => Yii::t('app', '密钥ID'),
            'start_time' => Yii::t('app', '密钥生效时间'),
            'create_time' => Yii::t('app', '记录创建时间'),
            'state' => Yii::t('app', ' 0启用 1废弃'),
            'status' => Yii::t('app', '0 正常 1删除'),
        ];
    }

    public static function findByImei($device_imei){
        return self::find()->where(['device_imei'=>$device_imei, 'state'=>self::STATE_USABLE, 'status'=> self::STATUS_ACTIVE])->orderBy('id DESC')->one();
    }
}