<?php
namespace credit\controllers;

use common\models\FinancialLog;
use common\models\UserLoginUploadLog;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use common\helpers\GlobalHelper;
use common\models\WeixinUser;

/**
 * Base controller
 *
 * @property \yii\web\Request $request The request component.
 * @property \yii\web\Response $response The response component.
 * @property common\models\Client $client The Client model.
 */
abstract class BaseController extends \common\components\BaseController
{
	// 由于都是api接口方式，所以不启用csrf验证
	public $enableCsrfValidation = false;
	public $sub_order_type = '';
	public $from_app = '';
	public function init()
	{
		parent::init();
		if ($this->request->get('callback')) { // 参数有callback的话则是jsonp
			$this->getResponse()->format = Response::FORMAT_JSONP;
		} else {
			$this->getResponse()->format = Response::FORMAT_JSON;
		}
	}

	public function beforeAction($action)
	{
	    $this->sub_order_type = \common\helpers\Util::t('sub_order_type');
	    $this->from_app = \common\helpers\Util::t('from_app');
		// 用于微信的openid登录
		if ($this->getRequest()->get('contact_id') && Yii::$app->user->getIsGuest()) {
			Yii::$app->user->loginByAccessToken(trim($this->getRequest()->get('contact_id')));
		}
		//日志记录开始
		FinancialLog::begin($action);

		return parent::beforeAction($action);
	}

	public function afterAction($action, $result)
	{
		$result = parent::afterAction($action, $result);

		//日志记录结束
		FinancialLog::end($result);

		// 调试情况下返回性能数据
		if (YII_DEBUG && YII_ENV_DEV && $this->getResponse()->format == Response::FORMAT_JSON) {
		    $messages = \yii\log\Target::filterMessages(Yii::getLogger()->messages, yii\log\Logger::LEVEL_PROFILE, ['yii\db\Command::query', 'yii\db\Command::execute']);
		    $timings = Yii::getLogger()->calculateTimings($messages);
		    $sql_count = count($timings);
            $sql_time_temp = 0;
            foreach ($timings as $timing) {
                $sql_time_temp += $timing['duration'];
            }
            $sql_time = number_format($sql_time_temp * 1000) . 'ms';

            $result['debug_info'] = [
                'sql_count' => strval($sql_count),
                'sql_time' => $sql_time,
            ];

        }

		if ($this->request->get('fmt')) {
			// 参数fmt不为空则是iframe post，一般用于跨域post
			$json_str = Json::encode($result);
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
			$this->getResponse()->format = Response::FORMAT_HTML;
			Yii::$app->end();
		} else if ($this->getResponse()->format == Response::FORMAT_JSONP) {
			// 特殊处理：如果是验证码，由于已经encode过了，所以需要先decode成原始数据
			if ($action->id == 'captcha') {
				$result = json_decode($result);
			}
			// jsonp返回数据特殊处理
			$callback = Html::encode($this->request->get('callback'));
			$result = [
				'data' => $result,
				'callback' => $callback,
			];
		}

		return $result;
	}

	public function autoWeChatLogin($params=null){
	    if($params === null){
	        $params = $this->params();
	    }
	    $openid = $this->getCookie('openid');
	    if($this->isFromWeichat() && (!$openid || Yii::$app->user->getIsGuest())){
	        $weixinUser = $openid ? WeixinUser::getUserInfo($openid) : null;
	        if($this->isFromWeichat() && !$weixinUser){
	            $this->response->getCookies()->remove('openid');
	            $params = $params ? $params : [];
	            array_unshift($params, $this->id.'/'.$this->action->id);
	            $backUrl = Url::toRoute($params, true);
	            $url = Url::toRoute(['wx/user-auth-template','redirectUrl'=>urlencode($backUrl)],true);
	            return $this->redirect($url);
	        }
	        if($weixinUser){
	            $user = $weixinUser->getKdUser();
	            if($user){
	                Yii::$app->user->login($user);
	            }
	        }
	    }
	}
}