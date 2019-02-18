<?php
namespace credit\modules\v2\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\Url;
use yii\filters\AccessControl;

/**
 * IndexController
 */
class IndexController extends BaseController
{

	public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => [],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionGetName(){
    	return [
    		"code" => 0,
    		"message" => "success",
    		"data" => [
    			"item" => []
    		]
    	];
    }
}