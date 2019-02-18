<?php
namespace console\components;

use common\helpers\System;

/**
 * Worker任务基类
 */
class Worker extends \Workerman\Worker {
    const MODEL_DAEMON = 'daemon';
    const MODEL_DEBUG = 'debug';

    static $running_model;

    /**
     * Run all worker instances.
     *
     * @return void
     */
    public static function runAll() {
        if (System::isWindowsOs()) {
            parent::runAll();
        }
        else {
            self::checkSapiEnv();
            self::init();
            self::parseCommand();
            self::daemonize();
            self::initWorkers();
            self::installSignal();
            self::saveMasterPid();
            self::forkWorkers();
            self::displayUI();
            self::resetStd();
            self::monitorWorkers();
        }
    }

    public static function init() {
        global $argv;
        if (self::$pidFile) {
            self::$pidFile = __DIR__ . "/../" . str_replace('/', '_', self::$_startFile) . ".pid";
        }

        if (self::$logFile) {
            self::$logFile = __DIR__ . '/../workerman.log';
        }

        parent::init();
    }

    /**
     * Parse command.
     * php yourfile.php start | stop | restart | reload | status
     *
     * @return void
     */
    public static function parseCommand() {
        global $argv;

        if (!System::isWindowsOs()) {
            if (substr($argv[0], -3, 3) === 'yii') {
                $runPath = array_shift($argv);
                $name = array_shift($argv);
                $runPath = substr($runPath, 0, -3);
//                $filePath = $runPath . 'console' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . ucfirst($name) . 'Controller.php';

                list($con, $act) = explode('/', $name);
                $act_words = explode('-', $act);
                $act = '';
                foreach($act_words as $_w) {
                    $act .= ucfirst($_w);
                }
                $filePath = sprintf('console/controllers/%sController_action%s', ucfirst($con), $act);
                array_unshift($argv, $filePath);

                if (self::$running_model == self::MODEL_DAEMON) {
                    $argv[2] = '-d';
                }
            }
        }

        parent::parseCommand();
    }
}
