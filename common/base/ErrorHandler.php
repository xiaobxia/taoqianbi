<?php
namespace common\base;

use Yii;
use yii\web\ErrorHandler AS BaseErrorHandler;
use common\behaviors\SendEmailBehavior;
use common\behaviors\SendSMSBehavior;
use common\events\ErrorEvent;
use common\events\MessageEvent;

/**
 * ErrorHandler
 * 错误处理
 * ------------
 * @author Verdient。
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * @var $sendMessage
     * 是否发送错误信息
     * -----------------
     * @author Verdient。
     */
    public $sendMessage = true;

    /**
     * @var $messageSubject
     * 消息主题
     * --------------------
     * @author Verdient。
     */
    public $messageSubject = 'An abnormal situation has occurred';

    /**
     * @var $receiver
     * 接收者
     * --------------
     * @author Verdient。
     */
    public $receiver = [];

    /**
     * behaviors()
     * 添加行为
     * -----------
     * @inheritdoc
     * -----------
     * @return Array
     * @author Verdient。
     */
    public function behaviors(){
        return [
            // 'sendSMSBehavior' => [
            //     'class' => SendSMSBehavior::className(),
            //     'receiver' => [
            //         '15757116316'
            //     ]
            // ],
            'sendEmailBehavior' => [
                'class' => SendEmailBehavior::className(),
                     'receiver' => [
                         '17682449388'
                     ]
            ],
        ];
    }

    /**
     * renderException(Exception $exception)
     * 渲染异常
     * -------------------------------------
     * @inheritdoc
     * -----------
     * @param Exception $exception 异常对象
     * -----------------------------------
     * @author Verdient。
     */
    protected function renderException($exception){
        $errorEvent = new ErrorEvent($exception);
        if($this->sendMessage === true && $errorEvent->isSystemError){
            $messageEvent = new MessageEvent($errorEvent->description);
            $messageEvent->subject = $this->messageSubject;
            $messageEvent->receiver = $this->receiver;
            $this->trigger(MessageEvent::EVENT_SEND_MESSAGE, $messageEvent);
        }
        parent::renderException($exception);
    }
}