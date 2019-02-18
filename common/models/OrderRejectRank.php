<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 订单拒绝因子排行榜
 * Class OrderRejectRank
 * @package common\models
 */
class OrderRejectRank extends  ActiveRecord
{

    public static $status = [
        0 => '决策树',
        1 => '复审',
        2 => '人工审核',
        3 => '数据采集',
        -1 => '其他',
    ];

    public static function tableName()
    {
        return '{{%order_reject_rank}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'date' => '日期',
            'key' => '算子',
            'value' => '数量',
            'rank'   => '当日排名',
            'percent' => '百分比',
            'status' => '状态', // 0: 初审， 1: 复审
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}