<?php

namespace common\services\fundChannel;

use Yii;
use yii\base\Component;
use common\helpers\Lock;
use common\models\fund\LoanFundDayPreQuota;
use common\models\fund\LoanFundDayQuota;
use common\models\UserLoanOrder;
use common\models\fund\LoanFund;
use common\models\UserOrderLoanCheckLog;
use common\models\fund\OrderFundInfo;
use common\api\RedisMQ;
use common\api\RedisQueue;
use common\base\ErrCode;
use common\models\BankConfig;

/**
 * 渠道基础有服务类
 * @author Vink
 */
abstract class BaseService extends Component
{

    /**
     * 是否开启调试状态
     * @var integer
     */
    protected $debug = 1;

    //上次请求的响应
    public $lastRequest = [
        'error' => null, //错误码
        'ret' => null, //请求结果
        'info' => null, //请求信息 包括http_code等数据
        'parse_ret' => null, //解析结果
    ];

    protected $config;

    /**
     * 初始化服务类
     */
    public function init() {
        parent::init();
        $this->config = !isset(Yii::$app->params['interface_'.$this->getChannelName()])
            ? []
            : Yii::$app->params['interface_'.$this->getChannelName()];
    }

    /**
     * 记录日志
     * @param string $message
     * @param string $level
     * @param string $category_suffix
     */
    protected function log($message, $level = 'info', $category_suffix=null) {
        if ($this->debug && $level == 'trace') {
            $level = 'info';
        }
        \yii::$level($message, 'wzd.fund.'.$this->getChannelName().($category_suffix ? ".{$category_suffix}" : ''));
    }

    /**
     * 获取渠道名称
     * @return string
     */
    public abstract function getChannelName();

    /**
     * 用户和资方进行预签约
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public function preSign($order) {
        throw new \Exception('请扩展该方法');
    }

    /**
     * 用户和资方进行预签约
     * @param UserLoanOrder $order 订单模型
     * @param [] $params 参数
     * @return []
     */
    public function confirmSign($order, $params) {
        throw new \Exception('请扩展该方法');
    }

    /**
     * 订单签约成功
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @return []
     */
    public function orderSignSuccess($order, $operator='') {
        throw new \Exception('请扩展该方法');
    }

    /**
     * 订单放款成功
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @return []
     */
    public function pushOrder($order, $operator='') {
        throw new \Exception('请扩展该方法');
    }

    /**
     * 查询订单状态
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public function queryOrder($order) {
        throw new \Exception('请扩展该方法');
    }

    /**
     * 获取银行列表
     * @return []
     */
    public function getSupportBankList() {
        return BankConfig::$bankInfo;
    }

    /**
     * 获取银行ID列表
     * @return []
     */
    public function getSupportBankIds() {
        return array_keys(BankConfig::$bankInfo);
    }

    /**
     * 订单放款成功
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public abstract function orderPaySuccess($order);

    /**
     * 订单放款成功
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public abstract function orderRepaySuccess($order);

    /**
     * 向资方查询更新订单推送状态
     * @param UserLoanOrder $order 订单模型
     * @param [] $params 参数
     * @return []
     */
    public abstract function updateOrderPushStatus($order, $params);

    public function actionTest() {
        return [
            'code'=>0,
            'message'=>'hello world!'
        ];
    }

    /**
     * 报告严重的错误
     * @param string $log
     */
    public function reportError($log) {

    }

    /**
     * 在预签约成功以后调用
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @param string $remark 备注
     * @return []
     */
    public function afterPreSignSuccess($order, $operator, $remark = '确认银行卡成功，修改订单为待放款') {
        //待签约 修改为待放款
        $ret = ['code'=>0];
        if($order->status == UserLoanOrder::STATUS_FUND_CONTRACT) {
            //待签约 修改为待放款
            $log = new UserOrderLoanCheckLog;
            $log->setAttributes([
                'user_id' => $order->user_id,
                'repayment_id' => 0,
                'before_status' => $order->status,
                'after_status'=>UserLoanOrder::STATUS_PENDING_LOAN,
                'operator_name'=>$operator,
                'remark'=>$remark,
                'type'=>UserOrderLoanCheckLog::TYPE_LOAN,
                'operation_type'=>UserOrderLoanCheckLog::LOAN_FUND,
                'repayment_type'=>0,
                'head_code'=>'',
                'back_code'=>'',
                'reason_remark'=>'',
            ], false);
            if(!$order->changeStatus(UserLoanOrder::STATUS_PENDING_LOAN, $log)) {
                $ret = [
                    'code'=>ErrCode::ORDER_STATUS_CHANGE_ERROR,
                    'message'=>'保存订单状态失败'
                ];
            } else {
                //放入推单
                $order->orderFundInfo->changeStatus(OrderFundInfo::STATUS_PUSH_WAIT, "激活资方银行卡签约已成功， 更新订单状态为待放款, 更新当前状态为已签约待推送");
                //OrderFundLog::add($order->fund_id, $order->id, "激活资方银行卡签约已成功， 更新订单状态为待放款");
                RedisMQ::push(RedisQueue::LIST_FUND_ORDER_EVENT, json_encode(['order_id'=>$order->id,'event_name'=>UserLoanOrder::EVENT_AFTER_SIGN_FUND]));
            }
        }
        return $ret;
    }

    /**
     * 是否支持借款期限
     * @param integer $term 借款的期限
     * @return bool
     */
    public function supportLoanTerm($term) {
        return true;
    }

    /**
     * 判断资方是否支持订单
     * @param UserLoanOrder $order 订单
     * @param LoanFund $fund 资方数据
     * @return [0 成功 -1 不支持]
     */
    public function supportOrder($order, $fund) {
        return [
            'code'=>0
        ];
    }

    /**
     * 在订单设置资方后执行
     * @param integer $time 放款时间
     * @return []
     */
    public function afterSetOrderFund($order, $time=null) {
        return [
            'code'=>0
        ];
    }

    /**
     * 在订单取消资方后执行
     * @param integer $time 放款时间
     * @return []
     */
    public function afterCancelOrderFund($order, $time=null) {
        return [
            'code'=>0
        ];
    }

    /**
     * 判断是否能转换资方
     * @param UserLoanOrder $order 订单
     * @return []
     */
    public function canChangeFund($order) {
        return [
            'code'=>0
        ];
    }

    /**
     * 获取资方第日配额
     * @param $fund
     * @return int
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function getTodayRemainingQouta($fund,$order) {
        $date = date('Y-m-d');
        $quota_model = LoanFundDayQuota::findOne([
            'fund_id'=> $fund->id,
            'date'=>$date
        ]);
        /* @var $quota_model LoanFundDayQuota */
        if(!$quota_model) {
            if($fund->quota_type == LoanFund::QUOTA_TYPE_TOTAL) {
                if(!($lock = Lock::get(( $lock_name = 'fundGetDayRemainingQuota'.$fund->id.':'.$date), 30, 3))) {
                    throw new \Exception('获取资方每日额度锁失败');
                }

                //获取到锁后  再获取一次, 防止获取锁时已经插入了配额记录
                if(($quota_model = LoanFundDayQuota::findOne([
                    'fund_id'=> $fund->id,
                    'date'=>$date
                ]))) {
                    Lock::del($lock_name);
                    return $quota_model;
                }

                $pre_quota = LoanFundDayPreQuota::findOne([
                    'fund_id'=> $fund->id,
                    'date'=>$date
                ]);
                $change_amount = $pre_quota ? ($pre_quota->incr_amount - $pre_quota->decr_amount) : 0;
                if($change_amount) {
                    LoanFund::getDb()->createCommand('UPDATE '.LoanFund::tableName().' SET `can_use_quota`=`can_use_quota`+'.$change_amount.
                        ' WHERE id='.$fund->id)->execute();
                    $can_use_quota = LoanFund::find()->select('`can_use_quota`')->where('`id`='.(int)$fund->id)->scalar();
                    $fund->can_use_quota = (int)$can_use_quota;
                    $fund->setOldAttribute('can_use_quota', $can_use_quota);
                }
                LoanFundDayQuota::add($fund->id, $date, $fund->can_use_quota>0?$fund->can_use_quota:0);
                if($lock) {
                    Lock::del($lock_name);
                }
            } else {
                LoanFundDayQuota::add($fund->id, $date, $fund->day_quota_default);
            }
            $quota_model = LoanFundDayQuota::findOne([
                'fund_id'=> $fund->id,
                'date'=>$date
            ]);
            if(!$quota_model) {
                Yii::error("异常：获取不到资金 {$fund->id} {$date}日配额");
                return 0;
            }
        }
        return $quota_model->remaining_quota;


    }


}
