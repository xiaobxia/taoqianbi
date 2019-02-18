<?php
namespace backend\controllers;
use common\exceptions\CodeException;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\UserCredit;
use common\models\UserCreditLog;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserRentCredit;
use common\services\MessageService;
use Yii;
use yii\base\Exception;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use common\models\FinancialLoanRecord;
use yii\data\Pagination;
use yii\db\Query;
use common\models\Company;
use common\models\UserOrderLoanCheckLog;
use common\models\LoanPersonBadInfo;
use common\models\UserCreditTotal;
use common\models\Setting;
use common\api\RedisQueue;
use common\models\Rong360LoanOrder;
use common\services\InterfaceRongService;
use common\services\AppEventService;
use common\models\fund\LoanFund;
use common\models\fund\OrderFundInfo;
use common\models\fund\LoanFundDayQuota;
use common\helpers\Lock;
use common\models\fund\OrderFundLog;

class StaffLoanController extends  BaseController
{
    /**
     * @name 借款管理-用户借款管理-放款列表/actionPocketLoanList
     */
    public function actionPocketLoanList()
    {
        $condition = $this->getPocketTrailFilter();
        $query = UserLoanOrder::find()
            ->where(["status" => UserLoanOrder::STATUS_PENDING_LOAN])
            ->andWhere($condition)->andWhere('fund_id in ('. implode(',', LoanFund::getAllowPayIds()).')')
            ->orderBy(["id" => SORT_DESC]);
        $countQuery = clone $query;

        $db = Yii::$app->get('db_kdkj_rd');

        if ($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = $db->cache(function ($db) use ($countQuery) {
                return $countQuery->count('*', $db);
            }, 3600);
        }

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson' => function (Query $query) {
                $query->select(['id','name','phone']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('loan-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }



    /**
     * 零钱贷放款列表过滤
     * @return string
     */
    protected function getPocketTrailFilter() {
        $condition = '1 = 1 and order_type ='.UserLoanOrder::LOAN_TYPE_LQD;
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $find = LoanPerson::find()->where(["like","name",$search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if(!empty($find)) {
                    $condition .= " AND user_id = ".$find['id'];
                }
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $find = LoanPerson::find()->where(["phone"=>trim($search['phone'])])->one(Yii::$app->get('db_kdkj_rd'));
                if(!empty($find)) {
                    $condition .= " AND user_id = ".$find['id'];
                }
            }
            if (isset($search['sub_order_type']) && $search['sub_order_type'] != -1) {
                $condition .= " AND sub_order_type = " . $search['sub_order_type'];
            }
            if (isset($search['card_type']) && $search['card_type'] != -1) {
                $condition .= " AND card_type = " . $search['card_type'];
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND order_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND order_time <= " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     * @name 借款管理-用户借款管理-放款列表-零钱包放款-放款/actionPocketLoan
     */
    public function actionPocketLoan($id)
    {
        #先获取锁 避免数据中途被修改
        if( $this->request->getIsPost() && !($lock=Lock::get($lock_name = UserLoanOrder::getChangeStatusLockName((int)$id), 30))) {
            $redirect_ret = $this->redirectMessage('该订单暂时处于修改锁定状态（可能正在被其他人修改），请稍后再试', self::MSG_ERROR);
            goto SHOW_RET;
        }

        //测试环境下延迟8秒查看并发情况
        if(YII_ENV==='test') {
            //sleep(8);
        }

        $creditChannelService = Yii::$app->creditChannelService;
        $information = Yii::$container->get("loanPersonInfoService")->getPocketInfo($id);
        $order = $information['info'];
        if (empty($order)) {
            $redirect_ret = $this->redirectMessage('该订单不存在',self::MSG_ERROR,Url::toRoute(['pocket/pocket-retrail-list']));
            goto SHOW_RET;
        }
        /* @var $order UserLoanOrder */
        $remark_code = Yii::$container->get("loanPersonInfoService")->getRemarkCode();
        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id' => $id])->orderBy(['id' => SORT_ASC])->all(Yii::$app->get('db_kdkj_rd'));
//        $credit_amount = UserCredit::find()->where(['user_id' => $order->user_id])->one(Yii::$app->get('db_kdkj_rd'));

        $userDetail = UserDetail::findOne(['user_id'=>$order->user_id]);
        if ($this->request->getIsPost()) {
            if($order->status != UserLoanOrder::STATUS_PENDING_LOAN){
                $redirect_ret = $this->redirectMessage('该订单状态已发生变化,请勿重复审核',self::MSG_ERROR,Url::toRoute(['pocket/pocket-retrail-list']));
                goto SHOW_RET;
            } else if(!in_array($order->fund_id, LoanFund::getAllowPayIds())) {
                $redirect_ret = $this->redirectMessage("该订单由 {$order->loanFund->name} 负责放款",self::MSG_ERROR,Url::toRoute(['pocket/pocket-retrail-list']));
                goto SHOW_RET;
            }

            $data = [];
            if($order->cardInfo){
                $data = [
                    'user_id' => $order['user_id'],
                    'bind_card_id' => $order['card_id'],
                    'business_id' => $order['id'],
                    'type' => $order['order_type'],
                    'payment_type' => FinancialLoanRecord::PAYMENT_TYPE_CMB,
                    'money' => $order['money_amount'],
                    'bank_id' => $order->cardInfo->bank_id,
                    'bank_name' => $order->cardInfo->bank_name,
                    'card_no' => $order->cardInfo->card_no,
                    'counter_fee' => $order['counter_fee'],
                ];
            }
            $operation = $this->request->post('operation');
            $code = $this->request->post('code');
            $code = explode("o",$code);
            $log = new UserOrderLoanCheckLog();

            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if ($operation == '1') {
                    if(!$data){
                        throw new \Exception('银行卡未绑定');
                    }
                    $log->order_id = $order['id'];
                    $log->repayment_id = 0;
                    $log->repayment_type = 0;
                    $log->before_status = $order['status'];
                    $log->after_status = UserLoanOrder::STATUS_PAY;
                    $log->operator_name = Yii::$app->user->identity->username;
                    $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                    $log->remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                    $log->operation_type = UserOrderLoanCheckLog::LOAN_DFK;
                    $log->head_code = $code[0];
                    $log->back_code = $code[1];

                    $financial = Yii::$container->get("financialService")->createFinancialLoanRecord($data);
                    $order->status = UserLoanOrder::STATUS_PAY;
                    $order->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                    $order->operator_name = Yii::$app->user->identity->username;
                    if ($financial['code'] == 0) {
                        $order->updateAttributes(['status','reason_remark','operator_name']);
                        $log->save(false);

                        $user_log_amount = $order['money_amount'];
                        $user_log_time   = time() - $order['order_time'];

                        // 处理借款消息日志
                        Yii::$container->get("financialService")->handleLoanMessage($order->loanPerson->phone,$user_log_amount,$user_log_time);

                        // 处理待抢金额
                        Yii::$container->get("financialService")->handleDailyQuota($user_log_amount);

                        //事件处理队列    放款成功
                        RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                            'event_name' => AppEventService::EVENT_SUCCESS_POCKET,
                            'params' => ['user_id' => $order['user_id'], 'order_id' => $order['id']],
                        ])]);

                        $transaction->commit();

                        $redirect_ret = $this->redirectMessage('放款成功', self::MSG_SUCCESS, Url::toRoute(['pocket/pocket-list']));
                    } else {
                        throw new Exception($financial['message'], $financial['code']);
                    }
                } elseif($operation == '2') {
                    $log->order_id = $order['id'];
                    $log->repayment_id = 0;
                    $log->repayment_type = 0;
                    $log->before_status = $order['status'];
                    $log->after_status = UserLoanOrder::STATUS_PENDING_CANCEL;
                    $log->operator_name = Yii::$app->user->identity->username;
                    $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                    $log->remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['backend_name'];
                    $log->operation_type = UserOrderLoanCheckLog::LOAN_DFK;
                    $log->head_code = $code[0];
                    $log->back_code = $code[1];
                    //解除用户该订单锁定额度
                    $credit = $information['credit'];
                    $credit->locked_amount = $credit->locked_amount - $order['money_amount'];

                    $order->status = UserLoanOrder::STATUS_PENDING_CANCEL;
                    $order->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                    $order->operator_name = Yii::$app->user->identity->username;
                    $log->save(false);
                    $order->updateAttributes(['status','reason_remark','operator_name']);

                    $user_credit_log = new UserCreditLog();
                    $user_credit_log->user_id = $order['user_id'];

                    switch($order->order_type){
                        case UserLoanOrder::LOAN_TYPE_LQD:
                            //更新额度表
                            $user_credit = $creditChannelService->getCreditTotalByUserAndOrder($order['user_id'], $order['id']);

                            if(false == $user_credit){
                                throw new Exception("用户信息查询失败！");
                            }
                            $user_credit->locked_amount = $user_credit->locked_amount- $order['money_amount'];
                            $user_credit->updated_at = time();
                            if(!$user_credit->save()){
                                throw new Exception("更新用户数据失败！");
                            }
                            $user_credit_log->type = UserCreditLog::REJECT_LOAN_LQD;
                            $user_credit_log->total_money=$user_credit->amount;
                            $user_credit_log->used_money=$user_credit->used_amount;
                            $user_credit_log->unabled_money=$user_credit->locked_amount;
                            break;
                        case UserLoanOrder::LOAN_TYPR_FZD:
                            //更新额度表
                            $user_rent_credit = $creditChannelService->getCreditTotalByUserAndOrder($order['user_id'], $order['id']);

                            if(false == $user_rent_credit){
                                throw new Exception("用户信息查询失败！");
                            }
                            $user_rent_credit->locked_amount = $user_rent_credit->locked_amount- $order['money_amount'];
                            $user_rent_credit->updated_at = time();
                            if(!$user_rent_credit->save()){
                                throw new Exception("更新用户数据失败！");
                            }
                            $user_credit_log->type = UserCreditLog::REJECT_LOAN_FZD;
                            $user_credit_log->total_money=$user_rent_credit->amount;
                            $user_credit_log->used_money=$user_rent_credit->used_amount;
                            $user_credit_log->unabled_money=$user_rent_credit->locked_amount;
                            break;
                        case UserLoanOrder::LOAN_TYPE_FQSC:
                            throw new Exception("不支持该业务订单！");
                        default:
                            throw new Exception("不支持该业务订单！");
                    }

                    $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
                    $user_credit_log->operate_money = $order['money_amount'];
                    $user_credit_log->created_at = time();
                    $user_credit_log->created_ip = $this->request->getUserIP();

                    if(!$user_credit_log->save()){
                        throw new Exception("更新用户数据失败！");
                    }

                    //给资方添加额度
                    if($order->fund_id) {
                        $order_fund_info = OrderFundInfo::findOne(['fund_id'=>(int)$order->fund_id, 'order_id'=>$order->id]);
                        $date = date('Y-m-d', $order_fund_info->created_at);
                        $order->loanFund->increaseQuota($order->money_amount, $date, $order->money_amount);

                        $order_fund_info->changeStatus(OrderFundInfo::STATUS_REMOVED, "订单放款失败，删除当前数据记录");
                    }

                    $transaction->commit();

                    $message_service = new MessageService();
                    $ret = $message_service->sendMessageLoanYgbReject($order['user_id'],$id);

                    //触发订单的放款拒绝事件 自定义的数据可添加到custom_data里
                    $order->trigger(UserLoanOrder::EVENT_AFTER_PAY_REJECTED, new \common\base\Event(['custom_data'=>[]]));

                    $redirect_ret =  $this->redirectMessage('驳回成功', self::MSG_SUCCESS, Url::toRoute(['pocket/pocket-list']));
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                $redirect_ret = $this->redirectMessage('放款失败' . $e->getMessage(), self::MSG_ERROR);
            }
        }

        #解除锁 跳转信息 或  显示页面
        SHOW_RET:
        if(!empty($lock)) {
            Lock::del($lock_name);
        }
        if(!empty($redirect_ret)) {
            return $redirect_ret;
        }

        return $this->render('loan-view', array(
            'info' => $order,
//            'credit' => $credit_amount,
            'loanPerson' => $order->loanPerson,
            'userDetail' => $userDetail,
            'card' => $order->cardInfo,
            'trail_log' => $trail_log,
            'pass_tmp'=>$remark_code['pass_tmp'],
            'reject_tmp'=>$remark_code['reject_tmp'],
        ));
    }

        //零钱贷放款列表
    public function actionHouseLoanList()
    {
        $condition = $this->getHouseTrailFilter();
        $query = UserLoanOrder::find()->where(["status" => UserLoanOrder::STATUS_PENDING_LOAN])->andWhere($condition)->orderBy(["id" => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count(Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson' => function (Query $query) {
                $query->select(['id','name','phone']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('house-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }


    /**
     * 零钱贷放款列表过滤
     * @return string
     */
    protected function getHouseTrailFilter()
    {
        $condition = '1 = 1 and order_type ='.UserLoanOrder::LOAN_TYPR_FZD;
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['company_name']) && !empty($search['company_name'])) {
                $find = Company::find()->where(["like","company_name",trim($search['company_name'])])->one(Yii::$app->get('db_kdkj_rd'));
                $condition .= " AND company_id = ".$find['id'];
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND phone = " . ($search['phone']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND order_time >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND order_time <= " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }

    /**
     * @name 一键审核通过零钱包放款
     */
    public function actionBatchApprove()
    {
        $allow_pay_ids = LoanFund::getAllowPayIds();
        $all = UserLoanOrder::find()->where(["status" => UserLoanOrder::STATUS_PENDING_LOAN])->andWhere(['order_type'=>UserLoanOrder::LOAN_TYPE_LQD])->andWhere(['fund_id'=>$allow_pay_ids])->orderBy(["id" => SORT_DESC])->all(Yii::$app->get('db_kdkj_rd'));

        $i = 0;
        $count = count($all);
        $error_list = [];
        if(!empty($all)){
            foreach($all as $model) {
                //获取锁防止并发处理
                if(!($lock=Lock::get($lock_name = UserLoanOrder::getChangeStatusLockName((int)$model['id']), 30))) {
                    $error_list[] = $model['id'];
                    $i++;
                    continue;
                }
                try {
                    $transaction = Yii::$app->db_kdkj->beginTransaction();
                    $code[0] = "A1";
                    $code[1] = "01";
                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $model['id'];
                    $log->repayment_id = 0;
                    $log->repayment_type = 0;
                    $log->before_status = $model['status'];
                    $log->after_status = UserLoanOrder::STATUS_PAY;
                    $log->operator_name = Yii::$app->user->identity->username;
                    $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                    $log->remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['backend_name'];
                    $log->operation_type = UserOrderLoanCheckLog::LOAN_DFK;
                    $log->head_code = "A1";
                    $log->back_code = "01";
                    $card = CardInfo::find()->where(['id'=>$model->card_id])->one(Yii::$app->get('db_kdkj_rd'));
                    $data = [
                        'user_id' => $model['user_id'],
                        'bind_card_id' => $model['card_id'],
                        'business_id' => $model['id'],
                        'type' => $model['order_type'],
                        'payment_type' => FinancialLoanRecord::PAYMENT_TYPE_CMB,
                        'money' => $model['money_amount'],
                        'bank_id' => $card['bank_id'],
                        'bank_name' => $card['bank_name'],
                        'card_no' => $card['card_no'],
                        'counter_fee' => $model['counter_fee'],
                    ];

                    $financial = Yii::$container->get("financialService")->createFinancialLoanRecord($data);
                    $model->status = UserLoanOrder::STATUS_PAY;
                    $model->reason_remark = LoanPersonBadInfo::$pass_code[$code[0]]['child'][$code[1]]['frontedn_name'];
                    $model->operator_name = Yii::$app->user->identity->username;
                    if ($financial['code'] == 0) {
                        if ($model->validate() && $model->save() && $log->save()) {
                            $transaction->commit();
                            $message_service = new MessageService();
                            $message = "有客户申请了一笔'" . UserLoanOrder::$loan_type[UserLoanOrder::LOAN_TYPE_LQD] . "'借款，需要进行放款，请及时处理";
                            //$message_service->sendWeixin(LoanPerson::WEIXIN_NOTICE_YGD_ALL_LOAN_CWSH, $message);
                            //$message_service->sendMessageLoanYgbPass($model['user_id'], $model['id']);
                        } else {
                            throw new \Exception("");
                        }
                    } else {
                        throw new \Exception("");
                    }

                    // todo : 更新用户放款日志 -- done
                    $user_id = $model['user_id'];
                    $loan_person = LoanPerson::findOne(['id'=>$user_id]);
                    if ($loan_person) {
                        if ($loan_person->phone) {
                            $user_log_amount = $model['money_amount'];
                            $user_log_time   = time() - $model['order_time'];

                            // 处理借款消息日志
                            Yii::$container->get("financialService")->handleLoanMessage($loan_person->phone,$user_log_amount,$user_log_time);
                            // 处理待抢金额
                            Yii::$container->get("financialService")->handleDailyQuota($user_log_amount,$model['card_type']);
                        }
                    }
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
            return $this->redirectMessage("无可放款的订单", self::MSG_NORMAL);
        }
    }
}