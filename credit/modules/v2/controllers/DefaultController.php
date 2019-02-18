<?php

namespace credit\modules\v2\controllers;

use yii\web\Response;

class DefaultController extends BaseWebController
{
	public $layout = 'v2';

    public function actionIndex()
    {
        return $this->render('index');
    }
}
