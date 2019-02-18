<?php
namespace common\exceptions;

use yii;

class ExternalApiExceptionExt extends \common\exceptions\UserExceptionExt {

    const PARAM_MISSING = 1001;
    const CHANNEL_MISSING = 1002;
    const SIGN_VALIDATE_FAILED = 1003;
    const STR_MAIL_ERROR = 1004;
    const STR_QQ_ERROR = 1005;
    const STR_PHONE_ERROR = 1006;
    const SMS_SEND_FAILED = 1007;
    const BANK_CARD_NO_ERROR = 1008;
    const BANK_CARD_ONLY_ONE = 1009;
    const BANK_SMS_CODE_ERROR = 1010;
    const BANK_ID_ERROR = 1011;
    const BANK_CARD_EXIST = 1012;
    const BANK_CARD_TYPE_ERROR = 1013;
    const BANK_CARD_VERIFY_ERROR = 1014;
    const BANK_CARD_SAVE_FAILED = 1015;
    const PAY_PASSWORD_UNSET = 1016;
    const PAY_PASSWORD_SETED = 1017;
    const PAY_PASSWORD_SET_FAILED = 1018;
    const STR_COMPANY_WORKTYPE_ERROR = 1019;
    const APPLY_TOO_FAST = 1020;
    const UPLOAD_INFO_TYPE_ERROR = 1021;
    const UPLOAD_INFO_DATA_ERROR = 1022;
    const UPLOAD_DATA_FORMAT_ERROR = 1023;
    const STR_CALLBACK_URL_ERROR = 1024;
    const PAY_PASSWORD_EMPTY = 1025;
    const PAY_PASSWORD_ERROR = 1026;
    const PAY_SIGN_CREATE_FAILED = 1027;
    const BANK_CARD_INFO_ERROR = 1028;
    const PAY_OLD_PASSWORD_ERROR = 1029;
    const BANK_BIAND_INFO_ERROR = 1030;

    const USER_CREATE_FAILED = 2001;
    const USER_RELATION_EXIST = 2002;
    const USER_RELATION_SAVE_FAILED = 2003;
    const USER_NOT_FOUND = 2004;
    const USER_FOUND_FAILED = 2005;
    const USER_UNVERIFY = 2006;
    const USER_VERIFIED = 2007;
    const USER_NEED_VERIFY = 2008;
    const USER_VERIFY_FAILED = 2009;
    const USER_ID_CARD_USED = 2010;
    const USER_SAVE_FAILED = 2011;
    const USER_MARRIAGE_ERROR = 2012;
    const USER_DEGREES_ERROR = 2013;
    const USER_CONTRACT_TYPE_ERROR = 2014;
    const USER_CONTRACT_SPARE_TYPE_ERROR = 2015;
    const USER_ZM_VERIFYED = 2016;
    const USER_LIVETIME_ERROR = 2017;
    const USER_COMPANYPERIOD_ERROR = 2018;
    const USER_ID_CARD_FACE_SAVE_FAILED = 2019;
    const USER_ID_CARD_BACK_SAVE_FAILED = 2020;
    const USER_ID_NUMBER_CHECK_FAILED = 2021;
    const USER_LOAN_CHECK_FAILED = 2022;
    const USER_LOAN_GET_CREDIT_FAILED = 2023;
    const USER_REGISTER_PHONE_ERROR = 2024;
    const USER_REGISTER_INFO_ERROR = 2025;
    const USER_RESET_PASSWORD = 2026;
    const USER_PAY_PASSWORD_LEN_ERROR = 2027;
    const USER_CARD_IMG_FRONT_ERROR = 2028;
    const USER_CARD_IMG_BACK_ERROR = 2029;
    const USER_CARD_IMG_FACE_ERROR = 2030;
    const USER_CARD_IMG_HEAD_EXIT = 2031;
    const USER_CARD_IMG_FRONT_EXIT = 2032;
    const USER_CARD_IMG_BACK_EXIT = 2033;
    const USER_CARD_IMG_HEAD_NO_EXIT = 2035;
    const USER_CARD_IMG_FRONT_NO_EXIT = 2036;
    const USER_CARD_IMG_BACK_NO_EXIT = 2037;
    const USER_CARD_IMG_FACE_SAVE_ERROR = 2034;
    const PAY_PASSWORD_NO_NUM = 2038;


    const DATA_SAVE_FAILED = 3001;
    const UPLOAD_FILE_TYPE_ERROR = 3002;
    const UPLOAD_FILE_ERROR = 3003;
    const UPLOAD_FILE_FAILED = 3004;
    const GET_LOAN_CREDIT = 3005;
    const GET_YYS_STATUS_FAILED = 3006;
    const GET_YYS_INFO_STATUS_FAILED = 3007;
    const GET_YYS_INFO_PHONE_EMPTY=3008;
    const YSS_STATUS_CAPTCHA_ERROR=3009;
    const YSS_STATUS_WAIT_QUERY_PWD_RESULT=3010;
    const YSS_STATUS_INPUT_QUERY_PWD=3011;
    const USER_CONTENT_EXIT=3012;


    const ORDER_UNPAY = 4001;
    const ORDER_NOT_FOUND = 4002;
    const ORDER_CREATE_FAILED = 4003;
    const USER_LOAN_ORDER_CHECK = 4004;
    const USER_CARD_CHECK_ERROR = 4005;

    const CARD_ADD_PHONE_EMPTY = 4006;
    const CARD_ADD_CARD_NO_EMPTY = 4007;



    public static $ERROR_MSG = [
        self::PARAM_MISSING => '必要参数缺失',
        self::CHANNEL_MISSING => '渠道号未设置',
        self::SIGN_VALIDATE_FAILED => '签名验证失败',
        self::STR_MAIL_ERROR => '邮箱不合法',
        self::STR_QQ_ERROR => 'QQ不合法',
        self::STR_PHONE_ERROR => '电话不合法',
        self::STR_COMPANY_WORKTYPE_ERROR => '工作类型未定义',
        self::SMS_SEND_FAILED => '发送验证码失败，请稍后再试',
        self::BANK_CARD_NO_ERROR => '银行卡号错误',
        self::BANK_CARD_INFO_ERROR => '银行卡信息错误',
        self::BANK_SMS_CODE_ERROR => '验证码错误或已过期',
        self::BANK_ID_ERROR => '银行选择错误',
        self::BANK_CARD_EXIST => '该银行卡已被绑定过',
        self::BANK_CARD_ONLY_ONE => '只能绑定一张借记卡',
        self::BANK_CARD_TYPE_ERROR => '数据有误，验证银行卡类型失败',
        self::BANK_CARD_VERIFY_ERROR => '银行卡验证错误',
        self::BANK_CARD_SAVE_FAILED => '银行卡状态保存失败',
        self::PAY_PASSWORD_UNSET => '支付密码未设置',
        self::PAY_PASSWORD_SETED => '支付密码已设置',
        self::PAY_PASSWORD_SET_FAILED => '支付密码设置失败，只能为6位数字',
        self::PAY_PASSWORD_EMPTY => '支付密码不能为空',
        self::PAY_PASSWORD_ERROR => '支付密码不正确',
        self::PAY_SIGN_CREATE_FAILED => '获取失败',
        self::PAY_OLD_PASSWORD_ERROR=>'旧交易密码错误',
        self::APPLY_TOO_FAST => '重复申请过快',
        self::UPLOAD_INFO_TYPE_ERROR => '上传信息类型未定义',
        self::UPLOAD_INFO_DATA_ERROR => '上传信息数据类型有误',
        self::UPLOAD_DATA_FORMAT_ERROR => '上传信息数据格式有误',
        self::STR_CALLBACK_URL_ERROR => '链接格式有误',
        self::USER_REGISTER_PHONE_ERROR=>'您输入的手机号与注册手机号不一致',
        self::USER_REGISTER_INFO_ERROR=>'身份证号或者姓名错误',
        self::USER_RESET_PASSWORD=>'请先设置交易密码或者选择忘记密码',
        self::USER_PAY_PASSWORD_LEN_ERROR=>'交易密码长度只能是6位数',
        self::BANK_BIAND_INFO_ERROR=>'请先绑定银行卡',
        self::PAY_PASSWORD_NO_NUM=>'交易密码只能是数字',


        self::USER_CREATE_FAILED => '用户创建失败',
        self::USER_RELATION_EXIST => '用户已存在',
        self::USER_RELATION_SAVE_FAILED => '用户关联失败',
        self::USER_NOT_FOUND => '用户不存在',
        self::USER_FOUND_FAILED => '获取用户信息失败',
        self::USER_UNVERIFY => '用户未实名认证',
        self::USER_VERIFIED => '用户已实名认证',
        self::USER_NEED_VERIFY => '用户认证未完成',
        self::USER_VERIFY_FAILED => '实名认证失败',
        self::USER_ID_CARD_USED => '该身份证已被绑定，请换一张身份证',//可能换电话
        self::USER_SAVE_FAILED => '用户保存失败',
        self::USER_MARRIAGE_ERROR => '用户婚姻状况错误',
        self::USER_DEGREES_ERROR => '用户个人学历错误',
        self::USER_ZM_VERIFYED => '用户芝麻信用已授权',
        self::USER_CARD_IMG_FRONT_ERROR=>'身份证正面模糊或错误,请重新上传',
        self::USER_CARD_IMG_BACK_ERROR=>'身份证背面模糊或错误,请重新上传',
        self::USER_CARD_IMG_FACE_ERROR=>'人脸识别模糊或错误，请重新上传',
        self::USER_CARD_IMG_HEAD_EXIT=>'人脸识别照片已上传，请误重新上传',
        self::USER_CARD_IMG_FRONT_EXIT=>'身份证正面照已上传，请误重新上传',
        self::USER_CARD_IMG_BACK_EXIT=>'身份证背面照已上传，请误重新上传',
        self::USER_CARD_IMG_HEAD_NO_EXIT=>'人脸识别照片未上传',
        self::USER_CARD_IMG_FRONT_NO_EXIT=>'身份证正面照未上传',
        self::USER_CARD_IMG_BACK_NO_EXIT=>'身份证背面照未上传',
        self::USER_CARD_IMG_FACE_SAVE_ERROR=>'人脸识别保存失败',

        self::USER_CONTRACT_TYPE_ERROR => '直系亲属与本人关系错误',
        self::USER_CONTRACT_SPARE_TYPE_ERROR => '其他联系人与本人关系错误',
        self::USER_LIVETIME_ERROR => '居住时长有误',
        self::USER_COMPANYPERIOD_ERROR => '工作时长有误',
        self::USER_ID_CARD_FACE_SAVE_FAILED => '身份证正面照保存失败',
        self::USER_ID_CARD_BACK_SAVE_FAILED => '身份证反面照保存失败',
        self::USER_ID_NUMBER_CHECK_FAILED =>'身份证号码错误',
        self::USER_LOAN_CHECK_FAILED=>'有未完成的订单，不能重复申请',
        self::DATA_SAVE_FAILED => '数据保存失败',
        self::UPLOAD_FILE_TYPE_ERROR => '文件不符合要求',
        self::UPLOAD_FILE_ERROR => '不能上传该类材料',
        self::UPLOAD_FILE_FAILED => '文件上传失败',
        self::GET_LOAN_CREDIT=>'已提交授信请求,请勿重复提交',
        self::USER_CONTENT_EXIT=>'紧急联系人和常用联系人不能一样',

        self::USER_LOAN_GET_CREDIT_FAILED => '未提交申请授信',
        self::GET_YYS_STATUS_FAILED=>'提交超时,请重新输入服务密码',
        self::GET_YYS_INFO_STATUS_FAILED=>'信息获取失败，建议您一周后再尝试',
        self::GET_YYS_INFO_PHONE_EMPTY=>'手机号暂不支持',
        self::YSS_STATUS_CAPTCHA_ERROR => '动态密码错误',
        self::YSS_STATUS_WAIT_QUERY_PWD_RESULT=>'输入查询密码后等待结果',
        self::YSS_STATUS_INPUT_QUERY_PWD => '待输入查询密码',

        self::ORDER_UNPAY => '订单未打款',
        self::ORDER_NOT_FOUND => '订单不存在',
        self::ORDER_CREATE_FAILED => '订单创建失败',
        self::USER_LOAN_ORDER_CHECK =>'用户有借款订单不能换卡',
        self::USER_CARD_CHECK_ERROR =>'银行预留的信息与您输入的信息不匹配',
        self::CARD_ADD_PHONE_EMPTY => '手机号不能为空',
        self::CARD_ADD_CARD_NO_EMPTY => '卡号不能为空',
    ];

}