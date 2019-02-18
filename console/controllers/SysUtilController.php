<?php
namespace console\controllers;

use common\helpers\CommonHelper;

class SysUtilController extends BaseController {

    /**
     * 清空全部的db缓存
     */
    public function actionClearSchemaCache() {
        $ret = CommonHelper::clearSchemaCache();
        print "done {$ret}\n";
    }

}
