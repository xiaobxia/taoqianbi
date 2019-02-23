<?php
namespace console\controllers;

use common\api\RedisQueue;
use common\base\LogChannel;
use common\helpers\Util;
use common\models\fund\LoanFund;
use common\models\LoanPerson;
use common\services\FinancialService;
use common\services\fundChannel\JshbService;
use yii;
use yii\base\Exception;
use common\api\RedisXLock;
use common\models\CardInfo;
use common\models\Setting;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\FinancialLoanRecord;
use common\models\fund\FundAccount;
use common\helpers\MessageHelper;
use common\helpers\CommonHelper;

class FinancialLoanPayController extends BaseController {

    private static $phone_list = [
        NOTICE_MOBILE,
    ];

    public static function actionCheckNotifyFailed()
    {
        $records = FinancialLoanRecord::find()->where('callback_result NOT LIKE "{\"is_notify\":1%"' . ' and type = 1 and status = 4')->asArray()->all();
        $notice = '';
        foreach ($records as $record) {
            $order_id = $record['business_id'];
            $repay_order = UserLoanOrderRepayment::find()->where('order_id = ' . $order_id)->one();
            $loan_order = UserLoanOrder::findOne($order_id);
            if (!empty($repay_order) && $loan_order->status != UserLoanOrder::STATUS_PAY) {
                $re = FinancialLoanRecord::findOne($record['id']);
                $c_ret = ['is_notify' => 1, "message" => '已回调重置'];
                $re->callback_result = json_encode($c_ret);
                $re->save();
            }
            else {
                try {
                    //提现到账后处理
                    $service = \Yii::$container->get('financialCommonService');
                    $back_result = $service->successLoanOrder($record->business_id, '打款成功', $record->review_username, $record->type);
                    $is_notify = ($back_result['code'] === 0) ? FinancialLoanRecord::NOTIFY_SUCCESS : FinancialLoanRecord::NOTIFY_FALSE;
                } catch (Exception $e) {
                    $is_notify = FinancialLoanRecord::NOTIFY_FALSE;
                }
                $callback_result = [
                    'is_notify' => $is_notify,
                    'message' => isset($back_result['message']) ? $back_result['message'] : ''
                ];
                $record->callback_result = json_encode($callback_result);
                $record->save();
                if (!$record || $is_notify == FinancialLoanRecord::NOTIFY_FALSE) {
                    $notice .= $order_id . ",";
                }
            }
        }
        if (!empty($notice)) {
            $phone_msg = '以下借款订单打款已成功，但未生成还款计划，请关注，订单号列表：' . $notice;
            foreach (self::$phone_list as $value) {
                MessageHelper::sendSMS($value, $phone_msg);
            }
        }
    }


    /**
     * 查询订单打款状态
     * financial-loan-pay/search-order-status
     */
    public function actionSearchOrderStatus()
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $where = [
            'l.status' => [FinancialLoanRecord::UMP_CMB_PAYING,FinancialLoanRecord::UMP_PAY_DOUBLE_FAILED],
            'l.type' => FinancialLoanRecord::$kd_platform_type,
            'l.review_result' => [FinancialLoanRecord::REVIEW_STATUS_APPROVE,FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED],
            'l.payment_type' => FinancialLoanRecord::PAYMENT_TYPE_CMB,
            'r.fund_id' => [LoanFund::ID_KOUDAI],
        ];

        $time = time() - 1200;
        $records = FinancialLoanRecord::find()
            ->from(FinancialLoanRecord::tableName().' as l')
            ->leftJoin(UserLoanOrder::tableName().' as r','l.business_id=r.id')
            ->where($where)
//            ->andWhere(['<=', 'l.updated_at',$time])
            ->select(['l.id', 'l.order_id', 'l.updated_at', 'l.review_result', 'l.status', 'l.business_id','r.fund_id'])
            ->asArray()->limit(5000)->all();
        if (empty($records)) {
            print "FinancialLoanRecord empty\n";
            return self::EXIT_CODE_NORMAL;
        }
        echo "符合查询要求的共计 ".count($records)."笔订单";

        foreach ($records as $record) {
            echo "打款ID{$record['id']}开始查询\n";
            $loan = FinancialLoanRecord::findOne($record['id']);
            if (!$loan) {
                CommonHelper::error( \sprintf('[%s][%s] %s loan missing.', __CLASS__, __FUNCTION__, $record['id']) );
                continue;
            }
            if ($loan['order_id'] != $record['order_id'] ||
                $loan['updated_at'] != $record['updated_at'] ||
                $loan['review_result'] != $record['review_result'] ||
                $loan['status'] != $record['status']
            ) {
                CommonHelper::error( \sprintf('[%s][%s] %s donot match %s.', __CLASS__, __FUNCTION__, $record['id'], $loan['order_id']) );
                continue;
            }
            switch ($record['fund_id']) {
                case LoanFund::ID_KOUDAI:
                    $project_name = FinancialService::KD_PROJECT_NAME;
                    $this->queryJshbPaymentResult($loan, $record);
                    echo "打款ID{$record['id']}资方为".APP_NAMES."\n";
                    break;
                default:
                    continue;
                    break;
            }
        }
    }


    /**
     * 查询需人工处理订单的打款状态
     * financial-loan-pay/query-order-status
     */
    public function actionQueryOrderStatus()
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $where = [
            'status' => [FinancialLoanRecord::UMP_PAY_HANDLE_FAILED],
            'type' => FinancialLoanRecord::$kd_platform_type,
            'review_result' => [FinancialLoanRecord::REVIEW_STATUS_APPROVE,FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED],
            'payment_type' => FinancialLoanRecord::PAYMENT_TYPE_CMB,
        ];
        $records = FinancialLoanRecord::find()
            ->where($where)
            ->select(['id', 'order_id', 'updated_at', 'review_result', 'status', 'business_id'])
            ->asArray()->limit(1000)->all();
        if (empty($records)) {
            print "FinancialLoanRecord empty\n";
            return self::EXIT_CODE_NORMAL;
        }

        foreach ($records as $record) {
            $loan = FinancialLoanRecord::findOne($record['id']);
            if (!$loan) {
                CommonHelper::error( \sprintf('[%s][%s] %s loan missing.', __CLASS__, __FUNCTION__, $record['id']) );
                continue;
            }
            if ($loan['order_id'] != $record['order_id'] ||
                $loan['updated_at'] != $record['updated_at'] ||
                $loan['review_result'] != $record['review_result'] ||
                $loan['status'] != $record['status']
            ) {
                CommonHelper::error( \sprintf('[%s][%s] %s donot match %s.', __CLASS__, __FUNCTION__, $record['id'], $loan['order_id']) );
                continue;
            }

            $params = [
                'biz_order_no' => $loan['order_id'],
            ];
            $service = new JshbService();
            $ret = $service->queryLoanRecord($params);
            if (!$ret) {
                CommonHelper::error( \sprintf('[%s][%s] post failed.', __CLASS__, __FUNCTION__) );
                continue;
            }

            if (isset($ret['data']['error_code'])){
                $loan->updated_at = time();
                $loan->remit_status_code = $ret['data']['error_code'];
                if ($loan->save()) {
                    echo 'success ' . $ret['code'] . 'id:' . $record['id'] . "\n";
                }
            }

        }
    }

    private function sendAlertMessage($message, $phone_list = []) {
        $phones = !empty($phone_list) ? $phone_list : self::$phone_list;
        foreach ($phones as $phone) {
            MessageHelper::sendSMS($phone, $message);
        }
    }


    /**
     * 极速荷包直连打款-v1版本
     */
    public function actionLiteWithdrawJshb($id = 0)
    {
        $tag = 'actionLiteWithdrawJshb';
        if (YII_ENV_PROD){
            if (!RedisXLock::lock($tag,300)) {
                echo '处理跳过';
                return self::EXIT_CODE_ERROR;
            }
        }
        if (Setting::checkSendWithdrawCmb() == false){
            echo '打款关闭';
            return self::EXIT_CODE_ERROR;
        }


        $_types = \array_diff(FinancialLoanRecord::$kd_platform_type, FinancialLoanRecord::$kd_platform_cash_type);
        $type = "(" . implode(",", $_types) . ")";
        $fund_id = LoanFund::ID_KOUDAI;//资方ID
        $max_id = 0;
        $id_where = $id ? ' and w.id='.$id : '';
        $time = time();
        $hour = date('H',time());
//        $bank_where = ($hour > 22 || $hour < 6) ? ' and w.bank_id != 8 ' : '';
        $bank_where = '';
        // 待打款或者2天内打款失败
        $sql = "select
                w.id, w.updated_at, w.user_id as user_id, w.money, w.pay_summary, w.counter_fee as fee, w.bank_id, w.card_no, w.bind_card_id,
                w.bank_name, w.order_id, w.review_result,
                w.type, w.status, w.payment_type, u.name as real_name, o.fund_id
                from tb_financial_loan_record as w
                    INNER JOIN tb_user_loan_order as o on w.business_id=o.id
                    INNER JOIN tb_loan_person as u on w.user_id=u.id
                where
                ((w.status =" . FinancialLoanRecord::UMP_PAYING . " and
                    w.review_result=" . FinancialLoanRecord::REVIEW_STATUS_APPROVE . ") or (
                    w.review_result=" . FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED . " and w.created_at >= ".($time-2*86400)."))
                and w.type in $type $bank_where
                and w.payment_type=" . FinancialLoanRecord::PAYMENT_TYPE_CMB . "
                and o.fund_id = $fund_id
                and w.id> :id ".$id_where."
                order by w.id asc limit 0,500";
        $records = Yii::$app->db_kdkj->createCommand($sql,[':id'=>$max_id])->queryAll();
        echo '符合条件打款['.count($records).']笔，处理中...';
        $op = Util::short(__CLASS__, __FUNCTION__);
        $error_count = 0;
        while ($records) {
            foreach ($records as $record) {
                if (!FinancialLoanRecord::addLock('LI' . $record['order_id'])) { //避免重复申请打款
                   continue;
                }

                $loan = FinancialLoanRecord::findOne($record['id']);

                if (!$loan) {
                    \yii::error( \sprintf('[%s][%s] %s loan missing.', __CLASS__, __FUNCTION__, $record['id']) );
                    continue;
                }

                if ($loan['order_id'] != $record['order_id'] ||
                    $loan['updated_at'] != $record['updated_at'] ||
                    $loan['review_result'] != $record['review_result'] ||
                    $loan['status'] != $record['status']
                ) {
                    CommonHelper::error( \sprintf('[%s] loan(%s) donot match rd(%s).', $op, $loan['order_id'], $record['order_id']), LogChannel::FINANCIAL_PAYMENT);
                    continue;
                }

                $loan_person = LoanPerson::find()->select(['source_id'])->where(['id'=>$record['user_id']])->one();

                $summary = isset(LoanPerson::$app_loan_source[$loan_person['source_id']]) ? LoanPerson::$app_loan_source[$loan_person['source_id']].'借款放款' : APP_NAMES.'借款放款';
                echo "{$summary}\n";

                try {
                    $order = UserLoanOrder::findOne($loan['business_id']);
      if(empty($order)){
                        \yii::error( \sprintf('[%s][%s] orderid=%s order missing.', __CLASS__, __FUNCTION__, $loan['order_id']) );
                        continue;
                    }
                    // 推送订单
                    // TODO:alexding use Yii to get service
                    $ret = $order->loanFund->getService()->pushOrder($order);
 var_dump($ret);
                   if ($ret && isset($ret['code']) && \in_array($ret['code'], [0,1000,1003])) {//生成记录 和 进行中
                        CommonHelper::info(\sprintf('打款成功： %s', \var_export($ret, true)));
                        $loan->status = FinancialLoanRecord::UMP_CMB_PAYING;
                        $loan->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
                        $loan->review_remark .= "&" . date("y-m-d H:i:s", time()) . ": 重新发起打款&";
                        $loan->updated_at = time();
                        if (!$loan->save()) {
                            CommonHelper::error(\sprintf('[%s][%s] FinancialLoanRecord update failed', __CLASS__, __FUNCTION__), LogChannel::FINANCIAL_PAYMENT);
                        }
                    }
                    elseif($ret && isset($ret['code']) && \in_array($ret['code'], [99])){
                        $message = sprintf(sprintf('打款接口返回失败： %s', \var_export($ret, true))) . "放款ID：{$loan['id']},订单ID：{$loan['order_id']}";
                        CommonHelper::error($message, LogChannel::FINANCIAL_PAYMENT);
//                        $this->sendAlertMessage($message);
                        $loan->status = FinancialLoanRecord::UMP_PAY_HANDLE_FAILED;
                        $loan->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
                        $loan->review_remark .= "&" . date("y-m-d H:i:s", time()) . ": 重新发起打款&";
                        $loan->updated_at = time();
                        if (!$loan->save()) {
                            CommonHelper::error(\sprintf('[%s][%s] FinancialLoanRecord update failed', __CLASS__, __FUNCTION__), LogChannel::FINANCIAL_PAYMENT);
                        }
                    }
                    elseif(isset($ret['code']) && \in_array($ret['code'], [1])) {//异常
                        var_dump(\common\helpers\CurlHelper::$http_info);
                        $message = sprintf(sprintf('打款接口请求无响应： %s', \var_export($ret, true))) . "放款ID：{$loan['id']},订单ID：{$loan['order_id']}";
                        CommonHelper::error($message, LogChannel::FINANCIAL_PAYMENT);
                        $key =  'Exception_' . __FUNCTION__ . '_' . (isset($ret['code'])? $ret['code'] : 9999);
                        if (!Yii::$app->cache->get($key)) {
                            //                            MessageHelper::sendSMS(NOTICE_MOBILE, $message); #异常短信报警-刘小龙
                            \yii::$app->cache->set($key, 1, 300);
                        }
                    }
                }
                catch (\Exception $e) {
                    CommonHelper::error(\sprintf('[%s][%s] exception: %s', $op, $e->getMessage().$e->getLine() ) , LogChannel::FINANCIAL_PAYMENT);
                    $error_count++;
                }

                $max_id = $record['id'];
                $records = Yii::$app->db_kdkj->createCommand($sql,[':id'=>$max_id])->queryAll();
                CommonHelper::stdout( "符合条件打款[".count($records)."]笔，处理中...\n" , LogChannel::FINANCIAL_PAYMENT);
            }
        }

        if ($error_count > 20) {
            $this->sendAlertMessage(APP_NAMES.'打款申请失败总数:' . $error_count);
        }

        CommonHelper::stdout( \sprintf("[%s] finished\n", Util::short(__CLASS__, __FUNCTION__)) , LogChannel::FINANCIAL_PAYMENT);
        if (YII_ENV_PROD){
            RedisXLock::unlock($tag);
        }
    }


    /**
     * 查询极速荷包发标结果
     * @param $loan
     * @param $record
     */
    private function queryJshbPaymentResult($loan, $record) {
        $order = UserLoanOrder::findOne($loan['business_id']);
        if(empty($order)){
            \yii::error( \sprintf('[%s][%s] orderid=%s order missing.', __CLASS__, __FUNCTION__, $loan['order_id']) );
            return;
        }
        // 查询推送订单
        // TODO:alexding use Yii to get service
        $ret = $order->loanFund->getService()->queryOrder($order);
        if (isset($ret['code']) && \in_array($ret['code'], [1003])) { // 代付成功
            echo "打款ID{$record['id']}查询结果为放款成功\n";
            $loan->status = FinancialLoanRecord::UMP_PAY_SUCCESS;
            if (!$loan->success_time && isset($ret['data']['opr_dat'])) {
                $loan->success_time = CommonHelper::getSuccessTime($ret['data']['opr_dat']);
            } else {
                $loan->success_time = time();
            }
            try { // 提现到账后处理
                $service = \Yii::$container->get('financialCommonService');
                $back_result = $service->successLoanOrder($loan->business_id, '打款成功', $loan->review_username, $loan->type,$loan->success_time);
                $is_notify = ($back_result['code'] === 0) ? FinancialLoanRecord::NOTIFY_SUCCESS : FinancialLoanRecord::NOTIFY_FALSE;
                if($is_notify==FinancialLoanRecord::NOTIFY_FALSE){
                    Yii::error('error：'.$loan['id'].' '.json_encode($back_result,JSON_UNESCAPED_UNICODE),'notify_no_order_backresult');
                }
                //触发放款成功事件
                $user_loan_order = UserLoanOrder::findOne($loan->business_id);
                $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_PAY_SUCCESS, new \common\base\Event(['custom_data'=>[]]));
                //放款成功时,添加成功信息到队列中
                RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_LOAN_INFO,json_encode([
                    'code' => '1001',
                    'user_id' => $loan->user_id,
                    'order_id' => $loan->business_id,
                    'loan_money' => $loan->money
                ])]);
            }
            catch (Exception $e) {
                Yii::error('error：'.$loan['id'].' '.json_encode($e,JSON_UNESCAPED_UNICODE),'notify_no_order_error');
                $is_notify = FinancialLoanRecord::NOTIFY_FALSE;
            }
            //通知业务方失败，短信报警
            if($is_notify == FinancialLoanRecord::NOTIFY_FALSE){
                $message = "放款订单号：{$loan['id']} ,放款成功，业务方通知失败，请处理";
                $this->sendAlertMessage($message);
            }
            $callback_result = [
                'is_notify' => $is_notify,
                'message' => isset($back_result['message']) ? $back_result['message'] : ''
            ];
            $loan->callback_result = json_encode($callback_result);
            if ($loan->save()) {
                echo 'success ' . $ret['code'] . 'id:' . $record['id'] . "\n";
            }
        }elseif (isset($ret['code']) && $ret['code'] == 1004 ){//TODO 代付失败处理 , 审核驳回&&打款失败 逻辑
            echo "打款ID{$record['id']}查询代付失败：\n";
            var_dump($ret);
            $loan->review_result = FinancialLoanRecord::REVIEW_STATUS_REJECT;
            $loan->status = FinancialLoanRecord::UMP_PAY_FAILED;
            if ($loan->save()) {
                echo 'success ' . $ret['code'] . 'id:' . $record['id'] . "\n";
            }
            $message = sprintf('用户放款状态异常，代付失败:放款ID[%s][%s]', $loan['id'], print_r($ret,true));
            Yii::error($message, LogChannel::FINANCIAL_PAYMENT);
            $key = 'Exception_loan_pay_weidai_notify';
            // 1分钟之内不重复发送
            if (!Yii::$app->cache->get($key)) {
                $this->sendAlertMessage($message);
                \yii::$app->cache->set($key, 1, 60);
            }
        }elseif (isset($ret['code']) && $ret['code'] != 0 ){//异常
            if ($ret['code'] != 1000){//排除  代付进行中 的订单
                echo "打款ID{$record['id']}查询结果异常：\n";
            }

            var_dump($ret);

        }
    }


    /**
     * 极速荷包直连测试Service
     */
    public function actionTest($id = 0)
    {
        if ($id){
            $order = UserLoanOrder::findOne($id);
            // 推送订单
            // TODO:alexding use Yii to get service
            $ret = $order->loanFund->getService()->pushOrder($order);
//        $ret = $order->loanFund->getService()->queryOrder($order);
//        $ret = $order->loanFund->getService()->preSign($order);
            var_dump($ret);
        }
    }

}
