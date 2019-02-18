<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%xwbank_user}}".
 *
 * @property string $id
 * @property string $user_id
 * @property string $card_id
 * @property integer $result
 * @property string $fail_reason
 * @property string $customer_id
 * @property string $customer_status
 * @property string $payment_sign_status
 * @property string $bank_sign_status
 * @property string $created_at
 * @property string $updated_at
 */
class BindCardInfo extends \yii\db\ActiveRecord
{
    const CARDBIND='bind';
    const UNCARDBIND='unbind';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bind_card_info}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'card_id'], 'integer'],
            [['user_id', 'card_id', 'result', 'created_at', 'updated_at'], 'integer'],
            [['fail_reason'], 'string', 'max' => 255],
            [['pay_channel'], 'string', 'max' => 50],
            [['customer_status', 'payment_sign_status', 'bank_sign_status'], 'string', 'max' => 10],
//            [['user_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户ID',
            'card_id' => '绑卡ID',
            'result' => '开户结果：0-失败 1-成功',
            'fail_reason' => '失败原因',
            'pay_channel' => '委贷平台',
            'customer_status' => '客户状态 00-未启用 01-已启用 02-已停用',
            'payment_sign_status' => '代收签约状态 00-未签约 01-已签约',
            'bank_sign_status' => '电子签章开户状态 00-未开户 01-已开户',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('db');
    }
}
