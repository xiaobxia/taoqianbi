<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%loan_person_hash_info}}".
 *
 * @property string $id 自增ID
 * @property string $user_id 借款用户ID
 * @property string $id_card_md5 身份证MD5值
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class LoanPersonHashInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_person_hash_info}}';
    }
    
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'id_card_md5', 'is_new'], 'required'],
            [['user_id', 'phone', 'source_id', 'is_new'], 'integer'],
            [['id_number'], 'string'],
            [['id_card_md5'], 'string', 'max' => 32],
            [['user_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '借款人ID',
            'phone' => '手机号',
            'id_number' => '身份证号',
            'source_id' => '来源',
            'is_new' => '是否新手机号1是2不是',
            'id_card_md5' => '手机号+身份证号码MD5值',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    public static function findCardOne($md5)
    {
        return self::find()->where(['id_card_md5' => $md5])->one();
    }


    /**
     * 插入用户hash
     */
    public static function addUserhash($user_id, $phone, $card, $is_new = 2)
    {
        if (empty($user_id) || empty($phone) || empty($card)) {
            return false;
        }
        $md5 = md5($phone . $card);
        $hash = LoanPersonHashInfo::findCardOne($md5);
        if (!$hash) {
            $hash = new LoanPersonHashInfo();
            $hash->user_id = $user_id;
            $hash->phone = $phone;
            $hash->id_number = $card;
            $hash->id_card_md5 = $md5;
            $hash->is_new = $is_new;
            $hash->created_at = time();
        }

        $hash->updated_at = time();
        return $hash->save();
    }
}
