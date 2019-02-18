<?php
/**
 * 扣款
 */

namespace backend\controllers;

use Yii;
use common\models\User;
use common\models\UserBankCard;
use common\services\BankCardService;
use common\services\PayService;
use common\services\ChargeService;
use common\models\BankConfig;
use common\helpers\Url;
use common\models\UserLog;
use common\models\UserAccount;
use common\models\LoanRepaymentPeriod;
use common\helpers\StringHelper;
use common\models\LoanRepayment;
use common\models\LoanRecordPeriod;
use common\services\YeePayService;

class DebitController extends BaseController
{

    public function actionAddDebit() {

        UserLog::begin(UserLog::LogTypeDeductMoney);

        $period_id = intval(\Yii::$app->request->get("id"));
        $data = $this->_get_period_info($period_id);

        if (\Yii::$app->request->isPost) {

            $account = \Yii::$app->request->post("account");
            $amount  = \Yii::$app->request->post("amount");
            $id_card = \Yii::$app->request->post("id_card");
            $bank_id = \Yii::$app->request->post("bank_id");
            $card_no = \Yii::$app->request->post("card_no");
            $stay_phone = \Yii::$app->request->post("stay_phone");
            $amount = intval(bcmul($amount, '100', 0));
            $platform = BankConfig::PLATFORM_UMPAY;

            if(!in_array($bank_id, [1, 2, 7, 3, 5])) {//非联动支持的银行
                $platform = BankConfig::PLATFORM_YEEPAY;
            }

            $user = User::find()->where(['phone' => $account])->one(\Yii::$app->db_read);
            $uid = $user['id'];
            $stay_phone = empty($stay_phone) ? $user['phone'] : $stay_phone;

            UserLog::setUser($user);

            if($id_card != $user['id_card']) {
                return $this->redirectMessage('身份证不一致', self::MSG_ERROR);
            }

            if($bank_id == -1) {//从用户账户余额抵扣

                $user_account = UserAccount::find()->where(['user_id' => $uid])->one(\Yii::$app->db_read);
                $usable_money = $user_account['usable_money'];

                if($usable_money < $amount) {
                    return $this->redirectMessage('用户账户余额不足', self::MSG_ERROR);
                }

                $charge_service = new ChargeService();
                $charge_service->deductMoney($uid, $amount, 0);

                //扣款成功，更新分期记录
                if($period_id > 0) {
                    $this->_update_period($period_id, $amount, 0, 0);
                }

                return $this->redirectMessage('扣款成功[从余额抵扣]', self::MSG_SUCCESS);
            }

            $bank_card = UserBankCard::getBankCardByNo($uid, $card_no);
            $card_type = UserBankCard::CARD_TYPE_CP;

            if(empty($bank_card) || $bank_card['status'] == 0) { //未绑定

                try {
                    $bank_card_service = new BankCardService();
                    $bank_card = $bank_card_service->directBindCard($uid, $bank_id, $card_no, $platform, $card_type, $stay_phone);
                } catch (\Exception $e) {

                    $bank_card = UserBankCard::getBankCardByNo($uid, $card_no);
                    $this->_update_period($period_id, 0, 0, $bank_card['id']);

                    $ret['code'] = $e->getCode();
                    $ret['message'] = $e->getMessage();

                    if($ret['code'] != 0) {
                        return $this->redirectMessage('绑卡失败:' . $ret['message'], self::MSG_ERROR);
                    }
                }

                UserLog::addLogDetail("用户绑卡成功", ['platform' => $platform, 'card_no' => $card_no, 'bank_id' => $bank_id]);
            }
            else {//已绑定，去绑定第三方

                $ret = [];
                if($platform == BankConfig::PLATFORM_YEEPAY) {
                    try {
                        $yeepay = new YeePayService($user, UserBankCard::CARD_TYPE_CP);
                        $bind_rs = $yeepay->bindBankcardApply($card_no);
                        if($bind_rs === true) {
                            $yeepay->bindBankcardConfirm();

                            $ret['code'] = 0;
                        }
                        else {
                            if($bind_rs['code'] == '600326') {
                                $ret['code'] = 0;
                            }
                            else {
                                \Yii::info("易宝绑卡异常:" . $bind_rs['msg'] . '#' . $bind_rs['code'], 'koudai.pay.*');

                                $ret['message'] = $bind_rs['msg'] . "#" . $bind_rs['code'];
                                $ret['code'] = $bind_rs['code'];
                            }
                        }
                    }
                    catch (\Exception $e) {

                        $ret['code'] = $e->getCode();
                        $ret['message'] = $e->getMessage();
                    }
                }
                else if($platform == BankConfig::PLATFORM_UMPAY) {
                    $pay_service = new PayService();
                    $ret = $pay_service->directBindCard($user['realname'], $user['id_card'], $card_no, $user['phone']);
                }

                if($ret['code'] != 0) {
                    return $this->redirectMessage('绑卡失败:' . $ret['message'], self::MSG_ERROR);
                }
            }

            //绑卡成功，更新扣款卡
            $this->_update_period($period_id, 0, 0, $bank_card['id']);

            if($amount > 0) {
                $bind_id = $bank_card['id'];
                $bind_phone = $bank_card['bind_phone'];

                $charge_service = new ChargeService();
                $ret = $charge_service->directCharge($uid, $amount, $bind_id, $platform, $card_no, $account);

                UserLog::addLogDetail("后台代扣，充值结果", $ret);

                if($ret['state'] === 0) {

                    $order_id = $ret['order_id'];
                    $charge_service->deductMoney($uid, $amount, $bind_id);

                    //扣款成功，更新分期记录
                    if($period_id > 0) {
                        $this->_update_period($period_id, $amount, $order_id, $bind_id);
                    }

                    return $this->redirectMessage("扣款成功[订单号:$order_id]", self::MSG_SUCCESS, Url::toRoute('debit/add-debit'));
                }
                else {

                    return $this->redirectMessage('扣款失败:' . $ret['msg'] . "#" . $ret['order_id'], self::MSG_ERROR);
                }
            }
        }

        return $this->render('add-debit', ['period_data' => $data]);
    }

    private function _get_period_info($period_id) {

        $data = [];

        if($period_id > 0) {//分期扣款ID

            $loan_repayment = LoanRepaymentPeriod::find()->where(['id' => $period_id])->one(\Yii::$app->db_financial);
            $user_id = $loan_repayment['user_id'];

            $loan_record_period = LoanRecordPeriod::findOne($loan_repayment['loan_record_id']);
            $debit_card_id = $loan_record_period['debit_card_id'];

            $user = User::find()->where(['id' => $user_id])->one(\Yii::$app->db_read);
            $data['period_id'] = $period_id;
            $data['amount'] = StringHelper::safeConvertIntToCent($loan_repayment['plan_repayment_money']);
            $data['account'] = $user['phone'];
            $data['id_card'] = $user['id_card'];

            if(!empty($debit_card_id)) {
                $bank_card = UserBankCard::findOne(['id' => $debit_card_id]);

                $data['card_no'] = $bank_card['card_no'];
                $data['bank_id'] = $bank_card['bank_id'];
                $data['stay_phone'] = $bank_card['bind_phone'];
            }
            else {
                $data['card_no'] = '';
                $data['bank_id'] = '';
                $data['stay_phone'] = $user['phone'];
            }
        }
        else {
            $data['period_id'] = '';
            $data['amount'] = '';
            $data['account'] = '';
            $data['id_card'] = '';
            $data['card_no'] = '';
            $data['bank_id'] = '0';
            $data['stay_phone'] = '';
        }

        return $data;
    }


    private function _update_period($period_id, $amount, $order_id, $bind_id) {

        $repayment_period = LoanRepaymentPeriod::findOne($period_id);
        if(empty($repayment_period)) {
            return false;
        }

        if($amount > 0) {
            //更新分期还款
            $repayment_period->true_repayment_money = $amount;
            $repayment_period->true_repayment_time = time();
            $repayment_period->status = $amount < $repayment_period->plan_repayment_money ? LoanRepaymentPeriod::STATUS_REPAY_PART : LoanRepaymentPeriod::STATUS_REPAYED;
            $repayment_period->remark = "手动还款";
            $repayment_period->order_id = $order_id;
            $repayment_period->admin_username = Yii::$app->user->identity->username;
            if(!$repayment_period->save()) {
                \Yii::info("分期还款更新失败" . var_export(['id' => $period_id, 'amount' => $amount], true), 'koudai.pay.*');
            }

            $loan_repayment_info = LoanRepayment::findOne($repayment_period->repayment_id);
            //更新总还款
            $loan_repayment_info->repaymented_amount += $amount;
            if($repayment_period->plan_repayment_time == $repayment_period->plan_next_repayment_time) {
                $loan_repayment_info->status = $repayment_period->status;
            } else {
                $loan_repayment_info->status = LoanRepaymentPeriod::STATUS_REPAYING;
            }
            if(!$loan_repayment_info->save()) {
                \Yii::info("更新总还款信息失败" . var_export(['id' => $period_id, 'amount' => $amount], true), 'koudai.pay.*');
            }
        }

        //更新分期银行卡
        $loan_record_period = LoanRecordPeriod::findOne($repayment_period['loan_record_id']);
        $debit_card_id = $loan_record_period['debit_card_id'];
        if(empty($debit_card_id)) {
            $loan_record_period->debit_card_id = $bind_id;
            if(!$loan_record_period->save()) {
                \Yii::info("更新还款银行卡", 'koudai.pay.*');
            }
        }
    }
}