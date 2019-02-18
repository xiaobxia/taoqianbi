<?php

namespace common\models\fund;

use Yii;
use common\models\UserLoanOrder;

/**
 * 订单资方还款日志表
 * This is the model class for table "{{%order_fund_repayment_log}}".
 *
 * @property string $id
 * @property string $fund_id 资方ID
 * @property string $order_id 订单ID
 * @property string $repay_amount 金额
 * @property string $repay_principal 本金
 * @property string $overdue_interest 逾期利息
 * @property string $overdue_service_fee 逾期服务费
 * @property integer $repay_type 还款方式
 * @property integer $status 状态
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 */
class OrderFundRepaymentLog extends \yii\db\ActiveRecord
{
    const STATUS_FINISH = 0;//本次还款成功
    const STATUS_REMOVED = -1;//本次还款取消
    
    
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_fund_repayment_log}}';
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }
   
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fund_id', 'order_id', 'repay_amount', 'overdue_interest', 'overdue_service_fee', 'created_at', 'updated_at'], 'required'],
            [['fund_id', 'order_id', 'repay_amount', 'overdue_interest', 'overdue_service_fee', 'status', 'created_at', 'updated_at'], 'integer']
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
            'repay_amount' => '金额',
            'overdue_interest' => '逾期利息',
            'overdue_service_fee' => '逾期服务费',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
    
    /**
     * 添加记录
     * @param UserLoanOrder $order 订单模型
     * @param integer $amount 还款金额
     * @return static
     */
    public static function add($order, $amount, $creditId=null) {
        $model = new static;
        $model->fund_id = $order->fund_id;
        $model->order_id = $order->id;
        $model->repay_amount = $amount;
        $row = static::getDb()->createCommand('SELECT SUM(overdue_interest) AS overdue_interest,SUM(overdue_service_fee) AS overdue_service_fee '
                . ' from '.static::tableName().' WHERE order_id='.(int)$order->id.' AND `status`>=0')->queryOne();
        $overdue_service_fee = $row['overdue_service_fee'];
        $overdue_interest = $row['overdue_interest'];
        $pay_overdue_fee = $overdue_service_fee + $overdue_interest;//已收的逾期管理费
        $unpay_overdue_interest = $order->orderFundInfo && $order->orderFundInfo->cacl_overdue_interest > $overdue_service_fee ? $order->orderFundInfo->cacl_overdue_interest - $overdue_service_fee : 0;//未支付的利息
        $unpay_overdue_fee = $order->userLoanOrderRepayment->late_fee - $pay_overdue_fee;//未支付的逾期管理费
        $unpay_overdue_service_fee = $unpay_overdue_fee - $unpay_overdue_interest;
        
        if($amount>=$unpay_overdue_interest) {
            $amount -= $unpay_overdue_interest;
            $model->overdue_service_fee = $unpay_overdue_interest; 
        } else {
            $model->overdue_service_fee = $amount;
            $amount = 0;
        }
        
        if($amount>=$unpay_overdue_service_fee) {
            $amount -= $unpay_overdue_service_fee;
            $model->overdue_service_fee = $unpay_overdue_service_fee; 
        } else {
            $model->overdue_service_fee = $amount;
            $amount = 0;
        }
        
        if($creditId) {
            $model->credit_id = $creditId;
        }
        
        if($order->orderFundInfo){
            if(($order->orderFundInfo->settlement_status==OrderFundInfo::SETTLEMENT_STATUS_FINISH && $order->orderFundInfo->settlement_type == OrderFundInfo::SETTLEMENT_TYPE_PREPAY) || (time() > $order->orderFundInfo->plan_payment_time && $order->orderFundInfo->plan_payment_time>0 ))
            {
                $model->repay_account_id=FundAccount::ID_REPAY_ACCOUNT_QIANCHENG;
            }else{
                $model->repay_account_id = $order->loanFund->repay_account_id;
            }
        }else{//老数据  无资方订单 默认口袋
            $model->repay_account_id = FundAccount::ID_REPAY_ACCOUNT_DEFAULT;
        }
            
        //垫付时间后的还款主体变更凌融
        $model->repay_principal = $amount;
        $model->save(false);
        return $model;
    }
}
