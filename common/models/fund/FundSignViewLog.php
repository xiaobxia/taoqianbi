<?php

namespace common\models\fund;

use Yii;

/**
 * This is the model class for table "{{%fund_sign_view_log}}".
 *
 * @property string $id
 * @property string $ip
 * @property string $url_key
 * @property string $created_at
 * @property string $parse_order_id
 * @property string $parse_fund_id
 * @property string $parse_time
 */
class FundSignViewLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fund_sign_view_log}}';
    }
    
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip', 'url_key', 'created_at'], 'required'],
            [['created_at', 'parse_order_id', 'parse_fund_id', 'parse_time'], 'integer'],
            [['ip', 'url_key'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'url_key' => 'Url Key',
            'created_at' => 'Created At',
            'parse_order_id' => 'Parse Order ID',
            'parse_fund_id' => 'Parse Fund ID',
            'parse_time' => 'Parse Time',
        ];
    }
}
