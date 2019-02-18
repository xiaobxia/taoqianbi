<?php

namespace console\controllers;

use common\helpers\CommonHelper;
use common\helpers\MailHelper;
use common\helpers\ToolsUtil;
use common\helpers\Util;
use Yii;
use common\api\RedisQueue;
use common\models\Monitor;
use yii\base\Exception;

/**
 * 监控脚本
 */
class MonitorController extends BaseController{

    /**
     * 运行所有待触发的监控脚本
     */
    public function actionRun() {
        $models = Monitor::find()->where('next_check_time<'.time().' AND status>='.Monitor::STATUS_NORMAL)->all();
        foreach($models as $model) {
            /* @var $model Monitor */
            $model->runCheck();
        }
    }

    /**
     * 运行所有待触发的监控脚本
     */
    public function actionTestRun($id) {
        $model = Monitor::findOne((int)$id);
        /* @var $model Monitor */
        if(!$model) {
            throw new \Exception('monitor model not found!');
        } else {
            $model->runCheck();
        }
    }

}
