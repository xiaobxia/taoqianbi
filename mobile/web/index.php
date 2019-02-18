<?php

$appPath = dirname(__DIR__);
$basePath = dirname($appPath);
$commonPath = $basePath . DIRECTORY_SEPARATOR . 'common';
$vendorPath = $basePath . DIRECTORY_SEPARATOR . 'vendor';
$enviormentsPath = $basePath . DIRECTORY_SEPARATOR . 'environments';
$appName = pathinfo($appPath)['basename'];

$env = 'prod';
$debug = false;
$envPath = $enviormentsPath . DIRECTORY_SEPARATOR . 'env.php';
$debugPath = $enviormentsPath . DIRECTORY_SEPARATOR . 'debug.php';

if(file_exists($envPath)){
    $env = require($envPath);
}

if(file_exists($debugPath)){
    $debug = require($debugPath);
}

define('YII_ENV', $env);
define('YII_DEBUG', $debug);
define('APP_NAME', $appName);
define('BASE_PATH', $basePath);
define('COMMON_PATH', $commonPath);
define('VENDOR_PATH', $vendorPath);
define('ENV_PATH', $enviormentsPath);

require($vendorPath . DIRECTORY_SEPARATOR . 'autoload.php');
require($vendorPath . DIRECTORY_SEPARATOR . 'yiisoft' . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php');
require($commonPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php');
require($appPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php');

$sourceParams = [
    'common' => require($commonPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params.php'),
    'commonEnv' => [],
    'app' => require($appPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params.php'),
    'appEvn' => []
];
$sourceConfig = [
    'common' => require($commonPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php'),
    'commonEnv' => [],
    'app' => require($appPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php'),
    'appEvn' => []
];

$commonEnvConfigPath = $enviormentsPath . DIRECTORY_SEPARATOR . $env .DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main-local.php';
$commonEnvParamsPath = $enviormentsPath . DIRECTORY_SEPARATOR . $env .DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params-local.php';
if(file_exists($commonEnvConfigPath)){
    $sourceConfig['commonEnv'] = require($commonEnvConfigPath);
}
if(file_exists($commonEnvParamsPath)){
    $sourceParams['commonEnv'] = require($commonEnvParamsPath);
}

$appEnvConfigPath = $enviormentsPath . DIRECTORY_SEPARATOR . $env .DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main-local.php';
$appEnvParamsPath = $enviormentsPath . DIRECTORY_SEPARATOR . $env .DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params-local.php';
if(file_exists($appEnvConfigPath)){
    $sourceConfig['appEvn'] = require($appEnvConfigPath);
}
if(file_exists($appEnvParamsPath)){
    $sourceParams['appEvn'] = require($appEnvParamsPath);
}

$config = yii\helpers\ArrayHelper::merge($sourceConfig['common'], $sourceConfig['commonEnv'], $sourceConfig['app'], $sourceConfig['appEvn']);
$config['params'] = yii\helpers\ArrayHelper::merge($sourceParams['common'], $sourceParams['commonEnv'], $sourceParams['app'], $sourceParams['appEvn']);

$application = new yii\web\Application($config);
$application->run();