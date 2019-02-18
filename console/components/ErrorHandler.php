<?php
namespace console\components;

use Yii;
use yii\base\UserException;
use yii\base\Exception;
use yii\base\ErrorException;

use common\helpers\MessageHelper;
use common\base\LogChannel;
use common\helpers\ToolsUtil;

class ErrorHandler extends \yii\console\ErrorHandler {

    /**
     * Logs the given exception
     * @param \Exception $exception the exception to be logged
     */
    public function logException($exception) {
        parent::logException($exception);

        // 非用户级异常短信告警
        if (YII_ENV_PROD && (!$exception instanceof UserException)) {
            $name = ($exception instanceof Exception || $exception instanceof ErrorException)
                ? $exception->getName() : 'Exception';
            $server_ip = ToolsUtil::getLocalIp();
            if (YII_ENV_DEV && $server_ip == '127.0.0.1') {
                return ;
            }

            $client_ip = ToolsUtil::getIp();
            \yii::warning(sprintf("[%s][%s]%s", $name, $server_ip, $exception), LogChannel::SYSTEM_SMS_WARNING);
            $key =  'Exception_' . $name . '_' . $exception->getCode();
            if (!Yii::$app->cache->get($key)) {
                $message = sprintf('[%s][%s][%s]异常:%s. %s in %s:%s',
                    \yii::$app->id, $server_ip, $client_ip, $key,
                    $exception->getMessage(), $exception->getFile(), $exception->getLine());
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警-余晨
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警-黄文派
                \yii::$app->cache->set($key, 1, 300);
            }
        }
        else if (!$exception instanceof UserException) {
            $name = ($exception instanceof Exception || $exception instanceof ErrorException)
                ? $exception->getName() : 'Unknown';
            $server_ip = ToolsUtil::getLocalIp();
            if (YII_ENV_DEV && $server_ip == '127.0.0.1') {
                return ;
            }

            $client_ip = ToolsUtil::getIp();
            \yii::warning(sprintf("[%s][%s]%s", $name, $server_ip, $exception), LogChannel::SYSTEM_SMS_WARNING);
            $key =  'Exception_' . $name . '_' . $exception->getCode();
            if (!Yii::$app->cache->get($key)) {
                $message = sprintf('[%s][%s][%s]异常:%s. %s in %s:%s',
                    \yii::$app->id, $server_ip, $client_ip, $key,
                    $exception->getMessage(), $exception->getFile(), $exception->getLine());
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警-余晨
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警-黄文派

                \yii::$app->cache->set($key, 1, 300);
            }
        }
    }
}
