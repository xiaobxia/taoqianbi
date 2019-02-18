<?php
namespace common\helpers;
use Yii;

class GlobalHelper {
    public static function getDomain() {
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $_pos = strpos($domain, 'wzdai.');
        if ($_pos) {
            return YII_ENV_DEV ? \substr($domain, $_pos) : 'wzdai.com'; //开发环境, 也许不用.com后缀
        }
        else {
            return $domain;
        }
    }

    /**
     * ping db 数据库操作
     * @param string $db
     * @return mixed
     */
    public static function pingDb($db = 'db') {
        $db_handle = ($db instanceof \yii\db\Connection) ? $db : \yii::$app->$db;
        return $db_handle->createCommand("select 1")->queryScalar();
    }

    /**
     * connect db 重新连接数据库
     * @param string $db
     * @return \yii\db\Connection 返回数据库操作句柄
     */
    public static function connectDb($db = 'db') {
        $db_handle = ($db instanceof \yii\db\Connection) ? $db : \yii::$app->$db;
        if (empty($db_handle)) {
            return false;
        }

        try {
            return $db_handle->createCommand("select 1")->queryScalar();
        }
        catch (\Exception $e){
            $db_handle->close();
            $db_handle->open();
        }
    }

}
