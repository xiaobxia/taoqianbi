<?php
namespace mobile\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\base\UserException;
use common\helpers\StringHelper;
use common\helpers\CurlHelper;
use common\models\Indiana;
use yii\web\Response;
use yii\helpers\Url;
use yii\db\Query;
use yii\data\Pagination;
use common\models\IndianaOrder;
use common\models\LoanRecordPeriod;
use  yii\helpers\Html;
use common\services\UserService;
/**
 *一元夺宝
 */
class PeriodizationController extends BaseController
{

    protected $userService;

    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['index'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }


    /**
     * 0息分期
     */
    public function actionIndex($page=1){
        $page = max(intval($page),1);
        $pageSize = 20;
        $offset = ($page-1)*$pageSize;
        $data = Indiana::find()->where(['status'=>Indiana::STATUS_ON])->offset($offset)->limit($pageSize)->orderby('id desc')->asArray()->all();
        foreach ($data as &$val) {
            $month_arr = explode(',',$val['installment_month']);
            $val['max_month'] = intval(end($month_arr));
            $val['month_pay'] = $val['max_month'] ? sprintf('%.2f',$val['installment_price']/100/$val['max_month']) : $val['installment_price'];
        }

        $this->view->title = '分期购';
        if($page > 1){
            $this->response->format = Response::FORMAT_JSON;
            return ['data' => $data];
        }else{
            $retData = ['data' => $data];
            return $this->render('installment-list',$retData);
        }
    }

}