<?php

namespace common\models\loan;

use Yii;
use yii\base\Exception;
use common\models\loan\LoanCollection;
use common\models\LoanPerson;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoanOrder;
use backend\models\UserGroup;
use common\models\loan\UserCompany;
use common\models\loan\LoanCollectionStatusChangeLog;
use common\models\loan\LoanCollectionSuggestionChangeLog;
use common\helpers\MailHelper;
use common\helpers\ToolsUtil;
use common\services\loan_collection\UserLoanOrderRepaymentService;
use yii\db\Connection;

/**
 * This is the model class for table "{{%loan_collection_order}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $user_loan_order_id
 * @property integer $user_loan_order_repayment_id
 * @property string $dispatch_name
 * @property integer $dispatch_time
 * @property integer $current_collection_admin_user_id
 * @property integer $current_overdue_level
 * @property integer $customer_type
 * @property integer $s1_approve_id
 * @property integer $s2_approve_id
 * @property integer $s3_approve_id
 * @property integer $s4_approve_id
 * @property integer $status
 * @property integer $promise_repayment_time
 * @property integer $last_collection_time
 * @property integer $next_loan_advice
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $operator_name
 * @property string $remark
 */
class LoanCollectionOrder extends \yii\db\ActiveRecord
{
    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }

    public $amount = 0;
    const LIMIT_OVERDUE_DAY = 3; //限制可看到的联系人数量的逾期天数

    const LEVEL_ONE = 1;
    const LEVEL_TWO = 2;
    const LEVEL_THREE = 3;
    const LEVEL_FOUR = 4;
    const LEVEL_FIVE = 5;
    const OUTSIDE_ID = 3;  //达旷机构id   秒还卡订单目前只派给达旷
    // const LEVEL_SIX = 6;

    //逾期订单的逾期阶段：
    public static $level = [
        self::LEVEL_ONE =>'S1',
        self::LEVEL_TWO =>'S2',
        self::LEVEL_THREE =>'S3',
        self::LEVEL_FOUR =>'S4',
        self::LEVEL_FIVE =>'S5',
        // self::LEVEL_SIX =>'S6',
    ];
    const IS_NEW = 0;  // 新用户
    const IS_OLD = 1;  // 老用户
    const  SUB_TYPE_BT = 1; //白条订单
    const  SUB_TYPE_MHK = 2;  // 秒还卡订单
    public static $customer_types = [
        self::IS_NEW =>"新",
        self::IS_OLD =>"老"
    ];
    //订单项目来源
    public static $sub_order_type = [
        self::SUB_TYPE_BT =>APP_NAMES,
        self::SUB_TYPE_MHK =>"秒还卡"
    ];
    public static function queryCondition($condition,$order=true,$orderBy = ['id'=>SORT_DESC]){
        if ($order) {
            return self::find()
                ->from(self::tableName() . 'use index(idx_owner_id)')
                ->where($condition)
                ->orderBy($orderBy);
        }
        return self::find()
            ->from(self::tableName() . 'use index(idx_owner_id)')
            ->where($condition);
    }
    public static function array_select_condition($select="*",$condition="1=1"){
        return self::find()->select($select)->where($condition)->asArray()->all();
    }
    /**
     *返回指定入催时间,入催单量
     */
    public static function amount_rucui_date($date){
        $day_start = strtotime($date);
        $day_end = strtotime($date)+86400;
        return self::find()->select("count(`id`)")->where("`created_at` >= ".$day_start." AND `created_at` < ".$day_end)->scalar();
    }

    /**
     *返回指定入催时间，催收中单量
     */
    public static function amount_incollection_rucui_date($date){
        $day_start = strtotime($date);
        $day_end = strtotime($date)+86400;
        return self::find()->select("count(`id`)")->where("`created_at` >= ".$day_start." AND `created_at` < ".$day_end." AND `status` = ".self::STATUS_COLLECTION_PROGRESS)->scalar();
    }

    /**
     * 根据给定的还款ID，返回在催收表中的还款ID
     */
    public static function get_valid_repayIds ($ids = [], $email = false) {
        if (empty( $ids )) {
            return [];
        }
        $res = self::find()->select( "user_loan_order_repayment_id" )->where(
            "`status`!=" . self::STATUS_COLLECTION_FINISH .
            " AND  user_loan_order_repayment_id IN (" . implode( ',', $ids ) . ")"
        )->all();
        if (empty( $res )) {
            return [];
        }
        $tmp = array_column( $res, 'user_loan_order_repayment_id' );
        if ($email) {
            MailHelper::send(NOTICE_MAIL, '有效还款单ID(未去重总量：' . count( $tmp ) . ")", '' );
        }

        return array_unique( $tmp );
    }
    /**
     * 根据给定的还款ID，返回在催收表中的还款ID(refine)
     */
    public static function get_valid_repayId ($ids = [], $email = false) {
        if (empty( $ids )) {
            return [];
        }
        $res = self::find()->select( "user_loan_order_repayment_id" )->where(
            "`status`=" . self::STATUS_COLLECTION_FINISH .
            " AND  user_loan_order_repayment_id IN (" . implode( ',', $ids ) . ")"
        )->all();
        if (empty( $res )) {
            return [];
        }
        $tmp = array_column( $res, 'user_loan_order_repayment_id' );
        if ($email) {
            MailHelper::send(NOTICE_MAIL, '有效还款单ID(未去重总量：' . count( $tmp ) . ")", '' );
        }

        return array_unique( $tmp );
    }
    public static function get_valid_repayId_mhk ($ids = [], $email = false) {
        if (empty( $ids )) {
            return [];
        }
        $res = self::find()->select( "user_loan_order_repayment_id" )->where(
            "`status`=" . self::STATUS_COLLECTION_FINISH .
            " AND  user_loan_order_repayment_id IN (" . implode( ',', $ids ) . ")"
        )->all(self::getDbMhk());
        if (empty( $res )) {
            return [];
        }
        $tmp = array_column( $res, 'user_loan_order_repayment_id' );
        if ($email) {
            MailHelper::send(NOTICE_MAIL, '有效还款单ID(未去重总量：' . count( $tmp ) . ")", '' );
        }

        return array_unique( $tmp );
    }
    //返回指定派单时间后，不同催收机构、不同分组的派单数量
    public static function amount_dispatchTime_after($start){
        return self::find()->select([
                'amount' => "count(`id`)",
                'company_id' => 'outside',
                'group_id' => 'current_overdue_group',
            ])->where("`dispatch_time` >=".$start)
            ->groupBy(["outside", "current_overdue_group"])
            ->asArray()
            ->all(self::getDb_rd());
    }
    public static function amount_dispatchTime_after_mhk($start){
        return self::find()->select([
            'amount' => "count(`id`)",
            'company_id' => 'outside',
            'group_id' => 'current_overdue_group',
        ])->where("`dispatch_time` >=".$start)
            ->groupBy(["outside", "current_overdue_group"])
            ->asArray()
            ->all(self::getDbMhk());
    }

    //获取所有入催时间：
    public static function cui_dates(){

        return self::find()->select(["createTime"=>"FROM_UNIXTIME(created_at,'%Y-%m-%d')"])->groupBy("createTime")->orderBy(["createTime"=>SORT_DESC])->asArray()->all(self::getDb_rd());
    }

    /**
     *根据入催时间，返回入催订单信息
     */
    public static function cui_time($cuiTime = 0){
        $where = "`created_at` >= " . strtotime(date('Y-m-d 0:0:0', $cuiTime)) . " AND `created_at` <= ".strtotime(date('Y-m-d 23:59:59', $cuiTime));
        return self::find()->select('*')->where($where)->all(self::getDb_rd());
    }

    //返回催收成功，但无催收建议的记录
    public static function no_suggest(){
        // $query = "SELECT id,user_loan_order_id,user_loan_order_repayment_id,`status` FROM ".LoanCollectionOrder::tableName()." WHERE user_loan_order_repayment_id IN(SELECT id FROM " . UserLoanOrderRepayment::tableName() . " WHERE `status`=" . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE . ") AND next_loan_advice = ".LoanCollectionOrder::RENEW_DEFAULT." ORDER BY user_loan_order_id DESC;";
        // $loanOrders = Yii::$app->db_kdkj_rd->createCommand($query)->queryAll();

        return self::find()->select(['id', 'user_loan_order_id', 'user_loan_order_repayment_id', 'status'])
                           ->where(['next_loan_advice'=>self::RENEW_DEFAULT, 'status'=>self::STATUS_COLLECTION_FINISH])
                           ->asArray()->all(self::getDb_rd());
    }

    /**
     *指定日期前的还款ID
     *@return array 还款ID数组
     */
    public static function repaymentIds_between_dateTime($dateStart = 0, $dateEnd = 0, $offset = 0, $limit = 0,$db=true){
        ini_set('memory_limit', '-1');
        $result = array();
        $condition = "";
        if(!empty($dateStart)){
            if(is_integer($dateStart)){
                $condition .= "created_at >=".$dateStart;
            }else{
                $condition .= "created_at >= UNIX_TIMESTAMP('" . $dateStart . " 0:0:0')";
            }
        }

        if(!empty($dateEnd)){
            if(!empty($condition)){
                if(is_integer($dateEnd)){
                    $condition .= " AND created_at <=".$dateEnd;
                }else{
                    $condition .= " AND created_at <= UNIX_TIMESTAMP('" . $dateEnd . " 23:59:59')";
                }

            }else{
                if(is_integer($dateEnd)){
                    $condition .= "created_at <=".$dateEnd;
                }else{
                    $condition .= "created_at <= UNIX_TIMESTAMP('" . $dateEnd . " 23:59:59')";
                }
            }

        }
        if(!$db){
            $query = self::find()->select('user_loan_order_repayment_id')->where($condition)->orderBy(['id'=>SORT_DESC]);
            if(!empty($limit)){
                $query->offset($offset)->limit($limit);
            }
            $res = $query->all(self::getDbMhk());
        }else{
            $query = self::find()->select('user_loan_order_repayment_id')->where($condition)->orderBy(['id'=>SORT_DESC]);
            if(!empty($limit)){
                $query->offset($offset)->limit($limit);
            }
            $res = $query->all(self::getDb_rd());
        }

        // $res = self::find()->select('user_loan_order_repayment_id')->where("created_at <= UNIX_TIMESTAMP('".$dateEnd." 23:59:59')")->all(self::getDb_rd());
        if(!empty($res)){
            $result = array_column($res, 'user_loan_order_repayment_id');
        }

        return $result;
    }



    /**
     *根据用户ID，返回其当前任务
     */
    public static function mission_user($userId = ''){
        $uid = empty($userId) ? Yii::$app->user->id : $userId;
        $condition = 'current_collection_admin_user_id = '.intval($uid)." AND dispatch_time > ".strtotime(date("Y-m-1"));
        $res = self::find()->select("`status`, count(id) AS `amount`")->where($condition)->groupBy(['`status`'])->all(self::getDb_rd());
        $result = array();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['status']] = $item['amount'];
            }
        }
        if(empty($result)){
            $res = self::find()->select("`status`, count(id) AS `amount`")->where($condition)->groupBy(['`status`'])->all(self::getDbMhk());
            if(!empty($res)){
                foreach ($res as $key => $item) {
                    $result[$item['status']] = $item['amount'];
                }
            }
        }
        return $result;
    }

    /**
     *返回指定催收机构，指定逾期等级未催收成功订单
     *@author 李振国
     */
    public static function unsuccess($outside = 0, $overdue_level = 1){
        $condition = "`status` !=" . self::STATUS_COLLECTION_FINISH . " AND outside = " . $outside . " AND current_overdue_level = " . $overdue_level;
        return self::find()->where($condition)->all(self::getDb_rd());
    }

    /**
     *入催
     *@author 李振国
     */
    public static function ru_cui($user_loan_order_repayment, $remark = ''){
        $transaction = Yii::$app->db_assist->beginTransaction();
        try {
            $i = 1;
            $count = count($user_loan_order_repayment);
            foreach($user_loan_order_repayment as $item) {
                $loan_collection_order = LoanCollectionOrder::find()->where('user_id = '.$item['user_id'].' and user_loan_order_id = '.$item['order_id'].' and user_loan_order_repayment_id = '.$item['id'].' and status != '.LoanCollectionOrder::STATUS_COLLECTION_FINISH)->one();
                echo '入催进度：'.$i++.'/'.count($user_loan_order_repayment)."\r\n";
                if($loan_collection_order) {
                    continue;
                }
                //入催订单
                $loan_collection_order = new LoanCollectionOrder();
                $loan_collection_order->user_id = $item['user_id'];
                $loan_collection_order->user_loan_order_id = $item['order_id'];
                $loan_collection_order->user_loan_order_repayment_id = $item['id'];
                $loan_collection_order->dispatch_name = "";
                $loan_collection_order->dispatch_time = 0;
                $loan_collection_order->current_collection_admin_user_id = 0;
                $loan_collection_order->current_overdue_level = LoanCollectionOrder::LEVEL_ONE;
                $loan_collection_order->s1_approve_id = 0;
                $loan_collection_order->s2_approve_id = 0;
                $loan_collection_order->s3_approve_id = 0;
                $loan_collection_order->s4_approve_id = 0;
                $loan_collection_order->status = LoanCollectionOrder::STATUS_WAIT_COLLECTION;
                $loan_collection_order->promise_repayment_time = 0;
                $loan_collection_order->last_collection_time = 0;
                $loan_collection_order->next_loan_advice = "";
                $loan_collection_order->created_at = time();
                $loan_collection_order->updated_at = time();
                $loan_collection_order->operator_name = "系统";
                $loan_collection_order->remark = empty($remark) ? "系统自动入催" : $remark;
                $loan_collection_order->current_overdue_group = 0;//逾期1天，进催收分组S1
                if(!$loan_collection_order->save()) throw new Exception("入催失败, 还款ID：".$item['id'].', 借款ID：'.$item['order_id']);


                //状态流转换
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                $loan_collection_status_change_log->loan_collection_order_id = $loan_collection_order->id;
                $loan_collection_status_change_log->before_status = LoanCollectionOrder::STATUS_WAIT_COLLECTION;
                $loan_collection_status_change_log->after_status = LoanCollectionOrder::STATUS_WAIT_COLLECTION;
                $loan_collection_status_change_log->type = LoanCollectionOrder::TYPE_INPUT_COLLECTION;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = "系统";
                $loan_collection_status_change_log->remark = empty($remark) ? "系统自动入催" : $remark;
                if(!$loan_collection_status_change_log->save()) throw new Exception("记录入催失败，入催ID：".$loan_collection_order->id);
            }

            $transaction->commit();
        } catch (Exception $e){
            $transaction->rollBack();
            echo 'failed:'.$e->getMessage();
            Yii::error($e->getMessage(), 'collection');
            return false;
            exit;
        }
        return true;
    }

    /**
     *重置催收单
     *目标：清机构、清人、清分组、状态为【待催收】
     *@param array $loanOrders 要回收的催单
     *@param string $remark 备注信息
     *@param int $newStatus 回收后的状态，默认为【待催收】
     */
    public static function reset($loanOrders = array(), $remark = '回收未催收成功订单，为重新派单准备', $newStatus = self::STATUS_WAIT_COLLECTION){
        $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
        try{
            $i = 1;
            $amount = count($loanOrders);
            foreach ($loanOrders as $key => $item) {
                echo '回收进度：'.$i++."/".$amount."\r\n";
                if(!is_object($item)){
                    $item = self::id($item['id']);
                }
                $today = date('Y-m-d',time());
                $month = ToolsUtil::getMonthNum($today, date("Y-m-d", $item->created_at));
                if($item->current_overdue_group == 5 && $month < 6)  //催收机构id为12的机构特殊处理
                {
                     continue;   //催收订单为M3+并且派单时间没有达到三个月期限
                }
                if($item->current_overdue_group == 5 && $month == 6)
                {
                    $item->bad_loan = 1; //   坏账标识，不再委派
                }
                $before_status = $item->status;

                $item->outside = 0;
                $item->current_overdue_group = 0;
                $item->current_collection_admin_user_id = 0;
                if($before_status == self::STATUS_STOP_URGING){
                    $item->status = $before_status;
                }else{
                    $item->status = $newStatus;
                }
                if(!$item->save()){
                    throw new Exception("重置催收单失败，催收单ID：".$item['id'].",还款单ID：".$item['user_loan_order_id']);
                }

                //状态流转记录：
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                $loan_collection_status_change_log->loan_collection_order_id = $item->id;
                $loan_collection_status_change_log->before_status = $before_status;
                $loan_collection_status_change_log->after_status = $newStatus;
                $loan_collection_status_change_log->type = self::TYPE_RECYCLE;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = "系统";
                $loan_collection_status_change_log->remark = $remark . "(还款单ID：".$item['user_loan_order_id'].")";
                if(!$loan_collection_status_change_log->save()) throw new Exception("状态流转记录失败，催收单ID：".$item['id'].",还款单ID：".$item['user_loan_order_id']);

            }
            $transaction->commit();

        }catch(\Exception $e){
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'collection');
            echo $e->getMessage();
            return false;

        }
        return true;
    }

    public static function reset_mhk($loanOrders = array(), $remark = '回收未催收成功订单，为重新派单准备', $newStatus = self::STATUS_WAIT_COLLECTION){
        $transaction= Yii::$app->db_mhk_assist->beginTransaction();//创建事务
        try{
            $i = 1;
            $amount = count($loanOrders);
            foreach ($loanOrders as $key => $item) {
                echo '回收进度：'.$i++."/".$amount."\r\n";
                $item = LoanCollectionOrder::find()->where(['id'=>$item['id']])->one(Yii::$app->get('db_mhk_assist'));
                if($item){
                    $item::$connect_name = 'db_mhk_assist';
                }
                $today = date('Y-m-d',time());
                $month = ToolsUtil::getMonthNum($today, date("Y-m-d", $item->created_at));
                if($item->current_overdue_group == 5 && $month < 6 )
                {
                    continue;   //催收订单为M3+并且派单时间没有达到三个月期限
                }
                if($item->current_overdue_group == 5 && $month == 6)
                {
                    $item->bad_loan = 1; //   坏账标识，不再委派
                }
                $before_status = $item->status;
                $item->outside = 0;
                $item->current_overdue_group = 0;
                $item->current_collection_admin_user_id = 0;
                $item->status = $newStatus;
                if(!$item->save()){
                    throw new Exception("重置催收单失败，催收单ID：".$item['id'].",还款单ID：".$item['user_loan_order_id']);
                }

                //状态流转记录：
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog('db_mhk_assist');
                $loan_collection_status_change_log->loan_collection_order_id = $item->id;
                $loan_collection_status_change_log->before_status = $before_status;
                $loan_collection_status_change_log->after_status = $newStatus;
                $loan_collection_status_change_log->type = self::TYPE_RECYCLE;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = "系统";
                $loan_collection_status_change_log->remark = $remark . "(还款单ID：".$item['user_loan_order_id'].")";
                if(!$loan_collection_status_change_log->save()) throw new Exception("状态流转记录失败，催收单ID：".$item['id'].",还款单ID：".$item['user_loan_order_id']);

            }
            $transaction->commit();

        }catch(\Exception $e){
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'collection');
            echo $e->getMessage();
            return false;

        }
        return true;
    }
    /**
     *替换上面的静态变量$level，改用数据库中内容
     *若提供分组ID，则返回分组名称，否则返回分组数组
     *Author:李振国
     *Date：2016/10/27
     *备注：废弃，此方法返回的是催单分组，与上面的逾期阶段不同（虽然都叫S1，S2）
     */
    // public static function level($group_id = ''){
    //     $lists = UserGroup::lists();
    //     foreach ($lists as $key => $item) {
    //         $lists[$key] = $item['title'];
    //     }
    //     return empty($group_id) ? $lists : (isset($lists[$group_id]) ? $lists[$group_id] : '未知分组');
    // }

    /**
     *根据还款表订单逾期天数，更新催收表逾期等级
     *@param array $repaymentOrders 还款表订单
     *@param string $level 要更新成的订单逾期等级
     *@return int
     */
    public static function update_overdue_level($repaymentOrders = array(), $newLevel = ''){
        $count = 0;//成功更新数量
        $success_ids = array();//成功更新的还款单ID
        if(!array_key_exists($newLevel, self::$level) || empty($repaymentOrders)) return array('count'=>0, 'ids'=>array());
        $i = $total = count($repaymentOrders);


        /*
        //策略一：
        foreach ($repaymentOrders as $key => $order) {
            echo ($i--)."/".$total."\r\n";
            $loanOrders = self::order_id($order['order_id']);
        */
            //策略二：
            $ids = array_column($repaymentOrders, 'order_id');
            $loanOrders = self::order_ids($ids);
            foreach ($loanOrders as $key => $loanOrder) {

                if(empty($loanOrder) || ($loanOrder->current_overdue_level == $newLevel) || ($loanOrder['status'] == self::STATUS_COLLECTION_FINISH)){
                    //催单不存在，或逾期等级正确，或已催收成功：

                    // echo '跳过订单：'.$loanOrder['id']."\r\n";
                    continue;
                }

                $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
                try{
                    $old_level = $loanOrder->current_overdue_level;//旧的逾期等级
                    $loanOrder->current_overdue_level = $newLevel;//新的逾期等级

                    if($old_level > $newLevel){
                        throw new Exception("更新订单逾期等级时遇到异常订单：催单ID：".$loanOrder->id."，借款订单ID：".$loanOrder['user_loan_order_id'].",原逾期等级：".$old_level.",新逾期等级：".$newLevel);
                    }

                    if(!$loanOrder->save()){
                        throw new Exception("更新订单逾期等级失败，催单ID：".$loanOrder->id."，借款订单ID：".$loanOrder['user_loan_order_id']);
                    }

                    //状态流转换
                    $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                    $loan_collection_status_change_log->loan_collection_order_id = $loanOrder['id'];
                    $loan_collection_status_change_log->before_status = $loanOrder['status'];
                    $loan_collection_status_change_log->after_status = $loanOrder['status'];
                    $loan_collection_status_change_log->type = self::TYPE_LEVEL_CHANGE;
                    $loan_collection_status_change_log->created_at = time();
                    $loan_collection_status_change_log->operator_name = "系统";
                    $loan_collection_status_change_log->remark = "逾期等级变更：催单ID：".$loanOrder->id.", 借款订单ID：".$loanOrder['user_loan_order_id'].', '.self::$level[$old_level]." ==> ".self::$level[$newLevel];
                    if(!$loan_collection_status_change_log->save()){
                         throw new Exception("状态流转换失败，催单ID：".$loanOrder['id'].",借款订单ID：".$loanOrder['user_loan_order_id']);
                    }

                    $count++;
                    $success_ids[] = $loanOrder['user_loan_order_repayment_id'];
                    echo "更新订单逾期等级成功，催单ID：".$loanOrder->id.",借款单ID：".$loanOrder['user_loan_order_id'].",原逾期等级：".$old_level.",新逾期等级：".$newLevel."\r\n";
                    $transaction->commit();
                }catch(\Exception $e){
                    $transaction->rollBack();
                    // echo $e->getMessage();
                    Yii::error($e->getMessage(), 'collection');
                    // return false;
                   exit;
                }
            }
        /*
        //策略一：
        }
        */
        return array('count'=>$count, 'ids'=>$success_ids);
    }

    public static function update_overdue_level_mhk($repaymentOrders = array(), $newLevel = ''){
        $count = 0;//成功更新数量
        $success_ids = array();//成功更新的还款单ID
        if(!array_key_exists($newLevel, self::$level) || empty($repaymentOrders)) return array('count'=>0, 'ids'=>array());
        $i = $total = count($repaymentOrders);


        /*
        //策略一：
        foreach ($repaymentOrders as $key => $order) {
            echo ($i--)."/".$total."\r\n";
            $loanOrders = self::order_id($order['order_id']);
        */
        //策略二：
        $ids = array_column($repaymentOrders, 'order_id');
        $loanOrders = self::order_ids_mhk($ids);
        foreach ($loanOrders as $key => $loanOrder) {
            $loanOrder::$connect_name = 'db_mhk_assist';
            if(empty($loanOrder) || ($loanOrder->current_overdue_level == $newLevel) || ($loanOrder['status'] == self::STATUS_COLLECTION_FINISH)){
                //催单不存在，或逾期等级正确，或已催收成功：

                // echo '跳过订单：'.$loanOrder['id']."\r\n";
                continue;
            }

            $transaction= Yii::$app->db_mhk_assist->beginTransaction();//创建事务
            try{
                $old_level = $loanOrder->current_overdue_level;//旧的逾期等级
                $loanOrder->current_overdue_level = $newLevel;//新的逾期等级

                if($old_level > $newLevel){
                    throw new Exception("更新订单逾期等级时遇到异常订单：催单ID：".$loanOrder->id."，借款订单ID：".$loanOrder['user_loan_order_id'].",原逾期等级：".$old_level.",新逾期等级：".$newLevel);
                }

                if(!$loanOrder->save()){
                    throw new Exception("更新订单逾期等级失败，催单ID：".$loanOrder->id."，借款订单ID：".$loanOrder['user_loan_order_id']);
                }

                //状态流转换
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog('db_mhk_assist');
                $loan_collection_status_change_log->loan_collection_order_id = $loanOrder['id'];
                $loan_collection_status_change_log->before_status = $loanOrder['status'];
                $loan_collection_status_change_log->after_status = $loanOrder['status'];
                $loan_collection_status_change_log->type = self::TYPE_LEVEL_CHANGE;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = "系统";
                $loan_collection_status_change_log->remark = "逾期等级变更：催单ID：".$loanOrder->id.", 借款订单ID：".$loanOrder['user_loan_order_id'].', '.self::$level[$old_level]." ==> ".self::$level[$newLevel];
                if(!$loan_collection_status_change_log->save()){
                    throw new Exception("状态流转换失败，催单ID：".$loanOrder['id'].",借款订单ID：".$loanOrder['user_loan_order_id']);
                }

                $count++;
                $success_ids[] = $loanOrder['user_loan_order_repayment_id'];
                echo "更新订单逾期等级成功，催单ID：".$loanOrder->id.",借款单ID：".$loanOrder['user_loan_order_id'].",原逾期等级：".$old_level.",新逾期等级：".$newLevel."\r\n";
                $transaction->commit();
            }catch(\Exception $e){
                $transaction->rollBack();
                // echo $e->getMessage();
                Yii::error($e->getMessage(), 'collection');
                // return false;
                exit;
            }
        }
        /*
        //策略一：
        }
        */
        return array('count'=>$count, 'ids'=>$success_ids);
    }
    /**
     *反回待催收订单
     *Author:李振国
     *Date：2016/10/28
     */
    public static function wait_collection(){
       return  self::find()->where(['status'=>self::STATUS_WAIT_COLLECTION])->all();

    }
    public static function wait_collection_rd($where = ''){
        $condition = "`status`=".self::STATUS_WAIT_COLLECTION." AND `current_overdue_group`=0 AND `outside`=0 AND `current_collection_admin_user_id`=0 ";
        if(!empty($where))  $condition .= " AND ".$where;
        // echo self::find()->where($condition)->createCommand()->getRawSql();exit;
       return  self::find()->where($condition)->all(self::getDb_rd());

    }

    /**
     *根据订单ID返回催收记录
     *@param int $orderId 订单ID
     *@param boolean $latest 是否只返回最新一条，默认返回所有催收记录
     */
    public static function order_id($orderId, $latest = false){
        if($latest) return self::find()->where(['user_loan_order_id'=>$orderId])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one(self::getDb_rd());
        return self::find()->where(['user_loan_order_id'=>$orderId])->orderBy(['id'=>SORT_DESC])->all(self::getDb_rd());
    }

    public static function order_ids($ids = array()){
        return self::find()->where('`user_loan_order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->all(self::getDb_rd());

    }
    //秒还卡
    public static function order_ids_mhk($ids = array()){
        return self::find()->where('`user_loan_order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->all(Yii::$app->get('db_mhk_assist'));

    }
    public static function order_repayment_id($repaymentId, $latest = false){
        if($latest) return self::find()->where(['user_loan_order_repayment_id'=>$repaymentId])->orderBy(['id'=>SORT_DESC])->limit(1)->one(self::getDb_rd());
        return self::find()->where(['user_loan_order_repayment_id'=>$repaymentId])->orderBy(['id'=>SORT_DESC])->all(self::getDb_rd());
    }
    /**
     *根据订单ID，返回催单信息
     *结果数组以订单ID作为下标
     */
    public static function array_order_ids($ids = array()){
        $result = array();
        // $res = self::find()->where('`user_loan_order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->asArray()->all(self::getDb_rd());
        $res = self::getDb()->createCommand(" SELECT * FROM ( SELECT * FROM ".self::tableName()." WHERE `user_loan_order_id` IN (".implode(",", $ids).") ORDER BY `id` DESC) AS a GROUP BY `user_loan_order_id`")->queryAll();//去重
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['user_loan_order_id']] = $item;
            }
        }
        return $result;
    }
    public static function array_order_ids_mhk($ids = array()){
        $result = array();
        // $res = self::find()->where('`user_loan_order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->asArray()->all(self::getDb_rd());
        $res = self::getDbMhk()->createCommand(" SELECT * FROM ( SELECT * FROM ".self::tableName()." WHERE `user_loan_order_id` IN (".implode(",", $ids).") ORDER BY `id` DESC) AS a GROUP BY `user_loan_order_id`")->queryAll();//去重
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['user_loan_order_id']] = $item;
            }
        }
        return $result;
    }

     public static function last_order_ids($ids = array()){
        // return self::find()->where('`user_loan_order_id` IN ('.implode(",", $ids).')')->orderBy(['id'=>SORT_DESC])->all(self::getDb_rd());
        $sql = "SELECT * FROM (SELECT * FROM ".self::tableName()."  WHERE `user_loan_order_id` IN (".implode(",", $ids).")  ORDER BY `id` DESC)  AS a group by a.`user_loan_order_id`";
        return self::getDb_rd()->createCommand($sql)->queryAll();//有多条催收记录时，取最新一条
    }

    public static function id($id){
         return self::find()->where(['id'=>$id])->one(self::getDb_rd());
    }

    public static function array_id($id){
         return self::find()->where(['id'=>$id])->asArray()->one(self::getDb_rd());
    }


    public static function ids($ids = array()){
        $result = array();
        $res = self::find()->select("*")->where("`id` IN(".implode(',', $ids).")")->all();
         if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function ids_mhk($ids = array()){
        $result = array();
        $res = self::find()->select("*")->where("`id` IN(".implode(',', $ids).")")->all(Yii::$app->get('db_mhk_assist'));
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function array_ids($ids = array()){
        if(empty($ids)) return array();
        $result = array();
        $res = self::find()->select("*")->asArray()->where("`id` IN(".implode(',', $ids).")")->all();
         if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function array_ids_mhk($ids = array()){
        if(empty($ids)) return array();
        $result = array();
        $res = self::find()->select("*")->asArray()->where("`id` IN(".implode(',', $ids).")")->all(Yii::$app->get('db_mhk_assist'));
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function count_ids($ids = array()){
        return self::find()->select("count(`id`)")->where("`id` IN(".implode(',', $ids).")")->scalar();

    }

    /**
     *未催收成功订单
     */
    public static function unfinish($noServer = false){
        $condition = "`status` != ".self::STATUS_COLLECTION_FINISH;
       if($noServer){
            $condition.=" AND `current_collection_admin_user_id` = 0 ";
        }else{
            $condition .= " AND `current_collection_admin_user_id` != 0 ";
        }
        $query = "SELECT * FROM tb_loan_collection_order WHERE ".$condition;
       return Yii::$app->db_assist->createCommand($query)->queryAll();
    }

    //秒还卡
    public static function unfinish_mhk()
    {
        $condition = "`status` != ".self::STATUS_COLLECTION_FINISH." and `current_collection_admin_user_id` != 0 ";
        return LoanCollectionOrder::find()->where($condition)->all(Yii::$app->get('db_mhk_assist'));
    }
    /**
     *反回处于指定逾期阶段，且尚未催还成功的订单，默认返回逾期S1阶段订单
     *@param int $level 逾期等级（S1,S2，S3，S4，S5)
     *@param boolean $noServer 是否没有催收员，默认忽略
     *@return array 催单数组
     *Author:李振国
     *Date：2016/10/30
     */
    public static function orders_level($level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

       $query = self::find()->where(['current_overdue_level'=>$level])->andWhere(" status !=".self::STATUS_COLLECTION_FINISH);
       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->all(self::getDb_rd());
    }

    public static function waiting_orders_level($level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

       $query = self::find()->where(['current_overdue_level'=>$level])->andWhere(" status =".self::STATUS_WAIT_COLLECTION)->orderBy(['id'=>SORT_DESC]);
       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->all();
    }
    public static function ids_waiting_orders_level_rd($level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

       $query = self::find()->select("id")
            ->where(['current_overdue_level'=>$level])
            ->andWhere(" status =".self::STATUS_WAIT_COLLECTION)
            ->asArray()
            ->orderBy(['id'=>SORT_DESC]);
       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       if ($level == self::LEVEL_ONE) { //如果s1 并且是逾期前三天 不入催收
            $query->select(['id', 'user_id', 'user_loan_order_repayment_id']);
       }
       return $query->all(self::getDb_rd());
    }
    public static function ids_waiting_orders_level_rd_mhk($level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

        $query = self::find()->select("id")
            ->where(['current_overdue_level'=>$level])
            ->andWhere(" status =".self::STATUS_WAIT_COLLECTION)
            ->asArray()
            ->orderBy(['id'=>SORT_DESC]);
        if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
        if ($level == self::LEVEL_ONE) {
            $query->select(['id', 'user_id', 'user_loan_order_repayment_id']);
        }
        return $query->all(Yii::$app->get('db_mhk_assist'));
    }

    public static function ids_waiting_orders_level_rd_mod($mod = array(), $level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

       $query = self::find()->select("id")->where(['current_overdue_level'=>$level, ('`id`%'.$mod['total'])=>$mod['cur']])->asArray()->andWhere(" status =".self::STATUS_WAIT_COLLECTION)->orderBy(['id'=>SORT_DESC]);
       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->all(self::getDb_rd());
    }


    public static function waiting_orders_level_rd($level = self::LEVEL_ONE, $noServer = false){
        if(!array_key_exists($level, self::$level)) return array();

       $query = self::find()->where(['current_overdue_level'=>$level])->andWhere(" status =".self::STATUS_WAIT_COLLECTION)->orderBy(['id'=>SORT_DESC]);
       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->all(self::getDb_rd());
    }


    /**
     *反回处于指定催收分组，且尚未催还成功的订单，默认返回催收S1组订单
     *@param int $group 催收分组ID（S1，S2，M1M2，M2M3，M3+)
     *@param boolean $noServer 是否没有催收员，默认忽略
     *@return array 催单数组
     *Author:李振国
     *Date：2016/10/30
     */
    public static function orders_group($group = LoanCollection::GROUP_S_ONE, $noServer = false){
        $group = array_key_exists($group, LoanCollection::$group) ? $group : LoanCollection::GROUP_S_ONE;
       $query = self::find()->where(['current_overdue_group'=>$group])->andWhere(" status !=".self::STATUS_COLLECTION_FINISH);

       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->all();
    }

    public static function orders_group_array($group = LoanCollection::GROUP_S_ONE, $noServer = false){
        $group = array_key_exists($group, LoanCollection::$group) ? $group : LoanCollection::GROUP_S_ONE;
       $query = self::find()->where(['current_overdue_group'=>$group])->andWhere(" status !=".self::STATUS_COLLECTION_FINISH);

       if($noServer)    $query->andWhere(" current_collection_admin_user_id = 0 ");
       return $query->asArray()->all();
    }

    /**
     *更换催收订单的所属催收分组
     *催收状态为【已催收成功】的除外
     *清除【催收人】，【催收机构】，催收状态改为【待催收】
     *@param string|array $from 原所属分组
     *@param string $to 目标分组
     *@return int 成功更换记录条数
     */
    public static function release_order_group($from = '', $to){
        if(!is_array($from)) $from = array($from);
        $count = 0;
        $orders = self::find()->where("`status` != ".self::STATUS_COLLECTION_FINISH." AND `current_overdue_group` IN (".implode(',', $from).")")->all();//原催收组，且未催收成功的订单
        if(!empty($orders)){
            $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
            try{
                foreach ($orders as $key => $item) {

                    $old_group = $item->current_overdue_group;//旧的【催收组】
                    $old_collection_user_id = $item->current_collection_admin_user_id;//旧的【催收人】
                    $old_outside = $item->outside;//旧的【催收机构】

                    $item->status = self::STATUS_WAIT_COLLECTION;//催收状态改为‘待催收’
                    $item->current_collection_admin_user_id = 0;//当前催收人为空
                    $item->current_overdue_group = $to;//改为目标分组
                    $item->outside = 0;//催收机构清空
                    if(!$item->save()){
                        throw new Exception("更改订单所属催收分组失败，催单ID：".$item['id']."，原催收分组：".LoanCollection::$group[$old_group]."，要转成的催收分组：".LoanCollection::$group[$to]);
                    }
                    $count++;

                    //状态流转换
                    $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                    $loan_collection_status_change_log->loan_collection_order_id = $item->id;//催收表ID
                    $loan_collection_status_change_log->before_status = $item->status;
                    $loan_collection_status_change_log->after_status = self::STATUS_WAIT_COLLECTION;
                    $loan_collection_status_change_log->type = self::TYPE_MONTH_DISPATCH_GROUP;
                    $loan_collection_status_change_log->created_at = time();
                    $loan_collection_status_change_log->operator_name = "系统";
                    $loan_collection_status_change_log->remark = LoanCollection::$group[$old_group]."组转至".LoanCollection::$group[$to]."组，原催收人：".$old_collection_user_id.", 原催收机构：".$old_outside;
                    if(!$loan_collection_status_change_log->save()){
                        throw new Exception("状态流转换记录失败，催单ID：".$item['id']."，原催收分组：".LoanCollection::$group[$old_group]."，要转成的催收分组：".LoanCollection::$group[$to]);
                    }
                }
                $transaction->commit();

             }catch(\Exception $e){
                $transaction->rollBack();
                Yii::error($e->getMessage(), 'collection');
                return 0;
            }
        }
        return $count;
    }

    /**
     *将指定订单，派给指定人员
     *@param object $order 待催收订单(来自催收表)
     *@param object $user 催收人员
     *@param string $operator 派单人&操作人
     *@return int 1成功，0失败, -1订单不是待催收状态（可能在派单过程中，订单被还款出催了），-2已有催收人
     */
    public static function dispatch($order, $user, $operator = '系统',$sub_order_type=LoanCollectionOrder::SUB_TYPE_BT){
        if(empty($user) || empty($order))    return false;//用户不存在，或催单不存在

        // if(!is_object($order)){
        //     $order = self::find()->where(['id'=>$order['id']])->one(self::getDb_rd());
        // }

        //为了防止订单状态变更，重新获取订单信息：
        if($sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK)
        {
            $order = self::find()->where(['id'=>$order['id']])->one(self::getDbMhk());
            $order::$connect_name = 'db_mhk_assist';
        }else{
            $order = self::find()->where(['id'=>$order['id']])->one(self::getDb_rd());
        }
        if($order->status == self::STATUS_COLLECTION_FINISH)    return -1;//已催收成功，不重新派单
        if($order->status == self::STATUS_STOP_URGING)    return -12;     //已停催，不再重新委派
        if(!empty($order->current_collection_admin_user_id))    return -2;//已有催收人，不重新派单
        if($order->bad_loan == self::STATUS_WAIT_COLLECTION)    return -11;//已坏账，不再重新委派
        $user_company = UserCompany::id($user->outside);//催收员所属催收公司信息
        if($sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK)
        {
            $transaction= Yii::$app->db_mhk_assist->beginTransaction();//创建事务
        }else{
            $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
        }
        try{

            $order->outside = $user->outside;//修改催收公司
            $order->updated_at = time();
            $order->status = self::STATUS_COLLECTION_PROGRESS;//催收中
            $order->operator_name = $operator;//操作人
            $order->dispatch_name = $operator;//派单人
            $order->dispatch_time = time();//派单时间
            $order->current_overdue_group = $user->group;//当前所属催收分组
            $order->current_collection_admin_user_id = $user->admin_user_id;//当前催收员ID
            $order->outside_person = $user_company->system;//是否委派
            if($order->current_overdue_level == self::LEVEL_ONE) {
                $order->s1_approve_id = $user->admin_user_id;//s1审批人ID

            }elseif($order->current_overdue_level == self::LEVEL_TWO) {
                $order->s2_approve_id = $user->admin_user_id;//s2审批人ID

            }elseif($order->current_overdue_level == self::LEVEL_THREE) {
                $order->s3_approve_id = $user->admin_user_id;//s3审批人ID

            }elseif($order->current_overdue_level == self::LEVEL_FOUR) {
                $order->s4_approve_id = $user->admin_user_id;//s4审批人ID
            }elseif($order->current_overdue_level == self::LEVEL_FIVE) {
                $order->s5_approve_id = $user->admin_user_id;//s5审批人ID
            }
            if(!$order->save()){
                throw new Exception("派单失败，催单ID：".$order->id."，要派给的催收人ID：".$user->admin_user_id);
            }
            //状态流转换
            if($sub_order_type == LoanCollectionOrder::SUB_TYPE_MHK)
            {
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog('db_mhk_assist');
            }else{
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
            }
            $loan_collection_status_change_log->loan_collection_order_id = $order->id;//催收表ID
            $loan_collection_status_change_log->before_status = self::STATUS_WAIT_COLLECTION;
            $loan_collection_status_change_log->after_status = self::STATUS_COLLECTION_PROGRESS;
            $loan_collection_status_change_log->type = self::TYPE_DISPATCH_COLLECTION;//类型：派单
            $loan_collection_status_change_log->created_at = time();
            $loan_collection_status_change_log->operator_name = $operator;
            $loan_collection_status_change_log->remark = "派单，催收ID：".$order->id."，催收人ID：".$user->admin_user_id;
            if(!$loan_collection_status_change_log->save()){
                throw new Exception("状态流转换记录失败，催收ID：".$order->id."，催收人ID：".$user->admin_user_id);
            }
             $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'collection');
            return 0;
        }
        return 1;

    }

    public static $level_operator_map = [
        self::LEVEL_ONE =>'s1_approve_id',
        self::LEVEL_TWO =>'s2_approve_id',
        self::LEVEL_THREE =>'s3_approve_id',
        self::LEVEL_FOUR =>'s4_approve_id',
        self::LEVEL_FIVE =>'s5_approve_id',
    ];

    public static $overdue_day= [
        self::LEVEL_ONE=>[
            'min'=>1,
            'max'=>10
        ],
        self::LEVEL_TWO=>[
            'min'=>11,
            'max'=>30
        ],
        self::LEVEL_THREE=>[
            'min'=>31,
            'max'=>60
        ],
        self::LEVEL_FOUR=>[
            'min'=>61,
            'max'=>90
        ],
        self::LEVEL_FIVE=>[
            'min'=>91,
            'max'=>999
        ],
    ];

    /**
     *根据逾期天数返回逾期等级
     */
    public static function overdueDays2level($day){
        if($day >0 &&  $day <= 10)  return self::LEVEL_ONE;
        if($day <= 30)  return self::LEVEL_TWO;
        if($day <= 60)  return self::LEVEL_THREE;
        if($day <= 90)  return self::LEVEL_FOUR;
        return self::LEVEL_FIVE;
    }

    const NO_SERVER = true;//无催收员
    const STATUS_ALL_COLLECTION = 0;
    const STATUS_WAIT_COLLECTION = 1;
    const STATUS_COLLECTION_PROGRESS = 10;
    const STATUS_COLLECTION_PROMISE = 20;
    const STATUS_COLLECTION_FINISH = 30;
    const STATUS_COLLECTION_OUTSIDE = 40;
    const STATUS_COLLECTION_OUTSIDE_SUCCESS = 50;
    const STATUS_STOP_URGING = 60;

    public static $status = [
        self::STATUS_WAIT_COLLECTION=>'待催收',
        self::STATUS_COLLECTION_PROGRESS=>'催收中',
        self::STATUS_COLLECTION_PROMISE=>'承诺还款',
        self::STATUS_COLLECTION_FINISH=>'催收成功',
        self::STATUS_STOP_URGING=>'停催',
    ];
    const CONTACT_STATUS_DEFAULT = 0;
    const CONTACT_STATUS_ONESELF = 1;
    const CONTACT_STATUS_CONTACT = 2;
    const CONTACT_STATUS_NO_ANSWER = 3;
    const CONTACT_STATUS_OUT_OF_CONTACT = 4;

    public static $contack_status = [
        self::CONTACT_STATUS_DEFAULT        => '默认',
        self::CONTACT_STATUS_ONESELF        => '本人',
        self::CONTACT_STATUS_CONTACT        => '联系人',
        self::CONTACT_STATUS_NO_ANSWER      => '无人接听',
        self::CONTACT_STATUS_OUT_OF_CONTACT => '失联',
    ];

    /**
     *根据催收状态返回相应订单信息
     *@author 李振国
     *@param string|array $status 催收状态
     */
    public static function in_status($status = '', $condition = ''){
        if(!is_array($status))  $status = array($status);

        foreach ($status as $key => $_status) {
            if(!array_key_exists($_status, self::$status)) {
                throw new Exception("未知催收状态:".$_status);
                return;
            }
        }
        $_condition = " `status` IN (".implode(',', $status) . ")";
        if(!empty($condition))  $_condition .= ' AND '.$condition;
        return self::find()->where($_condition)->all();
    }

    public static function in_status_rd($status = '', $condition = ''){
        if(!is_array($status))  $status = array($status);

        foreach ($status as $key => $_status) {
            if(!array_key_exists($_status, self::$status)) {
                throw new Exception("未知催收状态:".$_status);
                return;
            }
        }
        $_condition = " `status` IN (".implode(',', $status) . ")";
        if(!empty($condition))  $_condition .= ' AND '.$condition;
        return self::find()->where($_condition)->all(self::getDb_rd());
    }
    public static function array_in_status_rd($status = '', $condition = ''){
        if(!is_array($status))  $status = array($status);

        foreach ($status as $key => $_status) {
            if(!array_key_exists($_status, self::$status)) {
                throw new Exception("未知催收状态:".$_status);
                return;
            }
        }
        $_condition = " `status` IN (".implode(',', $status) . ")";
        if(!empty($condition))  $_condition .= ' AND '.$condition;
        return self::find()->asArray()->where($_condition)->all(self::getDb_rd());
    }

    /**
     *返回指定催收员ID的指定催单状态的催单信息
     *@param string $uid 催收员ID
     *@param string $orderStatus 催单状态，默认为‘催收中’
     *@param boolean $todayOnly 时间限制，是否仅限当天，默认不限
     */
    public static function orders_user($uid, $orderStatus = self::STATUS_COLLECTION_PROGRESS, $todayOnly = false){
        $condition = 'current_collection_admin_user_id = '.$uid." AND `status` = ".$orderStatus;
        if($todayOnly)  $condition .= ' AND (dispatch_time BETWEEN '.strtotime(date('Y-m-d 0:0:0'))." AND ".strtotime(date('Y-m-d 23:59:59')).") ";
        $query = self::find()->where($condition);
        return $query->all();
    }
    public static function orders_user_count($uid, $orderStatus = self::STATUS_COLLECTION_PROGRESS){
        $start_time = strtotime(date('Y-m-d'));
        $end_time = $start_time+86400;
        $condition = 'current_collection_admin_user_id = '.$uid." AND `status` = ".$orderStatus." AND dispatch_time> ".$start_time." AND dispatch_time<".$end_time;;
        $query = self::find()->select('count(`id`)')->where($condition);
        return $query->scalar();
    }
    public static function orders_user_count_mhk($uid, $orderStatus = self::STATUS_COLLECTION_PROGRESS){
        $start_time = strtotime(date('Y-m-d'));
        $end_time = $start_time+86400;
        $condition = 'current_collection_admin_user_id = '.$uid." AND `status` = ".$orderStatus." AND dispatch_time> ".$start_time." AND dispatch_time<".$end_time;;
        $query = self::find()->select('count(`id`)')->where($condition);
        return $query->scalar(Yii::$app->get('db_mhk_assist'));
    }
    public static function orders_user_count_min($uid, $orderStatus = self::STATUS_COLLECTION_PROGRESS){
        var_dump($uid);
        $start_time = strtotime(date('Y-m-d'));
         $end_time = $start_time+86400;
        $condition = " dispatch_time> ".$start_time." AND dispatch_time<".$end_time;;
        $query = self::find()
            ->select('current_collection_admin_user_id,count(`id`) as count')
            ->where($condition)
            ->andWhere(['current_collection_admin_user_id'=>$uid,'status'=>$orderStatus])
            ->groupBy('current_collection_admin_user_id');
        $sql = $query->createCommand()->getRawSql();
        echo $sql;die;
//            ->orderBy('count')->limit(1)->asArray()->one();
        return $query;

    }

    const TYPE_INPUT_COLLECTION = 1;
    const TYPE_DISPATCH_COLLECTION = 2;
    const TYPE_LEVEL_CHANGE = 3;
    const TYPE_USER_CHANGE = 4;
    const TYPE_DISPATCH_OUTSIDE = 5;
    const TYPE_CANCEL_OUTSIDE = 6;
    const TYPE_OUTSIDE_SUCCESS = 7;
    const TYPE_LEVEL_FINISH = 100;
    const TYPE_MONTH_DISPATCH_GROUP = 8;
    const TYPE_RECYCLE = 9;
    const TYPE_MANUAL = 999;

    const RENEW_STATUS_NO = 100;
    const RENEW_STATUS_YES = 101;

    public $renew_status = [
        self::RENEW_STATUS_NO =>'没有续借出催',
        self::RENEW_STATUS_YES =>'续借出催'
    ];
    public static $type = [
        self::TYPE_INPUT_COLLECTION=>'入催',
        self::TYPE_DISPATCH_COLLECTION=>'派单',
        self::TYPE_LEVEL_CHANGE=>'逾期等级转换',
        self::TYPE_USER_CHANGE=>'转单',
        self::TYPE_DISPATCH_OUTSIDE=>'委外',
        self::TYPE_CANCEL_OUTSIDE=>'取消委外',
        self::TYPE_OUTSIDE_SUCCESS=>'委外成功',
        self::TYPE_LEVEL_FINISH=>'催收完成',
        self::TYPE_MONTH_DISPATCH_GROUP=>'月初分组',
        self::TYPE_RECYCLE=>'回收(重置催收状态为待催收)',
        self::RENEW_STATUS_YES=>'续借出催',
        self::TYPE_MANUAL=>'人工处理',
    ];

    const RENEW_PASS = 1;
    const RENEW_DEFAULT = 0;
    const RENEW_REJECT = -1;
    const RENEW_CHECK = 2;

    public static $next_loan_advice = [
        self::RENEW_DEFAULT => '未给建议',
        self::RENEW_PASS => '建议通过',
        self::RENEW_REJECT => '建议拒绝',
        self::RENEW_CHECK => '建议审核',
    ];
    public static $before_next_loan_advice = [
        self::RENEW_DEFAULT => '未给建议',
        self::RENEW_REJECT => '建议拒绝',
        self::RENEW_CHECK => '建议审核',
    ];

    /**
     *更新催收建议
     *@param int $collectionId 催收记录ID
     *@param int $advice 催收建议
     *@param string $remark 备注
     *@return mixed true:成功, string:失败
     */
    public static function update_next_loan_advice($collectionId=0, $advice = 0, $remark = '手动'){
        if(!array_key_exists($advice, self::$next_loan_advice)) return false;
        try{
            // $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
            if ((Yii::$app instanceof \yii\web\Application) && !empty(Yii::$app->user->identity)) {
                $username = Yii::$app->user->identity->username;
            } elseif ((Yii::$app instanceof \yii\web\Application) && empty(Yii::$app->user->identity)) {
                throw new Exception("抱歉，请先登录");
            } else {
                $username = "系统";
            }

            $item = self::find()->where(['id'=>$collectionId])->one();
            if(empty($item)){
                // $cur_user = empty(Yii::$app->user->identity->username) ? 'system' :Yii::$app->user->identity->username;
                $cur_user = $username;
                throw new Exception("更新催收建议失败——催收记录不存在，催收ID：".$collectionId.", 当前操作人：".$cur_user);
            }
            $advice_before = $item->next_loan_advice;
            $item->next_loan_advice = $advice;
            if(!$item->save()){
                throw new Exception("更新催收建议失败——建议更新失败 催收ID：".$collectionId.", 建议：".$advice);

            }
            // $operator = Yii::$app->user->identity->username ? Yii::$app->user->identity->username :'系统';
            $operator = $username;
            //建议转换
            $loan_collection_suggestion_change_log = new LoanCollectionSuggestionChangeLog();
            $loan_collection_suggestion_change_log->collection_id = $collectionId;
            $loan_collection_suggestion_change_log->order_id = $item->user_loan_order_id;
            $loan_collection_suggestion_change_log->suggestion_before = $advice_before;
            $loan_collection_suggestion_change_log->suggestion = $advice;
            $loan_collection_suggestion_change_log->created_at = time();
            $loan_collection_suggestion_change_log->operator_name = $operator;
            $loan_collection_suggestion_change_log->remark = $remark;

            $loanUser = LoanCollection::username($operator);
            if(!empty($loanUser)){
                $loan_collection_suggestion_change_log->outside = $loanUser['outside'];
            }

            if(!$loan_collection_suggestion_change_log->save()){
                throw new Exception("创建催收建议记录失败, 催收ID：".$collectionId.", 建议：".$advice);

            }
            // $transaction->commit();
            return true;

        }catch(Exception $e){
            // $transaction->rollBack();
            Yii::error($e->getMessage(), 'collection');
            return $e->getMessage();
        }

    }
    public static function update_next_loan_advice_mhk($collectionId=0, $advice = 0, $remark = '手动'){
        if(!array_key_exists($advice, self::$next_loan_advice)) return false;
        try{
            // $transaction= Yii::$app->db_assist->beginTransaction();//创建事务
            if ((Yii::$app instanceof \yii\web\Application) && !empty(Yii::$app->user->identity)) {
                $username = Yii::$app->user->identity->username;
            } elseif ((Yii::$app instanceof \yii\web\Application) && empty(Yii::$app->user->identity)) {
                throw new Exception("抱歉，请先登录");
            } else {
                $username = "系统";
            }

            $item = self::find()->where(['id'=>$collectionId])->one();
            $item::$connect_name = 'db_mhk_assist';
            if(empty($item)){
                // $cur_user = empty(Yii::$app->user->identity->username) ? 'system' :Yii::$app->user->identity->username;
                $cur_user = $username;
                throw new Exception("更新催收建议失败——催收记录不存在，催收ID：".$collectionId.", 当前操作人：".$cur_user);
            }
            $advice_before = $item->next_loan_advice;
            $item->next_loan_advice = $advice;
            if(!$item->save()){
                throw new Exception("更新催收建议失败——建议更新失败 催收ID：".$collectionId.", 建议：".$advice);

            }
            // $operator = Yii::$app->user->identity->username ? Yii::$app->user->identity->username :'系统';
            $operator = $username;
            //建议转换
            $loan_collection_suggestion_change_log = new LoanCollectionSuggestionChangeLog('db_mhk_assist');
            $loan_collection_suggestion_change_log->collection_id = $collectionId;
            $loan_collection_suggestion_change_log->order_id = $item->user_loan_order_id;
            $loan_collection_suggestion_change_log->suggestion_before = $advice_before;
            $loan_collection_suggestion_change_log->suggestion = $advice;
            $loan_collection_suggestion_change_log->created_at = time();
            $loan_collection_suggestion_change_log->operator_name = $operator;
            $loan_collection_suggestion_change_log->remark = $remark;

            $loanUser = LoanCollection::username($operator);
            if(!empty($loanUser)){
                $loan_collection_suggestion_change_log->outside = $loanUser['outside'];
            }

            if(!$loan_collection_suggestion_change_log->save()){
                throw new Exception("创建催收建议记录失败, 催收ID：".$collectionId.", 建议：".$advice);

            }
            // $transaction->commit();
            return true;

        }catch(Exception $e){
            // $transaction->rollBack();
            Yii::error($e->getMessage(), 'collection');
            return $e->getMessage();
        }

    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_order}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_assist');
    }

    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    public static function getDbMhk()
    {
        return Yii::$app->get('db_mhk_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'user_loan_order_id', 'user_loan_order_repayment_id', 'dispatch_time', 'current_collection_admin_user_id', 'current_overdue_level', 's1_approve_id', 's2_approve_id', 's3_approve_id', 's4_approve_id', 'status', 'promise_repayment_time', 'last_collection_time', 'created_at', 'updated_at'], 'integer'],
            [['next_loan_advice'], 'integer'],[['remark'],'string'],
            [['dispatch_name', 'operator_name'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', '借款人ID'),
            'user_loan_order_id' => Yii::t('app', '借款记录ID'),
            'user_loan_order_repayment_id' => Yii::t('app', '还款明细ID'),
            'dispatch_name' => Yii::t('app', '派单人'),
            'dispatch_time' => Yii::t('app', '派单时间'),
            'current_collection_admin_user_id' => Yii::t('app', '当前催收员ID'),
            'current_overdue_level' => Yii::t('app', '当前逾期等级'),
            's1_approve_id' => Yii::t('app', 's1审批人ID'),
            's2_approve_id' => Yii::t('app', 's2审批人ID'),
            's3_approve_id' => Yii::t('app', 's3审批人ID'),
            's4_approve_id' => Yii::t('app', 's4审批人ID'),
            'status' => Yii::t('app', '催收状态'),
            'promise_repayment_time' => Yii::t('app', '承诺还款时间'),
            'last_collection_time' => Yii::t('app', '最后催收时间'),
            'next_loan_advice' => Yii::t('app', '下次贷款建议'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'operator_name' => Yii::t('app', '操作人'),
            'remark' => Yii::t('app', '备注'),
        ];
    }

    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), ['id' => 'user_id']);
    }

    public function getRepaymentOrder()
    {
        return $this->hasOne(UserLoanOrderRepayment::className(), ['id' => 'user_loan_order_repayment_id']);
    }

    public function getLoanOrder()
    {
        return $this->hasOne(UserLoanOrder::className(), ['id' => 'user_loan_order_id']);
    }

    public function getOperatorId()
    {
        $attribute = isset(static::$level_operator_map[$this->current_overdue_level]) ? static::$level_operator_map[$this->current_overdue_level] : false;

        if(empty($attribute))
        {
            return false;
        }

        return $this->$attribute;
    }

    public function beforeSave($insert)
    {
        if(parent::beforeSave($insert)){
            if($this->isNewRecord){
                if(empty($this->created_at)) $this->created_at = time();

            }else{
               $this->updated_at = time();
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 根据催收订单order_ids返回用户ids
     * @param  integer $loan_order_id 催收订单order_ids
     * @return integer                用户ids
     */
    public static function loan_person_ids($user_loan_order_ids){
        return self::find()->select(['user_id','user_loan_order_id'])
            ->where(['in', 'user_loan_order_id', $user_loan_order_ids])
            ->asArray()
            ->all(self::getDb_rd());
    }
    /**
     * 根据给定的用户ID与反馈发布时间筛选出在发布时间前最近的三笔催收订单
     * @param  array $collection_user_feedback_list 反馈列表信息
     * @return array                                反馈列表信息与订单ID
     */
    public static function orderInfoByUserIdsAndCreateAt($collection_user_feedback_info){
        // 筛选当前user_id的用户，且催收订单创建时间在用户反馈时间之前的180天内最近的三条数据
        $condition  = self::tableName() . '.user_id = ' . intval($collection_user_feedback_info['user_id'])  . " AND " . self::tableName() . ".created_at BETWEEN  " . (intval($collection_user_feedback_info['feedback_create_at']) - 86400*180) . ' AND ' . intval($collection_user_feedback_info['feedback_create_at']);

        $collection_user_feedback_order_list = self::find()->select(['user_loan_order_id'])
            ->where($condition)
            ->orderBy('user_loan_order_id desc')
            ->asArray()
            ->limit(3)
            ->all(self::getDb_rd());
        return $collection_user_feedback_order_list;
    }
    //根据借款ID获取 最新的一条催收单子
    public static function getOrderInfoByULRI($user_loan_order_id){
        return self::find()->where(['user_loan_order_id'=>$user_loan_order_id])->limit(1)->orderBy(['created_at'=>SORT_DESC])->one();
    }

    //通过 借款人的ID 获取所有的催收单子
    public static function getOrderByUserId($user_id){
        return self::find()->where(['user_id'=>$user_id])->asArray()->all();
    }


    /**
     *根据给定催单，返回指定日期时的逾期天数
     */
    public static function get_real_overdueDay($collectionOrderId, $dispatch_day){
        $loanOrder = self::id($collectionOrderId);
        $repayOrder = UserLoanOrderRepaymentService::order_id($loanOrder['user_loan_order_id']);
        if($loanOrder['status'] == self::STATUS_COLLECTION_FINISH){
            //催收成功，实际还款时间-指定日期
            return $repayOrder['overdue_day'] - ToolsUtil::diffBetweenTwoDays(date("Y-m-d", $repayOrder['true_repayment_time']), $dispatch_day);

        }else{
            //催收中，当天时间-指定日期
            return $repayOrder['overdue_day'] - ToolsUtil::diffBetweenTwoDays(date("Y-m-d", time()), $dispatch_day);
        }
    }

    /**
     *将指定催单，转派给指定用户
     */
    public static function zhuan_pai($loanCollectionId, $admin_user_id){
        try{
            $transaction = Yii::$app->db_assist->beginTransaction();
            $loanCollection = LoanCollection::admin_id($admin_user_id);
            if(empty($loanCollection)){
                throw new Exception('催收人不存在，无法转派。催收人admin_id:'.$admin_user_id);

            }
            $loanOrder = self::id($loanCollectionId);
            if(empty($loanOrder)){
                throw new Exception('催单不存在，不可转派。催单ID：'.$loanCollectionId);
            }
            if($loanOrder['status'] == self::STATUS_COLLECTION_FINISH){
                // throw new Exception('催单状态为【催收成功】，不可转派。催单ID：'.$loanCollectionId);
            }

            if ((Yii::$app instanceof \yii\web\Application) && !empty(Yii::$app->user->identity)) {
                $username = Yii::$app->user->identity->username;

            } elseif ((Yii::$app instanceof \yii\web\Application) && empty(Yii::$app->user->identity)) {
                throw new Exception("抱歉，请先登录");
            } else {
                $username = "系统";
            }


            $before_cuishou_real_name_id = $loanOrder->current_collection_admin_user_id;

            if($before_cuishou_real_name_id == $admin_user_id){
                throw new Exception("转派前用户和转派后用户是同一人，不可转派。");
            }
            $before_admin = LoanCollection::admin_id($before_cuishou_real_name_id);
            $before_cuishou_real_name = $before_admin['real_name'];

            $loanOrder->current_collection_admin_user_id = $admin_user_id;
            if(!$loanOrder->save()){
                throw new Exception('转派失败');
            }

            //写入转派日志
            //转派成功，生成状态转换记录；
            $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
            $loan_collection_status_change_log ->loan_collection_order_id=$loanCollectionId;   //订单ID
            $loan_collection_status_change_log->before_status = $loanOrder->status;
            $loan_collection_status_change_log ->after_status = $loanOrder->status;
            $loan_collection_status_change_log ->type = LoanCollectionOrder::TYPE_USER_CHANGE;
            $loan_collection_status_change_log ->created_at = time();

            $loan_collection_status_change_log ->operator_name = $username;
            $loan_collection_status_change_log ->remark = '转派:'.$before_cuishou_real_name.'=>'.$loanCollection['real_name'];

            if(!$loan_collection_status_change_log->save()){
                throw new Exception('状态转换记录添加失败');
            }
            $transaction->commit();
            echo '转派成功：订单ID：'.$loanOrder['user_loan_order_id'].', 转派:'.$before_cuishou_real_name.'=>'.$loanCollection['real_name']."\r\n";
            return true;

        }catch(Exception $e){
            $transaction->rollBack();
            echo $e->getMessage()."\r\n";
            Yii::error($e->getMessage(), 'collection');
            return false;
        }

    }

}
