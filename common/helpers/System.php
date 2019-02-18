<?php

namespace common\helpers;

class System {
    static $err_code;
    static $err_msg;

    /**
     * 判断当前PHP运行环境是否为Windows
     *
     * @return boolean
     */
    public static function isWindowsOs() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * 判断当前PHP运行环境是否为Mac
     * @return boolean
     */
    public static function isMacOs() {
        return strtoupper(PHP_OS) === 'DARWIN';
    }

    /**
     * 端口连接测试
     * @param $domain
     * @param $port
     *
     * @return float|int|mixed
     */
    public static function ping($domain, $port, $timeout=10) {
        $starttime = microtime(true);
        $file      = @fsockopen($domain, $port, $errno, $errstr, $timeout);
        $stoptime  = microtime(true);

        if (!$file) {
            $status = -1;  // Site is down
            self::$err_code = $errno;
            self::$err_msg = $errstr;
        }
        else {
            fclose($file);
            $status = ($stoptime - $starttime) * 1000;
            $status = floor($status);
        }

        return $status;
    }
}
