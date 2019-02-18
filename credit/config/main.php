<?php
$_month = sprintf('%s_%s', date('ym'), intval(date('d') / 10));
$_info_except = ['yii\web\Session*']; #'yii\db\*', 'yii\mongodb\*',
return [
    'id' => 'app-credit',
    'name' => APP_NAMES,
    'basePath' => dirname(__DIR__),

    'controllerNamespace' => 'credit\controllers',
    'defaultRoute' => 'installment-shop',

    // 添加模块v1和v2，分别表示不同的版本
    'modules' => [
        'v2' => [
            'class' => 'credit\modules\v2\Module',
        ],
    ],

    'components' => [
        // 额度渠道服务组件
        'creditChannelService' => [
            'class' => 'common\services\UserCreditChannelService',
        ],
        'errorHandler' => [
            'class' => 'common\components\ErrorHandler',
        ],
        'request' => [
            'class' => 'common\components\Request',
            'cookieValidationKey' => 'zh$@tV!H5BVXWwKDEee7u16uSqFHkoZ^',
        ],
        'view' => [
            'class' => 'credit\components\View',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            // 'enableStrictParsing' => true,
            'rules' => [
                // '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                // '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<ns:\w+>/<controller:[\w|-]+>/<action:\w+>' => '<ns>/<controller>/<action>',
            ]
        ],
        'user' => [
            'identityClass' => 'common\models\LoanPerson',
            'enableAutoLogin' => true, // 允许使用auth_key来自动登录
            'loginUrl' => null, // 设为null避免跳转
//            'on afterLogin' => function($event) {
//                \Yii::$app->session->set(
//                    \Yii::$app->session->keyPrefix . \common\components\Session::SKEY,
//                    \Yii::$app->session->keyPrefix . \Yii::$app->user->identity->id
//                );
//            },
        ],
        'session' => [
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
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['yii\db\*'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/credit_db_{$_month}.log",
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'], # 'trace' 不能加，太多了！
                    'except' => $_info_except,
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/credit_info_{$_month}.log",
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/credit_error_{$_month}.log",
                ],

//                 [
//                     'class' => 'yii\mongodb\log\MongoDbTarget',
//                     'levels' => ['info'], # 'trace'
//                     'categories' => ['application'], #default
//                     'logCollection' => 'credit_info',
//                     'logVars' => [],
//                 ],

                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\base\UserException','yii\web\HttpException:*'], // UserException太多，所有错误打文件日志也先保留
                    'logVars' => [],
                    'logCollection' => "credit_error_{$_month}",
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => [ 'info', 'error', 'warning' ],
                    'logCollection' => 'integral_wall',
                    'logVars' => [],
                    'categories' => ['credit_integral_wall'],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => [ 'info', 'error', 'warning' ],
                    'logCollection' => 'dict_censor',
                    'categories' => ['dict_censor'],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => [ 'error', 'warning' ],
                    'logCollection' => 'user_credit_card_auth',
                    'logVars' => [],
                    'categories' => ['user_credit_card_auth*'],
                ],
            ]
        ],
    ]
];
