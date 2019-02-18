<?php
namespace common\exceptions;

class DataException extends UserExceptionExt {

    const PARAM_MISSING = 1001;
    const CHANNEL_MISSING = 1002;
    const SIGN_VALIDATE_FAILED = 1003;

    public static $ERROR_MSG = [
        self::PARAM_MISSING => '必要参数缺失',
        self::CHANNEL_MISSING => '渠道号未设置',
        self::SIGN_VALIDATE_FAILED => '签名验证失败',
    ];
}