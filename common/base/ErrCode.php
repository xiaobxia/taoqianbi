<?php

namespace common\base;

/**
 * 错误码
 */
class ErrCode
{

    const INTERNAL_ERROR = -1;//内部错误

    const NONE = 0;//无错误

    #基础错误
    const MISS_PARAMS = 10000;//参数缺失
    const INVALID_PARAMS = 10001;//参数不合法

    #订单错误
    const ORDER_LOCK = 11000;//订单已被锁定
    const ORDER_CANCEL_RENEW_TIMES_INVALID = 11001;//订单取消续期次数不合法
    const NOT_AVAILABLE_FUND = 11002;//无可用资方
    const FUND_UNSUPPORT_BANK = 11003;//资方不支持该银行
    const ORDER_HAS_FUND = 11004;//订单已有资方
    const FUND_OVER_QUOTA = 11005;//资方超出额度
    const ORDER_STATUS_INVALID = 11006;//订单状态不合法
    const ORDER_NOT_FUND = 11007;//订单找不到
    const ORDER_STATUS_CHANGE_ERROR = 11008;//改变订单状态错误
    const ORDER_FUND_FEE_NOT_FOUND = 11009;//订单费用获取不到
    const ORDER_FUND_SIGN_NOT_FOUND = 11010;//订单资方签约记录获取不到
    const ORDER_FUND_SIGN_ACCOUNT_NOT_FOUND = 11011;//订单资方签约账号获取不到
    const ORDER_FUND_INFO_NOT_FOUND = 11012;//订单资方信息获取不到
    const ORDER_FUND_INFO_STATUS_INVALID = 11013;//订单资方信息状态不合法
    const ORDER_FUND_NOT_MATCH = 11014;//订单资方不匹配
    const ORDER_FUND_INFO_DISABLE = 11015;//订单资方信息不可用
    const ORDER_FUND_NO_CHANGE = 11016;//订单资方没有变化
    const ORDER_FUND_HAS_CHANGE = 11017;//订单资方已经变化 
    const ORDER_USER_NOT_FOUND = 11018;//用户找不到
    const ORDER_FUND_ACCOUNT_SIGNED = 11019;//订单资方账号已经签约
    const ORDER_CARD_NOT_BIND = 11020;//订单未绑卡
    const FUND_UNSUPPORT_TERM = 11021;//资方不支持该期限
    const ORDER_FUND_RECORD_EXIST = 11022;//订单资方记录已经存在
    const ORDER_FUND_SETTLEMENT_FINISH = 11023;//订单资方记录结算已经完成
    const ORDER_FUND_PREPAY_AMOUNT_INVALID = 11024;//订单资方金额不合法
    const FUND_AMOUNT_LIMIT = 11025;//资方金额数量限制
    const FUND_ORDER_PUSHED = 11026;//订单状态已经推送
    const FUND_UNSUPPORT_CARD_TYPE = 11027;//资方不支持卡类型（白卡  发薪卡之类）

    #51资方错误
    const FUND_51_BIND_CARD_ERROR = 12000;//51绑卡错误
    const FUND_51_CONFIRM_SIGN_ERROR = 12001;//51确认签约错误
    const FUND_51_PUSH_ORDER_ERROR = 12003;//51推送订单错误
    const FUND_51_QUERY_ORDER_ERROR = 12004;//51推送订单错误
    const FUND_51_SIGN_ERROR = 12005;//51签约错误
    const FUND_51_PARSE_ERROR = 12006;//51解释数据错误

    #口袋
    const FUND_KOUDAI_ORDER_HAS_PUSH = 13000;//订单已经推送
    const FUND_KOUDAI_REQUEST_ERROR = 13001;//口袋请求失败

    #微贷网
    const FUND_WEIDAI_ORDER_SUCCESS = 0000;//交易成功
    const FUND_WEIDAI_ERROR = 1001;//系统异常
    const FUND_WEIDAI_PARAM_ILLEGAL = 1002;//参数非法
    const FUND_WEIDAI_CALL_ERROR = 1003;//调用异常
    const FUND_WEIDAI_CALLBACK_ERROR = 1004;//返回异常
    const FUND_WEIDAI_RUNTIME_ERROR = 1005;//运行异常
    const FUND_WEIDAI_OPEN_ACCOUNT_FAIL = 1006;//开户出错
    const FUND_WEIDAI_SIGN_FAIL = 1008;//验签失败
    const FUND_WEIDAI_ORDER_FAIL = 1009;//推标出错
    const FUND_WEIDAI_DUPLICATED_ORDER = 1010;//重复请求

    #渠道错误码
    const FAIL_TO_GET_AMOUNT = 14001;//获取额度失败
    const FAIL_SAVE_RMDC_DATA = 14002;//风控数据保存失败
    const FAIL_CREATE_ORDER = 14003;//创建订单失败
    const INVALID_ORDER = 14004;//无效的订单
    const REPAY_COMPLETE = 14005;//用户已还款
    const REPAY_FAIL = 14006;//主动还款失败
    const INVALID_PHONE = 14007;//无效的手机号
    const INVALID_BANK = 14008;//无效的银行
    const INVALID_CARD_INFO = 14009;//无效的银行卡信息
    const INVALID_ID_NUMBER = 14010;//无效的身份证号
    const INVALID_LOAN_DAY = 14011;//无效的借款期限
    const INVALID_LOAN_AMOUNT = 14012;//无效的借款金额
    const INVALID_AGE = 14013;//用户年龄不在可借范围内
    const LOAN_REJECTED = 14014;//拒绝借款
    const FAIL_REGISTER = 14015;//注册失败
    const USER_REAL_VERIFY_ERROR = 14016;//实名认证失败
    const SYSTEM_ERROR = 14017;//系统错误
    const INVALID_USER = 14018;//用户不存在

    #错误码对照表
    public static $code_list = [
        self::FAIL_TO_GET_AMOUNT => '获取额度失败',
        self::FAIL_SAVE_RMDC_DATA => '风控数据保存失败',
        self::FAIL_CREATE_ORDER => '创建订单失败',
        self::INVALID_ORDER => '无效的订单',
        self::REPAY_COMPLETE => '用户已还款',
        self::REPAY_FAIL => '主动还款失败',
        self::INVALID_PHONE => '无效的手机号',
        self::INVALID_BANK => '无效的银行',
        self::INVALID_CARD_INFO => '无效的银行卡信息',
        self::INVALID_ID_NUMBER => '无效的身份证号',
        self::INVALID_LOAN_DAY => '无效的借款期限',
        self::INVALID_LOAN_AMOUNT => '无效的借款金额',
        self::INVALID_AGE => '用户年龄不在可借范围内',
        self::LOAN_REJECTED => '拒绝借款',
        self::FAIL_REGISTER => '注册失败',
        self::USER_REAL_VERIFY_ERROR => '实名认证失败',
        self::SYSTEM_ERROR => '系统错误',
        self::INVALID_USER => '用户不存在',
    ];
}

