<?php

namespace common\models;
use yii\behaviors\TimestampBehavior;

class FinancialRefundLog extends BaseActiveRecord
{
    const TYPE_BANK = 1;
    const TYPE_ALIPAY = 2;
    const TYPE_YIMATONG = 3;
    const TYPE_BAOFU = 4;

    public static $type_list = [
        self::TYPE_BANK => '银行卡',
        self::TYPE_ALIPAY => '支付宝',
        self::TYPE_BAOFU => '宝付',
        self::TYPE_YIMATONG => '益码通',
    ];

    const STATUS_APPLY = 0;
    const STATUS_REJECT = -1;
    const STATUS_REFUNDING = 1;
    const STATUS_COMPLETE = 2;

    public static $status_list = [
        self::STATUS_APPLY => '待审核',
        self::STATUS_REJECT => '已拒绝',
        self::STATUS_REFUNDING => '退款中',
        self::STATUS_COMPLETE => '已退款',
    ];
    public static function tableName()
    {
        return '{{%financial_refund_log}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }


}