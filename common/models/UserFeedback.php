<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_feedback}}".
 */
class UserFeedback extends ActiveRecord
{

    const TYPE_PRODUCT      = 0; // 产品问题
    const TYPE_COLLECTION   = 1; // 催收投诉
    const TYPE_COLLECTION2   = 2; // 催收投诉

    const SUB_TYPE_DEFAULT    = 0; // 未知
    const SUB_TYPE_MAJOR    = 1; // 业务处理不专业
    const SUB_TYPE_NOTICE   = 2; // 逾期通知不及时
    const SUB_TYPE_PAYMENT  = 3; // 扣款不及时
    const SUB_TYPE_OTHER    = 4; // 其他
    const SUB_TYPE_SELF_REPAYMENT    = 5; // 无法自主还款

    const STATUS_NO         = 0; // 反馈未处理
    const STATUS_PASS       = 1; // 反馈已处理
    const STATUS_IGNORE     = 2; // 反馈忽略

    public static $type     = [
        self::TYPE_PRODUCT      => '产品问题',
        self::TYPE_COLLECTION   => '催收投诉',
        self::TYPE_COLLECTION2   => '催收投诉2',
    ];

//     public static $sub_type = [
//         self::SUB_TYPE_MAJOR     => '业务处理不专业',
//         self::SUB_TYPE_NOTICE    => '逾期通知不及时',
//         self::SUB_TYPE_PAYMENT   => '扣款不及时',
//         self::SUB_TYPE_OTHER     => '其他',
//         self::SUB_TYPE_DEFAULT   => '其他',
//     ];
    
    public static $sub_type = [
    		self::SUB_TYPE_SELF_REPAYMENT=> '无法自主还款',
    		self::SUB_TYPE_PAYMENT=> '扣款未成功',
    		self::SUB_TYPE_MAJOR=> '催收客服不专业',
    		self::SUB_TYPE_OTHER     => '其他',
    		self::SUB_TYPE_NOTICE	=> '逾期通知不及时',
    		self::SUB_TYPE_DEFAULT=> '其他',
    ];
    
    public static $show_sub_text = [
        self::SUB_TYPE_SELF_REPAYMENT => '无法自主还款',
        self::SUB_TYPE_PAYMENT   => '扣款未成功',
        self::SUB_TYPE_MAJOR     => '催收客服不专业',
        self::SUB_TYPE_OTHER     => '其他',
    ];

    public static $status   = [
        self::STATUS_NO     => '未处理',
        self::STATUS_PASS   => '已处理',
        self::STATUS_IGNORE => '已忽略',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_feedback}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }



}
