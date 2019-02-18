<?php
namespace newh5\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\Html;
use common\models\LoanPerson;
use newh5\components\ApiUrl;

class MSiteController extends BaseController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['index','login'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }
    
    public function actionIndex(){
        return $this->redirect(ApiUrl::toRouteH5Mobile(['mobile/'],true));
    }
    /**
    * 处理'登录','注册','找回密码'
    **/
    public function actionLogin(){
        $source_url = urlencode($this->request->get('source_url',Yii::$app->getHomeUrl()));
        if(!$this->request->get('source_url')){
            $source_url = urlencode($this->request->post('source_url',Yii::$app->getHomeUrl()));            
        }
        $type = intval($this->request->post('type',0)); // 设置默认标题和h5内容
        if( $type != 3 && $type != 4 && !empty(Yii::$app->user->identity) ){
            return $this->redirect(urldecode($source_url));
        }
        $view_arr = ['verify-user','login','regsiter','regsiter','regsiter'];
        $view_title_arr = ['输入手机号','登录','注册','找回登录密码','找回交易密码'];
        $retdata = [];
        $phone = Html::encode($this->request->post('phone'));
        if($phone){
            $user = LoanPerson::findByPhone($phone);
            if(!$type) $type = $user ? 1 : 2;
        }
        $view = isset($view_arr[$type]) ? $view_arr[$type] : $view_arr[0];
        $this->view->title = isset($view_title_arr[$type]) ? $view_title_arr[$type] : $view_title_arr[0];
        $retdata = ['redirect_url'=>$source_url,'phone'=>$phone,'type'=>$type];
        return $this->render($view,$retdata);
    }
    
    public function actionSetting(){
        $this->view->title = '设置';
        return $this->render('setting');
    }
    
    /**
    * 处理'修改登录密码','修改交易密码'
    **/
    public function actionChangePwd(){
        $type = intval($this->request->get('type',0)); // 设置默认标题和h5内容
        $view_arr = ['change-loginpwd','change-paypassword'];
        $view_title_arr = ['修改登录密码','修改交易密码'];
        $retdata = [];
        $view = isset($view_arr[$type]) ? $view_arr[$type] : $view_arr[0];
        $this->view->title = isset($view_title_arr[$type]) ? $view_title_arr[$type] : $view_title_arr[0];
        return $this->render($view,$retdata);
    }
}