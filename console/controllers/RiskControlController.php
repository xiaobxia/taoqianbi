<?php
namespace console\controllers;

use backend\models\AdminUser;
use common\helpers\MailHelper;
use common\helpers\StringHelper;
use common\helpers\ToolsUtil;
use common\models\AccumulationFund;
use common\models\CreditFacePlus;
use common\models\FinancialLoanRecord;
use common\models\LoanOrderDayQuota;
use common\models\mongo\risk\OrderReportMongo;
use common\models\Setting;
use common\models\UserDetail;
use common\services\UserService;
use common\services\WLService;
use Yii;

use common\api\RedisQueue;
use common\base\LogChannel;

use common\models\CreditJsqb;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\UserCreditLog;
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use common\services\AutoCheckService;
use common\services\RiskControlService;
use common\services\RiskControlTreeService;
use common\helpers\CommonHelper;
use common\helpers\Util;
use common\helpers\MessageHelper;
use yii\helpers\ArrayHelper;
use common\base\ErrCode;

/**
 * 风控规则相关脚本
 */
class RiskControlController extends BaseController {

    protected function printMessage($message) {
        $pid = function_exists('posix_getpid') ? posix_getpid() : '';
        $date = date('Y-m-d H:i:s');
        $mem = floor(memory_get_usage(true) / 1024 / 1024) . 'MB';
        //时间 进程号 内存使用量 日志内容
        echo "{$date} {$pid} $mem {$message} \n";
        //Yii::error("{$date} {$pid} $mem {$message}", LogChannel::CREDIT_AUTO_CHECK);
    }

    /**
     * 机审步骤2（执行决策）
     * touch /tmp/close_auto_check_rule.tag
     */
    public function actionAutoCheck() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);

        $tag_file = '/tmp/close_auto_check_rule.tag';
        $risk_control_service = new RiskControlService();
//        $service = new RiskControlService();
        $auto_service = new AutoCheckService();
        $now = time();

        pcntl_signal(SIGUSR1, function () {
            $this->printMessage('检测到结束信号，关闭当前脚本');
            exit;
        });

        $over_time = array();
        while (true) {
            pcntl_signal_dispatch();

            $current_time = time();
            if (file_exists($tag_file)) {
                if (! unlink($tag_file) ) {
                    CommonHelper::error("delete $tag_file failed.");
                }
                $this->printMessage('检测到标识文件，关闭当前脚本');
                exit;
            }

            if (time() - $now > 165) {
                $this->printMessage('运行满3分钟，关闭当前脚本');
                exit;
            }

            try {
                $id = RedisQueue::pop([RedisQueue::LIST_CHECK_ORDER]);
                if (!$id) {
                    if (time() % 10 == 0) {
                        $this->printMessage('无任务');
                    }
                    \sleep(1);
                    continue;
                }

                $this->printMessage("订单{$id}开始评分审核");
                $order = UserLoanOrder::findOne(['id' => $id]);
                if (empty($order)
                    || $order->status != UserLoanOrder::STATUS_CHECK
                    || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY
                ) {
                    $this->printMessage(\sprintf("订单{$id}已经审核[%s-%s-%s]，skip",
                        empty($order),
                        $order->status != UserLoanOrder::STATUS_CHECK,
                        $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY));
                    continue;
                }

                //借款申请时间0点到6的拒绝
//                $today_0 = date('Y-m-d'.'00:00:00',time());
//                $today_6 = date('Y-m-d'.'10:00:00',time());
//                if(in_array($order->created_at,[$today_0,$today_6])){
//                    $this->_csReject($order->id, "0点到6点的订单全部拒绝");
//                    continue;
//                }

                $loan_person = LoanPerson::findOne($order->user_id);
                //现金白卡黑名单
//                $bk_list = WLService::getIsBlack($loan_person);
//                if($bk_list == 'true'){
//                    $msg = '现金白卡黑名单拒接';
//                    $this->_csReject($order->id,$msg);
//                    continue;
//                }

                //判断是不是白名单
                $is_white = UserService::checkWhiteList($loan_person);
                $rule_id = 390;
                //跑390风控
//                if($is_white == false){
//                    $rule_id = 639;
//                }
                $this->printMessage(sprintf("订单%d开始执行决策树{$rule_id}", $id));
                $p1 = \microtime(true);

                $result = $risk_control_service->runSpecificRule([$rule_id], $loan_person, $order);

                $this->printMessage(\sprintf("订单%d完成{$rule_id}决策树,耗时%d秒,printTime:%s", $id, \microtime(true) - $p1, $result['printTime']));

                $result = $result[$rule_id]['value'];
                $credit = isset($result['credit']) ? $result['credit'] : "";
                $message = isset($result['message']) ? $result['message'] : "";
                $check_result = isset($result['result']) ? $result['result'] : "";
                $tree = isset($result['tree']) ? $result['tree'] : "";
                $auto_service->check($order, $credit, $message, $check_result, $tree);

                if($check_result == '2'){
                    //调试决策树
                    $this->printMessage(sprintf("订单%d开始调试决策树336", $id));
                    $this->printMessage(json_encode($result));
//                    $p1 = microtime(true);
//                    $res = $service->runSpecificRule([336], $loan_person, $order);
//                    $res = isset($res['336']['value']['result']) ? $res['336']['value']['result'] : 3;
//                    $auto_service->saveRuleResult($res, 'TreeTest', $order);
//                    $this->printMessage(sprintf("订单%d结束调试决策树336,耗时%d秒", $id, microtime(true) - $p1));
//                    $msg = '订单存疑直接拒绝510';
//                    $this->_csReject($order->id,$msg);
//                    continue;
                }

                //非白名单用户拒接
               /* if($check_result == 1 || $check_result == 2){
                    if($is_white == false){
                        $this->printMessage("user_{$loan_person->id} 非白名单用户跳过");
                        $msg = '非白名单用户直接拒接-390';
                        $this->_csReject($order->id,$msg);
                        continue;
                    }
                }*/


                $time = time() - $current_time;
                $this->printMessage("订单{$id}审核结束{$time}ms.");
                if ($time > 15) {
                    $over_time[] = $time;
                }
                $over_time_count = \count($over_time);
                if ($over_time_count >= 5) {
                    $svr_ip = ToolsUtil::getLocalIp();
                    MailHelper::sendCmdMail(sprintf('[risk-control] 审单脚本超时 - %s', date('y-m-d')),
                        "[{$svr_ip}]单进程审单时间超过15s，达{$over_time_count}单，请检验auto-check。",
                        NOTICE_MAIL);
                    $over_time = [];
                }

                // 放入数据分析队列
                RedisQueue::push([RedisQueue::LIST_ANALYSIS_ORDER, $id]);
            }
            catch (\Exception $e) {
                //db异常，重入队列
                if ($e instanceof \PDOException) {
                    if ($id && $order = UserLoanOrder::findOne($id)) {
                        if ($order->status == UserLoanOrder::STATUS_CHECK && $order->auto_risk_check_status == UserLoanOrder::AUTO_STATUS_ANALY) {
                            RedisQueue::push([RedisQueue::LIST_CHECK_ORDER, $id]);
                        }
                    }
                }
                CommonHelper::error(sprintf('order_%s auto_check_exception: %s', $id, $e), LogChannel::RISK_CONTROL);
            }
        }
    }



    /**
     * 0415 初审订单全部拒绝，允许一个月后再借
     */
    public function actionRejectCsOrder($limit = 1000) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(1024);

        $start_id = 0;
        $whitelist_phones = UserService::whitelistPhones();

        /* @var $redis \yii\redis\Connection */
        $redis = \yii::$app->redis;
        $rk_prefix = 'wzd:order:waiting_for_manual:';
        $rk_expire = 1800;

        do {
            $orders = UserLoanOrder::find()
                ->where([
                    'status' => UserLoanOrder::STATUS_CHECK,
                    'auto_risk_check_status' => UserLoanOrder::AUTO_STATUS_SUCCESS,
                ])
                ->andWhere(" id > {$start_id}")
                ->limit($limit)
                ->all();
            if (empty($orders)) {
                print \sprintf('%s none order.', \date('Y-m-d H:i'));
                return self::EXIT_CODE_NORMAL;
            }

            foreach ($orders as $_order) {
                $order_id = $_order['id'];
                $start_id = max($start_id, $order_id);
                if ($redis->get( $rk_prefix . $order_id )) {
                    CommonHelper::stdout( "{$order_id} in_redis, 等人工操作.\n" );
                    continue;
                }

                if (\in_array($_order->loanPerson->phone, $whitelist_phones) ) {
                    $redis->setex( $rk_prefix . $order_id, $rk_expire, 1 );
                    CommonHelper::stdout( "{$order_id} 白名单订单，等人工操作.\n" );
                    continue; //白名单订单，等人工操作
                }

                $user_id = $_order['user_id'];

                $loan_person = $_order->loanPerson;
                if (AccumulationFund::validateAccumulationStatus($loan_person)) {
                    $accumulation_fund = AccumulationFund::findLatestOne([
                        'user_id' => $user_id,
                        'status' => AccumulationFund::STATUS_SUCCESS,
                    ]);
                    $user_detail = UserDetail::find()
                        ->where(['user_id' => $user_id])
                        ->select('company_name')
                        ->limit(1)
                        ->asArray()
                        ->one();
                    $data = \json_decode($accumulation_fund->data, true);
                    if (!$data || !isset($data['company'])
                        || ($fuzzy_match = StringHelper::JaroWinkler($data['company'], $user_detail['company_name'])) < 0.75) {

                        $redis->setex( $rk_prefix . $order_id, $rk_expire, 1 ); //flag
                        $_msg = \sprintf('%s 公积金订单，等人工操作.', $order_id);
                        CommonHelper::stdout( $_msg . PHP_EOL );
                        continue;
                    }

                    /* @var $redis \yii\redis\Connection */
                    $redis = \yii::$app->redis;
                    $report_404_key = OrderReportMongo::RK_ORDER_REPORT_404_PREFIX . $order_id;
                    $report_404 = $redis->get($report_404_key);
                    if (isset($report_404) && $report_404 < 6) {
//                        $transaction = UserLoanOrder::getDb()->beginTransaction();
//                        try {
//                            $log = new UserOrderLoanCheckLog();
//                            $log->order_id = $_order['id'];
//                            $log->before_status = $_order->status;
//                            $log->after_status = UserLoanOrder::STATUS_REPEAT_TRAIL;
//                            $log->operator_name = 'leishuanghe';
//                            $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
//                            $log->reason_remark = '审核通过';
//                            $log->remark = '审核通过';
//                            $log->operation_type = UserOrderLoanCheckLog::LOAN_CS;
//                            $log->head_code = 'A1';
//                            $log->back_code = '01';
//
//                            $_order->tree = 'manual';
//                            $_order->tree = 'manual';
//                            $_order->status = UserLoanOrder::STATUS_REPEAT_TRAIL;
//                            $_order->reason_remark = '审核通过';
//                            $_order->operator_name = 'leishuanghe';
//
//                            if ($_order->save() && $log->save()) {
//                                $transaction->commit();
//                                CommonHelper::stdout( "{$order_id} 公积金订单脚本审核通过.\n" );
//                                \yii::warning(\sprintf('%s 公积金订单审核通过。公司名称匹配度：%s', $_order['id'], $fuzzy_match), LogChannel::ORDER_RESULT);
//                            } else {
//                                CommonHelper::stderr( "{$order_id} 数据保存失败.\n" );
//                                $transaction->rollBack();
//                            }
//                        }
//                        catch(\Exception $e) {
//                            CommonHelper::stderr( sprintf("{$order_id} exception: %s.\n", print_r($e, TRUE)) );
//                            $transaction->rollBack();
//                        }
                        continue; //过审
                    }
                    else {
                        $redis->setex( $rk_prefix . $order_id, $rk_expire, 1 ); //flag
                        $_msg = \sprintf('%s 公积金订单，等人工操作.', $order_id);
                        CommonHelper::stdout( $_msg . PHP_EOL );
                        continue;
                    }
                }

                $face = CreditFacePlus::findOne(['user_id' => $user_id]);
                if (is_null($face) || !isset($face['confidence']) || (isset($face['confidence']) && ($face['confidence'] < 50 || $face['confidence'] <= $face['1e-5']))) {
                    $redis->setex( $rk_prefix . $order_id, $rk_expire, 1 ); //flag
                    CommonHelper::stdout( "{$order_id} 人脸分低于50，转人工审核.\n" );
                    continue; //人脸识别低于50分，转人工
                }

//                CommonHelper::stdout( "{$order_id} 初审转人工全部拒绝.\n" );
//                $this->_csReject($_order['id'], '初审转人工全部拒绝');
            }
        } while ( count($orders) > 0 );

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 0502 账上没钱了，"打款列表"有 2176 单"申请中|待回调"，全部"重置状态"后，"驳回"
     * risk-control/reject-paying-order 1
     */
    public function actionRejectPayingOrder($limit = 100) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(1024);

        $orders = FinancialLoanRecord::find()
            ->where(['in', 'type', FinancialLoanRecord::$kd_platform_type])
            ->andwhere(['status' => FinancialLoanRecord::UMP_PAYING])
            ->andwhere(['success_time' => 0])
            ->andwhere(['callback_result' => 0])
            ->limit($limit)
            ->all();

        $now_ts = time();
        $fc_service = Yii::$container->get('financialCommonService');
        foreach($orders as $withdraw) {
            $_tmp = $withdraw->toArray();

            //跳过重置，直接驳回
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_REJECT;
                $withdraw->review_remark = '0502，账上没钱，"打款列表"有2176单"申请中|待回调"，全部"重置状态"后"驳回"';
                $withdraw->status = FinancialLoanRecord::UMP_PAY_FAILED;
                $withdraw->review_username = 'clark';
                $withdraw->updated_at = $now_ts;
                $withdraw->review_time = $now_ts;

                $back_result = $fc_service->rejectLoanOrder(
                    $withdraw->business_id, $withdraw->review_remark, $withdraw->review_username, $withdraw->type
                ); //驳回
                if ($back_result[ 'code' ] !== 0) {
                    throw new \Exception( "审核驳回通知业务方失败：{$back_result[ 'message' ]}" );
                }

                $callback_result = [
                    'is_notify' => FinancialLoanRecord::NOTIFY_SUCCESS,
                    'message'   => $back_result[ 'message' ]
                ];

                $withdraw->callback_result = \json_encode( $callback_result );
                if (!$withdraw->save()) {
                    throw new \Exception( "操作失败" );
                }
                $transaction->commit();
                CommonHelper::stdout( \sprintf("%s-%s-%s reject_success.\n", $_tmp['id'], $_tmp['order_id'], $_tmp['user_id']) );
            }
            catch (\Exception $e) {
                $transaction->rollBack();
                CommonHelper::stdout( \sprintf("%s-%s-%s reject_failed [%s].\n", $_tmp['id'], $_tmp['order_id'], $_tmp['user_id'], $e->getMessage()) );
            }

        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 待机审订单积压，全部初审拒绝
     * ./yii risk-control/reject-waiting-order   务必谨慎手动执行！！！
     */
    public function actionRejectWaitingOrder($limit = 100) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(1024);

        $orders = UserLoanOrder::find()
            ->where([
                'status' => UserLoanOrder::STATUS_CHECK,
                'auto_risk_check_status' => [
                    UserLoanOrder::AUTO_STATUS_FAILED,
                    UserLoanOrder::AUTO_STATUS_DEFAULT,
                    UserLoanOrder::AUTO_STATUS_ANALY,
                    UserLoanOrder::AUTO_STATUS_REVIEW,
                ],
            ])
            ->select(['id'])
            ->limit($limit)
            ->all();

        if (empty($orders)) {
            CommonHelper::stdout( \sprintf("%s none_waiting_order\n", \date('Y-m-d H:i')) );
            return self::EXIT_CODE_NORMAL;
        }

        foreach ($orders as $_order) {
            $this->_csReject($_order['id'], '初审存疑，全部拒单');
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 手动审单
     * @param $order_id
     */
    public function actionCheck($order_id) {
        try {
            $risk_control_service = new RiskControlService();
            $auto_service = new AutoCheckService();
            $op = Util::short(__CLASS__, __FUNCTION__);

            $order = UserLoanOrder::findOne(['id' => $order_id]);
            if ( empty($order) ) {
                $this->printMessage( "[{$op}][{$order_id}]订单不存在." );
                return self::EXIT_CODE_ERROR;
            }
            else if ($order->status != UserLoanOrder::STATUS_CHECK
                || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY) {
                $this->printMessage( sprintf("[{$op}][{$order_id}]已经审核过了[%s-%s]，跳出机审", $order->status, $order->auto_risk_check_status) );
                return self::EXIT_CODE_ERROR;
            }

            $loan_person = LoanPerson::find()->where(['id' => $order->user_id])->one();
            if (!empty($loan_person)) {
                $result = $risk_control_service->runSpecificRule([390], $loan_person, $order);
                print_r($result);
                $result = $result['390']['value'];
                $auto_service->check($order, $result['credit'], $result['message'], $result['result']);
                $order->logCardId();
                $this->printMessage("订单{$order_id}审核结束");
            }
            else {
                CommonHelper::stderr("{$order_id} loan_person_missing\n");
            }
        }
        catch (\Exception $e) {
            CommonHelper::error([
                'type' => '机审脚本[check]错误',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], LogChannel::RISK_CONTROL);
        }
    }

    public function actionRuleCheck() {
        $risk_control_service = new RiskControlService();
        $auto_service = new AutoCheckService();
        $tag_file = '/tmp/close_auto_check_rule.tag';

        $now = time();
        pcntl_signal(SIGUSR1, function () {
            $this->printMessage('检测到结束信号，关闭当前脚本');
            exit;
        });

        while (true) {
            if (file_exists($tag_file)) {
                $this->printMessage('检测到标识文件，关闭当前脚本');
                exit;
            }

            pcntl_signal_dispatch();
            if (time() - $now > 600) {
                $this->printMessage('运行满10分钟，关闭当前脚本');
                exit;
            }

            try {
                $id = RedisQueue::pop([RedisQueue::LIST_CHECK_ORDER_NEW]);

                if (!$id) {
                    sleep(2);
                    continue;
                }

                $order = UserLoanOrder::find()->where(['id' => $id])->one();
                $this->printMessage("订单{$id}开始评分审核");
                if (empty($order)
                    || $order->status != UserLoanOrder::STATUS_CHECK
                    || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY
                ) {
                    $this->printMessage("订单{$id}已经审核过了，跳出机审");
                    continue;
                }

                $loan_person = LoanPerson::findOne($order->user_id);

                $result = $risk_control_service->runGenerateRules($loan_person, $order);

                $message = $result['214']['value'];
                $creditLines = $result['210']['value'];
                $result = $result['212']['value'];

                $auto_service->check($order, $creditLines, $message, $result);

                $order->logCardId();

                $this->printMessage("订单{$id}审核结束");

            } catch (\Exception $e) {
                var_dump($e->getMessage());
                var_dump($e->getFile());
                var_dump($e->getLine());

                RedisQueue::push([RedisQueue::LIST_CHECK_ORDER_NEW, $id]);

                Yii::error([
                        'type' => '机审脚本错误',
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                    , 'risk_control'
                );
            }
        }

    }

    public function actionRuleCheckOne($order_id)
    {

        $risk_control_service = new RiskControlService();
        $auto_service = new AutoCheckService();

        $order = UserLoanOrder::find()->where(['id' => $order_id])->one();

        if (empty($order)
            || $order->status != UserLoanOrder::STATUS_CHECK
            || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY
        ) {
            $this->printMessage("订单{$order_id}已经审核过了，跳出机审");
            return;
        }

        $loan_person = LoanPerson::findOne($order->user_id);

        $result = $risk_control_service->runGenerateRules($loan_person, $order);

        $message = $result['214']['value'];
        $creditLines = $result['210']['value'];
        $result = $result['212']['value'];

        $auto_service->check($order, $creditLines, $message, $result);
    }

    public function actionNewRuleCheckOne($order_id, $rule_id, $is_real = 0)
    {

        // $risk_control_service = new RiskControlTreeServiceBak();
        $risk_control_service = new RiskControlTreeService();
        $order = UserLoanOrder::find()->where(['id' => $order_id])->one();

        // if (empty($order)
        //     || $order->status != UserLoanOrder::STATUS_CHECK
        //     || $order->auto_risk_check_status != UserLoanOrder::AUTO_STATUS_ANALY) {
        //     $this->printMessage("订单{$id}已经审核过了，跳出机审");
        //     return;
        // }

        $loan_person = LoanPerson::findOne($order->user_id);

        $result = $risk_control_service->runDesicionTree([$rule_id], $loan_person, $order, $is_real);
        echo "new \n";
        var_dump($result[$rule_id]);

        $risk_control_service = new RiskControlTreeService();
        $result = $risk_control_service->runDesicionTree([$rule_id], $loan_person, $order);
        echo "old \n";
        var_dump($result[$rule_id]);
        // $message = $result['214']['value'];
        // $creditLines = $result['210']['value'];
        // $result = $result['212']['value'];

        // $auto_service->check($order, $creditLines, $message, $result);

    }

    public function actionReviewPass($id)
    {
        $order = UserLoanOrder::find()->where(['id' => $id])->one();
        $order->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_PASS, new \common\base\Event(['custom_data' => ['remark' => '机器审核']]));
    }

    /**
     * 人工复审跳过直接到待放款，检验人工初审记录是否存在
     */
    public function actionUpdateLoan($limit = 100) {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $user_loan_order = UserLoanOrder::find()->where([
            'status' => UserLoanOrder::STATUS_REPEAT_TRAIL,
        ])->limit($limit)->all();
        if (empty($user_loan_order)) {
            print \sprintf('%s none order.', \date('Y-m-d H:i'));
            return self::EXIT_CODE_NORMAL;
        }

        $op = Util::short(__CLASS__, __FUNCTION__);
        $order_service = new \common\services\OrderService();
        $financial_service = \yii::$container->get('financialService');
        $whitelist_phones = UserService::whitelistPhones();

        $reject_all = false; #复审全拒？
        $enable_whitelist_service = false; #启用极速钱包白名单服务

        //从数据库获取每日限额
        $order_quota_model = new LoanOrderDayQuota();
        $order_quota = $order_quota_model->getTodayRemainingQouta();
        $order_total_count = $order_quota['norm'];
        $order_total_count_third = $order_quota['other'];
        $order_total_count_gjj = $order_quota['gjj'];
        $order_total_count_old_user = $order_quota['old_user'];

        foreach ($user_loan_order as $item) {
            $pass_type_int = UserLoanOrder::PASS_TYPE_NORMAL; #限单类型
            $loan_person = $item->loanPerson;
            if (empty($loan_person->phone)) {
                CommonHelper::stderr( "order-{$item['id']}, fs_phone_missing.\n" );
                $this->_fsReject($item['id'], '电话缺失，复审拒绝');
                continue;
            }

            // 20岁以下
            $age = Util::getAgeFromIdNumber($loan_person->id_number);
            if ($age < 20 ) {
                CommonHelper::stderr( "order-{$item['id']}, 年龄<20.\n" );
                $this->_fsReject($item['id'], '年龄不符合，复审拒绝');
                continue;
            }

            #0. 全拒？
            $is_whitelist_phone = in_array($loan_person->phone, $whitelist_phones);
            if ( $is_whitelist_phone ) { # 白名单手机
                CommonHelper::stdout("{$op} whitelist_phone [{$item['id']}]{$loan_person->phone}\n");
                //pass
            }
            else { # 非白名单手机
                if ($reject_all) { #复审全拒？
                    CommonHelper::stdout("order_{$item['id']} fake_reject\n");
                    $this->_fsReject($item['id'], '账户没钱，复审拒单');
                    continue;
                }
            }

            #1. 检查初审记录
            $log = UserOrderLoanCheckLog::findOne([
                'order_id' => $item['id'],
                'operation_type' => UserOrderLoanCheckLog::LOAN_CS,
            ]);
            if (empty($log)) { #无初审记录
                CommonHelper::stderr( \sprintf('[%s][%s mb]%s无初审.', \date('Y-m-d H:i'), \bcdiv(\memory_get_usage(true), 1024*1024), $item['id']) );
                continue;
            }

            #after_status 由分配资方后设置 所以日志记录暂时不设置状态
            $log = new UserOrderLoanCheckLog();
            $log->order_id = $item['id'];
            $log->before_status = $item['status'];
            //$log->after_status = UserLoanOrder::STATUS_PENDING_LOAN;
            $log->operator_name = $op; //auto_review
            $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
            $log->reason_remark = LoanPersonBadInfo::$pass_code['A1']['child']['01']['backend_name'];
            $log->remark = "机器审核，待复审直接修改状态到资方签约（如资方不需签约则为待放款）";
            $log->operation_type = UserOrderLoanCheckLog::LOAN_FS;
            $log->head_code = 'A1';
            $log->back_code = '01';

            #2.检查用户白名单(服务)
            if ($is_whitelist_phone) {
                //pass
            }
            else {
                // 非白名单手机，执行极速钱包黑名单检查
                if ($enable_whitelist_service) {
                    $_watchlist = CreditJsqb::findLatestOne(['person_id' => $loan_person->id]);
                    # if (! isset($_watchlist->is_white) || $_watchlist->is_white != 1) {
                    if ($_watchlist && isset($_watchlist->is_black) && $_watchlist->is_black != 0) {
                        try {
                            CommonHelper::stdout("order_{$item['id']} will_fs_reject\n");
                            # $this->_fsReject($item['id'], '白名单：否'); //非白名单，复审拒绝
                            $this->_fsReject($item['id'], '极速钱包黑名单：是'); //非白名单，复审拒绝
                        }
                        catch (\Exception $e) {
                            CommonHelper::error(\sprintf("{$item['id']} watchlist_service_fsReject exception: %s.", $e));
                        }

                        continue;
                    }
                }
            }

            #2.1 24小时过期 不是融360订单，7天过期；融360订单，7天过期
            if (($item['sub_order_type'] != UserLoanOrder::SUB_TYPE_RONG360 && (\time() - $item['created_at']) > 86400 * 7) ||
                ($item['sub_order_type'] == UserLoanOrder::SUB_TYPE_RONG360 && (\time() - $item['created_at']) > 86400 * 7)) {
                try {
                    CommonHelper::stdout("order_{$item['id']} timeout_reject\n");
                    $this->_fsReject($item['id'], '订单过期，复审拒绝');
                }
                catch (\Exception $e) {
                    CommonHelper::error(\sprintf("{$item['id']} timeout_reject_exception: %s.", $e));
                }

                continue;
            }

            #2.2
            $is_black = \yii::$container->get('loanBlackListService')->blackStatus($loan_person->id);
            if ($is_black) {
                CommonHelper::stderr( "order-{$item['id']}-{$loan_person->phone}, xybt_black_list_hit.\n" );
                $this->_fsReject($item['id'], '系统黑名单');
                continue;
            }


            #3. 手工控制放款单数 $order_total_count / $order_total_count_third
            $_order_count_key_third = sprintf('credit:order_count_third:%s', date('ymd'));
            $_order_count_key_gjj = sprintf('credit:order_count_gjj:%s', date('ymd'));
            $_order_count_key = sprintf('credit:order_count:%s', date('ymd'));
            $_order_count_key_old_user = sprintf('credit:order_count_old_user:%s', date('ymd'));

            $_order_real_count_key = sprintf('credit:order_real_count:%s', date('ymd'));
            if ($is_whitelist_phone) { //白名单 pass
                CommonHelper::stdout( "order-{$item['id']}-{$loan_person->phone}, is_whitelist_phone.\n" );
                \yii::$app->redis->incr($_order_real_count_key);
                $pass_type = 'white';
            }
            elseif ($loan_person->customer_type == LoanPerson::CUSTOMER_TYPE_OLD){
                //老用户逻辑
                if ($this->_reachOrderCount($_order_count_key_old_user, $order_total_count_old_user, $_order_real_count_key)) {
//                    CommonHelper::stderr( "order-{$item['id']}-{$loan_person->phone}, order_old_user_count_overload.\n" );
//                    $this->_fsReject($item['id'], '已达每日放款最大量，复审拒绝');
                    $key =  'risk_control_old_user_max_orders_notify';
                    if (!Yii::$app->cache->get($key)) {
                        $message = "用户放款，老用户已达每日放款限额,请及时处理";
                        MessageHelper::sendSMS(NOTICE_MOBILE, $message); #异常短信报警-刘小龙
                        MessageHelper::sendSMS(NOTICE_MOBILE, $message); #异常短信报警-李格

                        \yii::$app->cache->set($key, 1, 300);
                    }
                    continue;
                }
                $pass_type = 'old_user';
                $pass_type_int = UserLoanOrder::PASS_TYPE_OLD;
                if(AccumulationFund::validateAccumulationStatus($loan_person)){//老用户是否认证公积金
                    $pass_type_int = UserLoanOrder::PASS_TYPE_GJJ_OLD;
                }
            }
            elseif (AccumulationFund::validateAccumulationStatus($loan_person)) { //公积金 驳回到人工初审
                $admin_list = AdminUser::find()
                    ->where(['!=', 'callcenter', AdminUser::IS_LOAN_COLLECTION])
                    ->select('username')
                    ->asArray()->all();
                $admin_name_list = ArrayHelper::getColumn($admin_list, 'username');

//                if (in_array($item['operator_name'], $admin_name_list)) { //已经过人工审核，复审通过
                    if ($this->_reachOrderCount($_order_count_key_gjj, $order_total_count_gjj, $_order_real_count_key)) {
//                        CommonHelper::stderr( "order-{$item['id']}-{$loan_person->phone}, order_gjj_count_overload.\n" );
//                        $this->_fsReject($item['id'], '已达每日放款最大量，复审拒绝');
                        continue;
                    }

                    $pass_type = 'gjj';
                    $pass_type_int = UserLoanOrder::PASS_TYPE_GJJ;
                    CommonHelper::stdout( "order-{$item['id']}-{$loan_person->phone}, order_gjj_pass.\n" );
//                }
//                else {
//                    /*//驳回到人工初审
//                    $item->status = UserLoanOrder::STATUS_CHECK;
//                    $item->auto_risk_check_status = UserLoanOrder::AUTO_STATUS_SUCCESS;// 直接拒绝
//                    $item->is_hit_risk_rule = 0;
//                    $item->trail_time = time();
//
//                    if (! $item->save()) {
//                        CommonHelper::stderr( "order-{$item['id']}-{$loan_person->phone}, save_cs_failed.\n" );
//                    }*/
//                    $this->_fsReject($item['id'], '人工复审-公积金不存在直接拒绝');//机审 到人工复审   公积金不存在直接拒绝   18 - 4 -17
//                    continue;
//                }
            }else {
                if ($this->_reachOrderCount($_order_count_key, $order_total_count, $_order_real_count_key)) {
//                    CommonHelper::stderr( "order-{$item['id']}-{$loan_person->phone}, order_count_overload.\n" );
//                    $this->_fsReject($item['id'], '已达每日放款最大量，复审拒绝');
                    continue;
                }
                $pass_type = 'normal';
                $pass_type_int = UserLoanOrder::PASS_TYPE_NORMAL;
            }


            //设置限单类型
            $this->_setPassType($item, $pass_type_int);

            //复审通过
            $_order_real_count = \yii::$app->redis->get($_order_real_count_key);
            CommonHelper::stdout( "order-{$item['id']}-{$loan_person->phone}, order_count_current: {$_order_real_count}.\n" );

            $user_log_amount = $item->money_amount;
            $user_log_time = time() - $item->order_time;

            $financial_service->handleLoanMessage($loan_person->phone, $user_log_amount, $user_log_time); //处理借款消息队列
            $financial_service->handleDailyQuota($user_log_amount, $item->card_type); // 处理待抢金额

            $ret = $order_service->reviewPass($item, [ 'trail_time' => \time() ], $log, $op); //'auto_review'
            if ($ret['code'] != 0) {

                //保存到error信息中
                Yii::error('订单id：'.$item['id'].',复审分配资方失败，'.json_encode($ret));
                //今日放款金额已满，复审不要拒
                if($ret['code']==ErrCode::FUND_OVER_QUOTA){
                    continue;
                }

                CommonHelper::stderr( "order {$item['id']} failed: {$ret['message']}" );

                //分配资方失败，复审拒绝
                $key =  'risk_control_fund_money_max_notify';
                if (YII_ENV_PROD) {
                    if (!Yii::$app->cache->get($key)) {
                        $message = "用户放款，资方金额不足，请及时处理，订单id：{$item['id']}";
                        MessageHelper::sendSMS(NOTICE_MOBILE, $message); #异常短信报警
                        \yii::$app->cache->set($key, 1, 300);
                    }
                }
                $this->_fsReject($item['id'], '分配资方失败，复审拒绝');

                //重置限单数量
                switch ($pass_type) {
                    case 'gjj' :
                        $this->_resetOrderCount($_order_count_key_gjj , $_order_real_count_key);
                        break;
                    case 'third' :

                        $this->_resetOrderCount($_order_count_key_third, $_order_real_count_key);
                        break;
                    case 'normal' :
                        $this->_resetOrderCount($_order_count_key, $_order_real_count_key);
                        break;
                    case 'old_user' :
                        $this->_resetOrderCount($_order_count_key_old_user, $_order_real_count_key);
                        break;
                    default :
                        $curr_count = \yii::$app->redis->get($_order_real_count_key);
                        if ($curr_count > 0) {
                            \yii::$app->redis->decr($_order_real_count_key);
                        }
                        break;
                }
                continue;
            }
            else {
                //复审通过(分配资方),触发待放款回调
                $item->trigger(UserLoanOrder::EVENT_AFTER_PAY_READY, new \common\base\Event(['custom_data'=>[]]));
                CommonHelper::stderr( "order {$item['id']} success\n" );
            }
        }

        return self::EXIT_CODE_NORMAL;
    }

    public function actionRefreshRule() {
        //FIXME don't read from file.
        $orderIdStr = file_get_contents(__DIR__ . '/../../orderIds.txt');
        $orderIds = explode("\n", $orderIdStr);
        // $orderIds = array(1683735,1683719,1683712,1683710);
        $service = new RiskControlService();
        $now = time();
        $successOrderIdStr = '';
        $failOrderIdStr = '';
        $successCnt = 0;
        $failCnt = 0;

        $this->printMessage('开始运行脚本');
        if (is_array($orderIds) && count($orderIds) > 0) {
            foreach ($orderIds as $orderId) {
                try {

                    $order = UserLoanOrder::findOne($orderId);
                    if (empty($order)) {
                        $this->printMessage("order {$orderId} is null");
                        $failOrderIdStr .= ',' . $orderId;
                        $failCnt++;
                        continue;
                    }

                    $loan_person = LoanPerson::findOne($order->user_id);
                    if (empty($loan_person)) {
                        $this->printMessage("loan person {$order->user_id} is null");
                        $failOrderIdStr .= ',' . $orderId;
                        $failCnt++;
                        continue;
                    }

                    $result = $service->runSpecificRule2([260], $loan_person, $order);
                    if (isset($result['260']['value']) && !empty($result['260']['value'])) {
                        $successOrderIdStr .= ',' . $orderId;
                        $successCnt++;
                    } else {
                        $failOrderIdStr .= ',' . $orderId;
                        $failCnt++;
                    }

                } catch (\Exception $e) {
                    $failOrderIdStr .= ',' . $orderId;
                    $failCnt++;
                    var_dump($e->getMessage());
                    var_dump($e->getFile());
                    var_dump($e->getLine());
                }
                $this->printMessage("运行到订单{$orderId}");
            }
        } else
            $this->printMessage("没有订单");

        echo '总数：' . count($orderIds) . ',--成功数：' . $successCnt . ',--失败数：' . $failCnt . "\n\r";
        echo '成功orderIdStr：' . $successOrderIdStr . "\n\r";
        echo '失败orderIdStr：' . $failOrderIdStr . "\n\r";
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

    /*
     * 达到限单？
     */
    private function _reachOrderCount($key, $total, $total_key = '') {
        $curr_count = \yii::$app->redis->get($key);
        if (is_null($curr_count)) {
            \yii::$app->redis->set($key, 0);
            $curr_count = 0;
        }

        if ($curr_count < $total) { //未到最大量 pass
            \yii::$app->redis->incr($key);
            if ($total_key) {
                \yii::$app->redis->incr($total_key);
            }
            return false;
        }

        return true;
    }

    /**
     * 放款失败，重置限单数量
     * @param $key
     * @param string $total_key
     * @return bool 操作是否成功
     */
    private function _resetOrderCount($key, $total_key = '')
    {
        $curr_count = \yii::$app->redis->get($key);
        if (is_null($curr_count)) {
            return false;
        }

        if ($curr_count > 0) {
            \yii::$app->redis->decr($key);
            if ($total_key && $total_key > 0) {
                \yii::$app->redis->decr($total_key);
            }
            return true;
        }

        return false;
    }

    /*
     * 复审拒绝
     * @param $id 订单id
     * @return boolean
     */
    private function _fsReject($id, $remark='') {
        $_remark = empty($remark) ? '白名单：否' : $remark;
        $_op = Util::short(__CLASS__, __FUNCTION__);

        $loanPersonInfoService = \yii::$container->get("loanPersonInfoService");
        $information = $loanPersonInfoService->getPocketInfo($id);
        /* @var $info UserLoanOrder */
        $info = $information['info'];
        $credit = $information['credit'];

        $log = new UserOrderLoanCheckLog();
        $log->order_id = $id;
        $log->before_status = $info->status;
        $log->after_status = UserLoanOrder::STATUS_REPEAT_CANCEL;
        $log->operator_name = $_op;
        $log->type = UserOrderLoanCheckLog::TYPE_LOAN;
        $log->reason_remark = LoanPersonBadInfo::$reject_code['D1']['child']['07']['backend_name'];
        $log->remark = $_remark;
        $log->operation_type = UserOrderLoanCheckLog::LOAN_FS;
        $log->head_code = 'D1';
        $log->back_code = '07';

        $info->status = UserLoanOrder::STATUS_REPEAT_CANCEL;
        $info->reason_remark = LoanPersonBadInfo::$reject_code['D1']['child']['07']['frontedn_name'];
        $info->operator_name = $_op;
        $info->trail_time = time(); //审核时间

        //解除用户该订单锁定额度
        $credit->locked_amount = $credit->locked_amount - $info['money_amount'];

        //资金流水
        $user_credit_log = new UserCreditLog();
        $user_credit_log->user_id = $info['user_id'];
        $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_FS_CANCEL;
        $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
        $user_credit_log->operate_money = $info['money_amount'];
        $user_credit_log->created_at = time();
        $user_credit_log->created_ip = '106.15.41.23'; #脚本机ip
        $user_credit_log->total_money = $credit->amount;
        $user_credit_log->used_money = $credit->used_amount;
        $user_credit_log->unabled_money = $credit->locked_amount;

//        $loan_action = UserOrderLoanCheckLog::CAN_LOAN;
//        $log->can_loan_type = $loan_action;
//        $loanPerson = LoanPerson::findOne($info['user_id']);
//        if ($loan_action == UserOrderLoanCheckLog::CAN_LOAN) {
//            $loanPerson->can_loan_time = time() + 86400; #隔日再借
//        }
//        elseif ($loan_action == UserOrderLoanCheckLog::MONTH_LOAN) {
//            $loanPerson->can_loan_time = time() + 86400*30;
//        }
//        else {
//            $loanPerson->can_loan_time = 4294967295;
//        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if ($info->validate() && $log->validate() && $credit->validate() && $user_credit_log->validate()) {
                if ($info->save() && $log->save() && $credit->save() && $user_credit_log->save() ) { # && $loanPerson->save()
                    $transaction->commit();

                    //触发订单的审核拒绝事件 自定义的数据可添加到custom_data里
                    $info->trigger(UserLoanOrder::EVENT_AFTER_REVIEW_REJECTED, new \common\base\Event(['custom_data'=>['remark'=>$_remark, ]]));
                    print "{$id} reject success.\n";

                    return true;
                }
                else {
                    $transaction->rollBack();
                    print "{$id} save failed.\n";
                }
            }
            else {
                $info_err = $info->getErrors();
                $log_err = $log->getErrors();
                $credit_err = $credit->getErrors();
                $credit_log_err = $user_credit_log->getErrors();
                print \sprintf("{$id} validate failed [%s][%s][%s][%s].\n",
                    print_r($info_err, true), print_r($log_err, true), print_r($credit_err, true), print_r($credit_log_err, true));

                throw new \Exception("{$id} validate failed");
            }
        }
        catch (\Exception $e) {
            $transaction->rollBack();
        }

        return false;
    }

    /**
     * 设置订单限单类型
     * @param $loan_order 订单对象
     * @param $pass_type 限单类型
     */
    private function _setPassType($loan_order, $pass_type){
        $id = $loan_order['id'];
        $loan_order->pass_type = $pass_type;
        try {
            $ret = $loan_order->save();
        }catch(\Exception $e){
            CommonHelper::error(sprintf('设置订单限单类型失败 id: %s, pass_typ: %s, exception: %s', $id, $pass_type, $e), LogChannel::ORDER_RESULT);
        }
    }
}
