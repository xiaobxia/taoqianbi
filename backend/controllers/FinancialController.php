<?php
namespace backend\controllers;

use common\exceptions\PayException;
use common\helpers\BuildingMessageHelper;
use common\helpers\MessageHelper;
use common\helpers\StringHelper;
use common\models\AutoDebitLog;
use common\models\BankConfig;
use common\models\CardInfo;
use common\models\DebitRecordStatistics;
use common\models\FinancialDebitRecord;
use common\models\FinancialInstallmentRecord;
use common\models\FinancialLoanRecord;
use common\models\FinancialLog;
use common\models\FinancialRefundLog;
use common\models\HfdHouse;
use common\models\HfdNotarization;
use common\models\HfdOrder;
use common\models\HfdOrderCheckFlow;
use common\models\LoanHfdOrder;
use common\models\LoanPerson;
use common\services\LoanService;
use common\models\LoanTask;
use common\models\RepayRatesList;
use common\models\LoanRecordPeriod;
use common\models\MessageLog;
use common\models\Order;
use common\models\RidOverdueLog;
use common\models\Shop;
use common\models\SuspectDebitLostRecord;
use common\models\UserCreditMoneyLog;
use common\models\UserCreditReviewLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserVerification;
use common\services\AutoDebitService;
use common\services\FinancialCommonService;
use common\services\FinancialService;
use common\services\fundChannel\JshbService;
use common\services\OrderService;
use common\services\PayService;
use common\services\YeePayService;
use common\soa\ServerSoa;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\helpers\Json;
use yii\web\Response;
use common\models\User;
use common\models\UserLoanOrder;
use common\models\HfdFinancialRecord;
use common\services\MongoService;
use common\models\AlipayRepaymentLog;
use common\models\WeixinRepaymentLog;
use common\helpers\Util;
use common\models\FinancialSubsidiaryLedger;
use common\models\FinancialReconcillationRecord;
use common\models\CustomReconcillationRecord;
use common\models\CreditFacePlusLog;
use common\models\CreditZmop;
use common\models\CreditTdLog;
use common\models\FinancialExpense;
use common\models\asset\AssetOrder;
use common\models\fund\LoanFund;
use common\models\fund\OrderFundInfo;
use common\models\FinancialManReview;
use common\models\stats\DayNotYetPrincipalStatistics;
use yii\data\SqlDataProvider;
use backend\helpers\LExcelHelper;

class FinancialController extends  BaseController
{
    protected $financialService;

    public function __construct($id, $module, FinancialService $financialService, $config = []) {
        $this->financialService = $financialService;
        parent::__construct($id, $module, $config);
    }

    public function getFilter() {
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['username'])) {
                $username = $search['username'];
                $result = LoanPerson::find()->select(['id'])->where(['name' => $username])->all();
                if ($result) {
                    $uid = [];
                    foreach($result as $id){
                        $uid[] = $id['id'];
                    }
                    $uid = implode(',',$uid);
                    $condition .= " AND l.user_id in ({$uid}) ";
                }else{
                    $condition .= " AND l.user_id = 0" ;
                }
            }
            if (!empty($search['phone'])) {
                $phone = $search['phone'];
                $result = LoanPerson::find()->select(['id'])->where(['phone' => $phone])->asArray()->all();
                if(!empty($result)){
                    $user_list = [];
                    foreach($result as $v){
                        $user_list[] = $v['id'];
                    }
                }else{
                    $user_list = [0];
                }
                $user_list = implode(',',$user_list);
                $condition .= " AND l.user_id in ({$user_list})";
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = " . intval($search['user_id']);
            }
            if (!empty($search['rid'])) {
                $condition .= " AND l.id = " . "'".$search['rid']."'";
            }
            if (!empty($search['loan_term'])) {
                $condition .= " AND u.loan_term = " . "'".$search['loan_term']."'";
            }
            if (!empty($search['loan_amount_min'])) {
                $condition .= " AND l.money >= " . $search['loan_amount_min'] * 100;
            }
            if (!empty($search['loan_amount_max'])) {
                $condition .= " AND l.money <= " . $search['loan_amount_max'] * 100;
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.business_id = " . "'".$search['order_id']."'";
            }
            if (!empty($search['source_id'])) {
                $condition .= " AND p.source_id = " . "'".$search['source_id']."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND l.order_id = " . "'".trim($search['pay_order_id'])."'";
            }
            if (!empty($search['status'])) {
                $condition .= " AND l.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] != null) {
                $condition .= " AND l.type = " . intval($search['type']);
            }
            if (!empty($search['payment_type'])) {
                $condition .= " AND l.payment_type = " . intval($search['payment_type']);
            }
            if (isset($search['review_result']) && $search['review_result'] !== '') {
                $condition .= " AND l.review_result = " . intval($search['review_result']);
                $is_review_result = true;
            }
            if (isset($search['callback_result']) && $search['callback_result'] !== '') {
                if($search['callback_result']){
                    $condition .= " AND l.callback_result like '{\"is_notify\":".intval($search['callback_result']).",%'" ;
                }else{
                    $condition .= " AND l.callback_result = '0'" ;
                }
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at < " . strtotime($search['endtime']);
            }
            if (!empty($search['updated_at_begin'])) {
                $condition .= " AND l.success_time >= " . strtotime($search['updated_at_begin']);
            }
            if (!empty($search['updated_at_end'])) {
                $condition .= " AND l.success_time < " . strtotime($search['updated_at_end']);
            }
            if (isset($search['fund_id']) && !empty($search['fund_id']) && $search['fund_id'] >0) {
                if($search['fund_id'] == LoanFund::ID_KOUDAI){
                    $condition .= " AND u.fund_id IN (" . LoanFund::ID_KOUDAI .", 0 ) ";
                }else{
                    $condition .= " AND u.fund_id = " . (int)$search['fund_id'];
                }
            }
        }

        return $condition;
    }

    /**
     * @name 财务管理-小钱包打款扣款管理-打款列表/actionLoanList
     */
    public function actionLoanList($view = 'list') {
        if ($this->request->get('submitcsv') == 'exportcsv') {
            return $this->_exportLoanInfos(FinancialLoanRecord::$kd_platform_type);
        }
        else if ($this->request->get('submitcsv') == 'export_direct') {
            return $this->_exportLoanInfosDirect(FinancialLoanRecord::TYPE_LQD);
        }

        $condition = $this->getFilter();
        $query = FinancialLoanRecord::find()
            ->from(FinancialLoanRecord::tableName().' as l')
            ->where(['in', 'l.type', FinancialLoanRecord::$kd_platform_type])
            ->andwhere($condition)
            ->select(['l.*','l.id as rid','p.name','p.source_id','u.loan_term','u.loan_method','u.fund_id','r.coupon_money'])
            ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
            ->leftJoin(UserLoanOrder::tableName().' as u','l.business_id=u.id')
            ->leftJoin(UserLoanOrderRepayment::tableName().' as r','l.business_id=r.order_id')
            ->orderBy(['l.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        if ($condition == '1=1' && $this->request->get('is_summary')!=1) { //无任何条件 设为130万
            $count = 1300000;
        }
        else {
            /* @var $db \yii\db\Connection */
            /*$count = $db->cache(function($db) use ($countQuery) {
				return $countQuery->count('1',$db);
			}, 3600);*/
            $count = 9999;
        }

        $dataSt = '';
        if ($this->request->get('is_summary')==1) {
            $dataSt = $countQuery->select('sum(l.money) as money,sum(l.counter_fee) as counter_fee')->one($db);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);

        $type = FinancialLoanRecord::$types;
        foreach (FinancialLoanRecord::$other_platform_type as $t) {
            unset($type[$t]);
        }

        new LoanPerson();
        return $this->render('loan-list', [
            'withdraws' => $data,
            'pages' => $pages,
            'type' => $type,
            'view' => $view,
            'dataSt'=>$dataSt,
            'export' => 1
        ]);
    }


    /**
     * @return string
     * @name 财务管理-小钱包打款扣款管理-打款审核/actionLoanWithdrawList
     */
    public function actionLoanWithdrawList(){
        $_GET['review_result'] = FinancialLoanRecord::REVIEW_STATUS_NO;
        $_GET['search_submit'] = 1;
        return $this->actionLoanList('review');
    }

    /**
     * @name 重置状态
     */
    public function actionResetStatus() {
        $id = $this->request->get("id");
        $key="financial_loan_record_{$id}";
        $financial_loan_record_cache=RedisQueue::get(['key'=>$key]);
        if(!empty($financial_loan_record_cache)){
            if(time()-$financial_loan_record_cache<2*60){
                return $this->redirectMessage("操作失败,30分钟后操作", self::MSG_ERROR);
            }
        }
        $user_order = FinancialLoanRecord::findOne($id);
        if ($user_order && $user_order->status!=FinancialLoanRecord::UMP_PAY_SUCCESS && time()-$user_order->updated_at>40*60) {
            $user_order->order_id=$user_order->order_id.'1';
            $user_order->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
            $user_order->status = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
            $user_order->updated_at = time();
            $user_order->review_time = time();
            if($user_order->save()){
                //过期时间
                $expire=3600;
                RedisQueue::set(['expire'=>$expire,'key'=>$key,'value'=>time()]);
                if (in_array($user_order['type'], FinancialLoanRecord::$kd_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                } elseif (in_array($user_order['type'], FinancialLoanRecord::$other_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                }
            }
        }
        return $this->redirectMessage("操作失败", self::MSG_ERROR);
    }

    /**
     * 取消订单
     **/
    public function actionCancelOrder(){
        $id = $this->request->get("id");
        $user_order = FinancialLoanRecord::findOne($id);
        if ($user_order && $user_order->status!=FinancialLoanRecord::UMP_PAY_SUCCESS && time()-$user_order->updated_at>40*60 && time()-$user_order->created_at>40*60) {
            $user_order->status = FinancialLoanRecord::UMP_PAY_FAILED;
            $user_order->updated_at = time();
            $user_order->review_time = time();
            if($user_order->save()){
                $business_id=$user_order->business_id;
                $user_id=$user_order->user_id;
                $userloanorder=UserLoanOrder::findOne($business_id);
                $userloanorder->status = UserLoanOrder::STATUS_CANCEL;
                if($userloanorder->save()){
                    $usercredittotal=UserCreditTotal::find()->where(['user_id'=>$user_id])->one();
                    $usercredittotal->used_amount=0;
                    $usercredittotal->locked_amount=0;
                    if($usercredittotal->save()){
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                    }
                }
            }
        }
        return $this->redirectMessage("操作失败", self::MSG_ERROR);
    }

    /**
     * 更换新卡
     * @param $id FinancialLoanRecord
     * @param $user_id
     * @return string
     * @name ...
     */
    public function actionSetNewCard(){
        $id = $this->request->get("id");
        $user_id = $this->request->get("user_id");
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $FinancialLoanRecord = FinancialLoanRecord::findOne($id);
            $CardInfo = CardInfo::find()->where(['user_id'=>$user_id, 'main_card'=>1])->one();
            $UserLoanOrder = UserLoanOrder::findOne($FinancialLoanRecord->business_id);
            $UserLoanOrder->card_id = $CardInfo->id;
            $FinancialLoanRecord->bank_id = $CardInfo->bank_id;
            $FinancialLoanRecord->bank_name = $CardInfo->bank_name;
            $FinancialLoanRecord->card_no = $CardInfo->card_no;
            $FinancialLoanRecord->status = FinancialLoanRecord::UMP_PAYING;
            if($FinancialLoanRecord->save() && $UserLoanOrder->save()){
                $transaction->commit();
                return $this->redirectMessage("换卡成功", self::MSG_SUCCESS ,Url::toRoute('financial/loan-list'));
            }
        }catch (\Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage("操作失败".$e->getMessage(), self::MSG_ERROR);
        }



    }
    /**
     * @name 置为回调成功
     */
    public function actionSetCallbackSuccess()
    {
        $id = $this->request->get("id");
        $user_order = FinancialLoanRecord::findOne($id);
        if($user_order->callback_result){
            $notify =  json_decode($user_order->callback_result, true);
            if($notify){
                $notify['is_notify'] = FinancialLoanRecord::NOTIFY_SUCCESS;
                $notify['operate'] = Yii::$app->user->identity->username;
            }
            $user_order->callback_result = json_encode($notify);
            $user_order->updated_at = time();
            if($user_order->save()){
                if (in_array($user_order['type'], FinancialLoanRecord::$kd_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                } elseif (in_array($user_order['type'], FinancialLoanRecord::$other_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                }
            }else{
                return $this->redirectMessage("操作失败", self::MSG_ERROR);
            }
        }
    }


    public function actionHfdAllList()
    {
        $condition = '1=1';
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if(isset($search['order_id'])&&!empty($search['order_id'])){
                $condition = $condition." and order_id='".$search['order_id']."' ";
            }
            if(isset($search['status'])&& $search['status'] != ""){
                $condition = $condition." and  status=".$search['status']." ";
            }
            if(isset($search['begintime'])&&!empty(intval($search['begintime']))){
                $condition = $condition." and updated_at>=".$search['begintime']." ";
            }
            if(isset($search['begintime'])&&!empty(intval($search['begintime']))){
                $condition = $condition." and updated_at<=".$search['begintime']." ";
            }
        }
        $query = HfdFinancialRecord::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $data1 = [];
        $data2 = [];
        foreach($data as $item){
            $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$item['order_id']]);
            $data1[$item['id']] = $loan_hfd_order['loan_person_id'];
            $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order['loan_person_id']]);
            $data2[$item['id']] = $loan_person['name'];
        }

        return $this->render('hfd-all-list',array(
            'data' => $data,
            'pages' => $pages,
            'data1' => $data1,
            'data2' => $data2,
        ));
    }

    //财务打款列表
    public function actionHfdList(){
        $condition = '1=1 and status ='.HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY;
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if(isset($search['order_id'])&&!empty($search['order_id'])){
                $condition = $condition." and order_id='".$search['order_id']."' ";
            }
            if(isset($search['status'])&&!empty(intval($search['status']))){
                $condition = $condition." and  status=".$search['status']." ";
            }
            if(isset($search['begintime'])&&!empty(intval($search['begintime']))){
                $condition = $condition." and updated_at>=".$search['begintime']." ";
            }
            if(isset($search['begintime'])&&!empty(intval($search['begintime']))){
                $condition = $condition." and updated_at<=".$search['begintime']." ";
            }
            //order_id
        }
        //$condition = $this->getRecordFilter();

        $query = HfdFinancialRecord::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $data1 = [];
        $data2 = [];
        foreach($data as $item){
            $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$item['order_id']]);
            $data1[$item['id']] = $loan_hfd_order['loan_person_id'];
            $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order['loan_person_id']]);
            $data2[$item['id']] = $loan_person['name'];
        }

        return $this->render('hfd-trial-list',array(
            'data' => $data,
            'pages' => $pages,
            'data1' => $data1,
            'data2' => $data2,
        ));
    }

    //财务打款审核列表
    public function actionHfdTrialList(){
        $condition = '1=1 and status ='.HfdFinancialRecord::LOAN_STATUS_FINANCE_TRIAL;
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if(isset($search['order_id'])&&!empty($search['order_id'])){
                $condition = $condition." and order_id='".$search['order_id']."' ";
            }
            if(isset($search['begintime']) && !empty($search['begintime'])){
                $condition = $condition." and updated_at>=".$search['begintime']." ";
            }
            if(isset($search['begintime']) && !empty($search['begintime'])){
                $condition = $condition." and updated_at<=".$search['begintime']." ";
            }
        }
        $query = HfdFinancialRecord::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $data1 = [];
        $data2 = [];
        foreach($data as $item){
            $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$item['order_id']]);
            $data1[$item['id']] = $loan_hfd_order['loan_person_id'];
            $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order['loan_person_id']]);
            $data2[$item['id']] = $loan_person['name'];
        }

        return $this->render('hfd-trial-list',array(
            'data' => $data,
            'pages' => $pages,
            'data1' => $data1,
            'data2' => $data2,
        ));
    }

    //财务打款审核列表
    public function actionHfdRetrialList(){
        $condition = '1=1 and status ='.HfdFinancialRecord::LOAN_STATUS_FINANCE_STAY_MONEY;
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if(isset($search['order_id'])&&!empty($search['order_id'])){
                $condition = $condition." and order_id='".$search['order_id']."' ";
            }
            if(isset($search['begintime']) && !empty($search['begintime'])){
                $condition = $condition." and updated_at>=".$search['begintime']." ";
            }
            if(isset($search['begintime']) && !empty($search['begintime'])){
                $condition = $condition." and updated_at<=".$search['begintime']." ";
            }
            //order_id
        }
        //$condition = $this->getRecordFilter();

        $query = HfdFinancialRecord::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $data1 = [];
        $data2 = [];

        foreach($data as $item){
            $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$item['order_id']]);
            $data1[$item['id']] = $loan_hfd_order['loan_person_id'];
            $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order['loan_person_id']]);
            $data2[$item['id']] = $loan_person['name'];
        }

        return $this->render('hfd-retrial-list',array(
            'data' => $data,
            'pages' => $pages,
            'data1' => $data1,
            'data2' => $data2,
        ));
    }

    /**
     * @param $id
     * 放款
     */
    public function actionHfdLoan($order_id,$hfd_financial_record_id=0){
        $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($loan_hfd_order)){
            return;
        }
        $hfd_order = HfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($hfd_order)){
            return;
        }
        $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order->loan_person_id]);
        if(empty($loan_person)){
            return;
        }
        $shop_code = $hfd_order->shop_code;
        if(empty($shop_code)){
            return;
        }
        $shop = Shop::findOne(['shop_code'=>$shop_code]);
        if(empty($shop)){
            return;
        }
        $ywy_person = LoanPerson::findOne(['id'=>$hfd_order->user_id]);
        if(empty($ywy_person)){
            return;
        }
        $hfd_notarization  = HfdNotarization::findOne(['order_id'=>$order_id]);
        if(empty($hfd_notarization)){
            return;
        }
        $picture = json_decode($hfd_notarization->notarization_file);
        if(empty($picture)){
            $picture = [];
        }
        $hfd_house = HfdHouse::findAll(['order_id'=>$order_id]);
        $review_log = HfdOrderCheckFlow::findAll(['order_id'=>$order_id]);
        $house_picture = json_decode($hfd_order->first_file_url);
        if(empty($house_picture)){
            $house_picture = [];
        }
        $hfd_financial_record = HfdFinancialRecord::findOne(['order_id'=>$order_id,'id'=>$hfd_financial_record_id]);
        if(!$hfd_financial_record){
            $hfd_financial_record = new HfdFinancialRecord();
        }
        $hfd_financial_record->true_pay_money_time = date("Y-m-d",$hfd_financial_record->true_pay_money_time);
        $is_child = 0;
        if(HfdFinancialRecord::CHILD_YES == $hfd_financial_record->is_child){
            //只需要记录打款信息就可以
            $is_child = 1;

        }

        $loan_record_period =LoanRecordPeriod::findOne(['out_order_id'=>$order_id]);
        if(!$is_child&&empty($loan_record_period)){
            return $this->redirectMessage('记录不存在', self::MSG_ERROR);
        }
        $amount = 0;
        if($loan_record_period){
            //计算所有已经打款的
            $loan_hfd_financial_record = HfdFinancialRecord::findAll(['order_id'=>$order_id,'status'=>HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY]);
            if(false === $loan_hfd_financial_record){
                return $this->redirectMessage('获取财务打款数据失败', self::MSG_ERROR);
            }
            foreach($loan_hfd_financial_record as $item){
                $amount = $amount+$item->true_money;
            }
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                if(false === $hfd_notarization){
                    return;
                }
                $operation = $this->request->post('operation');
                $remark = $this->request->post('remark');
                $post = $this->request->post('HfdFinancialRecord');
                $true_loan_money = intval($this->request->post("true_loan_money"),0);
                $true_time = $post['true_pay_money_time'];
                $file = $post['file'];
                if(!empty($file)){
                    $file = str_replace("；",";",$file);
                    $file = explode(";",$file);
                    $file = json_encode($file);
                }
                $true_loan_money = $true_loan_money*1000000;
                $amount = $amount+$true_loan_money;
                if($loan_record_period){
                    //最好一笔打款或者全额打款
                    if(1 == $operation){
                        if(empty($true_loan_money)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写实际放款金额', self::MSG_ERROR);
                        }
                        if(empty($true_time)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写实际放款时间', self::MSG_ERROR);
                        }
                        $loan_hfd_order->status = LoanHfdOrder::LOAN_STATUS_FINANCE_ALREADY_MONEY;
                        $loan_hfd_order->updated_at = time();
                        $loan_hfd_order->operator_name = Yii::$app->user->identity->username;
                    }else if(2 == $operation){
                        if(empty($remark)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写备注', self::MSG_ERROR);
                        }
                    }else{
                        $transaction->rollBack();
                        return $this->redirectMessage('请选择审核状态', self::MSG_ERROR);
                    }
                    if(!$loan_hfd_order->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }
                    //更新财务记录表
                    $hfd_financial_record->true_money = $true_loan_money;
                    $hfd_financial_record->true_pay_money_time = strtotime($true_time);
                    $hfd_financial_record->remark = $remark;
                    if($operation == 1){
                        $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY;
                    } else {
                        $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL;
                    }
                    $hfd_financial_record->operator_name = Yii::$app->user->identity->username;
                    $hfd_financial_record->file = $file;
                    if(!$hfd_financial_record->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }

                    //插入订单审核流水表
                    $hfd_order_check_flow = new HfdOrderCheckFlow();
                    $hfd_order_check_flow->created_at = time();
                    $hfd_order_check_flow->operator_name = Yii::$app->user->identity->username;
                    $hfd_order_check_flow->type = HfdOrderCheckFlow::TYPE_FINANCE_LOAN;
                    $hfd_order_check_flow->order_id = $loan_hfd_order->order_id;
                    $hfd_order_check_flow->before_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY;
                    if(1 == $operation) {
                        $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY;
                    }else {
                        $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL;
                    }
                    $hfd_order_check_flow->remark = $remark;
                    $hfd_order_check_flow->hfd_financial_record_id = $hfd_financial_record_id;

                    if(!$hfd_order_check_flow->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }

                    //更新资产订单记录表
                    $loan_record_period->amount = $amount;
                    if(!$loan_record_period->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }

                    //更新提单状态为已完成
                    $hfd_order->status = HfdOrder::STATUS_COMPLETE;
                    $hfd_order->loan_time = time();
                    if(!$hfd_order->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }


                }else{
                    if(1 == $operation){
                        if(empty($true_loan_money)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写实际放款金额', self::MSG_ERROR);
                        }
                        if(empty($true_time)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写实际放款时间', self::MSG_ERROR);
                        }
                    }else if(2 == $operation){
                        if(empty($remark)){
                            $transaction->rollBack();
                            return $this->redirectMessage('请填写备注', self::MSG_ERROR);
                        }
                    }else{
                        $transaction->rollBack();
                        return $this->redirectMessage('请选择审核状态', self::MSG_ERROR);
                    }
                    //更新财务记录表
                    $hfd_financial_record->true_money = $true_loan_money;
                    $hfd_financial_record->true_pay_money_time = strtotime($true_time);
                    $hfd_financial_record->remark = $remark;
                    if($operation == 1){
                        $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY;
                    } else {
                        $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL;
                    }
                    $hfd_financial_record->operator_name = Yii::$app->user->identity->username;
                    $hfd_financial_record->file = $file;
                    if(!$hfd_financial_record->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }

                    //插入订单审核流水表
                    $hfd_order_check_flow = new HfdOrderCheckFlow();
                    $hfd_order_check_flow->created_at = time();
                    $hfd_order_check_flow->operator_name = Yii::$app->user->identity->username;
                    $hfd_order_check_flow->type = HfdOrderCheckFlow::TYPE_FINANCE_LOAN;
                    $hfd_order_check_flow->order_id = $loan_hfd_order->order_id;
                    $hfd_order_check_flow->before_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY;
                    if(1 == $operation) {
                        $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY;
                    }else {
                        $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL;
                    }
                    $hfd_order_check_flow->remark = $remark;
                    $hfd_order_check_flow->hfd_financial_record_id = $hfd_financial_record_id;

                    if(!$hfd_order_check_flow->save()){
                        $transaction->rollBack();
                        return $this->redirectMessage('打款失败', self::MSG_ERROR);
                    }
                }
                $transaction->commit();
                if(1 == $operation){
                    BuildingMessageHelper::sendSMS($loan_hfd_order['order_id'],$loan_person['phone'],HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY,$hfd_order['source']);
                    return $this->redirectMessage('打款成功', self::MSG_SUCCESS,Url::toRoute(['financial/hfd-list']));
                } else {
                    BuildingMessageHelper::sendSMS($loan_hfd_order['order_id'],$loan_person['phone'],HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_CANCEL,$hfd_order['source']);
                    return $this->redirectMessage('打款驳回成功', self::MSG_SUCCESS,Url::toRoute(['financial/hfd-list']));
                }
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('打款失败', self::MSG_ERROR);
        }

        return $this->render('hfd-loan',[
            'loan_hfd_order'=>$loan_hfd_order,
            'hfd_order'=>$hfd_order,
            'loan_person'=>$loan_person,
            'shop'=>$shop,
            'ywy_person'=>$ywy_person,
            'hfd_notarization'=>$hfd_notarization,
            'picture'=>$picture,
            'loan_record_period' => $loan_record_period,
            'hfd_house'=>$hfd_house,
            'review_log'=>$review_log,
            'house_picture'=>$house_picture,
            'hfd_financial_record'=>$hfd_financial_record,
            'is_child'=>(HfdFinancialRecord::CHILD_YES== $is_child)?"部分打款":"全额打款",
        ]);
    }

    public function actionHfdCheck($order_id,$hfd_financial_record_id=0){

        $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($loan_hfd_order)){
            return;
        }
        $hfd_order = HfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($hfd_order)){
            return;
        }
        $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order->loan_person_id]);
        if(empty($loan_person)){
            return;
        }
        $shop_code = $hfd_order->shop_code;
        if(empty($shop_code)){
            return;
        }
        $shop = Shop::findOne(['shop_code'=>$shop_code]);
        if(empty($shop)){
            return;
        }
        $ywy_person = LoanPerson::findOne(['id'=>$hfd_order->user_id]);
        if(empty($ywy_person)){
            return;
        }
        $hfd_notarization  = HfdNotarization::findOne(['order_id'=>$order_id]);
        if(empty($hfd_notarization)){
            return;
        }
        $picture = json_decode($hfd_notarization->notarization_file,true);
        if(empty($picture)){
            $picture = [];
        }
        $hfd_house = HfdHouse::findAll(['order_id'=>$order_id]);
        $review_log = HfdOrderCheckFlow::findAll(['order_id'=>$order_id]);
        $house_picture = json_decode($hfd_order->first_file_url);
        if(empty($house_picture)){
            $house_picture = [];
        }
        $hfd_financial_record = HfdFinancialRecord::findOne(['order_id'=>$order_id,'id'=>$hfd_financial_record_id]);
        $loan_record_period = LoanRecordPeriod::findOne(['out_order_id'=>$order_id]);
        $is_child = $hfd_financial_record->is_child;
        $is_child = (HfdFinancialRecord::CHILD_YES== $is_child)?"部分打款":"全额打款";

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                if(false === $hfd_notarization){
                    return;
                }
                $operation = $this->request->post('operation');
                $remark = $this->request->post('remark');
                if(1 == $operation){
                    $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_STAY_MONEY;
                }else if(2 == $operation){
                    if(empty($remark)){
                        $transaction->rollBack();
                        return $this->redirectMessage('请填写备注', self::MSG_ERROR);
                    }
                    $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_CANCEL;
                }else{
                    $transaction->rollBack();
                    return $this->redirectMessage('请选择审核状态', self::MSG_ERROR);
                }
                $hfd_financial_record->remark = $remark;
                $hfd_financial_record->operator_name = Yii::$app->user->identity->username;
                if(!$hfd_financial_record->save()){
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败', self::MSG_ERROR);
                }

                //插入订单审核流水表
                $hfd_order_check_flow = new HfdOrderCheckFlow();
                $hfd_order_check_flow->created_at = time();
                $hfd_order_check_flow->operator_name = Yii::$app->user->identity->username;
                $hfd_order_check_flow->type = HfdOrderCheckFlow::TYPE_FINANCE_TRIAL;
                $hfd_order_check_flow->order_id = $loan_hfd_order->order_id;
                $hfd_order_check_flow->before_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_TRIAL;
                if(1 == $operation) {
                    $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_STAY_MONEY;
                }else {
                    $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_CANCEL;
                }
                $hfd_order_check_flow->remark = $remark;
                $hfd_order_check_flow->hfd_financial_record_id = $hfd_financial_record_id;
                if(!$hfd_order_check_flow->save()){
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败', self::MSG_ERROR);
                }
                $transaction->commit();
                return $this->redirectMessage('审核成功', self::MSG_SUCCESS,Url::toRoute(['hfd-dispatch/list']));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('审核失败', self::MSG_ERROR);
        }

        return $this->render('hfd-check',[
            'loan_hfd_order'=>$loan_hfd_order,
            'hfd_order'=>$hfd_order,
            'loan_person'=>$loan_person,
            'shop'=>$shop,
            'ywy_person'=>$ywy_person,
            'hfd_notarization'=>$hfd_notarization,
            'picture'=>$picture,
            'hfd_house'=>$hfd_house,
            'review_log'=>$review_log,
            'house_picture'=>$house_picture,
            'hfd_financial_record'=>$hfd_financial_record,
            'is_child'=>$is_child,
            'loan_record_period'=>$loan_record_period,
        ]);

    }

    public function actionHfdRecheck($order_id,$hfd_financial_record_id=0)
    {
        $loan_hfd_order = LoanHfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($loan_hfd_order)){
            return;
        }
        $hfd_order = HfdOrder::findOne(['order_id'=>$order_id]);
        if(empty($hfd_order)){
            return;
        }
        $loan_person = LoanPerson::findOne(['id'=>$loan_hfd_order->loan_person_id]);
        if(empty($loan_person)){
            return;
        }
        $shop_code = $hfd_order->shop_code;
        if(empty($shop_code)){
            return;
        }
        $shop = Shop::findOne(['shop_code'=>$shop_code]);
        if(empty($shop)){
            return;
        }
        $ywy_person = LoanPerson::findOne(['id'=>$hfd_order->user_id]);
        if(empty($ywy_person)){
            return;
        }
        $hfd_notarization  = HfdNotarization::findOne(['order_id'=>$order_id]);
        if(empty($hfd_notarization)){
            return;
        }
        $picture = json_decode($hfd_notarization->notarization_file,true);
        if(empty($picture)){
            $picture = [];
        }
        $hfd_house = HfdHouse::findAll(['order_id'=>$order_id]);
        $review_log = HfdOrderCheckFlow::findAll(['order_id'=>$order_id]);
        $house_picture = json_decode($hfd_order->first_file_url);
        if(empty($house_picture)){
            $house_picture = [];
        }
        $loan_record_period = LoanRecordPeriod::findOne(['out_order_id'=>$order_id]);
        $hfd_financial_record = HfdFinancialRecord::findOne(['order_id'=>$order_id,'id'=>$hfd_financial_record_id]);
        $is_child = $hfd_financial_record->is_child;
        $is_child = (HfdFinancialRecord::CHILD_YES== $is_child)?"部分打款":"全额打款";
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            if ($this->getRequest()->getIsPost()) {
                if(false === $hfd_notarization){
                    return;
                }
                $operation = $this->request->post('operation');
                $remark = $this->request->post('remark');
                if(1 == $operation){
                    $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY;
                }else if(2 == $operation){
                    if(empty($remark)){
                        $transaction->rollBack();
                        return $this->redirectMessage('请填写备注', self::MSG_ERROR);
                    }
                    $hfd_financial_record->status = HfdFinancialRecord::LOAN_STATUS_FINANCE_RECANCEL;
                }else{
                    $transaction->rollBack();
                    return $this->redirectMessage('请选择审核状态', self::MSG_ERROR);
                }
                $hfd_financial_record->remark = $remark;
                $hfd_financial_record->operator_name = Yii::$app->user->identity->username;
                if(!$hfd_financial_record->save()){
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败', self::MSG_ERROR);
                }

                //插入订单审核流水表
                $hfd_order_check_flow = new HfdOrderCheckFlow();
                $hfd_order_check_flow->created_at = time();
                $hfd_order_check_flow->operator_name = Yii::$app->user->identity->username;
                $hfd_order_check_flow->type = HfdOrderCheckFlow::TYPE_FINANCE_RETRIAL;
                $hfd_order_check_flow->order_id = $loan_hfd_order->order_id;
                $hfd_order_check_flow->before_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_STAY_MONEY;
                if(1 == $operation) {
                    $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY;
                }else {
                    $hfd_order_check_flow->after_status = HfdFinancialRecord::LOAN_STATUS_FINANCE_RECANCEL;
                }
                $hfd_order_check_flow->remark = $remark;
                $hfd_order_check_flow->hfd_financial_record_id = $hfd_financial_record_id;

                if(!$hfd_order_check_flow->save()){
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败', self::MSG_ERROR);
                }

                $transaction->commit();
                return $this->redirectMessage('审核成功', self::MSG_SUCCESS,Url::toRoute(['hfd-dispatch/list']));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->redirectMessage('审核失败', self::MSG_ERROR);
        }

        return $this->render('hfd-check',[
            'loan_hfd_order'=>$loan_hfd_order,
            'hfd_order'=>$hfd_order,
            'loan_person'=>$loan_person,
            'shop'=>$shop,
            'ywy_person'=>$ywy_person,
            'hfd_notarization'=>$hfd_notarization,
            'picture'=>$picture,
            'hfd_house'=>$hfd_house,
            'review_log'=>$review_log,
            'house_picture'=>$house_picture,
            'hfd_financial_record'=>$hfd_financial_record,
            'is_child'=>$is_child,
            'loan_record_period'=>$loan_record_period,
        ]);
    }


    /**
     * 打款列表导出
     * @param int $type
     */
    private function _exportLoanInfos($type=0){
        $get = Yii::$app->request->get();
        if (empty($get['updated_at_begin']) || empty($get['updated_at_end'])) {
            return $this->redirectMessage('搜索必须带时间范围条件', self::MSG_ERROR);
        }
        $task_name = '打款列表导出任务-'.date('YmdHis');
        $data = json_encode(array_filter($get),JSON_UNESCAPED_UNICODE);
        $loanTask = new LoanTask();
        $loanTask->data = $data;
        $loanTask->name = $task_name;
        $loanTask->type = LoanTask::TYPE_3;
        $loanTask->created_at = time();
        $loanTask->updated_at = time();
        if ($loanTask->save()) {
            $params = ['id' => $loanTask->id];
            ServerSoa::instance("ExportLoanTask")->asend_runExport($params);
            return $this->redirectMessage('任务提交成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('任务提交失败', self::MSG_ERROR);
        }
    }

    /**
     * 打款列表导出 - 直接
     * @param int $type
     */
    private function _exportLoanInfosDirect($type=0) {
        Util::cliLimitChange(1024);

        if (!$type) {
            $type = FinancialLoanRecord::$other_platform_type;
        }

//		$this->_setcsvHeader('打款订单信息导出.csv');
        $condition = $this->getFilter();
        $condition = str_replace('AND ', 'AND ', $condition);
//        var_dump($condition);die;
        if ($type == FinancialLoanRecord::$other_platform_type || \in_array($type, FinancialLoanRecord::$other_platform_type)) { //合作资产需要左联AssetOrder表
//			$query = FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')
//				->where(['in', 'l.type', $type])
//				->andwhere($condition)
//				->select(['l.id','l.business_id','l.order_id','l.money','l.counter_fee','l.bank_name','l.card_no','l.type','l.payment_type','l.status','l.created_at','l.success_time','p.id_number','p.phone','p.name as personName','u.loan_term','u.loan_method','c.name as cardName'])
//				->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
//				->leftJoin(AssetOrder::tableName().' as u','l.business_id=u.id')
//				->leftJoin(CardInfo::tableName().' as c','l.bind_card_id=c.id')
//				->orderBy(['l.id' => SORT_DESC]);
        }
        else {
            $query = FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')
                ->where(['in', 'l.type', $type])
                ->andwhere($condition)
                ->select(['l.id','l.business_id','l.order_id','l.money','l.counter_fee','l.bank_name','l.card_no','l.type','l.payment_type','l.status','l.created_at','l.success_time','p.id_number','p.phone','p.name as personName','u.loan_term','u.loan_method','u.fund_id','c.name as cardName'])
                ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
                ->leftJoin(UserLoanOrder::tableName().' as u','l.business_id=u.id')
                ->leftJoin(CardInfo::tableName().' as c','l.bind_card_id=c.id')
                ->orderBy(['l.id' => SORT_ASC]);
        }

        $max_id = 0;
        $count = 0;
        $step = 10000;
        $max_count = 80000;
        $datas = $query->andWhere(['>', 'l.id', $max_id])
            ->limit($step)
            ->asArray()
            ->all(Yii::$app->get('db_kdkj_rd_new'));
        $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
        $all_funds = LoanFund::getAllFundArray();
        $items = [];

        while($datas) {
            foreach($datas as $key=>$value){
                $items[$count] = [
                    '订单ID' => $value['order_id'],
                    '资方' =>  isset($value['fund_id']) && !empty($all_funds[$value['fund_id']]) ? $all_funds[$value['fund_id']] :$fund_koudai->name,
                    '打款ID' => $value['id'],
                    '业务订单ID' => $value['business_id'],
                    '姓名' => $value['personName'],
                    '申请金额' => sprintf('%.2f', $value['money'] / 100),
                    '手续费' => sprintf('%.2f', $value['counter_fee'] / 100),
                    '实际打款金额' => sprintf('%.2f',  ($value['money'] - $value['counter_fee']) / 100),
                    '持卡人姓名' => $value['cardName'],
                    '绑卡银行' => $value['bank_name'],
                    '手机号' => substr_replace($value['phone'],'****',3,4),
                    '身份证号' => substr_replace($value['id_number'],'********',6,8),
                    '银行卡号' => "\t".$value['card_no'],
                    '业务类型' => isset(FinancialLoanRecord::$types[$value['type']]) ? FinancialLoanRecord::$types[$value['type']] : "---",
                    '打款状态' => empty($value['status']) ? "---" : FinancialLoanRecord::$ump_pay_status[$value['status']],
                    '打款渠道' => isset(FinancialLoanRecord::$payment_types[$value['payment_type']]) ? FinancialLoanRecord::$payment_types[ $value['payment_type']] : "-----",
                    '申请时间' => date('Y-m-d H:i', $value['created_at']),
                    '成功时间' => $value['success_time'] ? date('Y-m-d H:i', $value['success_time']) : '',
                ];

                if($value['loan_method']==0) {
                    $items[$count]['借款期限']=empty($value['loan_term']) ? 0 : $value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                }
                elseif($value['loan_method']==1) {
                    $items[$count]['借款期限']=$value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                }
                else {
                    $items[$count]['借款期限']=$value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                }
                $count++;
                $max_id = $value['id'];
            }

            if ($count > $max_count) {
                break;
            }

            $datas = $query->andWhere(['>', 'l.id', $max_id])
                ->limit($step)
                ->asArray()
                ->all(Yii::$app->get('db_kdkj_rd_new'));
        }

        LExcelHelper::exportExcel($items, '打款订单信息导出');
//		echo $this->_array2csv($items);
        exit;
    }

    private function _exportDebitInfos($type=0) {
        ini_set('memory_limit', '2048M');
        if(!$type){
            $type = FinancialDebitRecord::$other_platform_type;
        }
        $this->_setcsvHeader('扣款订单信息导出.csv');
        $condition = $this->getDebitFilter();
        $fundInfo = LoanFund::getAllFundArray();
        $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
        $query = FinancialDebitRecord::find()
            ->from(FinancialDebitRecord::tableName().' as l')
            ->where(['in', 'l.type', $type])
            ->andwhere($condition)
            ->select(['l.id','l.user_id','l.order_id','l.true_repayment_money','l.true_repayment_time','l.platform','l.remark','l.remark_two','p.name','p.phone',userLoanOrder::tableName().'.fund_id'])
            ->orderBy(['l.id' => SORT_DESC])
            ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
            ->leftJoin(userLoanOrder::tableName() ,userLoanOrder::tableName().'.id=l.loan_record_id');
        /*$countQuery = clone $query;
		$pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
		$pages->pageSize = 15;*/
        //$datas = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $max_id = 0;
        $count = 0;
        $datas = $query->andWhere(['>','l.id',$max_id])->limit(500)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
//        while ($datas) {
//            foreach($datas as $value)
//            {
//                $count ++;
//                try {
//                    $debitCnt = 0;
//                    if (preg_match_all("/发起扣款/",$value['remark'],$out1)) $debitCnt = count($out1[0]);
//                    if (count($out1[0]) == 0)
//                    {
//                        preg_match_all("/\*\*/",$value['remark_two'],$out2);
//                        $debitCnt = count($out2[0]);
//                    }
//                    $items[] = [
//                        '用户ID' => $value['user_id'],
//                        '资方' => !empty($fundInfo[$value['fund_id']])? $fundInfo[$value['fund_id']]:$fund_koudai->name,
//                        '姓名' => $value['name'],
//                        '扣款金额' => sprintf('%.2f', $value['true_repayment_money'] / 100),
//                        '扣款渠道' => isset(BankConfig::$platform[$value['platform']]) ? BankConfig::$platform[ $value['platform']] : "-",
//                        '扣款成功时间' => $value['true_repayment_time'] ? date('Y-m-d H:i', $value['true_repayment_time']) : '-',
//                        '订单ID' => "\t".$value['order_id'],
//                        '备注' => $value['remark'],
//                        '扣款次数' => $debitCnt
//                    ];
//                } catch(Exception $ex) {
//                }
//            }
//            if ($count>10000) break;
//            $max_id = $value['id'];
//            $datas = $query->andWhere(['>','l.id',$max_id])->limit(500)->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
//        }
        foreach($datas as $value){
            $items[] = [
                '用户ID' => $value['user_id'],
                '资方' => !empty($fundInfo[$value['fund_id']])? $fundInfo[$value['fund_id']]:$fund_koudai->name,
                '姓名' => $value['name'],
                '扣款金额' => sprintf('%.2f', $value['true_repayment_money'] / 100),
                '扣款渠道' => isset(BankConfig::$platform[$value['platform']]) ? BankConfig::$platform[ $value['platform']] : "-",
                '扣款成功时间' => $value['true_repayment_time'] ? date('Y-m-d H:i', $value['true_repayment_time']) : '-',
                '订单ID' => "\t".$value['order_id'],
                '备注' => $value['remark'],
            ];
        }
        echo $this->_array2csv($items);
    }
    /*
	* 还款日志列表导出方法
	*/
    private function _exportBankPayInfos($type=0){
        $get = Yii::$app->request->get();
        if (empty($get['success_begin_time']) || empty($get['success_end_time'])) {
            return $this->redirectMessage('搜索必须带时间范围条件', self::MSG_ERROR);
        }
        $task_name = '还款日志列表导出任务-'.date('YmdHis');
        $data = json_encode(array_filter($get),JSON_UNESCAPED_UNICODE);
        $loanTask = new LoanTask();
        $loanTask->data = $data;
        $loanTask->name = $task_name;
        $loanTask->type = LoanTask::TYPE_2;
        $loanTask->created_at = time();
        $loanTask->updated_at = time();
        if ($loanTask->save()) {
            $params = ['id' => $loanTask->id];
            ServerSoa::instance("ExportTask")->asend_runExport($params);
            return $this->redirectMessage('任务提交成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('任务提交失败', self::MSG_ERROR);
        }

    }

    /*
	* 还款日志列表导出方法 备份用
	*/
    private function _exportBankPayInfosBak($type=0){
        $this->_setcsvHeader('还款日志列表导出.csv');
        $condition = $this->getBankpayFilter();
        $max_id = 0;
        $query = UserCreditMoneyLog::find()->from(UserCreditMoneyLog::tableName().' as l')->
        select(['l.debit_channel','l.type','l.id','l.payment_type','l.remark','l.order_uuid','l.pay_order_id','l.operator_money','l.operator_name',
            'p.name','p.phone','l.updated_at','l.operator_principal','l.operator_interests','l.operator_late_fee','l.operator_overflow',
            'userLoanOrder.fund_id','l.order_id'])->orderBy(['l.id' => SORT_DESC])
            ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
            ->leftJoin(UserLoanOrderRepayment::tableName().' as uo','l.order_id=uo.order_id')
            ->leftJoin(UserLoanOrder::tableName() .' as userLoanOrder','userLoanOrder.id=l.order_id')
            ->andwhere($condition);
        $datas = $query->andWhere(['>','l.id',$max_id])->limit(10000)->asArray()->orderBy(['l.id'=>SORT_ASC])->all(Yii::$app->get('db_kdkj_rd_new'));
        $items = [];
        $payment_type=UserCreditMoneyLog::$payment_type;

        $fund  =  LoanFund::getAllFundArray();
        while ($datas)
        {
            foreach($datas as $value){

                if($value['debit_channel']){
                    $debit_channel =  isset(UserCreditMoneyLog::$third_platform_name[$value['debit_channel']]) ? UserCreditMoneyLog::$third_platform_name[$value['debit_channel']] : "---";
                }else{
                    $debit_channel = isset(UserCreditMoneyLog::$type[$value['type']]) ? UserCreditMoneyLog::$type[$value['type']] : "---";
                }
                $items[] = [
                    '资方'=>  !empty($value['fund_id'])?$fund[$value['fund_id']]:"口袋理财",
                    '姓名' => $value['name'],
                    '订单id' => $value['order_id'],
                    '通道'=> $debit_channel,
                    '还款金额/元' => sprintf('%.2f', $value['operator_money'] / 100),
                    '还款本金/元' => sprintf('%.2f', $value['operator_principal'] / 100),
                    '还款利息/元' => sprintf('%.2f', $value['operator_interests'] / 100),
                    '还款滞纳金/元' => sprintf('%.2f', $value['operator_late_fee'] / 100),
                    '还款溢出金额/元' => sprintf('%.2f', $value['operator_overflow'] / 100),
                    '成功时间' =>$value['updated_at'] ? date('Y-m-d H:i', $value['updated_at']) : '-',
                    '还款方式' => isset($payment_type[$value['payment_type']]) ? $payment_type[$value['payment_type']] : $value['payment_type'],
                    '银行流水号'=> "\t".$value['order_uuid'],
                    '流水订单号'=>"\t".$value['pay_order_id'],
                    '备注' => $value['remark'],
                    '操作人'=>$value['operator_name']
                ];
                $max_id = $value['id'];
            }
            unset($datas);
            $datas = $query->andWhere(['>','l.id',$max_id])->limit(10000)->asArray()->orderBy(['l.id'=>SORT_ASC])->all(Yii::$app->get('db_kdkj_rd_new'));
        }

        echo $this->_array2csv($items);
        exit;

    }
    /**
     * @name 合作资产-合作方打款扣款管理-打款列表/actionLoanOtherList
     */
    public function actionLoanOtherList($view = 'list') {
        if ($this->request->get('submitcsv') == 'exportcsv') {
            return $this->_exportLoanInfos();
        }

        $condition = $this->getFilter();
        $query = FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')->where(['in', 'l.type', FinancialLoanRecord::$other_platform_type])->andwhere($condition)->
        select(['l.*','l.id as rid','p.name','u.loan_term','u.loan_method'])
            ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')->leftJoin(AssetOrder::tableName().' as u','l.business_id=u.id')->orderBy(['l.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $dataSt = $countQuery->select('sum(l.money) as money,sum(l.counter_fee) as counter_fee')->one(Yii::$app->get('db_kdkj_rd'));

        $type = FinancialLoanRecord::$types;
        foreach(FinancialLoanRecord::$kd_platform_type as $t){
            unset($type[$t]);
        }
        return $this->render('loan-list', [
            'withdraws' => $data,
            'pages' => $pages,
            'type' => $type,
            'export' => 1,
            'view' => $view,
            'dataSt' => $dataSt,
            'isother' => 1,
        ]);

    }

    /**
     * 打款批量审核
     */
    public function actionBatchWithdrawApprove() {
        $this->response->format = "json";
        $ids = $this->request->get("ids");
        $id_array = explode(",", $ids);
        $false_arr = [];
        foreach($id_array as $v){
            //审核通过之前根据业务类型先对账
            $withdraw = FinancialLoanRecord::find()->where(['id' => $v])->one(Yii::$app->get('db_kdkj_rd'));
            if ($withdraw->review_result != 0 || $withdraw->status != 1) {
                $false_arr[] = $v."状态不在未审核提现中";
                continue;
            }
            try {
                $this->financialService->withdrawCheckLoanOrder($withdraw);
            } catch (Exception $e) {
                $false_arr[] = $v."对账失败".$e->getMessage();
                continue;
            }
            try {
                $this->financialService->newWithdrawApprove($v, $withdraw->payment_type, "直连打款批量审核通过", Yii::$app->user->identity->username);
            } catch (Exception $e) {
                $false_arr[] = $v."更改状态失败".$e->getMessage();
                continue;
            }
        }
        if(empty($false_arr)){
            $data = [
                'code' => 0,
                'false_ids' => $false_arr,
            ];
        }else{
            $data = [
                'code' => 1,
                'false_ids' => $false_arr,
            ];
        }

        return $data;
    }

    /**
     * 打款批量审核
     */
    public function actionAllWithdrawApprove() {
        $type = $_GET['type'];
        $review_result = $_GET['review_result'];
        $status = $_GET['status'];
        if (empty($type) || $review_result != FinancialLoanRecord::REVIEW_STATUS_NO || $status != FinancialLoanRecord::UMP_PAYING) {
            return $this->redirectMessage("抱歉，业务类型为空或者筛选状态不在审核通过申请中。", self::MSG_ERROR);
        }
        //审核通过之前根据业务类型先对账
        $withdraw = FinancialLoanRecord::find()->where(['type' => $type, 'review_result' => FinancialLoanRecord::REVIEW_STATUS_NO , 'status' => FinancialLoanRecord::UMP_PAYING])->all(Yii::$app->get('db_kdkj_rd'));
        if (empty($withdraw)) {
            return $this->redirectMessage("抱歉，无对应类型需要审核通过的记录。", self::MSG_ERROR);
        }
        $false_arr = [];
        set_time_limit(0);
        foreach ($withdraw as $k => $v) {
            try {
                $this->financialService->withdrawCheckLoanOrder($v);
            } catch (Exception $e) {
                $false_arr[] = "ID:".$v->id."对账失败".$e->getMessage();
                continue;
            }
            try {
                $this->financialService->newWithdrawApprove($v->id, $v->payment_type, "直连打款全部审核通过", Yii::$app->user->identity->username);
            } catch (Exception $e) {
                $false_arr[] = "ID:".$v->id."更改状态失败".$e->getMessage();
                continue;
            }
        }
        $msg = empty($false_arr) ? "" : "失败的记录：".implode(",", $false_arr);
        return $this->redirectMessage("您好，对应类型的记录全部审核通过成功。".$msg, self::MSG_SUCCESS);
    }


    /**
     * @name 财务管理-小钱包扣款打款管理-打款列表-详情/actionWithdrawDetail
     */
    public function actionWithdrawDetail($id)
    {
        $withdraw = FinancialLoanRecord::find()->where(['id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        if(empty($withdraw))
        {
            return $this->redirectMessage("无此打款记录({$id})：", self::MSG_ERROR);
        }
        $uid = $withdraw['user_id'];
        $user = LoanPerson::find()->where(['id' => $uid])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if(empty($user))
        {
            return $this->redirectMessage("无此用户记录uid({$uid})：", self::MSG_ERROR);
        }
        if (in_array($withdraw->type, FinancialLoanRecord::$kd_platform_type)) {
            $user_verify = UserVerification::findOne(['user_id' => $uid]);
            if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                return $this->redirectMessage("无此用户实名记录uid为({$uid})：", self::MSG_ERROR);
            }
        }
        if(empty($withdraw['bind_card_id'])) {
            $user_bank = CardInfo::find()->where(['user_id' => $uid, 'card_no' => $withdraw['card_no']])->one(Yii::$app->get('db_kdkj_rd'));
        } else {
            if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                $user_bank = CardInfo::find()->where(['user_id' => $uid, 'id' => $withdraw['bind_card_id']])->one(Yii::$app->get('db_kdkj_rd'));
            }else{
                $user_bank = CardInfo::find()->where(['id' => $withdraw['bind_card_id']])->one(Yii::$app->get('db_kdkj_rd'));
            }
        }
        if (empty($user_bank)) {
            return $this->redirectMessage("无对应用户的银行卡记录uid({$uid})：", self::MSG_ERROR);
        }
        $message = [
            'user' => $user,
            'user_bank' => $user_bank,
        ];
        if ($this->request->post() && $withdraw->load($this->getRequest()->post())) {
            if (isset($withdraw['review_result']) && !empty($withdraw['review_result'])) {
                switch($withdraw['review_result']) {
                    case FinancialLoanRecord::REVIEW_STATUS_APPROVE:
                        if (isset($withdraw['payment_type']) && !empty($withdraw['payment_type'])) {
                            //审核通过之前根据业务类型先对账
                            try {
                                if(empty($this->financialService)){
                                    $this->financialService = new FinancialService();
                                }
                                $this->financialService->withdrawCheckLoanOrder($withdraw);
                            } catch (Exception $e) {
                                return $this->redirectMessage($e->getMessage(), self::MSG_ERROR);
                            }

                            return $this->newWithdrawApprove($id, $withdraw['payment_type'], $withdraw['review_remark']);
                        }
                        else {
                            return $this->redirectMessage('请选择审核结果、打款渠道', self::MSG_ERROR);
                        }
                    case FinancialLoanRecord::REVIEW_STATUS_REJECT:
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try {
                            $withdraw['review_remark'] = trim($withdraw['review_remark']);
                            if (empty($withdraw['review_remark'])) {
                                throw new Exception("审核驳回请填写驳回原因！");
                            }
                            try {
                                $service = Yii::$container->get('financialCommonService');
                                $back_result = $service->rejectLoanOrder($withdraw->business_id, $withdraw['review_remark'], Yii::$app->user->identity->username, $withdraw['type']);//驳回

                                if ($back_result['code'] !== 0) {
                                    throw new Exception($back_result['message']);
                                }
                            } catch (Exception $e) {
                                throw new Exception("审核驳回通知业务方失败：".$e->getMessage());
                            }
                            $callback_result = [
                                'is_notify' => FinancialLoanRecord::NOTIFY_SUCCESS,
                                'message' =>  $back_result['message']
                            ];
                            $withdraw->status = FinancialLoanRecord::UMP_PAY_FAILED;
                            $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_REJECT;
                            $withdraw->review_remark = $withdraw['review_remark'];
                            $withdraw->review_username = Yii::$app->user->identity->username;
                            $withdraw->review_time = time();
                            $withdraw->callback_result = json_encode($callback_result);
                            if (!$withdraw->save()) {
                                throw new Exception("操作失败");
                            }
                            $transaction->commit();
                            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                            return $this->redirectMessage('操作出现异常：' . $e->getMessage(), self::MSG_ERROR);
                        }
                    case FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED:
                        $withdraw['review_username'] = Yii::$app->user->identity->username;
                        $withdraw['review_time'] = time();
                        switch ($this->request->post('operation')) {
                            case FinancialLoanRecord::CMB_FAILED_PAYING:
                                $withdraw['review_result'] = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
                                $withdraw['status'] = FinancialLoanRecord::UMP_CMB_PAYING;
                                break;
                            case FinancialLoanRecord::CMB_FAILED_MANUAL:
                                $withdraw['payment_type'] = FinancialLoanRecord::PAYMENT_TYPE_MANUAL;
                                $withdraw['review_result'] = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
                                $withdraw['status'] = FinancialLoanRecord::UMP_PAYING;
                                break;
                            case FinancialLoanRecord::CMB_FAILED_RESET:
                                $withdraw['payment_type'] = 0;  // 重置打款渠道
                                $withdraw['review_result'] = FinancialLoanRecord::REVIEW_STATUS_NO;
                                $withdraw['status'] = FinancialLoanRecord::UMP_PAYING;
                                break;
                            default:
                                return $this->redirectMessage('未知的直连失败操作', self::MSG_ERROR);
                                break;
                        }
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try {
                            if ($withdraw->validate() && $withdraw->save()) {
                                $transaction->commit();
                                if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                                    return $this->redirectMessage('直连失败操作成功', self::MSG_SUCCESS, Url::toRoute(['financial/loan-list']));
                                } elseif (in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)) {
                                    return $this->redirectMessage('直连失败操作成功', self::MSG_SUCCESS, Url::toRoute(['financial/loan-other-list']));
                                }
                            } else {
                                $transaction->rollBack();
                                return $this->redirectMessage('直连失败操作失败', self::MSG_ERROR);
                            }
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                            return $this->redirectMessage('异常状态，直连失败操作无效', self::MSG_ERROR);
                        }
                        break;
                    default:
                        return $this->redirectMessage('未知的审核状态', self::MSG_ERROR);
                }
            } else {
                return $this->redirectMessage('请选择审核结果、打款渠道', self::MSG_ERROR);
            }
        }

        return $this->render('withdraw-detail', [
            'withdraw' => $withdraw,
            'message' => $message,
        ]);
    }

    /**
     * @name 财务管理-小钱包扣款打款管理-打款列表-批量处理/actionWithdrawDetail
     */
    public function actionBatchHandle()
    {
        try {
            if (!$this->request->isPost) throw new Exception('请求方式错误!');
            $params = $this->request->post();
            if (!isset($params['financialRecordArr']) || strlen($params['financialRecordArr']) <=0 ) throw new Exception('参数错误');
            if (!isset($params['FinancialLoanRecord']['review_result'])) throw new Exception('请选择审核状态!');
            $review_result = $params['FinancialLoanRecord']['review_result'];
            if (!isset($params['FinancialLoanRecord']['payment_type'])) throw new Exception('请选择付款方式!');
            $financialIdArr = explode(',',$params['financialRecordArr']);
            if (count($financialIdArr) <=0)  throw new Exception('无可操作数据!');

            // 检查状态第三方返回状态
            $isAllFailed = true;
            foreach ($financialIdArr as $id) {
                $user_order = FinancialLoanRecord::findOne($id);
                $order_id = $user_order->order_id;
                $loan_order = UserLoanOrder::findOne($user_order->business_id);
                if ($loan_order['fund_id'] != LoanFund::ID_WZDAI) {
                    $url = 'http://test.abc.com';
                    switch ($loan_order['fund_id']) {
                        case LoanFund::ID_KOUDAI:
                            $project_name = FinancialService::KD_PROJECT_NAME;
                            break;
                        default:
                            throw new Exception("打款订单ID：{$id}，资方错误");
                            break;
                    }
                    $interFaceParams = [
                        'yur_ref' => $order_id,
                        'project_name' => $project_name,
                    ];
                    $sign = \common\models\Order::getSignNew($interFaceParams, $project_name);
                    $interFaceParams['sign'] = $sign;
                    if (YII_ENV==='prod'){
                        $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $interFaceParams);
                    } else {
                        $ret = ['code' => 1003,'data' => ['opr_dat' => 20170627],'msg' => '打款失败'];
                    }
                    if(!$ret || !isset($ret['code'])){
                        throw new Exception('查询失败，请稍后再试!');
                        break;
                    }
                    if ($ret && isset($ret['code']) && $ret['code'] != 1003) {
                        $isAllFailed = false;
                        break;
                    }
                } else {
                    $url = FinancialService::WZD_HOST.'api-borrow/ast/state';
                    $ret = \common\helpers\CurlHelper::wzdCurl($url,'GET',['orderNo'=>$order_id,'source' => 'xybt']);
                    if(!$ret || !isset($ret['data']['status'])){
                        throw new Exception('温州贷打款结果查询失败，请稍后再试!');
                        break;
                    }
                    if (isset($ret['status_code']) && isset($ret['data']['status']) && in_array($ret['data']['status'],[1,2,3,4,8])) {
                        $isAllFailed = false;
                        break;
                    }
                }
            }
            if (!$isAllFailed) {
                throw new Exception('处理的订单中存在不明确的失败的状态!');
            }
            foreach ($financialIdArr as $id) {
                //重置状态
                $user_order = FinancialLoanRecord::findOne($id);
                if (!($user_order && !$user_order->callback_result)) throw new Exception("重置状态操作失败,ID:".$id, self::MSG_ERROR);
                $user_order->review_result = FinancialLoanRecord::REVIEW_STATUS_NO;
                $user_order->status = FinancialLoanRecord::REVIEW_STATUS_NO;
                $user_order->updated_at = time();
                if (!$user_order->save()) throw new Exception("状态操作失败,ID:".$id, self::MSG_ERROR);
                //审核操作
                $withdraw = $user_order;
                $uid = $withdraw['user_id'];
                $user = LoanPerson::find()->where(['id' => $uid])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($user)) throw new Exception("无此用户记录uid({$uid})：", self::MSG_ERROR);
                if (in_array($withdraw->type, FinancialLoanRecord::$kd_platform_type)) {
                    $user_verify = UserVerification::findOne(['user_id' => $uid]);
                    if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                        throw new Exception("无此用户实名记录uid为({$uid})：", self::MSG_ERROR);
                    }
                }
                if(empty($withdraw['bind_card_id'])) {
                    $user_bank = CardInfo::find()->where(['user_id' => $uid, 'card_no' => $withdraw['card_no']])->one(Yii::$app->get('db_kdkj_rd'));
                } else {
                    if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                        $user_bank = CardInfo::find()->where(['user_id' => $uid, 'id' => $withdraw['bind_card_id']])->one(Yii::$app->get('db_kdkj_rd'));
                    }else{
                        $user_bank = CardInfo::find()->where(['id' => $withdraw['bind_card_id']])->one(Yii::$app->get('db_kdkj_rd'));
                    }
                }
                if (empty($user_bank)) throw new Exception('ID:'.$id."无对应用户的银行卡记录uid({$uid})：", self::MSG_ERROR);
                $params['FinancialLoanRecord']['review_remark'] = $withdraw['review_remark'].$params['FinancialLoanRecord']['review_remark'];
                if ($withdraw->load($params)) {
                    switch ($withdraw['review_result']){
                        case FinancialLoanRecord::REVIEW_STATUS_APPROVE :
                            //审核通过之前根据业务类型先对账
                            try {
                                if(empty($this->financialService)){
                                    $this->financialService = new FinancialService();
                                }
                                $this->financialService->withdrawCheckLoanOrder($withdraw);
                            } catch (Exception $e) {
                                throw new Exception('ID:'.$id.' '.$e->getMessage(), self::MSG_ERROR);
                            }
                            $this->newWithdrawApproveWithoutRes($id, $withdraw['payment_type'], $withdraw['review_remark']);
                            break;
                        case FinancialLoanRecord::REVIEW_STATUS_REJECT :
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try {
                                $withdraw['review_remark'] = trim($withdraw['review_remark']);
                                if (empty($withdraw['review_remark'])) {
                                    throw new Exception('ID:'.$id." 审核驳回请填写驳回原因！");
                                }
                                try {
                                    $service = Yii::$container->get('financialCommonService');
                                    $back_result = $service->rejectLoanOrder($withdraw->business_id, $withdraw['review_remark'], Yii::$app->user->identity->username, $withdraw['type']);//驳回
                                    if ($back_result['code'] !== 0) {
                                        throw new Exception('ID:'.$id.' '.$back_result['message']);
                                    }
                                } catch (Exception $e) {
                                    throw new Exception('ID:'.$id." 审核驳回通知业务方失败：".$e->getMessage());
                                }
                                $callback_result = [
                                    'is_notify' => FinancialLoanRecord::NOTIFY_SUCCESS,
                                    'message' =>  $back_result['message']
                                ];
                                $withdraw->status = FinancialLoanRecord::UMP_PAY_FAILED;
                                $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_REJECT;
                                $withdraw->review_remark = $withdraw['review_remark'];
                                $withdraw->review_username = Yii::$app->user->identity->username;
                                $withdraw->review_time = time();
                                $withdraw->callback_result = json_encode($callback_result);
                                if (!$withdraw->save()) throw new Exception('ID:'.$id." 状态修改操作失败");
                                $transaction->commit();

                            } catch (\Exception $e) {
                                $transaction->rollBack();
                                throw new Exception($e->getMessage(),self::MSG_ERROR);
                            }
                            break;
                        default:
                            throw new Exception('ID:'.$id." 未知状态!");
                    }
                }
            }
            return $this->redirectMessage('所选记录批量操作完成',self::MSG_SUCCESS);
        } catch (Exception $ex) {
            return $this->redirectMessage($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * 提现审核通过
     * @param $id               用户提现ID
     * @param $payment_type     打款类型
     * @param $review_remark    审核备注
     * @return string
     * @author zhangxiaoguang@koudailc.com
     */
    protected function newWithdrawApprove($id, $payment_type, $review_remark) {
        try {
            $withdraw = FinancialLoanRecord::findOne($id);
            if (empty($withdraw)) {
                return $this->redirectMessage("抱歉，不存在此提现ID".$id, self::MSG_ERROR);
            }
            $this->financialService->newWithdrawApprove($id, $payment_type, $review_remark, Yii::$app->user->identity->username);
            if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                $withdraw->status = FinancialLoanRecord::UMP_PAYING;
                $withdraw->save();
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
            } elseif (in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)) {
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
            }
        } catch (\Exception $e) {
            return $this->redirectMessage('操作出现异常：' . $e->getMessage() . "(". $e->getCode() .")", self::MSG_ERROR);
        }
    }

    /**
     * 提现审核通过(处理失败不显示到页面)
     * @param $id               用户提现ID
     * @param $payment_type     打款类型
     * @param $review_remark    审核备注
     * @return string
     * @author zhangyuliang@koudailc.com
     */
    protected function newWithdrawApproveWithoutRes($id, $payment_type, $review_remark)
    {
        try {
            $withdraw = FinancialLoanRecord::findOne($id);
            if (empty($withdraw)) throw new Exception("抱歉，不存在此提现ID".$id, self::MSG_ERROR);
            $this->financialService->newWithdrawApprove($id, $payment_type, $review_remark, Yii::$app->user->identity->username);
            if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                $withdraw->status = FinancialLoanRecord::UMP_PAYING;
                $withdraw->save();
            }
        } catch (\Exception $e) {
            throw new Exception('操作出现异常：' . $e->getMessage(),$e->getCode());
        }
    }

    /**
     * 提现审核驳回
     * @param $id 用户提现ID
     * @param $review_remark 审核备注
     * @return string
     * @author zhangxiaoguang@koudailc.com
     *
     */
    protected function newWithdrawReject($id, $review_remark) {
        try {
            $withdraw = FinancialLoanRecord::findOne($id);
            if (empty($withdraw)) {
                return $this->redirectMessage("抱歉，不存在此提现ID".$id, self::MSG_ERROR);
            }
            $this->accountService->newWithdrawReject(intval($id), $review_remark, Yii::$app->user->identity->username);
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        } catch (\Exception $e) {
            return $this->redirectMessage('操作出现异常：' . $e->getMessage(), self::MSG_ERROR);
        }
    }
    /**
     * @name 财务管理-小钱包打款扣款管理-打款列表-操作/actionWithdrawResult
     */
    public function actionWithdrawResult($id, $order_id)
    {
        $withdraw_result = FinancialLoanRecord::find()->where(['id' => $id])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if(empty($withdraw_result))
        {
            return $this->redirectMessage("无此提现记录({$id})：", self::MSG_ERROR);
        }
        $uid = $withdraw_result['user_id'];
        $user = LoanPerson::find()->where(['id' => $uid])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if(empty($user))
        {
            return $this->redirectMessage("无此用户提现记录({$uid})：", self::MSG_ERROR);
        }
        $status_desc = !empty(FinancialLoanRecord::$ump_pay_status[$withdraw_result['status']]) ? FinancialLoanRecord::$ump_pay_status[$withdraw_result['status']] : "无效状态";
        $type = $withdraw_result['type'];
        $withdraw_info = [
            'id' => $withdraw_result['id'],
            'business_id' => $withdraw_result['business_id'],
            'bind_card_id' => $withdraw_result['bind_card_id'],
            'user_id' => $withdraw_result['user_id'],
            'user_name' => $user['phone'],
            'user_realname' => $user['name'],
            'user_birthday' => $user['birthday'],
            'order_id' => $withdraw_result['order_id'],
            'money' => $withdraw_result['money'] / 100 ."元",
            'counter_fee' => $withdraw_result['counter_fee'] / 100 ."元",
            'true_money' => (($withdraw_result['money'] - $withdraw_result['counter_fee']) / 100)."元",
            'type' => $type,
            'payment_type' => $withdraw_result['payment_type'],
            'pay_summary' => $withdraw_result['pay_summary'],
            'status' => $withdraw_result['status'],
            'status_desc' => $status_desc,
            'review_username' => $withdraw_result['review_username'],
            'review_result' => $withdraw_result['review_result'],
            'review_time' => $withdraw_result['review_time'] ? date("Y-m-d H:i:s",$withdraw_result['review_time']) : '-',
            'created_at' => date("Y-m-d H:i:s",$withdraw_result['created_at']),
            'updated_at' => date("Y-m-d H:i:s",$withdraw_result['updated_at']),
            'bank_name' => $withdraw_result['bank_name'],
            'card_no' => $withdraw_result['card_no'],
            'result' => $withdraw_result['result'],
            'notify_result' => $withdraw_result['notify_result'],
            'callback_result' => $withdraw_result['callback_result'],
        ];
        $withdraw = FinancialLoanRecord::findOne(['order_id' => $order_id]);
        if ($this->request->getIsPost())
        {
            $submit_btn_sd = $this->request->post('submit_btn_sd');
            $submit_btn_zl = $this->request->post('submit_btn_zl');
            $submit_btn_tj = $this->request->post('submit_btn_tj');
            if($submit_btn_sd){
                try{
                    $loanTime = $this->request->post('loanTime');
                    if (!$loanTime) {
                        throw new Exception("人工打款时间必填！");
                    }
                    $handle_result = $this->financialService->withdrawHandleSuccess($order_id, 1);
                    if (!$handle_result) {
                        throw new Exception("人工打款成功更改数据库失败！");
                    }
                    $withdrawInfo = FinancialLoanRecord::findOne($id);
                    try {
                        //提现到账后处理
                        $service = Yii::$container->get('financialCommonService');
                        $back_result = $service->successLoanOrder($withdrawInfo->business_id, '打款成功，手动发起回调',  Yii::$app->user->identity->username, $withdrawInfo->type,strtotime($loanTime));
                        $is_notify = ($back_result['code'] === 0) ? FinancialLoanRecord::NOTIFY_SUCCESS : FinancialLoanRecord::NOTIFY_FALSE;
                    } catch (Exception $e) {
                        $is_notify = FinancialLoanRecord::NOTIFY_FALSE;
                    }
                    $callback_result = [
                        'is_notify' => $is_notify,
                        'message' =>  isset($back_result['message']) ? $back_result['message'] : ''
                    ];
                    $withdrawInfo->callback_result = json_encode($callback_result);
                    $withdrawInfo->success_time = strtotime($loanTime);
                    if (!$withdrawInfo->save()) {
                        throw new Exception("人工打款成功通知业务方更改数据库失败！");
                    }
                    if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                    } elseif (in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                    }
                } catch (Exception $e) {
                    return $this->redirectMessage($e->getMessage(), self::MSG_ERROR);
                }
            }
            if($submit_btn_zl){
                if($this->financialService->withdrawHandleSuccess($order_id, 2)) {
                    $loanTime = $this->request->post('loanTime');
                    if(!$loanTime){
                        $loanTime = date('Y-m-d H:i:s');
                    }
                    $withdrawInfo = FinancialLoanRecord::findOne($id);
                    try {
                        //提现到账后处理
                        $service = Yii::$container->get('financialCommonService');
                        $back_result = $service->successLoanOrder($withdrawInfo->business_id, '打款成功，手动发起回调',  Yii::$app->user->identity->username, $withdrawInfo->type,strtotime($loanTime));
                        $is_notify = ($back_result['code'] === 0) ? FinancialLoanRecord::NOTIFY_SUCCESS : FinancialLoanRecord::NOTIFY_FALSE;
                    } catch (Exception $e) {
                        $is_notify = FinancialLoanRecord::NOTIFY_FALSE;
                    }
                    $callback_result = [
                        'is_notify' => $is_notify,
                        'message' =>  isset($back_result['message']) ? $back_result['message'] : ''
                    ];
                    $withdrawInfo->callback_result = json_encode($callback_result);
                    $withdrawInfo->success_time = strtotime($loanTime);
                    if (!$withdrawInfo->save()) {
                        throw new Exception("人工打款成功通知业务方更改数据库失败！");
                    }

                    if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                    } elseif (in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                    }

                } else {
                    return $this->redirectMessage("重置提现状态失败", self::MSG_ERROR);
                }
            }
            if($submit_btn_tj){
                $iOriginReviewResult = $withdraw->review_result; //原审核状态
                $iOriginStatus = $withdraw->status;//原提现状态
                $iOriginOrderID = $withdraw->order_id;//原订单id
                $newOrderID = $this->request->post('newOrderID');
                $remarkMessage = $this->request->post('remarkMessage');
                if(empty($remarkMessage)){
                    return $this->redirectMessage("重新发起备注不能为空，请务必填写！", self::MSG_ERROR);
                }
                $withdraw->status = FinancialLoanRecord::UMP_PAYING;
                $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_NO;
                if(!empty($newOrderID)){
                    $withdraw->order_id = $newOrderID;
                }
                $iDate = date("Y-m-d H:i:s", time());
                $withdraw->review_remark =  "时间：".$iDate."。操作人：".Yii::$app->user->identity->username."。备注：".$remarkMessage."。原订单ID为：".$iOriginOrderID."。原审核状态：".$iOriginReviewResult."。原提现状态：".$iOriginStatus;
                if($withdraw->save()){
                    if (in_array($withdraw['type'], FinancialLoanRecord::$kd_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                    } elseif (in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)) {
                        return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                    }
                }
                return $this->redirectMessage("提交失败", self::MSG_ERROR);
            }

        }
        if(empty($withdraw))
        {
            return $this->redirectMessage("操作出现异常：未找到提现记录(2107)", self::MSG_ERROR);
        }
        $card_info = CardInfo::find()->where(['id' => $withdraw_info['bind_card_id']])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if(empty($card_info))
        {
            return $this->redirectMessage("无此用户提现银行卡({$uid})：", self::MSG_ERROR);
        }
        $payment_type = $withdraw['payment_type'];
        switch ($payment_type) {
            case FinancialLoanRecord::PAYMENT_TYPE_CMB:
                $result['order_id'] = $withdraw_result['order_id'];
                $result['amount']   = $withdraw_result['money'];
                $result['order_date'] = $withdraw_result["created_at"];
                return $this->render('withdraw-result-cmb', [
                    'result' => $result,
                    'withdraw_info' => $withdraw_info,
                    'card_info' => $card_info
                ]);
                break;
            case FinancialLoanRecord::PAYMENT_TYPE_MANUAL:
                $result['order_id'] = $withdraw_result['order_id'];
                $result['amount'] = $withdraw_result['money'];
                $result['order_date'] = $withdraw_result["created_at"];
                return $this->render('withdraw-result-rengong', [
                    'result' => $result,
                    'withdraw_info' => $withdraw_info,
                    'card_info' => $card_info
                ]);
                break;
            default:
                return $this->redirectMessage("操作出现异常：不支持的打款类型。", self::MSG_ERROR);
                break;
        }
    }

    /**
     * 直连失败体现中置为审核通过体现中
     * @return string
     */
    public function actionReviewSuccess(){
        $id = $this->request->get("id");
        $user_order = FinancialLoanRecord::findOne($id);
        if(($user_order->status == FinancialLoanRecord::UMP_PAYING && $user_order->review_result == FinancialLoanRecord::REVIEW_STATUS_NO) || ($user_order->status == FinancialLoanRecord::UMP_PAYING && $user_order->review_result == FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED)){
            $user_order->status = FinancialLoanRecord::UMP_PAYING;
            $user_order->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
            $user_order->payment_type = FinancialLoanRecord::PAYMENT_TYPE_CMB;
            $user_order->review_username = "backsql";
            if($user_order->save()){
                if (in_array($user_order['type'], FinancialLoanRecord::$kd_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
                } elseif (in_array($user_order['type'], FinancialLoanRecord::$other_platform_type)) {
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('financial/loan-other-list'));
                }
            }
            else{
                return $this->redirectMessage("重置提现状态失败", self::MSG_ERROR);
            }
        }
        return $this->redirectMessage("抱歉，初始状态不为直连失败体现中、或者未审核体现中。", self::MSG_ERROR);
    }
    public function getDebitFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['username'])) {
                $username = $search['username'];
                $result = LoanPerson::find()->select(['id'])->where(['name' => $username])->all();
                if ($result) {
                    $uid = [];
                    foreach($result as $id){
                        $uid[] = intval($id['id']);
                    }
                    $uid = implode(',',$uid);
                    $condition .= " AND l.user_id in ({$uid}) ";
                }else{
                    $condition .= " AND l.user_id = 0" ;
                }
            }
            if (!empty($search['phone'])) {
                $phone = $search['phone'];
                $result = LoanPerson::find() -> where(['phone' => $phone]) -> one(Yii::$app->get('db_kdkj_rd'));
                if($result){
                    $uid = $result["id"];
                    $condition .= " AND l.user_id = " . intval($uid);
                }else{
                    $condition .= " AND l.user_id = 0";
                }
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = " . intval($search['user_id']);
            }
            if (!empty($search['id'])) {
                $condition .= " AND l.id = " . "'".$search['id']."'";
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.order_id = " . "'".$search['order_id']."'";
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND l.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] != null) {
                $condition .= " AND l.type = " . intval($search['type']);
            }
            if (isset($search['platform']) && $search['platform'] !== '') {
                $condition .= " AND l.platform = " . intval($search['platform']);
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at < " . strtotime($search['endtime']);
            }
            if (!empty($search['updated_at_begin'])) {
                $condition .= " AND l.updated_at >= " . strtotime($search['updated_at_begin']);
            }
            if (!empty($search['updated_at_end'])) {
                $condition .= " AND l.updated_at < " . strtotime($search['updated_at_end']);
            }

            if (isset($search['fund_id']) && !empty($search['fund_id'] && $search['fund_id']>0)) {

                if($search['fund_id'] == LoanFund::ID_KOUDAI){
                    $condition .= " AND ".userLoanOrder::tableName().".fund_id IN (" . LoanFund::ID_KOUDAI .", 0 ) ";
                }else{
                    $condition  .= " AND ".userLoanOrder::tableName().".fund_id = ".(int)$search['fund_id'];
                }
            }
        }
        return $condition;
    }

    /**
     * @return string
     * !@name 财务管理-小钱包打款扣款管理-待扣款列表/actionDebitWaitList
     */
    public function actionDebitWaitList() {
        $_GET['status'] = FinancialDebitRecord::STATUS_PAYING;
        $_GET['search_submit'] = 1;
        $tip = 2;
        return $this->actionDebitList(['tip'=>$tip]);
    }
    /**
     * @return string
     * @name 支付宝交易记录
     */
    public function actionAlipayRecord($type='list'){
        if($this->request->getIsPost()){
            $post = $this->request->post();
            $source = $post['source_id'] ?? 0;
            $data='';
            foreach ($post['alipayTime'] as $k=>$v){
                $weixinExtend = trim($post['alipayExtend'][$k]??''); //是否续借
                if($weixinExtend=='' || empty($weixinExtend)){
                    $weixinExtend=0;
                }
                $weixinTime = trim($post['alipayTime'][$k]); //时间
                $weixinRecord = trim($post['alipayRecord'][$k]); //订单号
                $weixinMoney = trim($post['alipayMoney'][$k]); //金额
                $weixinAccount = trim($post['alipayUser'][$k]); //账户
                $weixinName = trim($post['alipayName'][$k]); //姓名
                $weixinRemark = trim($post['alipayRemark'][$k]); //备注

                if (empty($weixinRecord)) {
                    continue;
                }var_dump(AlipayRepaymentLog::findOne(['alipay_order_id' => $weixinRecord]));exit;
                if (AlipayRepaymentLog::findOne(['alipay_order_id' => $weixinRecord])) {
                    continue;
                }
                $data .= $weixinTime . ' ****** ' . $weixinRecord . ' ****** ' . $weixinMoney . ' ****** ' . $weixinAccount . ' ****** ' .
                    $weixinName . ' ****** ' . $weixinRemark . ' ****** '.$weixinExtend . ' @@@@@@ ';

            }var_dump(1);
            if (empty($data)) {
                return $this->redirectMessage('没有新数据，无需更新', Url::toRoute('financial/alipay-record'));
            }
            $timestamp = 'dsf@#$%&*dsfk';
            $sign = strtolower(md5($timestamp . '#abc!@#'));
            $params = [
                'data' => $data,
                'timestamp' => $timestamp,
                'sign' => $sign,
                'source' => $source
            ];var_dump(2);
            if (AlipayRepaymentLog::insertIgnore($params)) {
                return $this->redirectMessage('数据插入成功', self::MSG_SUCCESS, Url::toRoute('financial/alipay-record'));
            }
            return $this->redirectMessage('数据插入失败', self::MSG_ERROR, Url::toRoute('financial/alipay-record'));
        }var_dump(3);

        //还款列表部分
        $condition = '1 = 1 ';
        $pages = new Pagination();
        $info=[];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();var_dump($search);
            if((isset($search['user_id']) && !empty($search['user_id']))||(isset($search['order_id']) && !empty($search['order_id']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['user_id']) && !empty($search['user_id'])) {
                    $condition .= " AND  ".LoanPerson::tableName().".id = " . intval($search['user_id']);
                }
                if (isset($search['order_id']) && !empty($search['order_id'])) {
                    $condition .= " AND ".UserLoanOrderRepayment::tableName().".order_id = " . intval($search['order_id']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND ".LoanPerson::tableName().".name =  '{$search['name']}'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND ".LoanPerson::tableName().".phone = '{$search['phone']}'";
                }
                $query = UserLoanOrderRepayment::find()->orderBy([UserLoanOrderRepayment::tableName().".id" => SORT_DESC]);
                $query->joinWith([
                    'loanPerson' => function (Query $query) {
                        $query->select(['id','name','phone']);
                    },
                    'userLoanOrder' => function (Query $query) {
                        $query->select(['order_type']);
                    }
                ])->where($condition)->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD);

                $countQuery = clone $query;
                $pages->totalCount=$countQuery->count('*',UserLoanOrderRepayment::getDb_rd());
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->all(UserLoanOrderRepayment::getDb_rd());
                if($ret)
                    $info = $ret;
            }
        }

        return $this->render('alipay-record',[
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ]);
    }
    /**
     * @return string
     * @name 财务管理-小钱包打款扣款管理-扣款失败列表/actionDebitFaliedList
     */
    public function actionDebitFaliedList() {
        $_GET['status'] = FinancialDebitRecord::STATUS_FALSE;
        $_GET['search_submit'] = 1;
        $tip = 1;
        return $this->actionDebitList(['tip'=>$tip]);
    }
    public function actionWeixinList(){
        $condition = $this->getWeixinFilter();
        $query = WeixinRepaymentLog::find()->where($condition);
//        if($this->request->get('submitcsv') == 'exportcsv'){
//            $query->orderBy(['id' => SORT_DESC]);
//            return $this->_exportAlipayList($query);
//        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->orderBy(['id' => SORT_DESC])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        $dataSt = $countQuery->select('sum(money) as money,sum(CASE WHEN status ='.WeixinRepaymentLog::STATUS_FINISH.' THEN money END) as success_money,sum(CASE WHEN status ='.AlipayRepaymentLog::STATUS_BACK.'  THEN money END) as reject_money')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        return $this->render('weixin-list', [
            'info' => $info,
            'dataSt' => $dataSt,
            'pages' => $pages,
        ]);
    }

    /**
     * @return string
     * @name 添加微信交易流水
     */
    public function actionWeixinRecord($type='list'){
        if($this->request->getIsPost()){
            $post = $this->request->post();
            $source = $post['source_id'] ?? 0;
            $data='';
            foreach ($post['weixinTime'] as $k=>$v){
                $weixinExtend = trim($post['weixinExtend'][$k]??''); //是否续借
                if($weixinExtend=='' || empty($weixinExtend)){
                    $weixinExtend=0;
                }
                $weixinTime = trim($post['weixinTime'][$k]); //时间
                $weixinRecord = trim($post['weixinRecord'][$k]); //订单号
                $weixinMoney = trim($post['weixinMoney'][$k]); //金额
                $weixinAccount = trim($post['weixinUser'][$k]); //账户
                $weixinName = trim($post['weixinName'][$k]); //姓名
                $weixinRemark = trim($post['weixinRemark'][$k]); //备注

                if (empty($weixinRecord)) {
                    continue;
                }
                if (WeixinRepaymentLog::findOne(['weixin_order_id' => $weixinRecord])) {
                    continue;
                }
                $data .= $weixinTime . ' ****** ' . $weixinRecord . ' ****** ' . $weixinMoney . ' ****** ' . $weixinAccount . ' ****** ' .
                    $weixinName . ' ****** ' . $weixinRemark . ' ****** '.$weixinExtend . ' @@@@@@ ';

            }
            if (empty($data)) {
                return $this->redirectMessage('没有新数据，无需更新', Url::toRoute('financial/weixin-record'));
            }
            $timestamp = 'dsf@#$%&*dsfk';
            $sign = strtolower(md5($timestamp . '#abc!@#'));
            $params = [
                'data' => $data,
                'timestamp' => $timestamp,
                'sign' => $sign,
                'source' => $source
            ];
            if (WeixinRepaymentLog::insertIgnore($params)) {
                return $this->redirectMessage('数据插入成功', self::MSG_SUCCESS, Url::toRoute('financial/weixin-record'));
            }
            return $this->redirectMessage('数据插入失败', self::MSG_ERROR, Url::toRoute('financial/weixin-record'));
        }

        //还款列表部分
        $condition = '1 = 1 ';
        $pages = new Pagination();
        $info=[];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if((isset($search['user_id']) && !empty($search['user_id']))||(isset($search['order_id']) && !empty($search['order_id']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['user_id']) && !empty($search['user_id'])) {
                    $condition .= " AND  ".LoanPerson::tableName().".id = " . intval($search['user_id']);
                }
                if (isset($search['order_id']) && !empty($search['order_id'])) {
                    $condition .= " AND ".UserLoanOrderRepayment::tableName().".order_id = " . intval($search['order_id']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND ".LoanPerson::tableName().".name =  '{$search['name']}'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND ".LoanPerson::tableName().".phone = '{$search['phone']}'";
                }
                $query = UserLoanOrderRepayment::find()->orderBy([UserLoanOrderRepayment::tableName().".id" => SORT_DESC]);
                $query->joinWith([
                    'loanPerson' => function (Query $query) {
                        $query->select(['id','name','phone']);
                    },
                    'userLoanOrder' => function (Query $query) {
                        $query->select(['order_type']);
                    }
                ])->where($condition)->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD);

                $countQuery = clone $query;
                $pages->totalCount=$countQuery->count('*',UserLoanOrderRepayment::getDb_rd());
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->all(UserLoanOrderRepayment::getDb_rd());
                if($ret)
                    $info = $ret;
            }
        }

        return $this->render('weixin-record',[
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ]);
    }
    /**
     * @name 财务管理-小钱包打款扣款列表-扣款列表/actionDebitList
     * @name 扣款明细
     */
    public function actionDebitList($tip = 0) {
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDebitInfos(FinancialDebitRecord::$kd_platform_type);
        }
        $msg = '';
        if(($params = $this->request->post()) && $this->getRequest()->getIsPost()){
            $params['id'];
            $debit_record = FinancialDebitRecord::find()->where(['id'=>$params['id']])->one(Yii::$app->get('db_kdkj_rd'));
            $debit_record->remark_two = $params['remark_two'];
            $res = $debit_record->save();
            $msg = $res?'设置备注成功':'设置备注失败';
        }
        $condition = $this->getDebitFilter();
        $query = FinancialDebitRecord::find()->from(FinancialDebitRecord::tableName().' as l')
            ->select("l.*")
            ->where(['in', 'l.type', FinancialDebitRecord::$kd_platform_type])->andWhere($condition);
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        if($condition='1=1' && $this->request->get('is_summary')!=1)
        {
            $count = 9999999;
        } else {
            /* @var $db \yii\db\Connection */
            $count = $countQuery->count('*',Yii::$app->get('db_kdkj_rd'));
//            $count = $db->cache(function($db) use ($countQuery) {
//                return $countQuery->count('1',$db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;

        $info = $query->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id']);
            },

        ])->joinWith([
            'userLoanOrder'=> function($userLoanOrder) {
                $userLoanOrder->select(['id','fund_id']);
            },

        ])->orderBy(['l.id' => SORT_DESC])->offset($pages->offset)->limit($pages->limit)->all($db);

        $dataSt = '';
        if($this->request->get('is_summary')==1)
            $dataSt = $countQuery->select('sum(true_repayment_money) as true_repayment_money')->one($db);

        //echo $countQuery->select('sum(true_repayment_money) as money')->createCommand()->getRawSql();die;
        $type = FinancialDebitRecord::$types;
        unset($type[FinancialDebitRecord::TYPE_AST]);

        return $this->render('debit-list', [
            'info' => $info,
            'dataSt' =>$dataSt,
            'pages' => $pages,
            'type' => $type,
            'tip' => $tip['tip']
            //'msg' => $msg,
        ]);
    }


    /**
     * @return string
     * @name获取第三方的最新结果/actionGetNewCode
     */
    public function actionGetNewCode(){
        $data = $this->request->get();
        $this->response->format = Response::FORMAT_JSON;
        $id = $data['id'];
        $item = UserCreditMoneyLog::findOne(['id'=>$id]);
        $url = "http://test.abc.com";
        $params['order_id'] = $item['order_uuid'];
        if (UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP == $item->payment_type)
        {
            $params['project_name'] = FinancialService::KD_PROJECT_NAME_ALIPAY;
        } else {
            $userLoanOrder = UserLoanOrder::findOne(['id' => $item->order_id]);
            switch ($userLoanOrder -> fund_id) {
                case LoanFund::ID_KOUDAI:
                    $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                    break;

                default:
                    $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                    break;
            }
        }
        $sign = \common\models\Order::getPaySign($params,$params['project_name']);
        $params['sign'] = $sign;
        $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $params,20);
        $code = $ret['data']['state'];
        if($ret['code'] == 0){
            $item->pay_order_id = $ret['data']['pay_order_id'];
            $item->debit_channel = $ret['data']['third_platform'];
            if($ret['data']['state'] == 2 && $item->success_repayment_time == 0){
                $time = strtotime("{$ret['data']['pay_date']}") + 24*3600 -1;
                $item->success_repayment_time = $time;
            }
        }
        $item->service_code = $code;
        if($item->save()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @name 合作资产-合作方打款扣款管理-扣款列表/actionDebitOtherList
     */
    public function actionDebitOtherList() {
        $condition = $this->getDebitFilter();
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDebitInfos(FinancialDebitRecord::$other_platform_type);
        }
        $query = FinancialDebitRecord::find()->where(['in', 'type', FinancialDebitRecord::$other_platform_type])->andWhere($condition)
            ->with([
                'userLoanOrder'=> function($queryCard) {
                    $queryCard->select(['fund_id']);
                },
            ])
            ->orderBy('created_at desc');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        $type = FinancialDebitRecord::$types;
        unset($type[FinancialDebitRecord::TYPE_YGB]);
        return $this->render('debit-list', [
            'info' => $info,
            'pages' => $pages,
            'type' => $type,
        ]);
    }

    /**
     * @name 财务管理-小钱包打款扣款管理-扣款列表/actionDebitDetail
     * @name 订单详情
     */
    public function actionDebitDetail() {
        $id = $this->request->get("id");
        $data = FinancialDebitRecord::find()->where(['id' => $id])->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id', 'contact_username', 'is_verify', 'contact_phone', 'card_bind_status']);
            },
            'cardInfo'=> function($queryCard) {
                $queryCard->select(['bank_name', 'id', 'card_no','type', 'status']);
            },
            'userLoanOrder',
            'userVerification',
        ])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        return $this->render('debit-detail', [
            'info' => $data
        ]);
    }

    /**
     * @name 财务管理-小钱包打款扣款管理-待扣款列表-扣款/actionAddDebit
     */
    public function actionAddDebit() {
        $id = $this->request->get("id");
        $user_id = $this->request->get("user_id");
        $data = FinancialDebitRecord::find()->where(['id' => $id, 'user_id' => $user_id])->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id', 'id_number', 'contact_username', 'is_verify', 'id_number', 'contact_phone', 'card_bind_status']);
            },
            'cardInfo'=> function($queryCard) {
                $queryCard->select(['bank_name', 'bank_id', 'id', 'card_no','type', 'phone', 'status']);
            },
            'userLoanOrder',
        ])->asArray()->one(Yii::$app->get('db_kdkj_rd'));

        $card_no = $data['cardInfo']['card_no'];
        $yeepay_times =cardInfo::CARD_DEBIT_MAX_TIMES-cardInfo::getCardDebitTimes($card_no,BankConfig::PLATFORM_YEEPAY);
        if($yeepay_times<0)
            $yeepay_times = 0;
        $ldys_times = cardInfo::CARD_DEBIT_MAX_TIMES-cardInfo::getCardDebitTimes($card_no,BankConfig::PLATFORM_UMPAY);
        if($ldys_times<0)
            $ldys_times = 0;
        $bf_times = cardInfo::CARD_DEBIT_MAX_TIMES-cardInfo::getCardDebitTimes($card_no,BankConfig::PLATFORM_BFPAY);
        if($bf_times<0)
            $bf_times = 0;
        $kjt_times = cardInfo::CARD_DEBIT_MAX_TIMES-cardInfo::getCardDebitTimes($card_no,BankConfig::PLATFORM_KUAIJIETONG);
        if($kjt_times<0)
            $kjt_times = 0;
        return $this->render('add-debit-detail', [
            'info' => $data,
            'yeepay_times'=>$yeepay_times,
            'ldys_times' =>$ldys_times,
            'bf_times'=>$bf_times,
            'kjt_times' => $kjt_times
        ]);
    }

    /**
     * 扣款
     * @throws \yii\base\Exception
     * @name 财务系统扣款操作/actionDebitRecord
     */
    public function actionDebitRecord() {
        $id = $this->request->get("id");
        $params = \Yii::$app->request->post();
        $params['username'] = Yii::$app->user->identity->username;
        try {
            $data = FinancialDebitRecord::findOne(['id' => $id]);
//			if(!FinancialDebitRecord::addDebitLock($data->loan_record_id)){
//				return $this->redirectMessage('扣款失败,该用户有还款请求正在处理中', self::MSG_ERROR);
//			}
            if($data->status != FinancialDebitRecord::STATUS_PAYING){
                return $this->redirectMessage('扣款失败,该记录非代扣款状态', self::MSG_ERROR);
            }
            $UserCreditMoneyLog = UserCreditMoneyLog::find()->where([
                'user_id' => $data->user_id ,
                'order_id' => $data->loan_record_id
            ])->orderBy(['id'=>SORT_DESC])->one();
            if($UserCreditMoneyLog && ($UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_ING || $UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_NORMAL)){
                return $this->redirectMessage('扣款失败,该用户有主动还款请求正在处理中', self::MSG_ERROR);
            }
            $infos = UserLoanOrder::getOrderRepaymentCard($data->loan_record_id, $data->user_id);
            $loanPerson = LoanPerson::findOne($data->user_id);
            $amount=$params['amount'];
            $extra = [
                'money' => StringHelper::safeConvertCentToInt($amount),
                'debit_type' => AutoDebitLog::DEBIT_TYPE_SYS,
                'real_name' => $loanPerson->name,
                'id_card' => $loanPerson->id_number
            ];

            $loanService = new LoanService();
            //设置还款还款方式为用户主动还款
            $ret = $loanService->applyDebitNew($infos['order'], $infos['repayment'], $infos['card_info'], $extra);
            if($ret['code']==0){
                $order=$infos['order'];
                $adl=AutoDebitLog::find()->where(['user_id'=>$order['user_id'],'order_id'=>$order['id']])->orderBy('id DESC')->one();
                if($adl){
                    $order_uuid=$adl->order_uuid;
                    if(intval($adl->money)==intval($extra['money'])){
                        $data->status = FinancialDebitRecord::STATUS_RECALL;
                        $data->order_id = $order_uuid;
                        $data->update();
                    }
                }
                //提交扣款成功
                return $this->redirectMessage('该代扣已经提交成功，系统正在处理中...', self::MSG_SUCCESS);
            }else{
                //提交扣款失败
                $msg=$ret['msg'];
                return $this->redirectMessage($msg, self::MSG_ERROR);
            }
        }catch (Exception $e){
            return $this->redirectMessage($e->getMessage(), self::MSG_ERROR);
        }
    }

    /**
     * @name 重新发起扣款
     * @return string
     */
    public function actionReAddDebit() {
        $id  = \Yii::$app->request->get("id");
        $debit = FinancialDebitRecord::findOne($id);
        if (empty($debit) || !in_array($debit->status, [FinancialDebitRecord::STATUS_FALSE, FinancialDebitRecord::STATUS_PAYING])) {
            return $this->redirectMessage('抱歉，不存在此扣款订单或者状态不为扣款失败或者待扣款。', self::MSG_ERROR);
        }
        $debit->status = FinancialDebitRecord::STATUS_PAYING;
        $debit->repayment_time = 0;
        $debit->remark = $debit->remark."重新发起扣款(".date("y-m-d H:i", time()).")";
        $debit->callback_result = "";
        if ($debit->save()) {
            return $this->redirectMessage("重新发起扣款成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-list'));
        }
        return $this->redirectMessage("重新发起扣款失败", self::MSG_ERROR);
    }

    /**
     *
     * @return string
     * @name 财务管理-小钱包打款扣款管理-待扣款列表-驳回/actionDebitRefuse
     */
    public function actionDebitRefuse() {
        $id  = \Yii::$app->request->get("id");
        $debit = FinancialDebitRecord::findOne($id);
        if (empty($debit) || ($debit->status != FinancialDebitRecord::STATUS_PAYING)) {
            return $this->redirectMessage('抱歉，不存在此扣款订单或者状态不为待扣款。', self::MSG_ERROR);
        }

        if (Yii::$app->request->post("submit_btn"))
        {
            $id = Yii::$app->request->post("id");
            $remark = Yii::$app->request->post("remark");
            if (empty($remark) || empty($remark) || !in_array($remark, [1, 2])) {
                return $this->redirectMessage('抱歉，必要参数不能为空。', self::MSG_ERROR);
            }
            $data = [
                'type' => $remark,
                'message'  => $remark == 1 ? '客户要求取消扣款' : '订单异常驳回' ,
            ];
            $service = Yii::$container->get('financialCommonService');
            $result = $service->rejectDebitOrder($debit, $data, Yii::$app->user->identity->username);
            $callback_result = [
                'code' => $result['code'],
                'message' => $result['message']
            ];
            $debit = FinancialDebitRecord::findOne($id);
            $debit->status = FinancialDebitRecord::STATUS_REFUSE;
            $debit->remark = $remark == 1 ? '客户要求取消扣款' : '订单异常驳回';
            $debit->callback_result = json_encode($callback_result);
            if ($debit->save()) {
                return $this->redirectMessage("您好，驳回成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-list'));
            }
            return $this->redirectMessage("驳回失败", self::MSG_ERROR);
        }
        return $this->render("debit-refuse", [
            'info' => $debit
        ]);
    }


    /**
     * 扣款失败通知业务方
     */
    public function actionDebitNotice() {
        $id  = Yii::$app->request->post("id");
        $debitremark  = Yii::$app->request->post("debitremark");
        if (empty($debitremark) || empty($id)) {
            return $this->redirectMessage('抱歉，备注不能为空。', self::MSG_ERROR);
        }
        $debit = FinancialDebitRecord::findOne($id);
        if (empty($debit) || ($debit->status != FinancialDebitRecord::STATUS_FALSE)) {
            return $this->redirectMessage('抱歉，不存在此扣款订单或者状态不为扣款失败。', self::MSG_ERROR);
        }
        $service = new FinancialCommonService();
        $data = [
            'type' => 1,//1 代表客户失败原因
            'message' => $debitremark,
        ];
        $result = $service->falseDebitOrder($debit, $data, Yii::$app->user->identity->username);
        $callback_result = [
            'code' => $result['code'],
            'message' => $result['message']
        ];
        $debit = FinancialDebitRecord::findOne($id);
        $debit->remark = $debitremark;
        $debit->callback_result = json_encode($callback_result);
        if ($debit->save()) {
            if (in_array($debit['type'], FinancialDebitRecord::$kd_platform_type)) {
                return $this->redirectMessage("您好，通知业务方成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-list'));
            } elseif (in_array($debit['type'], FinancialDebitRecord::$other_platform_type)) {
                return $this->redirectMessage("您好，通知业务方成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-other-list'));
            }
        }
        return $this->redirectMessage("通知业务方失败", self::MSG_ERROR);
    }

    /**
     * 扣款成功通知业务方
     * @name 扣款成功通知业务方
     */
    public function actionDebitSuccessNotice() {
        $id  = Yii::$app->request->post("id");
        if (empty($id)) {
            return $this->redirectMessage('抱歉，备注不能为空。', self::MSG_ERROR);
        }
        $debit = FinancialDebitRecord::findOne($id);
        if (empty($debit) || ($debit->status != FinancialDebitRecord::STATUS_SUCCESS)) {
            return $this->redirectMessage('抱歉，不存在此扣款订单或者状态不为扣款成功。', self::MSG_ERROR);
        }
        $service = new FinancialCommonService();
        $result = $service->successDebitOrder($debit, '扣款成功，手动发起回调', Yii::$app->user->identity->username);
        $callback_result = [
            'code' => $result['code'],
            'message' => $result['message']
        ];
        $debit = FinancialDebitRecord::findOne($id);
        $debit->callback_result = json_encode($callback_result);
        if ($debit->save()) {
            if (in_array($debit['type'], FinancialDebitRecord::$kd_platform_type)) {
                return $this->redirectMessage("您好，通知业务方成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-list'));
            } elseif (in_array($debit['type'], FinancialDebitRecord::$other_platform_type)) {
                return $this->redirectMessage("您好，通知业务方成功", self::MSG_SUCCESS, Url::toRoute('financial/debit-list'));
            }
        }
        return $this->redirectMessage("通知业务方失败", self::MSG_ERROR);
    }

    /**
     * @name 合作资产-合作方打款扣款管理-打款列表打款回调/actionLoanNotice
     */
    public function actionLoanNotice() {
        $id  = Yii::$app->request->get("id");
        if (empty($id)) {
            return $this->redirectMessage('抱歉，参数为空。', self::MSG_ERROR);
        }
        $withdrawInfo = FinancialLoanRecord::findOne($id);
        $loan_time = empty($withdrawInfo->success_time) ? time() : $withdrawInfo->success_time;
        try {
            //提现到账后处理
            $service = Yii::$container->get('financialCommonService');
            $back_result = $service->successLoanOrder($withdrawInfo->business_id, '打款成功，手动发起回调',  Yii::$app->user->identity->username, $withdrawInfo->type, $loan_time);
            $is_notify = ($back_result['code'] === 0) ? FinancialLoanRecord::NOTIFY_SUCCESS : FinancialLoanRecord::NOTIFY_FALSE;
        } catch (Exception $e) {
            $is_notify = FinancialLoanRecord::NOTIFY_FALSE;
        }
        $callback_result = [
            'is_notify' => $is_notify,
            'message' =>  isset($back_result['message']) ? $back_result['message'] : ''
        ];
        $withdrawInfo->callback_result = json_encode($callback_result);
        if ($withdrawInfo->save()) {
            return $this->redirectMessage("您好，通知业务方成功", self::MSG_SUCCESS, Url::toRoute('financial/loan-list'));
        }
        return $this->redirectMessage("通知业务方失败", self::MSG_ERROR);
    }

    /**
     * 修改打款/扣款银行卡信息
     * @name 修改打款/扣款银行卡信息
     */
    public function actionUpdateCardInfo($id){
        $record = FinancialLoanRecord::find()->where(['id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        if(!$record){
            return $this->redirectMessage('记录不存在',self::MSG_ERROR);
        }
        $cardInfo = CardInfo::findOne(['id'=>$record->bind_card_id]);
        if(!$cardInfo){
            return $this->redirectMessage('找不到对应银行卡信息',self::MSG_ERROR);
        }
        if ($this->getRequest()->getIsPost()) {
            $post = $this->request->post();
            if(!$post['CardInfo']){
                return $this->redirectMessage('参数错误',self::MSG_ERROR);
            }
            foreach($post['CardInfo'] as $name => $value){
                $cardInfo->$name = $value;
            }
            $cardInfo->card_no = \common\helpers\StringHelper::trimBankCard($cardInfo->card_no);
            if(!$cardInfo->card_no){
                return $this->redirectMessage('银行卡格式错误',self::MSG_ERROR);
            }

            if($cardInfo->bank_id && isset(BankConfig::$bankInfo[$cardInfo->bank_id])){
                $cardInfo->bank_name = BankConfig::$bankInfo[$cardInfo->bank_id];
            }else{
                $cardInfo->bank_id = 0;
            }
            $cardInfo->updated_at = time();
            if($cardInfo->save()){
                $record->bank_id = $cardInfo->bank_id;
                $record->bank_name = $cardInfo->bank_name;
                $record->card_no = $cardInfo->card_no;
                $record->updated_at = time();
                $record->save();
                return $this->redirectMessage("修改银行卡信息成功", self::MSG_SUCCESS);
            }
        }
        return $this->render('update-card-info', [
            'cardInfo' => $cardInfo,
            'record' => $record,
        ]);
    }

    public function actionBatchRevertDebit()
    {
        $all = FinancialDebitRecord::find()->where(["status"=> FinancialDebitRecord::STATUS_FALSE])->andWhere(['in', 'type', FinancialDebitRecord::$kd_platform_type])->all(Yii::$app->get('db_kdkj_rd'));
        $i = 0;
        $count = count($all);
        $error_list = [];
        if(!empty($all)){
            foreach($all as $model) {
                try {
                    $model->status = FinancialDebitRecord::STATUS_PAYING;
                    $model->repayment_time = 0;
                    $model->remark = "小钱包扣款失败重新发起扣款(".date("y-m-d H:i", time()).")";
                    $model->callback_result = "";
                    $model->save();
                } catch(\Exception $e){
                    $error_list[] = $model['id'];
                    $i++;
                }
            }
            if($i == 0){
                return $this->redirectMessage("成功审核{$count}个订单", self::MSG_NORMAL);
            }else{
                $success = $count - $i;
                $list = "";
                foreach($error_list as $error) {
                    $list .= $error.",";
                }
                return $this->redirectMessage("成功审核{$success}个订单 失败{$i}个,失败ID号：".$list, self::MSG_NORMAL);
            }
        }else{
            return $this->redirectMessage("无可重新发起扣款的订单", self::MSG_NORMAL);
        }
    }
    public function getAlipayFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['alipay_name'])) {
                $condition .= " AND alipay_name = '".trim($search['alipay_name'])."'";
            }
            if (!empty($search['id'])) {
                $condition .= " AND id = ".intval($search['id']);
            }
            if (!empty($search['alipay_order_id'])) {
                $condition .= " AND alipay_order_id = '".trim($search['alipay_order_id'])."'";
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND status = " . intval($search['status']);
            }
            if (isset($search['source']) && $search['source'] !== '') {
                $condition .= " AND source = " . intval($search['source']);
            }
            if (isset($search['type']) && $search['type'] !== '') {
                $condition .= " AND type = " . intval($search['type']);
            }
            if (!empty($search['alipay_account'])) {
                $condition .= " AND alipay_account = '".trim($search['alipay_account'])."'";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND created_at < " . strtotime($search['endtime']);
            }
            if (!empty($search['pay_at_begin'])) {
                $condition .= " AND alipay_date >= '".trim($search['pay_at_begin'])."'";
            }
            if (!empty($search['pay_at_end'])) {
                $condition .= " AND alipay_date <  '".trim($search['pay_at_end'])."'";
            }
            if (!empty($search['updated_at_begin'])) {
                $condition .= " AND updated_at >= ".strtotime($search['updated_at_begin']);
            }
            if (!empty($search['updated_at_end'])) {
                $condition .= " AND updated_at <  ".strtotime($search['updated_at_end']);
            }
            if(!empty($search['operator_user'])){
                $condition .= " AND operator_user = '".trim($search['operator_user'])."'";
            }
        }
        return $condition;
    }
    public function getWeixinFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['weixin_name'])) {
                $condition .= " AND weixin_name = '".trim($search['weixin_name'])."'";
            }
            if (!empty($search['id'])) {
                $condition .= " AND id = ".intval($search['id']);
            }
            if (!empty($search['weixin_order_id'])) {
                $condition .= " AND weixin_order_id = '".trim($search['weixin_order_id'])."'";
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND status = " . intval($search['status']);
            }
            if (isset($search['source']) && $search['source'] !== '') {
                $condition .= " AND source = " . intval($search['source']);
            }
            if (isset($search['type']) && $search['type'] !== '') {
                $condition .= " AND type = " . intval($search['type']);
            }
            if (!empty($search['weixin_account'])) {
                $condition .= " AND weixin_account = '".trim($search['weixin_account'])."'";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND created_at < " . strtotime($search['endtime']);
            }
            if (!empty($search['pay_at_begin'])) {
                $condition .= " AND weixin_date >= '".trim($search['pay_at_begin'])."'";
            }
            if (!empty($search['pay_at_end'])) {
                $condition .= " AND weixin_date <  '".trim($search['pay_at_end'])."'";
            }
            if (!empty($search['updated_at_begin'])) {
                $condition .= " AND updated_at >= ".strtotime($search['updated_at_begin']);
            }
            if (!empty($search['updated_at_end'])) {
                $condition .= " AND updated_at <  ".strtotime($search['updated_at_end']);
            }
            if(!empty($search['operator_user'])){
                $condition .= " AND operator_user = '".trim($search['operator_user'])."'";
            }
        }
        return $condition;
    }

    /**
     * @return string
     * @name 财务管理-小钱包打款扣款管理-支付宝还款列表/actionAlipayList
     */
    public function actionAlipayList(){
        $condition = $this->getAlipayFilter();
        $query = AlipayRepaymentLog::find()->where($condition);
        if($this->request->get('submitcsv') == 'exportcsv'){
            $query->orderBy(['id' => SORT_DESC]);
            return $this->_exportAlipayList($query);
        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->orderBy(['id' => SORT_DESC])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        $dataSt = $countQuery->select('sum(money) as money,sum(CASE WHEN status ='.AlipayRepaymentLog::STATUS_FINISH.' THEN money END) as success_money,sum(CASE WHEN status ='.AlipayRepaymentLog::STATUS_BACK.'  THEN money END) as reject_money')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        return $this->render('alipay-list', [
            'info' => $info,
            'dataSt' => $dataSt,
            'pages' => $pages,
        ]);
    }


    /**
     * @name 支付宝列表/管理员添加备注
     * @return array
     */
    public function actionSetAlipayAdminRemark()
    {
        $this->response->format = 'json';
        try {
            if (!$this->getRequest()->getIsPost()) throw new Exception("请求方式错误!");
            $params = $this->request->post();
            if (!isset($params['id']) || $params['id']=='') throw new Exception("参数错误!");
            $alipayRepaymentLog = AlipayRepaymentLog::findOne($params['id']);
            if (!$alipayRepaymentLog) throw new Exception("未找到相关数据!");
            $alipayRepaymentLog->remark_admin = $params['remark_admin'];
            if (!$alipayRepaymentLog->save()) throw new Exception("添加备注失败!");
            return ['code'=> 0,'message'=>'备注添加成功'];
        } catch(Exception $ex) {
            return ['code' => 1,'msg' => $ex->getMessage()];
        }
    }

    /**
     * @name 微信列表/管理员添加备注
     * @return array
     */
    public function actionSetWeixinAdminRemark()
    {
        $this->response->format = 'json';
        try {
            if (!$this->getRequest()->getIsPost()) throw new Exception("请求方式错误!");
            $params = $this->request->post();
            if (!isset($params['id']) || $params['id']=='') throw new Exception("参数错误!");
            $weixinRepaymentLog = WeixinRepaymentLog::findOne($params['id']);
            if (!$weixinRepaymentLog) throw new Exception("未找到相关数据!");
            $weixinRepaymentLog->remark_admin = $params['remark_admin'];
            if (!$weixinRepaymentLog->save()) throw new Exception("添加备注失败!");
            return ['code'=> 0,'message'=>'备注添加成功'];
        } catch(Exception $ex) {
            return ['code' => 1,'msg' => $ex->getMessage()];
        }
    }

    private function _exportAlipayList($query){
        $this->_setcsvHeader('支付宝还款列表导出.csv');
        $datas = $query->all(Yii::$app->get('db_kdkj_rd'));
        $items = [];
        foreach($datas as $value){
            if(Util::verifyPhone($value['alipay_account'])){
                $phone = $value['alipay_account'];
            }elseif(preg_match('/([\d]{11})/', $value['remark'],$match)){

                $phone = $match[1];
            }else
            {
                $phone='无手机号';
            }

            $items[] = [
                '姓名' => $value['alipay_name'],
                '支付宝订单号' => "\t{$value['alipay_order_id']}",
                '支付宝账号' => "\t{$value['alipay_account']}",
                '支付金额' => $value['money']/100,
                '实际金额' => ($value['money']-$value['overflow_payment'])/100,
                '溢缴款' => $value['overflow_payment']/100,
                '手机号码'=> "\t{$phone}",
                '还款时间' => "\t{$value['alipay_date']}",
                '备注' =>$value['remark'],

            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @param $id
     * @param int $type
     * @return string
     * @name 财务管理-小钱包打款扣款管理-支付宝还款列表-置为已处理/actionFinishAlipayLog
     */
    public function actionFinishAlipayLog($id,$type=0){
        if($type){
            $status = AlipayRepaymentLog::STATUS_BACK;
        }else{
            $status = AlipayRepaymentLog::STATUS_FINISH;
        }
        $operatorUser = Yii::$app->user->identity->username;
        AlipayRepaymentLog::updateAll(['status'=>$status,'updated_at'=>time(),'operator_user'=>$operatorUser],'id='.intval($id).' and status<>'.AlipayRepaymentLog::STATUS_FINISH);
        return $this->redirectMessage("操作成功", self::MSG_NORMAL);
    }

    /**
     * @param $id
     * @param int $type
     * @return string
     * @name 财务管理-小钱包打款扣款管理-支付宝还款列表-置为需人工处理/actionManualAlipayLog
     */
    public function actionManualAlipayLog($id){
        $status = AlipayRepaymentLog::STATUS_FAILED;
        $operatorUser = Yii::$app->user->identity->username;
        AlipayRepaymentLog::updateAll(['status'=>$status,'updated_at'=>time(),'operator_user'=>$operatorUser],'id='.intval($id));
        return $this->redirectMessage("操作成功", self::MSG_NORMAL);
    }


    public function getBankpayFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND l.id = ".intval($search['id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.order_id = ".intval($search['order_id']);
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = ".intval($search['user_id']);
            }
            if (!empty($search['user_name'])) {
                $user_info = LoanPerson::find()->select(['id'])->where(['phone' => $search['user_name']])->asArray()->all();
                if(!empty($user_info)){
                    $user_list = [];
                    foreach($user_info as $v){
                        $user_list[] = $v['id'];
                    }
                }else{
                    $user_list = [0];
                }
                $user_list = implode(',',$user_list);
                $condition .= " AND l.user_id in ({$user_list})";

            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND l.order_uuid = '".trim($search['order_uuid'])."'";
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND l.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] !== '') {
                $condition .= " AND l.type = " . intval($search['type']);
            }
            if (isset($search['debit_channel']) && $search['debit_channel'] !== '') {
                $condition .= " AND l.debit_channel = " . intval($search['debit_channel']);
            }
            if (!empty($search['payment_type'])) {
                $condition .= " AND l.payment_type = " . intval($search['payment_type']);
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND l.pay_order_id = '".trim($search['pay_order_id'])."'";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at <= " . strtotime($search['endtime']);
            }
            //若更新时间为空，则取创建时间以及还款状态成功的单据；
            if(!empty($search['success_begin_time'])||!empty($search['success_end_time'])){
                //$port_condition="";
                if (!empty($search['success_begin_time'])) {
                    $condition .= " AND  l.success_repayment_time >= " . strtotime($search['success_begin_time']);
                    //$condition .= " AND ((updated_at >= " . strtotime($search['success_begin_time']);
                    //$port_condition .= " or(created_at>=". strtotime($search['success_begin_time']);
                }
                if (!empty($search['success_end_time'])) {
                    $condition .= " AND l.success_repayment_time <= " . strtotime($search['success_end_time']);
                    //$condition .= " AND updated_at < " . strtotime($search['success_end_time']).")";
                    //$port_condition .= " and created_at<=". strtotime($search['success_end_time'])."))";
                }
                //$condition .= $port_condition;
                $condition .=" AND l.status=".UserCreditMoneyLog::STATUS_SUCCESS;
            }

            if(isset($search['fund_id'])&& !empty($search['fund_id']) && $search['fund_id'] > 0 ){
                $condition  .= " AND userLoanOrder.fund_id = ".(int)$search['fund_id'];
            }
        }
        return $condition;
    }

    /**
     * @name 财务管理-还款日志列表/actionBankpayList
     */
    public function actionBankpayList(){
        $read_db = \Yii::$app->db_kdkj_rd_new;

        if($this->request->get('submitcsv') == 'exportcsv'){
//            return $this->_exportBankPayInfos();
            return $this->_exportBankPayInfosBak();
        }
        $condition = $this->getBankpayFilter();

        $query = UserCreditMoneyLog::find()->from(UserCreditMoneyLog::tableName().'as l')
            ->joinWith(['userLoanOrder'=> function($userLoanOrder) {
                $userLoanOrder->select(['id','fund_id']);
            },'userLoanOrderRepayment'=>function($userLoanOrderRepayment){
                $userLoanOrderRepayment->select(['order_id','late_fee','interests','principal',]);
            }])
            ->where($condition)->orderBy(['l.id' => SORT_DESC]);

        $countQuery = clone $query;


        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $read_db);
            $dataSt = $countQuery->select('sum(operator_money) as operator_money')->one($read_db);
        } else {
            $count = 9999999;
            $dataSt = 999999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);

        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id']);
            },
        ])->offset($pages->offset)->limit($pages->limit)->all($read_db);


        return $this->render('bankpay-list', [
            'info' => $info,
            'pages' => $pages,
            'dataSt' => $dataSt,
        ]);
    }

    /**
     * @name 财务管理-还款日志编辑/actionBankpayEdit
     */
    public function actionBankpayEdit(){
        $id = $this->request->get('id');
        $info = UserCreditMoneyLog::findOne((int)$id);
        if (empty($info)) {
            return $this->redirectMessage('信息不存在',self::MSG_ERROR);
        }
        if ($this->request->getIsPost()) {
            $post = $this->request->post();
            try{
                if(!empty($post['order_uuid'])){
                    $info->order_uuid = $post['order_uuid'];
                }
                $info->pay_order_id = $post['pay_order_id'];
                $info->operator_money = bcmul(floatval(sprintf("%0.2f",$post['operator_money'])),100);
                $info->remark = $post['remark'];
                $info->debit_channel = $post['debit_channel'];
                $info->payment_type = $post['payment_type'];
                $info->status = $post['status'];
                $info->success_repayment_time = $post['success_repayment_time'] ? strtotime($post['success_repayment_time']) : 0;
                $info->operator_name = Yii::$app->user->identity->username;
                $info->save();
            }catch(\Exception $e){
                return $this->redirectMessage($e->getMessage(),self::MSG_ERROR);
            }
            return $this->redirectMessage('修改成功',self::MSG_SUCCESS,-2);
        }
        return $this->render('edit-bankpay-detail', [
            'info' => $info,
        ]);
    }

    /**
     * @name 财务管理-小钱包扣款管理-自动扣款日志列表/actionDeductMoneyLog
     */
    public function actionDeductMoneyLog1()
    {
        $read_db = \Yii::$app->db_kdkj_rd_new;


        $condition = $this->getDecuctMoneyFilter();
        $query = AutoDebitLog::find()->from(AutoDebitLog::tableName() . ' as a')
            ->select([
                'a.id','a.user_id',
                'a.order_id','order_uuid','pay_order_id','a.card_id',
                'platform','bank_id','bank_name', 'money',
                'a.status','debit_type', 'a.created_at','callback_at','overdue_day','total_money','true_total_money','a.remark','callback_remark'])
            ->joinWith(['cardInfo','userLoanOrderRepayment'])
            ->where($condition)->orderBy(['a.id' => SORT_DESC]);

        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportAutoDebitLog($query);
        }

        $countQuery = clone $query;

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $read_db);
        } else {
            $count = 9999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($read_db);
        return $this->render('deduct-money-log',
            [
                'info' => $info,
                'pages' => $pages,
            ]);
    }

    /**
     * @name 财务管理-小钱包扣款管理-自动扣款日志列表/actionDeductMoneyLog
     */
    public function actionDeductMoneyLog()
    {
//        $order_type = $this->request->get('order_type') ?? '1';
//        return $this->_deductMoneyLogLong($order_type);
        $read_db = \Yii::$app->db_kdkj_rd_new;


        $condition = $this->getDecuctMoneyFilter();
        $query = AutoDebitLog::find()->from(AutoDebitLog::tableName() . ' as a')
            ->select([
                'a.id','a.user_id',
                'a.order_id','order_uuid','pay_order_id','a.card_id',
                'platform','bank_id','bank_name', 'money',
                'a.status','debit_type', 'a.created_at','callback_at','overdue_day','total_money','true_total_money','a.remark','callback_remark'])
            ->joinWith(['cardInfo','userLoanOrderRepayment'])
            ->where($condition)->orderBy(['a.id' => SORT_DESC]);

        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportAutoDebitLog($query);
        }

        $countQuery = clone $query;

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $read_db);
        } else {
            $count = 9999999;
            $db = Yii::$app->get('db_kdkj_rd_new');
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($read_db);
        return $this->render('deduct-money-log',
            [
                'info' => $info,
                'pages' => $pages,
            ]);
    }

    private function _deductMoneyLogLong($order_type='1')
    {
        $read_db = \Yii::$app->db_kdkj_rd_new;

        $condition=array();
        $query = AutoDebitLog::find()->from(AutoDebitLog::tableName() . ' as a')
            ->select([
                'a.id','a.user_id',
                'a.order_id','order_uuid','pay_order_id','a.card_id',
                'platform','bank_id','bank_name', 'money',
                'a.status','debit_type', 'a.created_at','callback_at','overdue_day','total_money','true_total_money','callback_remark'])
            ->joinWith(['cardInfo','userLoanOrderRepayment'])
            ->where($condition)->orderBy(['a.id' => SORT_DESC]);

        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportAutoDebitLog($query);
        }else if($this->request->get('submitcsv') == 'exportcsv2'){
            return $this->_exportAutoDebitLog2();
        }

        $countQuery = clone $query;

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $read_db);
        } else {
            $count = 9999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($read_db);
        if($order_type == 4){
            foreach ($info as $k => $v) {
                $period = UserLoanOrderRepaymentPeriod::find()
                    ->where('order_id='.$v->order_id.' and true_repayment_money < plan_repayment_money')
                    ->select(['overdue_day'])
                    ->orderBy(['overdue_day' => SORT_DESC])->one();
                $info[$k]->userLoanOrderRepayment->overdue_day = $period->overdue_day?? 0;
            }
        }
        return $this->render('deduct-money-log',
            [
                'info' => $info,
                'pages' => $pages,
                'order_type' => $order_type
            ]);
    }


    /*
	* 自动扣款日志导出
	*/
    private function _exportAutoDebitLog($query){
        $this->_setcsvHeader('自动扣款日志列表.csv');
        $max_id = 0;
        $limit = 1000;
        $datas = $query->andWhere(['>','a.id',$max_id])->limit($limit)->orderBy(['a.id'=>SORT_ASC])->all(Yii::$app->get('db_kdkj_rd_new'));
        $items = [];
        while ($datas)
        {
            foreach($datas as $value){
                $items[] = [
                    'ID' => $value->id,
                    '应还总额' => \common\helpers\StringHelper::safeConvertIntToCent($value->userLoanOrderRepayment->total_money),
                    '已还金额	' => \common\helpers\StringHelper::safeConvertIntToCent($value->userLoanOrderRepayment->true_total_money),
                    '扣款金额' => sprintf("%0.2f", ($value->money) / 100),
                    '逾期天数' => $value->userLoanOrderRepayment->overdue_day,
                    '扣款状态' =>  \common\models\AutoDebitLog::$status_list[$value->status],
                    '创建时间' => date('Y-m-d H:i:s',$value->created_at),
                    '回调时间' => empty($value->callback_at) ? '':date('Y-m-d H:i:s',$value->callback_at),
                ];
                $max_id = $value['id'];
            }
            unset($datas);
            $datas = $query->andWhere(['>','a.id',$max_id])->limit($limit)->orderBy(['a.id'=>SORT_ASC])->all(Yii::$app->get('db_kdkj_rd_new'));
        }

        echo $this->_array2csv($items);
        exit;

    }

    /**
     * @name 财务管理-扣款管理-合利宝参数统计/actionHeliabaoStatistic
     */
    public function actionHeliabaoStatistic(){
//        $rate = 0.3;
        $info = [];
        $today = date('Y-m-d');
        $debit = AutoDebitLog::find()
            ->select(['id','user_id','pay_order_id','status','debit_type'])
            ->where(['between','created_at',strtotime($today.'00:00:00'),strtotime($today.'23:59:59')])
            ->andWhere(['Not',['pay_order_id'=>'']])
            ->andWhere(['Not',['pay_order_id'=>null]])
            ->orderBy('id desc')->asArray()->all();
        $info['sum'] = $info['sum_succ'] = 0;
        $info['sys'] = $info['sys_succ'] = 0;
        $info['collection'] = $info['collection_succ'] = 0;
        $info['backend'] = $info['backend_succ'] = 0;
        $info['little'] = $info['little_succ'] = 0;
        $info['active'] = $info['active_succ'] = 0;
        foreach ($debit as $value){
            //1.系统代扣
            if($value['debit_type'] == AutoDebitLog::DEBIT_TYPE_SYS){
                $info['sys'] = $info['sys'] + 1;
                if ($value['status'] == AutoDebitLog::STATUS_SUCCESS){
                    $info['sys_succ'] = $info['sys_succ'] + 1;
                }
            }
            //2.催收扣款
            if($value['debit_type'] == AutoDebitLog::DEBIT_TYPE_COLLECTION){
                $info['collection'] = $info['collection'] + 1;
                if ($value['status'] == AutoDebitLog::STATUS_SUCCESS){
                    $info['collection_succ'] = $info['collection_succ'] + 1;
                }
            }
            //3.后台代扣
            if($value['debit_type'] == AutoDebitLog::DEBIT_TYPE_BACKEND){
                $info['backend'] = $info['backend'] + 1;
                if ($value['status'] == AutoDebitLog::STATUS_SUCCESS){
                    $info['backend_succ'] = $info['backend_succ'] + 1;
                }
            }
            //4.小额代扣
            if($value['debit_type'] == AutoDebitLog::DEBIT_TYPE_LITTLE){
                $info['little'] = $info['little'] + 1;
                if ($value['status'] == AutoDebitLog::STATUS_SUCCESS){
                    $info['little_succ'] = $info['little_succ'] + 1;
                }
            }
            //5.主动还款
            if($value['debit_type'] == AutoDebitLog::DEBIT_TYPE_ACTIVE){
                $info['active'] = $info['active'] + 1;
                if ($value['status'] == AutoDebitLog::STATUS_SUCCESS){
                    $info['active_succ'] = $info['active_succ'] + 1;
                }
            }
        }
        $info['sum'] = count($debit);
        $info['sum_succ'] = $info['sys_succ'] + $info['collection_succ'] + $info['backend_succ'] + $info['little_succ'] + $info['active_succ'];
//        $can_debit = ($info['sum'] * $rate) - ($info['sum'] - $info['sum_succ']);
        return $this->render('heliabao-statistic',
            [
                'info' => $info,
//                'can_debit' => $can_debit,
            ]);
    }
    /**
     * @name 财务管理-小钱包扣款管理-待观察扣款数据以/actionSuspectDebitLost
     */
    public function actionSuspectDebitLost()
    {

        $condition = $this->getSuspectDebitLoseFilter();
        $query = SuspectDebitLostRecord::find()->from(SuspectDebitLostRecord::tableName() . ' as a')
            ->select([
                'a.id','a.user_id',
                'order_id','order_uuid','pay_order_id','card_id',
                'platform','bank_id','bank_name', 'money',
                'a.status','debit_type','mark_type', 'a.created_at','a.updated_at','a.operator','a.remark'])
            ->joinWith(['cardInfo'])
            ->where($condition)->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd_new');
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportSuspectDebitLost($query,$db);
        }
        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = 9999999;
//            $count = $db->cache(function ($db) use ($countQuery) {
//                return $countQuery->count('*', $db);
//            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($db);
        return $this->render('suspect-debit-lost-record',
            [
                'info' => $info,
                'pages' => $pages,
            ]);
    }

    /**
     * @name 财务管理-小钱包扣款管理-待观察扣款数据导出
     */
    private function _exportSuspectDebitLost($query, $db){
        $this->_setcsvHeader('待观察扣款数据'.time().'.csv');
        $datas = $query->all($db);
        $items = [];
        foreach($datas as $value){
            $items[] = [
                'ID' => $value['id'],
                '用户ID' => $value['user_id'],
                '订单ID' => $value['order_id'],
                '银行流水号' => $value['order_uuid'],
                '第三方支付号' => $value['pay_order_id'],
                '通道' => isset(BankConfig::$platform[$value['platform']])?BankConfig::$platform[$value['platform']]:'代扣',
                '扣款金额'=> sprintf("%0.2f", ($value['money']) / 100),
                '状态' => SuspectDebitLostRecord::$STATUS_ARR[$value['status']],
                '标记类型' =>SuspectDebitLostRecord::$MARK_TYPE_ARR[$value['mark_type']],
                '业务类型' =>SuspectDebitLostRecord::$DEBIT_TYPE_ARR[$value['debit_type']],
                '备注' =>$value['remark'],
                '操作人' =>$value['operator'],
                '更新时间' =>date('Y-m-d H:i:s',$value['updated_at']),
                '创建时间' =>date('Y-m-d H:i:s',$value['created_at']),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @name 财务管理-小钱包扣款管理-手动将未回调订单置为失败/actionSuspectDebitLostRecord
     */
    public function actionForceDebitRecordFailed(){
        try{
            if (!$this->request->getIsPost()) throw new Exception("操作错误:提交方式有误",self::MSG_ERROR);
            $params = $this->request->post();
            if (!isset($params['id'])) throw new Exception("操作错误:id参数有误",self::MSG_ERROR);
            $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['id'=>$params['id']]);
            try {
                $autoDebitService = Yii::$container->get('autoDebitService');
                $autoDebitService -> handleDebitingOrder($suspectDebitLostRecord->order_uuid,['type' => AutoDebitService::TYPE_STAFF ,'remark' => $params['remark'],'isForceFailed' => true]);
                return $this->redirectMessage('操作成功');
            } catch (Exception $eex){
                throw new Exception($eex->getMessage(),self::MSG_ERROR);
            }
        } catch (Exception $ex){
            return $this->redirectMessage($ex->getMessage(), $ex->getCode());
        }
    }

    public function getDecuctMoneyFilter(){
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND a.id = ".intval($search['id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND a.order_id = ".intval($search['order_id']);
            }
            if (!empty($search['phone'])) {
                $phone = $search['phone'];
                $result = LoanPerson::find()->select(['id'])->where(['phone' => $phone])->all(Yii::$app->get('db_kdkj_rd_new'));
                if($result){
                    $user_ids = [];
                    foreach($result as $v){
                        $user_ids[] = $v['id'];
                    }
                    $user_ids = '('.implode(',',$user_ids) .')';
                    $condition .= " AND a.user_id in " . $user_ids;
                }else{
                    $condition .= " AND a.user_id = 0";
                }
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND a.user_id = ".intval($search['user_id']);
            }
            if (isset($search['overdue_day']) && $search['overdue_day'] !== '') {
                $condition .= " AND overdue_day = ".intval($search['overdue_day']);
            }
            if (isset($search['status']) && $search['status'] !== '' && in_array($search['status'],array_keys(AutoDebitLog::$status_list))) {
                $condition .= " AND a.status = ".intval($search['status']);
            }
            if (in_array($search['type'],array_keys(AutoDebitLog::$type_list))) {
                $condition .= " AND a.debit_type = ".intval($search['type']);
            }
            if (in_array($search['bank_id'],array_keys(CardInfo::$bankInfo))) {
                $condition .= " AND bank_id = ".intval($search['bank_id']);
            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND order_uuid = '".trim($search['order_uuid'])."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND pay_order_id = '".trim($search['pay_order_id'])."'";
            }
            if (isset($search['platforms']) && $search['platforms'] !== '') {
                $condition .= " AND platform = " . intval($search['platforms']);
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " . strtotime($search['endtime']);
            }
            if (!empty($search['callback_begin_time'])) {
                $condition .= " AND  a.callback_at >= " . strtotime($search['callback_begin_time']);
            }
            if (!empty($search['callback_end_time'])) {
                $condition .= " AND callback_at <= " . strtotime($search['callback_end_time']);
            }

        }
        return $condition;
    }

    private function getSuspectDebitLoseFilter()
    {
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND a.id = ".intval($search['id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND order_id = ".intval($search['order_id']);
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND a.user_id = ".intval($search['user_id']);
            }
            if (isset($search['status']) && $search['status'] !== '' && in_array($search['status'],array_keys(SuspectDebitLostRecord::$STATUS_ARR))) {
                $condition .= " AND a.status = ".intval($search['status']);
            }
            if (isset($search['mark_type']) && $search['mark_type'] !== '' && in_array($search['mark_type'],array_keys(SuspectDebitLostRecord::$MARK_TYPE_ARR))) {
                $condition .= " AND a.mark_type = ".intval($search['mark_type']);
            }
            if (isset($search['debit_type']) && $search['debit_type'] !== '' && in_array($search['debit_type'],array_keys(SuspectDebitLostRecord::$DEBIT_TYPE_ARR))) {
                $condition .= " AND a.debit_type = ".intval($search['debit_type']);
            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND order_uuid = '".trim($search['order_uuid'])."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND pay_order_id = '" . trim($search['pay_order_id'])."'";
            }
            if (isset($search['platforms']) && $search['platforms'] !== '') {
                $condition .= " AND platform = " . intval($search['platforms']);
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND a.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND a.created_at <= " . strtotime($search['endtime']);
            }
            if (!empty($search['update_begintime'])) {
                $condition .= " AND a.updated_at >= " . strtotime($search['update_begintime']);
            }
            if (!empty($search['update_endtime'])) {
                $condition .= " AND a.updated_at <= " . strtotime($search['update_endtime']);
            }
        }
        return $condition;
    }
    /**
     * @author chengyunbo
     * @date 2016-11-09
     * @name 财务管理-统计管理列表-收入统计表-actionSubsidiaryLedgerList
     **/
    public function actionSubsidiaryLedgerList($type='day'){
        $condition='1=1';
        $search = $this->request->get();
        if ((isset($search["fund_id"])&&!empty($search["fund_id"]))) {
            $fund_id=$search["fund_id"];
        }else{
            $fund_id=0;
        }
        if($type=='day')
            $condition .= " and type=0 ";
        else
            $condition .= " and type=1 ";

        if (!empty($search['begin_created_at'])) {
            $begin_created_at = str_replace(' ','',$search['begin_created_at']);
            if($type=='day')
                $condition .= " AND  date >= '{$begin_created_at}'";
            else
                $condition .= " AND  date >= '{$begin_created_at}-01'";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = str_replace(' ','',$search['end_created_at']);
            if($type=='day')
                $condition .= " AND  date <= '{$end_created_at}'";
            else
                $condition .= " AND  date <= '{$end_created_at}-01'";
        }
        $condition .=" and fund_id ={$fund_id}";
        $query = FinancialSubsidiaryLedger::find()->where($condition)->orderBy(['date' => SORT_DESC]);
//        echo $query->createCommand()->getRawSql();die;
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $sub_query = clone $query;
//        echo $sub_query->createCommand()->getRawSql();
        $sub = $sub_query->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        //获取各分类合计金额
        $sub_info= ['loan_num'=>0,'loan_money'=>0,'true_loan_money'=>0,
            'counter_fee'=>0,'rollover_money'=>0,'true_total_principal'=>0,'true_total_money'=>0,
            'true_rollover_handlefee' =>0,'true_rollover_counterfee'=>0,'true_rollover_apr'=>0,'coupon_money'=>0
        ];
        foreach($sub as $val){
            $sub_info['loan_num']+=$val['loan_num'];
            $sub_info['loan_money']+=$val['loan_money'];
            $sub_info['true_loan_money']+=$val['true_loan_money'];
            $sub_info['counter_fee']+=$val['counter_fee'];
            $sub_info['rollover_money']+=$val['rollover_money'];
            $sub_info['true_total_principal']+=$val['true_total_principal'];
            $sub_info['true_total_money']+=$val['true_total_money'];
            $sub_info['true_rollover_handlefee']+=$val['true_rollover_handlefee'];
            $sub_info['true_rollover_counterfee']+=$val['true_rollover_counterfee'];
            $sub_info['true_rollover_apr']+=$val['true_rollover_apr'];
            $sub_info['coupon_money']+=$val['coupon_money'];
        }
        $data = $query->offset($pages->offset)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $update_time = !empty($info[0]['updated_at'])?date('Y-m-d H:i:s',$info[0]['updated_at']):date('Y-m-d H:i:s',$info[0]['created_at']);
        //导出数据
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportSubsidiaryData($data,$type);
        }

        return $this->render('subsidiary-ledger-list',
            [
                'info'=>$info,
                'sub_info'=>$sub_info,
                'pages' => $pages,
                'update_time' => $update_time,
                'type' => $type
            ]
        );
    }

    private function _subsidiaryLedgerListM($type='month'){

        $condition='1=1';
        $search = $this->request->get();
        if ((isset($search["fund_id"])&&!empty($search["fund_id"]))) {
            $fund_id=$search["fund_id"];
        }else{
            $fund_id=0;
        }

        $condition .= " and type=0 ";

        if (!empty($search['begin_created_at'])) {
            $begin_created_at = str_replace(' ','',$search['begin_created_at']);
            $condition .= " AND  date >= '{$begin_created_at}-01'";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = str_replace(' ','',$search['end_created_at']);
            $condition .= " AND  date <= '{$end_created_at}-31'";

        }
        $condition .=" and fund_id ={$fund_id}";
//        var_dump($condition);
        $Filed = " extract(year_month from `date`) AS date,type,sum(loan_num) as loan_num,sum(loan_money) as loan_money,sum(true_loan_money) as true_loan_money,sum(counter_fee) as counter_fee,sum(rollover_money) as rollover_money,sum(true_total_principal) as true_total_principal,sum(true_total_money) as true_total_money,sum(late_fee) as late_fee,sum(true_rollover_handlefee) as true_rollover_handlefee,sum(true_rollover_counterfee) as true_rollover_counterfee,sum(true_rollover_apr) as true_rollover_apr,sum(coupon_money) as coupon_money,MAX(`created_at`) AS created_at,MAX(`updated_at`) as updated_at,source,fund_id";
        $query = FinancialSubsidiaryLedger::find()->select($Filed)->where($condition)->groupBy("extract(year_month from `date`) ")->orderBy(['date' => SORT_DESC]);
//        echo $query->createCommand()->getRawSql();die;
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $sub_query = clone $query;
//        echo $sub_query->createCommand()->getRawSql();
        $sub = $sub_query->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        //获取各分类合计金额
        $sub_info= ['loan_num'=>0,'loan_money'=>0,'true_loan_money'=>0,
            'counter_fee'=>0,'rollover_money'=>0,'true_total_principal'=>0,'true_total_money'=>0,
            'true_rollover_handlefee' =>0,'true_rollover_counterfee'=>0,'true_rollover_apr'=>0,'coupon_money'=>0
        ];
        foreach($sub as $val){
            $sub_info['loan_num']+=$val['loan_num'];
            $sub_info['loan_money']+=$val['loan_money'];
            $sub_info['true_loan_money']+=$val['true_loan_money'];
            $sub_info['counter_fee']+=$val['counter_fee'];
            $sub_info['rollover_money']+=$val['rollover_money'];
            $sub_info['true_total_principal']+=$val['true_total_principal'];
            $sub_info['true_total_money']+=$val['true_total_money'];
            $sub_info['true_rollover_handlefee']+=$val['true_rollover_handlefee'];
            $sub_info['true_rollover_counterfee']+=$val['true_rollover_counterfee'];
            $sub_info['true_rollover_apr']+=$val['true_rollover_apr'];
            $sub_info['coupon_money']+=$val['coupon_money'];
        }
        $data = $query->offset($pages->offset)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
//        var_dump($info);exit();
        $update_time = !empty($info[0]['updated_at'])?date('Y-m-d H:i:s',$info[0]['updated_at']):date('Y-m-d H:i:s',$info[0]['created_at']);
        //导出数据
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportSubsidiaryData($data,$type);
        }

        return $this->render('subsidiary-ledger-list',
            [
                'info'=>$info,
                'sub_info'=>$sub_info,
                'pages' => $pages,
                'update_time' => $update_time,
                'type' => $type
            ]
        );
    }
    private function _exportSubsidiaryData($data,$type){
        $this->_setcsvHeader('收付款统计.csv');
        foreach($data as $value){
            if (strlen($value['date']) <= 7 ) $value['date'] = $value['date'].'01';
            $items[] = [
                '日期'=> $type=='day'?date("Y-m-d",strtotime($value['date'])):date("Y-m",strtotime($value['date'])),
                '借款单数' =>empty($value['loan_num'])?'--':$value['loan_num'],
                '借款申请金额' =>empty($value['loan_money'])?'--':number_format($value['loan_money']/100,2),
                '银行打款金额' =>empty($value['true_loan_money'])?'--':number_format($value['true_loan_money']/100,2),
                '综合费用' =>empty($value['counter_fee'])?'--':number_format($value['counter_fee']/100,2),
                '续期金额' =>empty($value['rollover_money'])?'--':number_format($value['rollover_money']/100,2),
                '实收还款总额' =>empty($value['true_total_principal'])?'--':number_format($value['true_total_principal']/100,2),
                '实收还款本金' =>empty($value['true_total_money'])?'--':number_format($value['true_total_money']/100,2),
                '实收滞纳金' =>empty($value['late_fee'])?'--':number_format($value['late_fee']/100,2),
                '实收续期服务费' =>empty($value['true_rollover_counterfee'])?'--':number_format($value['true_rollover_counterfee']/100,2),
                '实收续期利息' =>empty($value['true_rollover_apr'])?'--':number_format($value['true_rollover_apr']/100,2),
                '优惠券减免金额' =>empty($value['coupon_money'])?'--':number_format($value['coupon_money']/100,2),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @name 财务管理-统计管理列表-收付款统计表（日表）\actionSubsidiaryLedgerDayList
     **/
    public function actionSubsidiaryLedgerDayList(){
        return $this->actionSubsidiaryLedgerList('day');
    }
    /**
     * @name 财务管理-统计管理列表-收付款统计表（月表）\actionSubsidiaryLedgerMonthList
     **/
    public function actionSubsidiaryLedgerMonthList(){
        return $this->_subsidiaryLedgerListM('month');
    }

    /**
     * @name 贷款余额统计/actionLoanBalanceList
     */
    public function actionLoanBalanceList(){
        $condition='1=1 and type=0 ';
        $search = $this->request->get();
        if (!empty($search['begin_created_at'])) {
            $begin_created_at = str_replace(' ','',$search['begin_created_at']);
            $condition .= " AND  date >= '{$begin_created_at}'";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = str_replace(' ','',$search['end_created_at']);
            $condition .= " AND  date <= '{$end_created_at}'";
        }
        //资方id
        $fund_id = $search['fund_id'] ?? 0;
        $condition .= " AND fund_id = {$fund_id}";
        $query = RepayRatesList::find()->where($condition)->orderBy(['date' => SORT_DESC]);
        //echo $query->createCommand()->getRawSql();
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $sub_query = clone $query;
//        echo $sub_query->createCommand()->getRawSql();
        $sub = $sub_query->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $info = $query->offset($pages->offset)->asArray()->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        $update_time = !empty($info[0]['updated_at'])?date('Y-m-d H:i:s',$info[0]['updated_at']):date('Y-m-d H:i:s',$info[0]['created_at']);
        return $this->render('loan-balance-list',
            [
                'info'=>$info,
                'pages'=>$pages,
                'update_time' => $update_time,
            ]
        );

    }

    /**
     * @name 财务管理-统计管理列表-逾期数据分布/actionOverdueList
     */
    public function actionOverdueList(){

        $condition=' type = 0 ';
        $search = $this->request->get();
        if (!empty($search['begin_created_at'])) {
            $begin_created_at = str_replace(' ','',$search['begin_created_at']);
            $condition .= " AND  date >= '{$begin_created_at}-01'";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = str_replace(' ','',$search['end_created_at']);
            $condition .= " AND  date <= '{$end_created_at}-01'";
        }
        //资方id
        $fund_id = $search['fund_id'] ?? 3;
        $condition .= " AND fund_id = {$fund_id}";
        $sql="select a.* from tb_repay_rates_list a join (
SELECT max(id) as myid FROM `tb_repay_rates_list` where($condition) GROUP BY date_format(date,'%Y-%m')) b on a.id=b.myid ORDER BY myid DESC;";

        $info=RepayRatesList::findBySql($sql)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $pages = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => count($info),
            'pagination' => [
                'pageSize' => 15,
            ],

        ]);

        foreach($info as $key=>$value){
            $info[$key]['overdue_money']=$value['repay_s1_money']+$value['repay_s2_money']+$value['repay_s3_money']+$value['repay_s4_money']+$value['repay_s5_money']+$value['repay_s6_money'];
        }
        $update_time = '';
        if(!empty($info[0])){
            $update_time= empty($info[0]['updated_at']) ? date('Y-m-d H:i:s',$info[0]['created_at']):date('Y-m-d H:i:s',$info[0]['updated_at']);
        }
        return $this->render('overdue-list',
            [
                'info'=>$info,
                'pages'=>$pages->pagination,
                'update_time' => $update_time,
            ]
        );
    }

    /**
     * @name 对账列表\actionDayReconciliationList
     **/
    public function actionDayReconciliationList(){
        $condition = '1=1';
        $countQuery = FinancialReconcillationRecord::find()->where($condition)->groupBy(["FROM_UNIXTIME(created_at,'%Y-%m-%d')"]);
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 10;
        //$num = ceil($countQuery->count()/$pages->pageSize);
        //$start= $pages->offset/10;
        //零钱包数据都是从这一时间点开始：
        //echo $start.'-';
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $startDayTime= mktime(0,0,0,$month,$day,$year);
        $startDayTime = $startDayTime-24*60*60*$pages->offset;
        $endDay = $startDayTime-24*60*60*($pages->pageSize);
        $defaultDay = strtotime('2016-10-08 00:00:00');
        //$startDay = $startDay+24*60*60*$pages->offset;
        //$endDay = strtotime('0000-00-00 00:00:00');
        //$endDay = $startDay+24*60*60*($pages->pageSize-1);
        //echo date('Y-m-d',$startDayTime).'<br>';
        //echo date('Y-m-d',$endDay);
        if($this->request->post('search_submit')) { // 过滤
            $search = $this->request->post();
            $created_at = str_replace(' ','',$search['created_at']);
            if (!empty($search['created_at']))
                $condition .= " AND  FROM_UNIXTIME(created_at,'%Y-%m-%d') =  \"$created_at\"";
        }else{
            $condition .= " and created_at>={$defaultDay} and created_at>={$endDay}   and created_at<{$startDayTime}";
        }
        $query = FinancialReconcillationRecord::find()->where($condition)->orderBy(['created_at'=>SORT_DESC]);
        //echo $query->createCommand()->getRawSql();
        $info = $query->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $query_custom = CustomReconcillationRecord::find()->where($condition)->select('money,created_at,platform')->where($condition);
        //echo $query_custom->createCommand()->getRawSql();die;
        $info_custom= $query_custom->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $custom=[];
        foreach($info_custom as $custom_value){
            if(!isset($custom[date('Y-m-d',$custom_value['created_at'])]['custom_total']))
                $custom[date('Y-m-d',$custom_value['created_at'])]['custom_total']=0;
            $custom[date('Y-m-d',$custom_value['created_at'])]['custom_total'] += $custom_value['money'];
            $custom[date('Y-m-d',$custom_value['created_at'])][$custom_value['platform']] =  $custom_value['money'];
        }
        //echo '<pre>';
        //print_r($custom);die;
        $web_arr = [];
        $total_web_arr = [];
        foreach($info as $web_value){
            if(!isset($web_arr[date('Y-m-d',$web_value['created_at'])][$web_value['payment_type']])){
                $web_arr[date('Y-m-d',$web_value['created_at'])][$web_value['payment_type']]=0;
            }
            if(!isset($web_arr[date('Y-m-d',$web_value['created_at'])]['web_total'])){
                $web_arr[date('Y-m-d',$web_value['created_at'])]['web_total']=0;
            }
            if(!isset($total_web_arr[date('Y-m-d',$web_value['created_at'])])){
                $total_web_arr[date('Y-m-d',$web_value['created_at'])]=0;
            }
            $web_arr[date('Y-m-d',$web_value['created_at'])]['web_total']+= $web_value['money'];
            $web_arr[date('Y-m-d',$web_value['created_at'])][$web_value['payment_type']]+= $web_value['money'];
        }
        //echo '<pre>';
        //print_r($web_arr);die;
        foreach($web_arr as $key=>$value){
            if( count($value)<6){
                $keys = array_keys($value);
                for($i=1;$i<=5;$i++){
                    if(!in_array($i,$keys)){
                        $web_arr[$key][$i] = 0;
                    }
                }
            }
        }
        //echo '<pre>';
        //print_r($web_arr);die;
        $list_view_arr=[];
        foreach($web_arr as $web_date=>$web_value){
            if(!isset($custom[$web_date])){
                $custom[$web_date]=[6=>0,7=>0,8=>0,9=>0,'custom_total'=>0];
            }
            $list_view_arr[$web_date] =$web_value+$custom[$web_date];
        }
        //ksort($list_view_arr);
        return $this->render('reconcillation-list',
            [
                'list_view_arr'=>$list_view_arr,
                'pages' => $pages
            ]);
    }
    /**
     * @author chengyunbo
     * @date 2016-11-15
     * @name 财务管理-对账列表-录入功能/actionInputInfoView
     **/

    public function actionInputInfoView($operate_date ='', $view_type = 'input_view'){
        $condition = " FROM_UNIXTIME(`created_at`,'%Y-%m-%d') = '".$operate_date."'";
        if($this->getRequest()->getIsPost()){//保存录入数据
            $data = $this->request->post();
            $insert_arr = [];
            //易宝
            $insert_arr['yeepay']=
                [
                    'platform'=>6,
                    'money'=>empty($data['custom_yeepay_money'])?0:$data['custom_yeepay_money']*100,
                    'counter_fee'=>empty($data['custom_yeepay_counter_fee'])? 0:$data['custom_yeepay_counter_fee']*100,
                    'remark'=>$data['remark'],
                    'created_at'=>strtotime($operate_date),
                    'updated_at'=>time()
                ];
            //联动
            $insert_arr['unionpay']=
                [
                    'platform'=>7,
                    'money'=>empty($data['custom_unionpay_money'])?0:$data['custom_unionpay_money']*100,
                    'counter_fee'=>empty($data['custom_unionpay_counter_fee'])?0:$data['custom_unionpay_counter_fee']*100,
                    'remark'=>$data['remark'],
                    'created_at'=>strtotime($operate_date),
                    'updated_at'=>time()
                ];
            //银行卡
            $insert_arr['bank_trans']=
                [
                    'platform'=>8,
                    'money'=>empty($data['custom_bank_money'])?0:$data['custom_bank_money']*100,
                    'counter_fee'=>0,
                    'remark'=>$data['remark'],
                    'created_at'=>strtotime($operate_date),
                    'updated_at'=>time()
                ];
            //支付宝
            $insert_arr['alipay']=
                [
                    'platform'=>9,
                    'money'=>empty($data['custom_alipay_money'])?0:$data['custom_alipay_money']*100,
                    'counter_fee'=>0,
                    'remark'=>$data['remark'],
                    'created_at'=>strtotime($operate_date),
                    'updated_at'=>time()
                ];
            //echo '<pre>';
            //print_r($insert_arr);die;
            // echo CustomReconcillationRecord::find()->where($condition)->select('platform')->createCommand()->getRawSql();exit;
            $custom_info = CustomReconcillationRecord::find()->where($condition)->select('platform')->asArray()->all();
            $custom_val_arr =[];
            foreach($custom_info as $info){
                $custom_val_arr[]= $info['platform'];
                /*
				foreach($info as $key=>$info_val){
					$custom_val_arr[]=$info_val;
				}    */
            }
            //echo '<pre>';
            //print_r($custom_val_arr);die;
            if(empty($custom_info)){
                foreach($insert_arr as $arr){
                    $create_CRR = new CustomReconcillationRecord();
                    $create_CRR->platform = $arr['platform'];
                    $create_CRR->money = $arr['money'];
                    $create_CRR->counter_fee = $arr['counter_fee'];
                    $create_CRR->remark = $arr['remark'];
                    $create_CRR->created_at = $arr['created_at'];
                    $create_CRR->updated_at = $arr['updated_at'];
                    $create_CRR->save();
                }
            }else{
                foreach($insert_arr as $insert_val){
                    if(in_array($insert_val['platform'],$custom_val_arr)){
                        Yii::$app->db_kdkj->createCommand()->update(CustomReconcillationRecord::tableName(), [
                            'money' => $insert_val['money'],
                            'counter_fee'=>$insert_val['counter_fee'],
                            'remark' =>$insert_val['remark']
                        ],
                            ['platform' => $insert_val['platform'],'created_at'=>$insert_val['created_at']])->execute();
                    }else{
                        $create_CRR = new CustomReconcillationRecord();
                        $create_CRR->platform = $insert_val['platform'];
                        $create_CRR->money = $insert_val['money'];
                        $create_CRR->counter_fee = $insert_val['counter_fee'];
                        $create_CRR->remark = $insert_val['remark'];
                        $create_CRR->created_at = $insert_val['created_at'];
                        $create_CRR->updated_at = $insert_val['updated_at'];
                        $create_CRR->save();
                    }
                }
            }
            return $this->redirectMessage('录入成功', self::MSG_SUCCESS,Url::toRoute(['financial/day-reconciliation-list']));
        }else{//录入界面
            $query = FinancialReconcillationRecord::find()->where($condition);
            //echo $query->createCommand()->getRawSql();
            $info = $query->asArray()->all();
            // echo '<pre>';
            // print_r($info);
            $info_view = ['total_money'=>['custom_total_money'=>0]];
            $web_yeepay_money =0;
            $web_yeepay_counter_fee = 0;
            $web_unionpay_money = 0;
            $web_unionpay_counter_fee = 0;
            $web_bank_trans_money = 0;
            $web_alipay_money = 0;
            $web_total_money = 0;
            foreach($info as $item){
                if($item['platform']==FinancialReconcillationRecord::PLAT_FORM_UMPAY){ //联动优势支付
                    $web_unionpay_money+= $item['money'];
                    $web_unionpay_counter_fee+= $item['counter_fee'];
                }elseif($item['platform']==FinancialReconcillationRecord::PLAT_FORM_YEEPAY){//易宝支付
                    $web_yeepay_money+=$item['money'];
                    $web_yeepay_counter_fee+=$item['counter_fee'];
                }elseif($item['platform']==FinancialReconcillationRecord::PLAT_FORM_BANK_TRANS){//银行卡转账
                    $web_bank_trans_money += $item['money'];
                }elseif($item['platform']==FinancialReconcillationRecord::PLAT_FORM_REJECT||$item['platform']==FinancialReconcillationRecord::PLAT_FORM_UNREJECT){
                    $web_alipay_money += $item['money'];
                }
                $web_total_money+= $item['money'];
            }
            $info_view['yeepay_money']['web_yeepay_money'] = $web_yeepay_money;
            $info_view['yeepay_counter_fee']['web_yeepay_counter_fee'] = $web_yeepay_counter_fee;
            $info_view['unionpay_money']['web_unionpay_money'] = $web_unionpay_money;
            $info_view['unionpay_counter_fee']['web_unionpay_counter_fee'] = $web_unionpay_counter_fee*100;
            $info_view['bank_trans_money']['web_bank_trans_money'] = $web_bank_trans_money;
            $info_view['alipay_money']['web_alipay_money'] = $web_alipay_money ;
            $info_view['total_money']['web_total_money'] = $web_total_money+$web_yeepay_counter_fee+$web_unionpay_counter_fee*100;


            $query_custom = CustomReconcillationRecord::find()->where($condition)->select('money,created_at,counter_fee,platform,remark')->where($condition);
            $custom_arr= $query_custom->asArray()->all();
            $info_view['yeepay_money']['custom_yeepay_money'] = 0;
            $info_view['yeepay_counter_fee']['custom_yeepay_counter_fee'] = 0;
            $info_view['unionpay_money']['custom_unionpay_money'] = 0;
            $info_view['unionpay_counter_fee']['custom_unionpay_counter_money'] =0;
            $info_view['bank_trans_money']['custom_bank_trans_money'] = 0;
            $info_view['alipay_money']['custom_alipay_money'] =0;
            $info_view['total_money']['custom_total_money'] = 0;
            $info_view['remark']['remark_info'] = '';
            foreach($custom_arr as $custom_vlaue){
                if($custom_vlaue['platform']==6){//易宝
                    $info_view['yeepay_money']['custom_yeepay_money'] = isset($custom_vlaue['money'])? $custom_vlaue['money']:0;
                    $info_view['yeepay_counter_fee']['custom_yeepay_counter_fee'] = isset($custom_vlaue['counter_fee'])? $custom_vlaue['counter_fee']:0;
                }elseif($custom_vlaue['platform']==7){//联动
                    $info_view['unionpay_money']['custom_unionpay_money'] = isset($custom_vlaue['money'])? $custom_vlaue['money']:0;
                    $info_view['unionpay_counter_fee']['custom_unionpay_counter_money'] = isset($custom_vlaue['counter_fee'])? $custom_vlaue['counter_fee']:0;

                }elseif($custom_vlaue['platform']==8){//银行卡
                    $info_view['bank_trans_money']['custom_bank_trans_money']=isset($custom_vlaue['money'])? $custom_vlaue['money']:0;
                }elseif($custom_vlaue['platform']==9){//支付宝
                    $info_view['alipay_money']['custom_alipay_money']=isset($custom_vlaue['money'])? $custom_vlaue['money']:0;
                }
                $info_view['total_money']['custom_total_money'] += $custom_vlaue['money']+$custom_vlaue['counter_fee'];
                $info_view['remark']['remark_info'] = $custom_vlaue['remark'];
            }
            $operate_date = date('Y年m月d日',strtotime($operate_date));
            $info_view['yeepay_money']['yeepay_balance'] = $info_view['yeepay_money']['web_yeepay_money']-$info_view['yeepay_money']['custom_yeepay_money'];
            $info_view['yeepay_counter_fee']['yeepay_counter_balance'] = $info_view['yeepay_counter_fee']['web_yeepay_counter_fee']-$info_view['yeepay_counter_fee']['custom_yeepay_counter_fee'];
            $info_view['unionpay_money']['unionpay_balance'] = $info_view['unionpay_money']['web_unionpay_money']-$info_view['unionpay_money']['custom_unionpay_money'];
            $info_view['unionpay_counter_fee']['unionpay_counter_balance'] = $info_view['unionpay_counter_fee']['web_unionpay_counter_fee']-$info_view['unionpay_counter_fee']['custom_unionpay_counter_money'];
            $info_view['bank_trans_money']['bank_trans_balance'] = $info_view['bank_trans_money']['web_bank_trans_money']-$info_view['bank_trans_money']['custom_bank_trans_money'];
            $info_view['alipay_money']['alipay_balance'] = $info_view['alipay_money']['web_alipay_money']-$info_view['alipay_money']['custom_alipay_money'];
            $info_view['total_money']['total_balance'] = $info_view['total_money']['web_total_money']-$info_view['total_money']['custom_total_money'];
            //echo '<pre>';
            //print_r($info_view);die;
            return $this->render('input-info-view',
                [
                    'info'=>$info_view,
                    'date'=>$operate_date,
                    'view_type'=>$view_type
                ]);
        }
    }
    /**
     * @author chengyunbo
     * @date 2016-11-15
     * @name 财务管理-对账列表-查看功能/actionLookInfoView
     **/
    public function actionLookInfoView($operate_date){

        return  self::actionInputInfoView($operate_date,'look_view');
    }
    /**
     * @author chengyunbo
     * @date 2016-11-22
     * @name 财务管理-统计管理-运营成本统计列表/actionExpenseList
     **/
    public function actionExpenseList(){
        $condition = '1=1';
        if($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['begin_created_at'])) {
                $begin_created_at = $search['begin_created_at'];
                $condition .= " AND date>='{$begin_created_at}'";
            }
            if(!empty($search['end_created_at'])){
                $end_created_at =$search['end_created_at'];
                $condition .= " AND date<='{$end_created_at}'";
            }
            //资方id
            $fund_id = $search['fund_id'] ?? 0;
            $condition .= " AND fund_id = {$fund_id}";
        }
        $query = FinancialExpense::find()->where($condition)->orderBy("date desc");
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $info = $countQuery->offset($pages->offset)->asArray()->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        $update_time = '';
        if(!empty($info[0])){
            $update_time= empty($info[0]['updated_at']) ? date('Y-m-d H:i:s',$info[0]['created_at']):date('Y-m-d H:i:s',$info[0]['updated_at']);
        }
        return $this->render('expense-list',
            [
                'info'=>$info,
                'pages' => $pages,
                'update_time'=>$update_time
            ]);
    }
    /**
     * @name 每日支出统计表\actionExpenseDayList
     **/
    public function actionExpenseDayList(){
        return $this->actionExpenseList('day');
    }
    /**
     * @name 每月支出统计表\actionExpenseMonthList
     **/
    public function actionExpenseMonthList(){
        return $this->actionExpenseList('month');
    }

    /**
     * @name 财务管理-每日未还本金列表/actionDayNotYetPrincipalList
     */
    public function actionDayNotYetPrincipalList(){
        $condition = " id > 0 ";
        $search = $this->request->get();
        if (!empty($search['begin_created_at'])) {
            $begin_created_at = strtotime($search['begin_created_at']);
            $condition .= " AND loan_time >= {$begin_created_at}";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = strtotime($search['end_created_at']);
            $condition .= " AND loan_time <= {$end_created_at}";
        }
        if(!empty($search['fund_id'])){
            $fund_id = $search['fund_id'];
            $condition .= " AND fund_id = {$fund_id}";
        }

        $db_stats = Yii::$app->get('db_stats');
        $query = DayNotYetPrincipalStatistics::find()
            ->select(["FROM_UNIXTIME(loan_time, '%Y-%m-%d') AS loan_date,
					SUM(loan_principal) AS total_principal,
					SUM(counter_fee) AS counter_fee,
					SUM(true_total_money) AS true_total_principal,
					SUM(normal_principal) AS normal_principal,
					SUM(s1_principal) AS s1_principal,
					SUM(s2_principal) AS s2_principal,
					SUM(m1_principal) AS m1_principal,
					SUM(m2_principal) AS m2_principal,
					SUM(m3_principal) AS m3_principal,
					created_at,
					updated_at"])
            ->where($condition)
            ->orderBy("loan_date DESC")
            ->groupBy('loan_date');
        //$sql = $query->createCommand()->getRawSql();
        $countQuery = $allQuery = clone $query;
        $all_data = $allQuery->asArray()->all();
        $count = $countQuery->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $countQuery->offset($pages->offset)->asArray()->limit($pages->limit)->all($db_stats);
        $update_time = $start_date = $end_date = "";
        if(!empty($all_data[0])){
            $update_time= empty($all_data[0]['updated_at']) ? date('Y-m-d H:i:s',$all_data[0]['created_at']):date('Y-m-d H:i:s',$all_data[0]['updated_at']);
            $start_date = $all_data[$count-1]['loan_date'] ?? "";
            $end_date = $all_data[0]['loan_date'] ?? "";
        }

        //获取累积本金相关数据
        $sub_info = [
            'sub_total' => [
                'loantime' => $start_date.'/'.$end_date,
                'total_principal'=>0,
                'true_loan_money'=>0,
                'true_total_principal'=>0,
                'not_yet_principal'=>0,
                'not_yet_normal_principal'=>0,
                's1_principal' =>0,
                's2_principal' =>0,
                's3_principal' =>0,
                's4_principal' =>0,
                's5_principal' =>0,
                'interests'    =>0,
                'late_fee'     =>0
            ]
        ];

        //汇总
        $exportData = [];
        foreach ($all_data as $k => $data) {
            $loan_date = $data['loan_date'] ?? '';//日期
            $total_principal = $data['total_principal'] ?? 0;//借款本金
            $counter_fee = $data['counter_fee'] ?? 0;//借款手续费
            $true_total_principal = $data['true_total_principal'] ?? 0;//实际已还金额
            $normal_principal = $data['normal_principal'] ?? 0;//正常未还本金
            $s1_principal = $data['s1_principal'] ?? 0;//S1未还本金
            $s2_principal = $data['s2_principal'] ?? 0;//S2未还本金
            $m1_principal = $data['m1_principal'] ?? 0;//M1未还本金
            $m2_principal = $data['m2_principal'] ?? 0;//M2未还本金
            $m3_principal = $data['m3_principal'] ?? 0;//M3未还本金
            $true_loan_money = $total_principal - $counter_fee;//实际放款金额
            $not_yet_principal = $normal_principal + $s1_principal + $s2_principal + $m1_principal + $m2_principal + $m3_principal;//未还本金

            $sub_info['sub_total']['total_principal'] += $total_principal;
            $sub_info['sub_total']['true_loan_money'] += $true_loan_money;
            $sub_info['sub_total']['true_total_principal'] += $true_total_principal;
            $sub_info['sub_total']['not_yet_principal'] += $not_yet_principal;
            $sub_info['sub_total']['not_yet_normal_principal'] += $normal_principal;
            $sub_info['sub_total']['s1_principal'] += $s1_principal;
            $sub_info['sub_total']['s2_principal'] += $s2_principal;
            $sub_info['sub_total']['s3_principal'] += $m1_principal;
            $sub_info['sub_total']['s4_principal'] += $m2_principal;
            $sub_info['sub_total']['s5_principal'] += $m3_principal;

            // 添加利息  滞纳金
            $interests = 0;
            $late_fee  = 0;
            $yh_interests = 0;
            $yh_late_fee = 0;
            $sy_interests = 0;
            $sy_late_fee = 0;

            if($loan_date){

                $time_start = strtotime($loan_date);
                $time_end   = $time_start + 86400;

                $sql='SELECT r.id,r.interests, r.late_fee,
					SUM(IFNULL(l.operator_interests,0)) operator_interests ,
					SUM(IFNULL(l.operator_late_fee,0)) operator_late_fee
					FROM `tb_user_loan_order_repayment`  r
					LEFT JOIN tb_user_credit_money_log  l ON r.order_id = l.order_id  AND l.`status` =  1
					WHERE r.loan_time >='.$time_start.' AND r.loan_time < '.$time_end.'
					GROUP BY id  ';

                $res  = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

                if($res){
                    $interests =  array_sum(array_column($res,'interests'));
                    $late_fee  =  array_sum(array_column($res,'late_fee'));
                    $yh_interests = array_sum(array_column($res,'operator_interests'));
                    $yh_late_fee = array_sum(array_column($res,'operator_late_fee'));

                    $sy_interests = $interests - $yh_interests;
                    $sy_late_fee  = $late_fee - $yh_late_fee;
                }
            }

            $sub_info['sub_total']['interests'] += $sy_interests;
            $sub_info['sub_total']['late_fee'] += $sy_late_fee;

            //导出数据
            $exportData[$loan_date] = [
                'loantime' => $loan_date,
                'total_principal' => $total_principal,
                'true_loan_money' => $true_loan_money,
                'true_total_principal' => $true_total_principal,
                'not_yet_principal' => $not_yet_principal,
                'not_yet_normal_principal' => $normal_principal,
                's1_principal' => $s1_principal,
                's2_principal' => $s2_principal,
                's3_principal' => $m1_principal,
                's4_principal' => $m2_principal,
                's5_principal' => $m3_principal,
                'interests'    => $sy_interests,
                'late_fee'     => $sy_late_fee,
            ];
        }
        //按天
        foreach ($info as $k => $data) {
            $loan_date = $data['loan_date'] ?? '';//日期
            $total_principal = $data['total_principal'] ?? 0;//借款本金
            $counter_fee = $data['counter_fee'] ?? 0;//借款手续费
            $true_total_principal = $data['true_total_principal'] ?? 0;//实际已还金额
            $normal_principal = $data['normal_principal'] ?? 0;//正常未还本金
            $s1_principal = $data['s1_principal'] ?? 0;//S1未还本金
            $s2_principal = $data['s2_principal'] ?? 0;//S2未还本金
            $m1_principal = $data['m1_principal'] ?? 0;//M1未还本金
            $m2_principal = $data['m2_principal'] ?? 0;//M2未还本金
            $m3_principal = $data['m3_principal'] ?? 0;//M3未还本金
            $true_loan_money = $total_principal - $counter_fee;//实际放款金额
            $not_yet_principal = $normal_principal + $s1_principal + $s2_principal + $m1_principal + $m2_principal + $m3_principal;//未还本金

            // 添加利息  滞纳金
            $interests = 0;
            $late_fee  = 0;
            $yh_interests = 0;
            $yh_late_fee = 0;
            $sy_interests = 0;
            $sy_late_fee = 0;

            if($loan_date){

                $time_start = strtotime($loan_date);
                $time_end   = $time_start + 86400;

                $sql='SELECT r.id,r.interests, r.late_fee,
					SUM(IFNULL(l.operator_interests,0)) operator_interests ,
					SUM(IFNULL(l.operator_late_fee,0)) operator_late_fee
					FROM `tb_user_loan_order_repayment`  r
					LEFT JOIN tb_user_credit_money_log  l ON r.order_id = l.order_id  AND l.`status` =  1
					WHERE r.loan_time >='.$time_start.' AND r.loan_time < '.$time_end.'
					GROUP BY id  ';

                $res  = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

                if($res){
                    $interests =  array_sum(array_column($res,'interests'));
                    $late_fee  =  array_sum(array_column($res,'late_fee'));
                    $yh_interests = array_sum(array_column($res,'operator_interests'));
                    $yh_late_fee = array_sum(array_column($res,'operator_late_fee'));

                    $sy_interests = $interests - $yh_interests;
                    $sy_late_fee  = $late_fee - $yh_late_fee;
                }
            }

            $sub_info[$loan_date] = [
                'loantime' => $loan_date,
                'total_principal' => $total_principal,
                'true_loan_money' => $true_loan_money,
                'true_total_principal' => $true_total_principal,
                'not_yet_principal' => $not_yet_principal,
                'not_yet_normal_principal' => $normal_principal,
                's1_principal' => $s1_principal,
                's2_principal' => $s2_principal,
                's3_principal' => $m1_principal,
                's4_principal' => $m2_principal,
                's5_principal' => $m3_principal,
                'interests'    => $sy_interests,
                'late_fee'     => $sy_late_fee,
            ];
        }
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDayNotYet($exportData);
        }
        unset($all_data);//用完之后，清空数组
        return $this->render('day-not-yet-principal-list',
            [
                'info' => $sub_info,
                'pages' => $pages,
                'update_time' => $update_time
            ]);
    }

    /**
     * @name 财务管理-每日未还本金对账/actionDayNotYetPrincipalAccount
     */
    public function actionDayNotYetPrincipalAccount(){
        $condition = " id > 0 ";
        $search = $this->request->get();
        if (!empty($search['begin_created_at'])) {
            $begin_created_at = strtotime($search['begin_created_at']);
            $condition .= " AND loan_time >= {$begin_created_at}";
        }
        if(!empty($search['end_created_at'])){
            $end_created_at = strtotime($search['end_created_at']);
            $condition .= " AND loan_time <= {$end_created_at}";
        }
        if(!empty($search['fund_id'])){
            $fund_id = $search['fund_id'];
            $condition .= " AND fund_id = {$fund_id}";
        }

        $db_stats = Yii::$app->get('db_stats');
        $query = DayNotYetPrincipalStatistics::find()
            ->select(["FROM_UNIXTIME(loan_time, '%Y-%m-%d') AS loan_date,
					SUM(loan_principal) AS total_principal,
					SUM(counter_fee) AS counter_fee,
					SUM(true_total_money) AS true_total_principal,
					SUM(normal_principal) AS normal_principal,
					SUM(s1_principal) AS s1_principal,
					SUM(s2_principal) AS s2_principal,
					SUM(m1_principal) AS m1_principal,
					SUM(m2_principal) AS m2_principal,
					SUM(m3_principal) AS m3_principal,
					created_at,
					updated_at"])
            ->where($condition)
            ->orderBy("loan_date DESC")
            ->groupBy('loan_date');
        //$sql = $query->createCommand()->getRawSql();
        $countQuery = $allQuery = clone $query;
        $all_data = $allQuery->asArray()->all();
        $count = $countQuery->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $countQuery->offset($pages->offset)->asArray()->limit($pages->limit)->all($db_stats);
        $update_time = $start_date = $end_date = "";
        if(!empty($all_data[0])){
            $update_time= empty($all_data[0]['updated_at']) ? date('Y-m-d H:i:s',$all_data[0]['created_at']):date('Y-m-d H:i:s',$all_data[0]['updated_at']);
            $start_date = $all_data[$count-1]['loan_date'] ?? "";
            $end_date = $all_data[0]['loan_date'] ?? "";
        }

        //获取累积本金相关数据
        $sub_info = [
            'sub_total' => [
                'loantime' => $start_date.'/'.$end_date,
                'total_principal'=>0,
                'true_loan_money'=>0,
                'true_total_principal'=>0,
                'not_yet_principal'=>0,
                'not_yet_normal_principal'=>0,
                's1_principal' =>0,
                's2_principal' =>0,
                's3_principal' =>0,
                's4_principal' =>0,
                's5_principal' =>0,
                'interests'    =>0,
                'late_fee'     =>0
            ]
        ];

        //汇总
        $exportData = [];
        foreach ($all_data as $k => $data) {
            $loan_date = $data['loan_date'] ?? '';//日期
            $total_principal = $data['total_principal'] ?? 0;//借款本金
            $counter_fee = $data['counter_fee'] ?? 0;//借款手续费
            $true_total_principal = $data['true_total_principal'] ?? 0;//实际已还金额
            $normal_principal = $data['normal_principal'] ?? 0;//正常未还本金
            $s1_principal = $data['s1_principal'] ?? 0;//S1未还本金
            $s2_principal = $data['s2_principal'] ?? 0;//S2未还本金
            $m1_principal = $data['m1_principal'] ?? 0;//M1未还本金
            $m2_principal = $data['m2_principal'] ?? 0;//M2未还本金
            $m3_principal = $data['m3_principal'] ?? 0;//M3未还本金
            $true_loan_money = $total_principal - $counter_fee;//实际放款金额
            $not_yet_principal = $normal_principal + $s1_principal + $s2_principal + $m1_principal + $m2_principal + $m3_principal;//未还本金

            $sub_info['sub_total']['total_principal'] += $total_principal;
            $sub_info['sub_total']['true_loan_money'] += $true_loan_money;
            $sub_info['sub_total']['true_total_principal'] += $true_total_principal;
            $sub_info['sub_total']['not_yet_principal'] += $not_yet_principal;
            $sub_info['sub_total']['not_yet_normal_principal'] += $normal_principal;
            $sub_info['sub_total']['s1_principal'] += $s1_principal;
            $sub_info['sub_total']['s2_principal'] += $s2_principal;
            $sub_info['sub_total']['s3_principal'] += $m1_principal;
            $sub_info['sub_total']['s4_principal'] += $m2_principal;
            $sub_info['sub_total']['s5_principal'] += $m3_principal;

            // 添加利息  滞纳金
            $interests = 0;
            $late_fee  = 0;
            $yh_interests = 0;
            $yh_late_fee = 0;
            $sy_interests = 0;
            $sy_late_fee = 0;

            if($loan_date){

                $time_start = strtotime($loan_date);
                $time_end   = $time_start + 86400;

                $sql='SELECT r.id,r.interests, r.late_fee,
					SUM(IFNULL(l.operator_interests,0)) operator_interests ,
					SUM(IFNULL(l.operator_late_fee,0)) operator_late_fee
					FROM `tb_user_loan_order_repayment`  r
					LEFT JOIN tb_user_credit_money_log  l ON r.order_id = l.order_id  AND l.`status` =  1
					WHERE r.loan_time >='.$time_start.' AND r.loan_time < '.$time_end.'
					GROUP BY id  ';

                $res  = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

                if($res){
                    $interests =  array_sum(array_column($res,'interests'));
                    $late_fee  =  array_sum(array_column($res,'late_fee'));
                    $yh_interests = array_sum(array_column($res,'operator_interests'));
                    $yh_late_fee = array_sum(array_column($res,'operator_late_fee'));

                    $sy_interests = $interests - $yh_interests;
                    $sy_late_fee  = $late_fee - $yh_late_fee;
                }
            }

            $sub_info['sub_total']['interests'] += $sy_interests;
            $sub_info['sub_total']['late_fee'] += $sy_late_fee;

            //导出数据
            $exportData[$loan_date] = [
                'loantime' => $loan_date,
                'total_principal' => $total_principal,
                'true_loan_money' => $true_loan_money,
                'true_total_principal' => $true_total_principal,
                'not_yet_principal' => $not_yet_principal,
                'not_yet_normal_principal' => $normal_principal,
                's1_principal' => $s1_principal,
                's2_principal' => $s2_principal,
                's3_principal' => $m1_principal,
                's4_principal' => $m2_principal,
                's5_principal' => $m3_principal,
                'interests'    => $sy_interests,
                'late_fee'     => $sy_late_fee,
            ];
        }
        //按天
        foreach ($info as $k => $data) {
            $loan_date = $data['loan_date'] ?? '';//日期
            $total_principal = $data['total_principal'] ?? 0;//借款本金
            $counter_fee = $data['counter_fee'] ?? 0;//借款手续费
            $true_total_principal = $data['true_total_principal'] ?? 0;//实际已还金额
            $normal_principal = $data['normal_principal'] ?? 0;//正常未还本金
            $s1_principal = $data['s1_principal'] ?? 0;//S1未还本金
            $s2_principal = $data['s2_principal'] ?? 0;//S2未还本金
            $m1_principal = $data['m1_principal'] ?? 0;//M1未还本金
            $m2_principal = $data['m2_principal'] ?? 0;//M2未还本金
            $m3_principal = $data['m3_principal'] ?? 0;//M3未还本金
            $true_loan_money = $total_principal - $counter_fee;//实际放款金额
            $not_yet_principal = $normal_principal + $s1_principal + $s2_principal + $m1_principal + $m2_principal + $m3_principal;//未还本金

            // 添加利息  滞纳金
            $interests = 0;
            $late_fee  = 0;
            $yh_interests = 0;
            $yh_late_fee = 0;
            $sy_interests = 0;
            $sy_late_fee = 0;

            if($loan_date){

                $time_start = strtotime($loan_date);
                $time_end   = $time_start + 86400;

                $sql='SELECT r.id,r.interests, r.late_fee,
					SUM(IFNULL(l.operator_interests,0)) operator_interests ,
					SUM(IFNULL(l.operator_late_fee,0)) operator_late_fee
					FROM `tb_user_loan_order_repayment`  r
					LEFT JOIN tb_user_credit_money_log  l ON r.order_id = l.order_id  AND l.`status` =  1
					WHERE r.loan_time >='.$time_start.' AND r.loan_time < '.$time_end.'
					GROUP BY id  ';

                $res  = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

                if($res){
                    $interests =  array_sum(array_column($res,'interests'));
                    $late_fee  =  array_sum(array_column($res,'late_fee'));
                    $yh_interests = array_sum(array_column($res,'operator_interests'));
                    $yh_late_fee = array_sum(array_column($res,'operator_late_fee'));

                    $sy_interests = $interests - $yh_interests;
                    $sy_late_fee  = $late_fee - $yh_late_fee;
                }
            }

            $sub_info[$loan_date] = [
                'loantime' => $loan_date,
                'total_principal' => $total_principal,
                'true_loan_money' => $true_loan_money,
                'true_total_principal' => $true_total_principal,
                'not_yet_principal' => $not_yet_principal,
                'not_yet_normal_principal' => $normal_principal,
                's1_principal' => $s1_principal,
                's2_principal' => $s2_principal,
                's3_principal' => $m1_principal,
                's4_principal' => $m2_principal,
                's5_principal' => $m3_principal,
                'interests'    => $sy_interests,
                'late_fee'     => $sy_late_fee,
            ];
        }
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDayNotYet($exportData);
        }
        unset($all_data);//用完之后，清空数组
        return $this->render('day-not-yet-principal-account',
            [
                'info' => $sub_info,
                'pages' => $pages,
                'update_time' => $update_time
            ]);
    }

    /**
     * （2017-09-18）之前使用的是这个方法
     * @author chengyunbo
     * @date 2016-11-30
     * @name 财务管理-每日未还本金列表（旧）/actionDayNotYetPrincipalListBak
     **/
    public function actionDayNotYetPrincipalListBak(){
        $condition = '';
        $today_time = strtotime(date("Y-m-d", time()));
        $before_time = $today_time - (120*86400);

        $search = $this->request->get();
        $source_id = 0;
        if(!empty($search['source_id'])){
            $source_id = intval($search['source_id']);
            $condition .= " AND p.source_id = {$source_id}";
        }
        $data = Yii::$app->cache->get("view_cache_financial_{$source_id}");
        if ($data === false)
        {
            //获取每天未还本金相关数据
            $query_str = "SELECT
							FROM_UNIXTIME(r.loan_time,'%Y-%m-%d') AS loantime,
							SUM(r.principal) AS total_principal,
							SUM(l.money_amount-l.counter_fee) AS true_loan_money,
							SUM(r.true_total_money) as true_total_principal,
							SUM(IF(r.is_overdue=0,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS not_yet_normal_principal,
							SUM(IF(r.is_overdue>0 AND r.overdue_day>=1 AND r.overdue_day<=10,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s1_principal,
							SUM(IF(r.is_overdue>0 AND r.overdue_day>=11 AND r.overdue_day<=30,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s2_principal,
							SUM(IF(r.is_overdue>0 AND r.overdue_day>=31 AND r.overdue_day<=60,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s3_principal,
							SUM(IF(r.is_overdue>0 AND r.overdue_day>=61 AND r.overdue_day<=90,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s4_principal,
							SUM(IF(r.is_overdue>0 AND r.overdue_day>=91 AND r.overdue_day<=120,if(r.principal-r.true_total_money<0,0,r.principal-r.true_total_money),0)) AS s5_principal
						FROM tb_user_loan_order_repayment AS r
						LEFT JOIN tb_user_loan_order AS l ON r.order_id = l.id
						LEFT JOIN tb_loan_person AS p ON r.user_id = p.id
						WHERE l.id > 0
						AND r.id > 0
						AND l.order_type=1
						AND r.loan_time < {$today_time}
						{$condition}
						GROUP BY loantime
						ORDER BY r.id desc";
            $data = Yii::$app->db_kdkj_rd->createCommand($query_str)->queryAll();
            Yii::$app->cache->set("view_cache_financial_{$source_id}", $data,300);//设置缓存数据有效期为5分钟
        }

        $sub_info =
            [
                'sub_total' =>
                    [
                        'loantime'=>'',
                        'total_principal'=>0,
                        'true_loan_money'=>0,
                        'true_total_principal'=>0,
                        'not_yet_principal'=>0,
                        'not_yet_normal_principal'=>0,
                        's1_principal' =>0,
                        's2_principal' =>0,
                        's3_principal' =>0,
                        's4_principal' =>0,
                        's5_principal' =>0
                    ]
            ];
        $data_arr = [];
        $begin_created_at = '';
        $end_created_at = '';
        if($this->request->get('search_submit')) { // 过滤
            if (!empty($search['begin_created_at'])) {
                $begin_created_at = str_replace(' ','',$search['begin_created_at']);
            }
            if(!empty($search['end_created_at'])){
                $end_created_at = str_replace(' ','',$search['end_created_at']);
            }
        }
        foreach($data as $data_info){
            if(empty($begin_created_at)&&empty($end_created_at)){
                $data_arr[]= $data_info;
            }elseif(!empty($begin_created_at)&&!empty($end_created_at)){
                if($data_info['loantime']>=$begin_created_at&&$data_info['loantime']<=$end_created_at){
                    $data_arr[]= $data_info;
                }
            }elseif(!empty($begin_created_at)){
                if($data_info['loantime']>=$begin_created_at){
                    $data_arr[]= $data_info;
                }
            }elseif(!empty($end_created_at)){
                if($data_info['loantime']<=$end_created_at){
                    $data_arr[]= $data_info;
                }
            }
        }
        $data = $data_arr;
        $pages = new Pagination(['totalCount' => count($data)]);
        $pages->pageSize = 10;
        $num = count($data);
        //获取累积本金相关数据
        for($j = 0;$j<$num;$j++){
            $sub_info['sub_total']['loantime'] = $data[$num-1]['loantime'].'/'.$data[0]['loantime'];
            $sub_info['sub_total']['not_yet_principal'] += $data[$j]['not_yet_normal_principal']+$data[$j]['s1_principal']+$data[$j]['s2_principal']+$data[$j]['s3_principal']+$data[$j]['s4_principal']+$data[$j]['s5_principal'];
            $sub_info['sub_total']['not_yet_normal_principal'] += $data[$j]['not_yet_normal_principal'];
            $sub_info['sub_total']['total_principal'] += $data[$j]['total_principal'];
            $sub_info['sub_total']['true_loan_money'] += $data[$j]['true_loan_money'];
            $sub_info['sub_total']['true_total_principal'] += $data[$j]['true_total_principal'];
            $sub_info['sub_total']['s1_principal'] += $data[$j]['s1_principal'];
            $sub_info['sub_total']['s2_principal'] += $data[$j]['s2_principal'];
            $sub_info['sub_total']['s3_principal'] += $data[$j]['s3_principal'];
            $sub_info['sub_total']['s4_principal'] += $data[$j]['s4_principal'];
            $sub_info['sub_total']['s5_principal'] += $data[$j]['s5_principal'];
        }
        //获取每日未还本金数据
        for($i=$pages->offset;$i<$num;$i++){
            if($i<$pages->offset+$pages->pageSize){
                $data[$i]['not_yet_principal'] = $data[$i]['not_yet_normal_principal']+$data[$i]['s1_principal']+$data[$i]['s2_principal']+$data[$i]['s3_principal']+$data[$i]['s4_principal']+$data[$i]['s5_principal'];
                $sub_info[$i] = $data[$i];
            }
        }
        unset($data);//用完之后，清空数组
        //排除掉因为初始化时而存在的有键没有值的情况
        if(count($sub_info)==1&&$sub_info['sub_total']['loantime']===''){
            $sub_info = [];
        }
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportDayNotYet($data_arr);
        }
        return $this->render('day-not-yet-principal-list',
            [
                'info'=>$sub_info,
                'pages' => $pages
            ]);
    }

    /**
     * @name 财务管理-统计管理列表-每日未还本金导出_exportDayNotYet
     */
    public function _exportDayNotYet($datas){
        Util::cliLimitChange(1024);
        $check = $this->_canExportData();
        if(!$check){
            return $this->redirectMessage('无权限', self::MSG_ERROR);
        }else{
            $this->_setcsvHeader('每日未还本金报表.csv');
            $items = [];
            foreach($datas as $value){
                $items[] = [
                    '放款日期' => $value['loantime'],
                    '借款本金' => sprintf("%0.2f",$value['total_principal']/100),
                    '实际打款金额' => sprintf("%0.2f",$value['true_loan_money']/100),
                    '实际已还金额' => sprintf("%0.2f",$value['true_total_principal']/100),
                    '未还本金' =>sprintf("%0.2f",$value['not_yet_principal']/100),
                    '正常未还本金' =>sprintf("%0.2f",$value['not_yet_normal_principal']/100),
                    'S1'=> sprintf("%0.2f",$value['s1_principal']/100),
                    'S2' =>sprintf("%0.2f",$value['s2_principal']/100),
                    'M1' =>sprintf("%0.2f",$value['s3_principal']/100),
                    'M2' =>sprintf("%0.2f",$value['s4_principal']/100),
                    'M3' =>sprintf("%0.2f",$value['s5_principal']/100),
                ];
            }
            echo $this->_array2csv($items);
            exit;
        }
    }
    /**
     * @name 打款扣款状态查询
     * @return string
     */
    public function actionYeePayQuery(){
        $order_id =  $this->request->post('order_id','');
        $order_id = strval(trim($order_id));
        $data = '';
        $map = [
            'start' => '进行中',
            'success' => '成功',
            'fail' => '失败',
        ];
        $ret = [];
        $type = $this->request->post('type');
        $fund = $this->request->post('fund');
        if(Yii::$app->request->isPost) {
            $service = Yii::$container->get('JshbService');

            if (empty($order_id) || (strlen($order_id) < 6)) {
                return $this->redirectMessage('非法订单号', self::MSG_ERROR);
            }
            if ($type == 1) {

                $params['order_id'] = $order_id;
                $ret = $service->withholdQuery($params);
                if (isset($ret['code']) && isset($ret['data']) && $ret['code'] == 0) {
                    $data = $ret['data'];
                }

            } else {
                $params = [
                    'biz_order_no' => $order_id
                ];
                $ret = $service->queryLoanRecord($params);
                if (isset($ret['code']) && isset($ret['data']) && $ret['code'] == 0) {
                    $data = $ret['data'];
                }
            }

        }

        return $this->render('yee-pay-result',[
            'type' => $type,
            'fund' => $fund,
            'ret' => $ret,
            'data' => $data,
            'order_id' => $order_id,
            'map' => $map
        ]);
    }

    /**
     * @name 日志作废
     * @param $id
     * @return string
     */
    public function actionCancelCreditMoneyLog($id)
    {
        if(!$id)
            return $this->redirectMessage('参数错误', self::MSG_ERROR, Url::toRoute('financial/bankpay-list'));

        $log = UserCreditMoneyLog::findOne($id);

        if(!$log)
            return $this->redirectMessage('无此记录', self::MSG_ERROR, Url::toRoute('financial/bankpay-list'));

        $log->status = UserCreditMoneyLog::STATUS_CANCEL;
        $log->pay_order_id = '_'.$log->pay_order_id;

        if($log->save())
            return $this->redirectMessage('作废成功', self::MSG_SUCCESS);
        else
            return $this->redirectMessage('作废失败', self::MSG_ERROR, Url::toRoute('financial/bankpay-list'));
    }


    /**
     * @name 51资方订单放款驳回
     * @param integer $id 订单ID
     */
    public function actionWyFundOrderPayRejected($id, $return_url=null) {
        $order = UserLoanOrder::findOne((int)$id);
        if(!$order) {
            return $this->redirectMessage('找不到对应订单',self::MSG_ERROR);
        } else if($order->status!= UserLoanOrder::STATUS_PAY) {
            return $this->redirectMessage('订单状态不为打款中',self::MSG_ERROR);
        }

        if(($post_data = Yii::$app->getRequest()->post())) {
            if(empty($post_data['remark'])) {
                return $this->redirectMessage('驳回备注不能为空',self::MSG_ERROR);
            }

            $service = Yii::$container->get('orderService');
            /* @var $service OrderService */
            $ret = $service->rejectLoan($order->id, $post_data['remark'],  Yii::$app->user->identity->username);

            if($ret['code']==0) {
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS, $return_url ? $return_url : Url::toRoute(['/pocket/pocket-list']));
            } else {
                return $this->redirectMessage('操作失败:'.$ret['message'], self::MSG_ERROR);
            }
        }

        return $this->render('wy-fund-order-pay-rejected', [
            'order'=>$order,
            'post_data'=>$post_data
        ]);
    }

    /**
     * @name 获取打款状态码
     * @param integer $id 订单ID
     */
    public function actionGetRemitStatus()
    {
        $this->response->format = "json";
        $id = \Yii::$app->request->get('id');
        $loan = FinancialLoanRecord::find()->where(['id'=>$id,'status'=>FinancialLoanRecord::UMP_PAY_HANDLE_FAILED])->one();
        if(!$loan){
            return [
                'code' => -1,
                'msg' => '记录不存在'
            ];
        }

        $params = [
            'biz_order_no' => $loan['order_id'],
        ];
        $service = new JshbService();
        $ret = $service->queryLoanRecord($params);
        if (!$ret) {
            return [
                'code' => -1,
                'msg' => '获取失败'
            ];
        }

        if (isset($ret['data']['error_code'])){
            $loan->updated_at = time();
            $loan->remit_status_code = $ret['data']['error_code'];
            if ($loan->save()) {
                return [
                    'code' => 0,
                    'msg' => '获取成功'
                ];
            }else{
                return [
                    'code' => -1,
                    'msg' => '状态保存失败'
                ];
            }
        }

    }


    /**
     *
     * @return string
     * @name 财务管理-小钱包打款扣款管理-待扣款列表-部分还款/actionPartialPayment
     */
    public function actionPartialPayment() {
        $id = $this->request->get("id");
        $user_id = $this->request->get("user_id");
        $data = FinancialDebitRecord::find()->where(['id' => $id, 'user_id' => $user_id])->with([
            'loanPerson'=> function($queryUser) {
                $queryUser->select(['name','uid','phone', 'id', 'id_number', 'contact_username', 'is_verify', 'id_number', 'contact_phone', 'card_bind_status']);
            },
            'cardInfo'=> function($queryCard) {
                $queryCard->select(['bank_name', 'bank_id', 'id', 'card_no','type', 'phone', 'status']);
            },
            'userLoanOrder',
        ])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if (Yii::$app->request->isPost){
            $id = Yii::$app->request->post("id");
            if (empty($id)) {
                return $this->redirectMessage('抱歉，必要参数不能为空。', self::MSG_ERROR);
            }
            $debit_record = FinancialDebitRecord::findOne($id);
            if (!$debit_record || ($debit_record->status != FinancialDebitRecord::STATUS_PAYING)){
                return $this->redirectMessage('扣款记录状态不正确', self::MSG_ERROR);
            }


            $service = Yii::$container->get('orderService');
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try{

                if (!FinancialDebitRecord::addDebitLock($debit_record->loan_record_id, $debit_record->user_id)) {
                    return $this->redirectMessage('该订单被锁定状态', self::MSG_ERROR);
                }
                $debit_record->status = FinancialDebitRecord::STATUS_REFUSE;
                $debit_record->remark = '部分还款,拒绝现有扣款记录';
                if(!$debit_record->save()){
                    throw new \Exception('拒绝旧记录失败');
                }

                $result = $service->getLqRepayInfo($debit_record['repayment_id']); #创建扣款记录
                if (!$result) {
                    throw new \Exception('扣款记录创建失败');
                }

                $transaction->commit();

                FinancialDebitRecord::clearDebitLock($debit_record->loan_record_id, $debit_record->user_id);
                return $this->redirectMessage('生成成功', self::MSG_SUCCESS);
            }catch(\Exception $e){
                $transaction->rollback();
                return $this->redirectMessage($e->getMessage(), self::MSG_ERROR);
            }



        }
        return $this->render('partial-payment', [
            'info' => $data,
        ]);

    }

    /**
     *
     * @return string
     * @name 财务管理-支付宝还款列表-修改溢缴款/actionModifyOverflowPayment
     */
    public function actionModifyOverflowPayment()
    {
        $this->response->format = Response::FORMAT_JSON;
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');


        $alipay_repayment_log = AlipayRepaymentLog::findOne($id);
        $userCreditMoneyLog = UserCreditMoneyLog::findOne(['pay_order_id'=>$alipay_repayment_log->alipay_order_id]);
        if(!$alipay_repayment_log){
            return [
                'code' => -1,
                'msg'  => '记录不存在'
            ];
        }
        if($type == 'overflow_payment'){
            $overflow_payment = Yii::$app->request->post('overflow_payment');
            $alipay_repayment_log->overflow_payment = StringHelper::safeConvertCentToInt($overflow_payment);
            if ($userCreditMoneyLog) {
                $userCreditMoneyLog->operator_money = $alipay_repayment_log->money - $alipay_repayment_log->overflow_payment;
            }
        }elseif($type == 'remark'){
            $remark = Yii::$app->request->post('remark');
            $alipay_repayment_log->remark2 = $remark;
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $alipay_repayment_log_result = $alipay_repayment_log->save();
            if (!$alipay_repayment_log_result)  throw new Exception("AlipayRementLog保存失败!");
            if ($userCreditMoneyLog) {
                $userCreditMoneyLogResult = $userCreditMoneyLog->save();
                if (!$userCreditMoneyLogResult) throw new Exception("UserCreditMoneyLog保存失败!");
            }
            $transaction->commit();
        } catch(Exception $ex) {
            $transaction->rollBack();
            return [
                'code' => -1,
                'msg' => '更新失败'
            ];
        }
        return [
            'code' => 0,
            'msg' => 'success'
        ];
    }

    /**
     *
     * @return string
     * @name 财务管理-支付宝还款列表-重置支付宝还款记录状态为未处理/actionResetAlipayLog
     */
    public function actionResetAlipayLog($id)
    {
        $operatorUser = Yii::$app->user->identity->username;
        $alipay_repayment_log = AlipayRepaymentLog::findOne($id);
        $alipay_repayment_log->status = AlipayRepaymentLog::STATUS_ING;
        $alipay_repayment_log->operator_user = $operatorUser;
        $alipay_repayment_log->updated_at = time();
        if($alipay_repayment_log->save()){
            return $this->redirectMessage("操作成功", self::MSG_NORMAL);
        }else{
            return $this->redirectMessage("操作失败", self::MSG_ERROR);
        }

    }


    /**
     * @name 财务管理-退款列表
     */
    public function actionRefundList()
    {
        $condition = "1=1";
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $name = trim($search['id']);
                $condition .= " AND id='{$name}'";
            }
            if (!empty($search['in_pay_order'])) {
                $in_pay_order = trim($search['in_pay_order']);
                $condition .= " AND in_pay_order='{$in_pay_order}'";
            }
            if (!empty($search['out_pay_order'])) {
                $out_pay_order = trim($search['in_pay_order']);
                $condition .= " AND out_pay_order='{$out_pay_order}'";
            }
            if (!empty($search['account'])) {
                $account = trim($search['account']);
                $condition .= " AND account='{$account}'";
            }
            if (!empty($search['name'])) {
                $name = trim($search['name']);
                $condition .= " AND name='{$name}'";
            }
            if (isset($search['status']) && $search['status'] !=='') {
                $status = trim($search['status']);
                $condition .= " AND status='{$status}'";
            }
            if (!empty($search['in_type'])) {
                $in_type = trim($search['in_type']);
                $condition .= " AND in_type='{$in_type}'";
            }
            if (!empty($search['out_type'])) {
                $out_type = trim($search['out_type']);
                $condition .= " AND in_type='{$out_type}'";
            }
            //创建时间
            if (!empty($search['created_at_begin'])) {
                $condition .= " AND created_at >=".strtotime($search['created_at_begin']);
            }
            if (!empty($search['created_at_end'])) {
                $condition .= " AND created_at <".strtotime($search['created_at_end']);
            }
            //退款时间
            if (!empty($search['operation_time_begin'])) {
                $condition .= " AND operation_time >=".strtotime($search['operation_time_begin']);
            }

            if (!empty($search['operation_time_end'])) {
                $condition .= " AND operation_time <".strtotime($search['operation_time_begin']);
            }
        }
        $query = FinancialRefundLog::find()->where($condition)->orderBy(['id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('refund-list',['data'=>$data,'pages'=>$pages]);
    }

    /**
     * @name 财务管理-添加退款记录
     */
    public function actionAddRefund()
    {
        if(Yii::$app->request->isPost){
            $in_type = trim($this->request->post('in_type'));
            $in_pay_order = trim($this->request->post('in_pay_order'));
            $name = trim($this->request->post('name'));
            $account = trim($this->request->post('account'));
            $out_money = trim($this->request->post('out_money'));
            $remark = trim($this->request->post('remark'));
            $id = trim($this->request->post('id'));
            try{
                if(!in_array($in_type,array_keys(FinancialRefundLog::$type_list))){
                    throw new Exception('请选择入账类型');
                }
                if(empty($name)){
                    throw new Exception('请输入退款人姓名');
                }
                if(empty($account)){
                    throw new Exception('请输入退款人账号');
                }
                if(empty($remark)){
                    throw new Exception('请输入退款原因');
                }
                if(empty($in_pay_order)){
                    throw new Exception('请输入支付流水号');
                }
                if(empty($out_money) || !is_numeric($out_money)){
                    throw new Exception('请输入退款金额');
                }

                switch ($in_type){
                    case FinancialRefundLog::TYPE_BANK:
                        $log = UserCreditMoneyLog::find()->where(['pay_order_id'=>$in_pay_order,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->one();
                        if(is_null($log)){
                            throw new Exception('查不到支付流水号对应记录');
                        }
                        $money = $log->operator_money;
                        break;
                    case FinancialRefundLog::TYPE_YIMATONG:
                        $log = UserCreditMoneyLog::find()->where(['pay_order_id'=>$in_pay_order,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->one();
                        if(is_null($log)){
                            throw new Exception('查不到支付流水号对应记录');
                        }
                        $money = $log->operator_money;
                        break;
                    case FinancialRefundLog::TYPE_ALIPAY:
                        $log = AlipayRepaymentLog::find()->where(['alipay_order_id'=>$in_pay_order])->one();
                        if(is_null($log)){
                            throw new Exception('查不到支付流水号对应记录');
                        }
                        $money = $log->money;
                        break;
                    default:
                        throw new Exception('请选择入账类型');
                }
                $out_money = StringHelper::safeConvertCentToInt($out_money);
                if($out_money > $money){
                    throw new Exception('退款金额不能大于流水记录金额');
                }
                if ($id > 0) {
                    $refund_log = FinancialRefundLog::findOne(['id' => $id]);
                    $refund_log->name = $name;
                    $refund_log->in_type = $in_type;
                    $refund_log->in_pay_order = $in_pay_order;
                    $refund_log->in_money = $money;
                    $refund_log->out_money = $out_money;
                    $refund_log->account = $account;
                    $refund_log->remark = $remark;
                } else {
                    $refund_log = new FinancialRefundLog();
                    $refund_log->name = $name;
                    $refund_log->in_type = $in_type;
                    $refund_log->in_pay_order = $in_pay_order;
                    $refund_log->in_money = $money;
                    $refund_log->out_money = $out_money;
                    $refund_log->status = FinancialRefundLog::STATUS_APPLY;
                    $refund_log->account = $account;
                    $refund_log->remark .= $remark.';';
                    $refund_log->apply_username = Yii::$app->user->identity->username;
                }
                if(!$refund_log->save()){
                    if ($id > 0) {
                        throw new Exception('记录修改失败,请重试');
                    } else {
                        throw new Exception('记录创建失败,请重试');
                    }
                }else{
                    if ($id > 0) {
                        return $this->redirectMessage('修改成功',1,-2);
                    } else {
                        return $this->redirectMessage('创建成功',1,-2);
                    }
                }
            }catch (\Exception $e){
                return $this->redirectMessage($e->getMessage(),2);
            }

        }
        $refundLog = null;$id = 0;
        if (Yii::$app->request->isGet) {
            $id = $this->request->get('id');
            if ($id > 0) {
                $refundLog = FinancialRefundLog::findOne(['id' => $id]);
            }
        }
        return $this->render('add-refund',[
            'model' => $refundLog,
            'id' => $id
        ]);
    }


    /**
     * @name 财务管理-退款审核
     */
    public function actionAuditRefund()
    {
        $id = intval($this->request->post('id',''));
        $status = intval($this->request->post('status',''));
        $remark = intval($this->request->post('remark',''));
        try {
            $financialRefundLog = FinancialRefundLog::findOne(['id' => $id]);
            if (!$financialRefundLog) {throw new Exception("未找到相关记录!");}
            if (!in_array($status,[FinancialRefundLog::STATUS_REJECT,FinancialRefundLog::STATUS_REFUNDING])) {
                throw new Exception("状态不合法!");
            }
            $pay_order_id = $financialRefundLog->in_pay_order;
            $financialRefundLog -> status = $status;
            $financialRefundLog -> remark_2 .= $remark.';';
            $financialRefundLog -> audit_username = Yii::$app->user->identity->username;
            $financialRefundLog -> updated_at = time();
            if ($financialRefundLog -> save()) {
                //流水表
                $money_log = UserCreditMoneyLog::find()->where(['pay_order_id'=>$pay_order_id,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->one(\yii::$app->db_kdkj_rd_new);
                if($money_log){
                    $order_id = $money_log->order_id;
                    //还款记录
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id'=>$order_id])->one(\yii::$app->db_kdkj_rd_new);
                    //修改还款表tb_user_loan_order_repayment已还款金额（true_total_money）
                    $true_total_money=$user_loan_order_repayment -> true_total_money - $financialRefundLog -> out_money;
                    $user_loan_order_repayment -> true_total_money = $true_total_money;
                    $user_loan_order_repayment->save();
                }

                return $this->redirectMessage('审核成功!',1,-2);
            } else {
                return $this->redirectMessage('审核失败!');
            }
        } catch (Exception $ex) {
            return $this->redirectMessage($ex->getMessage(),2);
        }
    }

    /**
     * @name 财务管理-退款详情
     */
    public function actionRefundDetail(){
        $id = intval(Yii::$app->request->get('id'));
        $model = FinancialRefundLog::find()->select(['in_type','in_pay_order'])->where(['id'=>$id])->one(\yii::$app->db_kdkj_rd_new);
        if(is_null($model)){
            return $this->redirectMessage('数据不存在', self::MSG_ERROR);
        }
        $pay_order_id = $model->in_pay_order;
        //流水表
        $money_log = UserCreditMoneyLog::find()->where(['pay_order_id'=>$pay_order_id,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->one(\yii::$app->db_kdkj_rd_new);
        if(is_null($money_log)){
            return $this->redirectMessage('该流水号未入账', self::MSG_ERROR);
        }

        $order_id = $money_log->order_id;
        //该订单下所有流水记录
        $logs = UserCreditMoneyLog::find()->where(['order_id'=>$order_id,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->all(\yii::$app->db_kdkj_rd_new);
        //打款记录
        $financial_loan_record = FinancialLoanRecord::find()->where(['business_id'=>$order_id])->one(\yii::$app->db_kdkj_rd_new);
        //还款记录
        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id'=>$order_id])->one(\yii::$app->db_kdkj_rd_new);

        //查询该订单下所有退款成功记录
        $financialRefundLog = FinancialRefundLog::find()->where(['in_pay_order'=>$pay_order_id,'status'=>FinancialRefundLog::STATUS_COMPLETE])->all(\yii::$app->db_kdkj_rd_new);

        return $this->render('refund-detail',[
            'logs' => $logs,
            'financial_loan_record' => $financial_loan_record,
            'user_loan_order_repayment' => $user_loan_order_repayment,
            'financialRefundLog' => $financialRefundLog
        ]);


    }
    /**
     * @name 财务管理-退款填写
     */
    public function actionSetRefund()
    {
        if (Yii::$app->request->isPost) {
            $id = intval($this->request->post('id',''));
            $outPayOrder = $this->request->post('out_pay_order','');
            $outType = $this->request->post('out_type','');
            try {
                if (!$outPayOrder) {
                    throw new Exception("退款订单号不能为空!");
                }
                if (in_array($outType,FinancialRefundLog::$type_list)) {
                    throw new Exception("退类型不合法!");
                }
                $financialRefundLog = FinancialRefundLog::findOne(['id' => $id]);
                if (!$financialRefundLog) {
                    throw new Exception("未找到相关记录!");
                }
                $financialRefundLog -> status = FinancialRefundLog::STATUS_COMPLETE;
                $financialRefundLog -> out_pay_order= $outPayOrder;
                $financialRefundLog -> out_type= $outType;
                $financialRefundLog -> audit_username = Yii::$app->user->identity->username;
                $financialRefundLog -> remark_2 .= '<br/>'.Yii::$app->user->identity->username.'置为已退款;';
                $financialRefundLog -> operation_time = time();
                $financialRefundLog -> updated_at = time();
                if ($financialRefundLog -> save()) {
                    return $this->redirectMessage('审核成功!');
                } else {
                    return $this->redirectMessage('审核失败!');
                }
            } catch(Exception $ex) {
                return $this->redirectMessage($ex->getMessage(),2);
            }
        } else {
            $id = intval($this->request->get('id',''));
            $refundLog = FinancialRefundLog::findOne(['id' => $id]);
            return $this->render('set-refund',[
                'model' => $refundLog,
                'id' => $id
            ]);
        }
    }
}
