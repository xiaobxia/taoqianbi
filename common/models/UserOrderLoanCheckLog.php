<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\UserLoanOrderRepayment;
/**
 * 借款审核表
 * @property integer $id ID
 * @property integer $order_id 订单ID
 * @property integer $repayment_id 还款表ID
 * @property integer $before_status 变更前状态
 * @property integer $after_status 变更后状态
 * @property string $operator_name 操作人名称
 * @property string $remark 备注
 * @property integer $type 1、借款；2、还款
 * @property integer $operation_type 操作类型，如放款初审、复审等，详见model类
 * @property integer $repayment_type 还款类型：1零钱贷  2房租贷
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property string $head_code 备注--头码
 * @property string $back_code 备注--尾码
 * @property string $reason_remark 审核码
 * @property integer $can_loan_type 是否可再借 1 可再借 -1 不可再借 2 1个月后再借
 * @property integer $user_id 用户ID
 * 
 */
class UserOrderLoanCheckLog extends ActiveRecord
{
    //类型
    const TYPE_LOAN = 1; //借款
    const TYPE_REPAY = 2;//还款

    public static $type = [
        self::TYPE_LOAN => '借款',
        self::TYPE_REPAY => '还款',
    ];

    const CAN_NOT_LOAN = -1;
    const CAN_LOAN = 1;
    const MONTH_LOAN = 2;

    public static $can_loan_type = [
        self::CAN_NOT_LOAN => '不可再借',
        self::CAN_LOAN => '可再借',
        self::MONTH_LOAN => '1个月后再借',
    ];

    const LOAN_CS = 1;
    const LOAN_FS = 2;
    const LOAN_DFK = 3;
    const LOAN_FK = 4;
    const REPAY_CS = 5;
    const REPAY_FS = 6;
    const REPAY_DKK = 7;
    const REPAY_KK = 8;
    const REPAY_XXKK = 9;
    const REPAY_KKJM = 10;
    const REPAY_BFHK = 11;
    const LOAN_JS = 12;
    const LOAN_FUND = 13;
    const REPAY_PART_CANCEL = 14;//取消部分还款
    const REPAY_RENEW_CANCEL = 15;//取消续期

    public static $operation_type_list = [
        self::LOAN_CS => '借款初审',
        self::LOAN_FS => '借款复审',
        self::LOAN_DFK => '借款待放款',
        self::LOAN_FK => '借款财务放款',
        self::REPAY_CS => '还款初审',
        self::REPAY_FS => '还款复审',
        self::REPAY_DKK => '还款待扣款',
        self::REPAY_KK => '还款财务扣款',
        self::REPAY_XXKK => '线下还款扣款',
        self::REPAY_KKJM => '扣款减免',
        self::REPAY_BFHK => '部分还款',
        self::LOAN_JS => '借款机审',
        self::LOAN_FUND => '资金方',
        self::REPAY_PART_CANCEL => '取消部分还款',
        self::REPAY_RENEW_CANCEL => '取消续期',
    ];

    //还款类型
    const REPAYMENT_TYPE_LQD = 1;   //零钱贷
    const REPAYMENT_TYPE_FZD = 2;   //房租贷
    const REPAYMENT_TYPE_FQSC = 3;   //分期购

    public static function tableName()
    {
        return '{{%user_order_loan_check_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
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
        ];
    }

    public function beforeSave($insert){

        if(!isset($this->user_id)||empty($this->user_id)){
            $order_id = $this->order_id;
            $user_loan_order = UserLoanOrder::findOne(['id'=>$order_id]);
            if($user_loan_order){
                $this->user_id = $user_loan_order->user_id;
            }

        }
        $this->created_at = time();
        $this->updated_at = time();
        return $this;

    }


    /**
     * 根据具体日期查询（机审或人审）的总金额和总订单数
     * @param $date
     * @param string $tree 策略树
     * @param int $auto  人审,机审,全部(0,1,2)
     * @param boolean $passed  已审通过/全部
     * @return mixed
     */
    public static function getLoan($date, $tree, $auto=1, $passed=true){
        $conn = Yii::$app->get('db_kdkj_rd');
        if($auto==1) {
            $map = ['in', 'operator_name', ['auto shell','shell auto','机审'] ];
        } elseif($auto==0) {
            $map = ['not in', 'operator_name', ['auto shell','shell auto','机审'] ];
        } else {
            $map = [];
        }
        if($passed){
            $condition = ['before_status'=>0, 'type'=>1, 'tree'=>$tree, 'after_status'=>7];  //已通过
        } else {
            $condition = ['before_status'=>0, 'type'=>1, 'tree'=>$tree, 'after_status'=>[7, -3]];  //全部
        }
        $result = self::find()
            ->select('DISTINCT(order_id)')
            ->where(['between','created_at', strtotime($date.' 00:00:00'), strtotime($date.' 23:59:59')])
            ->andWhere($condition)
            ->andWhere($map)
            ->asArray()->all($conn);

        $total = count($result);
        $money_amount = 0;
        $overdue3_amount  = $overdue3_count = 0;   //逾期3天+总金额， 总订单数初始值
        $overdue10_amount = $overdue10_count = 0;  //逾期10天+ 总金额，总订单数初始值
        $overdue30_amount = $overdue30_count = 0;  //逾期10天+ 总金额，总订单数初始值
        $all3_amount  = $all3_count = 0;
        $all10_amount  = $all10_count = 0;
        $all30_amount  = $all30_count = 0;
        $profit_total = 0;      //纯利润金额
        $total_principal = 0;   //总放款金额

        $order_ids = !empty($result) ? array_column($result, 'order_id') : []; //订单ID
        $query = UserLoanOrder::find()
            ->select('sum(money_amount) as money_amount')
            ->where(['in', 'id', $order_ids])->asArray()->one();
        $money_amount += $query['money_amount']?:0;   //订单处理总金额

//        $result_repayment = UserLoanOrderRepayment::find()
//            ->select('order_id')
//            ->where(['between','created_at', strtotime($date.' 00:00:00'), strtotime($date.' 23:59:59')])
//            ->andWhere(['<=', 'plan_fee_time', time()])
//            ->asArray()->all();
//        $repayment_order_ids = !empty($result_repayment) ? array_column($result_repayment, 'order_id') : [];  //还款表订单ID

        if($auto==2 && !empty($order_ids)){ //不分是否机审

            $over3 = UserLoanOrderRepayment::getOverdue($order_ids, 3);  //逾期3天+
            $overdue3_amount = $over3['principal'];
            $overdue3_count = $over3['count'];
            $over10 = UserLoanOrderRepayment::getOverdue($order_ids, 10); //逾期10天
            $overdue10_amount = $over10['principal'];
            $overdue10_count = $over10['count'];
            $over30 = UserLoanOrderRepayment::getOverdue($order_ids, 30); //逾期30天
            $overdue30_amount = $over30['principal'];
            $overdue30_count = $over30['count'];

            $all3 = UserLoanOrderRepayment::getAllOrders($order_ids, 3);
            $all3_amount = $all3['principal'];    //该时间段内所有的放款且未还款总额
            $all3_count  = $all3['count'];        //该时间段内所有的放款且未还款总订单
            $all10 = UserLoanOrderRepayment::getAllOrders($order_ids, 10);
            $all10_amount = $all10['principal'];
            $all10_count  = $all10['count'];
            $all30 = UserLoanOrderRepayment::getAllOrders($order_ids, 30);
            $all30_amount = $all30['principal'];
            $all30_count  = $all30['count'];

            $delay_fee = 0;
            $delay_fee = UserLoanOrderDelayLog::find()
                ->select('sum(service_fee+counter_fee) as total')
                ->where(['order_id'=>$order_ids])->andWhere(['status'=>1])->asArray()->one($conn)['total'];   //展期服务费+展期手续费
            $total = UserLoanOrderRepayment::getTotalMoney($order_ids);         //收回总金额
            $counter_fee = 0;
            $counter_fee = UserLoanOrder::find()
                ->select('sum(counter_fee) as counter_fee')
                ->where(['id'=>$order_ids])->asArray()->one($conn)['counter_fee'];  //手续费
            $total_principal = $total['total_principal']-$counter_fee;                               //放款金额
            $profit_total = $total['total_repayment'] + $delay_fee + $counter_fee - $total['total_principal'];  //纯利润
        }

        $data['money_amount']          = $money_amount/100;             //订单总金额
        $data['order_count']           = $total;                        //总订单数
        $data['overdue3_amount_rate']  = $all3_amount>0 ? number_format($overdue3_amount/$all3_amount*100, 2).'%' : 0;       //逾期率3+ (订单金额)
        $data['overdue10_amount_rate'] = $all10_amount>0 ? number_format($overdue10_amount/$all10_amount*100, 2).'%' : 0;    //逾期率10+
        $data['overdue30_amount_rate'] = $all30_amount>0 ? number_format($overdue30_amount/$all30_amount*100, 2).'%' : 0;    //逾期率30+
        $data['profit']                = $profit_total/100;                                                                  //纯利润(单位元)
        $data['risk_controlling']      = $total_principal>0 ? number_format($profit_total/$total_principal, 4) : 0;          //风控系数

        $data['overdue3_count_rate']   = $all3_count>0 ? number_format($overdue3_count/$all3_count*100, 2).'%' : 0;        //逾期率30+(订单数量)
        $data['overdue10_count_rate']  = $all10_count>0 ? number_format($overdue10_count/$all10_count*100, 2).'%' : 0;     //逾期率3+
        $data['overdue30_count_rate']  = $all30_count>0 ? number_format($overdue30_count/$all30_count*100, 2).'%' : 0;     //逾期率30+

        return $data;
    }

}