<?php

namespace credit\modules\v2\controllers;

use yii\web\Response;

/**
 * HTML 文件
 */
class BaseWebController extends \common\components\BaseController
{
    public function init() {
    	parent::init();
        $this->getResponse()->format == Response::FORMAT_HTML;
    }
    
}
