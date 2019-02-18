<?php

namespace backend\controllers;

use Yii;
use common\models\fund\LoanFund;
use yii\data\ActiveDataProvider;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\fund\OrderFundInfo;
use common\models\UserLoanOrder;
use common\models\fund\OrderFundLog;
use common\models\fund\LoanFundDayQuota;
use common\models\fund\LoanFundSignUser;
use common\services\FundService;
use yii\data\Pagination;
use common\models\LoanPerson;
use common\models\FinancialLoanRecord;
use common\models\fund\FundAccount;
use common\models\FinancialDebitRecord;
use common\models\UserCreditMoneyLog;
use credit\components\ApiUrl;
use common\helpers\StringHelper;
use common\models\UserLoanOrderRepayment;
use common\models\fund\LoanFundDayPreQuota;

/**
 * 借款资金管理
 */
class LoanFundController extends BaseController
{

    /**
     * 过滤条件
     * @return string
     */
    public function getFilter(){
        //$condition = '1 = 1 and a.order_type='.UserLoanOrder::LOAN_TYPE_LQD;
        $condition = [];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['fund_id']) && !empty($search['fund_id'])) {
                $condition[] = " a.fund_id = " . intval($search['fund_id']). ' AND c.fund_id='. intval($search['fund_id']);
            }
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition[] = " a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition[] = " d.name like '%" . $search['name']."%'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition[] = " d.phone = '" . (float)$search['phone']."'";
            }

            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition[] = " a.order_id = " . (int)$search['order_id'];
            }

            if (!empty($search['fund_order_id'])) {
                $condition[] = " a.fund_order_id = " . Yii::$app->db->quoteValue(trim($search['fund_order_id']));
            }

            if (isset($search['status'])&&(UserLoanOrder::STATUS_ALL != $search['status'])) {
                if($search['status'] == 10000) {
                    $condition[] = " (c.status = " . UserLoanOrder::STATUS_CANCEL . ' OR c.status = ' . UserLoanOrder::STATUS_REPEAT_CANCEL . ') AND c.is_hit_risk_rule != 1 ';
                }elseif ($search['status'] == 10001){
                    $condition[] = " c.status = " . UserLoanOrder::STATUS_CANCEL . ' AND c.is_hit_risk_rule = 1 ';

                }else{
                    $condition[] = " c.status = " . intval($search['status']);
                }

            }

            if (!empty($search['info_status'])) {
                if($search['info_status'] == 10000) {//所有状态

                }elseif ($search['info_status'] == 10001){//所有有效状态
                    $condition[] = " a.status>=0  ";
                }else{
                    $condition[] = " a.status = " . intval($search['info_status']);
                }

            }

            if (isset($search['begintime'])&&!empty($search['begintime'])) {
                $condition[] = " a.created_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime'])&&!empty($search['endtime'])) {
                $condition[] = " a.created_at <= " . strtotime($search['endtime']);
            }

            if (isset($search['pay_begintime'])&&!empty($search['pay_begintime'])) {
                $condition[] = " e.success_time >= " . strtotime($search['pay_begintime']);
            }
            if (isset($search['pay_endtime'])&&!empty($search['pay_endtime'])) {
                $condition[] = " e.success_time <= " . strtotime($search['pay_endtime']);
            }
        }
        return implode(' AND ', $condition);
    }

    /**
     * @name 资方列表
     * Lists all LoanFund models.
     * @ name 资方管理-资方列表/actionIndex
     * @return mixed
     */
    public function actionIndex()
    {

        $dataProvider = new ActiveDataProvider([
            'query' => LoanFund::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @name 每日限额列表
     */
    public function actionDayQuotaList() {
        $day_quota_table = LoanFundDayQuota::tableName();
        $fund_table = LoanFund::tableName();

        $db = LoanFundDayQuota::getDb();
        $count = $db->createCommand("SELECT COUNT(*) FROM {$day_quota_table}")->queryScalar();
        $pagination = new \yii\data\Pagination([
            'totalCount'=>$count
        ]);

        $sql = "SELECT a.*,b.`name` FROM {$day_quota_table} a LEFT JOIN {$fund_table} b ON a.fund_id=b.id ORDER BY id DESC LIMIT {$pagination->getOffset()},{$pagination->getLimit()}";
        $rows = $db->createCommand($sql)->queryAll();

        return $this->render('day-quota-list', [
            'rows' => $rows,
            'pagination'=>$pagination
        ]);
    }

    /**
     * @name 更新每日配额
     */
    public function actionUpdateDayQuota($id, $return_url=null) {
        $model = LoanFundDayQuota::findOne((int)$id);
        if($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            if($return_url) {
                return $this->redirect($return_url);
            } else {
                return $this->redirect(['day-quota-list']);
            }
        }
        $funds = LoanFund::getAll();
        $fund_options = [];
        foreach($funds as $fund) {
            /* @var $fund LoanFund */
            $fund_options[$fund->id] = $fund->name;
        }
        return $this->render('update-day-quota',[
            'model'=>$model,
            'fund_options'=>$fund_options
        ]);
    }

     /**
     * @name 更新每日配额
     */
    public function actionAddDayQuota($return_url=null) {
        $model = new LoanFundDayQuota();
        if($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            if($return_url) {
                return $this->redirect($return_url);
            } else {
                return $this->redirect(['day-quota-list']);
            }
        }
        $funds = LoanFund::getAll();
        $fund_options = [];
        foreach($funds as $fund) {
            $fund_options[$fund->id] = $fund->name;
        }
        return $this->render('update-day-quota',[
            'model'=>$model,
            'fund_options'=>$fund_options
        ]);
    }

    /**
     * @name 资方订单信息列表
     */
    public function actionOrderInfoList() {

        $condition = self::getFilter();
        //print_r($condition);exit;
        $db = Yii::$app->get('db_kdkj_rd');

        $info_table = OrderFundInfo::tableName();
        $fund_table = LoanFund::tableName();
        $order_table = UserLoanOrder::tableName();
        $order_repayment_table = UserLoanOrderRepayment::tableName();
        $user_table = LoanPerson::tableName();
        $financial_table = FinancialLoanRecord::tableName();

        $query = OrderFundInfo::find()->from($info_table. ' as a ')
            ->leftJoin($fund_table .' as b', 'a.fund_id=b.id')
            ->leftJoin($order_table .' as c','a.order_id=c.id')
            ->leftJoin($user_table .' as d', 'a.user_id = d.id')
            ->leftJoin($financial_table.' as e', ' (e.user_id=a.user_id AND a.order_id=e.business_id )')
            ->leftJoin($order_repayment_table.' as f', 'a.order_id=f.order_id')
            //->leftJoin(UserCreditMoneyLog::tableName().' as g', '(g.user_id=a.user_id AND g.order_id = a.order_id)')

            ->select('a.*,b.`name`,'
                . 'c.money_amount,c.counter_fee as total_fee,c.status as order_status, c.loan_term,'
                . 'e.success_time as pay_time, e.money as pay_money,e.order_id as bus_order_id, f.late_fee, f.coupon_money, f.true_total_money');



        if($condition) {
            $query->where($condition);
            $countQuery = clone $query;
            $count = $countQuery->count('*', $db);
        } else {
            $countQuery = clone $query;
            $count = OrderFundInfo::find()->count('*', $db);
        }

        //统计金额
        if($this->request->get('is_summary')==1)
        {
            $dataSt = $countQuery
            ->select('SUM(c.money_amount) as sum_money_amount,sum(c.counter_fee) as sum_total_fee,sum(a.service_fee) as sum_service_fee,sum(a.interest) as sum_interest,
                sum(a.deposit) as sum_deposit,sum(a.fund_service_fee) as sum_fund_service_fee,sum(a.credit_verification_fee) as sum_credit_verification_fee,sum(a.renew_fee) as sum_renew_fee,
                sum(a.renew_service_fee) as sum_renew_service_fee,sum(a.prepay_amount) as sum_prepay_amount')
            ->asArray()->one($db);
        }else{
            $dataSt = [];
        }

        if($this->request->get('submitcsv') == 'exportcsv'){//导出
            set_time_limit(0);
            $pay_accounts = FundAccount::getSelectOptions(FundAccount::TYPE_PAY);
            $repay_accounts = FundAccount::getSelectOptions(FundAccount::TYPE_REPAY);

            $dir = Yii::$app->getRuntimePath().'/tmp/';

            !file_exists($dir) && mkdir($dir);

            $dirFile = $dir."资方关联信息报表.xls";

            $i = 0;
            $str = "id\t资方\t订单ID\t订单状态\t资方订单ID\t状态\t创建时间\t借款期限\t本金\t所有费用\t我方服务费\t";
            $str .="利息\t保证金\t资方服务费\t凌融服务费\t征信费\t逾期利息\t逾期服务费\t续期手续费\t续期服务费\t用户还款金额\t";
            $str .="抵扣券\t垫付金额\t结算状态\t结算类型\t放款主体\t还款主体\t打款订单号\n";

            $myfile = fopen($dirFile, "w") or die("Unable to open file!");

            do {

               $datas = $query->orderBy(['id'=>SORT_DESC])->asArray()->offset($i*5000)->limit(5000)->all($db);

               if($datas) {
                   $i++;
                   foreach($datas as $key=>$value){

                       $order_status = isset(UserLoanOrder::$status[$value['order_status']])?UserLoanOrder::$status[$value['order_status']]:'未知状态'.$value['order_status'];
                       $status = \common\models\fund\OrderFundInfo::STATUS_LIST[$value['status']];
                       $created_at = date('Y-m-d H:i:s', $value['created_at']);
                       $money_amount = sprintf('%0.2f',$value['money_amount']/100);
                       $total_fee = sprintf("%0.2f",$value['total_fee']/100);
                       $service_fee = sprintf("%0.2f",$value['service_fee']/100);
                       $interest = sprintf("%0.2f",$value['interest']/100);
                       $deposit = sprintf("%0.2f",$value['deposit']/100);
                       $fund_service_fee = sprintf("%0.2f",$value['fund_service_fee']/100);
                       $credit_verification_fee = sprintf("%0.2f",$value['credit_verification_fee']/100);
                       $overdue_fee = sprintf("%0.2f",$value['overdue_fee']/100);
                       $overdue_interest = sprintf("%0.2f",$value['overdue_interest']/100);
                       $renew_fee = sprintf("%0.2f",$value['renew_fee']/100);
                       $renew_service_fee = sprintf("%0.2f",$value['renew_service_fee']/100);
                       $user_repay_amount = sprintf("%0.2f",$value['user_repay_amount']/100);
                       $coupon_money = sprintf("%0.2f",$value['coupon_money']/100);
                       // $true_repay_amount = sprintf("%0.2f",($value['true_total_money']-$value['coupon_money'])/100);
                       $prepay_amount = sprintf("%0.2f",$value['prepay_amount']/100);

                       $settlement_status = OrderFundInfo::SETTLEMENT_STATUS_LIST[$value['settlement_status']];
                       $settlement_type = !empty(OrderFundInfo::SETTLEMENT_TYPE_LIST[$value['settlement_type']])?OrderFundInfo::SETTLEMENT_TYPE_LIST[$value['settlement_type']]:0;
                       $pay_account_id = $value['pay_account_id']?$pay_accounts[$value['pay_account_id']]:'无';
                       $repay_account_id =!empty($repay_accounts[$value['repay_account_id']])?$repay_accounts[$value['repay_account_id']]:'无';
                       $qinanChengFee = sprintf("%0.2f",($value['total_fee']-$value['interest']-$value['fund_service_fee'])/100);//凌融服务费=所有费用-利息-资方服务费

                       $str .= "{$value['id']}\t" ."{$value['name']}\t" ."{$value['order_id']}\t" ."{$order_status} \t" ."{$value['fund_order_id']}\t" ."{$status}\t" ."{$created_at}\t{$value['loan_term']}\t{$money_amount}\t" ."{$total_fee}\t" ."{$service_fee}\t" .
                            "{$interest}\t" ."{$deposit}\t" ."{$fund_service_fee}\t" ."{$qinanChengFee}\t"."{$credit_verification_fee}\t" ."{$overdue_fee}\t" ."{$overdue_interest}\t" ."{$renew_fee}\t" ."{$renew_service_fee}\t" ."{$user_repay_amount}\t" .
                            "{$coupon_money}\t".
                            // "{$true_repay_amount}\t".
                            "{$prepay_amount}\t" ."{$settlement_status}\t" ."{$settlement_type}\t" ."{$pay_account_id}\t" ."{$repay_account_id}\n";
                   }

                   $str = mb_convert_encoding($str, 'gbk', 'utf-8');
                   fwrite($myfile, $str);

                   $str = '';
               }else{
                    goto SHOW_RET;
               }

            }while(true);

            SHOW_RET:
            fclose($myfile);

            yii::$app->response->sendFile($dirFile);
            Yii::$app->end();

        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $data = $query->orderBy(['id'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('order-info-list', [
            'dataSt' =>$dataSt,
            'rows' => $data,
            'pagination'=>$pages
        ]);
    }

     /**
     * @name 日志列表
     */
    public function actionLogList() {

        $log_table = OrderFundLog::tableName();
        $fund_table = LoanFund::tableName();
        $order_table = UserLoanOrder::tableName();
        $db = OrderFundInfo::getDb();
        $count = $db->createCommand("SELECT COUNT(*) FROM {$log_table}")->queryScalar();
        $pagination = new \yii\data\Pagination([
            'totalCount'=>$count
        ]);

        $sql = "SELECT a.*,b.`name` FROM {$log_table} a LEFT JOIN {$fund_table} b ON a.fund_id=b.id ORDER BY id DESC LIMIT {$pagination->getOffset()},{$pagination->getLimit()}";
        $rows = $db->createCommand($sql)->queryAll();

        return $this->render('log-list', [
            'rows' => $rows,
            'pagination'=>$pagination
        ]);
    }

    /**
     * @name 切换资方
     * @method GET
     * @param string $key 请求key
     * @param integer $seri_no 序列号ID
     * @param string $captcha 验证码
     * @return {"code":0,"data":{}}
     */
    public function actionSwitchFund($order_id, $return_url=null) {
        $order_id = (int)$order_id;
        $order = UserLoanOrder::findOne($order_id);
        $return_url = $return_url ? $return_url : Url::toRoute(['order-info-list']);
        if(!$order) {
            return $this->redirectMessage('找不到订单',self::MSG_ERROR,$return_url);
        }
        if (!$order->cardInfo) {
            return $this->redirectMessage('找不到订单绑定的银行卡',self::MSG_ERROR,$return_url);
        }
         if (!$order->loanFund) {
            return $this->redirectMessage('该订单没有关联资方，请调用设置资方接口',self::MSG_ERROR,$return_url);
        }
        if (!in_array($order->status, UserLoanOrder::$allow_switch_fund_status)) {
            return $this->redirectMessage('订单不处于可切换状态，操作失败',self::MSG_ERROR,$return_url);
        }

        $funds = LoanFund::getAll(LoanFund::STATUS_ENABLE);
        $fund_service = Yii::$container->get('fundService');
        /* @var $fund_service FundService */
        $ret = $fund_service->orderAutoSwitchFund($order, $funds, $order->user_id);

        if($ret['code']==0) {
            return $this->redirectMessage('切换成功',self::MSG_SUCCESS, $return_url);
        } else {
            return $this->redirectMessage($ret['message'],self::MSG_ERROR,$return_url);
        }
    }

    /**
     * @name 查看资方
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @name 创建资方
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new LoanFund();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * @name 更新资方
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Finds the LoanFund model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return LoanFund the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LoanFund::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @name 推送订单
     * @param integer $id
     */
    public function actionPushOrder($id) {
        $id = (int)$id;
        $order = UserLoanOrder::findOne($id);
        $ret = $order->loanFund->getService()->pushOrder($order);
        echo json_encode($ret,JSON_UNESCAPED_UNICODE);
    }

    private function getCalculateFilter(){
        if ($this->getRequest()->getIsGet()) {
            $condition =[];
            $search = $this->request->get();
            if (isset($search['fund_id']) && !empty($search['fund_id'])) {
                $condition[] = " fund_id = " . (int)$search['fund_id'];
            }

            if (isset($search['date']) && !empty($search['date'])) {
                $condition[] = " a.date = '" . trim($search['date'])."'";
            }
            return implode(" AND ",  $condition);
        }

    }



    /**
     *
     * @name 预留额度列表页
     *
     * @author czd
     *
     */
    public function actionReservedList(){

        $condition = '';
        $db = Yii::$app->get('db_kdkj_rd');
        $query = LoanFundDayPreQuota::find()->from(LoanFundDayPreQuota::tableName() .' as a')->select(['a.*']);

        if($condition) {
            $query->where($condition);
            $countQuery = clone $query;
            $count = $countQuery->count('*', $db);
        } else {
            $count = LoanFundDayPreQuota::find()->count('*', $db);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 10;
        $data = $query->orderBy(['id'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);

        return $this->render('reserved-list', [
            'rows' => $data,
            'pagination'=>$pages
        ]);


    }

    /**
     * @name  预留额度添加
     *
     * @author czd
     */
    public function actionLoanFundDayQuotaCreat()
    {
        $model = new LoanFundDayPreQuota();

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {

                $model->save();
                return $this->redirect(['reserved-list']);
                // form inputs are valid, do something here
                return;
            }
        }

        return $this->render('_loan_fund_day_quota_form', [
            'model' => $model,
        ]);
    }

    /**
     * @name  预留额度更新
     *
     * @author czd
     */
    public function actionLoanFundDayQuotaUpdate($id)
    {
        $model =  LoanFundDayPreQuota::findOne($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {

                $model->save();
                return $this->redirect(['reserved-list']);
                // form inputs are valid, do something here
                return;
            }
        }

        return $this->render('_loan_fund_day_quota_form', [
            'model' => $model,
        ]);
    }




}
