<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\base\Exception;

class CreditCheckHitMap extends  ActiveRecord
{
    const PRODUCT_ASSET_PARTHER = 1;
    const PRODUCT_YGD = 2;

    public static $product = [
        self::PRODUCT_ASSET_PARTHER => '合作资产'
    ];

    const STATUS_MISS = 0;
    const STATUS_HIT = 1;

    public static $status_list = [
        self::STATUS_MISS => '未命中',
        self::STATUS_HIT => '已命中',
    ];

    const TYPE_MANUAL = 0;
    const TYPE_AUTO = 1;

    public static $type_list = [
        self::TYPE_MANUAL => '手动',
        self::TYPE_AUTO => '自动',
    ];



    public static function tableName()
    {
        return '{{%credit_check_hit_map}}';
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

    public function getCreditQueryLog()
    {
        return $this->hasOne(CreditQueryLog::className(), ['id' => 'log_id']);
    }

}