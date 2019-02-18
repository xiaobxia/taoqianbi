<?php
/**
 * @doc: https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push/
 */
namespace common\exceptions;

class JPushException extends \Exception {

    const SYSTEM_ERROR = 1000;
    const ONLY_SUPPORT_MTEHOD = 1001;
    const PARAMS_MISSING = 1002;
    const PARAMS_ERROR = 1003;
    const VERIFICATION_FAILED = 1004;
    const MESSAGE_LENGTH_TOO_BIG = 1005;
    const APP_KEY_PARAMS_ERROR = 1008;
    const PUSH_OBJECT_NO_SUPPORT_KEY = 1009;
    const NO_PUSH_TARGET = 1011;
    const ONLY_SUPPORT_HTTPS = 1020;
    const SERVICE_TIMEOUT = 1030;
    const FREQUENCY_BEYOND_LIMIT = 2002;
    const RESTRICT_CALLS_API = 2003;
    const NO_PERMISSION = 2004;

    //自定义
    const NO_EXIST_CHINNAL = 100001;

    public static $ERROR_MSG = [
        self::SYSTEM_ERROR => '系统内部错误',
	    self::ONLY_SUPPORT_MTEHOD => '只支持 HTTP Post 方法',
	    self::PARAMS_MISSING => '缺少了必须的参数',
	    self::PARAMS_ERROR => '参数值不合法',
	    self::VERIFICATION_FAILED => '验证失败',
	    self::MESSAGE_LENGTH_TOO_BIG => '消息体太大',
	    self::APP_KEY_PARAMS_ERROR => 'app_key参数非法',
	    self::PUSH_OBJECT_NO_SUPPORT_KEY => '推送对象中有不支持的key',
	    self::NO_PUSH_TARGET => '没有满足条件的推送目标',
	    self::ONLY_SUPPORT_HTTPS => '只支持 HTTPS 请求',
	    self::SERVICE_TIMEOUT => '内部服务超时',
	    self::FREQUENCY_BEYOND_LIMIT => 'API调用频率超出该应用的限制',
	    self::RESTRICT_CALLS_API => '该应用appkey已被限制调用 API',
        self::NO_PERMISSION => '无权限执行当前操作',

	    self::NO_EXIST_CHINNAL => '该通道并不存在!',

    ];
}
