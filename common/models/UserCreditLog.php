<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%area}}".
 */
class UserCreditLog extends \yii\db\ActiveRecord
{

    const TRADE_TYPE_LQD_LOAN = 1;
    const TRADE_TYPE_LQD_LOAN_LOCK=2;
    const TRADE_TYPE_FZD_LOAN_LOCK=3;
    const TRADE_TYPE_LQD_FK=4;
    const TRADE_TYPE_FZD_FK=5;
    const TRADE_TYPE_LQD_KK=6;
    const TRADE_TYPE_FZD_KK=7;
    const REJECT_LOAN_LQD = 8;
    const REJECT_LOAN_FZD = 9;
    const TRADE_TYPE_FQSC_FH_LOCK=10;
    const TRADE_TYPE_FQSC_FH=11;
    const TRADE_TYPE_LQD_LATE_FEE = 12;
    const TRADE_TYPE_LQD_INTEREST = 13;
    const TRADE_TYPE_LQD_LOAN_UNLOCK=14;
    const TRADE_TYPE_FZD_LOAN_UNLOCK=15;
    const TRADE_TYPE_FZD_LATE_FEE = 16;
    const TRADE_TYPE_LQD_CS_CANCEL = 17;
    const TRADE_TYPE_LQD_FS_CANCEL = 18;
    const TRADE_TYPE_LQD_ZCFK_CANCEL = 19;
    const TRADE_TYPE_FZD_CS_CANCEL = 20;
    const TRADE_TYPE_FZD_FS_CANCEL = 21;
    const TRADE_TYPE_FZD_ZCFK_CANCEL = 22;
    const TRADE_TYPE_LQD_REGISTER_ED = 23;
    const TRADE_TYPE_FZD_REGISTER_ED = 24;
    const TRADE_TYPE_LQD_REGISTER_TZED = 25;
    const TRADE_TYPE_FZD_REGISTER_TZED = 26;
    const TRADE_TYPE_FQSC_KK = 27;
    const TRADE_TYPE_FQSC_LOAN_LOCK=28;
    const TRADE_TYPE_FQSC_CS_CANCEL=29;
    const TRADE_TYPE_FQSC_FS_CANCEL=30;
    const TRADE_TYPE_FQSC_FH_UNLOCK=31;
    const TRADE_TYPE_FQSC_ZCFH_CANCEL=32;
    const TRADE_TYPE_LQD_ADMIN=33;
    const TRADE_TYPE_ACT=34;
    const TRADE_TYPE_CHRISTMAS=35;
    const TRADE_TYPE_SET=36;

    public static $tradeTypes = [
        self::TRADE_TYPE_LQD_LOAN=>'零钱贷借款',
        self::TRADE_TYPE_LQD_LOAN_LOCK=>'零钱贷借款申请锁定额度',
        self::TRADE_TYPE_FZD_LOAN_LOCK=>'房租贷借款申请锁定额度',
        self::TRADE_TYPE_LQD_FK=>'零钱贷借款放款',
        self::TRADE_TYPE_FZD_FK=>'房租贷借款放款',
        self::TRADE_TYPE_LQD_KK=>'零钱贷借款扣款',
        self::TRADE_TYPE_FZD_KK=>'房租贷借款扣款',
        self::REJECT_LOAN_LQD=>'零钱贷放款驳回',
        self::REJECT_LOAN_FZD=>'房租贷放款驳回',
        self::TRADE_TYPE_FQSC_FH_LOCK=>'分期购发货申请锁定额度',
        self::TRADE_TYPE_FQSC_FH=>'分期购发货',
        self::TRADE_TYPE_LQD_LATE_FEE=>'零钱贷违约金计算',
        self::TRADE_TYPE_LQD_INTEREST=>'零钱贷利息计算',
        self::TRADE_TYPE_LQD_LOAN_UNLOCK=>'零钱贷借款驳回解除锁定额度',
        self::TRADE_TYPE_FZD_LOAN_UNLOCK=>'房租贷借款驳回解除锁定额度',
        self::TRADE_TYPE_FZD_LATE_FEE =>'房租贷违约金计算',
        self::TRADE_TYPE_LQD_CS_CANCEL =>'零钱贷初审驳回解除锁定额度',
        self::TRADE_TYPE_LQD_FS_CANCEL =>'零钱贷复审驳回解除锁定额度',
        self::TRADE_TYPE_LQD_ZCFK_CANCEL =>'零钱贷资产打款驳回解除锁定额度',
        self::TRADE_TYPE_FZD_CS_CANCEL =>'房租贷初审驳回解除锁定额度',
        self::TRADE_TYPE_FZD_FS_CANCEL =>'房租贷复审驳回解除锁定额度',
        self::TRADE_TYPE_FZD_ZCFK_CANCEL =>'房租贷资产打款驳回解除锁定额度',
        self::TRADE_TYPE_LQD_REGISTER_ED =>'零钱贷注册获取额度',
        self::TRADE_TYPE_FZD_REGISTER_ED =>'房租贷注册获取额度',
        self::TRADE_TYPE_LQD_REGISTER_TZED =>'零钱贷绑定公司调整额度',
        self::TRADE_TYPE_FZD_REGISTER_TZED =>'房租贷绑定公司调整额度',
        self::TRADE_TYPE_FQSC_KK =>'分期购扣款',
        self::TRADE_TYPE_FQSC_LOAN_LOCK=>'分期购借款申请锁定额度',
        self::TRADE_TYPE_FQSC_CS_CANCEL=>'分期购初审驳回解除锁定额度',
        self::TRADE_TYPE_FQSC_FS_CANCEL=>'分期购复审驳回解除锁定额度',
        self::TRADE_TYPE_FQSC_FH_UNLOCK=>'分期购发货驳回解除锁定额度',
        self::TRADE_TYPE_FQSC_ZCFH_CANCEL=>'分期购资产发货驳回解除锁定额度',
        self::TRADE_TYPE_LQD_ADMIN=>'正常还款提额',
        self::TRADE_TYPE_ACT=>'全民回馈活动额外额度',
        self::TRADE_TYPE_CHRISTMAS=>'圣诞节活动额外额度',
        self::TRADE_TYPE_SET=>'强制设置额度'
    ];

    //零钱贷类型
    public static $apr_group_lgd = [
        self::TRADE_TYPE_LQD_LOAN=>'零钱贷借款',
        self::TRADE_TYPE_LQD_LOAN_LOCK=>'零钱贷借款申请锁定额度',
        self::TRADE_TYPE_LQD_FK=>'零钱贷借款放款',
        self::TRADE_TYPE_LQD_KK=>'零钱贷借款扣款',
        self::REJECT_LOAN_LQD=>'零钱贷放款驳回',
        self::TRADE_TYPE_LQD_LATE_FEE=>'零钱贷违约金计算',
        self::TRADE_TYPE_LQD_INTEREST=>'零钱贷利息计算',
        self::TRADE_TYPE_LQD_LOAN_UNLOCK=>'零钱贷借款驳回解除锁定额度',
        self::TRADE_TYPE_LQD_ADMIN=>'正常还款提额',
    ];

    //房租贷类型
    public static $apr_group_fzd = [
        self::TRADE_TYPE_FZD_LOAN_LOCK=>'房租贷借款申请锁定额度',
        self::TRADE_TYPE_FZD_FK=>'房租贷借款放款',
        self::TRADE_TYPE_FZD_KK=>'房租贷借款扣款',
        self::REJECT_LOAN_FZD=>'房租贷放款驳回',
        self::TRADE_TYPE_FZD_LOAN_UNLOCK=>'房租贷借款驳回解除锁定额度',
        self::TRADE_TYPE_FZD_LATE_FEE =>'房租贷违约金计算',
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
        return '{{%user_credit_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), ['id' => 'user_id']);
    }
}