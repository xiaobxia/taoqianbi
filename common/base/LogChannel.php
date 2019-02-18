<?php
namespace common\base;

/**
 * log 分类
 */
class LogChannel {
    const SYSTEM_GENERAL = 'system.general'; #不知道哪里报错...
    const SYSTEM_SMS_WARNING = 'system.sms.warning'; #系统短信报警
    const SYSTEM_API_PARAM_ERROR = 'system.api.param.err'; #API请求参数有问题
    const SYSTEM_UA_MISSING = 'system.ua.missing'; #请求中的 ua 信息缺失

    const ADMIN_UPDATE_USER = 'admin.update_user'; # 更新用户信息

    const CHANNEL_USER_REG = 'channel.user.reg'; #渠道用户注册
    const CHANNEL_USER_LOGIN = 'channel.user.login'; #渠道用户登录

    const USER_REGISTER = 'user.register';
    const USER_LOGIN = 'user.login';
    const USER_UPLOAD = 'user.upload'; # 用户信息上传
    const USER_CARD = 'user.card'; # 用户银行卡
    const USER_ID_CARD = 'user.id_card'; # 用户身份证识别
    const USER_CREDIT = 'user.credit'; # 用户授信

    const SMS_GENERAL = 'sms.general';
    const SMS_REGISTER = 'sms.register';
    const SMS_FAKE_SUCCESSS = 'sms.fake_success'; //不发短信，返回成功
    const SMS_VOICE = 'sms.voice';
    const SMS_COLLECT = 'sms.collect'; //获取短信上行
    const SMS_STATUS = 'sms.status'; //获取短信状态
    const SMS_SCHEDULE = 'sms.schedule'; //脚本获取短信上行

    const CREDIT_CENSOR = 'dict_censor';
    const CREDIT_GENERAL = 'credit.general';
    const CREDIT_FACEPP = 'credit.facepp';
    const CREDIT_ZMXY = 'credit.zmxy';
    const CREDIT_JXL = 'credit.jxl';
    const CREDIT_HULU = 'credit.hulu';
    const CREDIT_MOXIE = 'credit.moxie';
    const CREDIT_ICEKREDIT = 'credit.icekredit';
    const CREDIT_GET_DATA_SOURCE_TIME = 'credit.get_data_source_time';
    const CREDIT_GET_DATA_SOURCE = 'credit.get_data_source';//数据落地log
    const CREDIT_SET_CREDIT_LINE = 'credit.set_credit_line';//授信log
    const CREDIT_AUTO_CHECK = 'credit.auto_check';//机审脚本

    const SERVICE_KDSOA = 'service.koudaisoa'; //口袋soa服务

    const FUND_GENERAL = 'wzd.fund.general'; //资方接口报错

    const MAIL_GENERAL = 'mail.general';

    const RISK_CONTROL = 'risk_control';
    const RISK_DEBUG = 'risk-debug';

    const CHECK_REGULAR_RESULT = 'check_regular_result'; //老用户续借审核结果

    const ORDER_RESULT = 'order_result'; #审单结果
    const ORDER_COUPON = 'order_coupon'; #订单优惠券相关
    const CODE_PHONE = 'code_phone';     //微信中奖手机号
    const FINANCIAL_PAYMENT = 'financial_payment'; #打款
    const FINANCIAL_DEBIT = 'financial_debit'; #扣款

    const ALIPAY_SYNC_LOG = 'alipay_sync_log'; #扣款

    const CHANNEL_RATE = 'channel_rate'; #渠道更新

    const GJJ_ORDER_SCHEDULE = 'gjj_order_schedule';//公积金放款统计脚本
    const GJJ_REPAYMENT_ORDER_SCHEDULE = 'gjj_repayment_order_schedule';//公积金还款统计脚本
    const USER_COUPON_SCHEDULE = 'user_coupon_schedule';//优惠券统计脚本

    const USER_CREDIT_CARD_AUTH = 'user_credit_card_auth_check'; //用户信用卡鉴权日志

    const CHANNEL_ORDER = 'channel_order';

    const CHANNEL_BRWH_ERROR = 'channel_brwh_error';//注册失败
    const CHANNEL_BRWH_MSG_ERROR = 'channel_brwh_msg_error';//短信发送失败
}

