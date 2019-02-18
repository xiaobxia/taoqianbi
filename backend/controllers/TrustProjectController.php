<?php
namespace backend\controllers;

use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use common\helpers\StringHelper;

use common\models\User;
use common\models\LoanProject;
use common\models\LoanRecord;
use common\models\Shop;

/**
 * Class ShopController     商铺管理控制器
 * @package backend\controllers
 * @author lizi@kdqugou.com
 */
class TrustProjectController extends BaseController
{
    //商户列表
    public function actionList()
    {
        $condition = $this->getLoanProjectFilter();
        $query = LoanProject::find()->where($condition)->andWhere(['type'=>LoanProject::TYPE_TRUST])->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 8;
        $loan_project_list = $query->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('list', array(
            'loan_project_list' => $loan_project_list,
            'pages' => $pages,
        ));
    }

    /**
     * 商户过滤
     * @return string
     */
    protected function getLoanProjectFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['loan_project_name']) && !empty($search['loan_project_name'])) {
                $condition .= " AND loan_project_name LIKE '%" . trim($search['loan_project_name']) . "%'";
            }
        }
        return $condition;
    }

    /**
     * 借款项目添加
     * @return string
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionAdd() {
        $loan_project = new LoanProject();
        $loan_project->success_number = 0;
        if ($this->getRequest()->getIsPost()) {
            $loan_project->load($this->request->post());
            $loan_project->type = LoanProject::TYPE_TRUST;
            $loan_project->status = LoanProject::STATUS_DRATF;
            $loan_project->amount_min = StringHelper::safeConvertCentToInt($loan_project->amount_min);
            $loan_project->amount_max = StringHelper::safeConvertCentToInt($loan_project->amount_max);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if ($loan_project->validate() && $loan_project->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('添加借款项目成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-project-list']));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('添加借款项目失败', self::MSG_ERROR);
            }
        }
        return $this->render('add', array(
            'loan_project' => $loan_project,
        ));
    }

    public function actionTrial(){
        $condition = $this->getLoanProjectFilter();
        $query = LoanProject::find()->where($condition)->andWhere(['type'=>LoanProject::TYPE_TRUST,'status'=>LoanProject::STATUS_DRATF])->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 8;
        $loan_project_list = $query->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('trial', array(
            'loan_project_list' => $loan_project_list,
            'pages' => $pages,
        ));
    }


    public function actionActive(){
        $condition = $this->getLoanProjectFilter();
        $query = LoanProject::find()->where($condition)->andWhere(['type'=>LoanProject::TYPE_TRUST,'status'=>LoanProject::STATUS_ADMIN])->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 8;
        $loan_project_list = $query->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('active', array(
            'loan_project_list' => $loan_project_list,
            'pages' => $pages,
        ));
    }

    public function actionTrialPass(){
        $id = intval($this->request->get('id'));
        $loanProject = LoanProject::findOne($id);
        if(is_null($loanProject)){
            return $this->redirectMessage('该项目不存在', self::MSG_ERROR);
        }
        $loanProject->status = LoanProject::STATUS_ADMIN;
        $ret = $loanProject->save();
        if($ret){
            return $this->redirectMessage('初审通过成功', self::MSG_SUCCESS);
        }else{
            return $this->redirectMessage('初审通过失败', self::MSG_ERROR);
        }
    }


    public function actionActivePass(){
        $id = intval($this->request->get('id'));
        $loanProject = LoanProject::findOne($id);
        if(is_null($loanProject)){
            return $this->redirectMessage('该项目不存在', self::MSG_ERROR);
        }
        $loanProject->status = LoanProject::STATUS_ACTIVE;
        $ret = $loanProject->save();
        if($ret){
            return $this->redirectMessage('发布成功', self::MSG_SUCCESS);
        }else{
            return $this->redirectMessage('发布失败', self::MSG_ERROR);
        }
    }

    public function actionTrialReject(){
        $id = intval($this->request->get('id'));
        $loanProject = LoanProject::findOne($id);
        if(is_null($loanProject)){
            return $this->redirectMessage('该项目不存在', self::MSG_ERROR);
        }
        $loanProject->status = LoanProject::STATUS_TRIAL_REJECT;
        $ret = $loanProject->save();
        if($ret){
            return $this->redirectMessage('初审驳回成功', self::MSG_SUCCESS);
        }else{
            return $this->redirectMessage('初审驳回失败', self::MSG_ERROR);
        }
    }

    public function actionActiveReject(){
        $id = intval($this->request->get('id'));
        $loanProject = LoanProject::findOne($id);
        if(is_null($loanProject)){
            return $this->redirectMessage('该项目不存在', self::MSG_ERROR);
        }
        $loanProject->status = LoanProject::STATUS_ACTIVE_REJECT;
        $ret = $loanProject->save();
        if($ret){
            return $this->redirectMessage('发布驳回成功', self::MSG_SUCCESS);
        }else{
            return $this->redirectMessage('发布驳回失败', self::MSG_ERROR);
        }
    }

    public function actionView($id) {
        $loan_project = LoanProject::find()->where(['id' => intval($id)])->one();
        if (!isset($loan_project) && empty($loan_project)) {
            throw new NotFoundHttpException('The requested loan project does not exist.');
        }
        return $this->render('view', array(
            'loan_project' => $loan_project,
        ));
    }

    public function actionEdit($id) {
        $loan_project = LoanProject::find()->where(['id' => intval($id)])->one();
        if (!isset($loan_project) && empty($loan_project)) {
            throw new NotFoundHttpException('The requested loan project does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $loan_project->load($this->request->post());
            $loan_project->amount_min = StringHelper::safeConvertCentToInt($loan_project->amount_min);
            $loan_project->amount_max = StringHelper::safeConvertCentToInt($loan_project->amount_max);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if ($loan_project->validate() && $loan_project->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('编辑借款项目成功', self::MSG_SUCCESS);
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('编辑借款项目失败', self::MSG_ERROR);
            }
        }
        return $this->render('edit', array(
            'loan_project' => $loan_project,
        ));
    }

    public function actionDel($id) {
        $result = Yii::$app->db_kdkj->createCommand()->update(LoanProject::tableName(), [
            'status' => LoanProject::STATUS_DELETE
        ],
            [
                'id' => intval($id),
            ])->execute();
        if (!$result) {
            return $this->redirectMessage('作废失败', self::MSG_ERROR);
        }
        return $this->redirectMessage('作废成功', self::MSG_SUCCESS);
    }
}
