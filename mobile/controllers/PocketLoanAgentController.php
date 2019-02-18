<?php
namespace mobile\controllers;

use common\models\LoanPerson;
use Yii;
use yii\web\Response;
use common\helpers\StringHelper;
use common\models\User;
use common\models\UserCaptcha;
use common\exceptions\UserExceptionExt;
use yii\filters\AccessControl;
use common\services\UserService;
use common\models\UserLoginLog;


// 口袋快借经纪人
class PocketLoanAgentController extends BaseController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['index'],
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
        $this->view->title = '口袋快借-好房贷';
        return $this->render('loans-portal');
    }

    //生成链接
    public function actionGenerateLink()
    {
        $this->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->identity;
        $link = "http://m.koudailc.com/quick-loan/page-haofang-info?title=好房贷&source=2&invite_id=".$user->id;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $link
        ];
    }

    //添加名字
    public function actionAddName()
    {
        $this->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->identity;
        $name = $this->request->post("username");
        if(empty($name) || ! $this->checkName($name)) {
            return UserExceptionExt::throwCodeAndMsgExt('请输入正确的姓名');
        }
        $info = LoanPerson::find()->where(["id" => $user->id])->one();
        $info->name = $name;
        if($info->save()) {
            return [
                'code'=>0,
                'message'=>'success',
                'data'=>$name,
            ];
        } else{
            return UserExceptionExt::throwCodeAndMsgExt('保存失败,请稍后再试');
        }
    }

    //正则验证姓名
    private function checkName($name)
    {
        $preg = "/^[\x80-\xff]{4,30}$/";
        if(!preg_match($preg,$name)) {
            return false;
        }
        return true;
    }


}