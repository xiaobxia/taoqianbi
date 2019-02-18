<?php
namespace common\events;

use Exception;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\base\UserException;

/**
 * MessageEvent
 * 消息事件
 * ------------
 * @author Verdient。
 */
class MessageEvent extends Event
{
    /**
     * EVENT_SEND_MESSAGE
     * 发送消息事件
     * ------------------
     * @author Verdient。
     */
    const EVENT_SEND_MESSAGE = 'sendMessage';

    /**
     * @var $_subject
     * 主题
     * --------------
     * @author Verdient。
     */
    protected $_subject;

    /**
     * @var $_message
     * 消息内容
     * --------------
     * @author Verdient。
     */
    protected $_message = '';

    /**
     * @var $_receiver
     * 接收者
     * ---------------
     * @author Verdient。
     */
    protected $_receiver = [];

    /**
     * __construct(String $message = '', Array $receiver = [])
     * 构造函数
     * -------------------------------------------------------
     * @inheritdoc
     * -----------
     * @param String $message 消息内容
     * @param Array $receiver 接收者
     * ------------------------------
     * @author Verdient。
     */
    public function __construct($message = '', $receiver = []){
        parent::__construct();
        $this->message = $message;
        $this->receiver = $receiver;
    }

    /**
     * setMessage(String $message)
     * 设置消息内容
     * ---------------------------
     * @param Array $message 消息
     * -------------------------
     * @author Verdient。
     */
    public function setMessage($message){
        if(is_string($message)){
            $this->_message = $message;
        }else{
            throw new InvalidParamException('message must be a string, ' . gettype($message) . ' given');
        }
    }

    /**
     * getMessage()
     * 获取消息内容
     * -------------
     * @return Array
     * @author Verdient。
     */
    public function getMessage(){
        return $this->_message;
    }

    /**
     * setReceiver(Array $receiver)
     * 设置接收者
     * ----------------------------
     * @param Array $receiver 接收者
     * ----------------------------
     * @author Verdient。
     */
    public function setReceiver($receiver){
        if(is_array($receiver)){
            $this->_receiver = $receiver;
        }else{
            throw new InvalidParamException('receiver must be an array, ' . gettype($receiver) . ' given');
        }
    }

    /**
     * getReceiver()
     * 获取接收者
     * -------------
     * @return Array
     * @author Verdient。
     */
    public function getReceiver(){
        return $this->_receiver;
    }

    /**
     * setSubject(String $subject)
     * 设置主题
     * ---------------------------
     * @param Array $message 消息
     * -------------------------
     * @author Verdient。
     */
    public function setSubject($subject){
        if(is_string($subject)){
            $this->_subject = $subject;
        }else{
            throw new InvalidParamException('subject must be a string, ' . gettype($subject) . ' given');
        }
    }

    /**
     * getSubject()
     * 获取主题
     * ------------
     * @return Array
     * @author Verdient。
     */
    public function getSubject(){
        return $this->_subject;
    }
}