<?php

namespace backend\controllers;

use yii;
use yii\data\Pagination;
use common\helpers\Url;
use common\models\RepaymentConfig;
use common\api\RedisQueue;

class RepaymentConfigController extends BaseController {

    private $db;
    private $read_db;

    /**
     * init
     */
    public function init(){
        parent::init();
        $this->db = \Yii::$app->db_kdkj_rd_new;
        $this->read_db = \Yii::$app->db_kdkj_rd_new;
    }
    /**
     *@name 内容管理-运营管理-还款配置/actionList
     */
    public function actionList() {
        $query = RepaymentConfig::find();
        $totalCount = $query->count('*', $this->read_db);
        $pages =new Pagination(['totalCount' => $totalCount]);;
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->all($this->read_db);
        return $this->render("list", [
                    'data' => $data,
                    'pages' => $pages
        ]);
    }
    /**
     *@name 内容管理-运营管理-还款配置增加/actionAdd
     */
    public function actionAdd() {
        $model = new RepaymentConfig();
        $id = intval($this->request->get('id', 0));
        if(!empty($id)) {
            $title = "还款配置编辑";
            $model = RepaymentConfig::findOne( array('id' => intval($id)) );
        } else{
            $title = "还款配置添加";
        }

        if($model->max) {
            $model->max = sprintf("%0.2f", $model->max / 100);
        }

        if ($model->load($this->request->post()) && $model->validate()) {
            $model->percent = intval($model->percent);
            $model->max = intval($model->max)*100;
            if ($model->save()) {
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('操作失败', self::MSG_ERROR);
            }
        }

        return $this->render('add-config', [
            'model' => $model,
            'title' => $title,
        ]);
    }


}
