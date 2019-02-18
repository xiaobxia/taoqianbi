<?php
namespace newh5\components;

use Yii;
use yii\helpers\Url;

class View extends \yii\web\View
{
    /**
     * 入口文件，不包括域名的目录
     */
    public $baseUrl;

    /**
     * 域名
     */
    public $hostInfo;

    /**
     * $hostInfo + $baseUrl
     */
    public $absBaseUrl;

    /**

     * other
     */
    public $userName;
    public $keywords;
    public $description;
    public $shareLogo;
    public $icon;
    public $showDownload;
    public $source_id;
    public $source_tag;
    public $source_app;
    // public $other;

    public function init()
    {
        parent::init();
        $this->baseUrl = Yii::$app->getRequest()->getBaseUrl();
        $this->hostInfo = Yii::$app->getRequest()->getHostInfo();
        $this->absBaseUrl = $this->hostInfo . $this->baseUrl;
        $this->userName = Yii::$app->user->identity['username'];
        $this->source_id = 21;
        $this->source_app = 'xybt';

        // $this->other = '';
    }
    public function getSource(){
        return Yii::$app->controller->getSource();
    }
    public function isFromApp(){
        return Yii::$app->controller->isFromXjk();
    }

    public function isFromWeichat(){
        return Yii::$app->controller->isFromWeichat();
    }
    /**
     * 皮肤资源路径
     */
    public function source_url(){
        $baseUrl = Yii::$app->request->getHostInfo() . Yii::$app->request->getBaseUrl();
        return $baseUrl;
    }

    public function actionSetHeaderUrl(){
        //获取header参数
        $header = Yii::$app->request->headers;
        $appVersion = $header->get('appVersion');
        $appMarket = $header->get('appMarket');
        $str = [];
        if(isset($appVersion)){
            $str['appVersion'] = $appVersion;
        }
        if(isset($appMarket)){
            $str['appMarket'] = $appMarket;
        }
        return $str;
    }
}
