<?php

namespace common\models;

use Yii;


class LoanOrderDefaultQuota extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_order_default_quota}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    public function rules()
    {
        return [
            [['norm_orders', 'gjj_orders','other_orders','old_user_orders'], 'required'],
            [['norm_orders', 'gjj_orders','other_orders','old_user_orders'], 'integer', 'min'=>0],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'norm_orders' => '普通订单',
            'gjj_orders' => '公积金订单',
            'other_orders' => '第三方订单',
            'old_user_orders' => '老用户订单',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }


}
