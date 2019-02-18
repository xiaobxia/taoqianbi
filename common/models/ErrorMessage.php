<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%area}}".
 */
class ErrorMessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%error_message}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    const SOURCE_ALL = -1;
    const SOURCE_ZM = 1;
    const SOURCE_MG = 2;
    const SOURCE_FKB = 3;
    const SOURCE_TD = 4;
    const SOURCE_JXL = 5;
    const SOURCE_HDZX = 6;
    const SOURCE_YXZC = 7;
    const SOURCE_ZZC = 8;
    const SOURCE_YD = 9;
    const SOURCE_BQS = 10;
    const SOURCE_ZBKJ = 11;
    const SOURCE_SAURON = 12;
    const SOURCE_BR = 13;
    const SOURCE_CERT = 14;
    const SOURCE_REALNAME = 15;
    const SOURCE_CHECKCARD = 16;
    const SOURCE_BINDCARD = 17;
    const SOURCE_IDCARD_CERT = 18;
    const SOURCE_BINDCARDCODE = 19;
    const SOURCE_MOBILEOPERATORS = 20;
    const SOURCE_BANKCARDSAVE = 21;

    public static $source = [
        self::SOURCE_ALL => '全部',
        self::SOURCE_ZM => '芝麻信用',
        self::SOURCE_MG => '蜜罐',
        self::SOURCE_SAURON => '索伦',
        self::SOURCE_FKB => '防控宝',
        self::SOURCE_TD => '同盾',
        self::SOURCE_JXL => '聚信立',
        self::SOURCE_HDZX => '华道征信',
        self::SOURCE_YXZC => '宜诚至信',
        self::SOURCE_ZZC => '中智诚',
        self::SOURCE_YD => '有盾',
        self::SOURCE_BR => '百融',
        self::SOURCE_CERT => 'face++人脸',
        self::SOURCE_IDCARD_CERT => 'face++身份证',
        self::SOURCE_REALNAME => '实名认证',
        self::SOURCE_CHECKCARD => '银行卡验证',
        self::SOURCE_BINDCARD => '银行卡四要素鉴权',
        self::SOURCE_BINDCARDCODE => '银行卡四要素鉴权验证码',
        self::SOURCE_MOBILEOPERATORS => '手机运营商',
        self::SOURCE_BANKCARDSAVE => '保存银行卡'
    ];

    //处理状态
    const STATUS_SUCCESS = 1;
    const STATUS_DEFAULT = 0;
    const STATUS_ALL = '';

    public static $status_remark=[
        self::STATUS_ALL => '全部',
        self::STATUS_SUCCESS => '已成功',
        self::STATUS_DEFAULT => '未处理'
    ];

    public static function getMessage($user_id, $error, $source)
    {
        if ($error != "该token没有报告。" && $error!='') {
            $message = new ErrorMessage();
            $message->user_id = $user_id;
            $message->message = is_array($error) ? json_encode($error) :$error;
            $message->error_source = $source;
            $message->error_time = time();
            $message->save();
        }
    }
}