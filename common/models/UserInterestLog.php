<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_interest_log}}".
 */
class UserInterestLog extends \yii\db\ActiveRecord
{

    const TRADE_TYPE_LQD_LATE_FEE = 1;
    const TRADE_TYPE_LQD_INTEREST = 2;
    const TRADE_TYPE_FZD_LATE_FEE = 3;
    const TRADE_TYPE_FZD_INTEREST = 4;
    const TRADE_TYPE_FQG_LATE_FEE = 5;
    const TRADE_TYPE_FQG_INTEREST = 6;


    public static $tradeTypes = [
        self::TRADE_TYPE_LQD_LATE_FEE=>'零钱贷违约金计算',
        self::TRADE_TYPE_LQD_INTEREST=>'零钱贷利息计算',
        self::TRADE_TYPE_FZD_LATE_FEE=>'房租贷违约金计算',
        self::TRADE_TYPE_FZD_INTEREST=>'房租贷利息计算',
        self::TRADE_TYPE_FQG_LATE_FEE=>'分期购违约金计算',
        self::TRADE_TYPE_FQG_INTEREST=>'分期购利息计算',

    ];

    const TRADE_TYPE_SECOND_NORMAL = 0;

    public static $secondTradeTypes = [
        self::TRADE_TYPE_SECOND_NORMAL=>'默认',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_interest_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}