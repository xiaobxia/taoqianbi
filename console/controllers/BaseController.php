<?php

namespace console\controllers;

use common\helpers\CommonHelper;
use common\helpers\System;

abstract class BaseController extends \yii\console\Controller {
    public function beforeAction($action) {
        if (parent::beforeAction($action)) {
            $this->message($this->route . ' begin');
            return true;
        } else {
            return false;
        }
    }

    public function afterAction($action, $result) {
        CommonHelper::closeMongo();
        $result = parent::afterAction($action, $result);
        $this->message($this->route . ' end');
        return $result;
    }

    /**
     * 输出错误信息到控制台，并记录log
     * @param string|\Exception $message
     * @param bool $log 是否记录日志，默认是
     */
    public function error($message, $log = true) {
        if (System::isWindowsOs()) {
            echo date('ymd H:i:s ') . "error: {$message}\n";
        } else {
            echo sprintf("%s(%s) error: %s\n", date('ymd H:i:s '), posix_getpid(), $message);
        }
        if ($log) {
            $trc = debug_backtrace();
            if (isset($trc[0])) {
                $message .= sprintf("\n  ↖ Logged At: %s:%s", $trc[0]['file'], $trc[0]['line']);
            }
            \Yii::error($message, $this->className());
        }
    }

    /**
     * 输出信息到控制台，并记录log
     * @param string $message
     * @param bool $log 是否记录日志，默认否
     */
    public function message($message, $log = false) {
        if (System::isWindowsOs()) {
            echo sprintf("%s info: %s\n", date('ymd H:i:s '), $message);
        } else {
            echo sprintf("%s(%s) info: %s\n", date('ymd H:i:s '), posix_getpid(), $message);
        }

        if ($log) {
            $trc = debug_backtrace();
            if (isset($trc[0])) {
                $message .= sprintf("\n  ↖ Logged At: %s:%s", $trc[0]['file'], $trc[0]['line']);
            }
            \yii::info($message, $this->className());
        }
    }
}
