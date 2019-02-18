<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/8/7
 * Time: 上午9:41
 */

namespace common\services;


use common\models\UserCreditMoneyLog;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\AutoDebitLog;
use common\helpers\MessageHelper;
use common\models\FinancialDebitRecord;
use common\models\SuspectDebitLostRecord;
use common\models\UserLoanOrderRepayment;


class AutoDebitService extends Component
{
    const TYPE_SHELL = 1; //脚本操作
    const TYPE_STAFF = 2; //手动操作

    //处理未回调的订单
    public function handleUnCallBackDebitRecord($uuid,$params=array())
    {
        $isForceFailed = isset($params['isForceFailed'])?$params['isForceFailed']:false;
        $autoDebitRecord = AutoDebitLog::findOne(['order_uuid' =>$uuid]);
        $financialDebitRecord = FinancialDebitRecord::findOne(['order_id'=>$uuid]);
        if (!$financialDebitRecord) throw new Exception("financialDebitRecord 记录不存在!");
        if (!$autoDebitRecord) throw new Exception("自动扣款日志不存在!");
        $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['user_id'=>$autoDebitRecord->user_id,'order_id'=>$autoDebitRecord->order_id,'order_uuid' => $autoDebitRecord->order_uuid]);
        if ((time() - $autoDebitRecord->created_at) > 900) {
            if (!$suspectDebitLostRecord) {
                $suspectDebitLostRecord = new SuspectDebitLostRecord();
                $suspectDebitLostRecord -> user_id = $autoDebitRecord->user_id;
                $suspectDebitLostRecord -> order_id = $autoDebitRecord->order_id;
                $suspectDebitLostRecord -> order_uuid = $autoDebitRecord->order_uuid;
                $suspectDebitLostRecord -> pay_order_id = $autoDebitRecord->pay_order_id;
                $suspectDebitLostRecord -> debit_record_id = $financialDebitRecord->id;
                $suspectDebitLostRecord -> platform = $autoDebitRecord->platform;
                $suspectDebitLostRecord -> card_id = $autoDebitRecord->card_id;
                $suspectDebitLostRecord -> money = $autoDebitRecord->money;
                $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM;
                $suspectDebitLostRecord -> operator = (isset($params['operator']) ? $params['operator'] : 'console shell').'<br/>';
                $suspectDebitLostRecord -> remark = date("YmdHis").'超过10分钟未回调<br/>';
                $suspectDebitLostRecord -> save();
            }
        }
        $timeDiff = time() - $autoDebitRecord->created_at;
        if ($timeDiff > 7200 || $isForceFailed) //如果超过两个小时直接置为失败
        {
            $ret = [
                'code' => 0,
                'data' => [
                    'state' => 3,
                    'err_code' => '400002',
                    'err_msg' => '订单长时间未回调,手动置为失败',
                    'pay_date' => date('Ymd'),
                    'money' => $autoDebitRecord -> money,
                    'third_platform' => $autoDebitRecord->platform,
                    'pay_order_id' => $autoDebitRecord -> pay_order_id
                ]
            ];
            if (!$suspectDebitLostRecord) throw new Exception("超过2个小时,未回调,但suspectDebitLostRecord无记录,uuid为:".$uuid);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if(!FinancialDebitRecord::addCallBackDebitLock($financialDebitRecord->order_id)) {
                    $conflictMsg = '系统代扣 order_uuid:'.$financialDebitRecord->order_id.' 查询与主动回调相冲突!';
                    MessageHelper::sendSMS(NOTICE_MOBILE2, $conflictMsg);
                }
                //第一步 更新日志列表
                $autoDebitRecord -> remark = $autoDebitRecord -> remark.' ** '.date("Ymd").'手动更新设置失败!';
                $autoDebitRecord -> status = AutoDebitLog::STATUS_FAILED;
                $autoDebitRecord -> callback_at = time();
                $autoDebitRecord -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                $autoDebitRecord -> pay_order_id = isset($ret['data']['pay_order_id']) ? $ret['data']['pay_order_id'] : $autoDebitRecord->pay_order_id;
                $autoDebitRecord -> error_code = isset($ret['data']['err_code']) ? $ret['data']['err_code'] : $autoDebitRecord->error_code;
                if (!$autoDebitRecord -> save()) {
                    throw new Exception("autoDebitRecord 记录保存失败,uuid".$uuid);
                }
                //第二步 更新suspectDebitLostRecord 记录
                $suspectDebitLostRecord -> status = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::STATUS_FAILED_SHELL : SuspectDebitLostRecord::STATUS_FAILED_STAFF; //SuspectDebitLostRecord::STATUS_DEFAULT;
                $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM;
                $suspectDebitLostRecord -> mark_type = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::MARK_TYPE_SYSTEM : SuspectDebitLostRecord::MARK_TYPE_STAFF;
                $suspectDebitLostRecord -> remark .= (isset($params['remark'])?$params['remark']:'').'<br/>';
                $suspectDebitLostRecord -> operator .= ($params['type'] == self::TYPE_SHELL ? 'console <br/>' : Yii::$app->user->identity->username.'<br/>');
                $suspectDebitLostRecord -> updated_at = time();
                if (!$suspectDebitLostRecord -> save()) {
                    throw new Exception("SuspectDebitLostRecord 记录保存失败,uuid:".$uuid);
                }
                //第三步 更新扣款记录表
                $financialDebitRecord -> platform =  $autoDebitRecord->platform;
                $financialDebitRecord -> status = FinancialDebitRecord::STATUS_FALSE;
                $financialDebitRecord -> updated_at = time();
                if (!($financialDebitRecord_ret = $financialDebitRecord->save()))
                {
                    throw new Exception("FinancialDebitRecord 更新失败");
                }
                //第四步 更新两表订单表与还款表状态
                $alterStatus = UserLoanOrderRepayment::alterOrderStatus($financialDebitRecord->loan_record_id, $financialDebitRecord->repayment_id);
                FinancialDebitRecord::clearDebitLock($financialDebitRecord->loan_record_id);
                FinancialDebitRecord::clearCallBackDebitLock($financialDebitRecord->order_id);
                $transaction -> commit();
            } catch (Exception $ex) {
                $transaction->rollback();
                throw new Exception($ex->getMessage(),$ex->getCode());
            }
        }
        return true;
    }

    //处理主动还款长时间未回调的订单
    public function handleUnPayedResRecord($uuid,$params=array()) {
        $isForceFailed = isset($params['isForceFailed'])?$params['isForceFailed']:false;
        $userCreditMoneyLog = UserCreditMoneyLog::findOne(['order_uuid' => $uuid]);
        if (!$userCreditMoneyLog) {
            throw new Exception("还款日志列表记录不存在,uuid:".$uuid);
        }
        $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['user_id'=>$userCreditMoneyLog->user_id,'order_id'=>$userCreditMoneyLog->order_id,'order_uuid' => $userCreditMoneyLog->order_uuid]);
        if ((time() - $userCreditMoneyLog->created_at) > 900 ) {
            if (!$suspectDebitLostRecord) {
                $suspectDebitLostRecord = new SuspectDebitLostRecord();
                $suspectDebitLostRecord -> user_id = $userCreditMoneyLog -> user_id;
                $suspectDebitLostRecord -> order_id = $userCreditMoneyLog -> order_id;
                $suspectDebitLostRecord -> order_uuid = $userCreditMoneyLog -> order_uuid;
                $suspectDebitLostRecord -> pay_order_id = $userCreditMoneyLog -> pay_order_id;
                $suspectDebitLostRecord -> debit_record_id = $userCreditMoneyLog -> id;
                $suspectDebitLostRecord -> platform = $userCreditMoneyLog -> debit_channel;
                $suspectDebitLostRecord -> card_id = $userCreditMoneyLog -> card_id;
                $suspectDebitLostRecord -> money = $userCreditMoneyLog -> operator_money;
                $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                $suspectDebitLostRecord -> operator = (isset($params['operator']) ? $params['operator'] : 'console shell').'<br/>';
                $suspectDebitLostRecord -> remark = date("YmdHis").'主动还款10分钟未回调<br/>';
                $suspectDebitLostRecord -> save();
            }
        }
        $timeDiff = time() - $userCreditMoneyLog->created_at;
        if ($timeDiff > 7200 || $isForceFailed) { //如果超过两个小时直接置为失败
            //手动设置回调参数
            $ret = [
                'code' => 0,
                'data' => [
                    'state' => 3,
                    'err_code' => '400002',
                    'err_msg' => '订单长时间未回调,手动置为失败',
                    'pay_date' => date('Ymd'),
                    'money' => $userCreditMoneyLog -> operator_money,
                    'third_platform' => $userCreditMoneyLog -> debit_channel,
                    'pay_order_id' => $userCreditMoneyLog -> pay_order_id
                ]
            ];
            if (!$suspectDebitLostRecord) throw new Exception("超过2个小时,未回调,但suspectDebitLostRecord无记录,uuid为:".$uuid);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if (!UserCreditMoneyLog::addMutexDebitLock($userCreditMoneyLog -> order_uuid)) {
                    $conflictMsg = '主动还款失败设置 order_uuid:'.$userCreditMoneyLog -> order_uuid.' 查询与主动还款回调相冲突!';
                    MessageHelper::sendSMS(NOTICE_MOBILE2, $conflictMsg);
                }
                $alterStatus = UserLoanOrderRepayment::alterOrderStatus($userCreditMoneyLog->order_id);
                //第一步 更新还款日志列表
                $userCreditMoneyLog -> status = UserCreditMoneyLog::STATUS_FAILED;
                $userCreditMoneyLog -> pay_order_id = $ret['data']['pay_order_id'];
                $userCreditMoneyLog -> remark = $ret['data']['pay_date'].'还款失败';
                if (!$userCreditMoneyLog -> save()) {
                    throw new Exception('还款日志列表修改保存失败!');
                }
                //第二步 更新扣款列表
                $suspectDebitLostRecord -> status = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::STATUS_FAILED_SHELL : SuspectDebitLostRecord::STATUS_FAILED_STAFF; //SuspectDebitLostRecord::STATUS_DEFAULT;
                $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                $suspectDebitLostRecord -> mark_type = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::MARK_TYPE_SYSTEM : SuspectDebitLostRecord::MARK_TYPE_STAFF;
                $suspectDebitLostRecord -> remark .= (isset($params['remark'])?$params['remark']:'').'<br/>';
                $suspectDebitLostRecord -> operator .= ($params['type'] == self::TYPE_SHELL ? 'actionApplyDebitStatus <br/>' : Yii::$app->user->identity->username.'<br/>');
                $suspectDebitLostRecord -> updated_at = time();
                if (!$suspectDebitLostRecord -> save()) {
                    throw new Exception("SuspectDebitLostRecord 记录保存失败,uuid:".$uuid);
                }
                UserCreditMoneyLog::clearMutexDebitLock($userCreditMoneyLog->order_uuid);
                FinancialDebitRecord::clearDebitLock($userCreditMoneyLog->order_id, $userCreditMoneyLog->user_id);
                $transaction->commit();
            } catch (Exception $ex) {
                $transaction->rollback();
                throw new Exception($ex->getMessage(),$ex->getCode());
            }
        }
        return true;
    }

    //处理扣款未回调的订单
    public function handleDebitingOrder($uuid,$params=array()) {
        $isForceFailed = isset($params['isForceFailed'])?$params['isForceFailed']:false;
        $autoDebitRecord = AutoDebitLog::findOne(['order_uuid' =>$uuid]);
        if (!$autoDebitRecord) throw new Exception("自动扣款日志不存在!");
        $userLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id' =>$autoDebitRecord->order_id,'user_id' => $autoDebitRecord->user_id]);
        if (!$userLoanOrderRepayment) {
            throw new Exception("还款表不存在");
        }
        $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['user_id'=>$autoDebitRecord->user_id,'order_id'=>$autoDebitRecord->order_id,'order_uuid' => $autoDebitRecord->order_uuid]);
        if ((time() - $autoDebitRecord->created_at) > 900) {
            if (!$suspectDebitLostRecord) {
                $suspectDebitLostRecord = new SuspectDebitLostRecord();
                $suspectDebitLostRecord -> user_id = $autoDebitRecord->user_id;
                $suspectDebitLostRecord -> order_id = $autoDebitRecord->order_id;
                $suspectDebitLostRecord -> order_uuid = $autoDebitRecord->order_uuid;
                $suspectDebitLostRecord -> pay_order_id = $autoDebitRecord->pay_order_id;
                $suspectDebitLostRecord -> debit_record_id = $autoDebitRecord->id;
                $suspectDebitLostRecord -> platform = $autoDebitRecord->platform;
                $suspectDebitLostRecord -> card_id = $autoDebitRecord->card_id;
                $suspectDebitLostRecord -> money = $autoDebitRecord->money;
                $suspectDebitLostRecord -> debit_type = (strlen($uuid) > 14)?SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM:SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                $suspectDebitLostRecord -> operator = (isset($params['operator']) ? $params['operator'] : 'console shell').'<br/>';
                $suspectDebitLostRecord -> remark = date("YmdHis").'超过10分钟未回调<br/>';
                $suspectDebitLostRecord -> save();
            }
        }
        $timeDiff = time() - $autoDebitRecord->created_at;
        if ($timeDiff > 7200 || $isForceFailed) //如果超过两个小时直接置为失败
        {
            $ret = [
                'code' => 0,
                'data' => [
                    'state' => 3,
                    'err_code' => '400002',
                    'err_msg' => '订单未回调,查询脚本置为失败',
                    'pay_date' => date('Ymd'),
                    'money' => $autoDebitRecord -> money,
                    'third_platform' => $autoDebitRecord->platform,
                    'pay_order_id' => $autoDebitRecord -> pay_order_id
                ]
            ];
            if (!$suspectDebitLostRecord) throw new Exception("超过2个小时,未回调,但suspectDebitLostRecord无记录,uuid为:".$uuid);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if(!FinancialDebitRecord::addCallBackDebitLock($uuid)) {
                    $conflictMsg = '系统代扣 order_uuid:'.$uuid.' 查询与主动回调相冲突!';
                    MessageHelper::sendSMS(NOTICE_MOBILE2, $conflictMsg);
                }
                //第一步 更新日志列表
                $autoDebitRecord -> remark = $autoDebitRecord -> remark.' ** '.date("Ymd").'手动更新设置失败!';
                $autoDebitRecord -> status = AutoDebitLog::STATUS_FAILED;
                $autoDebitRecord -> callback_at = time();
                $autoDebitRecord -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                $autoDebitRecord -> pay_order_id = isset($ret['data']['pay_order_id']) ? $ret['data']['pay_order_id'] : $autoDebitRecord->pay_order_id;
                $autoDebitRecord -> error_code = isset($ret['data']['err_code']) ? $ret['data']['err_code'] : $autoDebitRecord->error_code;
                if (!$autoDebitRecord -> save()) {
                    throw new Exception("autoDebitRecord 记录保存失败,uuid".$uuid);
                }
                //第二步 更新suspectDebitLostRecord 记录
                $suspectDebitLostRecord -> status = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::STATUS_FAILED_SHELL : SuspectDebitLostRecord::STATUS_FAILED_STAFF; //SuspectDebitLostRecord::STATUS_DEFAULT;
                $suspectDebitLostRecord -> debit_type = (strlen($uuid)>14) ? SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM : SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                $suspectDebitLostRecord -> mark_type = $params['type'] == self::TYPE_SHELL ? SuspectDebitLostRecord::MARK_TYPE_SYSTEM : SuspectDebitLostRecord::MARK_TYPE_STAFF;
                $suspectDebitLostRecord -> remark .= (isset($params['remark'])?$params['remark']:'').'<br/>';
                $suspectDebitLostRecord -> operator .= ($params['type'] == self::TYPE_SHELL ? 'console <br/>' : Yii::$app->user->identity->username.'<br/>');
                $suspectDebitLostRecord -> updated_at = time();
                if (!$suspectDebitLostRecord -> save()) {
                    throw new Exception("SuspectDebitLostRecord 记录保存失败,uuid:".$uuid);
                }
                //第三步 更新扣款记录表
                $financialDebitRecord = FinancialDebitRecord::findOne(['order_id'=>$uuid]);
                if ($financialDebitRecord) {
                    $financialDebitRecord -> platform =  $autoDebitRecord->platform;
                    $financialDebitRecord -> status = FinancialDebitRecord::STATUS_FALSE;
                    $financialDebitRecord -> updated_at = time();
                    if (!($financialDebitRecord->save())) {
                        throw new Exception("FinancialDebitRecord 更新失败");
                    }
                }
                //第四步 更新两表订单表与还款表状态
                $alterStatus = UserLoanOrderRepayment::alterOrderStatus($autoDebitRecord->order_id, $userLoanOrderRepayment->id);
                FinancialDebitRecord::clearDebitLock($autoDebitRecord->order_id);
                FinancialDebitRecord::clearCallBackDebitLock($autoDebitRecord->order_uuid);
                $transaction -> commit();
            } catch (Exception $ex) {
                $transaction->rollback();
                throw new Exception($ex->getMessage(),$ex->getCode());
            }
        }
        return true;
    }
}