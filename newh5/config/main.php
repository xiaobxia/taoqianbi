<?php
function isMobile(){
    $theusagt = @$_SERVER["HTTP_USER_AGENT"];
    $is_mobile = false;
    if(stripos($theusagt , "iPhone") !== false || stripos($theusagt , "iPod") !== false){
        $is_mobile = 'ios';
    }else if(stripos($theusagt , "Android") !== false){
        $is_mobile = 'android';
    }else if(stripos($theusagt , "Mobile") !== false){
        $is_mobile = true;
    }else if(stripos($theusagt , "Windows Phone") !== false){
        $is_mobile = true;
    }else if(stripos($theusagt , "xqb") !== false){
        $is_mobile = true;
    }else if(stripos($theusagt , "MicroMessenger") !== false){
        $is_mobile = true;
    }
    return $is_mobile;
}
return [
    'id' => 'app-newh5',
    'name' => APP_NAMES,
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'defaultRoute' => isMobile() ? 'm-site' : 'pc-site',
    'controllerNamespace' => 'newh5\controllers',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\LoanPerson',
            'loginUrl' => '',
            'on afterLogin' => function($event) {
                \Yii::$app->session->set(
                    \Yii::$app->session->keyPrefix . \common\components\Session::SKEY,
                    \Yii::$app->session->keyPrefix . \Yii::$app->user->identity->id
                );
            },
        ],
        'session' => [ // 使用redis做session
            'class' => 'common\components\Session',
            'redis' => 'redis',
            'name' => 'SESSIONID',
            'timeout' => 604800, //7 * 24 * 3600,
            'keyPrefix' => 'wzd_user:',
            'cookieParams' => [
                'lifetime' => 604800,
                'httponly' => true,
                'domain' => YII_ENV_PROD ? APP_DOMAIN : '',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\web\HttpException:*'],
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'categories' => ['application'],
                    'logFile' => "@runtime/logs/error.log",
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['application'],
                    'logFile' => "@runtime/logs/app.log",
                    'logVars' => [],
                ],
                // ------- 全局通用日志配置 begin -------
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\base\UserException', 'yii\web\HttpException*'],
                    'logCollection' => 'kd_newh5_error',
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['info'],
                    'categories' => ['application'],
                    'logCollection' => 'kd_newh5_info',
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => [ 'error', 'warning' ],
                    'logCollection' => 'user_credit_card_auth',
                    'logVars' => [],
                    'categories' => ['user_credit_card_auth*'],
                ],
                // ------- 全局通用日志配置 end -------
            ],
        ],
        'request' => [
            'class' => 'common\components\Request',
            'cookieValidationKey' => 'Wwpn7m5wzKDA2q141a6UVLKfK4lrfi-X',
        ],
        'view' => [
            'class' => 'newh5\components\View',
        ],
        'errorHandler' => [
            'class' => 'common\components\ErrorHandler',
        ],
        'image' => [
            'class' => 'yii\image\ImageDriver', //TODO 这个类没有这个类没有
            'driver' => 'GD',  //GD or Imagick
        ],
    ]
];
