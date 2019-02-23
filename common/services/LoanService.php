<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\UserException;
use yii\base\Exception;
use common\base\LogChannel;
use common\helpers\MessageHelper;
use common\models\AutoDebitLog;
use common\models\fund\LoanFund;
use common\models\LoanRepaymentPeriod;
use common\models\Order;
use common\models\WhiteList;
use common\services\fundChannel\JshbService;
use PhpOffice\Common\Tests\AutoloaderTest;
use common\models\User;
use common\models\LoanRecordPeriod;
use common\models\Shop;
use common\models\LoanPerson;
use common\services\AccountService;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use common\helpers\CurlHelper;
use common\models\FinancialDebitRecord;
use common\api\RedisQueue;
use common\exceptions\CodeException;
use common\models\UserCredit;
use common\models\UserCreditData;
use common\models\UserCreditLog;
use common\models\UserCreditTotal;
use common\models\UserVerification;
use common\services\MessageService;
use common\helpers\Util;
use common\helpers\ToolsUtil;
use common\models\UserProofMateria;
use common\models\UserContact;
use common\models\BaseUserCreditTotalChannel;
use common\helpers\TimeHelper;
use credit\components\ApiUrl;
use common\models\CardInfo;
use common\models\Setting;
use common\models\BankConfig;

/**
 * 借款模块service
 */
class LoanService extends Component
{

    //第三方平台用户借款范围
    public static $thirdparty_loan_range = [200, 1000];

    // 获取城市店铺多级联动
    public function getProvinceCityAreaShop($loan_project_id)
    {
        $loan_project_id = intval($loan_project_id);
        $province_all = [];

        $sql = "
            select id,province_id,city_id,area_id,province,city,area,shop_name
            from tb_shop
            where status = " . Shop::SHOP_ACTIVE . " and loan_project_id = " . $loan_project_id . "
        ";
        $shop_info = Yii::$app->db_financial->createCommand($sql)->queryAll();
        if ($shop_info) {
            foreach ($shop_info as $v) {
                $province_all[$v['province_id']]['province_title'] = $v['province'];
                $province_all[$v['province_id']]['province_id'] = $v['province_id'];
                $province_all[$v['province_id']]['city'][$v['city_id']]['city_title'] = $v['city'];
                $province_all[$v['province_id']]['city'][$v['city_id']]['city_id'] = $v['city_id'];
                $province_all[$v['province_id']]['city'][$v['city_id']]['area'][$v['area_id']]['area_title'] = $v['area'];
                $province_all[$v['province_id']]['city'][$v['city_id']]['area'][$v['area_id']]['area_id'] = $v['area_id'];
                $province_all[$v['province_id']]['city'][$v['city_id']]['area'][$v['area_id']]['shop'][] = [
                    'id' => $v['id'],
                    'shop_name' => $v['shop_name']
                ];
            }
        }
        return $province_all;
    }

    // 获取用户当前未完成的借款申请记录数
    public function getApplyRecordsCount($uid, $loan_project_id)
    {
        $sql_r = "
            select count(*) count
            from tb_loan_record_period
            where user_id = {$uid} and loan_project_id = {$loan_project_id} and status not in( " . LoanRecordPeriod::STATUS_APPLY_REPAY_SUCCESS . "," . LoanRecordPeriod::STATUS_APPLY_TRIAL_FALSE . "," . LoanRecordPeriod::STATUS_APPLY_TELE_FALSE . "," . LoanRecordPeriod::STATUS_APPLY_REVIEW_FALSE . "," . LoanRecordPeriod::STATUS_APPLY_CAR_FALSE . "," . LoanRecordPeriod::STATUS_APPLY_MONEY_FALSE . " )
        ";
        $loan_records_count = Yii::$app->db_financial->createCommand($sql_r)->queryOne();
        return $loan_records_count['count'];
    }

    // 生成申请记录
    public function insertApplyRecord($uid, $loan_project_id, $type, $amount, $period, $shop_id, $product_name)
    {
        $user = User::findOne($uid);
        if (!$user) throw new UserException("系统繁忙，请重新操作");
        $sql_n = "
            insert into {{%loan_record_period}}
            (user_id,loan_project_id,loan_trial_id,loan_review_id,loan_audit_id,loan_repayment_id,type,amount,credit_amount,period,status,shop_id,trial_success_time,review_success_time,supply_trial_time,supply_review_time,product_type_name,apply_time,created_at,updated_at)
            values( '{$uid}', '{$loan_project_id}', 0, 0, 0, 0, '{$type}', '{$amount}', 0, '{$period}', " . LoanRecordPeriod::STATUS_APPLY_TRIAL_APPLY . ", '{$shop_id}', 0, 0, 0, 0, '{$product_name}', " . time() . ", " . time() . ", " . time() . " )
        ";
        $affect_row_n = Yii::$app->db_financial->createCommand($sql_n)->execute();
        if (!$affect_row_n) throw new \Exception("insert user:{$uid} loan_record_period failed.");
        $last_period_id = Yii::$app->db_financial->lastInsertID;
        $loan_person = LoanPerson::findOne(['uid' => $uid]);
        if (!$loan_person) {
            $sex = ($user->sex == 1) ? '男' : '女';
            $birthday = strtotime($user->birthday);
            $sql_m = "
                insert into {{%loan_person}}
                (uid,id_number,type,name,phone,birthday,property,created_at,updated_at)
                values( '{$uid}', '{$user->id_card}', " . LoanPerson::PERSON_TYPE_PERSON . ", '{$user->realname}', '{$user->phone}', '{$birthday}', '{$sex}', " . time() . ", " . time() . " )
            ";
            $affect_row_m = Yii::$app->db_financial->createCommand($sql_m)->execute();
            if (!$affect_row_m) throw new \Exception("insert user:{$uid} loan_person failed.");
        }

        // 返回生成记录的ID
        return $last_period_id;
    }

    /**
     * 获取每期还款时间
     * @param string $sign_time
     * @param string $period
     * @return array
     */
    public function getPeriodRepayDate($sign_time = "", $period = "")
    {
        $days = intval(date('d', $sign_time));
        $time = strtotime(date('Y-m-01', $sign_time) . '+1 month');
        $repay_time = [];
        for ($i = 1; $i <= $period; $i++) {
            if ($days >= date('t', $time)) {
                $repay_date = date('Y-m-d', strtotime(date('Y-m-d', $time) . '+1 month -1 day'));
            } else {
                $repay_date = date('Y-m-' . $days, $time);
            }
            array_push($repay_time, $repay_date);
            $time = strtotime(date('Y-m-01', $time) . '+1 month');
        }
        return $repay_time;
    }

    //获得用户待还款（仅包含分期购和信用卡还款）总额
    public static function getRepayAmount($user_id)
    {
        $loan_person = LoanPerson::find()->where(["uid" => $user_id])->one();
        $amount = 0;
        if (!empty($loan_person)) {
            $loan_repayment = LoanRepaymentPeriod::find()->where(['loan_person_id' => $loan_person['id']])->andWhere("status != " . LoanRepaymentPeriod::STATUS_REPAYED)->all();
            if (!empty($loan_repayment)) {
                foreach ($loan_repayment as $list) {
                    $loan_record_period = LoanRecordPeriod::findOne($list['loan_record_id']);
                    if (!empty($loan_record_period) && ($loan_record_period['loan_project_id'] == 18 || $loan_record_period['loan_project_id'] == 19)) {
                        if ($list['status'] != LoanRepaymentPeriod::STATUS_REPAY_PART) {
                            $amount += $list['plan_repayment_money'];
                        } else {
                            $amount += $list['plan_repayment_money'] - $list['true_repayment_money'];
                        }
                    }
                }
            }
        }
        return $amount;
    }

    //验证是否可以提现（false 不可提现）
    public static function verifyReqChance($user_id, $amount, $type = false)
    {
        $duienRepayAmount = self::getRepayAmount($user_id);//待还总额
        $trueHoldAsset = AccountService::getRealHoldAsset($user_id);//净资产
        if ($type) {
            return $trueHoldAsset - ($duienRepayAmount + $amount);
        }
        return $trueHoldAsset <= ($duienRepayAmount + $amount) ? false : true;
    }

    /**
     * 获取放款成功次数
     * @param unknown $user_id
     * @return number
     */
    public function getSuccessLoanTimes($user_id, $params = [])
    {
        if (!$user_id) {
            return 0;
        }
        $ret = UserLoanOrder::find()->from(UserLoanOrder::tableName() . ' as o')->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
            ->where(['o.user_id' => $user_id, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
            ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere($params))
            ->andWhere('r.id is not null')->select('count(*) as count')->limit(1)->asArray()->one();
        return $ret ? $ret['count'] : 0;
    }

    /**
     * 判断是否有为未还款的借款
     * @param unknown $user_id
     * @return boolean|number
     */
    public function checkHashUnRepayment($user_id, $params = [])
    {
        if (!$user_id) {
            return false;
        }
        $ret = UserLoanOrder::find()->from(UserLoanOrder::tableName() . ' as o')->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
            ->where(['o.user_id' => $user_id, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
            ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere($params))
            ->andWhere('r.id is not null and r.status <>' . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->select('count(*) as count')->limit(1)->asArray()->one();
        return $ret ? $ret['count'] : 0;
    }

    /**
     * 获取审核中的订单
     * @param unknown $user_id
     */
    public function getUnConfirLoanOrderInfos($user_id, $params = [], $card_type = 0)
    {
        $ret = [];
        if (!$user_id) {
            return $ret;
        }
        // 金卡
        if ($card_type == BaseUserCreditTotalChannel::CARD_TYPE_TWO) {
            $order = UserLoanOrder::find()->from(UserLoanOrder::tableName() . ' as o')->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
                ->where(['o.user_id' => $user_id, 'is_user_confirm' => 0, 'o.card_type' => BaseUserCreditTotalChannel::CARD_TYPE_TWO, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
                ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere($params))
                ->andWhere('r.id is null')->select('o.*')->limit(1)->orderBy('o.id desc')->asArray()->one();
        } else {
            $order = UserLoanOrder::find()->from(UserLoanOrder::tableName() . ' as o')->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
                ->where(['o.user_id' => $user_id, 'is_user_confirm' => 0, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
                ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere($params))
                ->andWhere('o.card_type <> 2')
                ->andWhere('r.id is null')->select('o.*')->limit(1)->orderBy('o.id desc')->asArray()->one();
        }


        if ($order) {
            $header_tip = '风控审核中，请耐心等待';
            $ret['lists'] = [];
            if ($order['status'] >= UserLoanOrder::STATUS_PAY && !in_array($order['status'], UserLoanOrder::$checkStatus)) {//打款中
                $header_tip = '打款中，请注意查收短信';
                $ret['lists'][] = [
                    'title' => '打款中 ' . ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : ''),
                    'body' => '已进入打款状态，请您耐心等待',
                    'tag' => 1,
                ];
                $ret['lists'][] = [
                    'title' => '审核通过 ' . ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : ''),
                    'body' => '恭喜通过风控审核',
                    'tag' => 0,
                ];
            } else if ($order['status'] < UserLoanOrder::STATUS_CHECK) {//审核失败
                $header_tip = '很遗憾您未通过审核';
                $body = Yii::$container->get('userService')->getCanNotLoanMsgTip($order['user_id']);
                $ret['lists'][] = [
                    'title' => '审核未通过',
                    'body' => $body ? $body : '很遗憾，您的信用评分不足，该次借款未能通过。',
                    'tag' => 2,
                ];
                $ret['button'] = [
                    'msg' => '朕知道了',
                    'id' => $order['id'],
                    "active_url" => ApiUrl::toH5(["flow/index.html", "tag" => 'order_id'], true),
                ];
            } else {
                $ret['lists'][] = [
                    'title' => '审核中',
                    'body' => '已进入风控审核状态，请您耐心等待',
                    'tag' => 1,
                ];
            }
            $ret['lists'][] = [
                'title' => '申请提交成功',
                'body' => '申请借款' . sprintf("%0.2f", $order['money_amount'] / 100) . '元，期限' . $order['loan_term'] . '天，手续费' . sprintf("%0.2f", $order['counter_fee'] / 100) . '元',
                'tag' => 0,
            ];
            $ret['header_tip'] = $header_tip;
        }
        return $ret;
    }

    /**
     * 确认失败的订单
     * @param unknown $id
     * @param unknown $user_id
     */
    public function confirmFailedLoan($id, $user_id)
    {
        return UserLoanOrder::updateAll(['is_user_confirm' => 1], ['id' => $id, 'user_id' => $user_id, 'is_user_confirm' => 0]);
    }

    /**
     * 代扣通道
     * @param unknown $card
     * @param unknown $user
     * @param unknown $repayment
     * @param unknown $order_uuid
     * @param unknown $params
     */
    private function _doDebit($card, $user, $repayment, $order_uuid, $params)
    {
        $platform = \common\services\RouteDebitService::getDebitChannel($card, $repayment['order_id'], 1);
        if ($platform == BankConfig::PLATFORM_YEEPAY) {
            return $this->_doYeeDebit($card, $user, $repayment, $order_uuid, $params);
        } else {
            return $this->_payCenterDebit($card, $user, $repayment, $order_uuid, $params);
        }
        return ['data' => ['trade_state' => 2, 'message' => '无合适的扣款通道']];
    }

    /**
     * 易宝代扣通道
     * @param unknown $card
     * @param unknown $user
     * @param unknown $repayment
     * @param unknown $order_uuid
     * @param unknown $params
     */
    private function _doYeeDebit($card, $user, $repayment, $order_uuid, $params)
    {
        $user_data = [
            'id' => $user['id'],
            'amount' => YII_ENV_PROD ? $repayment['remain_money_amount'] : 1,
            'id_card' => $user['id_number'],
            'bank_id' => $card['bank_id'],
            'card_no' => $card['card_no'],
            'stay_phone' => (string)($card['phone'] ? $card['phone'] : $user['phone']),
            'real_name' => $user['name'],
            'order_id' => $order_uuid,
        ];
        //绑卡操作
        $ret = [];
        $yeepay_service = new Object();
        try {
            $yeepay_service = new YeePayService($user_data, YeePayService::AccountTypeCP);
            $yeepay_service = \common\services\RouteDebitService::getDebitService($yeepay_service, $repayment['order_id']);//切换扣款主体
            $bind_rs = $yeepay_service->bindBankcardApply($user_data['card_no']);//绑卡
            if ($bind_rs === true) {
                $yeepay_service->bindBankcardConfirm();
                $ret['code'] = 0;
            } else {
                if (isset($bind_rs['err_code']) && $bind_rs['err_code'] == '600326') {
                    $ret['code'] = 0;//已绑卡
                } else {
                    $ret['message'] = $bind_rs['err_msg'] . "#" . $bind_rs['err_code'];
                    $ret['code'] = $bind_rs['err_code'];
                }
            }
            if ($ret['code'] == 0) {
                $res = $yeepay_service->directPayNew(APP_NAMES, $user_data['amount'], $user_data['card_no'], $user_data['order_id']);//发送扣款
            }
        } catch (\Exception $e) {
            $ret['code'] = $e->getCode();
            $ret['message'] = $e->getMessage();
        }
        if ($ret['code'] == 0) {
            return ['data' => ['trade_state' => 0, 'debit_account' => $yeepay_service->account, 'debit_channel' => BankConfig::PLATFORM_YEEPAY]];
        }
        return ['data' => ['trade_state' => 2, 'message' => $ret['message'], 'debit_account' => $yeepay_service->account, 'debit_channel' => BankConfig::PLATFORM_YEEPAY]];

        /*$post = [
            'card_no' => $card['card_no'],
            'stay_phone' => $card['phone'] ? $card['phone'] : $user['phone'],
            'realname' => $user['name'],
            'idcard' => $user['id_number'],
            'amount' => YII_ENV_PROD ? $repayment['remain_money_amount'] : 1,
            'order_id' => $order_uuid,
            'user_id' => $user['id'],
            'user_type' => 2,
            'user_ip' => isset($params['user_ip']) ? $params['user_ip'] : '',
        ];
        if (YII_ENV_PROD) {
            $url = 'http://pay.api.koudailc.com/debit/direct-pay';
        } else if (YII_ENV_TEST) {
            $url = 'http://42.96.204.114/pay_v1/pay.api.koudailc.com/web/debit/direct-pay';
        } else {
            $url = 'http://192.168.39.214/pay_v1/pay.api.koudailc.com/web/debit/direct-pay';
        }
        return CurlHelper::curlHttp($url, 'post', $post, 20);*/
    }

    /**
     * 支付中心代扣
     * @param unknown $card
     * @param unknown $user
     * @param unknown $repayment
     * @param unknown $order_uuid
     * @param unknown $params
     */
    private function _payCenterDebit($card, $user, $repayment, $order_uuid, $params)
    {
        $ret['code'] = 0;
        $ret['message'] = '未知错误';

        $user_data = [
            'id' => $user['id'],
            'amount' => YII_ENV_PROD ? $repayment['remain_money_amount'] : 1,
            'id_card' => $user['id_number'],
            'bank_id' => $card['bank_id'],
            'card_no' => $card['card_no'],
            'stay_phone' => (string)($card['phone'] ? $card['phone'] : $user['phone']),
            'real_name' => $user['name'],
            'order_id' => $order_uuid,
        ];
        try {
            $user_data['user_id'] = $user_data['id'];
            $user_data['realname'] = $user_data['real_name'];
            $user_data['idcard'] = $user_data['id_card'];
            $user_data['order_id'] = $order_uuid;
            $user_data["platform"] = 0;
            $user_data['client_type'] = 1;
            $charge_rs = \common\soa\PaySoa::instance('Debit')->directPay($user_data);
            if (intval($params['trade_state']) === 1) $params['trade_state'] = 0;
        } catch (\Exception $e) {
            $ret['code'] = $e->getCode();
            $ret['message'] = $e->getMessage();
        }
        if ($ret['code'] == 0) {
            if ($charge_rs['trade_state'] == 0) {
                return ['data' => ['trade_state' => 0, 'debit_account' => $charge_rs['merchant_id'], 'debit_channel' => $charge_rs['platform']]];
            } elseif (intval($charge_rs['trade_state']) == 9) {
                return ['data' => ['trade_state' => 9, 'debit_account' => $charge_rs['merchant_id'], 'debit_channel' => $charge_rs['platform']]];
            }
            return ['data' => ['trade_state' => 2, 'message' => $charge_rs['message'], 'debit_account' => $charge_rs['merchant_id'], 'debit_channel' => $charge_rs['platform']]];
        }
        return ['data' => ['trade_state' => 2, 'message' => $ret['message'], 'debit_account' => 0, 'debit_channel' => 0]];
    }

    /**
     * 支付通道
     * @param unknown $card
     * @param unknown $user
     * @param unknown $repayment
     * @param unknown $order_uuid
     * @param unknown $params
     */
    /*private function _doCharge($card, $user, $repayment, $order_uuid, $params)
    {
       $platform = \common\services\RouteDebitService::getDebitChannel($card, $repayment['order_id'], 2);
        if ($platform == BankConfig::PLATFORM_FYPAY) {
            return $this->_doFyCharge($card, $user, $repayment, $order_uuid, $params);

        }
        return ['data' => ['trade_state' => 2, 'message' => '无合适的支付通道']];
    }*/
    public function _doCaptcha($card, $type)
    {
        $userService = Yii::$container->get('userService');
        $ret = $userService->generateAndSendCaptcha($card->phone,$type, $sms_type = false, $check = 0);
        /*if($ret){
            $ret = [
                'data'=>[
                    'debit_account'=>1,
                    'debit_channel'=>10,
                    'trade_state'=>0,
                ]
            ];
            return $ret;
        }else{

            $ret= [
                'data'=>[
                    'trade_state'=>2
                ]
            ];
            return $ret;
        }*/
        return $ret;
    }
    /**
     * 富友支付通道
     * @param unknown $card
     * @param unknown $user
     * @param unknown $repayment
     * @param unknown $order_uuid
     * @param unknown $params
     */
    private function _doFyCharge($card, $user, $repayment, $order_uuid, $params)
    {
        $accont = [
            'user_id' => $user['id'],
            'user_type' => 2,
            'client_ip' => isset($params['user_ip']) ? $params['user_ip'] : '',
            'realname' => $user['name'],
            'idcard' => $user['id_number'],
            'card_no' => $card['card_no'],
            'money' => YII_ENV_PROD ? $repayment['remain_money_amount'] : 10,
            'phone' => $card['phone'] ? $card['phone'] : $user['phone'],
            'created_at' => time(),
            'order_id' => $repayment['order_id'],
        ];
        $fuiou = new \common\services\pay\FuiouPayService($accont);
        $fuiou->phone = $accont['phone'];
        $ret = ['data' => ['trade_state' => 2]];
        try {
            \common\services\RouteDebitService::getDebitService($fuiou, $repayment['order_id']);//切换扣款主体
            $bank_order = $fuiou->chargeApply($accont['money'], $accont['card_no'], $order_uuid);
            \Yii::$app->cache->set('account_' . $order_uuid, $accont, 600);
            \Yii::$app->cache->set('account_id_' . $order_uuid, $fuiou->account, 600);
            $ret = ['data' => ['trade_state' => 0, 'pay_order_id' => $bank_order]];
        } catch (\Exception $e) {
            $ret['data']['message'] = $e->getMessage();
            \Yii::error($e->getMessage() . '--line:' . $e->getLine());
        }
        $ret['data']['debit_channel'] = BankConfig::PLATFORM_FYPAY;
        $ret['data']['debit_account'] = $fuiou->account;
        return $ret;
    }

    /**
     * 确认支付
     * @param unknown $sms_code
     * @param unknown $order_id
     * @param array $params
     */
    public function confirmCharge($sms_code, $order_id, $params = [])
    {
        $account = \Yii::$app->cache->get('account_' . $order_id);
        if (!$account) {
            return false;
        }
        $account_id = \Yii::$app->cache->get('account_id_' . $order_id);
        $fuiou = new \common\services\pay\FuiouPayService($account, $account_id);
        try {
            $cret = $fuiou->chargeConfirm($sms_code, $account['card_no'], $account['money'], $order_id);
            return true;
        } catch (\Exception $e) {
            if (isset($account['order_id'])) {
                UserCreditMoneyLog::setDebitErrorMsg($account['user_id'], $account['order_id'], $e->getMessage());
            }
            \Yii::error($e->getMessage() . '--line:' . $e->getLine());
        }

        return false;
    }

    public function applyAliyPay($order, $repayment,$money=null) {
        if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_SUCCESS, 'message' => '订单已还款,请勿重复还款!'];
        }
        $autoDebitLog = AutoDebitLog::find()->where(['user_id' => $order['user_id'],'order_id' => $order['id']])->orderBy('id desc')->one();
        $FinancialDebitRecord = FinancialDebitRecord::find()->where(['user_id' => $order['user_id'],'loan_record_id' => $order['id']])->andWhere(['>=', 'created_at', \strtotime(date('Y-m-d'))])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if ($autoDebitLog && ($autoDebitLog->status == AutoDebitLog::STATUS_DEFAULT || $autoDebitLog->status == AutoDebitLog::STATUS_WAIT)) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_APPLY, 'message' => '扣款正在进行中,请稍后再试'];
        }
        if ($FinancialDebitRecord && $FinancialDebitRecord->status == FinancialDebitRecord::STATUS_RECALL) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_APPLY, 'message' => '扣款正在处理中,请稍后再试'];
        }
        $order_id = \common\helpers\StringHelper::generateUniqid();
        $operator_money = $money ? $money : $repayment['remain_money_amount'];
        $operator_money = intval(bcmul($operator_money,1));
        try {
            $autoDebitLogAttr = [
                'user_id' => $order['user_id'],
                'order_id' => $order['id'],
                'order_uuid' => $order_id,
                'card_id' => '1001',
                'status' => AutoDebitLog::STATUS_DEFAULT,
                'money' => $operator_money,
                'debit_type' => AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            if (!AutoDebitLog::saveRecord(autoDebitLogAttr)) {
                return [ 'code'=> UserCreditMoneyLog::STATUS_FAILED, 'message' => 'AutoDebitLog记录保存失败,请稍后再试'];
            }
            $res = $this->payAliyApp($order_id,$money);
            if ($res['status'] == 0) {
                return [ 'code'=> UserCreditMoneyLog::STATUS_ING, 'message' => '扣款申请成功!','response' => $res['response']];
            } else {
                return [ 'code'=> UserCreditMoneyLog::STATUS_FAILED, 'message' => '扣款申请失败!'];
            }
        } catch (\Exception $e) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_FAILED, 'message' => '扣款申请异常!'];
        }

    }

    /**
     * 汇潮支付宝支付
     * @param $order
     * @param $repayment
     * @param null $money
     * @return array
     */
    public function applyHcAliyPay($order, $repayment,$money=null) {
        if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_SUCCESS, 'message' => '订单已还款,请勿重复还款!'];
        }
        $autoDebitLog = AutoDebitLog::find()->where(['user_id' => $order['user_id'],'order_id' => $order['id']])->orderBy('id desc')->one();
        $FinancialDebitRecord = FinancialDebitRecord::find()->where(['user_id' => $order['user_id'],'loan_record_id' => $order['id']])->andWhere(['>=', 'created_at', \strtotime(date('Y-m-d'))])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if ($autoDebitLog && ($autoDebitLog->status == AutoDebitLog::STATUS_DEFAULT || $autoDebitLog->status == AutoDebitLog::STATUS_WAIT)) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_APPLY, 'message' => '扣款正在进行中,请稍后再试'];
        }
        if ($FinancialDebitRecord && $FinancialDebitRecord->status == FinancialDebitRecord::STATUS_RECALL) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_APPLY, 'message' => '扣款正在处理中,请稍后再试'];
        }
        $order_id = \common\helpers\StringHelper::generateUniqid();
        $operator_money = $money ? $money : $repayment['remain_money_amount'];
        $operator_money = intval(bcmul($operator_money,1));
        try {
            $autoDebitLogAttr = [
                'user_id' => $order['user_id'],
                'order_id' => $order['id'],
                'order_uuid' => $order_id,
                'card_id' => '1001',
                'status' => AutoDebitLog::STATUS_DEFAULT,
                'money' => $operator_money,
                'debit_type' => AutoDebitLog::DEBIT_TYPE_ACTIVE_HC,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            if (!AutoDebitLog::saveRecord($autoDebitLogAttr)) {
                return [ 'code'=> UserCreditMoneyLog::STATUS_FAILED, 'message' => 'AutoDebitLog记录保存失败,请稍后再试'];
            }
            if (YII_ENV_PROD) {
                $operator_money_new = sprintf('%.2f',$operator_money/100);
            }else{
                $operator_money_new = '1.00';
            }
            $params = [
                'merchantOutOrderNo' => $order_id,
                'merid' => FinancialService::KD_HC_MERID,
                'noncestr' => 'hc'.\common\helpers\StringHelper::generateUniqid(),
                'orderMoney' => $operator_money_new,
                'orderTime' => date("YmdHis"),
                'notifyUrl' => FinancialService::KD_HC_NOTIFY_URL,
            ];
            $aliPayURL = FinancialService::KD_HC_HOST_URL;
            foreach ($params as $k => $v) { $aliPayURL .= $k.'='.$v.'&';}
            $sign = Order::genHcSign($params);
            $aliPayURL .= 'sign='.$sign;
            return [ 'code'=> UserCreditMoneyLog::STATUS_ING, 'message' => '扣款申请成功!','aliPayURL' => $aliPayURL];
        } catch (\Exception $e) {
            return [ 'code'=> UserCreditMoneyLog::STATUS_FAILED, 'message' => '扣款申请异常!'];
        }

    }

    /**
     * 确认支付宝支付
     * @param $order_id
     * @return bool
     */
    public function payAliyApp($order_id) {
        $autoDebitLog = AutoDebitLog::findOne(['order_uuid' => $order_id]);
        $money = intval(bcmul($autoDebitLog->money,1));
        $ip =\common\helpers\ToolsUtil::getIp();
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '180.175.171.233';
        }
        if (YII_ENV_PROD) {
            $url = 'http://jspay.koudailc.com:8081/repay-api/repay/repay';
            $params = [
                'bank_id' => 1001,
                'money' => $money,
                'user_ip' => $ip,
                'user_id' => $autoDebitLog -> user_id,
                'order_id' => $autoDebitLog -> order_uuid,
                'project_name' => FinancialService::KD_PROJECT_NAME_ALIPAY,
                'pay_summary' => empty($autoDebitLog->remark) ? 'wzd_debit '.date(DATE_ATOM) : $autoDebitLog->remark
            ];
            $params['sign'] = \common\models\Order::getPaySign($params, $params['project_name']);
            $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $params);
        } else { //开发环境直接设置为成功
            $params['order_id'] = $autoDebitLog->order_uuid;
            $params['project_name'] = 'jituanjujian';
            $params['bank_id'] = '1001';
            $params['money'] = $money?$money:$autoDebitLog->money;
            $params['user_id'] = $autoDebitLog->user_id;
            $params['user_ip'] = '192.168.8.101';
            $ret = [
                'code' => 0,
                'pay_order_id' => 'jk12324343'.rand(100,999).rand(100,999),
                'third_platform' => '7',
                'response' => 'alert(1212121)'
            ];
        }
        //根据返回结果更改还款日志记录状态
        if ($ret && isset($ret['code']) && $ret['code'] == 0) {
            $autoDebitLog -> pay_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';
            $autoDebitLog -> platform = isset($ret['third_platform']) ? $ret['third_platform'] : 0;
            if ($autoDebitLog->save()) {
                return ['status' => 0,'response' => $ret['response']];
            }
        } elseif ($ret && isset($ret['code'])) { //接口有数据返回的情况下
            $msg = '支付宝主动还款申请失败ID:'.$autoDebitLog->id.', uid:'.$autoDebitLog->user_id.', order_uuid:'.$autoDebitLog->order_uuid.' 失败原因:接口扣款请求失败';
            if (YII_ENV_PROD)
            {
                MessageHelper::sendSMS(NOTICE_MOBILE,$msg);
            }
            $autoDebitLog -> status = AutoDebitLog::STATUS_FAILED; //还款失败
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'未知错误!';
            $autoDebitLog -> pay_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';
            $autoDebitLog -> platform = isset($ret['third_platform']) ? $ret['third_platform'] : 0;
            $autoDebitLog -> save();
        } else {
            $msg = '支付宝主动还款申请失败,接口无任何返回结果:uid'.$autoDebitLog->user_id.'order_uuid'.$autoDebitLog->order_uuid;
            MessageHelper::sendSMS(NOTICE_MOBILE,$msg);
        }
        return ['status' => -1];
        //return false;
    }

    /**
     * 申请还款/扣款
     * @param [] $order 订单模型数组
     * @param [] $repayment 还款模型数组
     * @param [] $card 卡模型数组
     */
    public function applyDebit($order, $repayment, $card, $extra = []) {
        if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return false;
        }
        //切换到 autoDebitLog 记录表
        $autoDebitLog = AutoDebitLog::find()->where(['user_id' => $order['user_id'],'order_id'=> $order['id']])
            ->orderBy('id desc')
            ->one();
        if ($autoDebitLog && ($autoDebitLog->status == AutoDebitLog::STATUS_DEFAULT || $autoDebitLog->status == AutoDebitLog::STATUS_WAIT)) {
            UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], -5);
            return false;
        }
        $FinancialDebitRecord = FinancialDebitRecord::find()
            ->where(['user_id' => $order['user_id'],'loan_record_id' => $order['id']])
            ->andWhere(['>=', 'created_at', \strtotime(date('Y-m-d'))])
            ->orderBy(['id'=>SORT_DESC])
            ->limit(1)
            ->one();
        if ($FinancialDebitRecord && $FinancialDebitRecord->status == FinancialDebitRecord::STATUS_RECALL) {
            UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], -5);
            return false;
        }
        if (!in_array($card->bank_id,CardInfo::$debitbankInfo)) {
            UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], -4);
            return false;
        }

        $statusDay = UserCreditMoneyLog::getDebitStatusDay($order['user_id'], $card->id);
        if ($statusDay == UserCreditMoneyLog::STATUS_FAILED) {
            UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], -3);
            return false;
        }
        if(!FinancialDebitRecord::addDebitLock($order['id'])){
            return false;
        }
        $order_id = \common\helpers\StringHelper::generateUniqid();
        try {
            $autoDebitLogAttr = [
                'user_id' => $order['user_id'],
                'order_id' => $order['id'],
                'order_uuid' => $order_id,
                'card_id' => $card->id,
                'status' => AutoDebitLog::STATUS_WAIT,
                'money' => $repayment['remain_money_amount'],
                'debit_type' => AutoDebitLog::DEBIT_TYPE_ACTIVE,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            if (!AutoDebitLog::saveRecord($autoDebitLogAttr)) {
                UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], UserCreditMoneyLog::STATUS_FAILED);
                return false;
            }
            if ($this->payDebit($order_id, $card, $extra)) {
                UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], UserCreditMoneyLog::STATUS_SUCCESS);
                return true;
            } else {
                UserCreditMoneyLog::setDebitStatus($order['user_id'], $order['id'], UserCreditMoneyLog::STATUS_FAILED);
                return false;
            }
        } catch (\Exception $e) {
            \Yii::error($e);
            return false;
        }
    }


    /**
     * 新申请还款/扣款 2017-08-24
     * @param [] $order 订单模型数组
     * @param [] $repayment 还款模型数组
     * @param [] $card 卡模型数组
     */
    public function applyDebitNew($order, $repayment, $card, $extra = []) {
        if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return [ 'code' => -1, 'msg' => '该订单已还款，请勿重复操作'];
        }
        $order_id = $repayment['order_id'];
        $user_id = $repayment['user_id'];
        $current_time = strtotime(date('Y-m-d'));
        $autoDebitLog = AutoDebitLog::find()->select(['id'])
            ->where([ 'order_id' => $order_id,'user_id' => $user_id,'status' => [AutoDebitLog::STATUS_DEFAULT,AutoDebitLog::STATUS_WAIT]])
            ->one();
        if($autoDebitLog){
            return [ 'code' => -1, 'msg' => '扣款正在进行中，请勿重复操作'];
        }
        $financial_debit_record = FinancialDebitRecord::find()->select(['id'])
            ->where(['loan_record_id' => $order_id,'user_id' => $user_id,'status' => FinancialDebitRecord::STATUS_RECALL])
            ->andWhere(['>=', 'created_at', $current_time])
            ->one();
        if ($financial_debit_record) {
            return [ 'code' => -1, 'msg' => '扣款正在进行中，请勿重复操作'];
        }
        if (!FinancialDebitRecord::addDebitLock($order_id)) {
            return [ 'code' => -1, 'msg' => '扣款正在进行中，请勿重复操作'];
        }
        if (!in_array($card->bank_id,CardInfo::$debitbankInfo)) {
            return [ 'code' => -1, 'msg' => '银行卡不支持'];
        }
        $order_id = \common\helpers\StringHelper::generateUniqid();
        $operator_money = isset($extra['money']) ? $extra['money'] : $repayment['remain_money_amount'];
        try {
            $autoDebitLogAttr = [
                'user_id' => $order['user_id'],
                'debit_type' => isset($extra['debit_type']) ? $extra['debit_type'] : AutoDebitLog::DEBIT_TYPE_BACKEND,
                'order_id' => $order['id'],
                'order_uuid' => $order_id,
                'card_id' => $card->id,
                'status' => AutoDebitLog::STATUS_DEFAULT,
                'money' => $operator_money,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            if (!AutoDebitLog::saveRecord($autoDebitLogAttr)) {
                return [ 'code' => -1, 'msg' => '请求失败，请稍后再试'];
            }
            $ret = $this->PayDebitNew($order_id, $card, $extra);
            if ($ret['code'] == 0) {
                return [ 'code' => 0, 'msg' => '提交成功' ];
            } else  {
                return [ 'code' => -1, 'msg' => $ret['msg']];
            }
        } catch (\Exception $e) {
            return [ 'code' => -1, 'msg' => $e->getMessage().$e->getTraceAsString()];
        }
    }

    /*
 * 新确认支付直接扣款 王成-20170824
 * @param $loan_order_id  UserLoanOrder->id
 */
    public function PayDebitNew($order_id, $card, $extra = []) {
        $autoDebitLog = AutoDebitLog::findOne(['order_uuid' => $order_id]);

        $ip =\common\helpers\ToolsUtil::getIp();
        if(!filter_var($ip, FILTER_VALIDATE_IP)){
            $ip = '180.175.171.233';
        }
        if (!empty($extra) && !empty($extra['real_name'])) {
            $real_name = $extra['real_name'];
        } else {
            $real_name = Yii::$app->user->identity->name;
        }
        if (!empty($extra) && !empty($extra['id_card'])) {
            $id_card = $extra['id_card'];
        } else {
            $id_card = Yii::$app->user->identity->id_number;
        }

        $userLoanOrder = UserLoanOrder::findOne(['id' => $autoDebitLog -> order_id,'user_id' => $autoDebitLog -> user_id]);

        if (YII_ENV_PROD) {
            if (!preg_match("/^1[34578]{1}\d{9}$/",$card->phone)) {
                $errorMsg = '用户银行预留手机号有误,错误的手机号为:'.$card->phone.' 扣款驳回,扣款订单号:'.$autoDebitLog->order_uuid;
                MessageHelper::sendSMS(NOTICE_MOBILE,$errorMsg);
                return [ 'code' => -1, 'msg' => '请求失败,原因:银行预留手机号有误!' ];
            }
        }
//        $params = [
//            'project_name' => $project,
//            'order_id' => $autoDebitLog -> order_uuid,
//            'stay_phone' => $card->phone,
//            'real_name' => $real_name,
//            'id_card' => $id_card,
//            'bank_id' => $card->bank_id,
//            'card_no' => $card->card_no,
//            'money' => $autoDebitLog->money,
//            'user_ip' => $ip,
//            'pay_summary' => empty($autoDebitLog->remark) ? 'wzd_debit '.date(DATE_ATOM) : $autoDebitLog->remark,
//            'user_id' => $autoDebitLog->user_id,
//        ];

        $params = [
            'biz_order_no' => (string)$autoDebitLog -> order_uuid,
            'name'         => (string)$real_name,
            'id_card_no'   => (string)$id_card,
            'bank_card_no' => (string)$card->card_no,
            'bank_id'      => (string)$card->bank_id,
            'amount'       => (string)$autoDebitLog->money,
            'phone'        => (string)$card->phone,
        ];

        if (YII_ENV_PROD) {
            $service = new JshbService();
            $ret = $service->pushWithhold($params);
        } else { //开发环境直接设置为成功
            $ret['code'] = 0;
            $ret['msg'] = '测试提交成功';
            $ret['data']['pay_order_no'] ='jk'.(\common\helpers\StringHelper::generateUniqid());
            $ret['data']['pay_channel'] = 21;
        }

        $pay_channel = isset($ret['data']['pay_channel'])?$ret['data']['pay_channel']:0;
        if (isset(BankConfig::$platform_name[$pay_channel])){
            $third_platform = BankConfig::$platform_name[$pay_channel];
        }else{
            $third_platform = 0;
        }

        if ($ret && isset($ret['code']) && $ret['code'] == 0) {
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
            $autoDebitLog -> platform = isset($ret['data']['pay_channel']) ? $third_platform : 0;
            if ($autoDebitLog->save()) {
                return [ 'code' => 0, 'msg' => '提交成功' ];
            }else{
                return [ 'code' => -1, 'msg' => '请求失败,请稍后再试'];
            }
        }elseif(isset($ret['code']) && ($ret['code'] == 104)) { //接口有数据返回的情况下
            $autoDebitLog -> status = AutoDebitLog::STATUS_FAILED; //还款失败
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'未知错误!';
            $autoDebitLog -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
            $autoDebitLog -> platform= isset($ret['data']['pay_channel']) ? $third_platform : 0;
            $autoDebitLog -> save();
            return [ 'code' => -1,'msg' => '订单不存在,请稍后再试'];
        }elseif(isset($ret['code']) && $ret['code'] == 101){
            $autoDebitLog -> status = AutoDebitLog::STATUS_FAILED; //还款失败
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'未知错误!';
            $autoDebitLog -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
            $autoDebitLog -> platform= isset($ret['data']['pay_channel']) ? $third_platform : 0;
            $autoDebitLog -> save();
            return [ 'code' => -1,'msg' => '银行维护,请使用支付宝还款'];
        }
        elseif ($ret && isset($ret['code']) && $ret['code'] == 1) { //接口有数据返回的情况下
            $autoDebitLog -> status = AutoDebitLog::STATUS_FAILED; //还款失败
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'未知错误!';
            $autoDebitLog -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
            $autoDebitLog -> platform = isset($ret['data']['pay_channel']) ? $third_platform : 0;
            $autoDebitLog -> save();
            return [ 'code' => -1, 'msg' => '请求失败,请稍后再试'];
        } else {
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
            if($ret){
                $msg = '['.$server_ip.']用户主动还款申请失败,uid:'.$autoDebitLog->user_id.' order_uuid:'.$autoDebitLog->order_uuid . ' ret:' . json_encode($ret,JSON_UNESCAPED_UNICODE);
            }else{
                $msg = '['.$server_ip.']用户主动还款申请失败,uid:'.$autoDebitLog->user_id.' order_uuid:'.$autoDebitLog->order_uuid;
            }
            if (YII_ENV_PROD) {
                MessageHelper::sendInternalSms(NOTICE_MOBILE,$msg);
            }
        }
        return [ 'code' => -1, 'msg' => '请求失败' ];
    }

    /*
     * 确认支付直接扣款
     * @param $loan_order_id  UserLoanOrder->id
     */
    public function PayDebit($order_id, $card, $extra = []) {
        $autoDebitLog = AutoDebitLog::findOne(['order_uuid' => $order_id]);

        $ip =\common\helpers\ToolsUtil::getIp();
        if (!empty($extra)) {
            $real_name = $extra['real_name'];
            $id_card = $extra['id_card'];
        } else {
            $real_name = Yii::$app->user->identity->name;
            $id_card = Yii::$app->user->identity->id_number;
        }
        $userLoanOrder = UserLoanOrder::findOne(['id' => $autoDebitLog -> order_id,'user_id' => $autoDebitLog -> user_id]);
        if ($userLoanOrder -> fund_id == LoanFund::ID_SUBA) {
            $project = FinancialService::SUBA_PROJECT_NAME;
            $url = 'test';
        } else {
            $project = FinancialService::KD_PROJECT_NAME;
            $url = 'test';
        }

        if (YII_ENV_PROD) {
            if(!preg_match("/^1[34578]{1}\d{9}$/",$card->phone)){
                $errorMsg = '用户银行预留手机号有误,错误的手机号为:'.$card->phone.' 扣款驳回,订单号:'.$autoDebitLog->order_uuid;
                MessageHelper::sendSMS(NOTICE_MOBILE, $errorMsg);
                return false;
            }
        }

        $params = [
            'project_name' => $project,
            'order_id' => $autoDebitLog->order_uuid,
            'stay_phone' => $card->phone,
            'real_name' => $real_name,
            'id_card' => $id_card,
            'bank_id' => $card->bank_id,
            'card_no' => $card->card_no,
            'money' => $autoDebitLog->money,
            'user_ip' => $ip,
            'pay_summary' => empty($autoDebitLog->remark) ? 'wzd_debit '.date(DATE_ATOM) : $autoDebitLog->remark,
            'user_id' => $autoDebitLog->user_id,
        ];

        $params['sign'] = \common\models\Order::getPaySign($params, $params['project_name']);
        if (YII_ENV_PROD) {
            $ret = \common\helpers\CurlHelper::FinancialCurl($url, 'POST', $params, 120);
        } else { //开发环境直接设置为成功
            $ret['code'] = 0;
            $ret['msg'] = '还款申请测试提交成功';
            $ret['pay_order_id'] = 'jk'.(\common\helpers\StringHelper::generateUniqid());
            $ret['third_platform'] = 14;
        }

        if ($ret && isset($ret['code']) && $ret['code'] == 0) {
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'申请成功!';
            $autoDebitLog -> pay_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';
            $autoDebitLog -> platform = isset($ret['third_platform']) ? $ret['third_platform'] : 0;
            if ($autoDebitLog -> save()) {
                return true;
            }
        } elseif ($ret && isset($ret['code'])) { //接口有数据返回的情况下
            $autoDebitLog -> status = AutoDebitLog::STATUS_FAILED; //还款失败
            $autoDebitLog -> updated_at = time();
            $autoDebitLog -> remark = isset($ret['msg'])?$ret['msg']:'未知错误!';
            $autoDebitLog -> pay_order_id = isset($ret['pay_order_id']) ? $ret['pay_order_id'] : '';
            $autoDebitLog -> platform = isset($ret['third_platform']) ? $ret['third_platform'] : 0;
            if ($autoDebitLog -> save()) {
                return false;
            }
        } else {
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
            if ($ret) {
                $msg = '['.$server_ip.'][旧]主动还款申请失败:uid'.$autoDebitLog->user_id.'order_uuid'.$autoDebitLog->order_uuid . 'ret:' . json_encode($ret,JSON_UNESCAPED_UNICODE);
            } else {
                $msg = '['.$server_ip.'][旧]主动还款申请失败:uid'.$autoDebitLog->user_id.'order_uuid'.$autoDebitLog->order_uuid;
            }
            if (YII_ENV_PROD) {
                MessageHelper::sendSMS(NOTICE_MOBILE,$msg);
            }
        }
        return false;
    }

    /**
     * 解析易宝回调结果
     * @param unknown $params
     * @param number $type
     */
    public function parseYeeResult($params, $account = null)
    {
        if (empty($params)) {
            return false;
        }
        $yeepay_service = new YeePayService(null, $account ? $account : YeePayService::AccountTypeCP);
        $params = $yeepay_service->yeepay->parseReturn($params['data'], $params['encryptkey']);
        $params['order_id'] = $params['orderid'];//口袋订单号
        $params['third_order_id'] = $params['yborderid'];//易宝订单号
        return $this->debitResult($params, 1);
    }

    public function parsePayCenterResult($params)
    {
        if (empty($params)) {
            return false;
        }
        $params['order_id'] = $params['orderid'];
        $params['third_order_id'] = $params['trade_no'];
        return $this->debitResult($params, 2);
    }

    /**
     * 解析口袋扣款支付回调结果
     * @param unknown $params
     */
    public function debitResult($params, $type = 0)
    {
        $order_id = 0;
        try {
            $order_id = $params['order_id'];
            if (!$type) {
                $sign = $params['sign'];
                if ($sign !== \md5($order_id . 'kdlc@pay.api')) {
                    \Yii::error('debitResultError:sign:' . $order_id, LogChannel::FINANCIAL_DEBIT);
                    return false;
                }
            }
            $log = UserCreditMoneyLog::findOne(['order_uuid' => $order_id]);
            if (!$log) {
                \Yii::error('UserCreditMoneyLog missing: ' . $order_id, LogChannel::FINANCIAL_DEBIT);
                return false;
            }
            if (!in_array($log['status'], [UserCreditMoneyLog::STATUS_ING,UserCreditMoneyLog::STATUS_NORMAL])) {
                \Yii::error( \sprintf('%s debitResultError:status error - %s', $order_id, $log['status']), LogChannel::FINANCIAL_DEBIT );
                return false;
            }

            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $log['order_id']])->asArray()->limit(1)->one();
            if (!$repayment) {
                \Yii::error('debitResultError:repayment missing - ' . $order_id, LogChannel::FINANCIAL_DEBIT);
                return false;
            }

            # 2 支付接口回调返回值
            $status = $params['status'] == 2 ? UserCreditMoneyLog::STATUS_SUCCESS : UserCreditMoneyLog::STATUS_FAILED;
            $remark = isset($params['result']) ? $params['result'] : '';

            if (UserCreditMoneyLog::updateDebitResult($log['id'], $status, $params['amount'], $remark)
                && $status == UserCreditMoneyLog::STATUS_SUCCESS
            ) {
                if ($this->_doBusinessCallBack($log, $repayment, $params, $remark)) {
                    return true;
                }
            } else {
                \Yii::error( \sprintf('[%s|%s] debitResultError:updateDebitResult[%s] failed - %s.',
                    $order_id, $log['id'],  $params['amount'], $status) , LogChannel::FINANCIAL_DEBIT);
            }
            //UserCreditMoneyLog::setDebitStatus($log['user_id'], $log['order_id'], UserCreditMoneyLog::STATUS_FAILED);
        }
        catch (\Exception $e) {
            \Yii::error('debitResultError:exception:' . $order_id . '|' . $e->getMessage(), LogChannel::FINANCIAL_DEBIT);
        }

        return false;
    }

    private function _doBusinessCallBack($log, $repayment, $params, $remark) {
        $service = Yii::$container->get('orderService');
        if ($log['payment_type'] == UserCreditMoneyLog::PAYMENT_TYPE_DELAY) { //展期
            $back_result = $service->delayLqb($log['remark'], $repayment);
            $error_key = 'delayLqb';
            $error_msg = '主动展期回调失败';
        }
        else { //主动还款
            $back_result = $service->callbackDebitMoney($log['order_id'], $repayment['id'], $repayment['debit_times'], $params['amount'], $remark, 'self', ['addUserCreditMoneyLog' => 0, 'UserCreditMoneyLogId' => $log['id']]);
            $error_key = 'callbackDebitMoney';
            $error_msg = '主动还款回调失败';
        }

        if ($back_result['code'] == 0) {
            UserCreditMoneyLog::setDebitStatus($log['user_id'], $log['order_id'], UserCreditMoneyLog::STATUS_SUCCESS);
            return true;
        }

        \Yii::error(sprintf('debitResultError:%s:%s-%s', $error_key, $log['order_uuid'], print_r($back_result,1)));
        if (YII_ENV_PROD) {
            UserLoanOrder::sendSMS(NOTICE_MOBILE, print_r($back_result,1) . ':' . $log['order_uuid']);
        }
        return false;
    }

    /**
     * 申请借款订单
     * @param integer $user_id
     * @param float $money
     * @param integer $day
     * @param string $card_id
     * @param array $params 参数
     * @return array
     */
    public function applyLoan($user_id, $money, $day, $card_id, $params = [])
    {
        $checkRet = $this->checkCanApply($user_id, $money, $params);
        if ($checkRet['code']) {
            return $checkRet;
        }
        //计算利息
        //判断是否是首单
        //$quota = UserCreditTotal::findOne(['user_id'=>$user_id]);
        $creditChannelService = \Yii::$app->creditChannelService;
        $quota = $creditChannelService->getCreditTotalByUserId($user_id);
        $user_card_type = $quota->card_type ? $quota->card_type : 1;
        if (isset($params['card_type']) && $user_card_type < $params['card_type']) {
            return [
                'code' => -1,
                'message' => '无资格申请高级卡订单',
            ];
        }
        $card_type = isset($params['card_type']) ? $params['card_type'] : '';
        try {
            $loan_info = Util::calcLoanInfo($day, $money, $card_type, $user_id);
        } catch (\Exception $e) {
            return [
                'code' => -1,
                'message' => $e->getMessage(),
            ];
        }

//        $interests = 0;
        //综合汇率
        $config_management_fee_rate = \Yii::$app->params['management_fee_rate'];
        $interests=$money*$config_management_fee_rate;
        if (isset($params['card_type']) && $params['card_type'] == BaseUserCreditTotalChannel::CARD_TYPE_TWO) {//金卡
            $pocket_late_apr = BaseUserCreditTotalChannel::$multi_card_info[$params['card_type']]['card_late_apr'];
        } else {
            $pocket_late_apr = $quota->pocket_late_apr;
        }

        if(!empty($params['coupon_id'])){//如果使用了优惠券
            if(!empty($params['free_percentage'])){//如果使用了减息券
                $free_percentage_amount = $loan_info['counter_fee'] *0.1* $params['free_percentage']/100; //多除以100是对真实的利息进行四舍五入
                $counter_fee = $loan_info['counter_fee'] - $free_percentage_amount;//减息券是减免手续费里的百分占比金额
            } else {
                $counter_fee = $loan_info['counter_fee'] - $params['amount'];
            }
        }else{
            $counter_fee = $loan_info['counter_fee'];
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            //第一步：创建借款订单
            $user_loan_order = new UserLoanOrder();

            $user_loan_order->user_id = $user_id;
            $user_loan_order->order_type = UserLoanOrder::LOAN_TYPE_LQD;
            $user_loan_order->money_amount = $money;
            $user_loan_order->apr = $quota->pocket_apr;
            $user_loan_order->loan_method = BaseUserCreditTotalChannel::LOAN_TYPE_DAY;
            $user_loan_order->loan_term = $day;
            $user_loan_order->loan_interests = $interests / 100;
            $user_loan_order->operator_name = $user_id;
            $user_loan_order->remark = "";
            $user_loan_order->sub_order_type = isset($params['sub_order_type']) && isset(UserLoanOrder::$sub_order_type[$params['sub_order_type']]) ? $params['sub_order_type'] : UserLoanOrder::SUB_TYPE_XJD;//零钱包的子类型(手机信用卡app)
            $user_loan_order->from_app = isset($params['from_app']) && isset(UserLoanOrder::$from_apps[$params['from_app']]) ? $params['from_app'] : UserLoanOrder::FROM_APP_XJK;//来源的app
            $user_loan_order->late_fee_apr = $pocket_late_apr;
            $user_loan_order->order_time = time();
            $user_loan_order->created_at = time();
            $user_loan_order->updated_at = time();
            // 没有通讯录的订单
            if (isset($params["order_other"]) && in_array($params["order_other"], UserLoanOrder::$order_no_contact)) {
                $user_loan_order->status = isset($params["is_real_contact"]) ? UserLoanOrder::STATUS_CHECK : UserLoanOrder::STATUS_WAIT_FOR_CONTACTS;
            } else {
                $user_loan_order->status = UserLoanOrder::STATUS_CHECK;
            }
            $user_loan_order->card_id = $card_id;
            $user_loan_order->counter_fee = sprintf("%0.2f", $counter_fee);
            $user_loan_order->coupon_id = isset($params['coupon_id']) ? $params['coupon_id'] : 0;
            $user_loan_order->card_type = isset($params['card_type']) && isset(BaseUserCreditTotalChannel::$card_type[$params['card_type']]) ? $params['card_type'] : BaseUserCreditTotalChannel::CARD_TYPE_ONE;//白卡/金卡

            if (!$user_loan_order->save()) {
                //Yii::error("申请贷款订单 保存UserLoanOrder失败：" . var_export($user_loan_order->getErrors()));
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败,请稍后再试',
                ];
            }
            $order_id = $user_loan_order->attributes['id'];

            //第二步：修改额度表中的锁定金额
            $quota->locked_amount = $quota->locked_amount + $money;
            if (!$quota->save()) {
                //Yii::error("申请贷款订单 保存UserCreditTotal失败：" . var_export($quota->getErrors()));
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败(修改锁定额度失败),请稍后再试',
                ];
            }

            //第三步：资金流水
            $user_credit_log = new  UserCreditLog();

            $user_credit_log->user_id = $user_id;
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_LOAN_LOCK;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $money;
            $user_credit_log->apr = $quota->pocket_apr;
            $user_credit_log->interests = 0;
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = Util::getUserIP();
            $user_credit_log->total_money = $quota->amount;
            $user_credit_log->used_money = $quota->used_amount;
            $user_credit_log->unabled_money = $quota->locked_amount;
            if (!$user_credit_log->save()) {
                //Yii::error("申请贷款订单 保存UserCreditLog失败：" . var_export($user_credit_log->getErrors()));
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败(保存流水日志失败),请稍后再试',
                ];
            }
            $transaction->commit();

            //触发订单提交成功事件 自定义的数据可添加到custom_data里
            if ($user_loan_order->status == UserLoanOrder::STATUS_CHECK) {
                if (empty($params['skipTriggerEvent'])) {
                    $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_APPLY_ORDER, new \common\base\Event(['custom_data' => []]));
                }
            }

        } catch (\Exception $e) {
            $transaction->rollBack();
            //Yii::error($e);
            return [
                'code' => -1,
                'message' => '生成借款订单失败,请稍后再试' . (YII_ENV == 'prod' ? '' : "：{$e->getMessage()}"),
            ];
        }

        if ($user_loan_order->status == UserLoanOrder::STATUS_CHECK) {
            //事件处理队列    申请成功
            RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                'event_name' => AppEventService::EVENT_SUCCESS_APPLY,
                'params' => ['user_id' => $user_id, 'order_id' => $order_id],
            ])]);
        }


        return [
            'code' => 0,
            'message' => '申请成功',
            'order_id' => $order_id,
            'order' => $user_loan_order
        ];
    }


    /**
     * 获取借款手续费
     * @param float $amount
     * @param integer $day 借款天数获取新订单
     * @return float
     */
    public static function countLoanServiceFee($amount, $day)
    {
        $loan_info = Util::calcLoanInfo($day, $amount);
        return $loan_info['counter_fee'];
    }

    /**
     * 获取借款利息
     * @param float $amount 借款金额
     * @param integer $day 借款天数
     * @return float
     */
    public static function countLoanInterest($amount, $day)
    {
        return 0;
    }

    /**
     * 获取用户借款需要偿还的总金额
     * @param float $amount 贷款金额
     * @param integer $day 贷款天数
     */
    public static function countLoanRepayAmount($amount, $day)
    {
        return $amount + static::countLoanInterest($amount, $day);
    }

    /**
     * 获取最新的借款时间
     */
    public function getLoanOrderTime($user_id)
    {
        $list = UserLoanOrder::find()
            ->from(UserLoanOrder::tableName() . ' as o')
            ->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
            ->where(['o.user_id' => $user_id, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
            ->select(['o.*', 'r.id as rep_id',
                'r.is_overdue', 'r.overdue_day',
                'r.status as rep_status', 'r.plan_fee_time',
                'r.coupon_id as rep_coupon_id', 'r.coupon_money as rep_coupon_money',
                'r.updated_at as r_time','o.updated_at as o_time'
                ])
            ->orderBy('o.id desc')->asArray()->one();
        $userCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$user_id,'order_id'=>$list['id']])->orderBy(['id'=>SORT_DESC])->one();
        $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$user_id,'order_id'=>$list['id']])->orderBy(['id'=>SORT_DESC])->one();

        if ($list['rep_id']) {//已放款
            if ($list['rep_status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $time = $list['r_time'];
            } else if ($list['is_overdue'] && $list['overdue_day']) {
                $time = $list['r_time'];
            } else {
                $time = $list['r_time'];
            }
        }elseif ($autoDebitLog ||$userCreditMoneyLog ){
            if($autoDebitLog && in_array($autoDebitLog['status'],[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT])) {
                $time = $autoDebitLog->updated_at;
            } elseif($userCreditMoneyLog && in_array($userCreditMoneyLog['status'],[UserCreditMoneyLog::STATUS_ING,UserCreditMoneyLog::STATUS_NORMAL,UserCreditMoneyLog::STATUS_APPLY])) {
                $time =$userCreditMoneyLog->updated_at;
            } else  {
                $time =$userCreditMoneyLog->updated_at;
            }
        } else {
            if ($list['status'] < UserLoanOrder::STATUS_CHECK) {
                $time = $list['o_time'];
            } else if ($list['status'] >= UserLoanOrder::STATUS_PAY
                && !in_array($list['status'], UserLoanOrder::$checkStatus)
            ) {
                $time = $list['trail_time'];
            } else {
                $time = $list['o_time'];
            }
        }

        if(empty($time)){
            $time = 0;
        }
        return $time;
    }

    /**
     * 检查是否能够申请借款
     * 如果是rong360来源 则暂时不检测额度
     * @param integer $user_id 借款用户ID
     * @return array
     */
    public function checkCanApply($user_id, $money, $params = [])
    {
        //查询是否有在审核状态下的订单，存在不给申请
        $user_loan_order = UserLoanOrder::find()->where(['user_id' => $user_id, 'order_type' => UserLoanOrder::LOAN_TYPE_LQD])
            ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere($params))
            ->andWhere(" status in(" . UserLoanOrder::STATUS_CHECK . "," . UserLoanOrder::STATUS_PASS . "," . UserLoanOrder::STATUS_REPEAT_TRAIL . "," . UserLoanOrder::STATUS_PENDING_LOAN . "," . UserLoanOrder::STATUS_PAY . "," . UserLoanOrder::STATUS_WAIT_FOR_CONTACTS . ")")->one();
        if ($user_loan_order) {
            return [
                'code' => -1,
                'message' => CodeException::$code[CodeException::HAVE_ORDER_CHECK],
                'rongcode' => '400',
                'baicode' => 2,
            ];
        }
        if ($this->checkHashUnRepayment($user_id, $params)) {
            return [
                'code' => -1,
                'message' => '您当前有一笔借款正在进行中，请还款后再试',
                'rongcode' => '400',
                'baicode' => 2,
            ];
        }
        $userService = Yii::$container->get('userService');
        if ($tip = $userService->getCanNotLoanMsgTip($user_id)) {
            return [
                'code' => -1,
                'message' => $tip,
            ];
        }
        //$quota = UserCreditTotal::findOne(['user_id'=>$user_id]);
        $creditChannelService = \Yii::$app->creditChannelService;
        $quota = $creditChannelService->getCreditTotalByUserId($user_id);
        if (false == $quota) {
            return [
                'code' => -1,
                'message' => '获取数据失败，请稍后再试',
            ];
        }
        $card_type = isset($params["card_type"]) ? $params["card_type"] : 1;

        $min_amount = 20000;
        $max_amount = 1000000;//$card_type < 2 ? 300000 : 500000;

        // todo ： 借款金额必须是整数

        // if (!isset($params['sub_order_type']) || $params['sub_order_type'] != UserLoanOrder::SUB_TYPE_RONG360) {
            $money_foor = ($money / 10000);
            if ($money < $min_amount || $money > $max_amount || floor($money_foor) != $money_foor) {
                return [
                    'code' => -1,
                    'message' => '借款金额不在规定范围内',
                ];
            }
        // }

        if (!isset($params['sub_order_type'])) {
            $params['sub_order_type'] = UserLoanOrder::SUB_TYPE_XJD;
        }

        //是否需要检查用户可用金额
        $is_checked_used_amount=true;
        if(isset($params['is_checked_used_amount'])){
            $is_checked_used_amount=$params['is_checked_used_amount'];
        }

        if (isset($params['sub_order_type']) && $params['sub_order_type'] == UserLoanOrder::SUB_TYPE_XJD && $is_checked_used_amount) {
            if ($money > ($quota->amount - $quota->used_amount - $quota->locked_amount)) {
                return [
                    'code' => -1,
                    'message' => '额度不足',
                ];
            }
        }


        // 是否检查放款量
        $gloden = true;
        $sub_order_type = isset($params['sub_order_type']) ? $params['sub_order_type'] : UserLoanOrder::SUB_TYPE_XJD;
        if ($sub_order_type == UserLoanOrder::SUB_TYPE_WUYAO) {
            $gloden = false;
        }
        // if ($gloden && ($card_type == 2 && Setting::getAppGlodenAmount() <= 0) || ($card_type == 1 && Setting::getAppCardAmount() <= 0)) {
        if ($gloden == true && $card_type == 2 && Setting::getAppGlodenAmount() <= 0) {
            return [
                'code' => -1,
                'message' => '亲，今日额度已抢完，请明日再申请哦',
            ];
        }

        if (isset($params["coupon_id"])) {
            if (self::checkUserCoupon($user_id, $params) == false) {
                return [
                    'code' => -1,
                    'message' => '当前红包不可用',
                ];
            }
        }

        return [
            'code' => 0
        ];
    }

    /**
     * 获取用户借款未准备好的资料 包括： bank_card,id_picture_negative,id_picture_positive,face_picture,id_number,phone,emergency_contact...
     * @param LoanPerson $user 贷款用户
     * @return array 如果数组为空 则表示资料已经完整 可以借款
     */
    public function getUnreadyProfile($user, $skip_keys = [])
    {
        $unready_profiles = [];
        #银行卡
        $bank_card = $user->getMainCard();
        if (!$bank_card) {
            $unready_profiles[] = 'bank_card';
        }

        #身份证号码 手机号
        if (!$user->id_number || !ToolsUtil::checkIdNumber($user->id_number)) {
            $unready_profiles[] = 'id_number';
        }
        #手机号
        if (!$user->phone || !ToolsUtil::checkMobile($user->phone)) {
            $unready_profiles[] = 'phone';
        }

        #身份证正面
        if (!UserProofMateria::find()->where([
            'user_id' => (int)$user->id,
            'type' => UserProofMateria::TYPE_ID_CAR_Z
        ])->limit(1)->one()
        ) {
            $unready_profiles[] = 'id_picture_positive';
        }
        #身份证反面
        if (!UserProofMateria::find()->where([
            'user_id' => (int)$user->id,
            'type' => UserProofMateria::TYPE_ID_CAR_F
        ])->limit(1)->one()
        ) {
            $unready_profiles[] = 'id_picture_negative';
        }

        #人脸
        if (!UserProofMateria::find()->where([
            'user_id' => (int)$user->id,
            'type' => UserProofMateria::TYPE_FACE_RECOGNITION
        ])->limit(1)->one()
        ) {
            $unready_profiles[] = 'face_picture';
        }

        $user_verification = $user->userVerification;
        #芝麻信用
//        if (!$user_verification || $user_verification->real_zmxy_status != UserVerification::VERIFICATION_YES) {
//            $unready_profiles[] = 'real_zm_verification';
//        }

        #运营商报告
        if (!$user_verification || $user_verification->real_jxl_status != UserVerification::VERIFICATION_YES) {
            $unready_profiles[] = 'real_jxl_verification';
        }

        #绑卡验证
        if (!$user_verification || $user_verification->real_bind_bank_card_status != UserVerification::VERIFICATION_YES) {
            $unready_profiles[] = 'real_card_verification';
        }

        #实名认证
        if (!$user_verification || $user_verification->real_verify_status != UserVerification::VERIFICATION_YES) {
            $unready_profiles[] = 'real_verification';
        }

        #紧急联系人认证
        if (!$user_verification || $user_verification->real_contact_status != UserVerification::VERIFICATION_YES) {
            $unready_profiles[] = 'real_contact_verification';
        }

        #紧急联系人
        $emergency_contact = UserContact::findOne(['user_id' => (int)$user->id]);
        if (!$emergency_contact) {
            $unready_profiles[] = 'emergency_contact';
        }

        $return_keys = [];
        foreach ($unready_profiles as $value) {
            if (!in_array($value, $skip_keys)) {
                $return_keys[] = $value;
            }
        }
        return $return_keys;
    }

    /**
     * 获取可以借款的年龄范围
     * @return [] 第一个值为最小年龄 第二信值为最大年龄
     */
    public static function getAgeRange()
    {
        return [18, 35];
    }

    /**
     * 检查渠道的借款期限
     * @param integer $term 借款期限 单位为天
     * @param integer $channel 渠道  见 LoanPerson::PERSON_SOURCE_
     */
    public static function checkChannelLoanTerm($term, $channel)
    {
        return in_array((int)$term, [7, 14]);
    }

    /**
     * 用户借款展期
     * @param int $order_id
     * @param int $user_id
     * @param array
    **/
    public function extendApplyLoan($order_id,$user_id){
        $infos = UserLoanOrder::getOrderRepaymentCard($order_id, $user_id);
        if(empty($infos) || !$infos){
            return [
                'code' => -1,
                'message' => '无资格申请借款展期',
            ];
        }
        $order = $infos['order'];
        $repayment = $infos['repayment'];// 用户放款详情
        $sub_order_type=$order['sub_order_type'];
        $money=$order['money_amount'];
        $loan_interests=$order['loan_interests'];
        $counter_fee=$order['counter_fee'];
        $is_extend_loan=$order['is_extend_loan'];
        $status=$order['status'];
        //实际付款金额
        $true_total_money=$repayment['true_total_money'];
        //借款本金(25%)
        $principal=$repayment['principal'];
        //逾期的订单将不能展期借款（2018-9-11考虑逾期用户也可以展期；但展期的条件，还款金额=本金*0.25+逾期费+利息）
        //对于新客，服务费调成30%，还款金额=服务费+逾期费+利息
        $repayment_interests=$repayment['interests'];
        $repayment_late_fee=$repayment['late_fee'];
        $mycounter_fee=$counter_fee;
        if($mycounter_fee<$principal*ZHANQI_LOAN_LV){
            $mycounter_fee=$principal*ZHANQI_LOAN_LV;
        }
        //逾期7天以上不能展期
        $overdue_day=$repayment['overdue_day'];
        if($true_total_money!=intval($mycounter_fee+$repayment_interests+$repayment_late_fee) || $is_extend_loan=UserLoanOrder::IS_NOT_EXTEND_LOAN || $status==UserLoanOrder::STATUS_REPAY_COMPLETE || $overdue_day > 7){
            return [
                'code' => -1,
                'message' => '无资格申请借款展期',
            ];
        }

        $userService = Yii::$container->get('userService');
        if ($tip = $userService->getCanNotLoanMsgTip($user_id)) {
            return [
                'code' => -1,
                'message' => $tip,
            ];
        }

        $error_categroy='extendApplyLoan';
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            //第一步：创建借款订单
            $user_loan_order = new UserLoanOrder();

            $user_loan_order->user_id = $user_id;
            $user_loan_order->order_type = UserLoanOrder::LOAN_TYPE_LQD;
            $user_loan_order->money_amount = $money;
            $user_loan_order->apr = $order['pocket_apr'];
            $user_loan_order->loan_method = BaseUserCreditTotalChannel::LOAN_TYPE_DAY;
            $user_loan_order->loan_term = $order['loan_term'];
            $user_loan_order->loan_interests = $order['loan_interests'];
            $user_loan_order->operator_name= "auto";
            $user_loan_order->remark = "";
            $user_loan_order->sub_order_type = $order['sub_order_type'];//零钱包的子类型(手机信用卡app)
            $user_loan_order->from_app = $order['from_app'];//来源的app
            $user_loan_order->late_fee_apr = $order['late_fee_apr'];
            $user_loan_order->order_time = time();
            $user_loan_order->created_at = time();
            $user_loan_order->updated_at = time();
            $user_loan_order->status = UserLoanOrder::STATUS_LOAN_COMPLETE;;
            $user_loan_order->card_id = $order['card_id'];
            $user_loan_order->counter_fee = $order['counter_fee'];
            $user_loan_order->coupon_id = $order['coupon_id'];
            $user_loan_order->card_type = $order['card_type'];//白卡/金卡
            $user_loan_order->loan_time = time();
            $user_loan_order->is_first = UserLoanOrder::FIRST_LOAN_NO;
            $user_loan_order->reason_remark = UserLoanOrder::EXTENDREASONREMARK;
            $user_loan_order->trail_time = time();
            $user_loan_order->auto_risk_check_status = $order['auto_risk_check_status'];
            $user_loan_order->is_hit_risk_rule = $order['is_hit_risk_rule'];
            $user_loan_order->tree = $order['tree'];
            $user_loan_order->fund_id = $order['fund_id'];
            $user_loan_order->apr = $order['apr'];

            //第二步：判断是否是首单 重置为非新手
            $user_verification = UserVerification::findOne(['user_id'=>$user_loan_order->user_id]);
            if($user_verification){
                //判断是否为首次借款
                if($user_verification->is_first_loan==UserVerification::IS_FIRST_LOAN_NEW){
                    $user_verification->is_first_loan = UserVerification::IS_FIRST_LOAN_NO;
                    $user_verification->updated_at = time();
                    if(!$user_verification->save()){
                        $transaction->rollBack();
                        return [
                            'code'=>-1,
                            'message'=>'更新是否新手数据失败',
                        ];
                    }
                }
            }else{
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'获取认证表数据失败',
                ];
            }

            if (!$user_loan_order->save()) {
                $error = "申请借款展期订单 保存UserLoanOrder失败：" . var_export($user_loan_order->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败,请稍后再试',
                ];
            }
            //新生成的订单号
            $new_order_id = $user_loan_order->attributes['id'];
            $loan_time = time();

            //第三步：零钱贷插入分期总表
            $user_loan_order_repayment = new UserLoanOrderRepayment();
            if(!$user_loan_order_repayment){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'零钱贷分期表创建失败',
                ];
            }
            $user_loan_order_repayment->user_id=$user_loan_order->user_id;
            $user_loan_order_repayment->order_id = $new_order_id;
            $user_loan_order_repayment->principal=$user_loan_order->money_amount;
            //利息
            $user_loan_order_repayment->interests=$user_loan_order->loan_interests;
            $user_loan_order_repayment->interest_day = 1;//借款当天算一天利息
            $user_loan_order_repayment->interest_time = strtotime(date('Y-m-d',$loan_time));//利息计算到当天
            $user_loan_order_repayment->late_fee=0;
            $user_loan_order_repayment->plan_repayment_time= $loan_time+($user_loan_order->loan_term-1)*24*3600;
            $user_loan_order_repayment->plan_fee_time= $user_loan_order_repayment->plan_repayment_time+86400;
            $user_loan_order_repayment->operator_name="auto";
            $user_loan_order_repayment->status=UserLoanOrderRepayment::STATUS_NORAML;
            $user_loan_order_repayment->created_at=time();
            $user_loan_order_repayment->updated_at=time();
            $user_loan_order_repayment->total_money=$user_loan_order_repayment->principal+$user_loan_order_repayment->interests;
            $user_loan_order_repayment->true_total_money = 0;
            $user_loan_order_repayment->card_id=$user_loan_order->card_id;
            $user_loan_order_repayment->loan_time=$loan_time;
            $user_loan_order_repayment->apr=$user_loan_order->apr;
            if(!$user_loan_order_repayment->save()){
                $error = "申请借款展期订单 保存UserLoanOrderRepayment失败：" . var_export($user_loan_order_repayment->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'插入零钱贷分期表失败',
                ];
            }

            //第四步：修改额度表中的锁定金额
            $creditChannelService = \Yii::$app->creditChannelService;
            $quota = $creditChannelService->getCreditTotalByUserId($user_id);
            $quota->used_amount = $money;
            $quota->updated_at = time();
            if (!$quota->save()) {
                $error = "申请借款展期订单 保存UserCreditTotal失败：" . var_export($quota->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败(修改锁定额度失败),请稍后再试',
                ];
            }

            //第五步：资金流水
            $user_credit_log = new  UserCreditLog();

            $user_credit_log->user_id = $user_id;
            $user_credit_log->type = UserCreditLog::TRADE_TYPE_LQD_LOAN_LOCK;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $money;
            $user_credit_log->apr = $quota->pocket_apr;
            $user_credit_log->interests = 0;
            $user_credit_log->to_card = "";
            $user_credit_log->remark = "";
            $user_credit_log->created_at = time();
            $user_credit_log->created_ip = Util::getUserIP();
            $user_credit_log->total_money = $quota->amount;
            $user_credit_log->used_money = $quota->used_amount;
            $user_credit_log->unabled_money = $quota->locked_amount;
            if (!$user_credit_log->save()) {
                $error = "申请借款展期订单 保存UserCreditLog失败：" . var_export($user_credit_log->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '生成借款订单失败(保存流水日志失败),请稍后再试',
                ];
            }

            //第六步：修改展期订单还款表
            $user_loan_order_repayment = UserLoanOrderRepayment::findOne(['user_id'=>$user_id,'order_id'=>$order_id,'id'=>$repayment['id']]);
            if (false == $user_loan_order_repayment) {
                $error = "获取零钱贷分期总表数据失败";
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '获取零钱贷分期总表数据失败',
                ];
            }
            $user_loan_order_repayment->updated_at = time();
            $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
            $user_loan_order_repayment->true_repayment_time = time();
            //对于展期订单，实际还款金额等于应还金额
            $user_loan_order_repayment->true_total_money=$user_loan_order_repayment->total_money;
            //对于展期订单，原来订单当前扣款金额为0
            $user_loan_order_repayment->current_debit_money=0;
            if (!$user_loan_order_repayment->save()) {
                $error = "申请借款展期订单 修改UserLoanOrderRepayment失败：" . var_export($user_loan_order_repayment->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '操作零钱贷分期还款表失败',
                ];
            }

            //第7步：修改展期订单表
            $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
            $user_loan_order->updated_at = time();
            $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
            if(!$user_loan_order->save()){
                $error = "申请借款展期订单 修改UserLoanOrderRepayment失败：" . var_export($user_loan_order->getErrors());
                Yii::error($error,$error_categroy);
                MessageHelper::sendSMS(NOTICE_MOBILE,$error);
                $transaction->rollBack();
                return [
                    'code' => -1,
                    'message' => '操作零钱贷订单表还款表失败',
                ];
            }

            $transaction->commit();

            //借款展期考虑到借款提额（20180810）
            $creditChannelService = new UserCreditChannelService();
            $increase_credit = $creditChannelService->increaseUserCreditAccountNew($user_loan_order_repayment);

            //借款展期成功，通知用户
            if (YII_ENV_PROD) {
                $loan_person = LoanPerson::findOne(['id'=>$user_id]);
                //第8步：将新用户标记为老用户
                if($loan_person){
                    $phone = $loan_person->phone;
                    if($loan_person->customer_type==LoanPerson::CUSTOMER_TYPE_NEW){
                        $loan_person->customer_type=LoanPerson::CUSTOMER_TYPE_OLD;
                        $loan_person->save();
                    }
                    $send_message = "您有一笔".sprintf("%0.2f", $money / 100)."元借款已展期成功，请注意查看。";
                    MessageHelper::sendSMS($phone, $send_message, 'smsService_TianChang_HY',$loan_person->source_id);
                }
            }
        }
        catch(\Exception $e){
            $error = '借款展期异常：'.$e->getTraceAsString();
            Yii::error($error,$error_categroy);
            MessageHelper::sendSMS(NOTICE_MOBILE,$error);
            $transaction->rollBack();
            return [
                'code'=>$e->getCode(),
                'message'=>$error,
            ];
        }

        //记录日志中
        $error="用户{$user_id}申请借款展期成功，展期借款订单号：{$order_id}，新生成的借款订单号：{$new_order_id}";
        Yii::error($error,$error_categroy);
//        MessageHelper::sendSMS(NOTICE_MOBILE,$error);
        return [
            'code' => 0,
            'message' => '申请展期借款成功'
        ];
    }
}
