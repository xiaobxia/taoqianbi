<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Application;
use yii\web\Response;
use yii\helpers\Url;
use api\models\User;
use common\base\LogChannel;
use common\helpers\CurlHelper;
use common\models\AutoDebitLog;
use common\models\BankConfig;
use common\models\CardInfo;
use common\models\FinancialDebitRecord;
use common\models\FinancialLoanRecord;
use common\models\FinancialLog;
use common\models\fund\LoanFund;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\DeductMoneyLog;
use common\models\UserCreditMoneyLog;
use common\models\UserVerification;
use common\helpers\StringHelper;

use common\exceptions\PayException;
use common\helpers\MessageHelper;
use common\models\UserLoanOrderRepayment;
use common\models\Order;
use common\models\MessageLog;
use common\models\UserCreditReviewLog;
use common\models\Setting;
use common\api\RedisQueue;
use common\helpers\CommonHelper;

class FinancialService extends Component
{
    const KD_PROJECT_NAME = 'tqb'; #项目号
    const KD_PROJECT_PWD = 'test'; #项目密码
    const KD_MERCHANT_ID = 'test'; #商户号
    //速8项目号
    const SUBA_PROJECT_NAME = 'test';
    const SUBA_PROJECT_PASSWOD = 'test';
    //益码通付项目号
    const KD_PROJECT_NAME_ALIPAY = 'test';

    const WZD_USERNAME = 'test';
    const WZD_PASSWORD = 'test';
    const WZD_HOST = 'https://test.abc.com/';
    const WZD_PROJECT_NAME  = 'test';

    //汇潮支付配置
    const  KD_HC_HOST_URL = 'https://alipay.3c-buy.com/api/createOrder?';
    const  KD_HC_NOTIFY_URL = 'http://'.SITE_DOMAIN.'/frontend/web/notify/hc-aliypay-debit-callback-new';
    const  KD_HC_QUERY_URL = 'http://jh.chinambpc.com/api/queryOrder';
    const  KD_HC_MERID = 'yft2018033000009'; //汇潮支付 商户号
    const  KD_HC_KEY = 'kJ1Pbx8xzhutvAlAL6QRPd21iMRDwO3r';//汇潮支付 密钥


    /**
     * 生成温州贷Token
     * @return string
     */
    public static function generateWzdToken()
    {
        $tokenKey = 'wzdai_token';
        $tokenValue = RedisQueue::get(['key'=>$tokenKey]);
        $url = self::WZD_HOST.'users/login';
        if (empty($tokenValue))
        {
            $ret = CurlHelper::wzdCurl($url,'POST',['phone'=>self::WZD_USERNAME,'password'=>self::WZD_PASSWORD,'platformType'=>1]);
            if ($ret['status_code'] == 200 && isset($ret['data']['token'])) {
                RedisQueue::set(['expire'=>3600,'key'=>$tokenKey,'value'=>$ret['data']['token']]);
                return $ret['data']['token'];
            } else {
                MessageHelper::sendSMS(NOTICE_MOBILE,'温州登陆接口失败,token 生成失败');
            }
        }
        return $tokenValue;
    }

    /**
     * 生成打款订单
     * @param $data
     */
    public function createFinancialLoanRecord($data){
        try {

            if (Yii::$app instanceof \yii\web\Application) {
                if ( empty(\yii::$app->user->identity) ) {
                    throw new Exception("抱歉，请先登录");
                }

                $username = \yii::$app->user->identity->username;
            }
            else {
                $username = 'console';
            }

            $user_id = $data['user_id'];//借款人ID
            $bind_card_id = intval($data['bind_card_id']);//绑卡自增表ID
            $business_id = intval($data['business_id']);//业务订单主键ID
            $type = intval($data['type']);//业务类型
            $payment_type = intval($data['payment_type']);//打款类型
            $money = $data['money'];//打款金额
            $bank_id = intval($data['bank_id']);//银行卡ID
            $bank_name = $data['bank_name'];//银行名称
            $card_no = $data['card_no'];//银行卡号
            $counter_fee = $data['counter_fee'];//手续费
            $created_username = $username;//创建管理员名称
            $pay_summary = isset($data['pay_summary']) ? $data['pay_summary'] : "";//打款摘要
            if (empty($bind_card_id) || empty($business_id) || empty($type) || empty($payment_type)
                    || empty($money) || ($money <= 0 ) || empty($card_no) || empty($user_id)) {

                throw new Exception("抱歉，缺少必要的参数！");
            }
            if (\in_array($type, FinancialLoanRecord::$kd_platform_type)) {//第三方合作，无需验证
                if (empty($bank_id) || empty($bank_name)) {
                    throw new Exception("抱歉，缺少必要的参数！");
                }
            }

            $loan_data = FinancialLoanRecord::find()->where([
                'type' => $type,
                'business_id' => $business_id,
                'status' => [FinancialLoanRecord::UMP_PAYING, FinancialLoanRecord::UMP_PAY_SUCCESS, FinancialLoanRecord::UMP_CMB_PAYING],
            ])->one();
            if (!empty($loan_data)) {
                throw new Exception("抱歉，正在处理的打款订单号，不能重复添加！");
            }

            $loan_person = LoanPerson::findOne($user_id);
            if (\in_array($type, FinancialLoanRecord::$kd_platform_type)) { //第三方合作，无需验证
                $user_verify = UserVerification::findOne(['user_id' => $user_id]);
                if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                    throw new Exception("抱歉，该用户没有通过实名认证");
                }
                if (!array_key_exists($bank_id, BankConfig::$bankInfo)) {
                    throw new Exception("抱歉，不支持的打款银行");
                }
            }

            if (empty($loan_person)) {
                throw new Exception("抱歉，非平台用户");
            }

            $card_info = CardInfo::findOne($bind_card_id);
            if (empty($card_info)) {
                throw new Exception("抱歉，银行卡不存在");
            }
            if (!array_key_exists($type, FinancialLoanRecord::$types)) {
                throw new Exception("抱歉，不支持的业务类型");
            }
            if (!array_key_exists($payment_type, FinancialLoanRecord::$payment_types)) {
                throw new Exception("抱歉，不支持的打款类型");
            }

            $query = new FinancialLoanRecord();
            $query->user_id = $user_id;
            $query->order_id = self::generateOrderId($payment_type, $user_id);//订单号
            $query->bind_card_id = $bind_card_id;
            $query->business_id = $business_id;
            $query->type = $type;
            $query->payment_type = $payment_type;
            $query->status = FinancialLoanRecord::UMP_PAYING;
            $query->review_result = FinancialLoanRecord::REVIEW_STATUS_NO;
            $query->money = $money;
            $query->bank_id = $bank_id;
            $query->bank_name = $bank_name;
            $query->card_no = $card_no;
            $query->counter_fee = $counter_fee;
            $query->created_username = $created_username;
            $query->pay_summary = $pay_summary;
            if ($query->save()) {
                if ($type == FinancialLoanRecord::TYPE_LQD) {
                    RedisQueue::push([RedisQueue::LIST_USER_DATA_WALL, json_encode(['user_id'=>$user_id,'type'=>1])]);
                }
                return [
                    'code' => 0,
                    'message' => '插入成功',
                ];
            }
        }
        catch (Exception $e) {
            return [
                'code' => 1,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * 生成扣款订单
     * @param $data
     */
    public function createFinancialDebitRecord($data){
        try {
            if ((Yii::$app instanceof \yii\web\Application) && !empty(Yii::$app->user->identity)) {
                $username = Yii::$app->user->identity->username;
            } elseif ((Yii::$app instanceof \yii\web\Application) && empty(Yii::$app->user->identity)) {
                $username = 'callback';
            } else {
                $username = "console";
            }

            $user_id = intval($data['user_id']);//借款人ID
            $debit_card_id = intval($data['debit_card_id']);//扣款银行卡ID
            $type = intval($data['type']);//业务类型：1、员工帮，2、第三方合作
            $repayment_id = intval($data['repayment_id']);//总期还款ID
            $repayment_peroid_id = intval($data['repayment_peroid_id']);//分期还款计划ID
            $loan_record_id = $data['loan_record_id'];//原订单表ID
            $plan_repayment_money = intval($data['plan_repayment_money']);//预期还款金额
            $plan_repayment_principal = $data['plan_repayment_principal'];//预期还款本金
            $plan_repayment_interest = $data['plan_repayment_interest'];//预期还款利息
            $plan_repayment_late_fee = $data['plan_repayment_late_fee'];//滞纳金：单位分
            $plan_repayment_time = $data['plan_repayment_time'];//预期还款时间
            if (empty($user_id) || empty($debit_card_id) || empty($type) || empty($repayment_id)
                || empty($loan_record_id) || empty($plan_repayment_money) || empty($plan_repayment_time)) {
                throw new Exception("抱歉，缺少必要的参数！");
            }


            $old_record = FinancialDebitRecord::find()
                ->where(['user_id'=>$user_id,'loan_record_id'=>$loan_record_id])
                ->orderBy(['id'=>SORT_DESC])->one();

            if ($old_record && ($old_record->status == FinancialDebitRecord::STATUS_RECALL)){
                $old_record->created_at = time();
                $old_record->plan_repayment_money = $plan_repayment_money;
                $old_record->plan_repayment_interest = $plan_repayment_interest;
                $old_record->plan_repayment_late_fee = $plan_repayment_late_fee;
                if($old_record->save()){
                    return [
                        'code' => 0,
                        'message' => '插入成功',
                    ];
                }
            }
            else{
                if($old_record && ($old_record->status == FinancialDebitRecord::STATUS_PAYING)){
                    $old_record->status = FinancialDebitRecord::STATUS_REFUSE;
                    $old_record->save();
                }

                $query = new FinancialDebitRecord();
                $query->status = FinancialDebitRecord::STATUS_PAYING;
                $query->user_id = $user_id;
                $query->debit_card_id = $debit_card_id;
                $query->type = $type;
                $query->repayment_id = $repayment_id;
                $query->repayment_peroid_id = $repayment_peroid_id;
                $query->loan_record_id = $loan_record_id;
                $query->plan_repayment_money = $plan_repayment_money;
                $query->plan_repayment_principal = $plan_repayment_principal;
                $query->plan_repayment_interest = $plan_repayment_interest;
                $query->plan_repayment_late_fee = $plan_repayment_late_fee;
                $query->plan_repayment_time = $plan_repayment_time;
                $query->admin_username = $username;
                if ($query->save()) {
                    return [
                        'code' => 0,
                        'message' => '插入成功',
                    ];
                }
            }



        } catch (Exception $e) {
            return [
                'code' => 1,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 审核提现通过
     * @param $id
     * @param $payment_type
     * @param string $review_remark
     * @param $review_username
     * @return bool
     * @throws \Exception
     */
    public function newWithdrawApprove($id, $payment_type, $review_remark = "", $review_username)
    {
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $withdraw = FinancialLoanRecord::findOne($id);
            $uid = $withdraw->user_id; // 查找用户ID对应的绑卡平台
            $user = LoanPerson::findOne($uid);
            $user_name = $user->name;
            if (!empty($payment_type) && isset($payment_type)) {
                switch ($payment_type) {
                    case FinancialLoanRecord::PAYMENT_TYPE_CMB:    //直连打款
                        break;
                    case FinancialLoanRecord::PAYMENT_TYPE_MANUAL: //人工打款
                        break;
                    default:
                        throw new Exception("不支持的打款渠道!");
                        break;
                }
            } else {
                throw new UserException('请选择打款渠道');
            }
            $withdraw->review_username = $review_username;
            $withdraw->review_time = time();
            $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
            $withdraw->payment_type = $payment_type;
            $withdraw->review_remark = $review_remark;
            if ($withdraw->save()) {
            } else {
                throw new UserException('提现审核失败，请稍后再试');
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 处理借款消息队列
     */
    public function handleLoanMessage($phone,$amount,$log_time){
        $log_phone = strlen($phone) > 4 ? substr($phone,-4) : $phone;
        $log_money = intval($amount/100);

        $hour      = floor($log_time / 3600);
        //$min       = floor(($log_time - $hour * 3600) / 60);
        $min = \rand(2,5);//随机时间
        if ($min > 5) {
            $log_time = "5分钟";
        }else{
            $log_time = $min ."分钟";
        }
        $log_tmpl  = "**%s成功借款%s元，申请至放款耗时%s";

        $log_cache_key = RedisQueue::LIST_USER_LOAN_LOG_MESSAGE;
        $str_log_str   = \sprintf($log_tmpl, $log_phone, $log_money, $log_time);

        // 额度大于200 才显示
        if (intval($log_money) > 200) {
            RedisQueue::push([$log_cache_key, $str_log_str]);
            RedisQueue::getFixedLength([$log_cache_key, 0 , intval(RedisQueue::USER_LOAN_QUEUE_MAX_LENGTH) - 1]);
        }
    }

    /**
     * 处理首页提高额度消息队列
     */
    public function handleIncreaseMessage($phone,$amount){
        $log_phone = strlen($phone) > 4 ? substr($phone,-4) : $phone;
        $log_money = intval($amount/100);

        $log_tmpl  = "**%s正常还款，成功提额至%s元";

        $log_cache_key = RedisQueue::LIST_USER_INCREASE_LOG_MESSAGE;
        $str_log_str   = sprintf($log_tmpl,$log_phone,$log_money);

        RedisQueue::push([$log_cache_key,$str_log_str]);
        RedisQueue::getFixedLength([$log_cache_key, 0 , intval(RedisQueue::USER_LOAN_QUEUE_MAX_LENGTH) - 1]);

    }

    /**
     * 操作每日待抢金额
     */
    public function handleDailyQuota($money_amount, $card_type='1'){
        if ($card_type == 2) {
            $this->handleGoldenDailyQuota($money_amount);
        }
        else{
            $user_arr_enlarge_ratio = Setting::findByKey('app_enlarge_ratio');
            $user_enlarge_ratio = isset($user_arr_enlarge_ratio["svalue"]) ? $user_arr_enlarge_ratio["svalue"] : "200";

            $max_loan_cache_key = sprintf("%s:%s", RedisQueue::USER_TODAY_LOAN_MAX_AMOUNT, date("Ymd"));
            $max_loan_amount  = RedisQueue::get(['key'=>$max_loan_cache_key]);
            if (!$max_loan_amount) {
                $user_arr_daily_quota = Setting::findByKey('app_max_daily_quota');
                $max_loan_amount = isset($user_arr_daily_quota["svalue"]) ? $user_arr_daily_quota["svalue"] : "1000000000" ;
            }

            $user_loan_amount_log = intval($max_loan_amount) - intval((intval($user_enlarge_ratio) / 100) * $money_amount);

            $expire_time = strtotime(date('Y-m-d 23:59:59', time())) - time();
            RedisQueue::set(["expire"=> $expire_time,"key"=>$max_loan_cache_key,"value"=> intval($user_loan_amount_log)]);
        }
    }

    /**
     * 操作金卡的每日待抢金额
     */
    public function handleGoldenDailyQuota($money_amount){
        $max_loan_cache_key = sprintf("%s:%s",RedisQueue::USER_TODAY_LOAN_GOLDEN_AMOUNT,date("Ymd"));
        $max_loan_amount    = RedisQueue::get(['key'=>$max_loan_cache_key]);
        if (!$max_loan_amount) {
            $user_arr_daily_quota = Setting::findByKey('app_golden_daily_quota');
            $max_loan_amount = isset($user_arr_daily_quota["svalue"]) ? $user_arr_daily_quota["svalue"] : "200000000" ;
        }

        $user_loan_amount_log = intval($max_loan_amount) - intval($money_amount);
        $expire_time = strtotime(date('Y-m-d 23:59:59', time())) - time();
        RedisQueue::set(["expire"=> $expire_time,"key"=>$max_loan_cache_key,"value"=> intval($user_loan_amount_log)]);
    }

    /**
     * 提现成功后处理( 新版本)
     * @param $order_id 订单ID
     * @param int $manual
     * @return bool
     * @throws \yii\db\Exception
     * @author hezhuangzhuang@koudailc.com
     */
    public function withdrawHandleSuccess($order_id, $manual = 0)
    {
        $withdraw = FinancialLoanRecord::findOne(['order_id' => $order_id]);
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            // 如果已经是提现成功则不重复处理
            if ($withdraw->status == FinancialLoanRecord::UMP_PAY_SUCCESS) {
                $transaction->commit();
                return true;
            }
            $withdraw->status = FinancialLoanRecord::UMP_PAY_SUCCESS;
            if ($manual == 0) {

            } elseif ($manual == 1) {
                $withdraw->payment_type = FinancialLoanRecord::PAYMENT_TYPE_MANUAL;
            } elseif ($manual == 2) {
                 $withdraw->review_result = FinancialLoanRecord::REVIEW_STATUS_APPROVE;
                 $withdraw->payment_type = FinancialLoanRecord::PAYMENT_TYPE_CMB;
            }
            if (!$withdraw->save()) {
                throw new \Exception('提现记录修改失败');
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 打款根据对应的业务类型去对账
     * @param $withdraw
     */
    public function withdrawCheckLoanOrder($withdraw){
        $withdraw_money = $withdraw['money'];//打款金额
        $withdraw_counter_fee = $withdraw['counter_fee'];//打款手续费
        $withdraw_status = $withdraw['status'];//打款状态
        $withdraw_user_id = $withdraw['user_id'];//打款用户ID
        $withdraw_business_id = $withdraw['business_id'];//打款业务订单ID
        $withdraw_bank_id = $withdraw['bank_id'];//打款银行ID
        $withdraw_card_no = $withdraw['card_no'];//打款银行卡号
        $service = Yii::$container->get('financialCommonService');
        $response = $service->checkLoanOrder($withdraw_business_id, $withdraw['type']);
        if ($response['code'] == 0) {
            $response_data = $response['data'];
        } else {
            throw new Exception("打款对账失败原因：".$response['message']);
        }
        if (!isset($response_data['money']) || !isset($response_data['status']) || !isset($response_data['user_id']) || !isset($response_data['bank_id']) || !isset($response_data['card_no'])) {
            throw new Exception("打款对账返回数据不完整！");
        }
        $order_money = intval($response_data['money']);//订单金额
        $order_counter_fee = intval($response_data['counter_fee']);//订单手续费
        $order_status = intval($response_data['status']);//订单状态
        $order_user_id = intval($response_data['user_id']);//订单用户ID
        $order_bank_id = intval($response_data['bank_id']);//订单银行ID
        $order_card_no = $response_data['card_no'];//打款银行卡号
        $loan_person = LoanPerson::findOne($order_user_id);
        if (empty($loan_person)) {
            throw new Exception("打款对账返回用户ID不合法！");
        }
        if (in_array($withdraw->type, FinancialLoanRecord::$kd_platform_type)) {
            $user_verify = UserVerification::findOne(['user_id' => $order_user_id]);
            if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                throw new Exception("实名认证表中无此用户！");
            }
            if ($withdraw->type != FinancialLoanRecord::TYPE_JUPEI && $order_status != UserLoanOrder::STATUS_PAY) {
                throw new Exception("业务订单状态不合法不为打款中状态！");
            }
        }
        if (in_array($withdraw_status, [FinancialLoanRecord::UMP_PAY_SUCCESS, FinancialLoanRecord::UMP_CMB_PAYING])) {
            throw new Exception("打款订单状态不合法！".$withdraw_status);
        }
        if (($withdraw_money != $order_money) || ($withdraw_counter_fee != $order_counter_fee) || ($withdraw_user_id != $order_user_id) || ($withdraw_bank_id != $order_bank_id) || ($withdraw_card_no != $order_card_no) ) {
            throw new Exception("抱歉，打款数据与业务订单数据不一致，请联系管理员！");
        }
        return true;
    }

    /**
     * 扣款根据对应的业务类型去对账
     * @param $withdraw
     */
    public function withdrawCheckDebitOrder($data){
        $debit_repayment_money = $data['plan_repayment_money'];//预期打款金额
        $debit_plan_repayment_time = $data['plan_repayment_time'];//预期打款时间
        $debit_status = $data['status'];//打款状态
        $debit_user_id = $data['user_id'];//预期打款用户ID
        $debit_debit_card_id = $data['debit_card_id'];//打款银行卡ID
        $card = CardInfo::findOne($debit_debit_card_id);
        $debit_repayment_id = $data['repayment_id'];//打款总还款表ID
        $debit_repayment_peroid_id = $data['repayment_peroid_id'];//打款分期还款表ID
        $debit_loan_record_id = $data['loan_record_id'];//打款业务订单表ID
        $service = Yii::$container->get('financialCommonService');
        $response = $service->checkDebitOrder($debit_loan_record_id, $debit_repayment_id, $debit_repayment_peroid_id, $data['type']);
        if ($response['code'] == 0) {
            $response_data = $response['data'];
        } else {
            throw new Exception("扣款对账失败：".$response['message']);
        }
        if (empty($response_data)) {
            throw new Exception("扣款对账返回数据为空");
        }
        if (!isset($response_data['plan_repayment_money']) || !isset($response_data['debit_card_id']) || !isset($response_data['user_id']) || !isset($response_data['repayment_id'])) {
            throw new Exception("扣款返回返回数据为空");
        }
        $order_money = intval($response_data['plan_repayment_money']);//业务订单金额
        $order_plan_repayment_time = intval($response_data['plan_repayment_time']);//业务预期还款时间
        $order_debit_card_id = $response_data['debit_card_id'];//业务扣款银行卡ID
        $order_user_id = intval($response_data['user_id']);//业务扣款订单用户ID
        $order_repayment_id = intval($response_data['repayment_id']);//业务总还款表ID
        $order_repayment_peroid_id = intval($response_data['repayment_peroid_id']);//业务分期还款表ID
        $order_status = $response_data['status'];//业务扣款状态
        if ($debit_status != FinancialDebitRecord::STATUS_PAYING) {
            throw new Exception("扣款状态不合法, 不在待扣款中！");
        }
        if (($debit_repayment_money != $order_money) || ($debit_user_id != $order_user_id) || ($card->card_no != $order_debit_card_id) ||
            ($debit_repayment_id != $order_repayment_id) || ($debit_repayment_peroid_id != $order_repayment_peroid_id) || ($debit_plan_repayment_time != $order_plan_repayment_time) ) {
            Yii::error(["debit_repayment_money:{$debit_repayment_money},order_money:{$order_money}",
            "debit_user_id:{$debit_user_id},order_user_id:{$order_user_id}",
            "card->card_no:{$card->card_no},order_debit_card_id:{$order_debit_card_id}",
            "debit_repayment_id:{$debit_repayment_id},order_repayment_id:{$order_repayment_id}",
            "debit_repayment_peroid_id:{$debit_repayment_peroid_id},order_repayment_peroid_id:{$order_repayment_peroid_id}",
            "debit_plan_repayment_time:{$debit_plan_repayment_time},order_plan_repayment_time:{$order_plan_repayment_time}"], LogChannel::FINANCIAL_DEBIT);
            throw new Exception("抱歉，扣款数据与业务订单数据不一致，请联系管理员！");
        }
        return true;
    }

    /**
     * 生成订单号
     */
    public static function generateOrderId($platform, $uid)
    {
        $uniqid = "_" . StringHelper::generateUniqid();
        if(strlen($uid) == 9){
            $uid = substr($uid,2,7);
        }elseif(strlen($uid) > 9){
            $uid = substr($uid,0,7);
        }

        $order_id = "";
        switch ($platform) {
            case FinancialLoanRecord::PAYMENT_TYPE_CMB:
                $order_id = date('Ymd') . $uid . "{$uniqid}";
                break;
            case FinancialLoanRecord::PAYMENT_TYPE_MANUAL:
                $order_id = date('Ymd') . $uid . "{$uniqid}";
                break;
            default:
                throw new Exception("不支持的第三方打款类型");
        }
        if( strlen($order_id) > 30 ){
            $order_id = substr($order_id,0,30);
        }
        return $order_id;
    }

    /**
     * 联动扣款回调
     * @param null $params
     * @return bool
     */
    public function liandongCharge($params = null) {
        if(empty($params)) {
            return false;
        }
        FinancialLog::addLogDetail("联动开始回调数据解析结果", $params);
        $pay_order = FinancialDebitRecord::findOne(['order_id' => $params['order_id']]);
        try {
            if(empty($pay_order) || empty($pay_order['repayment_time'])) {
                throw new Exception("订单不存在或还款操作时间为空");
            }
            $user = LoanPerson::findOne($pay_order->user_id);
            FinancialLog::setUser($user);
            if ($pay_order['status'] != FinancialDebitRecord::STATUS_PAYING) {
                throw new Exception("该订单已经处理过");
            }
            if ($pay_order['status'] == FinancialDebitRecord::STATUS_SUCCESS) {
                throw new Exception("状态不合法扣款异常");
            }
            if ($params['trade_state'] === 0) {//状态成功
                FinancialLog::addLogDetail("联动扣款成功");
                if ($params['amount'] != $pay_order['plan_repayment_money']) {
                    //throw new Exception("回调结果：扣款金额与订单金额不一致！");
                }
                $pay_order['true_repayment_money'] = $params['amount'];
                $order_service = new FinancialCommonService();
                $username = (Yii::$app instanceof \yii\web\Application) ? Yii::$app->user->identity->username : 'auto shell';
                $order_result = $order_service->successDebitOrder($pay_order, '扣款成功,后台操作回调', $username,['debit_account'=>PayService::MER_ID]);

                if ($order_result['code'] == 0) {
                    $callback_result = [
                        'code' => 0,
                        'message' => '通知成功'
                    ];
                } else {
                    $callback_result = [
                        'code' => $order_result['code'],
                        'message' => "通知失败：".$order_result['message'],
                    ];
                }
                FinancialLog::addLogDetail("联动扣款成功回调业务方结果", $callback_result);
                //更新扣款结果
                $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                    "status" => FinancialDebitRecord::STATUS_SUCCESS,
                    "pay_result" => json_encode($params),
                    'true_repayment_money' => $params['amount'],
                    "third_platform_order_id" => $params['trade_no'],//易宝交易流水号
                    "true_repayment_time" => time(),
                    "callback_result" => json_encode($callback_result),
                    "updated_at" => time(),
                ],[
                    "user_id" => $pay_order['user_id'],
                    "order_id" => $params['order_id'],
                    "status" => FinancialDebitRecord::STATUS_PAYING,
                ])->execute();
                if(empty($affected_row)) {
                    MessageHelper::sendSMS(NOTICE_MOBILE, "联动口袋快借银行扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'], 'smsService_TianChang_HY', $user->source_id);
                }
                FinancialLog::addLogDetail("联动扣款成功更新扣款订单结果成功");
                return true;
            } else {
                $err_msg = isset($params['message']) ? $params['message'] : '未知';
                throw new Exception($err_msg);
            }
        }catch (Exception $e) {
            $params['err_msg'] = $e->getMessage();
            $params['err_code'] = $e->getCode();
        }
        FinancialLog::addLogDetail("联动扣款异常结果", [
            'err_msg' => isset($params['err_msg']) ? $params['err_msg'] : '未知错误',
            'err_code' => isset($params['err_code']) ? $params['err_code'] : 0 ]);
        //记录回调错误信息
        if (isset($params['order_id']) && !empty($params['order_id']) && !empty($pay_order)) {
            $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                "status" => FinancialDebitRecord::STATUS_FALSE,
                "pay_result" => json_encode($params),
                "true_repayment_time" => time(),
                "updated_at" => time(),
            ],[
                "order_id" => $params['order_id'],
            ])->execute();
            if(empty($affected_row)) {
                return false;
            }
            FinancialLog::addLogDetail("联动扣款异常更新扣款订单状态为失败");
            return true;
        }
    }

    /**
     * Pay Center 请求处理
     */
    public function payCenterCharge($params = null,$id=0)
    {
        if (!isset($params['platform'])) {
            return false;
        }

        if (intval($params['trade_state']) === 1)  $params['trade_state'] = 0;   //兼容原先0 为成功
        if (intval($params['trade_state']) === 9) { //等待回调
			if($id){
				\common\api\RedisQueue::set(["expire" => 7200, "key" => 'debit_lock_'.$id, "value" => 1]);
            }
			return false;
            //throw new Exception("等待回调");
        }
        if ($params['client_type'] == 1 && (intval($params['trade_state']) === 1 || intval($params['trade_state']) === 2)) {
            $this->CallLoadDebitResult($params);
        }

        switch ($params['platform']) {
            case BankConfig::PLATFORM_BFPAY:
                return $this->payCommCharge($params);
                break;
            case BankConfig::PLATFORM_KUAIJIETONG:
                return $this->payCommCharge($params);
                break;
            default:
                break;
        }
		return false;
    }

    /**
     * 公共扣款回调
     * @param null $params
     * @return bool
     */
    public function payCommCharge($params=null) {
        if(empty($params)) {
            return false;
        }
        $platform_name = BankConfig::$platform[$params['platform']];
        FinancialLog::addLogDetail($platform_name."开始回调数据解析结果", $params);
        $pay_order = FinancialDebitRecord::findOne(['order_id' => $params['order_id']]);
        try {
            if(empty($pay_order) || empty($pay_order['repayment_time'])) {
                throw new Exception("订单不存在或还款操作时间为空");
            }
			\common\api\RedisQueue::del(["key" => 'debit_lock_'.$pay_order->id]);
            $user = LoanPerson::findOne($pay_order->user_id);
            FinancialLog::setUser($user);
            /*if ($pay_order['status'] != FinancialDebitRecord::STATUS_PAYING) {
                throw new Exception("该订单已经处理过");
            }*/
            if ($pay_order['status'] == FinancialDebitRecord::STATUS_SUCCESS) {
                throw new Exception("状态不合法扣款异常");
            }
            if ($params['trade_state'] === 0) {//状态成功
                FinancialLog::addLogDetail($platform_name."扣款成功");

                $pay_order['true_repayment_money'] = $params['amount'];
                $pay_order['platform'] = $params['platform'];
                $order_service = new FinancialCommonService();
                $username = (Yii::$app instanceof \yii\web\Application && Yii::$app->user->identity) ? Yii::$app->user->identity->username : 'auto shell';
                $order_result = $order_service->successDebitOrder($pay_order, '扣款成功,后台操作回调', $username,['debit_account'=>$params['merchant_id']]);
                $order_result['code'] = 0;
                if ($order_result['code'] == 0) {
                    $callback_result = [
                        'code' => 0,
                        'message' => '通知成功'
                    ];
                } else {
                    $callback_result = [
                        'code' => $order_result['code'],
                        'message' => "通知失败：".$order_result['message'],
                    ];
                }
                FinancialLog::addLogDetail($platform_name."扣款成功回调业务方结果", $callback_result);
                //更新扣款结果
                $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                    "status" => FinancialDebitRecord::STATUS_SUCCESS,
                    "pay_result" => json_encode($params),
                    'true_repayment_money' => $params['amount'],
                    "third_platform_order_id" => $params['trade_no'],//易宝交易流水号
                    "true_repayment_time" => time(),
                    "callback_result" => json_encode($callback_result),
                    "updated_at" => time(),
                    "platform" => $params['platform'],
                ],"user_id=".$pay_order['user_id'].' and id='.$pay_order['id'].' and status <>'.FinancialDebitRecord::STATUS_SUCCESS)->execute();
                if(empty($affected_row)) {
                    MessageHelper::sendSMS(NOTICE_MOBILE, $platform_name."银行扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'], 'smsService_TianChang_HY', $user->source_id);
                }
                FinancialLog::addLogDetail($platform_name."扣款成功更新扣款订单结果成功");
                return true;
            } else {
                $err_msg = isset($params['message']) ? $params['message'] : '未知';
                throw new Exception($err_msg);
            }
        }catch (Exception $e) {
            $params['err_msg'] = $e->getMessage();
            $params['err_code'] = $e->getCode();
        }

        FinancialLog::addLogDetail($platform_name."扣款异常结果", [
            'err_msg' => isset($params['err_msg']) ? $params['err_msg'] : '未知错误',
            'err_code' => isset($params['err_code']) ? $params['err_code'] : 0 ]);
        //记录回调错误信息
        if (isset($params['order_id']) && !empty($params['order_id']) && !empty($pay_order)) {
            $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                "status" => FinancialDebitRecord::STATUS_FALSE,
                "pay_result" => json_encode($params),
                "true_repayment_time" => time(),
                "updated_at" => time(),
            ],[
                "order_id" => $params['order_id'],
                "id" => $pay_order['id'],
            ])->execute();
            if(empty($affected_row)) {
                return false;
            }
            FinancialLog::addLogDetail($platform_name."扣款异常更新扣款订单状态为失败");
            return true;
        }
    }


    /**
     * 易宝扣款回调
     * @param null $params
     * @return bool
     */
    public function charge($params = null,$account=null) {
        if(empty($params)) {
            return false;
        }
        $yeepay_service = new YeePayService(null, $account ? $account : YeePayService::AccountTypeCP);
        $params = $yeepay_service->yeepay->parseReturn($params['data'], $params['encryptkey']);
        FinancialLog::addLogDetail("易宝开始回调数据解析结果", $params);
        $params['order_id'] = $params['orderid'];//口袋订单号
        $pay_order = FinancialDebitRecord::findOne(['order_id' => $params['order_id']]);
        try {
            if(empty($pay_order) || empty($pay_order['repayment_time'])) {
                return $this->chargeException($params,$yeepay_service,$pay_order);
                throw new Exception("订单不存在或还款操作时间为空");
            }
            $user = LoanPerson::findOne($pay_order->user_id);
            FinancialLog::setUser($user);
            if ($pay_order['status'] != FinancialDebitRecord::STATUS_PAYING) {
                return $this->chargeException($params,$yeepay_service,$pay_order);
                throw new Exception("该订单已经处理过");
            }
            if ($pay_order['status'] == FinancialDebitRecord::STATUS_SUCCESS && $params['status'] == YeePayService::ChargeStatusFailed ) {//易宝又返回失败
                throw new Exception("状态不合法扣款异常");
            }
            if ($params['status'] == YeePayService::ChargeStatusSuccess) {//状态成功
                FinancialLog::addLogDetail("易宝扣款成功");
                if (!in_array($params['merchantaccount'], YeePayService::$accounts)) {
                    //throw new Exception("回调结果：商编不一致！");
                }
                if ($params['amount'] != $pay_order['plan_repayment_money']) {
                    //throw new Exception("回调结果：扣款金额与订单金额不一致！");
                }
                return $this->chargeSuccess($params,$yeepay_service,$pay_order);
            } else {
               $err_msg = isset($params['errormsg']) ? $params['errormsg'] : '未知';
               throw new Exception($err_msg);
            }
        }catch (Exception $e) {
            $params['err_msg'] = $e->getMessage();
            $params['err_code'] = $e->getCode();
        }
        FinancialLog::addLogDetail("易宝扣款异常结果", [
            'err_msg' => isset($params['err_msg']) ? $params['err_msg'] : '未知错误',
            'err_code' => isset($params['err_code']) ? $params['err_code'] : 0 ]);
        //记录回调错误信息
        if (isset($params['orderid']) && !empty($params['orderid']) && !empty($pay_order)) {
            $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                "status" => FinancialDebitRecord::STATUS_FALSE,
                "pay_result" => json_encode($params),
                "true_repayment_time" => time(),
                "updated_at" => time(),
            ],"user_id=".$pay_order['user_id'].' and id='.$pay_order['id'].' and status <>'.FinancialDebitRecord::STATUS_SUCCESS)->execute();
            if(empty($affected_row)) {
                return false;
            }
            FinancialLog::addLogDetail("易宝扣款异常更新扣款订单状态为失败");
            return true;
        }
    }
    private function chargeSuccess($params,$yeepay_service, $pay_order){
        $pay_order['true_repayment_money'] = $params['amount'];
        $pay_order['order_id'] = $params['order_id'];
		if($yeepay_service instanceof YeePayService){
			$pay_order['platform'] = BankConfig::PLATFORM_YEEPAY;
		}
        $order_service = new FinancialCommonService();
        $order_result = $order_service->successDebitOrder($pay_order, '扣款成功,易宝自动回调', $pay_order->admin_username,['debit_account'=>$yeepay_service->account]);
        $order_result['code'] = 0;
        if ($order_result['code'] == 0) {
            $callback_result = [
                    'code' => 0,
                    'message' => '通知成功'
            ];
        } else {
            $callback_result = [
                    'code' => $order_result['code'],
                    'message' => "通知失败：".$order_result['message'],
            ];
            @MessageHelper::sendSMS(NOTICE_MOBILE, "易宝扣款成功,回调失败order_id:" . $params['order_id']);
        }
        //更新扣款结果
        $affected_row = Yii::$app->db_kdkj->createCommand()->update(FinancialDebitRecord::tableName(),[
                "status" => FinancialDebitRecord::STATUS_SUCCESS,
                "pay_result" => json_encode($params),
                'true_repayment_money' => $params['amount'],
                "third_platform_order_id" => $params['yborderid'],//易宝交易流水号
                "true_repayment_time" => time(),
                "callback_result" => json_encode($callback_result),
                "updated_at" => time(),
                "order_id" => $params['order_id'],
                "platform" => $pay_order['platform'],
        ],"user_id=".$pay_order['user_id'].' and id='.$pay_order['id'].' and status <>'.FinancialDebitRecord::STATUS_SUCCESS)->execute();
        if(empty($affected_row)) {
            MessageHelper::sendSMS(NOTICE_MOBILE, "口袋快借银行扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id']);
        }
        \common\api\RedisQueue::del(["key" => $params['order_id']]);
        return true;
    }
    public function chargeException($params,$yeepay_service,$pay_order=null){
        if(!$pay_order){
            $fid = \common\api\RedisQueue::get(["key" => $params['order_id']]);
            if(!$fid){
                return false;
            }
            $pay_order = FinancialDebitRecord::findOne(['id' => $fid]);
        }
        if(!$pay_order){
            return false;
        }
        if ($params['status'] == YeePayService::ChargeStatusSuccess) {//成功
            if($params['order_id'] == $pay_order['order_id'] && $pay_order['status'] == FinancialDebitRecord::STATUS_SUCCESS){//已处理
                return true;
            }
            return $this->chargeSuccess($params, $yeepay_service, $pay_order);
        }
        return false;
    }

    /**
     * 手动将扣款记录变成已扣款
     * @param unknown $user_id
     * @param unknown $loan_record_id
     * @param unknown $repayment_id
     * @param unknown $type
     */
    public function rejectDebitRecord($user_id,$loan_record_id,$repayment_id,$type){
        $where = [
                'status' => [FinancialDebitRecord::STATUS_PAYING,FinancialDebitRecord::STATUS_FALSE],
                'user_id' => $user_id,
                'loan_record_id' => $loan_record_id,
                'repayment_id' => $repayment_id,
                'type' => $type,
        ];
        $FinancialDebitRecord = FinancialDebitRecord::find()->where($where)->andWhere(['>=','created_at',strtotime(date('Y-m-d',time()))])->orderBy('id desc')->one();
        if(empty($FinancialDebitRecord)){
            return false;
        }
        $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_REFUSE;
        $FinancialDebitRecord->remark = '订单手动置为已扣款';
        $FinancialDebitRecord->callback_result = json_encode(['code' => 0,'message'  =>'订单手动置为已扣款',]);
        if($FinancialDebitRecord->save()){
            return true;
        }else{
            return false;
        }

        // FinancialDebitRecord::updateAll([
        //         'status'=>FinancialDebitRecord::STATUS_REFUSE,
        //         'remark' => '订单手动置为已扣款',
        //         'callback_result' => json_encode(['code' => 0,'message'  =>'订单手动置为已扣款',])
        // ],$where);
        // return true;
    }


    /**
     * 扣款
     * @throws \yii\base\Exception
     */
    public function doDebitRecord($id, $params=[]) {
		//构造用户打款数据
        $bank_id = @$params["bank_id"];//扣款银行ID

        $amount  = @$params["amount"];//扣款金额
        //邮政储蓄银行单笔代扣上限为5000元
        if($bank_id == 4){
            $amount = min(5000, $amount);
        }

        $id_card = @$params["id_card"];//扣款身份证号
        $card_no = @$params["card_no"];//扣款银行卡号
        $stay_phone = @$params["stay_phone"];//银行预留手机号

        $data = FinancialDebitRecord::findOne(['id' => $id]);
        $UserCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$data['user_id'],'order_id'=>$data['loan_record_id']])->orderBy('id desc')->one();
        if($UserCreditMoneyLog && ($UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_ING || $UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_NORMAL)){
            \Yii::error("代扣请求错误提示：用户主动还款进行中，扣款表ID：{$data['id']}，还款表ID：{$data['repayment_id']}",LogChannel::FINANCIAL_DEBIT);
            return false;
        }
        // 对账准备
        $userLoanOrderRepayment = UserLoanOrderRepayment::findOne($data['repayment_id']);
        if($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
            $data->status = FinancialDebitRecord::STATUS_REFUSE;
            $data->updated_at = time();
            $data->save();
            return false;
        }
        $userLoanOrderRepayment->status = UserLoanOrderRepayment::STATUS_WAIT;
        if(!$userLoanOrderRepayment->save()){
            \Yii::error('YgdRejectautodebitinfo 还款表状态为扣款中，请勿重复扣款'.$data['repayment_id'],LogChannel::FINANCIAL_DEBIT);
            return false;
        }
        if(!in_array($bank_id, CardInfo::$debitbankInfo)){
            $data->remark = '银行卡不支持代扣';
            $data->status = FinancialDebitRecord::STATUS_REFUSE;
            $data->updated_at = time();
            $data->save();
            // 修改两表状态
            $alterStatus = UserLoanOrderRepayment::alterOrderStatus($data['loan_record_id'],$data['repayment_id']);
            if($alterStatus){
//                $person = LoanPerson::findOne($data['user_id']);
//                @MessageHelper::sendSMSCS($person->phone , '代扣还款失败，主卡不支持代扣，请进入APP进行支付宝还款','smsService_ChuangLan',$person->source_id);
            }
            \Yii::error('YgdRejectautodebitinfo 银行卡不支持代扣',LogChannel::FINANCIAL_DEBIT);
            return false;
        }
        try {
            if (empty($data)) {
                throw new Exception("不存在的待扣款订单");
            }

            $loan_person = LoanPerson::findOne($data['user_id']);
            if (empty($loan_person)) {
                throw new Exception("不存在的用户！");
            }
            if (\in_array($data['type'], FinancialDebitRecord::$kd_platform_type)) {
                $user_verify = UserVerification::findOne(['user_id' => $data['user_id']]);
                if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                    \yii::error( \sprintf('%s user_not_verify, %s cannot_dq.', $data['user_id'], $id) );
                    throw new Exception("抱歉，该用户没有进行实名认证。");
                }
            }

            //对账操作
            $this->withdrawCheckDebitOrder($data);
        }
        catch(\Exception $e) {
            throw new Exception($e->getMessage());
        }

        $operate_username = isset($params["username"]) ? $params["username"] : 'auto shell';
        $amount  = intval(bcmul($amount, '100', 0));
        if (empty($amount) || empty($id_card) || empty($bank_id) || empty($card_no) || empty($stay_phone)) {
            \yii::error( \sprintf('%s require_param_missing.', $id) );
            throw new Exception('扣款必要的参数不能为空！');
        }
        if ($amount > $data['plan_repayment_money']) {
            \yii::error( \sprintf('%s amount_error.', $id) );
            throw new Exception('扣款金额必须小于申请金额！');
        }

        if ((Yii::$app instanceof \yii\web\Application) && $amount < 1000 && $amount != $data['plan_repayment_money']) {
            if(($data['plan_repayment_late_fee']+$data['plan_repayment_interest']) >= $data['plan_repayment_money'] && $amount < 100){
                throw new Exception('扣款金额太小，无法完成扣款；请和用户确定还款金额，如已确定请联系管理员完成扣款！');
            }
            else if($amount < 100) {
                throw new Exception('扣款金额太小，无法完成扣款；请和用户确定还款金额，如已确定请联系管理员完成扣款！');
            }
        }

        $FinancialDebitRecord = FinancialDebitRecord::findOne($data['id']);
        $FinancialDebitRecord->order_id = FinancialDebitRecord::generateOrderDebit();//扣款订单号
        $FinancialDebitRecord->apply_debit_money = $amount;
        $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_RECALL;//扣款订单号
        $FinancialDebitRecord->save();

        //记录代扣日志
        $auto_debit_log = new AutoDebitLog();
        $auto_debit_log->user_id = $FinancialDebitRecord->user_id;
        $auto_debit_log->order_id = $FinancialDebitRecord->loan_record_id;
        $auto_debit_log->order_uuid = $FinancialDebitRecord->order_id;
        $auto_debit_log->card_id = $FinancialDebitRecord->debit_card_id;
        $auto_debit_log->money = $amount;
        $auto_debit_log->status = AutoDebitLog::STATUS_DEFAULT;
        if(isset($params['come_from']) && $params['come_from'] == 'collection_debit'){
            $auto_debit_log->debit_type = AutoDebitLog::DEBIT_TYPE_COLLECTION;
        }else{
            $auto_debit_log->debit_type = AutoDebitLog::DEBIT_TYPE_BACKEND;
        }
        $auto_debit_log->save();

        $user_loan_order = UserLoanOrder::findOne([$userLoanOrderRepayment->order_id]);
        $project_name = self::KD_PROJECT_NAME;

        $url = 'http://test.abc.com';
        $params = [
            'project_name' => $project_name,
            'order_id' => $FinancialDebitRecord->order_id,
            'stay_phone' => $stay_phone,
            'real_name' => $loan_person->name,
            'id_card' => $id_card,
            'bank_id' =>  $bank_id,
            'card_no' => $card_no,
            'money' => $amount,
            'user_ip' => '106.14.28.157',
            'pay_summary' => 'wzd_debit card_id:'.$id_card,
            'user_id' =>$data['user_id']
        ];
        $sign = \common\models\Order::getPaySign($params,$params['project_name']);
        $params['sign'] = $sign;
        if(YII_ENV_PROD){
            $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $params,10);
        }else{
            $ret['code'] = 0;
            $ret['third_platform'] = 12;
            $ret['pay_order_id'] = 'jk'.$FinancialDebitRecord->order_id;
        }
        if ($ret && isset($ret['code']) && $ret['code'] == 0) {
            $FinancialDebitRecord->repayment_time = time();//更新操作扣款时间
            $FinancialDebitRecord->admin_username = $operate_username;//更新操作管理员名称
            $FinancialDebitRecord->platform = isset($ret['third_platform']) ? $ret['third_platform'] : '';
            $FinancialDebitRecord->third_platform_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';

            $auto_debit_log->platform = isset($ret['third_platform']) ? $ret['third_platform'] : $auto_debit_log->platform;
            $auto_debit_log->pay_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : $auto_debit_log->pay_order_id;
            $auto_debit_log->remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
            $auto_debit_log->status = AutoDebitLog::STATUS_WAIT;
            $auto_debit_log->save();

            if ($FinancialDebitRecord->save()) {
                \Yii::info(sprintf("UserId[{$FinancialDebitRecord->user_id}] \n %s \n %s", print_r($params, true), print_r($ret, true)), __METHOD__);
                return true;
            }
        } elseif($ret && isset($ret['code']) && $ret['code'] == -1){
            $msg = "代扣请求被拒绝，扣款订单号：{$FinancialDebitRecord['id']}，错误码：". print_r($ret,true);
            Yii::error($msg,LogChannel::FINANCIAL_DEBIT);
            $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_FALSE;//扣款订单号
            $FinancialDebitRecord->repayment_time = time();//更新操作扣款时间
            $FinancialDebitRecord->admin_username = $operate_username;//更新操作管理员名称
            $FinancialDebitRecord->remark_two = $FinancialDebitRecord->remark_two . "**{$ret['msg']}";

            $auto_debit_log->remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
            $auto_debit_log->status = AutoDebitLog::STATUS_REJECT;
            $auto_debit_log->save();
            if($FinancialDebitRecord->save()){
                return true;
            }
        }else{
            $msg = "代扣请求失败，扣款订单号：{$FinancialDebitRecord['id']}，错误码：". (isset($ret['code']) ? print_r($ret,true) : '无响应内容');
            Yii::error('1010 FinancialService $ret='.print_r($ret,true),LogChannel::FINANCIAL_DEBIT);
            MessageHelper::sendSMS(NOTICE_MOBILE,$msg);
            $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_RECALL;//扣款订单号
            $FinancialDebitRecord->repayment_time = time();//更新操作扣款时间
            $FinancialDebitRecord->admin_username = $operate_username;//更新操作管理员名称
            $FinancialDebitRecord->platform = isset($ret['third_platform'])?$ret['third_platform']:'';
            $FinancialDebitRecord->third_platform_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';

            $auto_debit_log->status = AutoDebitLog::STATUS_REJECT;
            $auto_debit_log->save();
            if($FinancialDebitRecord->save()){
                return true;
            }

            return false;
        }

    }


    /**
     * 批量代扣--预备
     * @parmas 扣款记录
     */
    public function preCheckOrder($record) {
        //先判断银行卡是否支持
        $card = CardInfo::findOne(['id'=>$record['debit_card_id']]);
        if(!$card){
            return [
                'code'=>'-2',
                'msg'=>'银行卡不存在',
            ];
        }
        if(!in_array($card->bank_id,CardInfo::$debitbankInfo)){
            // 修改两表状态
            $alterStatus = UserLoanOrderRepayment::alterOrderStatus($record['loan_record_id'],$record['repayment_id']);
//            if($alterStatus){
//                $person = LoanPerson::findOne($record['user_id']);
//                MessageHelper::sendSMS($person->phone , '代扣还款失败，主卡不支持代扣，请进入APP进行支付宝还款');
//            }
            FinancialDebitRecord::clearDebitLock($record['loan_record_id']);
            return [
                'code'=>'-1',
                'msg'=>'银行卡不支持代扣',
            ];
        }
        //避免重复扣款
        $UserCreditMoneyLog = UserCreditMoneyLog::find()
            ->where(['order_id'=>$record['loan_record_id'],'user_id'=>$record['user_id']])
            ->orderBy('id desc')->one();
        if( $UserCreditMoneyLog && in_array($UserCreditMoneyLog->status,[UserCreditMoneyLog::STATUS_ING,UserCreditMoneyLog::STATUS_NORMAL]) )
        {
            return [ 'code'=>'-2', 'msg'=>'订单正在进行中'];
        }
        //避免重复扣款[zhangyuliang]
        $autoDebitLog = AutoDebitLog::find()->where(['order_id'=>$record['loan_record_id'],'user_id'=>$record['user_id']])->orderBy('id desc')->one();
        if($autoDebitLog && in_array($autoDebitLog->status,[AutoDebitLog::STATUS_DEFAULT,AutoDebitLog::STATUS_WAIT]) )
        {
            return [ 'code'=>'-2', 'msg'=>'订单正在进行中'];
        }
        // 对账准备
        $userLoanOrderRepayment = UserLoanOrderRepayment::findOne($record['repayment_id']);
        if($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
            return [
                'code'=>'-1',
                'msg'=>'订单已还款'
            ];
        }
        $userLoanOrderRepayment->status = UserLoanOrderRepayment::STATUS_WAIT;
        if(!$userLoanOrderRepayment->save()){
            return [
                'code'=>'-2',
                'msg'=>'更新还款订单状态失败'
            ];
        }

        try {
            $loan_person = LoanPerson::findOne($record['user_id']);
            if (empty($loan_person)) {
                throw new Exception("不存在的用户！");
            }
            if (in_array($record['type'], FinancialDebitRecord::$kd_platform_type)) {
                $user_verify = UserVerification::findOne(['user_id' => $record['user_id']]);
                if (empty($user_verify) || empty($user_verify->real_verify_status)) {
                    throw new Exception("抱歉，该用户没有进行实名认证。");
                }
            }
            //对账操作
            $this->withdrawCheckDebitOrder($record);
            if($record['plan_repayment_money'] < 100 ){
                throw new Exception('扣款金额太小，无法完成扣款；请和用户确定还款金额，如已确定请联系管理员完成扣款！');
            }
            return [
                'code'=>'0',
                'card_info'=>$card,
                'user_info'=>$loan_person,
            ];
        } catch(\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     *  批量代扣--发起
     */
    public function batchDebitRecord($params){
        if(YII_ENV_PROD){
            if($params['project_name'] == FinancialService::SUBA_PROJECT_NAME){
                $url = 'http://test.abc.com';
            }else{
                $url = 'http://test.abc.com';
            }

        }else{
            $url = 'http://test.abc.com';
        }
        $ret = \common\helpers\CurlHelper::FinancialCurl($url, 'POST', $params,120);
        $success_ids = '';
        $fail_ids = '';
        $success_msg = '';
        $fail_msg = '';
        print_r($ret);
        if($ret && isset($ret['code']) && $ret['code'] == 0){
            if(isset($ret['data']['success'])){
                foreach ($ret['data']['success'] as $item){
                    $id = explode('_',$item['order_id']);
                    $record = FinancialDebitRecord::findOne($id[1]);
                    $record->repayment_time = time();
                    $record->third_platform_order_id = $item['pay_order_id'];
                    $record->updated_at = time();
                    if($record->save()){
                        $success_ids .= ','.$record->id;
                        $auto_debit_log = AutoDebitLog::find()
                            ->where([
                                'order_uuid'=> $item['order_id'],
                                'user_id'=> $record->user_id
                            ])->one();
                        if($auto_debit_log){
                            $auto_debit_log->pay_order_id = $item['pay_order_id'];
                            $auto_debit_log->remark = json_encode($item,JSON_UNESCAPED_UNICODE);
                            $auto_debit_log->save();
                        }
                    }
                }
                $success_msg = '成功订单'.$success_ids;
            }
            if(isset($ret['data']['fail'])){
                foreach ($ret['data']['fail'] as $item){
                    $id = explode('_',$item['order_id']);
                    $record = FinancialDebitRecord::findOne($id[1]);
                    $record->status = FinancialDebitRecord::STATUS_FALSE;
                    $record->updated_at = time();
                    if($record->save()){
                        $auto_debit_log = AutoDebitLog::find()
                            ->where([
                                'order_uuid'=> $item['order_id'],
                                'user_id'=> $record->user_id
                            ])->one();
                        if($auto_debit_log){
                            $auto_debit_log->status = AutoDebitLog::STATUS_REJECT;
                            $auto_debit_log->remark = json_encode($item,JSON_UNESCAPED_UNICODE);
                            $auto_debit_log->save();
                        }
                        $fail_ids .= ','.$record->id;
                    }
                }
            }
            $fail_msg = '失败订单'.$fail_ids;
        }else{
            //先人工处理  然后观察
            if(YII_ENV==='prod'){
                $response = \common\helpers\CurlHelper::$http_info;
                Yii::error('错误信息:'.print_r($response,1),'financial_debit_batch');
                MessageHelper::sendSMS(NOTICE_MOBILE,'[批量处理] 异常批次号'.$params['batch_no'].' 返回值'.print_r($ret,true));
            }
        }
        $msg = $success_msg.'--'.$fail_msg;
        return [
            'code'=>'0',
            'msg'=>$msg,
        ];
    }

    private function CallPayCenter($id, &$data, $amount, $bank_name, $platform, &$user_data, $operate_username)
    {
        $order_id = Order::generateOrderId($platform, $user_data['id']);//扣款订单号
        $data->order_id = $order_id;//扣款订单号
        $data->repayment_time = time();//更新操作扣款时间
        $data->admin_username = $operate_username;//更新操作管理员名称
        $data->platform = $platform;
        $data->save();
        \common\models\DeductMoneyLog::saveRecord($data,$amount);//新增扣款日志
        $user_data['user_id'] = $user_data['id'];
        $user_data['realname'] = $user_data['real_name'];
        $user_data['idcard'] = $user_data['id_card'];
        $user_data['order_id'] = $order_id;
        $user_data["platform"] = $platform;
        $user_data['bank_name'] = $bank_name;
        $user_data['client_type'] = 0;
        return \common\soa\PaySoa::instance('Debit')->directPay($user_data);
    }

    private function CallLoadDebitResult($params)
    {
        $params = [
            'order_id'  => $params['order_id'],
            'amount'    => $params['amount'],
            'status'    => 1,
            'result'    => '充值成功',
            'third_order_id'=> $params['trade_no']
        ];
        $loanService = Yii::$container->get('loanService');
        $res = $loanService->debitResult($params, 1);
        if (!$res) {
            FinancialLog::addLogDetail($params['order_id']."主动还款失败");
        }
        FinancialLog::addLogDetail($params['order_id']."主动还款失败");
    }
}
