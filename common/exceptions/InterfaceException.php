<?php

namespace common\exceptions;

/**
 * 融360，借了吗等 接口异常 
 */
class InterfaceException extends \Exception {
    
    /**
     * 自定义错误 用于返回易于识别的字符串型错误码 如 HTTP_ERROR_500 HTTP_ERROR_404 INVALID_PARAMS 等;
     * @var string 
     */
    public $customCode = '';
    
    public function __construct($message = "", $code = 0, Throwable $previous = null, $customCode='UNKNOW') {
        parent::__construct($message, $code, $previous);
        $this->customCode = $customCode;
    }
    
    
    /**
     * 抛出一个带自定义错误码的异常
     * @param string $message
     * @param string $customCode
     * @param integer $code
     * @param \Throwable $previous
     * @throws \Exception
     */
    public static function throwException($message, $customCode,  $code = 0, \Throwable $previous = null) {
        throw new InterfaceException($message, $code, $previous, $customCode);
    }
    
    /**
     * 获取自定义的错误码
     * @return sring
     */
    public function getCustomCode() {
        return $this->customCode;
    }
}

