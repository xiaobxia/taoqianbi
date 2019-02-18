<?php
return [
    'id' => 'app-mobile',
    'name' => APP_NAMES,
    'basePath' => dirname(__DIR__),

    'controllerNamespace' => 'mobile\controllers',
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
            'loginUrl' => ['building/login'],
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
            'keyPrefix' => 'wzd_user:',
            'timeout' => 604800, //7 * 24 * 3600,
            'cookieParams' => [
                'lifetime' => 604800,
                'httponly' => true,
//                'domain' => YII_ENV_PROD ? APP_DOMAIN : '',
                'domain' => '',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                // ------- 全局通用日志配置 begin -------
                // UserException太多，先不打到mongodb，后面再看，所有错误打文件日志也先保留
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\base\UserException','yii\web\HttpException:403'],
                    'logCollection' => 'kdkj_mobile_error',
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => (YII_ENV==='prod' ? [] : ['info']) + ['error', 'warning'],
                    'except' => ['yii\base\UserException','yii\web\HttpException:403'],
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['info', 'trace'],
                    'categories' => ['application'],
                    'logCollection' => 'kdkj_mobile_info',
                    'logVars' => [],
                ],
                // ------- 全局通用日志配置 end -------
            ]
        ],
        'request' => [
            'class' => 'common\components\Request',
            'cookieValidationKey' => 'Wwpn7m5wzKDA2q141a6UVLKfK4lrfi-X',
        ],
        'view' => [
            'class' => 'mobile\components\View',
        ],
        // 额度渠道服务组件
        'creditChannelService' => [
            'class' => 'common\services\UserCreditChannelService',
        ],
        'errorHandler' => [
            'class' => 'common\components\ErrorHandler',
        ],
    ]
];
