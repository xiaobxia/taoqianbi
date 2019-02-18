<?php
$_month = sprintf('%s_%s', date('ym'), intval(date('d') / 10));
$_info_except = ['yii\web\Session*']; # 'yii\db\*', 'yii\mongodb\*', 'yii\base\UserException','yii\web\HttpException:403'

return [
    'id' => 'app-frontend',
    'name' => APP_NAMES,
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'defaultRoute' => 'installment-shop',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                'interface-loan/<channel>/<function>'=>'interface-loan/run',
                'interface-fund/<channel>/<function>'=>'interface-fund/run',
                'interface-fund/<channel>//<function>'=>'interface-fund/run',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\LoanPerson',
            // 允许使用auth_key来自动登录
            'enableAutoLogin' => true,
            // 设为null避免跳转
            'loginUrl' => null,
            'on afterLogin' => function($event) {
                \Yii::$app->session->set(
                    \Yii::$app->session->keyPrefix . \common\components\Session::SKEY,
                    \Yii::$app->session->keyPrefix . \Yii::$app->user->identity->id
                );
            },
        ],
        'session' => [
            'class' => \common\components\Session::class,
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
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['info'],
                    'categories' => ['yii\db\*'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/frontend_db_{$_month}.log",
                ],
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['info'], # 'trace' 不能加，太多了！
                    'except' => $_info_except,
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/frontend_info_{$_month}.log",
                ],
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'exportInterval' => (YII_DEBUG ? 1 : 1000), //console环境, log有一条, export一条
                    'logVars' => [], //donot log $_SERVER ...
                    'logFile' => "@runtime/logs/frontend_error_{$_month}.log",
                ],

                //mongo default
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['channel_order*'],
                    'logVars' => [],
                    'logCollection' => "channel_order_{$_month}",
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info'],
                    'categories' => ['application'],
                    'logVars' => [],
                    'logCollection' => "frontend_info_{$_month}",
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\base\UserException','yii\web\HttpException:403','channel_order.*'], // UserException太多
                    'logVars' => [],
                    'logCollection' => "frontend_error_{$_month}",
                ],

                //mongo others
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['error', 'warning'],
                    'categories' => ['risk_control*'],
                    'logVars' => [],
                    'logCollection' => "risk_control_error_{$_month}",
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info'],
                    'categories' => ['kdkj.asset.*'],
                    'logVars' => [],
                    'logCollection' => "frontend_asset_info_{$_month}",
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['kdkj.channelorder.*'],
                    'logVars' => [],
                    'logCollection' => "frontend_channelorder_{$_month}",
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info', 'warning', 'error'],
                    'categories' => ['wzd.fund.*'],
                    'logVars' => [],
                    'logCollection' => "front_fund_{$_month}", # 资方接口
                ],
                [
                    'class' => \yii\mongodb\log\MongoDbTarget::class,
                    'levels' => ['info', 'error', 'warning' ],
                    'categories' => ['integral_wall'],
                    'logVars' => [],
                    'logCollection' => "integral_wall_{$_month}",
                ],
                // ------- 全局通用日志配置 end -------
            ]
        ],
        // 下面是扩展了系统的组件
        'errorHandler' => [
            'class' => \common\components\ErrorHandler::class,
        ],
        'request' => [
            'class' => \common\components\Request::class,
            'cookieValidationKey' => 'XGsv@&nA2QxYAQY93iihCRSPb*#bJ*7j'
        ],
        'view' => [
            'class' => \common\components\View::class,
        ],
    ]
];
