<?php

namespace common\services;

use Yii;
use yii\base\Component;
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use common\models\fund\LoanFund;
use common\models\fund\LoanFundDayQuota;
use common\models\fund\OrderFundInfo;
use common\models\fund\OrderFundLog;
use common\helpers\MessageHelper;
use yii\helpers\Url;
use common\models\fund\LoanFundSignUser;
use common\api\RedisMQ;
use common\api\RedisQueue;
use common\helpers\Lock;
use common\base\ErrCode;
use common\models\BankConfig;

/**
 * 资金方服务
 */
class FundService extends Component
{

    /**
     * 订单自动分发给资金方
     * @param UserLoanOrder $order 订单模型
     * @param [] $update_order_attributes 更新的订单属性
     * @param UserOrderLoanCheckLog $log 检查日志记录
     * @param string $operator 操作人 用户ID 或 riskShell
     * @param [] $params 请求参数
     * @return []
     */
    public function orderAutoDispatch($order, $update_order_attributes, $log, $operator, $params=[]) {
        $funds =  LoanFund::canUserLoan(LoanFund::STATUS_ENABLE);
        if(!$funds) {
            return [
                'code'=> ErrCode::NOT_AVAILABLE_FUND,
                'message'=>'无可用的资金渠道'
            ];
        }

        $lock_name = 'orderChangeFund'.$order->id;
        if(!($lock=Lock::get($lock_name, 30))) {
            return [
                'code'=> ErrCode::ORDER_LOCK,
                'message'=>'订单正在锁定中'
            ];
        }

        /* @var $funds LoanFund */
        $useFund = null;
        $sort_funds = $funds;//LoanFund::randSortFunds($funds);

        $ret_code=0;
        foreach($sort_funds as $fund) {
            $ret = $this->orderSetFund($order, $fund, $update_order_attributes, $log, $operator, $params);
            $ret_code=$ret['code'];
            if($ret['code']==0) {//操作成功
                $useFund = $fund;

                break;
            } else {//其他错误
                Yii::error("订单 {$order->id}  资金渠道 {$fund->id} 不可用 ，结果：". json_encode($ret, JSON_UNESCAPED_UNICODE));
                continue;
            }
        }

        if($lock) {
            Lock::del($lock_name);
        }
        if(!$useFund) {
            if($ret_code==ErrCode::FUND_OVER_QUOTA){
                return [
                    'code'=>ErrCode::FUND_OVER_QUOTA,
                    'message'=>'今日放款金额已满'
                ];
            }
            return [
                'code'=>ErrCode::NOT_AVAILABLE_FUND,
                'message'=>'无适合的资金渠道'.json_encode($ret)
            ];
        }
        return [
            'code'=>0,
            'data'=>[
                'fund'=>$useFund
            ]
        ];
    }

    /**
     * 从多个资方中 自动切换
     * @param UserLoanOrder $order 订单模型
     * @param LoanFund[] $funds 切换资方模型数组
     * @param string $operator 操作人
     * @param [] 其他参数
     */
    public function orderAutoSwitchFund($order, $funds, $operator, $params=[]) {
        if(!$funds) {
            return [
                'code'=>ErrCode::NOT_AVAILABLE_FUND,
                'message'=>'无可用的资金渠道'
            ];
        }

        $can_change_ret = $order->loanFund->getService()->canChangeFund($order);
        if($can_change_ret['code']!=0) {
            return $can_change_ret;
        }

        $lock_name = 'orderChangeFund'.$order->id;
        if(!($lock=Lock::get($lock_name, 30))) {
            return [
                'code'=> ErrCode::ORDER_LOCK,
                'message'=>'订单正在锁定中'
            ];
        }

        /* @var $funds LoanFund */
        $useFund = null;
        foreach($funds as $fund) {
            $ret = $this->orderChangeFund($order, $fund, $operator, $params);
            if($ret['code']==0) {//操作成功
                $useFund = $fund;
                break;
            } else {//其他错误
                if(YII_ENV!=='prod') {
                    Yii::error("订单 {$order->id}  资金渠道 {$fund->id} 不可用，结果：". json_encode($ret, JSON_UNESCAPED_UNICODE));
                }
                continue;
            }
        }

        if($lock) {
            Lock::del($lock_name);
        }

        if(!$useFund) {
            return [
                'code'=>ErrCode::NOT_AVAILABLE_FUND,
                'message'=>'无可用的资金渠道'
            ];
        }
        return [
            'code'=>0,
            'data'=>[
                'fund'=>$useFund
            ]
        ];
    }

    /**
     * 指定订单使用指定的资方 只允许设置没有fund_id的订单
     * @param UserLoanOrder $order 订单模型
     * @param LoanFund $fund 资方模型、
     * @param [] $update_order_attributes 更新的订单属性 fd
     * @param UserOrderLoanCheckLog $log 插入日志记录
     * @param string $operator 用户ID 或 riskShell
     * @param [] $params 其他参数
     * @return LoanFund
     */
    public function orderSetFund($order, $fund, $update_order_attributes, $log, $operator, $params=[]) {
        $lock = null;
        $lock_name = UserLoanOrder::getChangeStatusLockName($order->id);
        if(!$order->card_id) {
            $ret = [
                'code'=>ErrCode::ORDER_CARD_NOT_BIND,
                'message'=>'订单未绑卡，不能分配资方 '
            ];
            goto RETURN_RET;
        }

        if(!$fund->supportBankId($order->cardInfo->bank_id)) {
            $ret = [
                'code'=>ErrCode::FUND_UNSUPPORT_BANK,
                'message'=>'该资方不支持银行 '.$order->cardInfo->bank_name
            ];
            goto RETURN_RET;
        } else if(!$fund->supportLoanTerm($order->loan_term)) {
            $ret = [
                'code'=>ErrCode::FUND_UNSUPPORT_TERM,
                'message'=>'该资方不支持订单借款期限 '
            ];
            goto RETURN_RET;
        }

        $fund_support_order_ret = $fund->getService()->supportOrder($order, $fund);

        if($fund_support_order_ret['code']!=0) {
            $ret = $fund_support_order_ret;
            goto RETURN_RET;
        }

        $date = date('Y-m-d');
        //$dayRemainingQuota = $fund->getTodayRemainingQouta();
        $dayRemainingQuota = $fund->getService()->getTodayRemainingQouta($fund,$order);
        if($dayRemainingQuota>=$order->money_amount) {
            if($lock = Lock::get($lock_name, 30)) {
                $old_row = UserLoanOrder::find()->where('`id`='.(int)$order->id)->one();
                if($old_row['fund_id']) {
                    $ret = [
                        'code'=> ErrCode::ORDER_HAS_FUND,
                        'message'=>'该订单已经绑定了资方'
                    ];
                    goto RETURN_RET;
                }

                $db = LoanFund::getDb();
                $transaction = $db->beginTransaction();
                try {
                    $order->fund_id = (int)$fund->id;

                    //减少资方额度
                    $fund->decreaseQuota($order->money_amount, $date, $order->money_amount);

                    //测试环境下延迟8秒查看并发情况
                    if(YII_ENV==='test') {
                        //sleep(8);
                    }

                    if($fund->requirePreSign() ) {
                        $status = UserLoanOrder::STATUS_FUND_CONTRACT;
                    } else {
                        $status = UserLoanOrder::STATUS_PENDING_LOAN;
                    }

                    $order->status = $status;
                    $order->fund_id = (int)$fund->id;
                    if(isset($update_order_attributes[0])) {
                        $update_order_attributes[] = 'status';
                        $update_order_attributes[] = 'fund_id';
                    } else {
                        $update_order_attributes['status'] = $status;
                        $update_order_attributes['fund_id'] = (int)$fund->id;
                    }

                    $order->updateAttributes($update_order_attributes);

                    if($log) {
                        $log->after_status = $status;
                        $log->save(false);
                    }

                    //插入资金记录
                    OrderFundInfo::add($order, $operator, OrderFundInfo::STATUS_DEFAULT, $fund);

                    $transaction->commit();
                    $ret = [
                        'code'=>0,
                    ];
                } catch (\Exception $ex) {
                    $transaction->rollBack();
                    $order->setOldAttribute('status', $old_row['status']);
                    $order->setOldAttribute('fund_id', $old_row['fund_id']);
                    OrderFundLog::add($fund->id, $order->id,"设置订单{$order->id} 资金渠道 {$fund->name} 失败， 继续查找可用资金渠道。旧状态为 {$old_row['status']} 旧资方为{$old_row['fund_id']} 错误信息：{$ex->getFile()} {$ex->getLine()} {$ex->getMessage()} {$ex->getTraceAsString()}");
                    $ret = [
                        'code'=>$ex->getCode() ? $ex->getCode() : -1,
                        'message'=>$ex->getMessage()
                    ];
                }
            } else {
                $ret = [
                    'code'=> ErrCode::ORDER_LOCK,
                    'message'=>'该订单资方处于锁定状态，请稍后再试'
                ];
            }

        } else {//超出余额
            $ret = [
                'code'=>ErrCode::FUND_OVER_QUOTA,
                'message'=>'金额超出余下限额'
            ];
        }
        RETURN_RET:

        if($lock) {
           Lock::del($lock_name);
        }
        if($ret['code']==0) {
            $order->populateRelation('loanFund', $fund);
            $this->afterSetOrderFund($order, $operator);
        }
        return $ret;
    }

    private function addOrUpdateSignedUser($signRecord, $status, $fundId, $userId, $cardNo, $retCode) {
        if (!$signRecord) {
            $signUser = new LoanFundSignUser();
            $signUser->fund_id = $fundId;
            $signUser->user_id = $userId;
            $signUser->card_no = $cardNo;
            $signUser->created_at = time();
        } else {
            $signUser = $signRecord;
        }
        $signUser->status = $status;
        $signUser->updated_at = time();
        $signUser->data = $retCode;

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if ($signUser->save()) {
                $transaction->commit();
                return;
            } else {
                throw new Exception;
            }
        } catch (Exception $e) {
            $transaction->rollBack();
        }
    }

    /**
     * 在设置订单资方后触发
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @throws \Exception
     */
    public function afterSetOrderFund($order, $operator) {
        $fund = $order->loanFund;
        $log_message = '';
        if($order->loanFund->requirePreSign()) {//要求预签约

            if($order->status == UserLoanOrder::STATUS_FUND_CONTRACT) {
                $order_fund_info = $order->orderFundInfo;
                //对于未激活的记录
                $sign_record=LoanFundSignUser::getSignedRecord($order->user_id, $fund->id, $order->cardInfo->card_no);
                $service = $fund->getService();
                $require_sign = true;
                //已经激活
                if($sign_record && ($sign_record->status == LoanFundSignUser::STATUS_SIGN_ACTIVE)) {
                    $require_sign = false;
                }
                if ($require_sign) {
                    $ret = $service->preSign($order);

                    if($ret['code']==0) {//激活签约成功
                        $log_message = "签约{$fund->name}已经有记录的银行卡{$order->cardInfo->card_no}成功";
                        $require_sign = false;
                        $this->addOrUpdateSignedUser($sign_record, LoanFundSignUser::STATUS_SIGN_ACTIVE, $fund->id, $order->user_id, $order->cardInfo->card_no, $ret['code']);
                    } else {//激活签约失败 跟没有签约的流程一样
                        $log_message = "签约{$fund->name}已经有记录的银行卡{$order->cardInfo->card_no}失败：".$ret['message']."，将通过新签约流程继续订单";
                        Yii::error($log_message);
                    }

                    OrderFundLog::add($fund->id, $order->id, $log_message);
                }

                if($require_sign) {
                    //1小时后 判断用户是否签约成功 签约失败的话可以进行切换资方等操作
                    RedisMQ::push(RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>UserLoanOrder::EVENT_CHECK_FUND_SIGN]), time() + 1200);

                    $order_fund_info->changeStatus(OrderFundInfo::STATUS_UNSIGN, $log_message);
                } else {
                    //修改订单状态为待放款
                    if($order->canChangeStatus(UserLoanOrder::STATUS_PENDING_LOAN)) {
                        $log = new UserOrderLoanCheckLog;
                        $log->setAttributes([
                            'user_id' => $order->user_id,
                            'repayment_id' => 0,
                            'before_status' => $order->status,
                            'after_status'=>UserLoanOrder::STATUS_PENDING_LOAN,
                            'operator_name'=>$operator,
                            'remark'=>"资方预约已经存在记录，跳过签约进入待放款",
                            'type'=>UserOrderLoanCheckLog::TYPE_LOAN,
                            'operation_type'=>UserOrderLoanCheckLog::LOAN_FUND,
                            'repayment_type'=>0,
                            'head_code'=>'',
                            'back_code'=>'',
                            'reason_remark'=>'',
                        ], false);

                        if(!$order->changeStatus(UserLoanOrder::STATUS_PENDING_LOAN, $log)) {
                            throw new \Exception("订单状态为 {$order->status} 更新为 待放款 失败");
                        }
                    } else {
                        Yii::error("订单 {$order->id} 当前状态为 {$order->status} 无法修改为 待放款");
                    }

                    $order_fund_info->changeStatus(OrderFundInfo::STATUS_PUSH_WAIT, "激活资方银行卡签约已成功，更新信息状态为待推送， 更新订单状态为待放款");

                    RedisMQ::push(RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>UserLoanOrder::EVENT_AFTER_SIGN_FUND]));
                }
            } else if($order->status==UserLoanOrder::STATUS_PENDING_LOAN) {
                RedisMQ::push(RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>UserLoanOrder::EVENT_AFTER_SIGN_FUND]));
            }
        } else {//不需要预签约
            //已经进入放款状态
        }
    }


    /**
     * 指定订单使用指定的资方
     * @param UserLoanOrder $order 订单模型
     * @param LoanFund $fund 资方模型
     * @param string $operator 操作人
     * @param [] $params 其他参数
     * @return []
     */
    public function orderChangeFund($order, $fund, $operator, $params=[]) {

        $lock = null;
        $lock_name = UserLoanOrder::getChangeStatusLockName($order->id);

        $date = date('Y-m-d');
        if(!in_array($order->status, UserLoanOrder::$allow_switch_fund_status)) {
            return [
                'code'=> ErrCode::ORDER_STATUS_INVALID,
                'message'=>'该订单状态无法变更资方'
            ];
        } else if(!$fund->supportLoanTerm($order->loan_term)) {
            return [
                'code'=> ErrCode::FUND_UNSUPPORT_TERM,
                'message'=>'该订单不支持当前订单借款期限'
            ];
        } else if(!$fund->supportBankId($order->cardInfo->bank_id)) {
            return [
                'code'=> ErrCode::FUND_UNSUPPORT_BANK,
                'message'=>'当前资方不支持订单绑定的银行卡'
            ];
        }

        $dayRemainingQuota = $fund->getTodayRemainingQouta();

        if($dayRemainingQuota>=$order->money_amount) {
            //需要锁定方法 避免同时调用

            if($lock = Lock::get($lock_name, 30)) {
                $row = UserLoanOrder::find()->select('`fund_id`,`status`')->where('`id`='.(int)$order->id)->one();
                $old_fund_id = $row['fund_id'];
                $old_status = $row['status'];
                if(!$old_fund_id) {
                    $ret = [
                        'code'=> ErrCode::ORDER_NOT_FUND,
                        'message'=>'该订单没有绑定资方'
                    ];
                    goto RETURN_RET;
                } else if($old_fund_id==$fund->id) {
                    $ret = [
                        'code'=>ErrCode::ORDER_FUND_NO_CHANGE,
                        'message'=>'该订单资方没有变化 '
                    ];
                    goto RETURN_RET;
                } else if( ($old_fund_id!=$order->fund_id) || ($order->status!=$old_status)) {
                    $ret = [
                        'code'=>ErrCode::ORDER_FUND_HAS_CHANGE,
                        'message'=>'该订单资方已经变化 '
                    ];
                    goto RETURN_RET;
                }
                $old_order_fund_info = OrderFundInfo::findOne([
                    'order_id'=>$order->id,
                    'fund_id'=>$old_fund_id
                ]);
                if(!$old_order_fund_info) {
                    $ret = [
                        'code'=>ErrCode::ORDER_FUND_INFO_NOT_FOUND,
                        'message'=>'该订单没有资方信息'
                    ];
                    goto RETURN_RET;
                }
                $change_fund_order_info = OrderFundInfo::findOne([
                    'order_id'=>$order->id,
                    'fund_id'=>$fund->id
                ]);
                if($change_fund_order_info) {
                    $ret = [
                        'code'=>ErrCode::ORDER_FUND_RECORD_EXIST,
                        'message'=>'该订单没有资方信息'
                    ];
                    goto RETURN_RET;
                }
                $old_fund = LoanFund::findOne($old_fund_id);
                $db = LoanFund::getDb();
                $transaction = $db->beginTransaction();
                try {
                    //变更新资方配额
                    $fund->decreaseQuota($order->money_amount, $date, $order->money_amount);

                    if($fund->requirePreSign() ) {
                        $status = UserLoanOrder::STATUS_FUND_CONTRACT;
                    } else {
                        $status = UserLoanOrder::STATUS_PENDING_LOAN;
                    }

                    //调整原来的渠道额度
                    if($old_order_fund_info->status>=0) {
                        $old_fund->increaseQuota($order->money_amount, date('Y-m-d', $old_order_fund_info->created_at), $order->money_amount);
                        $old_order_fund_info->changeStatus(OrderFundInfo::STATUS_REMOVED, "{$operator} 执行切换资方, 更新当前记录为删除状态");
                    }

                    $log = new UserOrderLoanCheckLog;
                    $log->setAttributes([
                        'user_id' => $order->user_id,
                        'repayment_id' => 0,
                        'before_status' => $old_status,
                        'after_status'=>$status,
                        'operator_name'=>$operator,
                        'remark'=>"切换资方",
                        'type'=>UserOrderLoanCheckLog::TYPE_LOAN,
                        'operation_type'=>UserOrderLoanCheckLog::LOAN_FUND,
                        'repayment_type'=>0,
                        'head_code'=>'',
                        'back_code'=>'',
                        'reason_remark'=>'',
                    ], false);
                    $log->save(false);
                    //更新需要放在添加资方记录前
                    $order->updateAttributes([
                        'fund_id'=>(int)$fund->id,
                        'status'=>$status
                    ]);

                    //插入资金记录 不支持多次切换 如果有存在的记录将会报错
                    OrderFundInfo::add($order, $operator, OrderFundInfo::STATUS_DEFAULT, $fund);

                    //测试环境下延迟8秒查看并发情况
                    if(YII_ENV==='test') {
                        //sleep(8);
                    }

                    $transaction->commit();
                    $ret = [
                        'code'=>0,
                    ];
                } catch (\Exception $ex) {
                    $transaction->rollBack();
                    //重置更新的信息
                    $order->fund_id = $old_fund_id;
                    $order->status = $old_status;
                    OrderFundLog::add($fund->id, $order->id, "切换资金渠道 {$fund->name} {$date}日 额度 {$order->money_amount} 失败：{$ex->getMessage()}  {$ex->getTraceAsString()}， 继续查找可用资金渠道");
                    $ret = [
                        'code'=>ErrCode::ORDER_STATUS_INVALID,
                        'message'=>$ex->getMessage()
                    ];
                }

            } else {
                $ret = [
                    'code'=> ErrCode::ORDER_LOCK,
                    'message'=>'该订单资方处于锁定状态，请稍后再试'
                ];
            }

        } else {
            $ret = [
                'code'=>ErrCode::FUND_OVER_QUOTA,
                'message'=>'该资方限额不足'
            ];
        }

        RETURN_RET:
        if($lock) {
           Lock::del($lock_name);
        }

        if($ret['code']==0) {
            $order->populateRelation('loanFund', $fund);
            $this->afterSetOrderFund($order, $operator);
        }

        return $ret;
    }

    /**
     * 获取订单资方签约URL(即确认银行卡)
     * @param UserLoanOrder $order 订单模型
     */
    public function getSignUrl($order) {
        $fund = $order->loanFund;
        switch ($fund->pre_sign_type) {
            case LoanFund::PRE_SIGN_1:
                $service = $fund->getService();
                $url  = $service->getSignUrl($order);
                break;
            default:
                $url = null;
                break;
        }
        return $url;
    }

    /**
     * 解析签约URL参数
     * @param type $key
     * @return type
     */
    public function parseSignParams($key) {
        $ret = null;
        $key = trim($key);
        $arr = explode('_', $key, 4);
        if(count($arr)>=4) {
            $order_id = (int)$arr[0];
            $fund_id = (int)$arr[1];
            $time = (int)$arr[2];
            $auth_key = $arr[3];

            $fund = LoanFund::findOne($fund_id);
            if($fund) {
                switch ($fund->pre_sign_type) {
                    case LoanFund::PRE_SIGN_1:
                        $service = $fund->getService();
                        $ret = $service->getSignParams($key);
                        break;
                    default:
                        break;
                }
            }
        }
        return $ret;
    }
}