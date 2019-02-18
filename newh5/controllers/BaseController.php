<?php
namespace newh5\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\Html;

/**
 * Base controller
 *
 * @property \yii\web\Request $request The request component.
 * @property \yii\web\Response $response The response component.
 */
abstract class BaseController extends \common\components\BaseController
{
    public $enableCsrfValidation = false;

    public function init()
    {
        parent::init();
        // other init
        if ($this->request->get('callback')) { // 参数有callback的话则是jsonp
            $this->getResponse()->format = Response::FORMAT_JSONP;
        }
        //指定跳app登录页
        if($this->isFromApp()){
            Yii::$app->user->loginUrl = 'koudaikj://app.launch/login/applogin';
        }
        if(YII_ENV_DEV){
            // $user = \common\models\LoanPerson::findByPhone('18516724450');
            // $user = \common\models\LoanPerson::findByPhone('18149791478');
            // $user = \common\models\LoanPerson::findByPhone('18817384484');
            // $user = \common\models\LoanPerson::findByPhone('18625990211');
            // \Yii::$app->user->login($user);
        }
    }

    public function beforeAction($action)
    {
        // 用于微信的openid登录
        if ($this->getRequest()->get('contact_id') && Yii::$app->user->getIsGuest()) {
            Yii::$app->user->loginByAccessToken(trim($this->getRequest()->get('contact_id')));
        }
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        if ($this->request->get('callback')) {
            // 由于部分Action可能存在中间设置成FORMAT_JSON，比如CaptchaAction，所以在有callback的时候再强制设置一遍FORMAT_JSONP
            $this->getResponse()->format = Response::FORMAT_JSONP;
            // jsonp返回数据特殊处理
            $callback = Html::encode($this->request->get('callback'));
            $result = [
                'data' => $result,
                'callback' => $callback,
            ];
        }
        return $result;
    }
}
