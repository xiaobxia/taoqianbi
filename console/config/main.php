<?php
$_month = sprintf('%s_%s', date('ym'), intval(date('d') / 10));
$_info_except = ['yii\db\*', 'yii\mongodb\*', 'yii\web\Session*'];

return [
    'timeZone' => 'Asia/Shanghai',
    'id' => 'app-console',
    'name' => APP_NAMES . '命令行',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'modules' => [
        'server' => [
            'class' => 'console\server\Module',
        ],
    ],
    'components' => [
        'errorHandler' => [
            'class' => 'console\components\ErrorHandler',
        ],
        'mutex' => [
            'class' => 'yii\mutex\FileMutex'
        ],
        'log' => [
            'targets' => [
                // ------- 全局通用日志配置 begin -------
//                [
//                    'class' => \yii\log\FileTarget::class,
//                    'levels' => ['info'],
//                    'categories' => ['yii\db\*'],
//                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
//                    'logVars' => [], //donot log $_SERVER ...
//                    'logFile' => "@runtime/logs/console_db_{$_month}.log",
//                ],
//                [
//                    'class' => \yii\log\FileTarget::class,
//                    'levels' => ['info'], # 'trace' 不能加，太多了！
//                    'except' => $_info_except,
//                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
//                    'logVars' => [], //donot log $_SERVER ...
//                    'logFile' => "@runtime/logs/console_info_{$_month}.log",
//                ],
//                [
//                    'class' => \yii\log\FileTarget::class,
//                    'levels' => ['error', 'warning'],
//                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
//                    'logVars' => [], //donot log $_SERVER ...
//                    'logFile' => "@runtime/logs/console_error_{$_month}.log",
//                ],

                //mongo
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['info'],
                    'except' => $_info_except,
                    # 'exportInterval' => 1, //mongo不能这样
                    'logVars' => [],
                    'logCollection' => "console_info_{$_month}",
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                    'logCollection' => "console_error_{$_month}",
                ],
                [
                    'class' => 'yii\mongodb\log\MongoDbTarget',   //微信中奖发放优惠券
                    'levels' => ['error', 'warning','info'],
                    'logVars' => [],
                    'categories' => ['code_phone'],
                    'logCollection' => "code_phone",
                ],
                // ------- 全局通用日志配置 end -------
            ],
        ],
    ],
];
