<?php

namespace common\helpers;

use yii\helpers\Console;
use yii\helpers\Json;

use common\base\LogChannel;

class CommonHelper {

    /**
     * 清空全部的 schema cache
     * @return bool
     */
    public static function clearSchemaCache() {
        $ret = false;
        try {
            $db_names = ['db','db_kdkj','db_kdkj_rd','db_kdkj_rd2','db_rcm','db_kdkj_risk','db_kdkj_risk_rd','db_assist','db_financial','db_stats'];
            foreach($db_names as $_db) {
                $db_ins = \yii::$app->get($_db);
                if ($db_ins) {
                    $db_ins->schema->refresh();
                }
            }

            $ret = true;
        }
        catch(\Exception $e) {
            \yii::warning( sprintf('clear_all_schema_cache_failed: %s', $e), LogChannel::SYSTEM_GENERAL );
        }

        return $ret;
    }

    /**
     * 尝试关闭全部的mongo
     */
    public static function closeMongo() {
        try {
            foreach(['mongodb_log','mongodb','mongodb_rule','mongodb_user_message','mongodb_info_capture','mongodb_new'] as $_ins) {
                if (\yii::$app->$_ins->isActive()) {
                    \yii::$app->$_ins->close(true);
                }
            }
        }
        catch (\Exception $e) {
            //
        }
    }

    /**
     * 加锁 (用在console中)
     * @param string $lock_name
     */
    public static function lock($lock_name = NULL) {
        if (empty($lock_name)) {
            $backtrace = \debug_backtrace(null, 2);
            $class = $backtrace[1]['class']; # self::class
            $func = $backtrace[1]['function'];
            $args = \implode('_', $backtrace[1]['args']);
            $lock_name = \base64_encode($class . $func . $args);
        }

        $lock = \yii::$app->mutex->acquire( $lock_name );
        if (!$lock) {
            $_err = "cannot get lock {$lock_name}.";
            if (self::inConsole()) {
                # CommonHelper::info( $_err );
                return FALSE;
            }

            throw new \Exception( $_err );
        }

        \register_shutdown_function(function() use($lock_name) {
            return \yii::$app->mutex->release( $lock_name );
        });

        return TRUE;
    }

    public static function stdout($string) {
        if (Console::streamSupportsAnsiColors(\STDOUT)) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }
        return Console::stdout($string);
    }

    public static function stderr($string) {
        if (Console::streamSupportsAnsiColors(\STDERR)) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }
        return fwrite(\STDERR, $string);
    }

    public static function info($msg, $channel = '') {
        if (\yii::$app instanceof \yii\console\Application) {
            self::stdout("{$msg}\n", Console::FG_BLUE);
        }
        \yii::info($msg, $channel);
    }

    public static function error($msg, $channel = '') {
        if (\yii::$app instanceof \yii\console\Application) {
            self::stderr("{$msg}\n", Console::FG_RED);
        }

        \yii::error($msg, $channel);
    }

    /**
     * 是否在 console 上下文中
     * @return boolean
     */
    public static function inConsole() {
        return \yii::$app instanceof \yii\console\Application;
    }

    /**
     * api响应统一接口封装
     * @param array $data
     * @param number $code
     * @param string $msg
     * @return array
     */
    public static function resp($data=[], $code=0, $msg='') {
        $msg = empty($msg) ? (($code==0) ? 'success' : 'failed') : $msg;

        return [
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ];
    }

    /**
     * print a json resp
     * @param array $data
     * @param number $code
     * @param string $msg
     * @return string
     */
    public static function resp_json($data = [], $code = 0, $msg = '')
    {
        if (empty($msg)) {
            if (array_key_exists($code, ErrorCodeHelper::$err_map)) {
                $msg = ErrorCodeHelper::$err_map[$code];
            }
        }

        echo Json::encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * print a json resp
     * @param array $data
     * @param number $code
     * @param string $msg
     * @return string
     */
    public static function json_encode($code = 0, $msg = '', $data = [])
    {
        if (empty($msg)) {
            if (array_key_exists($code, ErrorCodeHelper::$err_map)) {
                $msg = ErrorCodeHelper::$err_map[$code];
            } else {
                $msg = 'system error';
            }
        }

        echo Json::encode([
            'errno' => $code,
            'errmsg' => $msg,
            'data' => $data,
        ]);
    }

    public static function object_to_array($object)
    {
        if (is_null($object)) return null;
        $ret = array();
        foreach ($object as $key => $value) {
            $value_type = gettype($value);
            if ($value_type == "array" || $value_type == "object") {
                $ret[$key] = CommonHelper::object_to_array($value);
            } else {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    public static function sum_json_value($base, $addition)
    {
        if (!is_array($base)) return false;
        if (!empty($base)) $result = $base;
        else $result = [];
        foreach ($addition as $key => $value) {
            if (!in_array($key, $result)) {
                $result[$key] = $value;
            } else if (is_array($value)) {
                $ret = self::sum_json_value($result[$key], $value);
                if ($ret === false) return false;
                $result[$key] = $ret;
            } else if ((is_int($result[$key]) || is_float($result[$key]) || is_double($result[$key]))
                && (is_int($value) || is_float($value) || is_double($value))
                && (is_long($value) || is_long($value) || is_long($value))
            ) {
                $result[$key] += $value;
            } else if (is_string($value)) {
                $result[$key] = $result[$key] + floatval($value);
            } else {
                return false;
            }
        }
        return $result;
    }

    /**
     * 是否是本地开发环境
     * @return boolean
     */
    public static function isLocal()
    {
        return isset($_SERVER['USER']) && $_SERVER['USER'] == 'clarkyu';
    }

    /**
     * 从开始时间戳获取, 连续多少天的一个数组.
     * @param unknown $start_ts
     * @param unknown $length
     * @param boolean $reverse 正序? 倒序?
     * @return array
     */
    static function dateArray($start_ts, $length, $reverse = FALSE)
    {
        $ret = [];
        do {
            $ret[] = date('Y-m-d', $start_ts);
            $start_ts += ($reverse ? -86400 : 86400);
        } while (count($ret) != $length);

        return $reverse ? \array_reverse($ret) : $ret;
    }

    /**
     * 获取menu中定义的配置
     * @param string $nav
     * @return array
     */
    public static function getNavConfig($nav)
    {
        $root = \yii::$app->basePath;
        include_once "{$root}/config/menu.php";
        $ret = [];
        foreach ($menu as $_l1_key => $_info) {
            foreach ($_info as $_l2_key => $_l2_info) {
                if ($_l2_key == $nav) {
                    $ret = $_l2_info;
                    break 2;
                }
            }
        }

        return $ret;
    }

    /**
     * 重置 yii 的某个 components.
     * @param string $id
     * @return boolean 重置是否成功
     */
    public static function resetService($id)
    {
        $ret = FALSE;
        if (!isset(yii::$app->components[$id])) {
            return $ret;
        }

        try {
            $old_def = \yii::$app->components[$id];
            \yii::$app->clear($id);
            \yii::$app->set($id, $old_def);
            $ret = TRUE;
        } catch (\Exception $e) {
            \yii::error('[%s][%s] msg: %s; trace: %s.', date('y-m-d H:i:s'), __FUNCTION__, $e->getMessage(), $e->getTraceAsString());
        }

        return $ret;
    }

    /**
     * 获得银行交易成功时间
     * @param $opr_dat
     * @return int
     */
    public static function getSuccessTime($opr_dat)
    {
        $time = time();
        if($opr_dat){
            $opr_dat = strtotime($opr_dat);
            if($opr_dat > 0){
                $time = strtotime(date('Y-m-d',$opr_dat).' '.date('H:i:s'));
                if(date('Y-m-d',$time) != date('Y-m-d')){
                    $time = $opr_dat+86400-1;
                }
            }
        }
        return $time;
    }
}
