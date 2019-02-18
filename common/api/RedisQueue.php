<?php

namespace common\api;

use Yii;

class RedisQueue {

    const STR_JXL_FUND = 'risk:jxl:house_fund:citylist'; //[风控]聚信立公积金城市列表

    /**
     * redis队列key，都用"list_"前缀
     */
    const LIST_PROJECT_INVEST = 'list_project_invest';    // 投资成功队列key
    const LIST_USER_MESSAGE = 'list_user_message';      // 用户注册或绑卡成功队列key
    const LIST_PROJECT_CONTRACT = 'list_project_contract';    // 待生成合同队列key
    const LIST_PROJECT_CONTRACT_PROJECT = 'list_project_contract_project';    // 待生成合同队列key
    const LIST_CRAZY_SHAKE = 'list_crazy_shake';    // 疯狂摇消息队列
    const LIST_FAILURE = 'list_crazy_shake_failure';    // 疯狂摇发奖失败消息队列
    const LIST_PROJECT_CONTRACT_PROJECT_TRANSFER = 'list_project_contract_project_transfer';    //待生成转让合同队列key
    const LIST_FINISH_INDIANA = 'list_finish_indiana';    //一元夺宝
    const LIST_FINISH_PAY_PASSWORD = 'list_finish_pay_password'; //更改支付密码队列
    const LIST_USER_PAY_ORDER_BAOFU = 'list_user_pay_order_baofu'; // 实时轮询宝付轮询订单状态
    const LIST_USER_CHECK_PAY_ORDER_BAOFU = 'list_user_check_pay_order_baofu'; // 每隔5分钟查询宝付订单状态

    const LIST_USER_MOBILE_CONTACTS_UPLOAD = 'list_user_mobile_contacts_upload';//每隔五分钟查询时候有通讯记录上传
    const LIST_USER_MOBILE_MESSAGES_UPLOAD = 'list_user_mobile_messages_upload';//上报短信
    const LIST_USER_MOBILE_APPS_UPLOAD = 'list_user_mobile_apps_upload';//上报app名字
    const LIST_USER_POCKET_CALCULATION_FAILED = 'list_user_pocket_calculation_failed';//零钱贷寄利息失败
    const LIST_USER_FZD_CALCULATION_LATE_FEE_FAILED = 'list_user_pocket_calculation_failed';//房租贷计算违约金失败
    const LIST_USER_POCKET_REPAYMENT_FAILED = 'list_user_pocket_repayment_failed';//零钱包自动提交还款失败

    const LIST_GET_USER_JXL_BASIC_REPORT_USER_INFO = 'list_get_user_jxl_basic_report_user_info';//获取用户聚信立基本报告用户信息队列
    const LIST_GET_USER_JXL_BASIC_REPORT_CAPTCHA = 'list_get_user_jxl_basic_report_captcha';//获取用户聚信立基本报告手机验证码队列
    const LIST_GET_USER_JXL_BASIC_REPORT_QUERY_PWD = 'list_get_user_jxl_basic_report_query_pwd';//获取用户聚信立基本报告查询密码队列
    const LIST_GET_USER_JXL_BASIC_REPORT_RESULT = 'list_get_user_jxl_basic_report_result';//获取用户聚信立基本报告队列
    const LIST_GET_USER_JXL_BASIC_REPORT_FAILED = 'list_get_user_jxl_basic_report_failed';//获取用户聚信立基本报告失败队列

    const LIST_GET_USER_YYS_BASIC_REPORT_USER_INFO = 'list_get_user_yys_basic_report_user_info';//获取用户运营商基本报告用户信息队列
    const LIST_GET_USER_YYS_BASIC_REPORT_CAPTCHA = 'list_get_user_yys_basic_report_captcha';//获取用户运营商基本报告手机验证码队列
    const LIST_GET_USER_YYS_BASIC_REPORT_QUERY_PWD = 'list_get_user_yys_basic_report_query_pwd';//获取用户运营商基本报告查询密码队列
    const LIST_GET_USER_YYS_BASIC_REPORT_RESULT = 'list_get_user_yys_basic_report_result';//获取用户运营商基本报告队列
    const LIST_GET_USER_YYS_BASIC_REPORT_FAILED = 'list_get_user_yys_basic_report_failed';//获取用户运营商基本报告失败队列
    //放款与还款回调时使用的队列名称
    const LIST_WEIXIN_USER_LOAN_INFO  = 'list_weixin_user_loan_info';//微信用户放款信息队列
    const LIST_WEIXIN_USER_DEBIT_INFO = 'list_weixin_user_debit_info';//微信用户还款信息队列

    const LIST_ASSET_NOTIFICATION = 'list_kdkj_asset_notification';//资产消息推送队列
    const LIST_GET_HD_CREDIT_ACCESS_TOKEN='list_get_hd_credit_access_token';//华道征信获取access_token

    const LOCK_INSTALLMENT_ORDER = 'lock_installment_order'; //分期购订单锁

    const LIST_CHECK_ORDER = 'list_check_order'; //风控校验订单队列
    const LIST_CHECK_ORDER_NEW = 'list_check_order_new'; //风控校验订单队列
    const LIST_ADD_TREE_DATA = 'list_add_tree_data'; //评分树增加数据队列
    const LIST_CHECK_CARD_QUALIFICATION = 'list_check_card_qualification'; //用户卡资格审查

    const LIST_USER_GET_PHONE_CAPTCHA = 'list:user:get:phone:captcha';  //用户获取手机验证码队列
    const LIST_USER_PROMOTION_SMS = 'list:user:promotion_sms';  //促销短信 (redis中的格式与验证码队列不同！！！)

    const LIST_USER_LOAN_LOG_MESSAGE  = "list:user:loan:log:message";            // 记录用户借款日志
    const LIST_USER_INCREASE_LOG_MESSAGE  = "list:user:increase:log:message";    // 记录用户提额日志

    const USER_TODAY_LOAN_MAX_AMOUNT  = "user:today:max:loan:amount";           // 每天白卡固定额度
    const USER_TODAY_LOAN_GOLDEN_AMOUNT  = "user:today:golden:loan:amount";     // 每天金卡固定额度

    const LIST_USER_DATA_WALL  = "ygd_list_user_data_wall";    // 用户注册积分墙数据队列

    const LIST_CHANNEL_FEEDBACK = 'list:channel:feedback';//渠道反馈队列 如融360，借了吗等

    const LIST_FUND_ORDER_EVENT = 'list:fund:order:event';//资方 订单事件

    const LIST_APP_EVENT_MESSAGE = "list:app:event:message"; //应用事件处理队列

    const USER_LOAN_QUEUE_MAX_LENGTH  = 15; //记录队列固定长度

    const LIST_CREDIT_USER_DETAIL_RECORD = "list:credit:user:detail:record"; // 用户主动申请提额操作

    const LIST_VOICE_SMS = "list:voice:sms"; // 语音电话

    const LIST_LOAN_SUGGESTION = "list:loan:suggestion"; // 催收建议
    const LIST_LOAN_RENEW_OUT = "list:loan:renew:out"; // 续借出催
    const LIST_LOAN_PAYBACK_OUT = "list:loan:payback:out"; // 还款出催

    const LIST_INVITE_ORDER_EVENT = "list:invite:order:event"; // 邀请活动事件队列
    const LIST_ACTIVITY_EVENT = "list:activity:event"; // 活动事件通用队列

    const USER_LOAN_REGIST_COUPON_KEY = "user:loan:regist:coupon:key:user_id"; //用户注册发送红包

    const LIST_USER_ALIPAY_INFO = "list_user_alipay_info";  //用户支付宝数据队列
    const LIST_USER_TAOBAO_INFO = "list_user_taobao_info";  //用户淘宝数据队列
    const LIST_USER_JD_INFO = "list_user_jd_info";  //京东数据队列


    const LIST_ANALYSIS_ORDER = "list_analysis_order"; // 风控 数据分析订单队列
    const LIST_NEW_OPERATOR_COUNT = 'list_new_operator_count'; //新的用户的申请运营商人数队列

    const LIST_HOUSEFUND_TOKEN = 'list:housefund:token';//公积金获取token队列

    const LIST_CHANNEL_CALC_HISTORY = 'list:channelcalc:history';//渠道结算历史数据队列

    const LIST_PAY_SUCCESS_SEND_COUPON = 'list:pay:success:send:coupon';//成功打款优惠券发放数据队列

    const LIST_SEND_COUPON_BY_SCENE = 'list:send:coupon:by:scene';//成功打款优惠券发放数据队列

    const LOAN_SUCCESS_USE_COUPON = 'loan:success:use:coupon';//借款成修改优惠券信息队列

    const LIST_PUSH_TASK = 'list:push:task'; //极光推送数据队列

    const HASH_USER_FIRST_REPAYMENT_TIME = "wzd:user:first_repayment"; //用户首次还款时间表，仅定义
    const HASH_USER_CONTACTS_UPLOAD = "wzd:user:contacts_upload"; //用户通讯录已上传

    const LIST_DATA_REPORTING_BLACKLIST = 'list:data:reporing:blacklist'; //上报黑名单数据到集团接口

    const LIST_BR_CREDIT_DATA = 'list:br:credit:data'; // 百融数据队列

    const WEIXIN_TOKEN_LOCK = 'weixin_token_lock';//获取微信的锁

    /**
     * 添加锁
     */
    const USER_OPERATE_LOCK = "user:operate:lock:";
    const USER_QUEUE_MAIL = "user:operate:lock:queue:mail"; //发邮件的队列

    static function getRedis($params) {
        if (empty($params)) {
            return \yii::$app->redis;
        }

        return \yii::$app->redis;
    }

    public static function push($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('RPUSH', $params);
    }

    public static function pop($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('LPOP', $params);
    }

    public static function set($params=['expire'=>3600,'key'=>'','value'=>'']) {
        $redis = self::getRedis($params['key']);

        $redis->set($params['key'], $params['value']);
        $redis->expire($params['key'], $params['expire']);
        return true;
    }

    public static function get($params=['key'=>'']) {
        $redis = self::getRedis($params['key']);
        return $redis->get($params['key']);
    }

    public static function del($params=['key'=>'']) {
        $redis = self::getRedis($params['key']);
        return $redis->del($params['key']);
    }

    /**
     * 控制队列长度
     * COMMAND : LTRIM KEY_NAME START STOP
     */
    public static function getFixedLength($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('LTRIM', $params);
    }

    /**
     * 获取队列所有元素
     * COMMAND : lrange KEY_NAME 0 -1
     */
    public static function getQueueList($params) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('LRANGE', $params);
    }

    /**
     * 获取当前队列长度
     */
    public static function getLength($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('LLEN', $params);
    }

    /**
     * 递增
     */
    public static function inc($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('INCRBY', $params);
    }

    /**
     * 重置过期时间
     */
    public static function expire($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('EXPIRE', $params);
    }

    /**
     * lock
     */
    public static function setnx($params) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('SETNX', $params);
    }

    /**
     * 递减
     */
    public static function dec($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('DECRBY', $params);
    }

    /**
     * 递减
     */
    public static function desc($params = []) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('DECRBY', $params);
    }

    /**
     * 递增
     */
    public static function incr($params = ['key'=>'']) {
        $redis = self::getRedis($params);
        return $redis->executeCommand('INCRBY', $params);
    }

}

