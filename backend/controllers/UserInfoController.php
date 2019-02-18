<?php
namespace backend\controllers;

use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use common\helpers\Url;
use yii\db\Query;

use backend\helpers\loanrepayment\loanRepaymentFactory;
use common\models\AccumulationFund;
use common\models\CardInfo;
use common\models\LimitApply;
use common\models\LoanPerson;
use common\models\UserCreditDetail;
use common\models\UserCreditOperateLog;
use common\models\UserCreditReviewLog;
use common\models\UserCreditTotal;
use common\models\UserInterestLog;
use common\models\UserRentCredit;
use common\models\LoanRecordPeriod;
use common\models\LoanRepayment;
use common\models\LoanRepaymentPeriod;
use backend\models\LoanCollectionRecord;
use common\models\Shop;
use common\models\UserCredit;
use common\models\UserCreditLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use common\models\UserShellInterestErrorLog;
use common\models\UserVerification;
use common\helpers\MessageHelper;
use yii\helpers\VarDumper;


class UserInfoController extends BaseController {

    /**
     * @name 用户管理-额度管理-信用额度列表/actionLoanList
     */
    public function actionLoanList() {
        $condition = $this->getRepaymentPeriodFilter();
        $query = LoanPerson::find()->select(['id', 'name', 'phone'])->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;

        $count= 9999999;
//        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
//            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
//        }, 3600);

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);

        $loan_collection_list = $query->with([
            'userCreditTotal' => function(Query $query) {
                $query -> select(['user_id','amount','used_amount','locked_amount','pocket_apr','house_apr','installment_apr', 'counter_fee_rate']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('loan-list', array(
            'loan_collection_list' => $loan_collection_list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户借款信息列表过滤
     */
    protected function getRepaymentPeriodFilter()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND phone = " . trim($search['phone']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND name like '%". $search['name'] ."%'";
            }
            return $condition;
        }
    }

    /**
     * @name 审核修改用户额度
     */
    public function actionAmountReview($id) {
        $list = UserCreditReviewLog::findOne($id);
        $loan_person = LoanPerson::find()->where(['id' => $list['user_id']])->one(Yii::$app->get('db_kdkj_rd'));
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                $operation = Yii::$app->request->post("operation");
                $remark = Yii::$app->request->post("remark");
                if ($operation == 1) {
                    $list->status = UserCreditReviewLog::STATUS_PASS;
                    $list->operator_name = Yii::$app->user->identity->username;
                    $list->remark = $remark;
                    if($list['type'] == UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT) {
                        $user_credit_total = UserCreditTotal::findOne(['user_id' => $list['user_id']]);
                        $user_credit_total->amount = $list['after_number'];
                        $user_credit_total->operator_name = $list['creater_name'];
                        if(!$user_credit_total->save() || !$list->save()) {
                            $transaction->rollBack();
                            return $this->redirectMessage('审核失败', self::MSG_ERROR);
                        }
                    }
                    if($list['type'] == UserCreditReviewLog::TYPE_POCKET_APR) {
                        $user_credit_total = UserCreditTotal::findOne(['user_id' => $list['user_id']]);
                        $user_credit_total->pocket_apr = $list['after_number'];
                        $user_credit_total->operator_name = $list['creater_name'];
                        if(!$user_credit_total->save() || !$list->save()) {
                            $transaction->rollBack();
                            return $this->redirectMessage('审核失败', self::MSG_ERROR);
                        }
                    }
                    if($list['type'] == UserCreditReviewLog::TYPE_HOUSE_APR) {
                        $user_credit_total = UserCreditTotal::findOne(['user_id' => $list['user_id']]);
                        $user_credit_total->house_apr = $list['after_number'];
                        $user_credit_total->operator_name = $list['creater_name'];
                        if(!$user_credit_total->save() || !$list->save()) {
                            $transaction->rollBack();
                            return $this->redirectMessage('审核失败', self::MSG_ERROR);
                        }
                    }
                    if($list['type'] == UserCreditReviewLog::TYPE_INSTALLMENT_APR) {
                        $user_credit_total = UserCreditTotal::findOne(['user_id' => $list['user_id']]);
                        $user_credit_total->installment_apr = $list['after_number'];
                        $user_credit_total->operator_name = $list['creater_name'];
                        if(!$user_credit_total->save() || !$list->save()) {
                            $transaction->rollBack();
                            return $this->redirectMessage('审核失败', self::MSG_ERROR);
                        }
                    }
                } else {
                    $list->status = UserCreditReviewLog::STATUS_REJECT;
                    $list->operator_name = Yii::$app->user->identity->username;
                    $list->remark = $remark;
                    if(!$list->save()) {
                        $transaction->rollBack();
                        return $this->redirectMessage('审核失败', self::MSG_ERROR);
                    }
                }
                $transaction->commit();
                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute(['user-info/credit-review-list']));
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('审核失败', self::MSG_ERROR);
        }

        return $this->render('review-user',[
            'loan_person' => $loan_person,
            'list' => $list,
        ]);
    }

    /**
     * @param $id
     * @name 用户管理-额度管理-信用额度列表-编辑
     */
    public function actionAmountEdit($id)
    {
        $user_credit_total = UserCreditTotal::findOne(['user_id' => $id]);
        if(false == $user_credit_total){
            return ;
        }
        $user_credit_total->amount = $user_credit_total->amount/100;
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                $amount = intval(bcmul(Yii::$app->request->post('operate_amount'), 100));
                $operate_pocket_apr = Yii::$app->request->post('operate_pocket_apr');
                $operate_fee_apr = Yii::$app->request->post('operate_counter_fee_rate');
                $update_credit = [];
                if(!empty($amount)){
                    $review_log_pm = new UserCreditReviewLog();
                    if(Yii::$app->request->post('pm_algorithm') == 1){
                        $review_log_pm->operate_number = $amount;
                    }else{
                        $review_log_pm->operate_number = -$amount;
                    }
                    $review_log_pm->before_number = $user_credit_total->amount*100;
                    $review_log_pm->after_number = $user_credit_total->amount*100 + $review_log_pm->operate_number;
                    if ($review_log_pm->after_number < 0) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败：额度不能小于0', self::MSG_ERROR);
                    }
                    $review_log_pm->user_id = $id;
                    $review_log_pm->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
                    $review_log_pm->status = UserCreditReviewLog::STATUS_PASS;
                    $review_log_pm->creater_name = Yii::$app->user->identity->username;
                    $review_log_pm->operator_name = Yii::$app->user->identity->username;
                    $review_log_pm->created_at = time();
                    if(!$review_log_pm->save()) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败', self::MSG_ERROR);
                    }
                    $update_credit['amount'] = $review_log_pm->after_number;
                }
                if(!empty($operate_pocket_apr)){
                    $review_log_pa = new UserCreditReviewLog();
                    if (Yii::$app->request->post('pa_algorithm') == 1) {
                        $review_log_pa->operate_number = $operate_pocket_apr;
                    } else {
                        $review_log_pa->operate_number = -$operate_pocket_apr;
                    }
                    $review_log_pa->before_number = $user_credit_total->pocket_apr;
                    $review_log_pa->after_number = $user_credit_total->pocket_apr + $review_log_pa->operate_number;
                    if ($review_log_pa->after_number < 0) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败：利率不能小于0', self::MSG_ERROR);
                    }
                    $review_log_pa->user_id = $id;
                    $review_log_pa->type = UserCreditReviewLog::TYPE_POCKET_APR;
                    $review_log_pa->status = UserCreditReviewLog::STATUS_PASS;
                    $review_log_pa->creater_name = Yii::$app->user->identity->username;
                    $review_log_pa->operator_name = Yii::$app->user->identity->username;
                    $review_log_pa->created_at = time();
                    if(!$review_log_pa->save()) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败', self::MSG_ERROR);
                    }
                    $update_credit['pocket_apr'] = $review_log_pa->after_number;
                }

                if(!empty($operate_fee_apr)){
                    $review_log_fee = new UserCreditReviewLog();
                    if (Yii::$app->request->post('fee_algorithm') == 1) {
                        $review_log_fee->operate_number = $operate_fee_apr;
                    } else {
                        $review_log_fee->operate_number = -$operate_fee_apr;
                    }
                    $review_log_fee->before_number = $user_credit_total->counter_fee_rate;
                    $review_log_fee->after_number = $user_credit_total->counter_fee_rate + $review_log_fee->operate_number;
                    if ($review_log_fee->after_number < 0) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败：费率不能小于0', self::MSG_ERROR);
                    }
                    $review_log_fee->user_id = $id;
                    $review_log_fee->type = UserCreditReviewLog::TYPE_CREDIT_COUNTER_FEE_RATE;
                    $review_log_fee->status = UserCreditReviewLog::STATUS_PASS;
                    $review_log_fee->creater_name = Yii::$app->user->identity->username;
                    $review_log_fee->operator_name = Yii::$app->user->identity->username;
                    $review_log_fee->created_at = time();
                    if(!$review_log_fee->save()) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败', self::MSG_ERROR);
                    }
                    $update_credit['counter_fee_rate'] = $review_log_fee->after_number;
                }


                if($update_credit) {
                    $user_credit_total->updateAttributes($update_credit);
                }

                $transaction->commit();
                return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute(['user-info/loan-list']));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('编辑失败', self::MSG_ERROR);
        }

        return $this->render('amount-edit',[
            'user_credit_total' => $user_credit_total,
        ]);
    }

    /**
     * @name 用户管理-额度管理-信用额度审核/actionCreditReviewList
     */
    public function actionCreditReviewList() {
        $condition = '1 = 1 ';
        if ($this->request->isPost) {
            $search = $this->request->post();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . $search['phone'];
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND b.name like '%" . $search['name']."%'";
            }
            if (isset($search['status']) && $search['status'] != NULL) {
                $condition .= " AND a.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] != NULL) {
                $condition .= " AND a.type = " . intval($search['type']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " .strtotime($search['endtime']);
            }
        }

        $query = UserCreditReviewLog::find()
            ->from(UserCreditReviewLog::tableName().'as a')
            ->leftJoin(LoanPerson::tableName().'as b', 'a.user_id = b.id')
            ->where($condition)
            ->select('a.*, b.name, b.phone')
            ->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;
        $count = 99999999;
//        $count = $countQuery->count('*', \yii::$app->get('db_kdkj_rd'));
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);

        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('review-list', [
            'list' => $list,
            'pages' => $pages,
        ]);
    }

    /**
     * @param $id
     * @name 用户管理-额度管理-信用额度审核-查看/actionAmountView
     */
    public function actionAmountView($id)
    {
        $list = UserCreditReviewLog::findOne($id);
        $loan_person = LoanPerson::find()->where(['id' => $list['user_id']])->one(Yii::$app->get('db_kdkj_rd'));

        return $this->render('review-view',[
            'loan_person' => $loan_person,
            'list' => $list,
        ]);
    }

    /**
     * @return string
     * @name 用户管理-额度管理-信用额度调整流水/actionCreditModifyLog
     */
    public function actionCreditModifyLog()
    {
        $condition = '1 = 1 and a.type = '.UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT.' and a.status ='.UserCreditReviewLog::STATUS_PASS;
        $u_id=$this->request->get('user_id');
        if(!empty($u_id))
        {
            $condition .= " AND a.user_id = " . intval($u_id);
        }
        if ($this->getRequest()->getIsPost()) {
            $search = $this->request->post();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . $search['phone'];
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND b.name like '%" . $search['name']."%'";
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " .strtotime($search['endtime']);
            }
        }
        $query = UserCreditReviewLog::find()->from(UserCreditReviewLog::tableName().'as a')->leftJoin(LoanPerson::tableName().'as b',' a.user_id = b.id')->where($condition)->select('a.*,b.name,b.phone')->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->post('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = 99999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);

        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('credit-modify-log', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-利息管理-利息流水列表/actionInterestLog
     */
    public function actionInterestLog(){
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsPost()) {
            $search = $this->request->post();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . $search['phone'];
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND b.name like '%" . $search['name']."%'";
            }
            if (isset($search['type']) && !empty($search['type'])) {
                $condition .= " AND a.type = " . intval($search['type']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " .strtotime($search['endtime']);
            }
        }
        $query = UserInterestLog::find()
            ->from(UserInterestLog::tableName().'as a')
            ->leftJoin(LoanPerson::tableName().'as b', 'a.user_id = b.id')
            ->leftJoin(CardInfo::tableName().'as c', 'c.user_id = b.id')
            ->where($condition)
            ->select('a.*,b.name,b.phone,c.card_no')
            ->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;
        $count = 99999999;
//        $count = $countQuery->count('*', \yii::$app->get('db_kdkj_rd'));
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $loan_collection_list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('interest-list', array(
            'loan_collection_list' => $loan_collection_list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-额度管理-信用额度使用流水/actionAssetLog
     */
    public function actionAssetLog()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $loan_person = LoanPerson::findOne(['phone'=>$search['phone']]);
                if(!empty($loan_person)){
                    $condition .= " AND user_id = " . $loan_person['id'];
                }

            }
            if (isset($search['name']) && !empty($search['name'])) {
                $loan_person = LoanPerson::find()->where(['name' => $search['name']])->all(Yii::$app->get('db_kdkj_rd')); //->where(['like','name',$search['name']])
                if(!empty($loan_person)){
                    $ids = "";
                    foreach($loan_person as $item){
                        $ids .= $item['id'].",";
                    }
                    $ids = rtrim($ids,",");
                    $condition .= " AND user_id in (".$ids.")";
                }
            }
            if (isset($search['type']) && !empty($search['type'])) {
                $condition .= " AND type = " . intval($search['type']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND created_at <= " .strtotime($search['endtime']);
            }
        }
        $query = UserCreditLog::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = 99999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);

        $pages->pageSize = 15;
        $loan_collection_list = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        $data1 = [];
        $data2 = [];
        $data3 = [];
        foreach($loan_collection_list as $key => $item) {
            $loan_person = LoanPerson::find()->where(['id' => $item['user_id']])->select(['name','phone'])->one(Yii::$app->get('db_kdkj_rd'));
            $card = CardInfo::find()->where(['user_id' => $item['user_id']])->select(['card_no'])->one(Yii::$app->get('db_kdkj_rd'));
            $data1[$item['id']] = $loan_person['name'];
            $data2[$item['id']] = $loan_person['phone'];
            $data3[$item['id']] = $card['card_no'];
        }

        return $this->render('log-list', array(
            'loan_collection_list' => $loan_collection_list,
            'data1' => $data1,
            'data2' => $data2,
            'data3' => $data3,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-利息管理-利息错误日志/actionInterestErrorLog
     */
    public function actionInterestErrorLog()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND order_id = " . intval($search['order_id']);
            }
            if (isset($search['total_period_id']) && !empty($search['total_period_id'])) {
                $condition .= " AND repayment_id = " . intval($search['total_period_id']);
            }
            if (isset($search['period_id']) && !empty($search['period_id'])) {
                $condition .= " AND repayment_period_id = " . intval($search['period_id']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND status = " . intval($search['status']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND created_at <= " .strtotime($search['endtime']);
            }
        }
        $query = UserShellInterestErrorLog::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $loan_collection_list = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('error-log-list', array(
            'loan_collection_list' => $loan_collection_list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-利息管理-零钱包利息错误核对/actionLqdInterestErrorCheck
     */
    public function actionLqdInterestErrorCheck()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND order_id = " . intval($search['order_id']);
            }
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND status = " . intval($search['status']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND created_at <= " .strtotime($search['endtime']);
            }
            $condition = $condition ." and status <= ".UserLoanOrderRepayment::STATUS_NORAML;
            $condition = $condition . " and interest_time <".strtotime(date('Y-m-d',time()));
        }
        $query = UserLoanOrderRepayment::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $count = 9999999;
//        $count = $countQuery->count('id',Yii::$app->get('db_kdkj_rd'));
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $loan_collection_list = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('lqd-log-list', array(
            'loan_collection_list' => $loan_collection_list,
            'pages' => $pages,
        ));
    }

    /**
     * @name 借款管理-用户借款管理-提额申请列表/actionAddLimit
     */
    public function actionAddLimitList()
    {
        $condition = $this->getAddLimitFilter();
        $query = LimitApply::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $limit_apply_list = $query->
        with([
            'loanPerson' => function(Query $query) {
                $query -> select(['id','name','phone']);
            },
            'userCreditTotal'=>function(Query $query){
                $query -> select(['user_id','amount','increase_time','repayment_credit_add']);
            },
        ])->
        offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
        return $this->render('add-limit-list', array(
            'limit_apply_list' => $limit_apply_list,
            'pages' => $pages,
        ));
    }
    /**
     * 信用额度提额列表过滤
     */
    private function getAddLimitFilter()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND user_id = " . intval($search['id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $person = LoanPerson::find()->where(["phone" => $search['phone']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $person = LoanPerson::find()->where(["name" => $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
            if (isset($search['status']) && $search['status'] != NULL) {
                $condition .= " AND status = '" . $search['status']."'";
            }
            return $condition;
        }
    }

    /**
     * @name 借款管理-提额申请列表-审核/actionAddLimitTrail
     */
    public function actionAddLimitTrail()
    {
        $status=$this->request->get('status');
        $url=Yii::$app->homeUrl;
        $user_id=$this->request->get('user_id');
        $information = Yii::$container->get("loanPersonInfoService")->LimitPersonInfo($user_id);

        if($this->request->getIsPost())
        {
            $operation = $this->request->post('operation');
            $add_limit =intval($this->request->post('limit'))*100;
            $remark=$this->request->post('remark');
            $code=$this->request->post('code');
            $params=[];
            $params['remark']= $remark;
            $limit_apply=LimitApply::find()->where(['user_id'=>$user_id])->one(Yii::$app->get('db_kdkj_rd'));
            if($limit_apply['status']!=LimitApply::STATUS_TRIAL)
            {
                return $this->redirectMessage('已审核',self::MSG_ERROR);
            }
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            //审核通过
            if(intval($operation)==1)
            {
                //获取用户总额度
                $amount_limit=UserCreditTotal::find()->select('amount')->where(['user_id'=>$user_id])->one(Yii::$app->get('db_kdkj_rd'));
                if(($amount_limit['amount']+$add_limit)>200000){
                    return $this->redirectMessage('用户总额度不能大于2000',self::MSG_ERROR);
                }
                $limit_apply->operator_name = Yii::$app->user->identity->username;
                $limit_apply->status=LimitApply::STATUS_PASS;
                $limit_apply->remark =$remark;
                try {
                    if( $limit_apply->validate()) {
                        if($limit_apply->save()) {
                            UserCreditTotal::addCreditAmount($add_limit,$user_id,'',$params);
                            $userCreditReviewLog=new UserCreditReviewLog();
                            $userCreditReviewLog->user_id=$user_id;
                            $userCreditReviewLog->type=UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
                            $userCreditReviewLog->before_number=$amount_limit['amount'];
                            $userCreditReviewLog->operate_number=$add_limit;
                            $userCreditReviewLog->after_number=$amount_limit['amount']+$add_limit;
                            $userCreditReviewLog->status=UserCreditReviewLog::STATUS_PASS;
                            $userCreditReviewLog->operator_name=Yii::$app->user->identity->username;
                            $userCreditReviewLog->remark=$remark;
                            $userCreditReviewLog->created_at=time();
                            if(!$userCreditReviewLog->save()){
                                $transaction->callback();
                            }
                            $transaction->commit();
                            $user = LoanPerson::findOne($user_id);
//                             $phone_msg = '尊敬的'.$user->name.'，由于您的信用良好，信用额度已调整为'.($amount_limit['amount']/100+$add_limit/100).'元，感谢您的支持，良好的信用受益终生！';
                            $phone_msg = ' 尊敬的'.$user->name.'，您的'.APP_NAMES.'信用额度已调整为'.($amount_limit['amount']/100+$add_limit/100).'元，感谢您对我们的支持。';
                            MessageHelper::sendSMS($user->phone, $phone_msg, 'smsServiceXQB_XiAo', $user->source_id);
                            return $this->redirectMessage('审核成功', self::MSG_SUCCESS, "$url.?r=user-info%2Fadd-limit-list&status=$status");
                        }
                    } else {
                        throw new Exception;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败'.$e, self::MSG_ERROR);
                }
            }elseif(intval($operation)==2)
            {
                $limit_apply->status=LimitApply::STATUS_NO;
                $limit_apply->operator_name=Yii::$app->user->identity->username;
                $limit_apply->remark = !empty($code) ? (LimitApply::$code[$code]) : $remark;
                if($limit_apply->save())
                {
                    $transaction->commit();
                    return $this->redirectMessage('审核成功', self::MSG_SUCCESS, "$url.?r=user-info%2Fadd-limit-list&status=$status");
                }

                return $this->redirectMessage('审核失败', self::MSG_ERROR);

            }
        }

        return $this->render('add-limit-trail',array(
            'information'=>$information,
        ));
    }

    /**
     * @return string
     * @name 用户管理-用户认证列表/actionUserVerificationList
     */
    public function actionUserVerificationList()
    {
        $condition =$this->getUserVerificationFilter();
        $query = UserVerification::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson' => function(Query $query) {
                $query->select(['id', 'name','phone']);
            },
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        //echo $query->createCommand()->getRawSql();
        return $this->render('user-verification-list',
            [
                'info' => $info,
                'pages' => $pages,
            ]);
    }
    public function getUserVerificationFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['user_id'])) {
                $condition .= " AND user_id = ".intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $person = LoanPerson::find()->where(["phone" => $search['phone']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $person = LoanPerson::find()->where(["name" => $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
        }
        return $condition;
    }

    /**
     * @name 待人工确认额度列表
     */
    public function actionAmountWaitList()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . $search['phone'];
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND b.name like '%" . $search['name']."%'";
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " .strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " .strtotime($search['endtime']);
            }
        }
        $condition .= " AND c.credit_status = " . UserCreditDetail::STATUS_WAIT;
        $query = UserCreditTotal::find()
            ->from(UserCreditTotal::tableName().'as a')
            ->leftJoin(LoanPerson::tableName().'as b', 'a.user_id = b.id')
            ->leftJoin(UserCreditDetail::tableName().'as c', 'a.user_id = c.user_id')
            ->where($condition)
            ->select('a.*, b.name, b.phone')
            ->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', \yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        foreach ($list as $k => $v) {
            $list[$k]['house_fund_id'] = AccumulationFund::findLatestOne(['user_id' => $v['user_id']])->id;
        }

        return $this->render('amount-wait-list', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @name 人工确认额度
     * @return string
     */
    public function actionAmountWaitEdit($id)
    {
        $user_credit_total = UserCreditTotal::findOne(['user_id' => $id]);
        if(false == $user_credit_total){
            return $this->redirectMessage('用户额度不存在', self::MSG_ERROR);
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                $amount = intval(bcmul(Yii::$app->request->post('operate_amount'), 100));
                if(!empty($amount)){
                    if(Yii::$app->request->post('pm_algorithm') == 1){
                        $operate_number = $amount;
                    } else {
                        $operate_number = - $amount;
                    }

                    $review_log_pm = new UserCreditReviewLog();

                    $review_log_pm->operate_number = $operate_number;
                    $review_log_pm->before_number = $user_credit_total->amount;
                    $review_log_pm->after_number = $user_credit_total->amount + $review_log_pm->operate_number;
                    $review_log_pm->user_id = $id;
                    $review_log_pm->type = UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT;
                    $review_log_pm->status = UserCreditReviewLog::STATUS_PASS;
                    $review_log_pm->creater_name = Yii::$app->user->identity->username;
                    $review_log_pm->operator_name = Yii::$app->user->identity->username;
                    $review_log_pm->created_at = time();

                    if(!$review_log_pm->save()) {
                        $transaction->rollBack();
                        return $this->redirectMessage('编辑失败', self::MSG_ERROR);
                    }

                    $user_credit_total->amount = $user_credit_total->amount + $operate_number;
                }

                $user_credit_detail = UserCreditDetail::findOne(['user_id' => $id]);
                $user_credit_detail->credit_status = UserCreditDetail::STATUS_FINISH;

                if(!$user_credit_total->save() || !$user_credit_detail->save()) {
                    $transaction->rollBack();
                    return $this->redirectMessage('编辑失败', self::MSG_ERROR);
                }
                $transaction->commit();
                return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute(['user-info/amount-wait-list']));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('编辑失败', self::MSG_ERROR);
        }

        $user_credit_total->amount = $user_credit_total->amount / 100;
        return $this->render('amount-wait-edit',[
            'user_credit_total' => $user_credit_total,
        ]);
    }
}
