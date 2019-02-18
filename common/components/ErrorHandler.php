<?php
namespace common\components;

use common\base\LogChannel;
use common\helpers\ToolsUtil;
use Yii;
use yii\base\UserException;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\ForbiddenHttpException;
use yii\helpers\Html;
use yii\helpers\Json;

use common\helpers\GlobalHelper;
use common\helpers\MessageHelper;

class ErrorHandler extends \yii\web\ErrorHandler {
    /**
     * @see \yii\web\ErrorHandler::renderException()
     */
    public function renderException($exception) {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
        } else {
            $response = new Response();
        }

        $useErrorView = $response->format === Response::FORMAT_HTML && (!YII_DEBUG || $exception instanceof UserException);

        if ($useErrorView && $this->errorAction !== null) {
            $result = Yii::$app->runAction($this->errorAction);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
        }
        elseif ($response->format === Response::FORMAT_HTML) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || YII_ENV_TEST) {
                // AJAX request
                $response->data = '<pre>' . $this->htmlEncode($this->convertExceptionToString($exception)) . '</pre>';
            } else {
                // if there is an error during error rendering it's useful to
                // display PHP error in debug mode instead of a blank screen
                if (YII_DEBUG) {
                    \ini_set('display_errors', true);
                }

                if (YII_ENV_PROD){
                    $file = '@common/components/error/error_404.php';
                }else{
                    $file = $useErrorView ? $this->errorView : $this->exceptionView;
                }

                $response->data = $this->renderFile($file, [
                    'exception' => $exception,
                ]);

            }
        } else {
            $response->data = $this->convertExceptionToArray($exception);
        }

        // http状态码统一用200，避免客户端处理麻烦，错误内容在返回内容的code中体现
        // 后面看是否需要对网站做处理
        $response->setStatusCode(200);

        $response->send();
    }

    /**
     * 重写抛出的异常数据的数据结构
     * @see \yii\web\ErrorHandler::convertExceptionToArray()
     */
    public function convertExceptionToArray($exception)
    {
        // 非debug模式下的非用户级的异常将模糊提示，避免暴露服务端信息
        if (!YII_DEBUG && !$exception instanceof UserException && !$exception instanceof HttpException) {
            $exception = new HttpException(500, '服务器繁忙，请稍后再试！');
        }

        if ($exception instanceof ForbiddenHttpException) { // 未登录code为-2
            $code = -2;
            $message = "登录态失效";
        }
        else if ($exception->getCode() == 0) { // 特殊处理，所有默认exception为0时，返回给客户端都置为-1
            $code = -1;
            $message = $exception->getMessage() ?: '系统异常，请稍后重试';
        }
        else {
            $code = $exception->getCode();
            $message = $exception->getMessage();
        }

        $array = [
            'message' => $message,
            'data' => [],
            'code' => $code,
        ];

        if (YII_DEBUG) { // debug模式下，多一些错误信息
            $array['type'] = get_class($exception);
            if (!$exception instanceof UserException) {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
                $array['stack-trace'] = explode("\n", $exception->getTraceAsString());
                if ($exception instanceof \yii\db\Exception) {
                    $array['error-info'] = $exception->errorInfo;
                }
            }
            else
            {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
            }
        }
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = $this->convertExceptionToArray($prev);
        }

        // 参数fmt不为空则是iframe post，一般用于跨域post
        if (\yii::$app->request->get('fmt')) {
            $json_str = Json::encode($array);
            $domain = GlobalHelper::getDomain();
            echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-cn" lang="zh-cn">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<meta http-equiv="Content-Language" content="utf8" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<title>POST - KD</title>
</head>
<body>
<span id = 'json_data' style=display:none>{$json_str}</span>
<script type="text/javascript">
document.domain = '{$domain}';
<!--
frameElement.callback({$json_str});
//-->
</script>
</body>
</html>
EOT;
            \yii::$app->end();
        }

        // 如果是jsonp
        if (Yii::$app->getResponse()->format === Response::FORMAT_JSONP) {
            $array = [
                'data' => $array,
                'callback' => Html::encode(\yii::$app->request->get('callback')),
            ];
        }
        if (-2 == $code) {
            $array = [
                'code'=>-2,
                'message'=>'登录态失效',
                'data'=>[ 'item'=>[] ],
            ];
        }

        return $array;
    }

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
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
            $client_ip = ToolsUtil::getIp();
            \yii::warning(sprintf("[%s][%s]%s", $name, $server_ip, $exception), LogChannel::SYSTEM_SMS_WARNING);

            $key =  'Exception_' . $name . '_' . $exception->getCode();
            if (!Yii::$app->cache->get($key)) {
                $message = sprintf('[%s][%s][%s]异常:%s. %s in %s:%s',
                    \yii::$app->id, $server_ip, $client_ip, $key,
                    $exception->getMessage(), $exception->getFile(), $exception->getLine());
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警
//                MessageHelper::sendInternalSms(NOTICE_MOBILE2, $message); #异常短信报警
                \yii::$app->cache->set($key, 1, 300);
            }
        }
        else if (!$exception instanceof UserException) {
            $name = ($exception instanceof Exception || $exception instanceof ErrorException)
                ? $exception->getName() : 'Unknown';
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
            $client_ip = ToolsUtil::getIp();
            \yii::warning(sprintf("[%s][%s]%s", $name, $server_ip, $exception), LogChannel::SYSTEM_SMS_WARNING);

            $key =  'Exception_' . $name . '_' . $exception->getCode();
            if (!Yii::$app->cache->get($key)) {
                $message = sprintf('[%s][%s][%s]异常:%s. %s in %s:%s',
                    \yii::$app->id, $server_ip, $client_ip, $key,
                    $exception->getMessage(), $exception->getFile(), $exception->getLine());
                MessageHelper::sendInternalSms(NOTICE_MOBILE, $message); #异常短信报警-余晨
//                MessageHelper::sendInternalSms(NOTICE_MOBILE2, $message); #异常短信报警-黄文派
                \yii::$app->cache->set($key, 1, 300);
            }
        }
    }
}
