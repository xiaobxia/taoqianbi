<?php
namespace backend\models;


use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class LoanFkRecord extends ActiveRecord
{

    const LOANING = 0;
    const LOANED = 1;

    public static $status = [
        self::LOANING => "待放款",
        self::LOANED => "已放款"
    ];

    const REPAY_TYPE_MONTH = 5;
    const REPAY_TYPE_DEBX = 2;
    const REPAY_TYPE_ALL = 3;
    const REPAY_TYPE_AJFX = 4;
    const REPAY_TYPE_DBDX = 1;
    const REPAY_TYPE_XXHB = 6;
    const REPAY_TYPE_ABNFX = 7;
    const REPAY_TYPE_ANFX = 8;

    public static $repay_type = [
        self::REPAY_TYPE_DBDX => '等本等息',
        self::REPAY_TYPE_AJFX => '按季付息',
        self::REPAY_TYPE_ABNFX => '按半年付息',
        self::REPAY_TYPE_ANFX => '按年付息',
        self::REPAY_TYPE_DEBX  => '等额本息',
        self::REPAY_TYPE_MONTH  => '按月付息',
        self::REPAY_TYPE_ALL   => '一次性还款',
    ];

    const INTEREST_BEFORE = 1;
    const INTEREST_BACK = 2;

    public static $operation = [
        self::INTEREST_BEFORE => '利息前置',
        self::INTEREST_BACK => '利息后置',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%loan_fk_record}}';
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

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'loan_person_id', 'loan_record_id', 'status', 'repay_type', 'apr', 'period', 'remark', 'sign_repayment_time',
            'fee_money', 'urgent_money', 'repay_operation', 'credit_repayment_time', 'created_at', 'updated_at','first_repay_time',
            'fk_money','audit_person'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }
}