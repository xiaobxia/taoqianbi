<?php

namespace backend\controllers;

use Yii;
use yii\base\Exception;

use common\models\ContentActivity;
use yii\data\Pagination;
use common\helpers\Url;

use common\models\PopBox;
/**
 *
 * 红包设置以及使用
 *
 */
class ContentActivityController extends  BaseController{
	/**
	 * @name 获取数据列表
	 */
	public function actionList(){
        $condition = $this->getListFilter();

        $query = ContentActivity::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $pages = new Pagination(['totalCount' => $query->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 10;
        $data = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('list', array(
            'data_list' => $data,
            'pages' => $pages,
        ));
	}


    private function getListFilter(){
        $condition = '1 = 1 AND status <> 0 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['title']) && !empty($search['title'])) {
                $condition .= " AND title like '%" . trim($search['title']) . "%'";
            }
            if (isset($search['use_case']) && !empty($search['use_case'])) {
                $condition .= " AND use_case=" . intval($search['use_case']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND status=" . intval($search['status']);
            }
            if (isset($search['start_time']) && !empty($search['start_time'])) {
                $condition .= " AND start_time >= " . strtotime($search['start_time']);
            }
            if (isset($search['end_time']) && !empty($search['end_time'])) {
                $condition .= " AND end_time <= " . strtotime($search['end_time']);
            }
        }
        return $condition;
    }

    /**
     * @return string
     * @name 内容管理-运营管理-消息添加/actionAdd
     */
    public function actionAdd(){
        $model = new ContentActivity();

        if ( $model->load($this->request->post()) && $model->validate() ) {

            $post_arr = $this->request->post();

			$model->user_admin = Yii::$app->user->identity->username;
			$model->start_time   = strtotime($model->start_time);
            $model->end_time     = strtotime($model->end_time);
            $model->status       = ContentActivity::STATUS_EDIT;

            $model->is_up        = isset($post_arr["sel_up"]) ? $post_arr["sel_up"] : 0;
            if ($model->save()) {
                // 同步数据
                $sel_pop  = isset($post_arr["sel_pop"]) ? $post_arr["sel_pop"] : [];
                $img_url  = isset($post_arr["pop_img_1"]) ? $post_arr["pop_img_1"] : "";
                $img_pop_url  = isset($post_arr["pop_img_2"]) ? $post_arr["pop_img_2"] : "";

                if (in_array("1", $sel_pop)) {
                    $pop_box = new PopBox();
                    $pop_box->img_url = $img_url;
                    $pop_box->expect_time = $model->start_time;
                    $pop_box->expire_time = $model->end_time;
                    $pop_box->action_url  = $model->link;
                    $pop_box->action_type = PopBox::ACTION_H5;
                    $pop_box->creater     = Yii::$app->user->identity->username;
                    $pop_box->created_at  = time();
                    $pop_box->updated_at  = time();
                    $pop_box->show_site   = PopBox::SHOW_SITE_ONE;
                    if ($model->status == ContentActivity::STATUS_SUCCESS) {
                        $pop_box->status = PopBox::STATUS_SUCC;
                    }
                    $pop_box->save();
                }

                if (in_array("2", $sel_pop)) {
                    $pop_box = new PopBox();
                    $pop_box->img_url = $img_pop_url;
                    $pop_box->expect_time = $model->start_time;
                    $pop_box->expire_time = $model->end_time;
                    $pop_box->action_url  = $model->link;
                    $pop_box->action_type = PopBox::ACTION_H5;
                    $pop_box->creater     = Yii::$app->user->identity->username;
                    $pop_box->created_at  = time();
                    $pop_box->updated_at  = time();
                    $pop_box->show_site   = PopBox::SHOW_SITE_TWO;
                    if ($model->status == ContentActivity::STATUS_SUCCESS) {
                        $pop_box->status = PopBox::STATUS_SUCC;
                    }
                    $pop_box->save();
                }
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }
        return $this->render('add',[
            'model' => $model,
            'data' => [],
        ]);
    }

    /**
     * @return string
     * @name 内容管理-合同管理-合同列表-编辑/actionEdit
     */
    public function actionEdit(){
        $id = intval($this->request->get('id',0));
        $model = ContentActivity::findOne($id);
        $model->start_time   = date("Y-m-d H:i:s",$model->start_time);
        $model->end_time     = date("Y-m-d H:i:s",$model->end_time);
        $model->status       = $model->status;
        if ( $model->load($this->request->post()) && $model->validate() ) {

            $post_arr = $this->request->post();
            $model->is_up        = isset($post_arr["sel_up"]) ? $post_arr["sel_up"] : 0;
            $model->user_admin = Yii::$app->user->identity->username;
            $model->start_time   = strtotime($model->start_time);
            $model->end_time     = strtotime($model->end_time);
            if ($model->save()) {
                return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('编辑失败', self::MSG_ERROR);
            }
        }
        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * 更新启用状态
     * @name 更新启用状态
     */
    public function actionUpdate(){
        $id = intval($this->request->get('id',0));
        $model = ContentActivity::findOne($id);

        if ( $model->load($this->request->post()) && $model->validate() ) {
            $post_arr = $this->request->post();
            $model->user_admin = Yii::$app->user->identity->username;
            $model->is_up        = isset($post_arr["sel_up"]) ? $post_arr["sel_up"] : 0;
            if ($model->save()) {
                return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('编辑失败', self::MSG_ERROR);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
}
