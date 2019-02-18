<?php

namespace backend\controllers;

use common\models\credit_line\CreditLine;
use common\models\LoanPerson;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use backend\models\search\CreditLineSearch;

class CreditLineController extends BaseController{

    public $enableCsrfValidation = false;

    public function behaviors(){
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

    /**
     * @name 金卡资格管理 -提额申请列表/actionList
     *
     */
    public function actionList(){

        $searchModel = new CreditLineSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('list',[
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    protected function getFilter()
    {
        $condition = '1 = 1 and a.id>0 and a.status = ' . CreditLine::STATUS_ACTIVE;
        $search = $this->request->get();
        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition .= " AND a.user_id = " . intval($search['user_id']);
        }

        if (isset($search['add_start']) && !empty($search['add_start'])) {
            $condition .= " AND unix_timestamp(a.update_time) >= " . strtotime($search['add_start']);
        }
        if (isset($search['add_end']) && !empty($search['add_end'])) {
            $condition .= " AND unix_timestamp(a.update_time) < " . strtotime($search['add_end']);
        }

        if (isset($search['phone']) && !empty($search['phone'])) {
            $condition .= " and p.phone = {$search['phone']}";
        }

        return $condition;
    }

    /**
     * @name 授信额度列表
     * @return string
     */
    public function actionShowList()
    {
        $condition = $this->getFilter();
        $query = CreditLine::find()->from(CreditLine::tableName() . ' as a')->leftJoin(LoanPerson::tableName() . ' as p', 'a.user_id = p.id')->where($condition)->orderBy('a.id DESC');
        $countQuery = clone $query;
        //$pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages = new Pagination(['totalCount' => 100000]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('user-credit-line', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }
}