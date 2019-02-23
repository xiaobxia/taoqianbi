<?php
namespace console\controllers;

use common\helpers\ArrayHelper;
use common\helpers\MailHelper;
use common\helpers\MessageHelper;
use common\models\LoanBlackList;
use common\models\UserLoanOrderRepayment;
use common\services\CreditCheckService;
use common\services\UserService;
use Yii;
use yii\base\Exception;

use common\api\RedisQueue;
use common\models\CreditCheckHitMap;
use common\models\CreditJxlQueue;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\UserCreditData;
use common\models\UserCreditLog;
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use common\models\UserVerification;
use common\helpers\CommonHelper;
use common\base\LogChannel;
use common\helpers\Util;
use common\models\Setting;
use common\helpers\Lock;
use common\models\credit_line\CreditLine;
use common\models\credit_line\CreditLineMsgCount;
use common\models\credit_line\CreditLineTimeLog;
use common\models\UserCreditDetail;
use common\services\credit_line\CreditLineService;

class CreditCheckController extends BaseController {

    //第三方征信获取队列key
    const CREDIT_GET_DATA_SOURCE_PREFIX = 'credit_get_data_source';

    protected function reject($order, $type = 1, $logInfo = "") {
        if ($type == 2) {
            $front_remark = LoanPersonBadInfo::$reject_code['D2']['child']['04']['frontedn_name'];
            $backend_remark = LoanPersonBadInfo::$reject_code['D2']['child']['04']['backend_name'];
            $head_code = 'D2';
            $back_code = '04';
        } elseif ($type == 3) {//多笔订单
            $front_remark = LoanPersonBadInfo::$reject_code['D2']['child']['25']['frontedn_name'];
            $backend_remark = LoanPersonBadInfo::$reject_code['D2']['child']['25']['backend_name'];
            $head_code = 'D2';
            $back_code = '25';
        } else {
            $front_remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['frontedn_name'];
            $backend_remark = LoanPersonBadInfo::$reject_code['D1']['child']['01']['backend_name'];
            $head_code = 'D1';
            $back_code = '01';
        }
        $info = $order;
        $order_id = $info->id;

        $log = new UserOrderLoanCheckLog();

        //$credit = UserCreditTotal::find()->where(['user_id' => $info['user_id']])->one();
        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($info['user_id'], $order_id);

        $user_credit_log_type = '';
        $user_credit_log_apr = '';
        switch ($order->order_type) {
            case UserLoanOrder::LOAN_TYPE_LQD:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
                $user_credit_log_apr = $credit->pocket_apr;
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_FZD_CS_CANCEL;
                $user_credit_log_apr = $credit->house_apr;
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $user_credit_log_type = UserCreditLog::TRADE_TYPE_FQSC_CS_CANCEL;
                $user_credit_log_apr = $credit->installment_apr;
                break;
        }

        $log->order_id = $order_id;
        $log->before_status = $info->status;
        $log->after_status = UserLoanOrder::STATUS_CANCEL;
        $log->operator_name = 'auto shell';
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->remark = (empty($logInfo)) ? $backend_remark : $logInfo;
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = $head_code;
        $log->back_code = $back_code;
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if ($type == 2 || $type == 3) {
                $log->can_loan_type = UserOrderLoanCheckLog::CAN_LOAN;
            } else {
                $log->can_loan_type = UserOrderLoanCheckLog::CAN_NOT_LOAN;
                $loanPerson = LoanPerson::findOne($info['user_id']);
                $loanPerson->can_loan_time = time() + 86400 * 30;
                if (!$loanPerson->save()) {
                    throw new Exception('');
                }
            }

            $info->status = UserLoanOrder::STATUS_CANCEL;
            $info->reason_remark = $front_remark;
            $info->operator_name = 'auto shell';
            $info->auto_risk_check_status = 1;
            $info->is_hit_risk_rule = 1;
            $info->trail_time = time();
            //$info->coupon_id = 0;//优惠券id重置

            //解除用户该订单锁定额度
            $credit->locked_amount = (($credit->locked_amount - $info['money_amount']) >= 0) ? $credit->locked_amount - $info['money_amount'] : 0;
            //资金流水
            $interests = sprintf('%.2f', $info['money_amount'] / 100 * $info['loan_term'] * $credit->pocket_apr / 10000);
            $user_credit_log = new UserCreditLog();
            $user_credit_log->user_id = $info['user_id'];
            $user_credit_log->type = $user_credit_log_type;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $info['money_amount'];
            $user_credit_log->apr = $user_credit_log_apr;
            $user_credit_log->interests = $interests * 100;
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = "";
            $user_credit_log->total_money = $credit->amount;
            $user_credit_log->used_money = $credit->used_amount;
            $user_credit_log->unabled_money = $credit->locked_amount;

            $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data' => ['remark' => $log->remark]]));

            if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save()) {
                $transaction->commit();
                return true;
            } else {
                throw new Exception('');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 订单队列延迟入征信采集
     * @return int
     */
    public function actionOrderDelayToList() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);

        try {
            $this->_lpopRpush(UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX);
            $this->_lpopRpush(UserCreditData::CREDIT_GET_DATA_SOURCE_SIMPLE_DELAY, UserCreditData::CREDIT_GET_DATA_SOURCE_SIMPLE_PREFIX);
        } catch (\Exception $e) {
            $error_msg = "push delay order to data_source_list failed, error: " . $e->getMessage();
            $this->printMessage($error_msg);
            MessageHelper::sendSMS(NOTICE_MOBILE, $error_msg);
//            MessageHelper::sendSMS(NOTICE_MOBILE2, $error_msg);
        }

        return self::EXIT_CODE_NORMAL;
    }


    /**
     * 第三方征信数据落地
     * touch /tmp/close-get-data-source.tag
     * @param int $o_id 可选订单号
     */
    public function actionGetDataSource($o_id = 0) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);

        //脚本关闭标识
        $close_tag = '/tmp/close-get-data-source.tag';
        try {
            $product_id = CreditCheckHitMap::PRODUCT_YGD;
            $redis = Yii::$app->redis;
            $redis->open();
            $now = time();
            $retry_times = 6; //之前是5分钟一次，最多10次

            while (true) {
                pcntl_signal_dispatch();

                if (\file_exists($close_tag)) {
                    if (! unlink($close_tag) ) {
                        CommonHelper::error("delete $close_tag failed.");
                    }
                    $this->printMessage('检测到标识文件，关闭当前脚本');
                    exit;
                }
                if (\time() - $now > 170) {
                    $this->printMessage('running_3mins，close');
                    exit;
                }

                if ($o_id) {
                    $order_id = $o_id;
                    $o_id = 0;
                } else {
                    $last_order_id = isset($order_id) ? $order_id : 0; //上一条处理的订单号
                    $order_id = RedisQueue::pop([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX]);
                }



                if (empty($order_id)) {
                    if (time() % 10 == 0) {
                        $this->printMessage('none_order_id, sleep');
                    }
                    \sleep(1);
                    continue;
                }
                if (isset($last_order_id) && $last_order_id == $order_id) { //如果相同订单ID则跳过
                    $this->printMessage( "order_{$order_id} last_oid_same_skip." );
                    continue;
                }

                $get_times = 1;
                $order_time = time();
                if (strpos($order_id, '_') !== false) {
                    $order_arr = \explode('_', $order_id);
                    $order_id = $order_arr[0];
                    $order_time = $order_arr[1];
                    $get_times = $order_arr[2];
                }
                $order = UserLoanOrder::findOne($order_id);
                if (empty($order)) {
                    $this->printMessage("order_{$order_id} not_exists.");
                    continue;
                }

                $loanPerson = LoanPerson::findOne([
                    'id' => $order->user_id,
                    'status' => LoanPerson::PERSON_STATUS_PASS,
                ]);
                //借款申请时间0点到6的拒绝   2018-6-12暂时去掉
                /*$today_0 = strtotime(date('Y-m-d'.'00:00:00',time()));
                $today_6 = strtotime(date('Y-m-d'.'06:00:00',time()));
                if($today_0<=$order->created_at && $order->created_at<=$today_6){
                    $this->printMessage("user_{$loanPerson->id} 0点到6点的订单全部拒绝");
                    $this->_csReject($order->id, "0点到6点的订单全部拒绝");
                    continue;
                }*/

                //5多次还款之后拒绝
//                $loan_count = UserLoanOrderRepayment::CheckSuccessLoan($loanPerson->id);
//                if($loan_count>7){
//                    $this->_csReject($order->id,'借款成功7次之后拒绝');
//                }
                //历史逾期三天之后拒绝
//                $over_count = UserLoanOrderRepayment::CheckRepaymentOver($loanPerson->id);
//                if($over_count>0){
//                    $this->_csReject($order->id,'历史逾期三天拒绝');
//                }
                if ($order->status != UserLoanOrder::STATUS_CHECK || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_DATA) {
                    $_notice = \sprintf("order_{$order_id} 非采集状态[%s-%s], skip.",
                        $order->status != UserLoanOrder::STATUS_CHECK,
                        $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_DATA);

                    $this->printMessage( $_notice );
                    continue;
                }

                //未绑定银行卡
                if(!$order->card_id || !$order->cardInfo) {
                    $this->printMessage(\sprintf("order_{$order_id} 未绑定银行卡."));
                    continue;
                }

                if (isset($order_time) && (time() - $order_time) < (YII_ENV_DEV ? 3 : 3)) {
                    $_input = "{$order_id}_{$order_time}_{$get_times}";
                    RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, $_input]);
                    \usleep(10000);
                    continue;
                }

                if (empty($loanPerson)) {
                    $this->printMessage("order_{$order_id} user_{$order_id} user_not_exists");
                    continue;
                }

                try {
                    if ($loanPerson->skipRiskCheck()) { # 跳过风控审核
                        $log = new UserOrderLoanCheckLog();
                        $log->order_id = $order->id;
                        $log->before_status = $order->status;
                        $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                        $log->operator_name = '机审';
                        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                        $log->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['backend_name'];
                        $log->remark = '白名单用户';
                        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                        $log->head_code = 'A3';
                        $log->back_code = '01';

                        $order->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                        $order->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['frontedn_name'];
                        $order->operator_name = 'auto shell';
                        $order->trail_time = time();
                        $order->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS; //已审核
                        $order->is_hit_risk_rule = 0;
                        $_log = $log->save();
                        $_order = $order->save();

                        $this->printMessage("order_{$order_id} white_list, skipRiskCheck, log-{$_log},order-{$_order}");
                        continue;
                    }

                    /** @var CreditCheckService $service */
                    $service = Yii::$container->get('creditCheckService');
                    $regular_result = $service->checkRegular($loanPerson, $product_id, $order_id);
                    Yii::trace(\sprintf('order: %s 老用户判断结果：%s', $order_id, $regular_result['code']), LogChannel::CHECK_REGULAR_RESULT);
                    // 正常老客户续借 & 不在黑名单
                    if ( \in_array($regular_result['code'], [1])
                        && (!LoanBlackList::isInBlacklist($loanPerson)) ) {

                        $order->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_REVIEW;
                        $_save = $order->save();
                        $chk_order = UserLoanOrder::findOne($order_id);
                        $this->printMessage( sprintf("order_%s_%s_%s_%s 老用户续借，进入决策流程\n",
                            $order_id, $_save,$chk_order->status, $chk_order->auto_risk_check_status) );
                        continue;
                    }

                    $credit_data = UserCreditData::findOne([
                        'order_id' => $order_id,
                        'user_id' => $loanPerson->id,
                    ]);
                    if (!empty($credit_data)) {
                        $this->printMessage("order_{$order_id} 征信数据已存在, 跳过采集");
                        continue;
                    }

                    $start_time = \microtime(true);

                    //过期老用户重新获取征信数据
                    $force = \in_array($regular_result['code'], [-3]);
                    if ($service->getAllDataSource($loanPerson, $product_id, $order_id, $force)) {
                        $order->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_ANALY; //待分析数据
                        $_save = $order->save();
                        $this->printMessage("order_{$order_id} 征信数据采集完毕, 状态更新" . ($_save?'成功':'失败'));
                        RedisQueue::push([RedisQueue::LIST_CHECK_ORDER, $order_id]);

                        \yii::trace(\sprintf('订单%s获取征信数据耗时 : %s', $order_id, \microtime(true) - $start_time), LogChannel::CREDIT_GET_DATA_SOURCE_TIME);
                    }
                    else {
                        $_notice = "{$order_id} 征信数据采集失败";
                        $this->printMessage($_notice);
                        MailHelper::send(NOTICE_MAIL, $_notice);
                    }
                }
                catch (\Exception $e) {
                    $this->printMessage(sprintf('[order_%s] get_ext: (%s)%s', $order_id, $e->getCode(), $e));

                    if ($e->getCode() == 3000) {
                        $time = time();
                        if (($time - $order->updated_at) > 3600 * 2) {
                            $queue = CreditJxlQueue::findOne(['user_id' => $loanPerson->id]);
                            if ($queue) {
                                $queue->current_status = -1;
                                $queue->message = '';
                                $queue->save();
                            }
                            $verification = UserVerification::findOne(['user_id' => $loanPerson->id]);
                            if ($verification) {
                                $verification->real_jxl_status = 0;
                                $verification->real_yys_status = 0;
                                $verification->save();
                            }
                            $this->reject($order, 2, "采集不到聚信立数据 拒绝");
                            UserService::resetJxlStatus($loanPerson->id);
                            $phone_msg = '尊敬的' . $loanPerson->name . '，您的运营商数据获取失败，本次借款申请未通过；请登录APP，打开认证中心重新认证手机运营商信息后，再次申请。';
                            UserLoanOrder::sendSms($loanPerson->phone, $phone_msg, $order);
                            $this->printMessage("{$order_id} 采集不到聚信立数据,拒绝订单并通知用户");
                        }
                        else {
                            if ($get_times <= $retry_times) {
                                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, $order_id . '_' . time() . '_' . ($get_times + 1)]);
                                $this->printMessage("{$order_id} 采集不到聚信立数据,5分钟后重新获取-第{$get_times}次");
                            }
                            else {
                                $this->reject($order, 2, "采集不到聚信立数据 拒绝");
                                UserService::resetJxlStatus($loanPerson->id);
                                $phone_msg = '尊敬的' . $loanPerson->name . '，您的运营商数据获取失败，本次借款申请未通过；请登录APP，打开认证中心重新认证手机运营商信息后，再次申请。';
                                UserLoanOrder::sendSms($loanPerson->phone, $phone_msg, $order);
                                $this->printMessage("{$order_id} 采集不到聚信立数据,拒绝订单并通知用户");
                            }
                        }
                    }
                    elseif ($e->getCode() == 3002) {
                        $time = time();
                        if (($time - $order->updated_at) > 3600 * 2) {
                            $this->reject($order, 2, "采集不到手机联系人信息 拒绝");
                            $phone_msg = '尊敬的' . $loanPerson->name . '，您未选择紧急联系人，本次借款申请未通过，请登录App，完善紧急联系人信息后，再次申请。';
                            UserVerification::resetVerificationInfo($loanPerson->id, UserVerification::TAG_CONTACT_INFO);
                            UserLoanOrder::sendSms($loanPerson->phone, $phone_msg, $order);
                            $this->printMessage("{$order_id} 采集不到手机联系人信息,拒绝订单并通知用户");
                        } else {
                            if ($get_times > $retry_times) {
                                $this->reject($order, 2, "多次采集不到手机联系人信息 拒绝");
                                $this->printMessage("{$order_id} 采集不到手机联系人信息,拒绝");
                            } else {
                                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, $order_id . '_' . time() . '_' . ($get_times + 1)]);
                                $this->printMessage("{$order_id} 采集不到手机联系人信息,5分钟后重新获取-第{$get_times}次");
                            }
                        }
                    }
                    else {
                        \yii::error(new Exception('机审脚本错误', 0, $e), LogChannel::RISK_CONTROL);

                        $limit = 30;
                        $err_msg = $e->getMessage();
                        $key = 'koudaikj:alarm::getdata:' . $order_id . ':' . md5($err_msg);
                        $redis = Yii::$app->redis;
                        $i = $redis->get($key);
                        if (empty($i)) {
                            $i = 0;
                        }
                        $redis->setex($key, 7200, $i++);
                        if ($i <= $limit) {
                            if ($get_times <= $retry_times) {
                                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, $order_id . '_' . time() . '_' . ($get_times + 1)]);
                                $this->printMessage("{$order_id} 征信采集异常466,5分钟-第{$get_times}次");
                            } else {
                                $this->reject($order, 2, $err_msg);
                                $this->printMessage("{$order_id} {$err_msg}");
                            }
                        }
                        else {
                            $this->printMessage("{$order_id} {$err_msg}，出错达到{$limit}次。");
                        }
                    }

                    continue;
                }
            }

            if ($o_id) {
                $this->printMessage("手动检测[{$o_id}]退出");
                exit();
            }
        }
        catch (\Exception $e) {
            Yii::error($e, 'risk_control');
            MessageHelper::sendInternalSms(NOTICE_MOBILE, 'GetDataSource出现异常');
        }
    }

    /**
     * 老用户跳过机审
     */
    public function actionCheckOrders($limit = 500) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(1024);
        $orders = UserLoanOrder::find()->where([
            'status' => UserLoanOrder::STATUS_CHECK,
            'auto_risk_check_status' => UserLoanOrder::AUTO_STATUS_REVIEW,
        ])
            ->limit($limit)
            ->all();

        if (empty($orders)) {
            CommonHelper::stdout("none orders\n");
            return self::EXIT_CODE_NORMAL;
        }

        $product_id = CreditCheckHitMap::PRODUCT_YGD;
        foreach ($orders as &$v) {
            $order_id = $v->id;
            CommonHelper::stdout( \sprintf("mem:%s mb, order_%s.\n", \bcdiv(\memory_get_usage(true), 1048576), $order_id) );

            try {
                $loanPerson = LoanPerson::findOne($v->user_id);
                if (empty($loanPerson)) {
                    CommonHelper::stderr("order_{$order_id}, loanPerson_missed.\n", LogChannel::RISK_CONTROL);
                    continue;
                }

                //同一用户多平台订单数验证
                $loan_persons = LoanPerson::find()->where(['id_number' => $loanPerson->id_number])->asArray()->all();
                $user_ids = ArrayHelper::getColumn($loan_persons, 'id');
                $user_orders = UserLoanOrder::find()->where(['in', 'user_id' , $user_ids])
                    ->andWhere(['not in', 'status', [
                        UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL,
                        UserLoanOrder::STATUS_REPAY_CANCEL,
                        UserLoanOrder::STATUS_PENDING_CANCEL,
                        UserLoanOrder::STATUS_REPEAT_CANCEL,
                        UserLoanOrder::STATUS_CANCEL,
                        UserLoanOrder::STATUS_REPAY_COMPLETE,
                        10000,
                        10001]])->all();
                if (count($user_orders) > 1) {
                    $this->reject($v, 3);
                    continue;
                }

                $warn_amount = Setting::getCardWarnQuota($v->card_type);
                if ($v->money_amount > $warn_amount) {
                    $v->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;
                    $v->is_hit_risk_rule = 0;
                    if (!$v->save()) {
                        CommonHelper::stderr("order_{$order_id}, save_failed");
                    }

                    CommonHelper::stderr("order_{$order_id}, Quota_exception_{$warn_amount}. manual.\n");
                    continue;
                }

                /** @var CreditCheckService $service */
                $service = Yii::$container->get('creditCheckService');
                $regular_result = $service->checkRegular($loanPerson, $product_id, $order_id); //老用户判断
                if ($regular_result['code'] == 1) {
                    CommonHelper::stdout("order_{$order_id}, old_user_pass\n");

                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $v->id;
                    $log->before_status = $v->status;
                    $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                    $log->operator_name = '机审';
                    $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
                    $log->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['backend_name'];
                    $log->remark = '老用户续借';
                    $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
                    $log->head_code = 'A3';
                    $log->back_code = '01';
                    if (! $log->save()) {
                        CommonHelper::stderr("order_{$v->id}, log_save_failed.\n");
                    }

                    $v->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
                    $v->reason_remark = LoanPersonBadInfo::$pass_code['A3']['child']['01']['frontedn_name'];
                    $v->operator_name = Util::short(__CLASS__, __FUNCTION__); //'auto shell';
                    $v->trail_time = time();
                    $v->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;
                    $v->is_hit_risk_rule = 0;
                    if (! $v->save()) {
                        CommonHelper::stderr("order_{$v->id}, save_failed.\n");
                    }
                }
                else {
                    CommonHelper::stdout("order_{$order_id}, other_into_get_ds_again.\n");

                    $v->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_DATA;
                    if ($v->save()) {
                        RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX, $order_id]);
                        $this->printMessage("{$order_id} 非老用户，重新进入风控流程");
                    }
                }
            }
            catch (\Exception $e) {
                CommonHelper::error( \sprintf("order_{$v->id} old_user_check err: %s", $e), LogChannel::RISK_CONTROL);
                continue;
            }
        }

        return self::EXIT_CODE_NORMAL;
    }


    protected function printMessage($message)
    {
        $pid = function_exists('posix_getpid') ? posix_getpid() : get_current_user();
        $date = date('Y-m-d H:i:s');
        $mem = floor(memory_get_usage(true) / 1024 / 1024) . 'MB';
        //时间 进程号 内存使用量 日志内容
        echo "{$date} {$pid} $mem {$message} \n";
        //Yii::error("{$date} {$pid} $mem {$message}", LogChannel::CREDIT_GET_DATA_SOURCE);
    }

    private function _lpopRpush($source_list, $destination_list) {
        $_len = RedisQueue::getLength([$source_list]);
        if ($_len <= 0) {
            return true;
        }

        $order_list = [];
        for ($i = 0; $i < $_len; $i++) {
            $order_id = RedisQueue::pop([$source_list]);
            if (empty($order_id)) {
                break;
            }
            $order_list[] = $order_id;
        }

        $order_list_len = count($order_list);
        if (!empty($order_list)) {
            array_unshift($order_list, $destination_list);
            $list_len = RedisQueue::push($order_list);
            if ($list_len < $order_list_len) {
                CommonHelper::error("pop {$source_list} to push {$destination_list}, list len: {$list_len}, order_list_len: {$order_list_len}, order_list: " . implode(",", $order_list) . ".\n");
                return false;
            }
        }

        CommonHelper::stdout("pop {$source_list} to push {$destination_list} succ.\n");
        return true;
    }

    /*
     * 初审拒绝
     * @param $id 订单id
     * @param $remark 初审拒绝备注
     * @return boolean
     */
    private function _csReject($id, $remark='未定义备注') {
        Yii::info(\sprintf('%s 初审转人工拒绝.', $id), LogChannel::ORDER_RESULT);
        $_op = Util::short(__CLASS__, __FUNCTION__);
        $remark = empty($remark) ? '新增脚本拒绝初审存疑订单' : $remark;
        $loan_action = UserOrderLoanCheckLog::CAN_LOAN; # UserOrderLoanCheckLog::MONTH_LOAN; #1个月后再借
        $code = \explode('o', 'D1o08'); #信用类 / 负面信息提示-须备注原因

        $loanPersonInfoService = \yii::$container->get("loanPersonInfoService");
        $information = $loanPersonInfoService->getPocketInfo($id);

        /* @var $info UserLoanOrder */
        $info = $information['info'];
        $info->tree = 'manual';

        $credit = $information['credit'];

        $log = new UserOrderLoanCheckLog();
        $log->tree = 'manual';
        $log->order_id = $id;
        $log->before_status = $info->status;
        $log->after_status = UserLoanOrder::STATUS_CANCEL;
        $log->operator_name = $_op;
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['backend_name'];
        $log->remark = $remark;
        $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
        $log->head_code = $code[0];
        $log->back_code = $code[1];

        $info->status = UserLoanOrder::STATUS_CANCEL;
        $info->reason_remark = LoanPersonBadInfo::$reject_code[$code[0]]['child'][$code[1]]['frontedn_name'];
        $info->operator_name = $_op;
        //$info->coupon_id = 0;//优惠券id重置

        //解除用户该订单锁定额度
        $credit->locked_amount = $credit->locked_amount - $info['money_amount'];

        //资金流水
        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $info['user_id'];
        $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_CS_CANCEL;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $info['money_amount'];
        $user_credit_log->created_at = \time();
        $user_credit_log->created_ip = '106.15.41.23';
        $user_credit_log->total_money = $credit->amount;
        $user_credit_log->used_money = $credit->used_amount;
        $user_credit_log->unabled_money = $credit->locked_amount;

        $log->can_loan_type = $loan_action;
        $loanPerson = LoanPerson::findOne($info['user_id']);
        if ($loan_action == UserOrderLoanCheckLog::CAN_LOAN) {
            $loanPerson->can_loan_time = time() + 86400;
        }
        elseif ($loan_action == UserOrderLoanCheckLog::MONTH_LOAN) {
            $loanPerson->can_loan_time = time() + 86400*30;
        }
        else {
            $loanPerson->can_loan_time = 4294967295;
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save() && $loanPerson->save()) {
                    $transaction->commit();

                    //触发订单的审核拒绝事件 自定义的数据可添加到custom_data里
                    $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data'=>['remark'=>$remark]]));
                    CommonHelper::stdout( "{$id} cs_reject success.\n" );
                }
                else {
                    $transaction->rollBack();
                    CommonHelper::stderr( "{$id} cs_reject failed.\n");
                }
            }
            else {
                $transaction->rollBack();

                $info_err = $info->getErrors();
                $log_err = $log->getErrors();
                $credit_err = $credit->getErrors();
                $credit_log_err = $user_credit_log->getErrors();
                CommonHelper::stderr( \sprintf("{$id} validate failed [%s][%s][%s][%s].\n",
                        print_r($info_err, true),
                        print_r($log_err, true),
                        print_r($credit_err, true),
                        print_r($credit_log_err, true))
                );
            }
        }
        catch (\Exception $e) {
            CommonHelper::stderr( \sprintf("{$id} cs_reject exception: %s.\n", $e) );
            $transaction->rollBack();
        }

        return false;
    }
}
