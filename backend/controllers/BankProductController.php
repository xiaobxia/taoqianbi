<?php
namespace backend\controllers;

use backend\helpers\DraftHelper;
use common\helpers\StringHelper;
use common\models\Project;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\data\Pagination;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use backend\models\FinancialDraft;

class BankProductController extends BaseController
{

    /**
     * 银行理财列表
     * @return string
     * @author zhangxiaoguang@kdqugou.com
     */
    public function actionBankProductList()
    {
        $condition = $this->getFilter();
        $query = FinancialDraft::find()->where($condition)->andWhere("type =".FinancialDraft::PRODUCT_TYPE_BANK)
            ->orderBy([
                'buy_time' => SORT_DESC,
            ]);
        $query_count = clone $query;
        $pages = new Pagination(['totalCount' => $query_count->count()]);
        $pages->pageSize = 20;
        $draft_list = $query->with([
            'project'=> function($queryProject) {
                    $queryProject->select(['id','status']);
                }
        ])->offset($pages->offset)->limit($pages->limit)->all();
        $user_repayed_profits = 0;
        $user_repaying_profits = 0;
        $user_profits = 0;
        $project_money = 0;
        $sum = $query_count->select(['sum(true_money)', 'sum(draft_account)', 'sum(user_profits)'])->asArray()->all();
        $query = "select sum(draft_account) as draft_account_repay from ".FinancialDraft::tableName()." where status =".FinancialDraft::STATUS_INTEREST_COLLECTION." and type = ".FinancialDraft::PRODUCT_TYPE_BANK;
        $result = Yii::$app->db_kdkj->createCommand($query)->queryOne();
        $sum[0]['draft_account_repay'] = $result['draft_account_repay'];

        $query = "select distinct project_id, user_profits from ".FinancialDraft::tableName()." where type = ".FinancialDraft::PRODUCT_TYPE_BANK;
        $result = Yii::$app->db_kdkj->createCommand($query)->queryAll();
        foreach($result as $k => $v){
            $user_profits += $v['user_profits'];
            $project = Project::findOne($v['project_id']);
            if(empty($project)){
                continue;
            }
            if($project->status == Project::STATUS_REPAYED){
                $user_repayed_profits += $v['user_profits'];
            }else{
                $user_repaying_profits += $v['user_profits'];
            }
            $project_money += $project->total_money;
        }
        $sum[0]['project_money'] = $project_money;
        $sum[0]['user_profits'] = $user_profits;
        $sum[0]['user_repayed_profits'] = $user_repayed_profits;
        $sum[0]['user_repaying_profits'] = $user_repaying_profits;
        return $this->render('/draft/bank-product-list', [
            'draft_list' => $draft_list,
            'pages' => $pages,
            'sum' => $sum[0],
            'type' => ''
        ]);
    }

    /**
     * 创建银行理财项目
     * @return string
     */
    public function actionBankProductCreate()
    {
        $draft = new FinancialDraft();
        if ($this->getRequest()->getIsPost()) {
            $draft->load($this->request->post());
            $message = $this->request->post();
            $bank_name = $message['bank_name'];
            $bank_username = $message['bank_username'];
            $bank_number = $message['bank_number'];
            $draft->bank_name = $bank_name;
            $draft->bank_username = $bank_username;
            $draft->bank_number = $bank_number;
            $draft->buy_time = strtotime($draft->buy_time);
            $draft->expire_time = strtotime($draft->expire_time);
            $draft->start_time = strtotime($draft->start_time);
            $draft->draft_account = StringHelper::safeConvertCentToInt($draft->draft_account);
            $draft->user_profits = StringHelper::safeConvertCentToInt($draft->user_profits);
            $draft->img_url = FinancialDraft::getImgUrl($draft->img_url);
            $draft->create_user = Yii::$app->user->identity->username;
            $draft->status = FinancialDraft::STATUS_INTEREST_REVIEW;
            $draft->type = FinancialDraft::PRODUCT_TYPE_BANK;
            $draft->created_at = time();
            $draft->updated_at = time();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($draft->validate() && $draft->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('添加银行理财成功', self::MSG_SUCCESS, Url::toRoute('bank-product/bank-product-list'));
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('添加银行理财失败', self::MSG_ERROR);
            }
        }
        return $this->render('/draft/bank-product-create', [
            'draft' => $draft,
            'type' => 'create',
        ]);
    }


    /**
     * 银行理财列表过滤
     * @return string
     * @author zhangxiaoguang@kdqugou.com
     */
    protected function getFilter() {
        $condition = '1=1';
        if ($this->request->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= ' AND id = ' . intval($search['id']);
            }
            if (isset($search['project_id']) && !empty($search['project_id'])) {
                $condition .= ' AND project_id = ' . intval($search['project_id']);
            }
            if (isset($search['bank_name']) && !empty($search['bank_name'])) {
                $condition .= " AND bank_name like '%".$search['bank_name']."%'";
            }
            if (isset($search['status']) && ($search['status'] != '')) {
                $condition .= ' AND status = ' . intval($search['status']);
            }
            if (isset($search['buy_time_start']) && !empty($search['buy_time_start'])) {
                $condition .= ' AND buy_time >= ' . strtotime($search['buy_time_start']);
            }
            if (isset($search['buy_time_end']) && !empty($search['buy_time_end'])) {
                $condition .= ' AND buy_time < ' . strtotime($search['buy_time_end']);
            }
            if (isset($search['expire_time_start']) && !empty($search['expire_time_start'])) {
                $condition .= ' AND expire_time >= ' . strtotime($search['expire_time_start']);
            }
            if (isset($search['expire_time_end']) && !empty($search['expire_time_end'])) {
                $condition .= ' AND expire_time < ' . strtotime($search['expire_time_end']);
            }
            if (isset($search['project_status']) && !empty($search['project_status'])) {
                $condition .= ($search['project_status'] == 1) ? ' AND (project_id =  0 or project_id is null )' : ' AND project_id != ' . 0;
            }
        }
        return $condition;
    }

    public function actionBankSet(){
        $this->response->format = Response::FORMAT_JSON;
        $key = $_GET['key'];
        $bank_name = @FinancialDraft::$bank_list[$key]['bank_name'];
        $bank_username = @FinancialDraft::$bank_list[$key]['bank_username'];
        return array(
            'bank_name' => $bank_name,
            'bank_username' => $bank_username,
        );
    }

    /**
     * 删除银行理财
     * @return string
     * @param $id       票据ID
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductDel($id)
    {
        $id = intval($id);
        if(!empty($id)){
            $model = FinancialDraft::find()->where("id = ".$id)->andWhere("type = ".FinancialDraft::PRODUCT_TYPE_BANK)->one();
            if($model->delete()){
                return $this->redirectMessage("删除成功", self::MSG_SUCCESS, Url::toRoute('bank-product/bank-product-list'));
            }
        }
        return $this->redirectMessage("抱歉，删除失败！", self::MSG_ERROR, Url::toRoute('bank-product/bank-product-list'));
    }

    /**
     * 查看银行理财
     * @param $id       银行理财ID
     * @return string
     * @throws NotFoundHttpException
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductView($id)
    {
        $draft = FinancialDraft::find()->where(['id' => intval($id)])->andWhere("type =".FinancialDraft::PRODUCT_TYPE_BANK)->one();
        if (!isset($draft) && empty($draft)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->render('/draft/bank-product-view', [
            'draft' => $draft
        ]);
    }

    /**
     * 编辑银行理财
     * @param $id       银行理财ID
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductEdit($id)
    {
        $draft = FinancialDraft::find()->where(['id' => intval($id)])->one();
        if (!isset($draft) && empty($draft)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $draft->load($this->request->post());
            $message = $this->request->post();
            $bank_name = $message['bank_name'];
            $bank_username = $message['bank_username'];
            $bank_number = $message['bank_number'];
            $draft->bank_name = $bank_name;
            if(!empty($draft->project_id)){
                $project = Project::findOne($draft->project_id);
                if(empty($project)){
                    return $this->redirectMessage('抱歉，不存在此项目ID，编辑失败！', self::MSG_ERROR);
                }
            }
            $draft->bank_username = $bank_username;
            $draft->bank_number = $bank_number;
            $draft->buy_time = strtotime($draft->buy_time);
            $draft->expire_time = strtotime($draft->expire_time);
            $draft->start_time = strtotime($draft->start_time);
            $draft->draft_account = StringHelper::safeConvertCentToInt($draft->draft_account);
            $draft->user_profits = StringHelper::safeConvertCentToInt($draft->user_profits);
            $draft->true_money = StringHelper::safeConvertCentToInt($draft->true_money);
            $draft->img_url = FinancialDraft::getImgUrl($draft->img_url);
            $draft->updated_at = time();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($draft->validate() && $draft->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('编辑银行理财成功', self::MSG_SUCCESS, Url::toRoute('bank-product/bank-product-list'));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('编辑银行理财失败', self::MSG_ERROR);
            }
        }
        $draft->buy_time = date('Y-m-d', $draft->buy_time);
        $draft->expire_time = date('Y-m-d', $draft->expire_time);
        $draft->start_time = date('Y-m-d', $draft->start_time);
        $draft->draft_account = StringHelper::safeConvertIntToCent($draft->draft_account);
        $draft->user_profits = StringHelper::safeConvertIntToCent($draft->user_profits);
        $draft->true_money = StringHelper::safeConvertIntToCent($draft->true_money);
        return $this->render('/draft/bank-product-edit', [
            'draft' => $draft,
            'type' => 'edit',
        ]);
    }

    /**
     * 银行理财还款
     * @param $id       银行理财ID
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductRepay($id)
    {
        $draft = FinancialDraft::find()->where(['id' => intval($id)])->one();
        if (!isset($draft) && empty($draft)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $draft->load($this->request->post());
            if(!empty($draft->project_id)){
                $project = Project::findOne($draft->project_id);
                if(empty($project)){
                    return $this->redirectMessage('抱歉，不存在此项目ID，编辑失败！', self::MSG_ERROR);
                }
            }
            $draft->status = FinancialDraft::STATUS_INTEREST_COLLECTION;
            $draft->true_money = StringHelper::safeConvertCentToInt($draft->true_money);
            $draft->updated_at = time();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($draft->validate() && $draft->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('银行理财还款成功', self::MSG_SUCCESS, Url::toRoute('bank-product/bank-product-list'));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('银行理财还款失败', self::MSG_ERROR);
            }
        }
        $draft->buy_time = date('Y-m-d', $draft->buy_time);
        $draft->expire_time = date('Y-m-d', $draft->expire_time);
        $draft->start_time = date('Y-m-d', $draft->start_time);
        $draft->draft_account = StringHelper::safeConvertIntToCent($draft->draft_account);
        $draft->user_profits = StringHelper::safeConvertIntToCent($draft->user_profits);
        $draft->true_money = StringHelper::safeConvertIntToCent($draft->true_money);
        return $this->render('/draft/bank-product-repay', [
            'draft' => $draft,
            'type' => 'repay',
        ]);
    }

    /**
     * 审核银行理财
     * @param $id       银行理财ID
     * @return string
     * @throws NotFoundHttpException
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductReview($id)
    {
        $draft = FinancialDraft::find()->where(['id' => intval($id)])->one();
        if (!isset($draft) && empty($draft)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $type = $this->getRequest()->get("type");
        if($type == 'edit'){
            $status = $this->getRequest()->get("status");
            $draft->review_username = Yii::$app->user->identity->username;
            $draft->review_time = time();
            $draft->status = $status;
            if($draft->save()){
                return $this->redirectMessage("提交成功", self::MSG_SUCCESS, Url::toRoute('bank-product/bank-product-review-list'));
            }
            return $this->redirectMessage("提交失败", self::MSG_ERROR);
        }
        return $this->render('/draft/bank-product-review', [
            'draft' => $draft,
        ]);
    }

    /**
     * 银行理财待审核列表
     * @return string
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionBankProductReviewList()
    {
        $condition = $this->getFilter();
        $query = FinancialDraft::find()->where($condition)->andWhere("type =".FinancialDraft::PRODUCT_TYPE_BANK)
            ->andWhere("status = ".FinancialDraft::STATUS_INTEREST_REVIEW)->orderBy([
                'buy_time' => SORT_DESC,
            ]);
        $query_count = clone $query;
        $pages = new Pagination(['totalCount' => $query_count->count()]);
        $pages->pageSize = 20;
        $draft_list = $query->with([
            'project'=> function($queryProject) {
                    $queryProject->select(['id','status']);
                }
        ])->offset($pages->offset)->limit($pages->limit)->all();
        $user_profits = 0;
        $sum = $query_count->select(['sum(true_money)', 'sum(draft_account)', 'sum(user_profits)'])->asArray()->all();
        $query = "select distinct project_id, user_profits from ".FinancialDraft::tableName()." where status = ".FinancialDraft::STATUS_INTEREST_REVIEW." and type = ".FinancialDraft::PRODUCT_TYPE_BANK;
        $result = Yii::$app->db_kdkj->createCommand($query)->queryAll();
        foreach($result as $k => $v){
            $user_profits += $v['user_profits'];
        }
        $sum[0]['user_profits'] = $user_profits;
        $sum[0]['draft_account_repay'] = 0;
        $sum[0]['project_money'] = 0;
        $sum[0]['user_repaying_profits'] = 0;
        $sum[0]['user_repayed_profits'] = 0;

        return $this->render('/draft/bank-product-list', [
            'draft_list' => $draft_list,
            'pages' => $pages,
            'sum' => $sum[0],
            'type' => 'review'
        ]);
    }

}