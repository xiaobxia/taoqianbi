<?php
namespace credit\components;
use Yii;

class View extends \common\components\View
{
    public function getSource(){
        return Yii::$app->controller->getSource();
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