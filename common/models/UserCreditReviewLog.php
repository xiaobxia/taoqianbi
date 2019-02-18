<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class UserCreditReviewLog extends ActiveRecord
{
    //状态
    const STATUS_REJECT = -1;
    const STATUS_CHECK = 0;
    const STATUS_PASS = 1;

    public static $status = [
        self::STATUS_REJECT => '审核驳回',
        self::STATUS_CHECK => '待审核',
        self::STATUS_PASS => '审核通过',
    ];
    
    //类型
    const TYPE_POCKET_AMOUNT = 1;
    const TYPE_POCKET_APR = 2;
    const TYPE_HOUSE_AMOUNT = 3;
    const TYPE_HOUSE_APR = 4;
    const TYPE_POCKET_REGISTER_AMOUNT = 5;
    const TYPE_HOUSE_REGISTER_AMOUNT = 6;
    const TYPE_POCKET_REGISTER_APR = 7;
    const TYPE_HOUSE_REGISTER_APR = 8;
    const TYPE_CREDIT_TOTAL_AMOUNT = 9;
    const TYPE_POCKET_LATE_APR = 10;
    const TYPE_HOUSE_LATE_APR = 11;
    const TYPE_INSTAILLMENT_LATE_APR = 12;
    const TYPE_INSTALLMENT_APR = 13;
    const TYPE_POCKET_TERM_MIN = 14;
    const TYPE_POCKET_TERM_MAX = 15;
    const TYPE_HOUSE_TERM_MIN = 16;
    const TYPE_HOUSE_TERM_MAX = 17;
    const TYPE_INSTAILLMENT_TERM_MIN = 18;
    const TYPE_INSTAILLMENT_TERM_MAX = 19;    
    const TYPE_CREDIT_TOTAL_INCREASE = 20;
    const TYPE_CREDIT_COUNTER_FEE_RATE = 21;

    public static $type = [
        self::TYPE_POCKET_AMOUNT => '调整零钱宝额度',
        self::TYPE_POCKET_APR => '调整零钱宝利率',
        self::TYPE_HOUSE_AMOUNT => '调整房租宝额度',
        self::TYPE_HOUSE_APR => '调整房租宝利率',
        self::TYPE_POCKET_REGISTER_AMOUNT => '零钱贷注册获取额度',
        self::TYPE_HOUSE_REGISTER_AMOUNT => '房租贷注册获取额度',
        self::TYPE_POCKET_REGISTER_APR => '零钱贷注册获取利率',
        self::TYPE_HOUSE_REGISTER_APR => '房租贷注册获取利率',
        self::TYPE_CREDIT_TOTAL_AMOUNT => '调整总额度',
        self::TYPE_POCKET_LATE_APR =>'零钱包违约金利率',
        self::TYPE_HOUSE_LATE_APR=>'房租宝违约金利率',
        self::TYPE_INSTAILLMENT_LATE_APR=>'分期商城违约金利率',
        self::TYPE_INSTALLMENT_APR =>'调整分期商城利率',
        self::TYPE_POCKET_TERM_MIN =>'零钱包借款最少天数',
        self::TYPE_POCKET_TERM_MAX =>'零钱包借款最多天数',
        self::TYPE_HOUSE_TERM_MIN =>'房租宝借款最少月数',
        self::TYPE_HOUSE_TERM_MAX =>'房租宝借款最多月数',
        self::TYPE_INSTAILLMENT_TERM_MIN =>'分期商城借款最少月数',
        self::TYPE_INSTAILLMENT_TERM_MAX =>'分期商城借款最多月数',
        self::TYPE_CREDIT_TOTAL_INCREASE => '增加总额度',
        self::TYPE_CREDIT_COUNTER_FEE_RATE => '调整手续费率',
    ];

    public static function tableName()
    {
        return '{{%user_credit_review_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

}
