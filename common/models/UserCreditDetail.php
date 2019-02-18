<?php

namespace common\models;

use api\exceptions\Exception;
use common\api\RedisQueue;
/**
 * This is the model class for table "{{%_user_credit_detail}}".
 */
class UserCreditDetail extends BaseActiveRecord
{
    const TAG_USER_CENTER_LOAN = 1; //借款记录
    const TAG_USER_CENTER_INFO = 2; //完善资料
    const TAG_USER_CENTER_CARD = 3; //收款银行卡
    const TAG_USER_CENTER_HELP = 4; //帮助中心

    const TAG_USER_CENTER_COUPON   = 5; //我的优惠
    const TAG_USER_CENTER_MESSAGE  = 6; //公告中心
    const TAG_USER_CENTER_INVITE   = 7; //我的邀请
    const TAG_USER_CENTER_SETTING  = 8; //设置
    const TAG_USER_CENTER_SITE_URL = 9; //跳转地址
    const TAG_USER_CENTER_NOTICE   = 10; //我的消息

    const TAG_GROUP_TYPE_ONE = 1;
    const TAG_GROUP_TYPE_TWO = 2;
    const TAG_GROUP_TYPE_THREE = 3;

    const STATUS_NORAML = 0;
    const STATUS_ING = 1;
    const STATUS_FINISH = 2;
    const STATUS_WAIT = 3; //等待人工确认

    const USER_CREDIT_TYPE_ZERO = 0;  //新用户
    const USER_CREDIT_TYPE_ONE  = 1;  //老用户

    const CARD_GOLDEN_REJECT = 0;
    const CARD_GOLDEN_AUTO_PASS = 1;
    const CARD_GOLDEN_MANUAL = 2;
    const CARD_GOLDEN_MANUAL_REJECT = 3;
    const CARD_GOLDEN_MANUAL_PASS = 4;
    const CARD_GOLDEN_ING = 5;

    public static $status = [
        self::STATUS_NORAML=>'未完成',
        self::STATUS_ING=>'认证中',
        self::STATUS_FINISH=>'已完成',
        self::STATUS_WAIT=>'待审核',
    ];

    public static $card_golden = [
        self::CARD_GOLDEN_REJECT=>'转人工',
        self::CARD_GOLDEN_AUTO_PASS   =>'机审通过',
        self::CARD_GOLDEN_MANUAL=>'机审拒绝',
        self::CARD_GOLDEN_MANUAL_REJECT=>'人工拒绝',
        self::CARD_GOLDEN_MANUAL_PASS=>'人工通过',
        self::CARD_GOLDEN_ING => '激活中'
    ];

    public static $card_pass = [
        self::CARD_GOLDEN_AUTO_PASS,
        self::CARD_GOLDEN_MANUAL_PASS,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_credit_detail}}';
    }

    /**
     * 添加用户授信重复点击锁
     */
    public static function lockUserCreditRecord($user_id){
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"user:credit:detail:user_id:",$user_id);

        if (1 == RedisQueue::inc([$lock_key,1])) {
            RedisQueue::expire([$lock_key,60]);
            return true;
        
        }else{
            RedisQueue::expire([$lock_key,60]);
        }
        return false;
    }

    /**
     * 获取用户授信重复点击锁
     * @param $user_id
     * @return mixed
     */
    public static function getUserCreditRecordLock($user_id)
    {
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"user:credit:detail:user_id:",$user_id);
        try{
            $res = RedisQueue::get($lock_key);
        } catch (\Exception $e) {
            $res = '';
        }
        return $res;
    }

     /**
     * 添加用户授信重复插入锁
     */
    public static function lockUserCreditInsertRecord($user_id){
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"user:credit:insert:user_id:",$user_id);

        if (1 == RedisQueue::inc([$lock_key,1])) {
            RedisQueue::expire([$lock_key,15]);
            return true;
        }else{
            RedisQueue::expire([$lock_key,15]);
        }
        return false;
    }

    /**
     * 释放用户授信重复点击锁
     */
    public static function releaseUserCreditLock($user_id){
        $lock_key  = sprintf("%s%s:%s",RedisQueue::USER_OPERATE_LOCK,"user:credit:detail:user_id:",$user_id);
        RedisQueue::del(["key"=>$lock_key]);
    }

    /**
     * 初始化用户认证信息
     */
    public static function initUserCreditDetail($user_id, $type=0) {
        try {
            $model = new static();
            $model->user_id = $user_id;
            $model->credit_status = self::STATUS_NORAML;
            $model->expire_time = strtotime('+6 months');
            $model->user_type = $type;
            if($model->insert()){
                return [
                    "code" => 0,
                    "message" => "操作成功",
                ];
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }

        // 释放锁
        static::releaseUserCreditLock($user_id);
        return [
            "code" => -1,
            "message" => "操作失败",
        ];
    }
}
