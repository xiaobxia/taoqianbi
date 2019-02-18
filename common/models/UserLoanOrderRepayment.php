<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\loan\LoanCollectionOrder;
use common\helpers\MailHelper;

/**
 * This is the model class for table "{{%user_loan_order}}".
 * 
 * @property integer $id ID
 * @property integer $user_id 用户ID
 * @property integer $order_id 订单ID
 * @property integer $principal 资方本金
 * @property integer $interests 利息
 * @property integer $late_day 滞纳天数
 * @property integer $late_fee 滞纳金（分）
 * @property integer $plan_repayment_time 结息日期
 * @property integer $plan_fee_time 开始计算滞纳金时间
 * @property string $operator_name 操作人
 * @property integer $status 状态
 * @property integer $remark 备注
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property integer $total_money 还款总额（分）
 * @property integer $card_id 银行卡ID
 * @property integer $true_repayment_time 实际还款时间
 * @property integer $loan_time 起息日期
 * @property integer $interest_day 计息天数
 * @property integer $apr 日利率，单位为万分之几
 * @property integer $loan_day 借还总天数
 * @property integer $apply_repayment_time 申请还款时间
 * @property integer $interest_time 当前计算利息的时间
 * @property integer $true_total_money 实际还款总额（分）
 * @property integer $debit_times 扣款次数
 * @property integer $current_debit_money 当前扣款金额（分）
 * @property integer $is_overdue 是否是逾期：0，不是；1，是
 * @property integer $overdue_day 逾期天数
 * @property integer $coupon_id 抵扣券ID
 * @property integer $coupon_money 抵扣券抵扣金额
 * @property integer $user_book 用户留言
 * 
 * @property UserLoanOrderDelay $orderDelayLog 订单延迟记录
 */
class UserLoanOrderRepayment extends \yii\db\ActiveRecord
{
    const OVERDUE_YES = 1;
    const OVERDUE_NO = 0;
    public static $overdue = [
        self::OVERDUE_NO=>'否',
        self::OVERDUE_YES=>'是',
    ];

    public $source_id;
    public $customer_type;

    const STATUS_REPAY_CANCEL = -6;
    const STATUS_DEBIT_FALSE = -5;
    const STATUS_BAD_DEBT = -4;
    const STATUS_OVERDUE = -3;
    const STATUS_REPAY_REPEAT_CANCEL = -2;
    const STATUS_CANCEL=-1;
    const STATUS_NORAML = 0;
    const STATUS_CHECK = 1;
    const STATUS_PASS = 2;
    const STATUS_REPAY_COMPLEING = 3;
    const STATUS_REPAY_COMPLETE = 4;
    const STATUS_WAIT = 5;

    public static $status = [
        self::STATUS_REPAY_CANCEL=>'扣款驳回',
        self::STATUS_DEBIT_FALSE=>'扣款失败',
        self::STATUS_BAD_DEBT=>'已坏账',
        self::STATUS_OVERDUE=>'已逾期',
        self::STATUS_REPAY_REPEAT_CANCEL=>'还款复审驳回',
        self::STATUS_CANCEL=>'还款初审驳回',
        self::STATUS_NORAML =>'生息中',
        self::STATUS_CHECK=>'还款初审',
        self::STATUS_PASS=>'还款复审',
        self::STATUS_REPAY_COMPLEING=>'待扣款',
        self::STATUS_WAIT=>'扣款中',
        self::STATUS_REPAY_COMPLETE=>'已还款',
    ];

    public static $frontend_status=[
        self::STATUS_REPAY_CANCEL=>'还款失败',
        self::STATUS_CANCEL=>'还款失败',
        self::STATUS_REPAY_REPEAT_CANCEL=>'还款失败',
        self::STATUS_NORAML =>'生息中',
        self::STATUS_OVERDUE =>'生息中',
        self::STATUS_BAD_DEBT =>'生息中',
        self::STATUS_CHECK=>'申请还款中',
        self::STATUS_PASS=>'申请还款中',
        self::STATUS_REPAY_COMPLEING=>'还款中',
        self::STATUS_DEBIT_FALSE=>'扣款失败',
        self::STATUS_REPAY_COMPLETE=>'已还款',
    ];

    //自动审核用状态
    public static $auto_pass_status = [
        self::STATUS_REPAY_CANCEL=>'扣款驳回',
        self::STATUS_DEBIT_FALSE=>'扣款失败',
        self::STATUS_BAD_DEBT=>'已坏账',
        self::STATUS_OVERDUE=>'已逾期',
        self::STATUS_REPAY_REPEAT_CANCEL=>'还款复审驳回',
        self::STATUS_CANCEL=>'还款初审驳回',
        self::STATUS_NORAML =>'生息中',
        self::STATUS_CHECK=>'还款初审',
        self::STATUS_PASS=>'还款复审',
        self::STATUS_REPAY_COMPLEING=>'待扣款',
        self::STATUS_WAIT=>'扣款中',
    ];

    public static function ids($ids = array()){
        $result = array();
        $res = self::find()->select("*")
                           ->where("`id` IN (".implode(',', $ids).")")
                           ->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

     public static function array_ids($ids = array()){
        $result = array();
        $res = self::find()->select("*")
                           ->where("`id` IN (".implode(',', $ids).")")
                           ->asArray()->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    
//    
//    public function rules(){
//        return [
//            [[ 'id','user_id', 'order_id', 'principal', 'interests','late_day','late_fee', 'plan_repayment_time','plan_fee_time','operator_name','status','remark','created_at','updated_at','total_money','card_id','true_repayment_time','loan_time','interest_day','apr','loan_day','apply_repayment_time','interest_time','true_total_money','debit_times','current_debit_money','is_overdue','overdue_day','coupon_id','coupon_money','user_book'], 'safe'],
//        ];
//    }

    //返回指定ID范围内的实际还款本金与逾期天数信息
    public static function principal_overduedays_in_ids($ids = array()){
        return self::find()
                    ->select(["total"=>"IF(true_total_money>principal, principal, true_total_money)", 'OverdueDays'=>"overdue_day"])
                    ->where("`id` IN(".implode(',', $ids).") AND `true_repayment_time` != 0 AND `status` = 4")
                    ->orderBy(['overdue_day'=>SORT_ASC])
                    ->asArray()->all(self::getDb_rd());
    }

    //返回指定ID范围内的统计信息
    public static function statistics_in_ids($ids = array()){
        
        return self::find()
                    ->select(["Principal"=>"SUM(`principal`)", 'amount'=>"count(`id`)", "createTime"=>"FROM_UNIXTIME(`plan_fee_time`,'%Y-%m-%d')"])
                    ->where("`id` IN(".implode(',', $ids).")")
                    ->asArray()->all(self::getDb_rd());
    }

    //指定应还时间内的本金总和：
    public static function principal_planFeeTime_between($time_start=0, $time_end=0){
       
        $condition = "";
        if(!empty($time_start)){
            if(is_integer($time_start)){
                $condition .= "plan_fee_time >=".$time_start;
            }else{
                $condition .= "plan_fee_time >= UNIX_TIMESTAMP('" . $time_start . " 0:0:0')";
            }
        }

        if(!empty($time_end)){
            if(!empty($condition)){
                if(is_integer($time_end)){
                    $condition .= " AND plan_fee_time <=".$time_end;
                }else{
                    $condition .= " AND plan_fee_time <= UNIX_TIMESTAMP('" . $time_end . " 23:59:59')";
                }

            }else{
                if(is_integer($time_end)){
                    $condition .= "plan_fee_time <=".$time_end;
                }else{
                    $condition .= "plan_fee_time <= UNIX_TIMESTAMP('" . $time_end . " 23:59:59')";
                }
            }
           
        }

        return self::find()->select(["amount"=>"SUM(principal)", "plan_feeTime"=>"FROM_UNIXTIME(plan_fee_time,'%Y-%m-%d')"])->where($condition)->groupBy("plan_feeTime")->asArray()->all(self::getDb_rd());
    }

    //新增还款数量、本金、滞纳金：
    public static function daily($dateTime, $ids=array()){
        $condition = "`true_repayment_time` >= UNIX_TIMESTAMP('".$dateTime."') AND `true_repayment_time` <= UNIX_TIMESTAMP('".$dateTime." 23:59:59')";
        if(!empty($ids)){
            $condition .= "AND `id` IN(".implode(',',  $ids).")";
        }
        $result = array();
        $query = self::find()
                ->select(['amount'=>"count(`id`)", 
                          'Principal'=>"SUM(IF(`true_total_money` > `principal`,`principal`,`true_total_money`))", 
                          'late_fee'=>"SUM(IF(`true_total_money` > `principal`,(`true_total_money` - `principal`), 0 ))", 
                          'payTime'=>"FROM_UNIXTIME(`true_repayment_time`,'%Y-%m-%d')",
                          'id']
                          )
                ->where($condition);
       
        $res = $query
                // ->groupBy('payTime')
                // ->createCommand()->getRawSql();
                ->asArray()->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    //新增还款数量、本金、滞纳金：
    public static function daily_detail($dateTime, $ids=array()){
        $condition = "`true_repayment_time` >= UNIX_TIMESTAMP('".$dateTime."') AND `true_repayment_time` <= UNIX_TIMESTAMP('".$dateTime." 23:59:59')";
        if(!empty($ids)){
            $condition .= "AND `id` IN(".implode(',',  $ids).")";
        }
        $result = array();
        $query = self::find()
                ->select([
                          'Principal'=>"IF(`true_total_money` > `principal`,`principal`,`true_total_money`)", 
                          'late_fee'=>"IF(`true_total_money` > `principal`,(`true_total_money` - `principal`), 0 )", 
                          'id']
                          )
                ->where($condition);
        
        $res = $query
                // ->groupBy('payTime')
                // ->createCommand()->getRawSql();
                ->asArray()->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    /**
     *返回逾期订单，默认返回逾期1天的订单
     *Author:李振国
     *Date:2016年10月28日
     *@param int $overdueDay 逾期天数
     *@param string $condition 其他条件，默认未还款
     */
    public static function overdue($overdueDay = ' = 1', $condition = 'status != 4'){
        $condition = '`overdue_day` '.$overdueDay.' AND '.$condition;//条件：逾期&未还款
        return self::find()->where($condition)->all();
    }
    public static function overdue_range($overdueDay = ' = 1', $start = 0, $limit = 1000){
        $condition = '`overdue_day` '.$overdueDay.' AND '.$condition = 'status !=4';//条件：逾期&未还款
        return self::find()->where($condition)->offset($start)->limit($limit)->orderBy(['id'=>SORT_ASC])->all();
    }

    public static function overdue_amount($overdueDay = ' = 1', $condition = 'status != 4'){
        $condition = '`overdue_day` '.$overdueDay.' AND '.$condition;//条件：逾期&未还款
        return self::find()->where($condition)->count();
    }
    public static function overdue_amount_rd($overdueDay = ' = 1', $condition = 'status !=4 '){
        $condition = '`overdue_day` '.$overdueDay.' AND '.$condition;//条件：逾期&未还款
        return self::find()->where($condition)->select("count(id)")->scalar(self::getDb_rd());
    }

    /**
     *根据入催时间，返回已还本金总额
     */
    public static function true_repay_by_collectionTime($cuiTime = 0){
        $where = " plan_fee_time >= ".(strtotime(date('Y-m-d 0:0:0', $cuiTime)) - 24*3600) ." AND plan_fee_time <= ".(strtotime(date('Y-m-d 23:59:59', $cuiTime)) - 24*3600)." AND `status` = ".self::STATUS_REPAY_COMPLETE;
        return self::find()->select("SUM(IF(true_total_money > principal,principal,true_total_money)) AS `total`")->where($where)->scalar(self::getDb_rd());
    }


    /**
     *根据逾期等级，返回相应逾期天数范围的订单,默认反应逾期等级为S1（对应逾期天数1~10天）的订单
     *附加条件：已催收成功订单除外
     */
    public static function overdue_days_by_level($level = LoanCollectionOrder::LEVEL_ONE, $start=0, $limit = 0){
        $result = array();
        if(!array_key_exists($level, LoanCollectionOrder::$level)) return $result;
        $_level = LoanCollectionOrder::$overdue_day[$level];
        $query = self::find()->where('overdue_day >='.$_level['min'].' AND overdue_day <='.$_level['max'].' AND status != '.self::STATUS_REPAY_COMPLETE)->orderBy(['id'=>SORT_ASC]);
        if(!empty($limit)){
            $query->offset($start)->limit($limit);
        }
        $ret = $query->all();

        if(!empty($ret)){
            foreach ($ret as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
        
    }

    public static function id($id){
        return self::find()->where(['id'=>$id])->one();
    }

    public static function id_rd($id){
        return self::find()->where(['id'=>$id])->one(self::getDb_rd());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_loan_order_repayment}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
     public static function getDb_rd()
    {
        return Yii::$app->get('db_kdkj_rd');
    }

    public static function getDbMhk()
    {
        return Yii::$app->get('db_mhk');
    }

    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), ['id' => 'user_id']);
    }

    public function getUserLoanOrder()
    {
        return $this->hasOne(UserLoanOrder::className(), ['id' => 'order_id']);
    }
    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), ['id' => 'card_id']);
    }
    
    public function getOrderDelay()
    {
        return $this->hasOne(UserLoanOrderDelay::className(), ['order_id' => 'order_id']);
    }

    //关联注册表
    public function getUserRegisterInfo()
    {
        return $this->hasOne(UserRegisterInfo::className(), ['user_id' => 'user_id']);
    }

    /**
     *未还款的还款单
     */
    public static function unpayed($start = 0, $limit = 999999){
        return self::find()->select("*")->where("`status` != ".self::STATUS_REPAY_COMPLETE ." AND  `is_overdue` > 0")->offset($start)->limit($limit)->orderBy(['id'=>SORT_DESC])->all();
    }

    public static function unpayed_count(){
        return self::find()->select("count(`id`)")->where("`status` != ".self::STATUS_REPAY_COMPLETE." AND  `is_overdue` > 0")->scalar();
    }

    /**
     *根据订单ID，返回还款信息
     *结果数组以订单ID作为下标
     */
    public static function array_order_ids($ids = array()){
        $result = array();
        $res = self::find()->where('`order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->asArray()->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['order_id']] = $item;
            }
        }
        return $result;
    }

    /**
     * 根据订单ID，及条件查询逾期订单金额及订单数量
     * @param array $order_ids
     * @param string $condition
     * @return array|null|ActiveRecord
     */
    public static function getOverdue($order_ids, $days) {
        $over = UserLoanOrderRepayment::find()
            ->select('sum(principal) as total_principal, count(id) as count')
            ->where(['in', 'order_id', $order_ids])
            ->andWhere(['>', 'overdue_day', $days])
            ->andWhere(['<', 'plan_fee_time', strtotime(date('Y-m-d'))-$days*86400])
            ->asArray()
            ->one();
        $result['principal'] = !empty($over['total_principal']) ?$over['total_principal']: 0;   //金额
        $result['count'] = !empty($over['count']) ?$over['count']: 0;  //订单数
        return $result;
    }

    /**
     * 根据订单ID，及条件查询放款订单金额及订单数量
     * @param array $order_ids
     * @param string $condition
     * @return array|null|ActiveRecord
     */
    public static function getAllOrders($order_ids, $days) {
        $over = UserLoanOrderRepayment::find()
            ->select('sum(principal) as total_principal, count(id) as count')
            ->where(['in', 'order_id', $order_ids])
            ->andWhere(['<', 'plan_fee_time', strtotime(date('Y-m-d'))-$days*86400])
            ->asArray()
            ->one();
        $result['principal'] = $over['total_principal'] ?: 0;
        $result['count'] = $over['count'] ?: 0;
        return $result;
    }

    public static function getTotalMoney($order_ids) {
        $over = UserLoanOrderRepayment::find()
            ->select('sum(true_total_money) as total_repayment, sum(principal) as total_principal')
            ->where(['in', 'order_id', $order_ids])
            ->asArray()
            ->one();
        $result['total_repayment'] = $over['total_repayment'] ?: 0;  //还款总额
        $result['total_principal'] = $over['total_principal'] ?: 0;  //借款总额
        return $result;
    }

    public static function alterOrderStatus($order_id = 0,$repayment_id = 0){
        $UserLoanOrder = UserLoanOrder::findOne($order_id);
        if($repayment_id){
            $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne($repayment_id);
        }else{
            $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id'=>$UserLoanOrder->id]);
        }
        if(!$UserLoanOrder || !$UserLoanOrderRepayment){
            return false;
        }
        if($UserLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE || $UserLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
            return false;
        }
        if($UserLoanOrderRepayment->true_total_money > 0 && $UserLoanOrderRepayment->true_total_money < $UserLoanOrderRepayment->total_money){
            $UserLoanOrder->status = UserLoanOrder::STATUS_PARTIALREPAYMENT;
        }else{
            $UserLoanOrder->status = UserLoanOrder::STATUS_LOAN_COMPLING;
        }
        if($UserLoanOrderRepayment->is_overdue){
            $UserLoanOrderRepayment->status =UserLoanOrderRepayment::STATUS_OVERDUE;
        }else{
            $UserLoanOrderRepayment->status =UserLoanOrderRepayment::STATUS_NORAML;
        }
        if($UserLoanOrderRepayment->save() && $UserLoanOrder->save()){
            return true;
        }
    }
    //查询成功借款的次数
    public static function CheckSuccessLoan($person_id){
        $count = self::find()->where(['user_id'=>$person_id,'status'=>4])->count();
        return $count;
    }
    //最近一次有逾期三天的用户
    public static function CheckRepaymentOver($person_id){
        $res = self::find()->where(['user_id'=>$person_id,'is_overdue'=>1])->andWhere(['>','overdue_day',3])->orderBy('id desc')->count();
        return $res;
    }
}