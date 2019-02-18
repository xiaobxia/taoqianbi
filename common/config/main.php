<?php
$config = [
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'vendorPath' => VENDOR_PATH,
    'bootstrap' => ['log'],
    'components' => [
        'cUrl' => [
            'class' => 'common\components\CUrl'
        ],
        'shortUrl' => [
            'class' => 'common\components\ShortUrl',
            'appKey' => 1681459862,
            'host' => 'api.t.sina.com.cn',
            'routes' => [
                'generate' => 'short_url/shorten.json'
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => false,
            'showScriptName' => false,
        ],
        'cache' => [
            'class' => 'common\caching\RedisCache',
            'randomDuration' => 5,
        ],
        'security' => [
            'passwordHashCost' => 8, #8就够了
        ],
        'creditChannelService' => [ // 额度渠道服务组件
            'class' => 'common\services\UserCreditChannelService',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.mxhichina.com',
                'port' => '465',
                'username' => 'dev@sdpurse.com',
                'password' => 'sdhbdev2018!!',
                'encryption' => 'ssl',
            ],
            'viewPath' => Yii::getAlias('@common/mail'),
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => ['dev@sdpurse.com' => APP_NAMES],
            ],
        ],
        'email' => [
            'class' => 'common\components\Email'
        ],
        'creditFacePlusService' => [
            'class' => 'common\services\CreditFacePlusService',
            'apiKey' => '4xMkSCbQVt5od2rHYfEIbk5dpjUvNMaC',
            'apiSecret' => 'Hnx9kJGOxjNazd9OFM7S5DodrW-mumrq'
        ],
        'jxlService' => [
            'class' => 'common\services\JxlService',
            'clientSecret' => '61a372909b5b44278020f2e7f1379ada',
            'orgName' => 'shandianhb'
        ],
        'tdService' => [
            'class' => 'common\services\TdService',
            'protocol' => 'https',
            'host' => 'api.tongdun.cn',
            'routes' => [
                'getReportData' => 'preloan/apply/v5',
                'getTdReport' => 'preloan/report/v7'
            ]
        ],
        'weixinService' => [
            'class' => 'common\services\WeixinService',
            'protocol' => 'https',
            'host' => 'api.weixin.qq.com',
            'appID' => WEIXIN_APPID,
            'appSecret' => WEIXIN_SECRET,
            'appToken' => WEIXIN_Token,
            'routes' => [
                'get_code_token' => 'sns/oauth2/access_token',
                'get_user_info' => 'cgi-bin/user/info',
                'check_access_token' => 'sns/auth',
                'check_auth_access_token' => 'sns/auth',
                'get_access_token_redis' => 'cgi-bin/token',
                'get_auth_user_info' => 'sns/userinfo',
                'getShortUrl' => 'cgi-bin/shorturl',
                'getMenu' => 'cgi-bin/menu/create',
                'sendMsg' => 'cgi-bin/message/template/send'
            ]
        ]
    ],
];

$db_1 = [
    'host' => 'rm-bp10hl2ez6sqklpxa.mysql.rds.aliyuncs.com:3306',
    'username' => 'bubuying',
    'password' => 'Xiajian033022',
];

$dbConfig = [
    'db' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj_rd' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj_rd2' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj_rd_new' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_rcm' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj_risk' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_kdkj_risk_rd' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_financial' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_stats' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai_stats',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_stats_read' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai_stats',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
    'db_assist' => [
        'dsn' => 'mysql:host='.$db_1['host'].';dbname=xjdai_assist',
        'username' => $db_1['username'],
        'password' => $db_1['password'],
    ],
];

foreach($dbConfig as $name => $value){
    $config['components'][$name] = [
        'class' => 'yii\db\Connection',
        'dsn' => $value['dsn'],
        'username' => $value['username'],
        'password' => $value['password'],
        'tablePrefix' => 'tb_',
        'charset' => 'utf8',
        'enableSchemaCache' => YII_ENV_PROD,
        'schemaCacheDuration' => YII_ENV_PROD ? 86400 : 1800, // Duration of schema cache.
        'schemaCache' => 'cache', // Name of the cache component used to store schema information
        'attributes' => [
            PDO::ATTR_TIMEOUT => 10, // rds 的 connection_timeout 设置为10
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
    ];
}

foreach([
    'redis',
    'redis_session'
] as $name) {
    $config['components'][$name] = [
        'class' => 'yii\redis\Connection',
        'hostname' => '172.16.212.153',
        'password' => 'w8r#wR@ei3',
        'connectionTimeout' => 5,
        'dataTimeout' => 5,
        'socketClientFlags' => STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
    ];
}

foreach([
    'mongodb_log',
    'mongodb',
    'mongodb_rule',
    'mongodb_user_message',
    'mongodb_info_capture',
    'mongodb_new',
    'mongodb_log_mhk',
    'mongodb_rule_mhk',
    'mongodb_rule_new'
] as $name) {
    $config['components'][$name] = [
        'class' => 'yii\mongodb\Connection',
        'dsn' => 'mongodb://sdhb_user:r5mY&iU^38#I3t@172.16.212.153:27017/bby_log?authSource=admin',
        'options' => [
            'connectTimeoutMS' => 15000,
            'socketTimeoutMS' => 20000,
        ],
    ];
}

return $config;
