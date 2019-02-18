<?php
namespace common\behaviors;

use yii\base\InvalidConfigException;
use common\events\MessageEvent;

/**
 * SendSMSBehavior
 * 发送短信行为
 * ---------------
 * @author Verdient。
 */
class SendSMSBehavior extends SendMessageBehavior
{
    /**
     * SMS
     * 短信组件
     * -------
     * @author Verdient。
     */
    public $SMS;

    /**
     * events()
     * 绑定事件
     * -------
     * @return Array
     * @author Verdient。
     */
    public function events(){
        return [
            MessageEvent::EVENT_SEND_MESSAGE => 'sendMessage',
        ];
    }

    /**
     * sendMessage(MessageEvent $event)
     * 发送信息
     * --------------------------------
     * @param MessageEvent $event 消息事件
     * ----------------------------------
     * @author Verdient。
     */
    public function sendMessage(MessageEvent $event){
        if(!$this->SMS){
            throw new InvalidConfigException('SMS must be set');
        }
        $this->SMS->batchSend($event->message, $this->_acquireReceiver($event));
    }
}