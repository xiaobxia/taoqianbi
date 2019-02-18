<?php

namespace common\models\fund;

use Yii;

/**
 * 订单资方变动日志
 * This is the model class for table "{{%order_fund_log}}".
 *
 * @property string $id
 * @property string $fund_id
 * @property string $order_id
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 */
class OrderFundLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_fund_log}}';
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
            [['fund_id', 'order_id', 'content'], 'required'],
            [['fund_id', 'order_id'], 'integer'],
            [['content'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fund_id' => '资方ID',
            'order_id' => '订单ID',
            'content' => '内容',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
    
    /**
     * 添加日志记录
     * @param integer $fund_id 资金ID
     * @param integer $order_id 订单ID
     * @param string $content 日志内容
     * @return \static
     */
    public static function add($fund_id, $order_id, $content) {
        $model = new static;
        $model->fund_id = (int)$fund_id;
        $model->order_id = (int)$order_id;
        $model->content = trim($content);
        $model->save(false);
        return $model;
    }
}
