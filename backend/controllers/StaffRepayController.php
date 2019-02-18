<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/18
 * Time: 9:49
 */
namespace backend\controllers;
use backend\models\ActionModel;
use backend\models\AdminOperatorLog;
use common\models\AlipayRepaymentLog;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\OverdueResetLog;
use common\models\SuspectDebitLostRecord;
use common\models\UserCreditReviewLog;
use common\models\UserCreditTotal;
use common\models\UserCreditMoneyLog;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepayment;
use common\models\UserRepaymentPeriod;
use common\services\AutoDebitService;
use Yii;
use yii\base\Exception;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use common\helpers\Util;
use yii\data\Pagination;
use yii\db\Query;
use common\models\UserLoanOrderDelay;
use common\models\UserOrderLoanCheckLog;
use common\models\FinancialDebitRecord;
use common\models\UserLoanOrderDelayLog;
use common\models\loan\LoanCollectionOrder;
use common\models\UserProofMateria;
use common\models\RepayEditLog;
use backend\models\AdminUser;
use common\helpers\Lock;
use common\base\ErrCode;
use common\models\UserLoanOrderForzenRecord;
use common\models\UserLoanOrderForzenRecordLog;
use common\models\RidOverdueLog;

use common\models\fund\LoanFund;
use yii\web\Response;
use yii\web\UploadedFile;
use common\models\TimedTask;
use common\models\SmsReturn;

class StaffRepayController extends  BaseController
{

    private $message = '';

    /**
     * @param $id
     * @param $from_url 来源URL 通过不同的来源跳转到不同的页面
     * @return string
     * @name 借款管理-贷后管理-零钱包还款列表-发起还款申请/actionPocketViewApply
     */
    public function actionPocketViewApply($id, $from_url=null){
        $info = UserLoanOrderRepayment::findOne(intval($id));
        if(is_null($info)){
            return $this->redirectMessage('还款记录不存在',self::MSG_ERROR);
        }
        $loanPerson = LoanPerson::findOne($info['user_id']);
        if(is_null($loanPerson)){
            return $this->redirectMessage('借款人不存在',self::MSG_ERROR);
        }
        $order = UserLoanOrder::findOne($info['order_id']);
        if(is_null($order)){
            return $this->redirectMessage('借款订单不存在',self::MSG_ERROR);
        }
        $last_repayment = UserLoanOrderRepayment::find()->where(['order_id'=>$order['id']])->orderBy(['id'=>SORT_DESC])->one(UserLoanOrderRepayment::getDb_rd());
        if(is_null($last_repayment)){
            return $this->redirectMessage('分期还款记录不存在',self::MSG_ERROR);
        }
        $last_repayment_time = date('Y-m-d',$last_repayment['plan_repayment_time']);
        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id'=>$order['id'],'repayment_id'=>$id])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $equipment = UserDetail::find()->where(['user_id' => $loanPerson['id']])->one(Yii::$app->get('db_kdkj_rd'));


        if ($this->request->getIsPost()) {
            $repayment_money = $this->request->post('repayment_money','0')*100;
            $ret = $this->actionPocketApply($info['user_id'],$info['order_id'],$info['id'],$repayment_money);
            if(isset($ret['code'])&&(0 == $ret['code'])){
                if($from_url) {
                    return $this->redirectMessage($ret['message'], self::MSG_SUCCESS, $from_url);
                } else {
                    return $this->redirectMessage($ret['message'], self::MSG_SUCCESS, Url::toRoute('staff-repay/pocket-repay-list'));
                }

            }else{
                return $this->redirectMessage($ret['message'],self::MSG_ERROR);
            }
        }

        return $this->render('pocket-view-apply', [
            'info' => $info,
            'trail_log' => $trail_log,
            'order' => $order,
            'loanPerson' => $loanPerson,
            'equipment' => $equipment,
            'last_repayment_time' =>$last_repayment_time,
        ]);

    }

    /**
     * 后台发起还款
     * @param $user_loan_order
     * @param $repayment_id
     * @param $card_id
     * @return array
     */
    public function actionPocketApply($user_id,$user_loan_order_id,$repayment_id,$repayment_money=''){

        $card_info = CardInfo::findOne(['user_id'=>$user_id]);
        if(false == $card_info){
            return [
                'code'=>-1,
                'message'=>'获取银行卡号失败',
            ];
        }
        $card_id = $card_info->id;
        $user_loan_order = UserLoanOrder::findOne(['id'=>$user_loan_order_id]);
        if(false == $user_loan_order){
            return [
                'code'=>-1,
                'message'=>'获取订单数据失败',
            ];
        }
        $status = $user_loan_order->status;
        switch($status){
            case UserLoanOrder::STATUS_BAD_DEBT :
            case UserLoanOrder::STATUS_OVERDUE :
            case UserLoanOrder::STATUS_REPAYING_CANCEL :
            case UserLoanOrder::STATUS_DEBIT_FALSE :
            case UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL :
            case UserLoanOrder::STATUS_REPAY_CANCEL :
            case UserLoanOrder::STATUS_PARTIALREPAYMENT :
            case UserLoanOrder::STATUS_LOAN_COMPLETE:
                //正常还款
                $user_loan_order_repayment =  UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'id'=>$repayment_id]);
                if(false == $user_loan_order_repayment){
                    return [
                        'code'=>-1,
                        'message'=>'该还款单不存在，请确认',
                    ];
                }
                if($repayment_money > 0){
                    $user_loan_order_repayment->current_debit_money = $repayment_money;
                }else{
                    $user_loan_order_repayment->current_debit_money = $user_loan_order_repayment->principal+$user_loan_order_repayment->interests+$user_loan_order_repayment->late_fee-$user_loan_order_repayment->true_total_money;
                }
                $user_loan_order_repayment->debit_times = $user_loan_order_repayment->debit_times+1;
                $user_loan_order_repayment->updated_at = time();
                $user_loan_order_repayment->operator_name = Yii::$app->user->identity->getId();
                $user_loan_order_repayment->status= UserLoanOrderRepayment::STATUS_CHECK;
                $user_loan_order_repayment->card_id = $user_loan_order->card_id;
                $user_loan_order_repayment->apply_repayment_time = time();
                $user_loan_order->status = UserLoanOrder::STATUS_APPLY_REPAY;
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    if(!$user_loan_order_repayment->update()){
                        return [
                            'code'=>-1,
                            'message'=>'还款失败，请稍后再试',
                        ];

                    }
                    if(!$user_loan_order->update()){
                        $transaction->rollBack();
                        return [
                            'code'=>-1,
                            'message'=>'还款失败，请稍后再试',
                        ];
                     }
                    $transaction->commit();
                    return [
                        'code'=>0,
                        'message'=>'还款申请已提交，请等待审核',
                    ];
                }catch(\Exception $e){
                    $transaction->rollBack();
                    return [
                        'code'=>-1,
                        'message'=>'还款失败，请稍后再试',
                    ];
                }
                break;
            case UserLoanOrder::STATUS_LOAN_COMPLING:
                return [
                    'code'=>-1,
                    'message'=>'该单处于申请还款中，请不要重复申请',
                ];
                break;
            case UserLoanOrder::STATUS_REPAY_COMPLETE;
                return [
                    'code'=>-1,
                    'message'=>'该单已经还款，请不要重复申请',
                ];
                break;
            default:
                return [
                    'code'=>-1,
                    'message'=>'还款失败，请稍后再试',
                ];

                break;
        }



    }

    /**
     * @param string $type
     * @return string|void
     * @name 借款管理-贷后管理-零钱包还款列表/actionPocketRepayList
     */
    public function actionPocketRepayList($type='list')
    {
        $condition = $this->getPocketTrailFilter();

        $query = UserLoanOrderRepayment::find()->orderBy([UserLoanOrderRepayment::tableName().".id" => SORT_DESC]);
        $query->joinWith([
            'loanPerson' => function (Query $query) {
                $query->select(['id','name','phone','source_id']);
            },
            'userLoanOrder' => function (Query $query) {
                $query->select(['id','order_type','sub_order_type','card_type','fund_id']);
            },
        ])->where($condition)->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD);
        //导出逾期表
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportOverdue($query,$type);
        }
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
//            $count = 99999999;
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($db);

        return $this->render('repay-list', array(
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ));
    }

    /**
     * @param string $type
     * @return string|void
     * @name 借款管理-贷后管理-零钱包还款对账/actionPocketRepayAccount
     */
    public function actionPocketRepayAccount($type='list')
    {
        $condition = $this->getPocketTrailFilter(1);

        $query = UserLoanOrderRepayment::find()->orderBy([UserLoanOrderRepayment::tableName().".id" => SORT_DESC]);
        $query->joinWith([
            'loanPerson' => function (Query $query) {
                $query->select(['id','name','phone','source_id']);
            },
            'userLoanOrder' => function (Query $query) {
                $query->select(['id','order_type','sub_order_type','card_type','fund_id']);
            },
        ])->where($condition)->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD);
        //导出逾期表
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportOverdue($query,$type);
        }
        $countQuery = clone $query;
        $db = Yii::$app->get('db_kdkj_rd');

        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
//            $count = 99999999;
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all($db);

        return $this->render('account-list', array(
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ));
    }

    /**
     * 添加冻结订单
     * @param  string $id   借款订单
     * @return string|void
     * @name 借款管理-贷后管理-零钱包还款列表/force-frozen-order
     */
    public function actionForceFrozenOrder($id){
        // 设置锁定时间
        $frozen_day_list = [
            "1" => "1天",
            "2" => "2天",
            "3" => "3天",
            "4" => "4天",
            "5" => "5天",
            "6" => "6天",
            "7" => "7天",
        ];
        $info = UserLoanOrderRepayment::findOne(intval($id));
        if(is_null($info)){
            return $this->redirectMessage('还款记录不存在',self::MSG_ERROR);
        }

        if ($this->request->getIsPost()) {
            $frozen_day  = intval($this->request->post("free_day",0));
            // 处理延期天数
            if ($frozen_day > 0 && $frozen_day <= 7) {
                // 处理逾期
                $true_plan_repayment_time  = $info->plan_fee_time;
                if ($info->is_overdue){
                    $add_day = intval($info->overdue_day + $frozen_day);
                    $info->plan_fee_time = strtotime("+{$add_day} day",$info->plan_fee_time);
                }else{
                    $info->plan_fee_time = strtotime("+{$frozen_day} day",$info->plan_fee_time);
                }
                $info->plan_repayment_time = $info->plan_fee_time - 86400;

                if($info->save()){
                    // 处理冻结日志
                    $operation_name = Yii::$app->user->identity->username;

                    if(UserLoanOrderForzenRecord::insertRecord($info->order_id,$info->user_id,$frozen_day,$true_plan_repayment_time,$operation_name)){
                        return $this->redirectMessage('操作冻结订单成功',self::MSG_SUCCESS);
                    }
                }
                return $this->redirectMessage('操作冻结订单失败',self::MSG_ERROR);
            }else{
                return $this->redirectMessage('冻结天数不在规定的范围内',self::MSG_ERROR);
            }
        }
        // 添加--冻结记录
        $condition = sprintf("order_id=%d",$info->order_id);
        $userForzenRecord = UserLoanOrderForzenRecord::find()->where($condition)->orderBy("id desc")->limit(5)->all();
        $serForzenLog = UserLoanOrderForzenRecordLog::find()->where($condition)->orderBy("id desc")->limit(10)->all();

        $loanPerson = LoanPerson::findOne($info['user_id']);
        if(is_null($loanPerson)){
            return $this->redirectMessage('借款人不存在',self::MSG_ERROR);
        }
        $order = UserLoanOrder::findOne($info['order_id']);
        if(is_null($order)){
            return $this->redirectMessage('借款订单不存在',self::MSG_ERROR);
        }

        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }
        $common = $result['common'];

        return $this->render('force-frozen-order', array(
            'common' => $common,
            'user_forzen_record' => $userForzenRecord,
            'user_forzen_log' => $serForzenLog,
            'forzen_list' => $frozen_day_list,
        ));
    }

    /**
     * 处理冻结订单
     * forzen-order
     */
    public function actionForzenOrder($forzen_id){
        //
        $userForzenRecord = UserLoanOrderForzenRecord::findOne($forzen_id);

        return $this->render('force-frozen-order', array(
            'user_forzen_record' => $userForzenRecord,
        ));
    }

    private function _exportOverdue($query,$type){
        Util::cliLimitChange(1024);
        $check = $this->_canExportData();
        if(!$check){
            return $this->redirectMessage('无权限', self::MSG_ERROR);
        }{
            if($type=='list'){
                $this->_setcsvHeader('零钱包还款列表导出.csv');
            }else{
                $this->_setcsvHeader('逾期中列表导出.csv');
            }
            //$datas = $query->all(UserLoanOrderRepayment::getDb_rd());

            $max_id = 0;
            $datas = $query->andWhere(['>','tb_user_loan_order_repayment.id',$max_id])->limit(1000)->orderBy(['tb_user_loan_order_repayment.id' => SORT_ASC])->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
            $items = [];
            $fund = LoanFund::getAllFundArray();
            $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
            while ($datas) {
                foreach($datas as $value){
                    $items[] = [
                        '资方' =>!empty(($fund[$value['userLoanOrder']['fund_id']]))?$fund[$value['userLoanOrder']['fund_id']]:$fund_koudai->name,
                        '订单号' =>$value['order_id'],
                        '用户ID' =>$value['user_id'],
                        '姓名' => isset($value['loanPerson']) && $value['loanPerson'] ?$value['loanPerson']['name']:'',
                        //'手机号' => isset($value['loanPerson']) && $value['loanPerson'] ?$value['loanPerson']['phone']:'',
                        '本金' => isset($value['principal'])?sprintf("%0.2f",$value['principal']/100):'',
                        '利息' => isset($value['interests'])?sprintf("%0.2f",$value['interests']/100):'',
                        '滞纳金' => isset($value['late_fee'])?sprintf("%0.2f",$value['late_fee']/100):'',
                        '已还金额' => isset($value['true_total_money'])?sprintf("%0.2f",$value['true_total_money']/100):'',
                        '抵扣券金额' => isset($value['coupon_money'])?sprintf("%0.2f",$value['coupon_money']/100):'',
                        '放款日期' => isset($value['loan_time'])?date('Y-m-d',$value['loan_time']):'',
                        '应还日期' => isset($value['plan_fee_time'])?date('Y-m-d',$value['plan_fee_time']):'',
                        '逾期天数' => isset($value['overdue_day'])?$value['overdue_day']:'',
                    ];
                }
                $max_id = $value['id'];
                $datas = $query->andWhere(['>','tb_user_loan_order_repayment.id',$max_id])->limit(1000)->orderBy(['tb_user_loan_order_repayment.id' => SORT_ASC])->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
            }
            echo $this->_array2csv($items);
        }
        exit;
    }

    /**
     * @return string|void
     * @name 借款管理-贷后管理-逾期中列表/actionPocketOverdueList
     */
    public function actionPocketOverdueList()
    {
        $_GET['is_overdue'] = UserLoanOrderRepayment::OVERDUE_YES;
        $_GET['!status'] = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
        return $this->actionPocketRepayList('overdue');
    }


    /**
    *@name 零钱贷还款初审列表
    */
    public function actionPocketRepayTrailList()
    {
        $_GET['status'] = UserLoanOrderRepayment::STATUS_CHECK;
        return $this->actionPocketRepayList('trail');
    }
    //零钱贷还款复审列表
    public function actionPocketRepayRetrailList()
    {
        $_GET['status'] = UserLoanOrderRepayment::STATUS_PASS;
        return $this->actionPocketRepayList('retrail');
    }
    /**
     * @name 零钱贷扣款列表/actionPocketRepayCutList
    **/
    public function actionPocketRepayCutList()
    {
        $_GET['status'] = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
        return $this->actionPocketRepayList('cut');
    }
    /**
     * 零钱贷还款列表过滤
     * @return string
     */
    protected function getPocketTrailFilter($type=0) {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND  ".UserLoanOrderRepayment::tableName().".id = " . intval($search['id']);
            }
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND  ".LoanPerson::tableName().".id = " . intval($search['user_id']);
            }
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".order_id = " . intval($search['order_id']);
            }
            if (isset($search['status']) && ($search['status'])!== '') {
                if($search['status']==4&&$type==1){
                    $condition .= " AND ".UserLoanOrderRepayment::tableName().".true_total_money > 0 ";
                }else{
                    $condition .= " AND ".UserLoanOrderRepayment::tableName().".status = " . intval($search['status']);
                }
            }
            if (isset($search['!status'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".status <> " . intval($search['!status']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition .= " AND ".LoanPerson::tableName().".name =  '{$search['name']}'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND ".LoanPerson::tableName().".phone = '{$search['phone']}'";
            }
            if (isset($search['sub_order_type']) && $search['sub_order_type'] != -1) {
                $condition .= " AND sub_order_type = " . $search['sub_order_type'];
            }
            if (isset($search['card_type']) && $search['card_type'] != -1) {
                $condition .= " AND card_type = " . $search['card_type'];
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".plan_fee_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".plan_fee_time <= " . strtotime($search['endtime']);
            }
            if (isset($search['sq_begintime']) && !empty($search['sq_begintime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".apply_repayment_time >= " . strtotime($search['sq_begintime']);
            }
            if (isset($search['sq_endtime']) && !empty($search['sq_endtime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".apply_repayment_time <= " . strtotime($search['sq_endtime']);
            }
            if (isset($search['r_begintime']) && !empty($search['r_begintime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".true_repayment_time >= " . strtotime($search['r_begintime']);
            }
            if (isset($search['r_endtime']) && !empty($search['r_endtime'])) {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".true_repayment_time <= " . strtotime($search['r_endtime']);
            }
            if (isset($search['is_overdue']) && $search['is_overdue'] !== '') {
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".is_overdue = " . intval($search['is_overdue']);
            }
            if(isset($search['operate_date']) && !empty($search['operate_date'])){
                if($type==1){
                    $operate_date=trim($search['operate_date']);
                    $operate_begin_date=strtotime('+7 day',strtotime($operate_date));
                    $operate_end_date=strtotime('+8 day',strtotime($operate_date));
                    $condition .= " AND  ".UserLoanOrderRepayment::tableName().".plan_fee_time >= '" .$operate_begin_date ."'";
                    $condition .= " AND  ".UserLoanOrderRepayment::tableName().".plan_fee_time < '" .$operate_end_date ."'";
                }else{
                    $condition .= " AND  FROM_UNIXTIME(".UserLoanOrderRepayment::tableName().".loan_time,'%Y-%m-%d')= '" . $search['operate_date']."'";
                }
            }
            if(isset($search['overdue_from_day']) && !empty($search['overdue_from_day'])){
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".overdue_day >= " . intval($search['overdue_from_day']);
            }
            if(isset($search['overdue_to_day']) && !empty($search['overdue_to_day'])){
                $condition .= " AND ".UserLoanOrderRepayment::tableName().".overdue_day <= " . intval($search['overdue_to_day']);
            }
            //财务管理--每日未还本金列表链接查看本金明细情况
            if(isset($search['view_type']) && !empty($search['view_type'])){
                switch($search['view_type']) {

                    case  'true_total_principal': //已还金额
                        if($type==0){
                            $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_NO;
                        }
                        break;
                    case  'not_yet_normal_principal': //未逾期的未还本金
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_NO;
                        break;
                    case  's1_principal':
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_YES;
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day >= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_ONE]['min'];
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day <= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_ONE]['max'];

                        break;
                    case  's2_principal':
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_YES;
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day >= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_TWO]['min'];
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day <= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_TWO]['max'];
                        break;
                    case  's3_principal':
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_YES;
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day >= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_THREE]['min'];
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day <= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_THREE]['max'];
                        break;
                    case  's4_principal':
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_YES;
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day >= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_FOUR]['min'];
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day <= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_FOUR]['max'];
                        break;
                    case  's5_principal':
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".is_overdue = " . UserLoanOrderRepayment::OVERDUE_YES;
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day >= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_FIVE]['min'];
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".overdue_day <= " . LoanCollectionOrder::$overdue_day[LoanCollectionOrder::LEVEL_FIVE]['max'];
                        break;
                    case  'not_yet_principal': //未还本金
                        $condition .= " AND " . UserLoanOrderRepayment::tableName() . ".status <> " . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                        $condition .=" AND ". UserLoanOrderRepayment::tableName() . ".principal > ".UserLoanOrderRepayment::tableName().".true_total_money";
                        break;
                }
            }

            //资方信息
            if (isset($search['fund_id']) && !empty($search['fund_id']) && $search['fund_id'] >0 ) {
                $condition .= "  AND ".UserLoanOrder ::tableName().".fund_id ".($search['fund_id']== LoanFund::ID_KOUDAI ? " IN (0, ".(int)($search['fund_id']).")" : "= " . (int)($search['fund_id']));
            }

        }
        return $condition;
    }


    /**
     * 房租贷还款查看
     * @return string
     */
    public function actionFzdView(){
        $id = intval($this->request->get('id'));
        $result = $this->commonFzdView($id);
        if(!$result){
            return $this->redirectMessage($this->message, self::MSG_ERROR);
        }
        return $this->render('fzd-view',array(
            'private'=>$result['private'],
            'common'=>$result['common'],
        ));
    }

    private function commonFzdView($id){
        $repaymentPeriod = UserRepaymentPeriod::findOne($id);
        if(is_null($repaymentPeriod)){
            $this->message = '分期还款记录不存在';
            return false;
        }
        $card_id = $repaymentPeriod['card_id'];
        $loanOrder = UserLoanOrder::findOne($repaymentPeriod['loan_order_id']);
        if(is_null($loanOrder)){
            $this->message = '借款订单不存在';
            return false;
        }
        $repayment = UserRepayment::findOne($repaymentPeriod['repayment_id']);
        if(is_null($repayment)){
            $this->message = '总还款记录不存在';
            return false;
        }
        $last_repayment = UserRepaymentPeriod::find()->where(['repayment_id'=>$repayment['id']])->orderBy(['id'=>SORT_DESC])->one(Yii::$app->get('db_kdkj_rd'));
        if(is_null($last_repayment)){
            $this->message = '最后一条分期还款记录不存在';
            return false;
        }
        $last_repayment_time = date('Y-m-d',$last_repayment['plan_repayment_time']);
        $private = [
            'repaymentPeriod'=>$repaymentPeriod,
            'repayment'=>$repayment,
        ];
        $service = Yii::$container->get('repaymentService');
        $common = $service->repaymentCommonView($loanOrder,$card_id,$last_repayment_time);
        if(!$common){
            $this->message = $service->message;
            return false;
        }

        return [
            'private'=>$private,
            'common'=>$common,
        ];

    }


    /**
     * 房租贷还款初审
     * @return string
     */
    public function actionFzdTrail(){
        $id = intval($this->request->get('id'));
        $result = $this->commonFzdView($id);
        if(!$result){
            return $this->redirectMessage($this->message, self::MSG_ERROR);
        }
        $private = $result['private'];
        $common = $result['common'];
        if ($this->request->getIsPost()) {
            $operation = $this->request->post('operation');
            $remark = $this->request->post('remark');
            if(empty($remark)) {
                return $this->redirectMessage('备注不能为空', self::MSG_ERROR);
            }

            $log = new UserOrderLoanCheckLog();
            $repaymentPeriod = $private['repaymentPeriod'];
            $order = $common['loanOrder'];
            $transaction = Yii::$app->db_kdkj->beginTransaction();

            if ($operation == '1') {
                $log->order_id = $order['id'];
                $log->repayment_id = $repaymentPeriod['id'];
                $log->before_status = $repaymentPeriod['status'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
                $log->after_status = UserRepaymentPeriod::STATUS_WAIT;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;

                $repaymentPeriod->status = UserRepaymentPeriod::STATUS_WAIT;
                $repaymentPeriod->remark = $remark;
                $repaymentPeriod->admin_username = Yii::$app->user->identity->username;


            } elseif($operation == '2') {
                $log->order_id = $order['id'];
                $log->repayment_id = $repaymentPeriod['id'];
                $log->before_status = $repaymentPeriod['status'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
                $log->after_status = UserRepaymentPeriod::STATUS_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;

                $repaymentPeriod->status = UserRepaymentPeriod::STATUS_CANCEL;
                $repaymentPeriod->remark = $remark;
                $repaymentPeriod->admin_username = Yii::$app->user->identity->username;
            }
            try {
                if ($log->validate() && $repaymentPeriod->validate()) {
                    if($log->save() && $repaymentPeriod->save()) {
                        $transaction->commit();
                        return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('house-repay-trail-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('审核失败', self::MSG_ERROR);
            }
        }
        return $this->render('fzd-trail',array(
            'private'=>$private,
            'common'=>$common,
        ));
    }



    /**
     * 房租贷还款扣款
     * @return string
     */
    public function actionFzdCut(){
        $id = intval($this->request->get('id'));
        $result = $this->commonFzdView($id);
        if(!$result){
            return $this->redirectMessage($this->message, self::MSG_ERROR);
        }
        $private = $result['private'];
        $common = $result['common'];
        if ($this->request->getIsPost()) {
            $operation = $this->request->post('operation');
            $remark = $this->request->post('remark');
            if(empty($remark)) {
                return $this->redirectMessage('备注不能为空', self::MSG_ERROR);
            }

            $log = new UserOrderLoanCheckLog();
            $repaymentPeriod = $private['repaymentPeriod'];
            $order = $common['loanOrder'];
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            if ($operation == '1') {
                $result = $this->getFzdRepayInfo($id);
                if(!$result){
                    return $this->redirectMessage('操作失败',self::MSG_ERROR);
                }
                $log->order_id = $order['id'];
                $log->repayment_id = $repaymentPeriod['id'];
                $log->before_status = $repaymentPeriod['status'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
                $log->after_status = UserRepaymentPeriod::STATUS_LOAN_COMPLING;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_DKK;

                $repaymentPeriod->status = UserRepaymentPeriod::STATUS_LOAN_COMPLING;
                $repaymentPeriod->remark = $remark;
                $repaymentPeriod->admin_username = Yii::$app->user->identity->username;
            } elseif($operation == '2') {
                $log->order_id = $order['id'];
                $log->repayment_id = $repaymentPeriod['id'];
                $log->before_status = $repaymentPeriod['status'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD;
                $log->after_status = UserRepaymentPeriod::STATUS_REPAY_REPEAT_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_DKK;

                $repaymentPeriod->status = UserRepaymentPeriod::STATUS_REPAY_REPEAT_CANCEL;
                $repaymentPeriod->remark = $remark;
                $repaymentPeriod->admin_username = Yii::$app->user->identity->username;
            }
            try {
                if ($log->validate() && $repaymentPeriod->validate()) {
                    if($log->save() && $repaymentPeriod->save()) {
                        $transaction->commit();
                        return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('house-repay-cut-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('审核失败', self::MSG_ERROR);
            }
        }
        return $this->render('fzd-cut',array(
            'private'=>$private,
            'common'=>$common,
        ));

    }

    /**
     * @param $id
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     *
     */
    private function commonPocketView($id){
        $Repayment = UserLoanOrderRepayment::findOne(intval($id));
        if(is_null($Repayment)){
            $this->message = '还款记录不存在';
            return false;
        }
        $last_repayment_time = date('Y-m-d',$Repayment['plan_repayment_time']);
        $loanOrder = UserLoanOrder::findOne($Repayment['order_id']);
        if(is_null($loanOrder)){
            $this->message = '借款订单不存在';
            return false;
        }
        $private = [
            'repayment'=>$Repayment,
        ];
        $card_id = $Repayment['card_id'];
        $service = Yii::$container->get('repaymentService');
        $common = $service->repaymentCommonView($loanOrder,$card_id,$last_repayment_time);
        if(!$common){
            $this->message = $service->message;
            return false;
        }

        return [
            'private'=>$private,
            'common'=>$common,
        ];

    }

    /**
     * @param $id
     * @return string
     * @name 借款管理-贷后管理-零钱包还款列表-查看/actionPocketView
     */
    public function actionPocketView($id){
       $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }
        $common = $result['common'];
        $private = $result['private'];

        return $this->render('pocket-view', [
            'common' => $common,
            'private'=>$private,
        ]);
    }

    /**
     * @name 零钱初审 ---先逻辑修改成直接跳到待扣款
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function actionPocketTrail($id){
        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }

        $common = $result['common'];
        $private = $result['private'];
        if ($this->request->getIsPost()) {
            $operation = $this->request->post('operation');
            $remark = $this->request->post('remark');
            $log = new UserOrderLoanCheckLog();
            $repayment = $private['repayment'];
            $loanOrder = $common['loanOrder'];
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            if ($operation == '1') {
                $log->order_id = $loanOrder['id'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                $log->repayment_id = $id;
                $log->before_status = $repayment->status;
                $log->after_status = UserLoanOrderRepayment::STATUS_WAIT;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;

                $loanOrder->status = UserLoanOrder::STATUS_REPAYING;
                $loanOrder->operator_name = Yii::$app->user->identity->username;

                $repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                $repayment->remark = $remark;
                $repayment->operator_name = Yii::$app->user->identity->username;

                $result = $this->getLqRepayInfo($repayment->id);
                if(!$result){
                    return $this->redirectMessage('操作失败', self::MSG_ERROR);
                }

            } elseif($operation == '2') {
                $log->order_id = $loanOrder['id'];
                $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                $log->repayment_id = $id;
                $log->before_status = $repayment->status;
                $log->after_status = UserLoanOrderRepayment::STATUS_CANCEL;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;

                $loanOrder->status = UserLoanOrder::STATUS_REPAY_CANCEL;
                $loanOrder->operator_name = Yii::$app->user->identity->username;

                $repayment->status = UserLoanOrderRepayment::STATUS_CANCEL;
                $repayment->remark = $remark;
                $repayment->operator_name = Yii::$app->user->identity->username;
            }
            try {
                if ($loanOrder->validate() && $log->validate() && $repayment->validate()) {
                    if($loanOrder->save() && $log->save() && $repayment->save()) {
                        $transaction->commit();
                        return $this->redirectMessage('审核成功', self::MSG_SUCCESS, Url::toRoute('pocket-repay-trail-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('审核失败', self::MSG_ERROR);
            }
        }

        return $this->render('pocket-trail', [
            'common' => $common,
            'private'=>$private,
        ]);
    }



    //零钱扣款
    public function actionPocketCut($id){
        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }

        $common = $result['common'];
        $private = $result['private'];
        if ($this->request->getIsPost()) {
            $operation = $this->request->post('operation');
            $remark = $this->request->post('remark');
            if(empty($remark)) {
                return $this->redirectMessage('备注不能为空', self::MSG_ERROR);
            }
            $log = new UserOrderLoanCheckLog();
            $repayment = $private['repayment'];
            $loanOrder = $common['loanOrder'];
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            if ($operation == '1') {
                $result = $this->getLqRepayInfo($id);
                if(!$result){
                    return $this->redirectMessage('操作失败', self::MSG_ERROR);
                }
                $log->order_id = $loanOrder['id'];
                $log->before_status = $repayment->status;
                $log->after_status = UserLoanOrderRepayment::STATUS_WAIT;
                $log->repayment_id = $id;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_DKK;

                $loanOrder->status = UserLoanOrder::STATUS_REPAYING;
                $loanOrder->operator_name = Yii::$app->user->identity->username;

                $repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                $repayment->remark = $remark;
                $repayment->operator_name = Yii::$app->user->identity->username;
            } elseif($operation == '2') {
                $log->order_id = $loanOrder['id'];
                $log->before_status = $repayment->status;
                $log->after_status = UserLoanOrderRepayment::STATUS_REPAY_CANCEL;
                $log->repayment_id = $id;
                $log->operator_name = Yii::$app->user->identity->username;
                $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                $log->remark = $remark;
                $log->operation_type = UserOrderLoanCheckLog::REPAY_DKK;

                $loanOrder->status = UserLoanOrder::STATUS_REPAYING_CANCEL;
                $loanOrder->operator_name = Yii::$app->user->identity->username;

                $repayment->status = UserLoanOrderRepayment::STATUS_REPAY_CANCEL;
                $repayment->remark = $remark;
                $repayment->operator_name = Yii::$app->user->identity->username;
            }
            try {
                if ($repayment->validate() && $log->validate() && $loanOrder->validate()) {
                    if($repayment->save() && $log->save() && $loanOrder->save()) {
                        $transaction->commit();
                        return $this->redirectMessage('扣款成功', self::MSG_SUCCESS, Url::toRoute('pocket-repay-cut-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('扣款失败', self::MSG_ERROR);
            }
        }

        return $this->render('pocket-cut', [
            'common' => $common,
            'private'=>$private,
        ]);
    }

    //获取零钱贷扣款所需的信息
    private function getLqRepayInfo($user_loan_order_repayment_id){
        $service = Yii::$container->get('orderService');
        UserLoanOrderRepayment::getDb()->createCommand('update '.UserLoanOrderRepayment::tableName().' set debit_times=debit_times+1 where id=:id',[':id'=>$user_loan_order_repayment_id])->execute();
        return $service->getLqRepayInfo($user_loan_order_repayment_id);
    }

    //获取房租贷扣款所需的信息
    private function getFzdRepayInfo($user_repayment_period_id){
        $info = UserRepaymentPeriod::findOne($user_repayment_period_id);
        $data = [];
        $data['user_id'] = $info['user_id'];
        $data['debit_card_id'] = $info['card_id'];
        $data['type'] = 1;
        $data['repayment_id'] = $info['repayment_id'];
        $data['repayment_peroid_id'] = $info['id'];
        $data['loan_record_id'] = $info['loan_order_id'];
        $data['plan_repayment_money'] = $info['plan_repayment_principal']+$info['plan_repayment_interest']+$info['plan_late_fee'];
        $data['plan_repayment_principal'] = $info['plan_repayment_principal'];
        $data['plan_repayment_interest'] = $info['plan_repayment_interest'];
        $data['plan_repayment_late_fee'] = $info['plan_late_fee'];
        $data['plan_repayment_time'] = $info['plan_repayment_time'];
        $service = Yii::$container->get('financialService');
        $result = $service->createFinancialDebitRecord($data);
        if($result['code'] != 0){
            Yii::error([
                'line' => __LINE__,
                'method' => __METHOD__,
                'message' => $result['message']
            ]);
            return false;
        }
        return true;

    }
    /**
     * 加入催收名单
     * @param unknown $id
     * @throws Exception
     * @name 加入催收
     */
    public function actionAddCollection($id){
        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }

        $common = $result['common'];
        $private = $result['private'];
        $repayment = $private['repayment'];
        $loanOrder = $common['loanOrder'];
        $loanType = \common\models\UserLoanCollection::LOAN_TYPE_KD;
        if(\common\models\UserLoanCollection::checkExist($loanOrder['user_id'],$loanType,$repayment['id'])){
            return $this->redirectMessage('已存在入催记录', self::MSG_ERROR);
        }
        $params = [
                'loan_id' => $loanOrder['id'],
                'repayment_id' => $repayment['id'],
                'loan_type' => $loanType,
                'user_id' => $loanOrder['user_id'],
                'loan_status' => $repayment['status'],
                'plan_time' => $repayment['plan_repayment_time'] + 86400,
                'is_child' => 0,
        ];
        try {
            if (\common\models\UserLoanCollection::saveRecord($params)) {
                return $this->redirectMessage('入催成功', self::MSG_SUCCESS);
            } else {
                throw new Exception;
            }
        } catch (\Exception $e) {
            return $this->redirectMessage('入催失败:'.$e->getMessage(), self::MSG_ERROR);
        }
    }
    /**
     * @name 还款凭证
     */
    public function actionRepaymentVoucher($id){
        $Repayment = UserLoanOrderRepayment::findOne(intval($id));
        $sql = UserProofMateria::find()->where(['user_id'=>$Repayment['order_id']])->andWhere(['=','type',13])->select(['id','pic_name','url','created_at'])->limit(1)->asArray()->one();
        return $this->render('repayment-voucher', [
            'sql' => $sql,
        ]);
    }

    /**
     * 置为已还款
     * @param unknown $id
     * @throws Exception
     * @name 置为已还款（1） 减免滞纳金（2） 部分还款（3）
     */
    public function actionForceFinishDebit($id){
        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }
        $common = $result['common'];
        $private = $result['private'];
        $repayment = $private['repayment'];
        $loanOrder = $common['loanOrder'];
        $aliyPayOrderId = $this->request->post('pay_order_id');
        if($this->request->getIsPost()){
            $operation = $this->request->post('operation');
            if($operation != 2 &&$this->request->post('repayment_type')==0){//还款方式需要选择
                return $this->redirectMessage('请选择还款方式', self::MSG_ERROR);
            }
            if ($operation != 2 && $this->request->post('repayment_type')== UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS)
            {
                if (!preg_match('/\d{32}/',trim($aliyPayOrderId))) return $this->redirectMessage('流水号格式错误!', self::MSG_ERROR);
                $userCreditMoneyLogs = UserCreditMoneyLog::find()->select('id,pay_order_id')
                    ->where(['pay_order_id' => $aliyPayOrderId, 'status' => UserCreditMoneyLog::STATUS_SUCCESS])
                    ->limit(1)->asArray()->all();
                if (is_array($userCreditMoneyLogs) && count($userCreditMoneyLogs) > 0) return $this->redirectMessage('此流水号已经存在!', self::MSG_ERROR);

                $aliPaymentLogs = AlipayRepaymentLog::find()->where(['alipay_order_id' => $aliyPayOrderId, 'status'=> AlipayRepaymentLog::STATUS_FAILED])->one();
                if (is_null($aliPaymentLogs)) return $this->redirectMessage('此流水号不存在或非待人工状态!', self::MSG_ERROR);
                $ture_money = $aliPaymentLogs['money']-$aliPaymentLogs['overflow_payment'];
                if ($ture_money != bcmul($this->request->post('money'),100)) return $this->redirectMessage('该流水号对应的金额不正确!'.$ture_money/100, self::MSG_ERROR);
            }
            if( $operation == 2 && $repayment['principal'] > $repayment['true_total_money']) {
                return $this->redirectMessage('实际还款金额不能小于借款本金', self::MSG_ERROR);
            }
            $money = intval(bcmul($this->request->post('money'),100));
            if($money <= 0){
                return $this->redirectMessage('实际还款金额不能为空', self::MSG_ERROR);
            }

            $remark = $this->request->post('remark');
            if(empty($remark)) {
                return $this->redirectMessage('备注不能为空', self::MSG_ERROR);
            }
            // 添加锁
            $service = Yii::$container->get('orderService');
            $loan_record_id = $loanOrder['id'];//原订单表ID
            $repayment_id = $repayment['id'];//总还款表ID
            $user_id = $repayment['user_id'];//用户ID
            $repayment_peroid_id = $repayment['debit_times'];
            $type = [FinancialDebitRecord::TYPE_YGB_LQB,FinancialDebitRecord::TYPE_YGB];
            $username = Yii::$app->user->identity->username;

            $params = $this->request->post();
            if(empty($params['order_uuid'])){
                unset($params['order_uuid']);
            }

            if( $operation == 3){ //部分还款
                $amount = $money;
                $params['operationType'] = UserOrderLoanCheckLog::REPAY_BFHK ;
                $actual_repay_money = intval($this->request->post('actual_repay_money'));
                $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$loanOrder['id'],'id'=>$repayment_id,'true_total_money'=>$actual_repay_money]);
                if(!$user_loan_order_repayment)
                    return $this->redirectMessage('此订单已被其他管理员修改', self::MSG_ERROR);
            }else{      //置为已还款 减免滞纳金
                $amount = $money-$repayment['true_total_money'];//扣款金额
                if($amount < 0){
//                $service->releaseAdminForceFinishDebitLock($id);
                    return $this->redirectMessage('实际还款金额非法', self::MSG_ERROR);
                }
                /*//如果还款日观察列表有相关记录则设置为手动置为已还款
                $suspectDebitLostRecord = SuspectDebitLostRecord::find()->where(['order_id'=>$loan_record_id,'user_id' => $user_id])->one();
                if ($suspectDebitLostRecord) {
                    try {
                        $autoDebitService = Yii::$container->get('autoDebitService');
                        if ($suspectDebitLostRecord->debit_type == SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM) {
                            $autoDebitService->handleUnCallBackDebitRecord($suspectDebitLostRecord->order_uuid,['type' => AutoDebitService::TYPE_STAFF ,'remark' => '手动置为已还款','isForceFailed' => true]);
                        } elseif ($suspectDebitLostRecord->debit_type == SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE) {
                            $autoDebitService->handleUnPayedResRecord($suspectDebitLostRecord->order_uuid,['type' => AutoDebitService::TYPE_STAFF ,'remark' => '手动置为已还款','isForceFailed' => true]);
                        } else {
                            throw new Exception("操作错误:未知扣款类型",self::MSG_ERROR);
                        }
                    } catch (Exception $ex) {
                        return $this->redirectMessage($ex->getMessage(),$ex->getCode());
                    }
                }*/
                $params['boolForceFinish'] = 1;
                $params['operationType'] = UserOrderLoanCheckLog::REPAY_XXKK;
                if($operation==2){ //减免
                    $params['operationType'] = UserOrderLoanCheckLog::REPAY_KKJM;
                    $params['operator_id'] = Yii::$app->user->identity->getId();
                    $params['rid_type'] = RidOverdueLog::TYPE_ADMIN_SYSTEM;
                    if($params['view_type']=='cuishou'){
                        $params['rid_type'] = RidOverdueLog::TYPE_CS_SYSTEM;
                    }
                }
            }
            //$params['debit_channel'] = $params['repayment_type'] == UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?UserCreditMoneyLog::Platformzfbsapy:0;
            //$back_result = $service->callbackDebitMoney($loan_record_id, $repayment_id, $repayment_peroid_id,$amount, $remark, $username,$params);
            $back_result = $service->optimizedCallbackDebitMoney($loan_record_id, $repayment_id,$amount, $remark, $username,$params);
            if($back_result && $back_result['code'] == 0){
                $order_service = $service;
                $service = Yii::$container->get('financialService');
                $service->rejectDebitRecord($user_id,$loan_record_id,$repayment_id,$type);
                if ( $operation!=3 ){
                    $order_service->releaseAdminForceFinishDebitLock($id);
                }
                //将支付宝流水表置为已处理
                if (isset($aliPaymentLogs) && !is_null($aliPaymentLogs)){
                    $aliPaymentLogs->status = AlipayRepaymentLog::STATUS_FINISH;
                    $aliPaymentLogs->operator_user = $username;
                    $aliPaymentLogs->save();
                }

                if(!empty($params['view_type'])&&$params['view_type']=='cuishou'){
                     return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute(['collection/collection-order-list']));
                }else{
                    return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute(['staff-repay/pocket-repay-list']));
                }
            }else{
//                $service->releaseAdminForceFinishDebitLock($id);
                return $this->redirectMessage('操作失败:'.$back_result['message'], self::MSG_ERROR);
            }
        }
        if($this->request->get('list_type')=='cuishou'){//若操作来至于催收管理系统列表
            $view = 'pocket-success-debit' ;
        }else{
            $view='pocket-finish-debit';
        }

        /*订单还款操作日志*/
        $logs = UserCreditMoneyLog::find()->where(['order_id'=>$loanOrder['id']])->offset(0)->limit(10)->orderBy(['id' => SORT_DESC])->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render($view, [
            'common' => $common,
            'private'=>$private,
            'money'=>$repayment['true_total_money'],
            'repayment_money'=>$repayment['principal']+$repayment['interests']+$repayment['late_fee'],
            'logs' => $logs,
        ]);
    }
    /**
     * 部分还款
     * @param unknown $id
     * @throws Exception
     * @name 部分还款
     */
    public function actionForcePartDebit($id){
        $result = $this->commonPocketView($id);
        if(!$result){
            return $this->redirectMessage($this->message,self::MSG_ERROR);
        }
        $common = $result['common'];
        $private = $result['private'];
        $repayment = $private['repayment'];
        $loanOrder = $common['loanOrder'];
        if($this->request->getIsPost()){
            if($this->request->post('repayment_type')==0){//还款方式需要选择
                return $this->redirectMessage('请选择还款方式', self::MSG_ERROR);
            }
            $money = $this->request->post('money');
            if($money == ""){
                return $this->redirectMessage('还款金额不能为空', self::MSG_ERROR);
            }
            if ($this->request->post('repayment_type')== UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS)
            {
                $aliyPayOrderId = $this->request->post('pay_order_id');
                if (!preg_match('/\d{32}/',trim($aliyPayOrderId))) return $this->redirectMessage('流水号格式错误!', self::MSG_ERROR);
                $userCreditMoneyLogs = UserCreditMoneyLog::find()->select('id,pay_order_id')
                    ->where(['pay_order_id' => $aliyPayOrderId, 'status' => UserCreditMoneyLog::STATUS_SUCCESS])
                    ->limit(1)->asArray()->all();
                if (is_array($userCreditMoneyLogs) && count($userCreditMoneyLogs) > 0) return $this->redirectMessage('此流水号已经存在!', self::MSG_ERROR);

                $aliPaymentLogs = AlipayRepaymentLog::find()->where(['alipay_order_id' => $aliyPayOrderId, 'status'=> AlipayRepaymentLog::STATUS_FAILED])->one();
                if (is_null($aliPaymentLogs)) return $this->redirectMessage('此流水号不存在!', self::MSG_ERROR);
                $ture_money = $aliPaymentLogs['money']-$aliPaymentLogs['overflow_payment'];
                if ($ture_money != bcmul($this->request->post('money'),100)) return $this->redirectMessage('该流水号对应的金额不正确!'.$ture_money/100, self::MSG_ERROR);
            }

            $amount = intval(bcmul($money,100));
            if(!$amount){
                return $this->redirectMessage('还款金额不能为空', self::MSG_ERROR);
            }
            $remark = $this->request->post('remark');
            if(empty($remark)) {
                return $this->redirectMessage('备注不能为空', self::MSG_ERROR);
            }
            $pay_order_id = $this->request->post('pay_order_id');
            if(empty($pay_order_id)) {
                return $this->redirectMessage('还款流水号不能为空', self::MSG_ERROR);
            }
            $loan_record_id = $loanOrder['id'];//原订单表ID
            $repayment_id = $repayment['id'];//总还款表ID
            $user_id = $repayment['user_id'];//用户ID
            $repayment_peroid_id = $repayment['debit_times'];
            $type = [FinancialDebitRecord::TYPE_YGB_LQB,FinancialDebitRecord::TYPE_YGB];
            $username = Yii::$app->user->identity->username;
            $params = $this->request->post();
            if(empty($params['order_uuid'])){
                unset($params['order_uuid']);
            }
            $params['operationType'] = UserOrderLoanCheckLog::REPAY_BFHK;
            //$params['debit_channel'] = $params['repayment_type'] == UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?UserCreditMoneyLog::Platformzfbsapy:0;
            $actual_repay_money = intval($this->request->post('actual_repay_money'));

            $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['order_id'=>$loanOrder['id'],'id'=>$repayment_id,'true_total_money'=>$actual_repay_money]);
            if(!$user_loan_order_repayment)
                return $this->redirectMessage('此订单已被其他管理员修改', self::MSG_ERROR);


            $service = Yii::$container->get('orderService');
            $back_result = $service->callbackDebitMoney($loan_record_id, $repayment_id, $repayment_peroid_id,$amount, $remark, $username,$params);
            if($back_result && $back_result['code'] == 0){
                $service = Yii::$container->get('financialService');
                $service->rejectDebitRecord($user_id,$loan_record_id,$repayment_id,$type);

                //将支付宝流水表置为已处理
                if (isset($aliPaymentLogs) && !is_null($aliPaymentLogs)){
                    $aliPaymentLogs->status = AlipayRepaymentLog::STATUS_FINISH;
                    $aliPaymentLogs->operator_user = $username;
                    $aliPaymentLogs->save();
                }
                return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute(['staff-repay/pocket-repay-list']));
            }else{
                return $this->redirectMessage('操作失败:'.$back_result['message'], self::MSG_ERROR);
            }
        }
        if($this->request->get('list_type')=='cuishou'){//若操作来至于催收管理系统列表
            $view = 'pocket-part-debit' ;
        }else{
            $view='pocket-part-debit';
        }

        /*订单还款操作日志*/
        $logs = UserCreditMoneyLog::find()->where(['order_id'=>$loanOrder['id']])->orderBy(['id' => SORT_DESC])->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render($view, [
                'common' => $common,
                'private'=>$private,
                'logs' => $logs,
                'money'=>$repayment['true_total_money'],
                'repayment_money'=>$repayment['principal']+$repayment['interests']+$repayment['late_fee'],
        ]);
    }

    //还款初审一键审核通过
    public function actionBatchApprove()
    {
        $query = UserLoanOrderRepayment::find()->where([UserLoanOrderRepayment::tableName().'.status'=>UserLoanOrderRepayment::STATUS_CHECK])->orderBy(["id" => SORT_DESC]);
        $all = $query->joinWith([
            'userLoanOrder' => function (Query $query) {
                $query->select(['order_type']);
            }
        ])->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD)->all(UserLoanOrderRepayment::getDb_rd());
        $i = 0;
        $count = count($all);
        $error_list = [];
        if(!empty($all)){
            foreach($all as $model) {
                try {
                    $transaction = Yii::$app->db_kdkj->beginTransaction();
                    $user_loan_order = UserLoanOrder::find()->where(['id'=>$model['order_id']])->one(Yii::$app->get('db_kdkj_rd'));
                    $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                    $user_loan_order->operator_name = Yii::$app->user->identity->username;
                    if(!$user_loan_order->save()){
                        $transaction->rollback();
                        throw new \Exception("");
                    }
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['id'=>$model['id']])->one(UserLoanOrderRepayment::getDb_rd());
                    $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                    $user_loan_order_repayment->operator_name = Yii::$app->user->identity->username;
                    if(!$user_loan_order_repayment->save()){
                        $transaction->rollback();
                        throw new \Exception("");
                    }
                    $result = $this->getLqRepayInfo($user_loan_order_repayment['id']);
                    if(!$result){
                        $transaction->rollback();
                        throw new \Exception("");
                    }
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $user_loan_order['id'];
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                    $log->repayment_id = $user_loan_order_repayment->id;
                    $log->before_status = $user_loan_order_repayment->status;
                    $log->after_status = UserLoanOrderRepayment::STATUS_WAIT;
                    $log->operator_name = Yii::$app->user->identity->username;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = "一键审核";
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;
                    if(!$log->save()){
                        $transaction->rollback();
                        throw new \Exception("");
                    }
                    $transaction->commit();
                } catch(\Exception $e){
                    $error_list[] = $model['order_id'];
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

    /**
     * @param $id
     * @return string
     * @name 借款管理-贷后管理-零钱包还款列表-备注/actionPocketRemark
     */
    public function actionPocketRemark($id)
    {
        $model = UserLoanOrderRepayment::findOne($id);
        $remark = $this->request->post('remark');
        if($remark)
        {
            $model->remark = $remark;
            if($model->save())
            {
                return $this->redirectMessage('备注成功', self::MSG_SUCCESS,Url::toRoute('staff-repay/pocket-repay-list'));
            }else
            {
                return $this->redirectMessage('备注失败', self::MSG_ERROR);
            }

        }
        return $this->render('pocket-remark',['info'=>$model]);
    }

    /**
     * @param $id
     * @return string
     * @author chengyunbo
     * @date 2017-01-12
     * @name 借款管理-贷后管理-零钱包还款列表-修改实际还款金额/actionRepayEdit
     */
    public function actionRepayEdit($id)
    {
        $model = UserLoanOrderRepayment::findOne($id);
        $query = RepayEditLog::find()->where(['order_id'=>$model->order_id])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if($query){
            $repay = $query;
            $repay ->true_total_money =  $repay ->true_repay_money;
        }else{
           $repay = $model;
        }
        if ($this->request->getIsPost()) {
            $true_total_money = $this->request->post('true_total_money');
            $true_repay_money = $this->request->post('true_repay_money');
            $remark = $this->request->post('remark');
            if(!is_numeric($true_repay_money)) {
                return $this->redirectMessage('输入错误', self::MSG_ERROR);
            }
            if(empty(trim($remark))){
               return $this->redirectMessage('修改原因不能为空', self::MSG_ERROR);
            }
            $model->true_total_money = $true_repay_money*100;
            if($model->save())
            {
                //生成修改实际还款金额操作日志
                $repayEditLog =  new RepayEditLog();
                $repayEditLog->order_id = $model->order_id;
                $operator_id = $audit_person = Yii::$app->user->id;
                Yii::info('修改实际还款金额, user_id:'.$operator_id.", username:".Yii::$app->user->identity->username);
                $repayEditLog->operator_id = $operator_id;
                $repayEditLog->true_total_money = $true_total_money*100;
                $repayEditLog->true_repay_money = $true_repay_money*100;
                $repayEditLog->true_repayment_time = $model->true_repayment_time;
                $repayEditLog->remark = $remark;
                $repayEditLog->created_at = time();
                $repayEditLog->updated_at = time();
                $repayEditLog->save();
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS,Url::toRoute('staff-repay/pocket-repay-list'));
            }else
            {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }

        }
        return $this->render('repay-edit',['repay'=>$repay]);
    }
    /**
     * @author chengyunbo
     * @date 2017-01-12
     * @name 借款管理-贷后管理-修改实际还款金额日志列表/actionRepayEditLogList
     */
    public function actionRepayEditLogList()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND ".RepayEditLog::tableName().".order_id = " . intval($search['order_id']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND ".RepayEditLog::tableName().".updated_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND ".RepayEditLog::tableName().".updated_at <= " . strtotime($search['endtime']);
            }
        }
        $query = RepayEditLog::find()->where($condition)->orderBy(['id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        //echo $query->offset($pages->offset)->limit($pages->limit)->createCommand()->getRawSql();
        $query = $query->offset($pages->offset)->limit($pages->limit)->all();
        if($query){
            $user_ids = array_column($query,'operator_id');
            $AdminUser = AdminUser::find()->select('id,username')->where("id IN(".implode(',', $user_ids).")")->asArray()->all();
            foreach($AdminUser as $us){
                $users[$us['id']] = $us['username'];
            }
            foreach($query as $k=> $q){
                $query[$k]['operator_id'] = $users[$q['operator_id']];
            }
        }
        return $this->render('repay-edit-log-list',
            [
                'repay'=>$query,
                'pages'=>$pages
            ]
        );
    }

    /**
     * 零钱贷还款卡列表过滤
     * @return string
     */
    protected function getRepayCardListFilter() {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['bank_name']) && !empty($search['bank_name'])) {
                $condition .= " AND  c.bank_id = " . intval($search['bank_name']);
            }


            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND r.plan_fee_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND r.plan_fee_time <= " . strtotime($search['endtime']);
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND r.status = " . intval($search['status']);
            }
        }
        return $condition;
    }
    /**
     * @name 借款管理-贷后管理-还款卡列表
     */
    public function actionRepayCardList($type="list")
    {
        $condition = $this->getRepayCardListFilter();
        $query = UserLoanOrderRepayment::find()->from(UserLoanOrderRepayment::tableName().' as r ')
            ->leftJoin(LoanPerson::tableName().' as p ','r.user_id = p.id')
            ->leftJoin(UserLoanOrder::tableName().'as u','r.order_id=u.id')
            ->leftJoin(CardInfo::tableName().'as c','r.card_id=c.id')
            ->where('order_type = '.UserLoanOrder::LOAN_TYPE_LQD)
            ->andWhere($condition)->select('r.*,p.name,p.phone,u.order_type,c.bank_name,c.card_no')->orderBy(['r.id'=>SORT_DESC]);
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportRepayCartList($query);
        }

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',UserLoanOrderRepayment::getDb_rd())]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(UserLoanOrderRepayment::getDb_rd());

        return $this->render('repay-card-list', array(
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ));
    }
    private function _exportRepayCartList($query){
        $this->_setcsvHeader('还款卡列表导出.csv');
        $datas = $query->all(UserLoanOrderRepayment::getDb_rd());
        $items = [];
        foreach($datas as $value){
            $items[] = [
                '姓名' => isset($value['loanPerson']) && $value['loanPerson'] ?$value['loanPerson']['name']:'',
                '手机号' => isset($value['loanPerson']) && $value['loanPerson'] ?$value['loanPerson']['phone']:'',
                '本金' => isset($value['principal'])?sprintf("%0.2f",$value['principal']/100):'',
                '利息' => isset($value['interests'])?sprintf("%0.2f",$value['interests']/100):'',
                '滞纳金' => isset($value['late_fee'])?sprintf("%0.2f",$value['late_fee']/100):'',
                '已还金额' => isset($value['true_total_money'])?sprintf("%0.2f",$value['true_total_money']/100):'',
                '放款日期' => isset($value['loan_time'])?date('Y-m-d',$value['loan_time']):'',
                '应还日期' => isset($value['plan_fee_time'])?date('Y-m-d',$value['plan_fee_time']):'',
                '逾期天数' => isset($value['overdue_day'])?$value['overdue_day']:'',
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @param $id
     * @return string
     * @name 借款管理-贷后管理-零钱包还款列表-延期/actionRepayDelay
     */
    public function actionRepayDelay($id)
    {
        $infos = UserLoanOrder::getOrderRepaymentCard($id);
        $repayment = $infos['repayment'];
        $order = $infos['order'];
        $user_id = $repayment['user_id'];
        if ($this->request->getIsPost()) {
            if(!($lock = Lock::get($lock_name = UserLoanOrder::getChangeStatusLockName($id), 30))) {
                $redirect = $this->redirectMessage('订单已经被锁定', self::MSG_ERROR, Url::toRoute('staff-repay/pocket-repay-list'));
                goto SHOW_RET;
            }

            $post = $this->request->post();
            $p_late_fee = $post['late_fee']*100;
            $p_service_fee = $post['service_fee']*100;
            $p_counter_fee = $post['counter_fee']*100;
            $p_total_money = $post['total_money']*100;
            $p_principal = $post['principal']*100;

            $day = UserLoanOrderDelay::$delay_days[$post['day']];

            $log = new UserLoanOrderDelayLog();
            $log->user_id = $user_id;
            $log->order_id = $id;
            $log->service_fee = $p_service_fee;
            $log->counter_fee = $p_counter_fee;
            $log->late_fee = $p_late_fee;
            $log->delay_day = $day;
            $log->principal = $p_principal;
            if($log->save()){
                $service = Yii::$container->get('orderService');
                $remark = ['remark'=>$post['remark'],'operator_name'=>Yii::$app->user->identity->username];
                $back_result = $service->delayLqb($log->id,$repayment,json_encode($remark));
                if(($back_result['code'] == 0)){
                    $redirect = $this->redirectMessage('延期成功', self::MSG_SUCCESS,Url::toRoute('staff-repay/pocket-repay-list'));
                } else {
                    $redirect = $this->redirectMessage('延期失败,'.$back_result['message'], self::MSG_ERROR,Url::toRoute('staff-repay/pocket-repay-list'));
                }
            } else {
                $redirect = $this->redirectMessage('延期失败,保存日志失败', self::MSG_ERROR,Url::toRoute('staff-repay/pocket-repay-list'));
            }
            SHOW_RET:
            Lock::del($lock_name);
            return $redirect;
        }

        $fees = [];
        $service_arr  = [];
        $total_moneys = [];
        //$quota = UserCreditTotal::findOne(['user_id'=>$user_id]);
        $creditChannelService = \Yii::$app->creditChannelService;
        $quota = $creditChannelService->getCreditTotalByUserAndOrder($user_id,$order['id']);
        if(!$quota){
            return $this->redirectMessage('数据非法', self::MSG_ERROR);
        }
        $delay_info = UserLoanOrderDelay::findOne(['order_id'=>$id]);
        $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'],$delay_info ? $delay_info['delay_times']:0,$order['card_type']);
        foreach(UserLoanOrderDelay::$delay_days as $idx => $day){
        // foreach(UserLoanOrderDelay::getDalayDays() as $idx => $day){
            $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'],$delay_info ? $delay_info['delay_times']:0,$order['card_type'],$day);
            $fee = Util::calcLqbLoanInfo($day, $repayment['remain_principal'], $quota->pocket_apr,$order['card_type']);
            $total_moneys[$idx] = sprintf("%0.2f",bcadd(bcadd($service_fee, $fee), $repayment['late_fee'])/100);
            $service_arr[$idx] = sprintf("%0.2f",$service_fee/100);
            $fees[$idx] = sprintf("%0.2f",$fee/100);
        }
        return $this->render('repay-delay',[
                'repayment' => $repayment,
                'fees' => $fees,
                'total_moneys' => $total_moneys,
                'service_fee' => $service_fee,
                'service_arr' => $service_arr,
        ]);
    }


    /**
     * @name 取消续期
     */
    public function actionCancelRenew($id) {
        $id = (int)$id;
        $order = UserLoanOrder::findOne($id);
        if(!$order) {
            return $this->redirectMessage('订单不存在', self::MSG_ERROR);
        }
        $max_times = UserLoanOrderDelayLog::find()->where([
            'user_id'=>(int)$order->user_id,
            'order_id'=>(int)$order->id,
        ])->andWhere('`status`='.UserLoanOrderDelayLog::STATUS_SUCCESS)->count();
        if($max_times<1) {
            return $this->redirectMessage('该订单没有续借记录，不能取消续借', self::MSG_ERROR);
        }

        if(Yii::$app->getRequest()->isPost) {
            $times = Yii::$app->getRequest()->post('times');
            if($times<=0) {
                return $this->redirectMessage('取消次数最少为1', self::MSG_ERROR);
            }
            $service = Yii::$container->get('orderService');
            /* @var $service \common\services\OrderService */
            $ret = $service->cancelRenewUseLock($id, $times);
            if($ret['code']==0) {
                return $this->redirectMessage("取消续期 {$times} 次成功", self::MSG_SUCCESS, Url::toRoute('staff-repay/pocket-repay-list'));
            } else {
                return $this->redirectMessage("取消续期 {$times} 次失败：{$ret['message']}", self::MSG_ERROR);
            }
        }

        return $this->render('cancel-renew', [
            'order'=>$order,
            'max_times' => $max_times
        ]);
    }


    /**
     * @name 将订单变成生息中
     */
    public function actionResetInterest($id) {
        $id = intval($id);

        $order = UserLoanOrder::findOne($id);
        if(!$order) {
            return $this->redirectMessage('订单不存在', self::MSG_ERROR);
        }
        $repayment = $order->userLoanOrderRepayment;
        if($repayment->status!=UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return $this->redirectMessage('订单状态不对', self::MSG_ERROR);
        }

        if(($lock = Lock::get($lock_name = UserLoanOrder::getChangeStatusLockName($id), 30))) {
            $service = Yii::$container->get('orderService');
            /* @var $service \common\services\OrderService */
            $ret = $service->resetOrderInterest($id);
        } else {
            Lock::del($lock_name);
            $ret = [
                'code'=>ErrCode::ORDER_LOCK,
                'message'=>'订单已被锁定'
            ];
        }

        if($ret['code']==0) {

            /*记录操作日志*/
            AdminOperatorLog::log($id,$this->request->get('note'));

            return $this->redirectMessage("订单置为生息中成功", self::MSG_SUCCESS);
        }
        else
            return $this->redirectMessage("订单置为生息中失败：{$ret['message']}", self::MSG_ERROR);
    }

    /**
     * @name 取消订单部分还款
     */
    public function actionCancelPartRepay($id) {
        $id = intval($id);

        $order = UserLoanOrder::findOne($id);
        if(!$order) {
            return $this->redirectMessage('订单不存在', self::MSG_ERROR);
        }
        if($order->status==UserLoanOrder::STATUS_REPAY_COMPLETE) {
            return $this->redirectMessage('订单状态不对', self::MSG_ERROR);
        } else {
            if(($lock = Lock::get($lock_name = UserLoanOrder::getChangeStatusLockName($id), 30))) {
                $service = Yii::$container->get('orderService');
                /* @var $service \common\services\OrderService */
                $ret = $service->cancelPartRepay($id);
            } else {
                Lock::del($lock_name);
                $ret = [
                    'code'=>ErrCode::ORDER_LOCK,
                    'message'=>'订单已被锁定'
                ];
            }


            if($ret['code']==0) {

                /*记录操作日志*/
                AdminOperatorLog::log($id);

                return $this->redirectMessage("取消订单部分还款成功", self::MSG_SUCCESS);
            }
            else
                return $this->redirectMessage("订取消订单部分还款失败：{$ret['message']}", self::MSG_ERROR);
        }


    }

    /**
     * @name 解除订单锁
     * @param integer $id UserLoanOrderRepayment id
     */
    public function actionReleaseLock($id) {
        $service = new \common\services\OrderService();
        $service->releaseAdminForceFinishDebitLock((int)$id);
    }

    /**
     * @name 支付宝 订单编号
     * @return array
     */
    public function actionGetAlipayLogDetail()
    {
        $this->response->format = Response::FORMAT_JSON;
        $aliyPayOrderId = $this->request->get('alipayOrderId');
        try {
            if (!preg_match('/\d{32}/',trim($aliyPayOrderId))) throw new Exception('流水号格式错误!');
            $userCreditMoneyLogs = UserCreditMoneyLog::find()->select('id,pay_order_id')->where(['pay_order_id' => $aliyPayOrderId])->limit(1)->asArray()->all();
            if (is_array($userCreditMoneyLogs) && count($userCreditMoneyLogs) > 0) throw new Exception('此流水号已经存在!');
            $aliPaymentLogs = AlipayRepaymentLog::find()->select('id,alipay_order_id,money,overflow_payment')->where(['alipay_order_id' => $aliyPayOrderId])->asArray()->limit(1)->one();
            if (is_null($aliPaymentLogs)) throw new Exception('此流水号不存在!');
            $aliPaymentLogs['money'] = sprintf("%0.2f",($aliPaymentLogs['money']-$aliPaymentLogs['overflow_payment'])/100);
            return [
                'code' => 1,
                'message' => '获取成功!',
                'data' => $aliPaymentLogs
            ];
        }catch (Exception $ex){
            return ['code'=>0,'message'=>$ex->getMessage()];
        }
    }




    /**
     * @name 重置 逾期
     * @return array
     */
    public function actionResetOverdue($id)
    {
        $userLoanOrderRepayment = UserLoanOrderRepayment::findOne($id);
        if(is_null($userLoanOrderRepayment['order_id'])){
            return $this->redirectMessage('借款订单不存在',self::MSG_ERROR);
        }
        $loanPerson = LoanPerson::findOne($userLoanOrderRepayment['user_id']);
        if(is_null($loanPerson)){
            return $this->redirectMessage('借款人不存在',self::MSG_ERROR);
        }
        $order = UserLoanOrder::findOne($userLoanOrderRepayment['order_id']);
        if(is_null($order)){
            return $this->redirectMessage('借款订单不存在',self::MSG_ERROR);
        }
        if($this->request->getIsPost()) {
            $msg = '重置失败';
            try{
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                if((int)$id <= 0){
                    $msg = '订单不存在';
                    throw new \Exception($msg);
                }
                if($userLoanOrderRepayment['overdue_day'] < 1 || $userLoanOrderRepayment['overdue_day'] > 3 ){
                    $msg = '只能重置逾期天数为1-3天';
                    throw new \Exception($msg);
                }
                $before_overdue_day = $userLoanOrderRepayment->overdue_day;
                $before_overdue_status = $userLoanOrderRepayment->is_overdue;
                $order_id = $userLoanOrderRepayment->order_id;
                $userLoanOrderRepayment->overdue_day = 0;
                $userLoanOrderRepayment->is_overdue = 0;
                if(!$userLoanOrderRepayment->save()){
                    $msg = '修改失败';
                    throw new \Exception($msg);
                }
                $admin_user_id = Yii::$app->user->identity->id;
                $admin_user_name = Yii::$app->user->identity->username;
                $overdueResetInfo = OverdueResetLog::find()->where(['repay_order_id'=>$userLoanOrderRepayment['id']])->one();
                if(!empty($overdueResetInfo)){
                    $msg = '该订单已重置过逾期状态';
                    throw new \Exception($msg);
                }
                $overdueResetLog = new OverdueResetLog();
                $overdueResetLog->repay_order_id = $id;
                $overdueResetLog->order_id = $order_id;
                $overdueResetLog->before_overdue_day = $before_overdue_day;
                $overdueResetLog->before_overdue_status = $before_overdue_status;
                $overdueResetLog->after_overdue_day = 0;
                $overdueResetLog->after_overdue_status = 0;
                $overdueResetLog->operate_person_id = $admin_user_id;
                $overdueResetLog->operate_person_name = $admin_user_name;
                if(!$overdueResetLog->save()){
                    $msg = '添加日志失败';
                    throw new \Exception($msg);
                }
                $transaction->commit();
                return $this->redirectMessage('重置成功', self::MSG_SUCCESS);
            }catch (\Exception $e){
                $transaction->rollBack();
                return $this->redirectMessage($msg, self::MSG_ERROR);
            }
        }
        $info = OverdueResetLog::find()->where(['repay_order_id'=>$id])->orderBy(['id'=>SORT_DESC])->all();
        return $this->render('reset-overdue', [
            'info' => $info,
            'loanPerson' => $loanPerson,
            'userLoanOrderRepayment' => $userLoanOrderRepayment,
            'order' => $order
        ]);
    }
}