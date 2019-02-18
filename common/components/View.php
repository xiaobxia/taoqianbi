<?php
namespace common\components;

use Yii;

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
    public $isSkipID;
    // public $other;

    public function init() {
        parent::init();
        $this->baseUrl = Yii::$app->getRequest()->getBaseUrl();
        $this->hostInfo = Yii::$app->getRequest()->getHostInfo();
        $this->absBaseUrl = $this->hostInfo . $this->baseUrl;
        $this->userName = Yii::$app->user->identity['username'];
        // $this->other = '';
    }
    public function isFromApp(){
        return method_exists(Yii::$app->controller, 'isFromApp') ? Yii::$app->controller->isFromApp() : false;
    }

    public function isFromWeichat(){
        return method_exists(Yii::$app->controller, 'isFromWeichat') ? Yii::$app->controller->isFromWeichat() : false;
    }

    public function staticUrl($path,$type=''){
        return method_exists(Yii::$app->controller, 'staticUrl') ? Yii::$app->controller->staticUrl($path,$type) : '';
    }

    protected function findViewFile($view, $context = null) {
        return parent::findViewFile($view,$context);
    }
}
