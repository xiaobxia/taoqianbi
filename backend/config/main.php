<?php
$_month = sprintf('%s_%s', date('ym'), intval(date('d') / 10));
$_info_except = ['yii\web\Session*', 'yii\db\*', 'yii\mongodb\*',];
return [
    'id' => 'app-backend',
    'name' => APP_NAMES .'后台',
    'basePath' => dirname(__DIR__),

    'defaultRoute' => 'main/index',
    'controllerNamespace' => 'backend\controllers',
    'components' => [
        'view' => [
            'class' => 'backend\components\View',
        ],
        'user' => [
            'identityClass' => 'backend\models\AdminUser',
            'loginUrl' => ['main/login'],
        ],
        'session' => [
            'class' => 'yii\redis\Session',
            'redis' => 'redis', // 使用redis做session
            'name' => 'ADMIN_SID', // 与后台区分开会话key，保证前后台能同时单独登录
            'timeout' => 1728000, #20 * 24 * 3600,
            'keyPrefix' => 'wzd_admin:',
            'cookieParams' => [
                'lifetime' => 43200, #12 * 3600,
                'httponly' => true,
                // 'domain' => YII_ENV_PROD ? APP_DOMAIN : '',
                'domain' => '',
            ],
        ],
        'errorHandler' => [
            'class' => 'common\base\ErrorHandler'
        ],
        'request' => [
            'class' => 'common\components\Request',
            'cookieValidationKey' => 'h&pKeJ&&J3z5CyfLLfv8b2fb#L7DeyFG'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                // ------- 全局通用日志配置 begin -------
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'], # 'trace' 不能加，太多了！
                    'except' => $_info_except,
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/backend_info_{$_month}.log",
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/backend_error_{$_month}.log",
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['yii\db\*'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/backend_db_{$_month}.log",
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'], # 'trace' 不能加，太多了！
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'categories' => ['yii\mongodb\*'],
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/mongo_{$_month}.log",
                ],

                //mongo
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['info'],
                    'except' => $_info_except,
                    # 'exportInterval' => 1, //mongo不能这样
                    'logCollection' => "backend_info_{$_month}",
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    # 'exportInterval' => 1, //mongo不能这样
                    'logCollection' => "backend_error_{$_month}",
                    'logVars' => [],
                ],
                // ------- 全局通用日志配置 end -------

                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    'categories' => ['risk_control*'],
                    'logCollection' => 'risk_control_error',
                    'logVars' => [],
                ],

                //支付宝同步记录
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['warning'],
                    'categories' => ['alipay_sync_log'],
                    'logCollection' => 'alipay_sync_log',
                    'logVars' => [],
                ],

                //渠道操作log
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['warning', 'info', 'error'],
                    'categories' => ['channel_rate'],
                    'logCollection' => 'channel_rate',
                    'logVars' => [],
                ],
            ],
        ],
    ]
];
