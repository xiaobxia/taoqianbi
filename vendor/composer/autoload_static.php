<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitaed39ad1b5103541f523a8477fafa0f1
{
    public static $files = array (
        '2cffec82183ee1cea088009cef9a6fc3' => __DIR__ . '/..' . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php',
        '2c102faa651ef8ea5874edb585946bce' => __DIR__ . '/..' . '/swiftmailer/swiftmailer/lib/swift_required.php',
    );

    public static $prefixLengthsPsr4 = array (
        'y' => 
        array (
            'yii\\swiftmailer\\' => 16,
            'yii\\redis\\' => 10,
            'yii\\mongodb\\' => 12,
            'yii\\gii\\' => 8,
            'yii\\debug\\' => 10,
            'yii\\composer\\' => 13,
            'yii\\bootstrap\\' => 14,
            'yii\\' => 4,
        ),
        'o' => 
        array (
            'omnilight\\scheduling\\' => 21,
        ),
        'c' => 
        array (
            'cebe\\markdown\\' => 14,
        ),
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
        'J' => 
        array (
            'JPush\\' => 6,
        ),
        'C' => 
        array (
            'Curl\\' => 5,
            'Cron\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'yii\\swiftmailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-swiftmailer',
        ),
        'yii\\redis\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-redis',
        ),
        'yii\\mongodb\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-mongodb',
        ),
        'yii\\gii\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-gii',
        ),
        'yii\\debug\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-debug',
        ),
        'yii\\composer\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-composer',
        ),
        'yii\\bootstrap\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2-bootstrap',
        ),
        'yii\\' => 
        array (
            0 => __DIR__ . '/..' . '/yiisoft/yii2',
        ),
        'omnilight\\scheduling\\' => 
        array (
            0 => __DIR__ . '/..' . '/omnilight/yii2-scheduling',
        ),
        'cebe\\markdown\\' => 
        array (
            0 => __DIR__ . '/..' . '/cebe/markdown',
        ),
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
        'JPush\\' => 
        array (
            0 => __DIR__ . '/..' . '/jpush/jpush/src/JPush',
        ),
        'Curl\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-curl-class/php-curl-class/src/Curl',
        ),
        'Cron\\' => 
        array (
            0 => __DIR__ . '/..' . '/mtdowling/cron-expression/src/Cron',
        ),
    );

    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Symfony\\Component\\Process\\' => 
            array (
                0 => __DIR__ . '/..' . '/symfony/process',
            ),
        ),
        'H' => 
        array (
            'HTMLPurifier' => 
            array (
                0 => __DIR__ . '/..' . '/ezyang/htmlpurifier/library',
            ),
        ),
        'D' => 
        array (
            'Diff' => 
            array (
                0 => __DIR__ . '/..' . '/phpspec/php-diff/lib',
            ),
        ),
    );

    public static $classMap = array (
        'JpGraph\\JpGraph' => __DIR__ . '/..' . '/jpgraph/jpgraph/lib/JpGraph.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitaed39ad1b5103541f523a8477fafa0f1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitaed39ad1b5103541f523a8477fafa0f1::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitaed39ad1b5103541f523a8477fafa0f1::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitaed39ad1b5103541f523a8477fafa0f1::$classMap;

        }, null, ClassLoader::class);
    }
}
