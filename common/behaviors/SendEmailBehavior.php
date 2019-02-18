<?php
namespace common\behaviors;

use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\validators\EmailValidator;
use common\components\Email;
use common\events\MessageEvent;

/**
 * SendEmailBehavior
 * 发送电子邮件行为
 * -----------------
 * @author Verdient。
 */
class SendEmailBehavior extends SendMessageBehavior
{
    /**
     * @var $mailer
     * 电子邮件组件
     * ------------
     * @author Verdient。
     */
    public $mailer = 'email';

    /**
     * init()
     * 初始化
     * ------
     * @inheritdoc
     * -----------
     * @author Verdient。
     */
    public function init(){
        parent::init();
        if(!$this->mailer){
            throw new InvalidConfigException('mailer must be set');
        }
        $this->mailer = Instance::ensure($this->mailer, Email::className());
    }

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
        $receiver = $this->_acquireReceiver($event);
        if(!empty($receiver)){
            $this->mailer->sendText($receiver, $event->message, [
                'subject' => $event->subject
            ]);
        }
    }

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
        $receiver = parent::_acquireReceiver($event);
        $validator = new EmailValidator();
        foreach($receiver as $key => $value){
            if(!$validator->validate($value)){
                unset($receiver[$key]);
            }
        }
        return $receiver;
    }
}