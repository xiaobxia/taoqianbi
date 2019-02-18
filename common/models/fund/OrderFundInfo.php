<?php

namespace common\models\fund;

use Yii;
use common\models\UserLoanOrder;

/**
 * 订单资方信息
 * This is the model class for table "{{%order_fund_info}}".
 *
 * @property string $id
 * @property integer $fund_id 资方ID 
 * @property integer $order_id 订单ID
 * @property integer $user_id 用户ID
 * @property integer $fund_order_id 现金渠道订单ID
 * @property integer $pay_account_id 支付主体ID
 * @property integer $repay_account_id 扣款主体ID
 * @property integer $status 状态值 
 * @property integer $fund_status 资方状态
 * @property integer $settlement_status 结算状态
 * @property integer $order_push_time 订单推送时间
 * @property integer $fund_pay_time 资方放款时间
 * @property integer $settlement_time 结算时间
 * @property integer $settlement_type 结算类型
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property integer $service_fee 服务费
 * @property integer $fund_service_fee 资方服务费
 * @property integer $interest 利息
 * @property integer $credit_verification_fee 征信费
 * @property integer $deposit 保证金
 * @property integer $overdue_fee 逾期费用
 * @property integer $overdue_interest 逾期利息
 * @property integer $cacl_overdue_interest 应收逾期利息
 * @property integer $user_repay_amount 用户还款金额
 * @property integer $prepay_amount 垫付金额
 * @property integer $prepay_time 垫付时间
 * @property integer $renew_fee 续借手续费（分）
 * @property integer $renew_service_fee 续借服务费（分）
 * @property integer $discount 抵扣（分）
 * @property integer $plan_payment_time 计划垫付时间
 * @property integer $fund_arrival_time 资方满标时间
 */
class OrderFundInfo extends \yii\db\ActiveRecord
{
    const STATUS_REMOVED = -1;//删除状态
    const STATUS_DEFAULT = 0;//未签约
    const STATUS_UNSIGN = 10;//待签约
    const STATUS_SIGNED_ACTIVE = 20;//已签约
    const STATUS_PUSH_WAIT = 30;//订单待推送
    const STATUS_PUSH_READY = 40;//订单开始推送
    const STATUS_PUSH = 50;//订单已经推送
    const STATUS_PAY_NOTIFY = 60;//收到支付通知
    const STATUS_REPAY_NOTIFY = 80;//收到还款通知
    
    #最终状态
    const STATUS_SETTLEMENT_FINISH = 100;//结算完成
    
    const FUND_STATUS_DEFAULT = 0;//资方默认状态
    
    const SETTLEMENT_STATUS_NO = 0;//无结算状态 
    const SETTLEMENT_STATUS_FINISH = 1;//结算状态完成 
    
    const SETTLEMENT_STATUS_LIST = [
        self::SETTLEMENT_STATUS_NO=>'未结算',
        self::SETTLEMENT_STATUS_FINISH=>'已结算',
    ];
    
    #结算类型 垫付 或 用户还款
    const SETTLEMENT_TYPE_NO = 0;//未结算
    const SETTLEMENT_TYPE_PREPAY = 1;//垫付类型
    const SETTLEMENT_TYPE_USER_REPAY = 2;//用户还款
    
    const SETTLEMENT_TYPE_LIST = [
        self::SETTLEMENT_TYPE_NO=>'未结算',
        self::SETTLEMENT_TYPE_PREPAY=>'垫付',
        self::SETTLEMENT_TYPE_USER_REPAY=>'用户还款',
    ];
    
    const STATUS_LIST = [
        self::STATUS_REMOVED=>'已删除',
        self::STATUS_DEFAULT=>'无',
        self::STATUS_UNSIGN=>'待签约',
        self::STATUS_SIGNED_ACTIVE=>'已签约',
        self::STATUS_PUSH_WAIT=>'订单待推送',
        self::STATUS_PUSH_READY=>'订单开始推送',
        self::STATUS_PUSH=>'订单已经推送',
        self::STATUS_PAY_NOTIFY=>'收到支付通知',
        self::STATUS_REPAY_NOTIFY=>'收到还款通知',
        self::STATUS_SETTLEMENT_FINISH => '结算完成',
    ];
    
     /**
     * 允许切换资方的状态
     * @var type 
     */
    public static $allow_switch_fund_status = [
        self::STATUS_DEFAULT,
        self::STATUS_UNSIGN, 
        self::STATUS_SIGNED_ACTIVE,
        self::STATUS_PUSH_WAIT,
        self::STATUS_PUSH_READY,
    ];
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_fund_info}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fund_id' => '资方ID',
            'order_id' => '订单ID',
            'user_id' => '用户ID',
            'status' => '状态',
            'fund_status' => '资方状态',
            'settlement_status' => '结算状态',
            'order_push_time' => '推单时间',
            'fund_pay_time' => '资方放款时间',
            'settlement_time' => '结算时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'service_fee' => '居间服务费（分）',
            'fund_service_fee' => '资方服务费（分）',
            'interest' => '利息（分）',
            'credit_verification_fee' => '征信费用（分）',
            'deposit' => '保证金（分）',
            'pay_account_id' => '放款主体',
            'repay_account_id' => '还款主体',
            'user_repay_amount' => '用户还款金额',
            'prepay_amount' => '垫付金额',
            'renew_fee' => '续借手续费（分）',
            'renew_service_fee' => '续借服务费（分）',
            'discount' => '抵扣（分）',
            'cacl_overdue_interest'=>'应收逾期利息',
        ];
    }
    
    /**
     * 添加记录
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人 管理员为管理员ID 或 riskShell等等
     * @param integer $status 状态值 
     * @param LoanFund $fund 资方  无资方时使用订单的资方
     */
    public static function add($order, $operator, $status=self::STATUS_DEFAULT, $fund=null) {
        $model = new static;
        if(!$fund) {
            $fund = $order->loanFund;
        }
        $model->fund_id = (int)$fund->id;
        $model->user_id = (int)$order->user_id;
        $model->order_id = (int)$order->id;
        $model->pay_account_id = (int)$fund->pay_account_id;
        $model->repay_account_id = (int)$fund->repay_account_id;
        $model->status = (int)$status;
        $model->fund_status = static::FUND_STATUS_DEFAULT;
        $model->settlement_status = static::SETTLEMENT_STATUS_NO;
        $model->order_push_time = 0;
        $model->fund_pay_time = 0;
        $model->settlement_time = 0;
        
        if($order->loan_time) {
            $model->created_at = $model->updated_at = $order->loan_time;
        }
        
        $fees = $fund->getFeeList($order->loan_term, $order->money_amount, $order->card_type, $order->user_id);
        
        $model->service_fee = $fees['service_fee'];//我方服务费
        $model->fund_service_fee = $fees['fund_service_fee'];//资方服务费
        $model->interest = $fees['interest'];//利息
        $model->credit_verification_fee = $fees['credit_verification_fee'];//征信费用
        $model->deposit = $fees['deposit'];//保证金
        $model->pay_account_id = $fund->pay_account_id;
        $model->repay_account_id = $fund->repay_account_id;
        
        $model->save(false);
        
        OrderFundLog::add((int)$fund->id, $order->id, $operator.'创建了订单资方关联信息记录'.$model->id);
        $order->populateRelation('orderFundInfo', $model);
        $order->populateRelation('loanFund', $fund);
        
        return $model;
    }
    
    /**
     * 判断当前状态是否能调整到新的状态
     * @param integer $status 新的状态值 
     * @return array
     */
    public function canChangeStatus($status) {
        $disable_status = $allow_status = [];
        switch($this->status) {
            case static::STATUS_DEFAULT:
                $disable_status = [];
                break;
            case static::STATUS_PUSH_READY:
                $disable_status = [];
                break;
            case static::STATUS_PUSH:
                $disable_status = [];
                break;
            case static::STATUS_PAY_NOTIFY:
                $disable_status = [static::STATUS_PUSH, static::STATUS_DEFAULT];
                break;
            case static::STATUS_REMOVED:
                $disable_status = [];
                break;
            default:
                break;
        }
        return !in_array($status, $disable_status);
    }
    
    /**
     * 改变状态
     * @param integer $status 状态值
     * @param string $remark 备注
     * @param array $update_attributes 更新属性
     * @return []
     */
    public function changeStatus($status, $remark, $update_attributes=[]) {
        $ret = ['code'=>0];
        if($status!=$this->status) {
            $old_status = $this->status;
            $this->status = (int)$status;
            $db = static::getDb();
            if(isset($update_attributes[0])) {
                $update_attributes[] = 'status';
                $attrs = $update_attributes;
            } else {
                $update_attributes['status'] = $status;
                $attrs = array_keys($update_attributes);
            }
            
            foreach($attrs as $attr) {
                if(in_array($attr, ['fund_id','order_id', 'user_id'])) {
                    throw new \Exception("{$attr}属性值不允许被更改");
                }
            }
            
            $transaction = $db->beginTransaction();
            
            try {
                //更新
                $this->updateAttributes($update_attributes);
                //保存日志
                OrderFundLog::add($this->fund_id, $this->order_id, "更新状态 {$old_status} 为 {$status}, 备注：".$remark);
                $transaction->commit();
            } catch (\Exception $ex) {
                $transaction->rollBack();
                $ret = [
                    'code'=>-1,
                    'message'=>$ex->getMessage()
                ];
            }
        }
        return $ret;
    }
    
    public function getLoanFund() {
        return $this->hasOne(LoanFund::className(),['id'=>'fund_id']);
    }
    
    /**
     * 通过订单ID获取记录
     * @param integer $order_id 订单ID
     * @return OrderFundInfo
     */
    public static function getByOrderId($order_id){
        return static::find()->where(['order_id'=>$order_id])->where('status >= '.self::STATUS_DEFAULT)->limit(1)->one();
    }
}
