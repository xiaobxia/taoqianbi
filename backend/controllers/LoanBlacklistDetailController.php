<?php
namespace backend\controllers;


use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\data\Pagination;
use common\models\LoanPerson;
use common\models\LoanBlacklistDetail;
use common\models\LoanBlackList;

class LoanBlacklistDetailController extends BaseController {

    protected function getFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['type']) && !empty($search['type'])) {
                $condition .= " AND type = " . intval($search['type']);
            }
            if (isset($search['source']) && !empty($search['source'])) {
                $condition .= " AND source = " . intval($search['source']);
            }
            if (isset($search['content']) && !empty(trim($search['content']))) {
                $condition .= " AND content LIKE " . intval($search['content']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND updated_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND updated_at <= " . strtotime($search['endtime']);
            }
        }

        return $condition;
    }

    /**
     * @name 用户管理-用户管理-黑名单规则列表/actionList
     */
    public function actionList() {
        $condition = $this->getFilter();
        $query = LoanBlacklistDetail::find()->where($condition)->orderBy(['id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('list', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-用户管理-黑名单用户列表/actionBlackUsers
     */
    public function actionBlackUsers(){
        $condition = 'a.black_status = \'1\'';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();

            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND b.name = '" . trim($search['name']) . "'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = '" . trim($search['phone']) . "'";
            }
        }

        $query = LoanBlackList::find()->from(LoanBlackList::tableName() . ' as a ')
            ->leftJoin(LoanPerson::tableName() . 'as b', ' a.user_id = b.id')
            ->select('a.id,a.user_id,a.black_remark,a.black_admin_user,a.black_count,a.created_at,a.updated_at,b.name,b.phone')
            ->where($condition)
            ->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('1',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('black-users', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @return array
     * @name 用户管理-用户管理-黑名单规则列表-删除/actionDel
     */
    public function actionDel() {
        $this->response->format = Response::FORMAT_JSON;
        $id = intval($this->request->get('id'));
        if(LoanBlacklistDetail::deleteAll(['id'=>$id])) {
           return [
               'code' => 0,
               'message' => '删除成功'
           ];
        }else{
            return [
                'code'=> -1,
                'message' => '删除失败'
            ];
        }
    }

    /**
     * @return string
     * @name 用户管理-用户管理-黑名单规则列表-黑名单规则添加/actionAdd
     */
    public function actionAdd() {
        $data = new LoanBlacklistDetail();
        if($this->getRequest()->getIsPost()) {
            $form = $this->request->post();
            if(!is_null(LoanBlacklistDetail::find()->where(['type'=>$form['LoanBlacklistDetail']['type'],'content'=>$form['LoanBlacklistDetail']['content']])->one(Yii::$app->get('db_kdkj_rd')))) {
                return $this->redirectMessage('规则不能重复',self::MSG_ERROR,Url::toRoute(['add']));
            }
            $form['LoanBlacklistDetail']['source'] = LoanBlacklistDetail::SOURCE_MANUAL;
            $form['LoanBlacklistDetail']['admin_username'] = Yii::$app->user->identity->username;

            if($data->load($form) && $data->save()) {
                return $this->redirectMessage('创建成功',self::MSG_SUCCESS,Url::toRoute(['list']));
            }else{
                return $this->redirectMessage('创建失败',self::MSG_ERROR,Url::toRoute(['add']));
            }
        }
        return $this->render('add', array(
            'data' => $data
        ));
    }

    /**
     * @param $id
     * @name 用户管理-用户管理-黑名单规则列表-编辑/actionEdit
     */
    public function actionEdit($id) {
        $data = LoanBlacklistDetail::findOne($id);
        if($this->getRequest()->getIsPost()) {
            $form = $this->request->post();
            if(!is_null(LoanBlacklistDetail::find()->where(['type'=>$form['LoanBlacklistDetail']['type'],'content'=>$form['LoanBlacklistDetail']['content']])->one(Yii::$app->get('db_kdkj_rd')))) {
                return $this->redirectMessage('规则不能重复',self::MSG_ERROR,Url::toRoute(['edit','id'=>$id]));
            }
            $form['LoanBlacklistDetail']['source'] = LoanBlacklistDetail::SOURCE_MANUAL;
            $form['LoanBlacklistDetail']['admin_username'] = Yii::$app->user->identity->username;

            if($data->load($form) && $data->save()) {
                return $this->redirectMessage('编辑成功',self::MSG_SUCCESS,Url::toRoute(['list']));
            }else{
                return $this->redirectMessage('编辑失败',self::MSG_ERROR,Url::toRoute(['edit','id'=>$id]));
            }
        }
        return $this->render('edit', array(
            'data' => $data
        ));
    }
}
