<?php
namespace backend\controllers;

use common\models\CardInfo;
use common\models\CreditJxlQueue;
use common\models\LoanPersonBadInfo;
use common\models\UserCreditData;
use common\models\UserCreditLog;
use common\models\LoanPerson;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\UserProofMateria;
use common\models\UserVerification;
use common\services\MessageService;
use backend\models\AdminUser;
use Yii;
use common\api\RedisQueue;
use yii\base\Exception;
use yii\data\Pagination;
use common\helpers\Url;
use yii\web\Response;
use common\models\fund\LoanFund;
use common\services\OrderService;
use common\helpers\ArrayHelper;
use common\helpers\Util;
use common\models\mongo\risk\OrderReportMongo;
use backend\models\ManualOrderDispatch;
use common\helpers\StringHelper;
use common\models\AutoDebitLog;
use common\services\LoanService;
use common\models\UserQuotaPersonInfo;
use common\models\UserLoginUploadLog;
use yii\web\NotFoundHttpException;

class PocketController extends BaseController {

    //零钱贷借款列表过滤
    public function getFilter($default_status = -100) {

        $condition = 'userLoanOrder.order_type = ' . UserLoanOrder::LOAN_TYPE_LQD;
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            $search['status'] = isset($search['status']) ? $search['status'] : $default_status;
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND userLoanOrder.id = " . intval($search['id']);
            }
            if (isset($search['uid']) && !empty($search['uid'])) {
                $condition .= " AND userLoanOrder.user_id = " . intval($search['uid']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND loanPerson.name = '" . $search['name']."'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND loanPerson.phone = '" . $search['phone']."'";
            }
            if (isset($search['company_name']) && !empty($search['company_name'])) {
                $condition .= " AND userDetail.company_name =  '" . $search['company_name']."'";
            }
            if (isset($search['sub_order_type']) && $search['sub_order_type'] != -1) {
                $condition .= " AND userLoanOrder.sub_order_type = " . $search['sub_order_type'];
            }
            if (isset($search['card_type']) && $search['card_type'] != -1) {
                $condition .= " AND userLoanOrder.card_type = " . $search['card_type'];
            }
            if (isset($search['old_user']) && !empty($search['old_user'])) {
                if ($search['old_user'] == 1){
                    $condition .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                } elseif ($search['old_user'] == -1){
                    $condition .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                }
            }
            if (isset($search['status']) && (UserLoanOrder::STATUS_ALL != $search['status'])) {
                if($search['status'] == 10000) {
                    $condition .= " AND (userLoanOrder.status = " . UserLoanOrder::STATUS_CANCEL . ' OR userLoanOrder.status = ' . UserLoanOrder::STATUS_REPEAT_CANCEL . ') AND userLoanOrder.is_hit_risk_rule != 1 ';
                }elseif ($search['status'] == 10001){
                    $condition .= " AND userLoanOrder.status = " . UserLoanOrder::STATUS_CANCEL . ' AND userLoanOrder.is_hit_risk_rule = 1 ';
                }elseif ($search['status'] == -1000) {
                    $condition .= " AND userLoanOrder.status != " . UserLoanOrder::STATUS_REPAY_COMPLETE . ' AND userLoanOrder.status != ' . UserLoanOrder::STATUS_PARTIALREPAYMENT;
                }elseif(in_array($search['status'], [UserLoanOrder::STATUS_PERSON, UserLoanOrder::STATUS_MACHINE])){
                    $condition .= " AND userLoanOrder.status_type = " . intval($search['status']);
                }else{
                    $condition .= " AND userLoanOrder.status = " . intval($search['status']);
                }
            }
            if (isset($search['loan_term']) && !empty($search['loan_term'])) {
                $condition .= " and  userLoanOrder.`loan_term` = ".$search['loan_term'];
            }
            if (isset($search['plan_fee_time']) && !empty($search['plan_fee_time'])) {
                $plan_fee_time_s = strtotime($search['plan_fee_time']);
                $plan_fee_time_e = strtotime($search['plan_fee_time'])+86400;
                $condition .= " and  userLoanOrderRepayment.`plan_fee_time` >= ".$plan_fee_time_s." and userLoanOrderRepayment.`plan_fee_time` < ".$plan_fee_time_e;
            }
            if (isset($search['overdue_day']) && !empty($search['overdue_day'])) {
                $condition .= " and  userLoanOrderRepayment.`overdue_day` > 0 ";
            }
            if (isset($search['re_status']) && !empty($search['re_status'])) {
                $condition .= " and  userLoanOrderRepayment.`status` != 4";
            }
            if (isset($search['_status']) && !empty($search['_status'])) {
                $condition .= " and  userLoanOrder.`status` = 6";
            }
            if (isset($search['amount_min']) && !empty($search['amount_min'])) {
                $condition .= " AND userLoanOrder.money_amount >= " . \intval($search['amount_min']*100);
            }
            if (isset($search['amount_max']) && !empty($search['amount_max'])) {
                $condition .= " AND userLoanOrder.money_amount <= " . \intval($search['amount_max']*100);
            }
            if (isset($search['begintime'])&&!empty($search['begintime'])) {//申请时间
                $condition .= " AND userLoanOrder.order_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime'])&&!empty($search['endtime'])) {//申请时间
                $condition .= " AND userLoanOrder.order_time <= " . strtotime($search['endtime']);
            }
            if (isset($search['begintime2'])&&!empty($search['begintime2'])) {//放款时间
                $condition .= " AND userLoanOrderRepayment.created_at >= " . strtotime($search['begintime2']);
            }
            if (isset($search['endtime2'])&&!empty($search['endtime2'])) {//放款时间
                $condition .= " AND userLoanOrderRepayment.created_at <= " . strtotime($search['endtime2']);
            }
        }
        return $condition;
    }

    /**
     * @name 借款管理-用户借款管理-借款列表/actionPocketList
     *
     */
    public function actionPocketList(){
        Util::cliLimitChange(1024);
        /* @var $db \yii\db\Connection */
        $db = Yii::$app->get('db_kdkj_rd');
        $condition = self::getFilter();
        $channel = $this->request->get('channel');
        $begintime = $this->request->get('time');
        $status = $this->request->get('status');
        $old_user = $this->request->get('old_user');
        $plan_fee_time = $this->request->get('plan_fee_time');
        $_status = $this->request->get('_status');
        $overdue_day = $this->request->get('overdue_day');
        $source_id = $this->request->get('source_id');

        new LoanPerson();
        $num = [];
        $condition1 = $condition2 = $condition3 = $condition;
        if(!is_null($source_id) && $source_id != '0' && in_array($source_id,array_keys(LoanPerson::$current_loan_source))){
            $condition .= " and loanPerson.source_id=".$source_id;
            $condition2 .= " and loanPerson.source_id=".$source_id;
            $condition3 .= " and loanPerson.source_id=".$source_id;
        }
        if (isset($begintime) && !empty($begintime)) {
            $begintime = strtotime($begintime);
            $endtime = $begintime + 86400;
            if(isset($old_user)&&!empty($old_user)){
                if($old_user == 1){
                    $condition1 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                    $condition2 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                    $condition3 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;

                }elseif ($old_user == -1){
                    $condition1 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                    $condition2 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                    $condition3 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                }
            }
            $condition .= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime";
            $condition1.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
            $condition2.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
            $condition3.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
        }
        else {
            if (isset($plan_fee_time)&&!empty($plan_fee_time)) {
                $begintime = strtotime($plan_fee_time)-14*86400;
            }
            if (isset($status)&&!empty($status)) {
                if($status == UserLoanOrder::STATUS_ALL){//全部
                    $condition1.= " and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                }elseif ($status == UserLoanOrder::STATUS_PARTIALREPAYMENT){//部分还款
                    $condition1.= " and userLoanOrder.status = 55"." and ".$condition;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status = 55";
                }elseif($status == UserLoanOrder::STATUS_REPAY_COMPLETE) {//已还款
                    $condition1 .= " and userLoanOrder.status = " . UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2 .= " and userLoanOrder.status = 66";
                    $condition3 .= " and userLoanOrder.status = 66";
                }elseif(in_array($status, [UserLoanOrder::STATUS_MACHINE, UserLoanOrder::STATUS_PERSON])){
                    $condition1 .= "and userLoanOrder.status_type = ".$status;
                    $condition2 .= "and userLoanOrder.status_type = ".$status;
                    $condition3 .= "and userLoanOrder.status_type = ".$status;
                }else{
                    $condition1.= " AND userLoanOrder.status = 1000";
                    $condition2.= " AND userLoanOrder.status = 1000";
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                }
            }else{
                if(isset($plan_fee_time)&&!empty($plan_fee_time)){//到期单数
                    $start_time = strtotime($plan_fee_time);
                    $end_time = $start_time+86400;
                    $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time";
                    $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    if(isset($_status)&&!empty($_status)){
                        $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status = 4";
                        $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status = 4";
                        $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status = 44";
                        $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status = 44";
                    }
                    if (isset($overdue_day) &&! empty($overdue_day)) {
                        $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                    }
                } else {
                    $condition1.= " and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=" . UserLoanOrder::STATUS_REPAY_COMPLETE;
                }
            }
        }
        // 查公积金
        if (!empty($this->request->get('is_gjj'))) {
            $condition.= " AND (userLoanOrder.pass_type = ".UserLoanOrder::PASS_TYPE_GJJ." OR userLoanOrder.pass_type = ".UserLoanOrder::PASS_TYPE_GJJ_OLD.")";
        }

        if(($this->request->get('is_white'))==1){
            $condition.=" AND (creditJsqb.is_white=".UserLoanOrder::CUSTOMER_TYPE_NEW_YES.")";
        }else if(($this->request->get('is_white'))==2){
            $condition.=" AND (creditJsqb.is_white=".UserLoanOrder::CUSTOMER_TYPE_NEW_NO." or creditJsqb.is_white is null)";
        }

        //总单数
//        $query = UserLoanOrder::find()
//            ->where($condition)
//            ->joinWith(['userLoanOrderRepayment', 'loanPerson', 'userDetail', 'loanFund']);
//        $countQuery = clone $query;

        $query = UserLoanOrder::find()
            ->where($condition)
            ->joinWith(['userLoanOrderRepayment', 'loanPerson', 'userDetail', 'loanFund','creditJsqb']);
        $countQuery = clone $query;

//        $count = 9999999;
        //禁用count，写死个值
        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
        }, 3600);
//       $num['count'] = $count;
        $num = ['count' => 0, 'repay_num' => 0, 'part_num' => 0, 'other' => 0];
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);

        if($this->request->get('submitcsv') == 'exportcsv'){//数据导出
            $data = $query
                ->select('userLoanOrder.*,loanPerson.name,loanPerson.id_number,loanPerson.source_id,loanPerson.phone,loanPerson.customer_type,userDetail.company_name,userDetail.reg_app_market,loanFund.name as fund_name,userLoanOrderRepayment.true_repayment_time,userLoanOrderRepayment.created_at as re_created_at,userLoanOrderRepayment.coupon_money as re_coupon_money')
                ->orderBy(['userLoanOrder.id' => SORT_DESC])
                ->asArray()
                ->all($db);
        }else{
            $data = $query
                ->select('userLoanOrder.*,loanPerson.name,loanPerson.id_number,loanPerson.source_id,loanPerson.phone,loanPerson.customer_type,userDetail.company_name,userDetail.reg_app_market,loanFund.name as fund_name,userLoanOrderRepayment.true_repayment_time,userLoanOrderRepayment.created_at as re_created_at,userLoanOrderRepayment.coupon_money as re_coupon_money')
                ->offset($pages->offset)
                ->limit($pages->limit)
                ->orderBy(['userLoanOrder.id' => SORT_DESC])//->createCommand()->sql;
                ->asArray()
                ->all($db);
//            echo $data;die;
        }
        $order_ids = ArrayHelper::getColumn($data, 'id');
        $user_order_loan_check_log = UserOrderLoanCheckLog::find()
            ->where([
                'order_id' => $order_ids,
                'before_status' => 0,
                'after_status' => 0,
            ])
            ->asArray()
            ->all();
        $check_data = [];
        foreach($user_order_loan_check_log as $item) {
            $check_data[$item['order_id']] = $item['order_id'];
        }

        $status_data = [];
        foreach ($data as $item) {
            $status = $item['status'];
            if (UserLoanOrder::STATUS_CHECK == $status) {
                if (isset($check_data[$item['id']])) {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."转人工";
                } else {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."机审";
                }
            }
            else {
                $_status = (isset($item['status_type']) && $item['status_type'] > 0) ? $item['status_type'] : $item['status'];
                $status_data[$item['id']] = isset(UserLoanOrder::$status[$_status]) ? UserLoanOrder::$status[$_status] : '';
            }
        }

        //导出数据
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportPocketData($data, $status_data);
        }

        return $this->render('pocket-list', array(
            'data_list' => $data,
            'status_data'=>$status_data,
            'pages' => $pages,
            'channel'=>$channel,
            'begintime'=>$begintime,
            'num'=>$num
        ));
    }

    /**
     * @name 借款管理-用户借款管理-借款初审拒绝列表/actionPocketRejectList
     *
     */
    public function actionPocketRejectList(){
        /* @var $db \yii\db\Connection */
        $db = Yii::$app->get('db_kdkj_rd');
        $condition = self::getFilter(-3);
        $channel = $this->request->get('channel');
        $begintime = $this->request->get('time');
        $status = $this->request->get('status', -3);
        $old_user = $this->request->get('old_user');
        $plan_fee_time = $this->request->get('plan_fee_time');
        $_status = $this->request->get('_status');
        $overdue_day = $this->request->get('overdue_day');
        $num = [];

        $condition1 = $condition2 = $condition3 = $condition;

        if (isset($begintime) && !empty($begintime)) {
            $endtime = $begintime + 86400;
            if(isset($old_user)&&!empty($old_user)){
                if($old_user == 1){
                    $condition1 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                    $condition2 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                    $condition3 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;

                }elseif ($old_user == -1){
                    $condition1 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                    $condition2 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                    $condition3 .= " and loanPerson.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                }
            }
            $condition .= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime";
            $condition1.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
            $condition2.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
            $condition3.= " and userLoanOrderRepayment.`created_at` >= $begintime and userLoanOrderRepayment.`created_at` < $endtime and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
        }
        else {
            if (isset($plan_fee_time)&&!empty($plan_fee_time)) {
                $begintime = strtotime($plan_fee_time)-14*86400;
            }
            if (isset($status)&&!empty($status)) {
                if($status == UserLoanOrder::STATUS_ALL){//全部
                    $condition1.= " and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                }elseif ($status == UserLoanOrder::STATUS_PARTIALREPAYMENT){//部分还款
                    $condition1.= " and userLoanOrder.status = 55"." and ".$condition;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status = 55";
                }elseif($status == UserLoanOrder::STATUS_REPAY_COMPLETE){//已还款
                    $condition1.=" and userLoanOrder.status = ".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.=" and userLoanOrder.status = 66";
                    $condition3.=" and userLoanOrder.status = 66";
                }else{
                    $condition1.= " AND userLoanOrder.status = 1000";
                    $condition2.= " AND userLoanOrder.status = 1000";
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                }
            }else{
                if(isset($plan_fee_time)&&!empty($plan_fee_time)){//到期单数
                    $start_time = strtotime($plan_fee_time);
                    $end_time = $start_time+86400;
                    $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time";
                    $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    if(isset($_status)&&!empty($_status)){
                        $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status = 4";
                        $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status = 4";
                        $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status = 44";
                        $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrder.status = 44";
                    }
                    if (isset($overdue_day) &&! empty($overdue_day)) {
                        $condition .= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition1.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition2.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                        $condition3.= " AND userLoanOrderRepayment.plan_fee_time >={$start_time} and  userLoanOrderRepayment.plan_fee_time <$end_time and userLoanOrderRepayment.status != 4 and userLoanOrderRepayment.status != 0";
                    }
                } else {
                    $condition1.= " and userLoanOrder.status =".UserLoanOrder::STATUS_REPAY_COMPLETE;
                    $condition2.= " and userLoanOrder.status =".UserLoanOrder::STATUS_PARTIALREPAYMENT;
                    $condition3.= " and userLoanOrder.status !=".UserLoanOrder::STATUS_PARTIALREPAYMENT." and userLoanOrder.status !=" . UserLoanOrder::STATUS_REPAY_COMPLETE;
                }
            }
        }

        //总单数
        $query = UserLoanOrder::find()
            ->where($condition)
            ->joinWith(['userLoanOrderRepayment', 'loanPerson', 'userDetail', 'loanFund']);
        $countQuery = clone $query;

        $count = 9999999;
//        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
//            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
//        }, 3600);

        $num = ['count' => 0, 'repay_num' => 0, 'part_num' => 0, 'other' => 0];
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $data = $query
            ->select('userLoanOrder.*,loanPerson.name,loanPerson.id_number,loanPerson.source_id,loanPerson.phone,loanPerson.customer_type,userDetail.company_name,loanFund.name as fund_name,userLoanOrderRepayment.true_repayment_time,userLoanOrderRepayment.created_at as re_created_at')
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy(['userLoanOrder.id' => SORT_DESC])
            ->asArray()
            ->all($db);
        $order_ids = ArrayHelper::getColumn($data, 'id');
        $user_order_loan_check_log = UserOrderLoanCheckLog::find()
            ->where([
                'order_id' => $order_ids,
                'before_status' => 0,
                'after_status' => 0,
            ])
            ->asArray()
            ->all();
        $check_data = [];
        foreach($user_order_loan_check_log as $item) {
            $check_data[$item['order_id']] = $item['order_id'];
        }
        $status_data = [];

        $order_ids = [];
        foreach ($data as $item) {
            $order_ids[] = intval($item['id']);
        }
        $order_reports = OrderReportMongo::find()
            ->where([ 'order_id' => $order_ids])
            ->select(['order_id', 'reject_roots', 'reject_detail'])
            ->asArray()
            ->all();

        $reject_info = [];
        foreach ($order_reports as $item) {
            $reject_info[$item['order_id']] = [
                'reject_roots' => $item['reject_roots'] ?? '',
                'reject_detail' => $item['reject_detail'] ?? '',
            ];
        }
        unset($order_reports);

        foreach ($data as $k => $item) {
            $status = $item['status'];
            if (UserLoanOrder::STATUS_CHECK == $status) {
                if (isset($check_data[$item['id']])) {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."转人工";
                } else {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."机审";
                }
            }
            else {
                $status_data[$item['id']] = isset(UserLoanOrder::$status[$item['status']]) ? UserLoanOrder::$status[$item['status']] : '';
            }

            $data[$k]['reject_roots'] = $reject_info[$item['id']]['reject_roots'] ?? '';
            $data[$k]['reject_detail'] = $reject_info[$item['id']]['reject_detail'] ?? '';
        }

        return $this->render('pocket-reject-list', array(
            'data_list' => $data,
            'status_data'=>$status_data,
            'pages' => $pages,
            'channel'=>$channel,
            'begintime'=>$begintime,
            'num'=>$num
        ));
    }

    /**
     * @name 借款管理-用户借款管理-导出借款列表/actionExportPocketList
     */
    public function actionExportPocketList(){

        /* @var $db \yii\db\Connection */
        $db = Yii::$app->get('db_kdkj_rd');
        $begintime = $this->request->get('begintime');
        $endtime = $this->request->get('endtime');
        $status = $this->request->get('status', UserLoanOrder::STATUS_ALL);

        $limit_day = 86400 * 7;

        if (empty($begintime) && empty($endtime)) {
            $begintime = strtotime("-1 day");
            $endtime = time();
        } else if(empty($begintime)) {
            $begintime = strtotime("{$endtime} -1 day");
            $endtime = strtotime($endtime);
        } else if (empty($endtime)) {
            $endtime = strtotime("{$begintime} +1 day");
            $begintime = strtotime($begintime);
        } else {
            $begintime = strtotime($begintime);
            $endtime = strtotime($endtime);
        }

        $diff_time = $endtime - $begintime;
        if ($diff_time > $limit_day || $diff_time < 0) {
            $this->setDownload("export_error.txt");
            echo "allow export max days number is 7.";
            exit;
        }
        $status_name = UserLoanOrder::$status[$status] ?? "全部";
        $this->setDownload("order_" . date("Y-m-d H:i:s", $begintime) . "_" . date('Y-m-d H:i:s', $endtime) . "_status_"  . $status_name . ".csv");

        $condition = '1=1 and order_time>=' . $begintime . ' and order_time<=' . $endtime;
        if (isset($status) && (UserLoanOrder::STATUS_ALL != $status)) {
            if($status == 10000) {
                $condition .= " AND (a.status = " . UserLoanOrder::STATUS_CANCEL . ' OR a.status = ' . UserLoanOrder::STATUS_REPEAT_CANCEL . ') AND a.is_hit_risk_rule != 1 ';
            }elseif ($status == 10001){
                $condition .= " AND a.status = " . UserLoanOrder::STATUS_CANCEL . ' AND a.is_hit_risk_rule = 1 ';
            }elseif ($status == -1000){
                $condition .= " AND a.status != " . UserLoanOrder::STATUS_REPAY_COMPLETE . ' AND a.status != '.UserLoanOrder::STATUS_PARTIALREPAYMENT;
            }else{
                $condition .= " AND a.status = " . intval($status);
            }
        }

        //总单数
        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')
            ->leftJoin(UserLoanOrderRepayment::tableName().' as r','a.id = r.order_id')
            ->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')
            ->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')
            ->leftJoin(LoanFund::tableName().' as d', 'a.fund_id=d.id')
            ->where($condition)
            ->select('a.*,b.name,b.id_number,b.phone,b.customer_type,c.company_name,d.name as fund_name,r.true_repayment_time,r.is_overdue,r.late_fee,r.overdue_day')
            ->orderBy(['a.id'=>SORT_DESC]);
        $data = $query->asArray()->all($db);

        $order_ids = ArrayHelper::getColumn($data, 'id');
        $user_order_loan_check_log = UserOrderLoanCheckLog::find()
            ->where([
                'order_id' => $order_ids,
                'before_status' => 0,
                'after_status' => 0,
            ])
            ->asArray()
            ->all();
        $check_data = [];
        foreach($user_order_loan_check_log as $item) {
            $check_data[$item['order_id']] = $item['reason_remark'];
        }
        $status_data = [];
        foreach($data as $item){
            $status = $item['status'];
            if (UserLoanOrder::STATUS_CHECK == $status) {
                if (isset($check_data[$item['id']])) {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."转人工";
                }
                else {
                    $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."机审";
                }
            }
            else {
                $status_data[$item['id']] = isset(UserLoanOrder::$status[$item['status']]) ? UserLoanOrder::$status[$item['status']] : '';
            }
        }

        $header = "订单号,用户名,姓名,手机号,是否是老用户,借款金额,借款期限,公司名称,申请时间,还款时间,状态,拒绝原因,资方,是否逾期,逾期天数,滞纳金" . PHP_EOL;
        echo $header;
        foreach ($data as $item) {
            $is_old_user = isset(LoanPerson::$cunstomer_type[$item['customer_type']])? LoanPerson::$cunstomer_type[$item['customer_type']] : "";
            $loan_term = isset(UserLoanOrder::$loan_method[$item['loan_method']])? $item['loan_term'] .UserLoanOrder::$loan_method[$item['loan_method']] : $item['loan_term'];
            $money_amount = sprintf("%0.2f",$item['money_amount']/100);
            $true_repayment_time = empty($item['true_repayment_time'])?'--':date('Y-m-d H:i:s',$item['true_repayment_time']);
            $status = isset($status_data[$item['id']]) ? $status_data[$item['id']] : "";
            $order_time = date('Y-m-d H:i:s',$item['order_time']);
            $fund_name = $item['fund_name'] ? $item['fund_name'] : '无';
            $is_overdue = $item['is_overdue'] ? "是" : "否";
            $reason_mark = $check_data[$item['id']] ?? '';
            $late_day = $item['late_fee'] ? sprintf("%0.2f",$item['late_fee'] / 100) : '';

            $content = <<<STR
{$item['id']},{$item['user_id']},{$item['name']},{$item['phone']},$is_old_user,$money_amount,$loan_term,{$item['company_name']},$order_time,$true_repayment_time,$status,$reason_mark,$fund_name,$is_overdue,{$item['overdue_day']},{$late_day}\n
STR;
            echo $content;
        }

    }

    private function setDownload($filename){
        $mime = 'application/force-download';
        header('Pragma: public'); // required
        header('Expires: 0'); // no cache
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        header('Connection: close');
        ob_clean();
        flush();
    }

    /**
     * @name 借款列表-渠道分销/actionPocketListChannel
     */
    public function actionPocketListChannel()
    {
        $_GET['channel'] = 1;
        $channel=Yii::$app->params['DistributionChannel'];
        $admin_user=Yii::$app->user->identity->username;
        $admin_name=[];
        foreach($channel as $value)
        {
            foreach($value['username'] as $item)
            {
                $admin_name[]=$item;
            }
            if(in_array($admin_user,$value['username']))
            {
                $_GET['sub_order_type'] =$value['sub_order_type'];
            }
        }
        if(!in_array($admin_user,$admin_name))
        {
            return $this->redirectMessage('请配置渠道',self::MSG_ERROR);
        }
        return $this->actionPocketList();
    }

    /**
     * @name 借款管理-风控管理-待机审订单列表/actionPocketAutoTrailList
     */
    public function actionPocketAutoTrailList() {
        $conditionArray = $this->getPocketTrailFilter();
        $condition = $conditionArray['match'].$conditionArray['a'].$conditionArray['b'].$conditionArray['c'];
        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')
            ->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')
            ->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')
            ->where($condition)
            ->andWhere([
                "a.status" => UserLoanOrder::STATUS_CHECK,
                'a.auto_risk_check_status'=>[0,-1,2,3],
            ])->select('a.*,b.name,b.id_number,b.phone,c.company_name')
            ->orderBy(['a.id'=>SORT_DESC]);
        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd');

        if ($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        }
        else {
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);

        return $this->render('auto-trail-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @name 借款管理-风控管理-机审拒绝订单列表/actionPocketAutoRejectList
     */
    public function actionPocketAutoRejectList()
    {
        $conditionArray = $this->getPocketTrailFilter();
        $condition = $conditionArray['match'].$conditionArray['a'].$conditionArray['b'].$conditionArray['c'];
        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')->where($condition)->andWhere(["a.status" => UserLoanOrder::STATUS_CANCEL,'a.auto_risk_check_status'=>1,'a.is_hit_risk_rule'=>1])->select('a.*,b.name,b.id_number,b.phone,c.company_name')->orderBy(['a.id'=>SORT_DESC]);
        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' =>$count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);

        return $this->render('auto-trail-list', array(
            'info' => $info,
            'pages' => $pages,
            'tip'=>1,
        ));
    }

    /**
     * @name 借款管理-风控管理-小钱包人工初审/actionPocketTrailList
     */
    public function actionPocketTrailList(){
        // 'a.sub_order_type' => UserLoanOrder::SUB_TYPE_YGD
        $condition = " and a.sub_order_type = 0 ";
        $view = "pocket";
        return $this->renderTrailListView($condition, $view);
    }

    /**
     * @name 借款管理-风控管理-极速钱包人工初审/actionXjdTrailList
     */
    public function actionXjdTrailList() {
        // 'a.sub_order_type' => UserLoanOrder::SUB_TYPE_XJD
        $condition = " and a.sub_order_type = 1 ";
        $view = "xjd";
        return $this->renderTrailListView($condition, $view);
    }

    /**
     *@name 功能:信审人员手动领取订单
     *参数:
     *@param order_id 点击领取订单链接时会传送order_id到该action,
     *
     */
    public function actionGetOrder() {
        $condition = "";
        $view = "other";
        $order_id = Yii::$app->request->get('order_id','');
        if(!empty($order_id)){
            if(AdminUser::isCreditOfficer(Yii::$app->user->identity->id)){
                if (!ManualOrderDispatch::dispatchManualOrder($order_id, Yii::$app->user->identity->id)) {
                    return $this->redirectMessage('派单失败', self::MSG_ERROR);
                }
            }else{
                return $this->redirectMessage('非信审人员', self::MSG_ERROR);
            }
        }
        $order_ids = ManualOrderDispatch::find()
            ->select('order_id')
            ->distinct()
            ->asArray()
            ->all(); //分配给当前信审人员的订单id集合
        if (count($order_ids) > 0) {
            $oids = ArrayHelper::getColumn($order_ids, 'order_id');
            $condition = ' AND a.id NOT IN ('.implode(',', $oids).')';
        }
        return $this->renderTrailListView($condition, $view,'get-order');
    }

    /**
     * @name 其他订单人工初审
     */
    public function actionOtherTrailList() {
        $condition = "";
        $view = "other";
        if (AdminUser::isCreditOfficer(\yii::$app->user->identity->id)) {
            $order_ids = ManualOrderDispatch::find()
                ->select('order_id')
                ->where(['admin_user_id'=>Yii::$app->user->identity->id])
                ->asArray()
                ->all(); //分配给当前信审人员的订单id集合
            if (count($order_ids) > 0) {
                $oids = ArrayHelper::getColumn($order_ids, 'order_id');
                $condition = ' AND a.id IN ('.implode(',', $oids).')';
            }
            else {
                $condition = ' AND 1 > 2 ';
            }
        }

        return $this->renderTrailListView($condition, $view,'trail-list');
    }

    /**
     * @param $c:传入的额外搜索条件
     * @param $view
     * @param $to_this_view:跳转到这个页面
     * @return view
     * @name 借款管理-风控管理-小钱包人工初审、极速钱包人工初审、其他人工初审界面渲染/renderTrailListView
     */
    protected function renderTrailListView($c = "", $view,$to_this_view) {
        // $assessor = Assessor::find()->from(Assessor::tableName().' as a ')
        //         ->leftJoin(AdminUser::tableName().' as b ','a.user_id = b.id')
        //         ->where(['a.acceptance'=>Assessor::STATUS_YES])
        //         ->select('a.*,b.username')
        //         ->orderBy(['a.status'=>SORT_DESC])
        //         ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $conditionArray = $this->getPocketTrailFilter();
        if ($c != "") {
            $conditionArray['a'] .= $c;
        }
        $condition = $conditionArray['match'] . $conditionArray['a'] . $conditionArray['b'];

        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')
            ->joinWith(['loanPerson as b'])
            ->where($condition)
            ->andWhere([
                "a.status" => UserLoanOrder::STATUS_CHECK,
                'a.auto_risk_check_status' => UserLoanOrder::AUTO_STATUS_SUCCESS,
            ])
            ->select('a.id, a.user_id, a.money_amount, a.order_type, a.loan_method,
                a.loan_term, a.created_at, a.sub_order_type, a.card_type, a.status, a.order_time, a.from_app,
                b.name, b.id_number, b.customer_type, b.phone,b.source_id')
            ->orderBy(['a.id' => SORT_ASC]);

        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd_new');
//        $count = 9999999;
        $count = $countQuery->count('*', $db);
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);

//         $index = 0;
//         foreach ($info as $value) {
//             $info[$index]['assessor_name'] = Assessor::find()->from(Assessor::tableName() . ' as a ')
//                     ->leftJoin(AdminUser::tableName() . ' as b ', 'a.user_id = b.id')
//                     ->where([
//                         'a.id' => AuditorAudit::find()->select('assessor_id')->where(['order_id' => $value['id']])->asArray()->one($db)['assessor_id']])
//                     ->select('b.username')
//                     ->asArray()
//                     ->one($db);
//             $index++;
//         }
        return $this->render($to_this_view, array(
            // 'assessor' => $assessor,
            'info' => $info,
            'pages' => $pages,
            'view' => $view,
        ));
    }

    /**
     * @name 借款管理-风控管理-人工复审/actionPocketRetrailList
     */
    public function actionPocketRetrailList() {
        $conditionArray = $this->getPocketTrailFilter();
        $condition = $conditionArray['match']. $conditionArray['a'] . $conditionArray['b'] . $conditionArray['c'];
        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')
            ->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')
            ->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')
            ->where($condition)
            ->andWhere(["a.status" => UserLoanOrder::STATUS_REPEAT_TRAIL])
            ->select('a.*, b.name, b.id_number, b.phone, b.customer_type, c.company_name')
            ->orderBy(['a.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        $count = $countQuery->count('*');
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);

        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();

        return $this->render('retrail-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * 列表展示公共方法
     * @return string
     */
//    private function getList($type='list')
//    {
//        $condition = $this->getPocketTrailFilter();
//        $where = '';
//        $view = 'pocket-list';
//        switch($type){
//            case 'list':
//
//                break;
//            case 'trail':
//                break;
//            case 'retrail':
//                $where = ["a.status" => UserLoanOrder::STATUS_REPEAT_TRAIL];
//                break;
//        }
//        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')->where($condition)->andWhere()->select('a.*,b.name,b.id_number,b.phone,c.company_name')->orderBy(['a.id'=>SORT_DESC]);
//        $countQuery = clone $query;
//        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
//        $pages->pageSize = 15;
//        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
//
//        return $this->render('retrail-list', array(
//            'info' => $info,
//            'pages' => $pages,
//        ));
//    }
    /**
     * @param $id
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @name 借款管理-用户借款管理-借款列表-查看/actionPocketDetail
     */
    public function actionPocketDetail($id)
    {
        $information = Yii::$container->get("loanPersonInfoService")->getPocketInfo($id);
        return $this->render('pocket-view', array(
            'information' => $information
        ));
    }

    /**
     * 团伙骗贷提示 单独接口请求
     */
    public function actionDetailMatch(){
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            $id = $search['id'];
            $info = UserLoanOrder::find()->where(['id' => $id])->one();
            $loanPerson = LoanPerson::findOne($info['user_id']);
            $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $info['user_id']])->one();
            $equipment = UserDetail::find()->where(['user_id' => $info['user_id']])->one();

            $distinct_match = [];
            $address_match = [];
            if(!is_null($person_relation)){
                //常住地址区域匹配
                $address_distinct = $person_relation->address_distinct;
                $address = $person_relation->address;
                if(!empty($address_distinct)){
                    $distinct_match = UserQuotaPersonInfo::find()->where(['address_distinct'=>$address_distinct])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
                }
                //常住地址完全匹配

                if(!empty($distinct_match)){
                    foreach($distinct_match as $v){
                        if($v->address == $address){
                            $address_match[] = $v;
                        }
                    }
                }
            }

            //登录地址
            $login_log = UserLoginUploadLog::find()->where(['user_id'=>$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
            $log_deviceId_list = [];
            $log_address_list = [];
            if(!empty($login_log)){
                foreach($login_log as $v){
                    if(!empty(trim($v->deviceId)) && !is_null($v->deviceId) && $v->deviceId != 'null'){
                        $log_deviceId_list[] = $v->deviceId;
                    }
                    if(!empty(trim($v->address))){
                        $log_address_list[] = $v->address;
                    }

                }
            }
            $log_deviceId_list = array_unique($log_deviceId_list);
            $log_address_list = array_unique($log_address_list);
            $log_dev_match = UserLoginUploadLog::find()->select(['user_id','deviceId'])->where(['deviceId'=>$log_deviceId_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            $log_address_match = UserLoginUploadLog::find()->select(['user_id','address'])->where(['address'=>$log_address_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));

            //单位名称重复
            $repeat_company_address = [];
            $repeat_company_name = [];
            if(!is_null($equipment)){
                $company_name = $equipment->company_name;
                $company_address = $equipment->company_address;
                if(!empty(trim($company_name))){
                    $repeat_company_name = UserDetail::find()->where(['company_name'=>$company_name])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
                }
                if(!empty(trim($company_address))){
                    $repeat_company_address = UserDetail::find()->where(['company_address'=>$company_address])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
                }
            }

        }

        Yii::$app->response->format=Response::FORMAT_JSON;
        return [
            'distinct_match' => $distinct_match ?? [],
            'address_match' => $address_match ?? [],
            'log_dev_match' => $log_dev_match ?? [],
            'log_address_match' => $log_address_match ?? [],
            'log_deviceId_list' => $log_deviceId_list ?? [],
            'log_address_list' => $log_address_list ?? [],
            'repeat_company_name' => $repeat_company_name ?? [],
            'repeat_company_address' => $repeat_company_address ?? [],
        ];
    }

    /**
     * @param $id
     * @return string
     * @name 借款列表新页面/actionPocketInfo
     */
    public function actionPocketInfo($id){
        $information = Yii::$container->get("loanPersonInfoService")->getPocketInfo($id);
        $info = $information['info'];
        $remark_code = Yii::$container->get("loanPersonInfoService")->getActiveRemarkCode();
        if ($this->request->getIsPost()) {
            //当前状态判断，用于避免重复审核
            if($info->status != UserLoanOrder::STATUS_CHECK){
                return $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/pocket-trail-list']));
            }
            $operation = $this->request->post('operation');
            $code = $this->request->post('code');
            $code = explode("o",$code);
            $remark = $this->request->post('remark');

            $log = new UserOrderLoanCheckLog();
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            if ($operation == '1') {//审核成通过
                $log->order_id = $id;
                $log->before_status = $info->status;
                $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $info->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                $info->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $info->operator_name = Yii::$app->user->identity->username;

                try {
                    if ($info->validate() && $log->validate()) {
                        if($info->save() && $log->save()) {
                            $message_service = new MessageService();
                            $message = "有客户申请了一笔'".UserLoanOrder::$loan_type[UserLoanOrder::LOAN_TYPE_LQD]."'借款，需要复审，请及时审核";
                            $ret = $message_service->sendWeixin(LoanPerson::WEIXIN_NOTICE_YGD_ALL_LOAN_FS,$message);

                            $transaction->commit();
                            if($info->sub_order_type == UserLoanOrder::SUB_TYPE_XJD){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            }else{
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            }

//                            return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                        }
                    } else {
                        throw new Exception;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败'.$e, self::MSG_ERROR);
                }
            } elseif($operation == '2') {//审核不通过

                $credit = $information['credit'];
                $log->order_id = $id;
                $log->before_status = $info->status;
                $log->after_status = UserLoanOrder::STATUS_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $info->status = UserLoanOrder::STATUS_CANCEL;
                $info->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $info->operator_name = Yii::$app->user->identity->username;
                //$info->coupon_id = 0;//优惠券id重置

                //解除用户该订单锁定额度
                $credit->locked_amount = $credit->locked_amount - $info['money_amount'];
                //资金流水
                $user_credit_log = new UserCreditLog();
                $user_credit_log->user_id = $info['user_id'];
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $info['money_amount'];
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = $this->request->getUserIP();
                $user_credit_log->total_money = $credit->amount;
                $user_credit_log->used_money = $credit->used_amount;
                $user_credit_log->unabled_money = $credit->locked_amount;

                $loan_action = $this->request->post('loan_action',1);
                $log->can_loan_type = $loan_action;
                $loanPerson = LoanPerson::findOne($info['user_id']);
                if($loan_action == UserOrderLoanCheckLog::CAN_LOAN){
                    $loanPerson->can_loan_time = 0;
                }elseif ($loan_action == UserOrderLoanCheckLog::MONTH_LOAN){
                    $loanPerson->can_loan_time = time()+86400*30;
                }else{
                    $loanPerson->can_loan_time = 4294967295;
                }

                try {
                    if($code[0] == 'D2' && $code[1] == '08'){
                        $verification = UserVerification::findOne(['user_id'=>$info['user_id']]);
                        $verification->real_work_status = 0;
                        if(!$verification->save()){
                            throw new Exception('认证表保存失败');
                        }

                    }
                    if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                        if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save() && $loanPerson->save()) {
                            $transaction->commit();

                            //触发订单的审核拒绝事件 自定义的数据可添加到custom_data里
                            $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data'=>['remark'=>$remark]]));

                            if($info->sub_order_type == UserLoanOrder::SUB_TYPE_XJD){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            } else if($info->sub_order_type == UserLoanOrder::SUB_TYPE_YGD) {
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            } else {
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/other-trail-list'));
                            }

                        }
                    } else {
                        throw new Exception;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败' . $e->getMessage(), self::MSG_ERROR);
                }
            }
        }
        return $this->render('pocket-info',array(
            'pass_tmp' => $remark_code['pass_tmp'],
            'reject_tmp' => $remark_code['reject_tmp'],
            'information' => $information,
        ));
    }

    /**
     * 初审列表过滤
     * @return string
     */
    protected function getPocketTrailFilter() {
        $condition = [];
        $condition['match'] = '1 = 1';
        $a = ' and a.order_type ='.UserLoanOrder::LOAN_TYPE_LQD;
        $b = "";
        $c = "";
        $d = "";
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $a .= " AND a.id = " . intval($search['id']);
            }
            // if (isset($search['name']) && !empty($search['name'])) {
            //     $b .= " AND b.name like '%".$search['name']."%'";
            // }
            if (isset($search['name']) && !empty($search['name'])) {
                $b .= " AND b.name = '".$search['name']."'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $b .= " AND b.phone = '" . $search['phone']."'";
            }
            if (isset($search['id_number']) && !empty($search['id_number'])) {
                $b .= " AND b.id_number = '" . $search['id_number']."'";
            }

            if (isset($search['company_name']) && !empty($search['company_name'])){
                $c .= " AND c.company_name like '%" . $search['company_name']."%'";
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $a .= " AND a.order_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $a .= " AND a.order_time <= " . strtotime($search['endtime']);
            }
            if (isset($search['channel_type']) && !empty($search['channel_type'])) {
                $a .= " AND b.source_id = " . $search['channel_type'];
            }
            if (isset($search['sub_order_type']) && $search['sub_order_type'] != -1) {
                $a .= " AND a.sub_order_type = " . $search['sub_order_type'];
            }
            if (isset($search['card_type']) && $search['card_type'] != -1) {
                $a .= " AND a.card_type = " . $search['card_type'];
            }
            if (isset($search['auto_risk_check_status']) && $search['auto_risk_check_status'] != UserLoanOrder::AUTO_STATUS_ALL) {
                $a .= " AND a.auto_risk_check_status = " . $search['auto_risk_check_status'];
            }
            if (isset($search['customer_type']) && !empty($search['customer_type'])) {

                if($search['customer_type'] == 1){
                    $b .= " and b.customer_type=".LoanPerson::CUSTOMER_TYPE_OLD;
                    //$condition .= " AND a.user_id in (select user_id from tb_user_loan_order_repayment where id in (select max(id) from tb_user_loan_order_repayment GROUP BY user_id) and status = 4) ";

                }elseif ($search['customer_type'] == -1){
                    $b .= " and b.customer_type=".LoanPerson::CUSTOMER_TYPE_NEW;
                    // $condition .= " AND a.user_id not in (select user_id from tb_user_loan_order_repayment where id in (select max(id) from tb_user_loan_order_repayment GROUP BY user_id) and status = 4) ";

                }
            }
            if (isset($search['min_money']) && !empty($search['min_money'])) {
                $a .= " AND a.money_amount >= " . $search['min_money'] * 100;
            }
            if (isset($search['max_money']) && !empty($search['max_money'])) {
                $a .= " AND a.money_amount <= " . $search['max_money'] * 100;
            }
            if (isset($search['username']) && !empty($search['username'])) {
                $d .= " AND d.username = " . "'" . $search['username'] . "'";
            }
        }
        $condition['a'] = $a;
        $condition['b'] = $b;
        $condition['c'] = $c;
        $condition['d'] = $d;

        return $condition;
    }

    /**
     * @param $id
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @name 借款管理-风控管理-小钱包人工初审-零钱包-审核/actionPocketFirstTrail
     */
    public function actionPocketFirstTrail($id, $view)
    {
        $information = Yii::$container->get("loanPersonInfoService")->getPocketInfo($id);
        $info = $information['info'];

        if ($this->request->getIsPost()) {
            //当前状态判断，用于避免重复审核
            $key = "actionPocketFirstTrail::".$id;
            $cache = Yii::$app->redis;
            $i = $cache->get($key);
            if (!empty($i)) {
                if($view == "pocket"){
                    return $this->redirectMessage('该订单状态正在发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/pocket-trail-list']));
                } else if($view == "xjd"){
                    return $this->redirectMessage('该订单状态正在发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/xjd-trail-list']));
                } else{
                    return $this->redirectMessage('该订单状态正在发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/other-trail-list']));
                }
            }
            $cache->setex( $key, 5, 1);

            if($info->status != UserLoanOrder::STATUS_CHECK){
                if($view == "pocket"){
                    return $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/pocket-trail-list']));
                } else if($view == "xjd"){
                    return $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/xjd-trail-list']));
                } else{
                    return $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/other-trail-list']));
                }

            }
            $operation = $this->request->post('operation');
            $code = $this->request->post('code');
            $code = explode("o",$code);
            $remark = $this->request->post('remark');

            $log = new UserOrderLoanCheckLog();
            $info->tree = 'manual';
            $log->tree = 'manual';
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            if ($operation == '1') {//审核成通过
                $log->order_id = $id;
                $log->before_status = $info->status;
                $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $info->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                $info->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $info->operator_name = Yii::$app->user->identity->username;

                try {
                    if ($info->validate() && $log->validate()) {
                        if($info->save() && $log->save()) {
                            $message_service = new MessageService();
                            $message = "有客户申请了一笔'".UserLoanOrder::$loan_type[UserLoanOrder::LOAN_TYPE_LQD]."'借款，需要复审，请及时审核";
                            $ret = $message_service->sendWeixin(LoanPerson::WEIXIN_NOTICE_YGD_ALL_LOAN_FS,$message);

                            $transaction->commit();
                            // if($info->sub_order_type == UserLoanOrder::SUB_TYPE_XJD){
                            //     return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            // }else{
                            //     return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            // }

                            if($view == "pocket"){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            } else if($view == "xjd"){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            } else{
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/other-trail-list'));
                            }

//                            return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                        }
                    } else {
                        throw new Exception;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败'.$e, self::MSG_ERROR);
                }
            } elseif($operation == '2') {//审核不通过
                if (trim($remark) == "") {
                    return $this->redirectMessage('审核失败, 拒绝原因必须填写', self::MSG_ERROR);
                }

                $credit = $information['credit'];
                $log->order_id = $id;
                $log->before_status = $info->status;
                $log->after_status = UserLoanOrder::STATUS_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $info->status = UserLoanOrder::STATUS_CANCEL;
                $info->status_type = UserLoanOrder::STATUS_PERSON;//人工拒绝
                $info->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $info->operator_name = Yii::$app->user->identity->username;
                //$info->coupon_id = 0;//优惠券id重置

                //解除用户该订单锁定额度
                $credit->locked_amount = $credit->locked_amount - $info['money_amount'];
                //资金流水
                $user_credit_log = new UserCreditLog();
                $user_credit_log->user_id = $info['user_id'];
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $info['money_amount'];
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = $this->request->getUserIP();
                $user_credit_log->total_money = $credit->amount;
                $user_credit_log->used_money = $credit->used_amount;
                $user_credit_log->unabled_money = $credit->locked_amount;

                $loan_action = $this->request->post('loan_action',1);
                $log->can_loan_type = $loan_action;
                $loanPerson = LoanPerson::findOne($info['user_id']);
                if($loan_action == UserOrderLoanCheckLog::CAN_LOAN){
                    $loanPerson->can_loan_time = 0;
                }elseif ($loan_action == UserOrderLoanCheckLog::MONTH_LOAN){
                    $loanPerson->can_loan_time = time()+86400*30;
                }else{
                    $loanPerson->can_loan_time = 4294967295;
                }

                try {
                    if($code[0] == 'D2' && $code[1] == '08'){
                        $verification = UserVerification::findOne(['user_id'=>$info['user_id']]);
                        $verification->real_work_status = 0;
                        if(!$verification->save()){
                            throw new Exception('认证表保存失败');
                        }

                    }
                    if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                        if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save() && $loanPerson->save()) {
                            $transaction->commit();

                            //触发订单的审核拒绝事件 自定义的数据可添加到custom_data里
                            $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data'=>['remark'=>$remark]]));

                            // if($info->sub_order_type == UserLoanOrder::SUB_TYPE_XJD){
                            //     return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            // } else if($info->sub_order_type == UserLoanOrder::SUB_TYPE_YGD) {
                            //     return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            // } else {
                            //     return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/other-trail-list'));
                            // }

                            if($view == "pocket"){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-trail-list'));
                            } else if($view == "xjd"){
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/xjd-trail-list'));
                            } else{
                                return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/other-trail-list'));
                            }

                        }
                    } else {
                        throw new Exception;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败' . $e->getMessage(), self::MSG_ERROR);
                }
            }
        }

        $remark_code = Yii::$container->get("loanPersonInfoService")->getActiveRemarkCode();
        return $this->render('first-trail', array(
            'information' => $information,
            'pass_tmp'=>$remark_code['pass_tmp'],
            'reject_tmp'=>$remark_code['reject_tmp'],
        ));
    }

    /**
     * @param $id
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @name 借款管理-风控管理-人工复审-审核/actionPocketTwiceTrail
     */
    public function actionPocketTwiceTrail($id) {
        $loanPersonInfoService = \yii::$container->get("loanPersonInfoService");
        $information = $loanPersonInfoService->getPocketInfo($id);
        $info = $information['info'];

        /* @var $info UserLoanOrder */
        if ($this->request->isPost) {
            if ($info->status != UserLoanOrder::STATUS_REPEAT_TRAIL) {
                return $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/pocket-retrail-list']));
            }

            $operation = $this->request->post('operation');
            $code = $this->request->post('code');
            $code = \explode("o", $code);
            $remark = $this->request->post('remark');

            $log = new UserOrderLoanCheckLog();
            if ($operation == '1') {
                $log->order_id = $id;
                $log->before_status = $info->status;
                //$log->after_status = UserLoanOrder::STATUS_PENDING_LOAN;//分配资方时会自动指定订单状态 及日志的after_status
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_FS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $order_service = new \common\services\OrderService();
                $ret = $order_service->reviewPass($info, [
                    'trail_time'=>time(),
                    'operator_name'=>Yii::$app->user->identity->username,
                    'reason_remark'=>LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'],
                ], $log,  Yii::$app->user->identity->username);

                if($ret['code']===0) {
                    $message_service = new MessageService();
                    $message = "有客户申请了一笔'".UserLoanOrder::$loan_type[UserLoanOrder::LOAN_TYPE_LQD]."'借款，需要进行资产放款，请及时处理";
                    $message_service->sendWeixin(LoanPerson::WEIXIN_NOTICE_YGD_ALL_LOAN_ZCFK, $message);
                    return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-retrail-list'));
                } else {
                    return $this->redirectMessage('审核失败:'.$ret['message'], self::MSG_ERROR);
                }
            }
            elseif ($operation == '2') {
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                $credit = $information['credit'];
                $log->order_id = $id;
                $log->before_status = $info->status;
                $log->after_status = UserLoanOrder::STATUS_REPEAT_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['backend_name'];
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::LOAN_FS;
                $log->head_code = $code[0];
                $log->back_code = $code[1];

                $info->status = UserLoanOrder::STATUS_REPEAT_CANCEL;
                $info->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                $info->operator_name = Yii::$app->user->identity->username;
                $info->trail_time = time();//审核时间
                //$info->coupon_id = 0;//优惠券id重置

                //解除用户该订单锁定额度
                $credit->locked_amount = $credit->locked_amount - $info['money_amount'];
                //资金流水
                $user_credit_log = new UserCreditLog();
                $user_credit_log->user_id = $info['user_id'];
                $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_FS_CANCEL;
                $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                $user_credit_log->operate_money = $info['money_amount'];
                $user_credit_log->created_at = time();
                $user_credit_log->created_ip = $this->request->getUserIP();
                $user_credit_log->total_money = $credit->amount;
                $user_credit_log->used_money = $credit->used_amount;
                $user_credit_log->unabled_money = $credit->locked_amount;

                try {
                    if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                        if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {
                            $transaction->commit();

                            //触发订单的审核拒绝事件 自定义的数据可添加到custom_data里
                            $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data'=>['remark'=>$remark]]));

                            // $message_service = new MessageService();
                            // $ret = $message_service->sendMessageLoanYgbReject($info['user_id'],$id);
                            return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket/pocket-retrail-list'));
                        }
                    }
                    else {
                        throw new Exception( \sprintf('%s::%s', \basename(__FILE__), __LINE__) );
                    }
                }
                catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->redirectMessage('审核失败' . $e->getMessage(), self::MSG_ERROR);
                }
            }
        }

        $remark_code = $loanPersonInfoService->getActiveRemarkCode();

        return $this->render('twice-trail', [
            'information' => $information,
            'pass_tmp'=>$remark_code['pass_tmp'],
            'reject_tmp'=>$remark_code['reject_tmp'],
        ]);
    }

    /**
     * @return array
     * @name 借款管理-用户借款管理-借款列表-查看-删除图片/actionDelUserImg
     */
    public function actionDelUserImg(){
        $this->getResponse()->format = Response::FORMAT_JSON;
        $user_id = intval($this->request->get('id'));
        $type = intval($this->request->get('type'));
        if($type == UserProofMateria::TYPE_ID_CAR){
            $count = UserProofMateria::deleteAll(['user_id' => $user_id,'type'=>[$type,UserProofMateria::TYPE_FACE_RECOGNITION]]);
        }else{
            $count = UserProofMateria::deleteAll(['user_id' => $user_id,'type'=>$type]);
        }

        $verification = UserVerification::findOne(['user_id'=>$user_id]);
        $verification->real_work_fzd_status = 0;
        $verification->save();
        if($count > 0){
            return [
                'code' => 0,
                'message' => '删除成功'
            ];
        }else{
            return [
                'code' => -1,
                'message' => '删除失败'
            ];
        }
    }

    /**
     * @return string
     * @name 借款管理-风控管理-用户运营商认证状态/actionJxlStatusView
     */
    public function actionJxlStatusView(){
        $condition = '1 = 1';
        $pages = new Pagination();
        $info=[];
        $credit_jxl_data = [];
        $count='';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if((isset($search['uid']) && !empty($search['uid']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['uid']) && !empty($search['uid'])) {
                    $condition .= " and id = " . intval($search['uid']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND name = '" . $search['name']."'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND phone = '" .str_replace(' ','',$search['phone'])."'";
                }
                $query = LoanPerson::find()->where($condition)->orderBy('id desc');
//                $query = CreditJxlQueue::find()->from(CreditJxlQueue::tableName() . ' as a')
//                ->leftJoin([LoanPerson::tableName() . ' as b on a.user_id = b.id'])
//                ->where($condition)
//                ->select('a.*,b.name,b.phone');
                $countQuery = clone $query;
                $count =  $countQuery->count('*',Yii::$app->get('db_kdkj_rd'));
                $pages ->totalCount=$count;
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                $user_ids = [];
                if($ret){
                    $info=$ret;
                    foreach($info as $item){
                        $user_ids[] = $item['id'];
                    }
                    $tmp = CreditJxlQueue::find()->where(['user_id'=>$user_ids])->asArray()->all(Yii::$app->get('db_kdkj_risk_rd'));
                    foreach($tmp as $item){
                        $credit_jxl_data[$item['user_id']]= $item;
                    }

                }

            }
        }
        return $this->render('jxl-status-view', array(
            'info' => $info,
            'credit_jxl_data'=>$credit_jxl_data,
            'pages' => $pages,
            'count' => $count,
        ));
    }

    /**
     * @name 跳过用户的机审/actionSkipCheckStatus
     * @param integer $user_id 用户ID
     */
    public function actionSkipCheckStatus($user_id)
    {
        $model = UserLoanOrder::find()->where(['user_id'=>(int)$user_id, 'status'=> UserLoanOrder::STATUS_CHECK])->orderBy('id DESC')->one();
        if(!$model){
            echo "订单不存在";
            exit;
        }
        $model->auto_risk_check_status = 1;
        $model->updateAttributes(['auto_risk_check_status']);
        echo "OK";
    }

    /**
     * @name 跳过机审/actionCheckStatus
     * @param integer $id 订单ID
     */
    public function actionCheckStatus($id)
    {
        try {
            $transaction = UserLoanOrder::getDb()->beginTransaction();
            $model = UserLoanOrder::findOne(intval($id));
            if(!$model){
                return $this->redirectMessage('借款记录不存在',self::MSG_ERROR);
            }
            $model->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;
            if($model->save())
            {
                $log = new UserOrderLoanCheckLog();
                $log->order_id = $id;
                $log->before_status = $model->status;
                $log->after_status = $model->status;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                $log->reason_remark = '';
                $log->remark = '人工操作跳过机审';
                $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                $log->save();
                $transaction->commit();
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
            }
            else
            {
                $transaction->rollback();
                return $this->redirectMessage('操作失败', self::MSG_ERROR);
            }
        } catch (Exception $e) {
            $transaction->rollback();
            \Yii::error('人工跳过机审出错' . $e->getMessage() . ' - ' . $e->getLine());
        }
    }


    /**
     * @name 取消融360订单/actionCancelRongOrder
     * @param integer $id 订单ID
     */
    public function actionCancelRongOrder($id)
    {
        $model = UserLoanOrder::findOne(intval($id));
        if(!$model){
            return $this->redirectMessage('借款记录不存在',self::MSG_ERROR);
        }
        $service = Yii::$container->get('orderService');
        /* @var $service OrderService */
        $ret = $service->rejectLoan($model->id, '取消订单',  Yii::$app->user->identity->username);

        if($ret['code']==0) {
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('操作失败:'.$ret['message'], self::MSG_ERROR);
        }
    }


    /**
     *
     * gaokuankuan
     * 2016-10-25
     *
     * @name 审核员列表/actionAssessorList
     *
     */
    public function actionAssessorList()  {
        $assessor = Assessor::find()->from(Assessor::tableName() . ' as a ')
            ->leftJoin(AdminUser::tableName() . ' as b ', 'a.user_id = b.id')
            ->leftJoin(AuditorAudit::tableName() . ' as c ', 'a.id = c.assessor_id')
            ->select('a.*,b.username')
            ->groupBy('a.id')
            ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $index = 0;
        foreach ($assessor as $value) {
            $assessor[$index]['total'] = count(AuditorAudit::find()->from(AuditorAudit::tableName() . ' as a ')
                ->leftJoin(UserLoanOrder::tableName() . ' as b ', 'a.order_id = b.id')->where(['a.assessor_id' => $value['id'],"b.status" => UserLoanOrder::STATUS_CHECK])->asArray()->all(Yii::$app->get('db_kdkj_rd')));
            $index++;
        }
        return $this->render('assessor-list', array(
            'info' => $assessor,
        ));
    }

    /**
     * gaokuankuan
     * 2016-10-25
     *
     * @name 审核员状态/actionUpdateStatus
     *
     */
    public function actionUpdateStatus($status) {
        $model = Assessor::find()->where(['user_id' => Yii::$app->user->identity->id])->one(Yii::$app->get('db_kdkj_rd'));
        $model->status = $status;
        if ($model->save()) {
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('操作失败', self::MSG_ERROR);
        }
    }

    /**
     * gaokuankuan
     * 2016-10-25
     *
     * @name 审核员是否接入/actionUpdateAcceptance
     *
     */
    public function actionUpdateAcceptance($id, $acceptance) {
        $model = Assessor::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if($acceptance == 1){
            $model->acceptance = 0 ;
            $model->status = 0;
        }else{
            $model->acceptance = 1 ;
        }
        if ($model->save()) {
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('操作失败', self::MSG_ERROR);
        }
    }

    /**
     *  gaokuankuan
     * 2016-10-25
     *
     * @name 待审已清空/actionPendingToEmpty
     *
     */
    public function actionPendingToEmpty($id) {

        if (AuditorAudit::updateAll(['assessor_id' => Assessor::STATUS_NO], ['assessor_id' => intval($id), 'status' => Assessor::STATUS_NO])) {
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('待审已清空', self::MSG_ERROR);
        }
    }

    /**
     * @return string
     * @name 借款管理-带机审订单列表-查看-删除图片/actionLoanProofDelete
     */
    public function actionLoanProofDelete()
    {
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->get();
            if(!empty($data['user_id'])&&!empty($data['proof_id'])){
                $loan_Person_Proof=UserProofMateria::find()->where(['id'=>intval($data['proof_id'])])->andWhere(['<>','status',UserProofMateria::STATUS_DEL])->all(Yii::$app->get('db_kdkj_rd'));
                if(!empty($loan_Person_Proof))
                {
                    UserProofMateria::deleteById($data['user_id'],$data['proof_id']);
                }
                else{
                    return $this->redirectMessage('照片不存在',self::MSG_ERROR);
                }
            }else
            {
                return $this->redirectMessage('操作失败',self::MSG_ERROR);
            }
        }

    }




    // Codes below are created by Shayne.

    /**
     * @return string
     * @name 借款管理-审核订单使用量/actionAuditNumber
     */
    public function actionAuditNumber(){
        $date = date('Y-m-d');
        $start_time = strtotime($this->request->get('add_start'));
        $end_time = strtotime($this->request->get('add_end'));
        if($start_time){
            $start_time =  $start_time ;
        }else{
            $start_time = strtotime($date)-86400;
        }
        if($end_time){
            $end_time = $end_time ;
        }else{
            $end_time = strtotime($date);
        }
        $sql = "SELECT COUNT(*) count,operator_name FROM tb_user_order_loan_check_log where before_status=0 AND created_at >=$start_time AND created_at <=$end_time GROUP BY operator_name";
        $data = UserOrderLoanCheckLog::findBySql($sql)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('audit-number', array(
            'start_time'=>date('Y-m-d',$start_time),
            'end_time' => date('Y-m-d',$end_time),
            'data'=>$data,
        ));
    }

    /**
     * @name 重新进入风控队列
     */
    public function actionPushRedis()
    {
        $list = [
//            RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD => '授信队列',
            UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX => '获取征信数据队列',
//            UserCreditData::CREDIT_GET_DATA_SOURCE_SIMPLE_PREFIX => '获取征信数据队列（简化版）',
            RedisQueue::LIST_CHECK_ORDER => '机审队列',
        ];
        if ($this->getRequest()->isPost) {
            $params = $this->getRequest()->post();

            if (empty($params['list_type'] || !isset($list[$params['list_type']]))) {
                return $this->redirectMessage('队列名错误', self::MSG_ERROR);
            }

            if (empty($params['ids'])) {
                return $this->redirectMessage('ids不能为空', self::MSG_ERROR);
            }

            $ids = explode(PHP_EOL, $params['ids']);

            foreach ($ids as $id) {
                $id = trim($id);
                if (empty($id)) {
                    continue;
                }
                if (!is_numeric($id)) {
                    return $this->redirectMessage(\sprintf('id：%s 类型错误', $id), self::MSG_ERROR);
                }

                //修改订单状态
                $user_loan_order=UserLoanOrder::findOne($id);
                if (empty($user_loan_order)){
                    return $this->redirectMessage(\sprintf('id：%s 订单号有误', $id), self::MSG_ERROR);
                }
                if($user_loan_order->status!=UserLoanOrder::STATUS_CANCEL){
                    return $this->redirectMessage(\sprintf('id：%s 状态不是初审驳回', $id), self::MSG_ERROR);
                }
                $user_loan_order->status=UserLoanOrder::STATUS_CHECK;
                if($user_loan_order->auto_risk_check_status!=0){
                    $user_loan_order->auto_risk_check_status=0;
                }
                if($user_loan_order->is_hit_risk_rule!=0){
                    $user_loan_order->is_hit_risk_rule=0;
                }
                $result=$user_loan_order->save();
                if($result){
                    RedisQueue::push([$params['list_type'], $id]);
                }else{
                    return $this->redirectMessage(\sprintf('id：%s 状态重置失败', $id), self::MSG_ERROR);
                }
            }

            return $this->redirectMessage('push成功', self::MSG_SUCCESS);

        } else {
            return $this->render('push-redis', [
                'list' => $list
            ]);
        }

    }

    /**
     * 导出借款列表
     * @param $data
     */
    private function _exportPocketData($data, $status_data){
        Util::cliLimitChange(1024);
        $check = $this->_canExportData();
        if(!$check){
            return $this->redirectMessage('无权限', self::MSG_ERROR);
        }else{
            new LoanPerson();
            $this->_setcsvHeader('订单列表数据.csv');
            $items = [];
            foreach($data as $value){
                $items[] = [
                    '订单号' => $value['id'] ?? 0,
                    '用户ID' => $value['user_id'] ?? 0,
                    '姓名' => $value['name'] ?? 0,
                    '手机号' => $value['phone'] ?? 0,
                    '是否是老用户' => isset(LoanPerson::$cunstomer_type[$value['customer_type']]) ? LoanPerson::$cunstomer_type[$value['customer_type']]:"",
                    '借款金额(元)' => (!empty($value['money_amount'])) ? sprintf("%0.2f",$value['money_amount']/100) : "---",
                    '抵扣券金额(元)' => (!empty($value['re_coupon_money'])) ? sprintf("%0.2f",$value['re_coupon_money']/100) : "---",
                    '借款期限' => isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term'],
                    '公司名称' => $value['company_name'] ?? "",
                    '申请时间' => empty($value['order_time']) ? '--' : date('Y-m-d H:i:s',$value['order_time']),
                    '放款时间' => empty($value['re_created_at']) ? '--' : date('Y-m-d H:i:s',$value['re_created_at']),
                    '还款时间' => empty($value['true_repayment_time'])?'--':date('Y-m-d H:i:s',$value['true_repayment_time']),
                    '状态' => isset($status_data[$value['id']])?$status_data[$value['id']]:"",
                    '资方' => $value['fund_name'] ? $value['fund_name'] : '无',
                    '来源' => isset($value['source_id']) ? LoanPerson::$person_source[$value['source_id']] : '-',
                    '渠道' => isset($value['sub_order_type']) ? UserLoanOrder::$sub_order_type[$value['sub_order_type']] : '-',
                ];
            }
            echo $this->_array2csv($items);
        }
        exit;
    }

    /**
     * 强制用户还款
     * @para
     **/
    public function actionDaikou($id) {
        if ($this->request->post('submit')) {
            //如果提交了银行卡信息2018/5/16
            $data = $this->request->post();
            if(!isset($data['amount']) || !isset($data['bank_id']) || !isset($data['orderid'])){
                return $this->redirectMessage("抱歉，传递参数有误！", self::MSG_ERROR);
            }
            //扣款金额
            $amount=$data['amount'];
            //扣款银行卡id
            $bank_id=$data['bank_id'];
            //订单id
            $id=$data['orderid'];
            if(doubleval($amount)<=0 || intval($bank_id)<=0 || intval($id)<=0){
                return $this->redirectMessage("抱歉，传递参数有误！", self::MSG_ERROR);
            }

            //获得借款订单
            $info = UserLoanOrder::find()->where(['id' => $id])->one();
            if(empty($info) && !isset($info)) {
                return $this->redirectMessage("抱歉，未获得借款订单！", self::MSG_ERROR);
            }

            if($info['status']!=UserLoanOrder::STATUS_LOAN_COMPLING  && $info['status']!=UserLoanOrder::STATUS_PARTIALREPAYMENT && $info['status']!=UserLoanOrder::STATUS_APPLY_REPAY){
                return $this->redirectMessage("抱歉，该借款订单您不能扣款！", self::MSG_ERROR);
            }

            $loanPerson = LoanPerson::findOne($info['user_id']);
            if(is_null($loanPerson)){
                return $this->redirectMessage("抱歉，该借款订单对应的用户不存在！", self::MSG_ERROR);
            }
            $repaymentinfo=UserLoanOrderRepayment::find()->where(['order_id'=>$id])->one();
            //实际应还金额（应还金额-已还金额）
            $preayment_amount=$repaymentinfo->total_money - $repaymentinfo->true_total_money;
            if($preayment_amount<$amount){
                return $this->redirectMessage("抱歉，扣款金额不能大于{$preayment_amount}元！", self::MSG_ERROR);
            }
            //判断卡号
            $cardinfo=CardInfo::find()->where(['type'=>CardInfo::TYPE_DEBIT_CARD,'status'=>CardInfo::STATUS_SUCCESS,'user_id'=>$info['user_id'],'id'=>$bank_id])->one();
            if(empty($cardinfo) && !isset($cardinfo)){
                return $this->redirectMessage("抱歉，您不能使用该银行卡进行扣款！", self::MSG_ERROR);
            }

            //扣款操作
            $infos=UserLoanOrder::getOrderRepaymentCard($id, $info['user_id'], $bank_id);
            $money = StringHelper::safeConvertCentToInt($amount);
            $extra['money'] = $money;
            $extra['debit_type'] = AutoDebitLog::DEBIT_TYPE_SYS;

            //real_name、id_card（真实姓名、身份证号）
            $extra['real_name'] = $loanPerson->name;
            $extra['id_card'] = $loanPerson->id_number;

            $loanService = new LoanService();
            //设置还款还款方式为用户主动还款
            $ret = $loanService->applyDebitNew($infos['order'], $infos['repayment'], $infos['card_info'],$extra);
            if($ret['code']==0){
                //提交扣款成功
                return $this->redirectMessage('该代扣已经提交成功，系统正在处理中...', self::MSG_SUCCESS);
            }else{
                //提交扣款失败
                $msg=$ret['msg'];
                return $this->redirectMessage($msg, self::MSG_ERROR);
            }
        }else{
            //获得借款订单
            $info = UserLoanOrder::find()->where(['id' => $id])->one();
            if(empty($info) && !isset($info)) {
                throw new NotFoundHttpException('订单不存在');
            }
            if($info['status']!=UserLoanOrder::STATUS_LOAN_COMPLING && $info['status']!=UserLoanOrder::STATUS_PARTIALREPAYMENT&& $info['status']!=UserLoanOrder::STATUS_APPLY_REPAY){
                throw new NotFoundHttpException('该订单未在还款中');
            }
            $loanPerson = LoanPerson::findOne($info['user_id']);
            if(is_null($loanPerson)){
                throw new NotFoundHttpException('用户不存在');
            }
            $repaymentinfo=UserLoanOrderRepayment::find()->where(['order_id'=>$id])->one();
            //实际应还金额（应还金额-已还金额）
            $preayment_amount=$repaymentinfo->total_money - $repaymentinfo->true_total_money;

            //获得该用户绑定的储蓄卡
            $cardinfo=CardInfo::find()->where(['type'=>CardInfo::TYPE_DEBIT_CARD,'status'=>CardInfo::STATUS_SUCCESS,'user_id'=>$info['user_id']])->all();
            return $this->render('daikou', ['cardinfo'=>$cardinfo,'loanPerson'=>$loanPerson,'preayment_amount'=>$preayment_amount,'orderid'=>$id]);
        }
    }
}
