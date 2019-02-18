<?php
namespace common\behaviors;

use yii\base\Behavior;
use yii\base\InvalidParamException;
use common\events\MessageEvent;

/**
 * SendMessageBehavior()
 * 发送消息行为
 * ---------------------
 * @author Verdient。
 */
class SendMessageBehavior extends Behavior
{
    /**
     * @var $receiver
     * 接收者
     * --------------
     * @author Verdient。
     */
    public $receiver;

    /**
     * _acquireReceiver(MessageEvent $event)
     * 取得接收者
     * ------------------------------------
     * @param MessageEvent $event 消息事件
     * ----------------------------------
     * @return Array
     * @author Verdient。
     */
    protected function _acquireReceiver(MessageEvent $event){
        $receiver = $event->receiver;
        if(empty($receiver)){
            $receiver = $this->receiver;
        }
        if(empty($receiver)){
            throw new InvalidParamException('receiver must be set');
        }
        return $receiver;
    }
}