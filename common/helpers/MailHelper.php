<?php
/**
 * Created by JohnnyLin.
 * User: JohnnyLin
 * Date: 2015/06/21
 */

namespace common\helpers;

use common\base\LogChannel;
use Yii;
use yii\base\Exception;
use common\api\RedisMQ;
use common\api\RedisQueue;

class MailHelper {
    const SUBJECT_YGB = APP_NAMES;
    const MODLE_CAPTCHA = 1;

    /**
     * 通过命令行发送邮件
     * @param $subject
     * @param $content
     * @param $to
     */
    public static function sendCmdMail($subject, $content, $to, $cc=[], $bcc=[]) {
        $to = is_array($to) ? implode(',', $to) : $to;

        $headers = [
            "MIME-Version: 1.0\r\n",
            "Content-type: text/html; charset=utf-8\r\n", #iso-8859-1
        ];

        $svr_name = isset($_ENV['HOSTNAME']) ? $_ENV['HOSTNAME'] : 'svr';
        $yii_env = YII_ENV;
        $headers[] = "From: {$svr_name}<{$svr_name}@{$yii_env}>\r\n";

        if (!empty($cc)) {
            $cc = is_array($cc) ? implode(',', $cc) : $cc;
            $headers[] = "Cc: $cc\r\n";
        }
        if (!empty($bcc)) {
            $bcc = is_array($bcc) ? implode(',', $bcc) : $bcc;
            $headers[] = "Bcc: $cc\r\n";
        }

        return mail($to, $subject, $content, implode('', $headers));
    }

    /**
     * @param $subject
     * @param $content
     * @param $to
     *
     * @return bool
     */
    public static function sendMail($subject, $content, $to) {
        $ret = \yii::$app->mailer->compose()
            ->setTo( $to )
            ->setSubject( $subject )
            ->setTextBody( $content )
            ->send();

        \yii::info( \sprintf('[%s]send_mail %s: %s',
            date('ymd H:i:s'), (is_array($to) ? implode(',', $to) : $to), ($ret ? '成功' : '失败')), LogChannel::MAIL_GENERAL);

        return $ret;
    }

    /**
     * 发送附件
     * @param        $content
     * @param        $to
     * @param        $attach
     * @param string $subject
     *
     * @return bool
     */
    public static function sendMailAttach($content, $to, $attach, $subject = APP_NAMES) {
        $ret = \Yii::$app->mailer->compose()
            ->setTo( $to )
            ->setSubject( $subject )
            ->attach( $attach )
            ->setTextBody( $content )
            ->send();

        \yii::info( \sprintf('[%s]send_mail_attach %s: %s',
            date('ymd H:i:s'), (is_array($to) ? implode(',', $to) : $to), ($ret ? '成功' : '失败')), LogChannel::MAIL_GENERAL);

        return $ret;
    }

    /**
     * 使用守护进程发邮件
     */
    public static function sendQueueMail($subject='', $content='', $to = '') {
        RedisMQ::push(RedisQueue::USER_QUEUE_MAIL, json_encode([
            'to' => $to,
            'subject' => $subject,
            'content' => $content,
        ], true));
    }

    /**
     * 直接发邮件
     * @param string $to 收件人
     * @param string $subject 标题
     * @param string $content 内容
     * @param bool   $retry 重试次数
     *
     * @return bool
     */
    public static function send($to = '', $subject="", $content="", $retry = 2) {
        $users = is_array($to) ? $to : explode(',', $to);
        $users_str = implode(',', $users);
        try {
            $send_time = date('ymd H:i');

            if (empty($to)) {
                \yii::warning(sprintf('[%s]send_mail_failed: %s:%s', $send_time, '空', $subject), LogChannel::MAIL_GENERAL);
                return false;
            }

            $messages = [];
            foreach ($users as $key => $user) {
                $messages[] = \yii::$app->mailer->compose()
                                ->setSubject( $subject )
                                ->setHtmlBody( $content )
                                ->setTo( trim($user) );
            }

            $ret = \yii::$app->mailer->sendMultiple($messages);
            if ($ret != count($users)) {
                while ($retry > 0) {
                    $ret = self::send($to, $subject, $content, false);
                    if ($ret > 0) {
                        break;
                    }

                    $retry --;
                }
            }

            if ($ret) {
                \yii::info(sprintf('[%s]send_mail_success: %s:%s', $send_time, $users_str, $subject), LogChannel::MAIL_GENERAL);
                return true;
            }

            \yii::warning(sprintf('[%s]send_mail_failed: %s:%s', $send_time, $users_str, $subject), LogChannel::MAIL_GENERAL);
            return false;
        }
        catch (\Exception $e) {
            if (YII_ENV_DEV && PHP_SAPI == 'cli') {
                var_dump($e);
                return false;
            }

            \yii::warning(sprintf('[%s]send_mail_exp %s:%s, exp:%s', $send_time, $users_str, $subject, $e), LogChannel::MAIL_GENERAL);
            return false;
        }
    }

    /**
     * 邮件群发
     */
    public static function sendMass($to, $subject='', $content='', $retry = 2) {
        try {
            $send_time = date('ymd H:i');

            if (empty($to)) {
                \yii::warning(sprintf('[%s]send_mass_mail_failed: %s:%s', $send_time, '空', $subject), LogChannel::MAIL_GENERAL);
                return false;
            }

            $ret = Yii::$app->mailer->compose()
                ->setSubject( $subject )
                ->setHtmlBody( $content )
                ->setTo($to)
                ->send();
            if (! $ret) {
                while($retry) {
                    $ret = self::sendMass($to, $subject, $content, false); //第二次尝试
                    if ($ret) {
                        break;
                    }

                    $retry --;
                }
            }

            $_to = is_string($to) ? $to : json_encode($to);
            if ($ret) {
                \yii::info(sprintf('[%s]send_mass_mail_success: %s:%s', $send_time, $_to, $subject), LogChannel::MAIL_GENERAL);
                return true;
            }

            \yii::warning(sprintf('[%s]send_mass_mail_failed: %s:%s', $send_time, $_to, $subject), LogChannel::MAIL_GENERAL);
            return false;
        }
        catch (\Exception $e) {
            if (YII_ENV_DEV && PHP_SAPI == 'cli') {
                var_dump($e);
                return false;
            }
            \yii::warning(sprintf('[%s]send_mass_mail_exp %s:%s, exp:%s', $send_time, $_to, $subject, $e), LogChannel::MAIL_GENERAL);
            return false;
        }
    }

}

