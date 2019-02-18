<?php
namespace newh5\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;

class PcSiteController extends BaseController
{
	public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['index','borrow-index','login','sxd-index','gjj-index','mhk-index','download'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }
    public $layout = 'pc-main';
    
    public function actionIndex(){
        $this->view->title = APP_NAMES;
        $this->view->keywords = APP_NAMES.',我要贷款,个人贷款,个人如何申请贷款,如何申请小额贷款,'.APP_NAMES;
        $this->view->icon = '';
        return $this->render('index');
    }

     public function actionDownload() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");

        $link = APP_DOWNLOAD_URL;
        if ( $iPod || $iPhone || $iPad ) {
            $link = APP_IOS_DOWNLOAD_URL;
        }

        header("Location: {$link}");
    }
}