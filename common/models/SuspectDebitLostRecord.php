<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/8/4
 * Time: 下午5:55
 */

namespace common\models;

use yii;
use yii\db\ActiveRecord;

class SuspectDebitLostRecord extends ActiveRecord {

    const STATUS_DEFAULT = 0; //未标记
    const STATUS_FAILED_SHELL  = 1; //脚本标记失败
    const STATUS_FAILED_STAFF  = 2; //手动标记失败
    const STATUS_FAILED_CALLBACK  = 3; //回调标记失败
    const STATUS_FAILED_QUERY  = 6; //查询标记失败
    const STATUS_SUCCESS_UNREPAYMENT  = 4; //标记成功(未还款)
    const STATUS_SUCCESS_REPAYMENTED  = 5; //标记成功(已还款)

    const DEBIT_TYPE_DEFAULT = 0; //默认
    const DEBIT_TYPE_SYSTEM  = 1; //系统代扣
    const DEBIT_TYPE_ACTIVE  = 2; //主动还款



    const MARK_TYPE_DEFAULT  = 0; //未标记
    const MARK_TYPE_SYSTEM   = 1; //脚本标记
    const MARK_TYPE_CALLBACK = 2; //回调设置
    const MARK_TYPE_STAFF    = 3; //人工标记

    public static $MARK_TYPE_ARR = [
        self::MARK_TYPE_DEFAULT => '未标记',
        self::MARK_TYPE_SYSTEM => '脚本标记',
        self::MARK_TYPE_CALLBACK => '回调设置',
        self::MARK_TYPE_STAFF => '人工标记',
    ];
    public static $DEBIT_TYPE_ARR = [
        self::DEBIT_TYPE_DEFAULT => '默认',
        self::DEBIT_TYPE_SYSTEM => '系统代扣',
        self::DEBIT_TYPE_ACTIVE => '主动还款'
    ];
    public static $STATUS_ARR = [
        self::STATUS_DEFAULT => '待观察',
        self::STATUS_FAILED_SHELL => '系统置为失败(需关注)',
        self::STATUS_FAILED_STAFF => '手动置为失败(需关注)',
        self::STATUS_FAILED_CALLBACK => '扣款失败(回调)',
        self::STATUS_FAILED_QUERY => '扣款失败(查询)',
        self::STATUS_SUCCESS_UNREPAYMENT => '扣款成功(已入账)',
        self::STATUS_SUCCESS_REPAYMENTED => '扣款成功(未入账)',
    ];
    public function behaviors() {
        return [
            yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public static function tableName() {
        return '{{%suspect_debit_lost_record}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), ['id' => 'card_id']);
    }

    public function getUserLoanOrder()
    {
        return $this->hasOne(UserLoanOrder::className(), ['id' => 'order_id']);
    }
}