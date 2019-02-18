<?php
namespace common\events;

use Exception;
use yii\base\Event;
use yii\base\UserException;
use common\events\MessageEvent;

/**
 * ErrorEvent
 * 错误事件
 * ----------
 * @author Verdient。
 */
class ErrorEvent extends Event
{
    /**
     * @var $_exception
     * 异常对象
     * ----------------
     * @author Verdient。
     */
    protected $_exception;

    /**
     * __construct(Exception $exception)
     * 构造函数
     * ---------------------------------
     * @inheritdoc
     * -----------
     * @param Exception $exception 异常对象
     * -----------------------------------
     * @author Verdient。
     */
    public function __construct($exception){
        parent::__construct();
        $this->_exception = $exception;
    }

    /**
     * getException()
     * 获取异常对象
     * --------------
     * @return Exception
     * @author Verdient。
     */
    public function getException(){
        return $this->_exception;
    }

    /**
     * getIsUserError()
     * 获取是否是用户错误
     * ----------------
     * @return Boolean
     * @author Verdient。
     */
    public function getIsUserError(){
        return $this->_exception instanceof UserException;
    }

    /**
     * getIsSystemError()
     * 获取是否是系统错误
     * ------------------
     * @return Boolean
     * @author Verdient。
     */
    public function getIsSystemError(){
        return !$this->getIsUserError();
    }

    /**
     * getDescription()
     * 获取错误描述
     * ----------------
     * @return String
     * @author Verdient。
     */
    public function getDescription(){
        $exception = $this->exception;
        $description = $exception->getMessage() . ' in ' . str_replace(BASE_PATH, '', $exception->getFile()) . ':' . $exception->getLine();
        return $description;
    }
}