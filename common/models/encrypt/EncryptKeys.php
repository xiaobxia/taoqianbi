<?php

namespace common\models\encrypt;

use Yii;

/**
 * This is the model class for table "tb_encrypt_keys".
 *
 * @property integer $id
 * @property string $private_key
 * @property string $public_key
 * @property string $encrypt_type
 * @property integer $encrypt_bits
 * @property string $create_time
 * @property integer $state
 * @property integer $status
 */
class EncryptKeys extends EncryptActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%encrypt_keys}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['private_key', 'public_key'], 'required'],
            [['private_key', 'public_key'], 'string'],
            [['encrypt_bits', 'state', 'status'], 'integer'],
            [['create_time'], 'safe'],
            [['encrypt_type'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'private_key' => Yii::t('app', '私钥'),
            'public_key' => Yii::t('app', '公钥'),
            'encrypt_type' => Yii::t('app', '加密类型'),
            'encrypt_bits' => Yii::t('app', '加密位数'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'state' => Yii::t('app', '状态'),
            'status' => Yii::t('app', '是否删除'),
        ];
    }

}