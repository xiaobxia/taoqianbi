<?php

namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\MessageHelper;
use common\helpers\MailHelper;
use common\api\RedisQueue;

/**
 * 监控相关服务
 * @author VinkChen
 */
class MonitorService extends Component
{

    /**
     * 通知手机
     * @param array $phones 手机号
     * @param string $notify_content 通知内容
     * @return bool 至少发送一条短信成功 结果为true,否则为false
     */
    public function notifyPhones($phones, $notify_content) {
        $ret = false;
        if ($phones) {
            \yii::info("发送: {$notify_content}, 给手机号 ". implode(',', $phones));

            foreach($phones as $phone) {
                $send_ret = MessageHelper::sendSMS($phone, $notify_content);
                if (!$ret && $send_ret) {
                    $ret = $send_ret;
                }
            }
        }

        return $ret;
    }

    /**
     * 通知邮件
     * @param array $emails 邮箱地址
     * @param string $notify_content 通知内容
     * @return boolean 至少发送一封邮件成功 结果为true,否则为false
     */
    public function notifyEmails($emails, $notify_content) {
        Yii::info("发送内容 {$notify_content} 给邮箱 ". implode(',', $emails));
        $ret = false;
        if($emails) {
            foreach($emails as $email) {
                MailHelper::send($email, '监控报警！', $notify_content);
            }
        }
        return $ret;
    }
}