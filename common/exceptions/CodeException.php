<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 2014/12/25
 * Time: 14:12
 */

namespace common\exceptions;

use yii;

class CodeException
{

    const LOGIN_DISABLED=-2;
    const SUCCESS=0;
    const UNTIED_BANK_CARD=1;
    const UNSET_TRADE_PASSWORD=2;
    const PAYPASSWORD_ERROR = 3;
    const ID_CARD_USED = 4;
    const COMPAY_EMAIL_BIND = 5;
    const KDLC_USER = 6;
    const HAVE_ORDER_CHECK = 7;
    const YGB_SUER = 8;
    const NEED_VERIFY = 9;
    const MOBILE_REGISTERED=1000;
    const VERSION_UNUSED = 1001;
    const CLIENT_TYPE_ERROR = 1002;
    const QUOTA_NULL = 1003;

    public static $code=[
        self::LOGIN_DISABLED=>'登录态失效',
        self::SUCCESS=>'成功',
        self::UNTIED_BANK_CARD=>'未绑定银行卡',
        self::UNSET_TRADE_PASSWORD=>'未设置交易密码',
        self::PAYPASSWORD_ERROR=>'支付密码错误',
        self::ID_CARD_USED =>'该身份证已被绑定，请换一张身份证',
        self::COMPAY_EMAIL_BIND=>'该邮箱已被绑定，请更换邮箱重新认证',
        self::KDLC_USER=>'请输入您的登录密码',
        self::HAVE_ORDER_CHECK=>'你有处于审核状态下的借款,无法申请新的借款',
        self::YGB_SUER=>'请输入您的登录密码',
        self::MOBILE_REGISTERED=>'手机号已注册',
        self::VERSION_UNUSED=>'版本过期',
        self::CLIENT_TYPE_ERROR=>'客户端类型错误',
        self::QUOTA_NULL => '客户额度为零',
        self::NEED_VERIFY => '认证未完成',
    ];


    const WEIXIN_ERROR_YGB = 19;

    public static $weixin_market_id_warning = array(
        self::WEIXIN_ERROR_YGB =>'员工帮系统错误报警',
    );

}