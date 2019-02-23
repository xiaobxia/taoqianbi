<?php

namespace console\controllers;

use common\api\RedisQueue;
use common\api\RedisXLock;
use common\base\LogChannel;
use common\helpers\CurlHelper;
use common\models\AutoDebitLog;
use common\models\CreditZmop;
use common\models\FinancialDebitRecord;
use common\models\fund\LoanFund;
use common\models\LoseDebitOrder;
use common\models\SuspectDebitLostRecord;
use common\models\UserCreditMoneyLog;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\PromotionMobileUpload;
use common\models\UserCreditDetail;
use common\models\CardInfo;
use common\models\LoanBlackList;
use common\helpers\MessageHelper;
use common\services\AutoDebitService;
use common\services\fundChannel\JshbService;
use Yii;
use yii\base\Exception;
use common\models\LoanPerson;
use yii\db\Query;
use common\models\BankConfig;
use common\services\CollectionService;
use common\services\FinancialService;
use common\helpers\Util;
use common\helpers\CommonHelper;
use yii\helpers\ArrayHelper;

class YgdRejectNewController extends  BaseController{

    const DEBIT_MONEY = 10000; //尝试代扣金额

    const OVERDUE_TYPE_DEF    = [0];   //逾期区间(闭区间)
    const OVERDUE_TYPE_ONE    = [0,2];
    const OVERDUE_TYPE_TWO    = [3,6]; //此区间循环扣款500
    const OVERDUE_TYPE_THREE  = [7,200]; //此区间循环扣款200
//    const OVERDUE_TYPE_FOUR   = [7,200];

    const TYPE_AUTO_DEBIT = 1;
    const TYPE_CIRCLE_DEBIT = 2;
    const TYPE_TIME_DEBIT = 3;
    /**
     * 财务代扣 0-2天全额扣款
     */
    public function actionAutoDebitOne()
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $this->_batchDebit(self::OVERDUE_TYPE_ONE,self::TYPE_AUTO_DEBIT,LoanFund::ID_KOUDAI);
    }

    /**
     * 自动代扣逾期天数 3-6天 500元循环扣款
     * ygdrejectnew/auto-debit-two
     */
    public function actionAutoDebitTwo($money_rate = null)
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $this->_batchDebit(self::OVERDUE_TYPE_TWO,self::TYPE_CIRCLE_DEBIT);
    }

    /**
     * 自动扣款 逾期 7-200天以上200元循环扣款
     * ygdrejectnew/auto-debit-three
     */
    public function actionAutoDebitThree()
    {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }
        $this->_batchDebit(self::OVERDUE_TYPE_THREE,self::TYPE_CIRCLE_DEBIT);
    }

    /**
     * 自动扣款 逾期 7-45天以上50元循环扣款
     * ygdrejectnew/auto-debit-four
     */
//    public function actionAutoDebitFour()
//    {
//        $script_lock = CommonHelper::lock();
//        if (!$script_lock) {
//            return self::EXIT_CODE_ERROR;
//        }
//        $this->_batchDebit(self::OVERDUE_TYPE_FOUR,self::TYPE_CIRCLE_DEBIT);
//    }

    /**
     *逾期7天以上还完借款本金后，将借款订单修改为已还款，同时加入黑名单
     * ygdrejectnew/auto-check-over-principal
     */
    public function actionAutoCheckOverPrincipal(){
        $user_loan_order_repayment=UserLoanOrderRepayment::find()
            ->where(['is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES])
            ->andwhere(['<>','status',UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
            ->andwhere(['>=','overdue_day',7])
            ->andWhere('true_total_money>=principal')
            ->select('*')->all();
        if($user_loan_order_repayment){
            foreach ($user_loan_order_repayment as $v){
                //借款订单还款表id
                $id=$v->id;
                //借款订单id
                $order_id=$v->order_id;
                //用户id
                $user_id=$v->user_id;
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    $remark='逾期7天以上用户还完借款本金后，订单置为已还款，并将用户加入黑名单';
                    //1、加入黑名单
                    if(!LoanBlackList::findOne(['user_id' => $user_id])){
                        $loan_person = LoanPerson::findOne($user_id);
                        $black_list = new LoanBlackList();
                        $black_list->user_id = $user_id;
                        $black_list->phone = $loan_person->phone;
                        $black_list->id_number = $loan_person->id_number;
                        $black_list->black_status = LoanBlackList::STATUS_YES;
                        $black_list->black_remark = $remark;
                        $black_list->black_admin_user = 'auto';
                        if(!$black_list->save()){
                            echo '逾期7天以上用户:'.$user_id." 加入黑名单失败\n\r";
                            $transaction->rollBack();
                            continue;
                        }
                    }

                    //2、将借款订单置为已还款
                    $user_loan_order=UserLoanOrder::find()->where(['id'=>$order_id])
                        ->andwhere(['<>','status',UserLoanOrder::STATUS_REPAY_COMPLETE])
                        ->select('*')->one();
                    if($user_loan_order){
                        $user_loan_order->status = UserLoanOrder::STATUS_REPAY_COMPLETE;
                        if(!$user_loan_order->save()){
                            echo '逾期7天以上用户:'.$user_id.' 订单id：'.$order_id." 修改订单状态失败\n\r";
                            $transaction->rollBack();
                            continue;
                        }
                    }

                    //3、将借款订单还款表置为已还款
                    $v->remark=$remark;
                    $v->status=UserLoanOrderRepayment::STATUS_REPAY_COMPLETE;
                    if(!$v->save()){
                        echo '逾期7天以上用户:'.$user_id.' 订单id：'.$order_id." 修改订单还款表状态失败\n\r";
                        $transaction->rollBack();
                        continue;
                    }
                    $transaction->commit();
                    echo '逾期7天以上用户:'.$user_id.' 订单id：'.$order_id." 还款已处理，并加入黑名单\n\r";
                }catch (\Exception $e){
                    $transaction->rollBack();
                    echo '操作出现异常：'.$e->getMessage()."\n\r";
                    \Yii::error('操作出现异常：'.$e->getMessage(),'autocheckover');
                }
            }
        }
        unset($user_loan_order_repayment);
    }

    /**
     * 财务自动扣款功能
     * 每天6点~20点,每小时跑一次
     */
    public function _autoDebit($overdue_day=self::OVERDUE_TYPE_ONE,$type=self::TYPE_AUTO_DEBIT){
        $service = Yii::$container->get('financialService');

        $mod = 0;
        $current_time = time();
        $today_start = strtotime(date('Y-m-d',time())); // 当日零点
        if($type == self::TYPE_CIRCLE_DEBIT){
            $sql = 'SELECT f.id FROM tb_financial_debit_record as f LEFT JOIN tb_user_loan_order_repayment as u ON f.repayment_id = u.id
                WHERE f.status =' . FinancialDebitRecord::STATUS_PAYING . '
                AND f.id > :id AND f.created_at >= ' . $today_start . '
                AND f.created_at <=' . $current_time .'
                AND u.overdue_day >=' . $overdue_day[0] .'
                ORDER BY  id ASC LIMIT 0, 1000';
        }else{
            $sql = 'SELECT f.id FROM tb_financial_debit_record as f LEFT JOIN tb_user_loan_order_repayment as u ON f.repayment_id = u.id
                WHERE f.status IN (' . FinancialDebitRecord::STATUS_FALSE . ',' . FinancialDebitRecord::STATUS_PAYING . ')
                AND f.id > :id AND f.created_at >= ' . $today_start . '
                AND f.id > :id AND f.created_at <= ' . $current_time . '
                AND u.overdue_day >=' . $overdue_day[0] . ' AND  u.overdue_day <= ' . $overdue_day[1] . '
                ORDER BY  id ASC LIMIT 0, 1000';
        }

        $financial_debit_record = Yii::$app->db_kdkj->createCommand($sql ,[':id'=>$mod])->queryAll();
        CommonHelper::stdout(\sprintf("[%s] count: %s\n", \date('ymd H:i'), \count($financial_debit_record)));

        $count = 0;
        while ($financial_debit_record) {
            foreach ($financial_debit_record as &$id) {
                try {
                    $item = FinancialDebitRecord::findOne($id);
                    if (!FinancialDebitRecord::addDebitLock($item->loan_record_id, $item->user_id)) {
                        CommonHelper::stdout(\sprintf("[%s] FinancialDebitRecord_lock_%s_%s.\n", \date('ymd H:i'), $item->loan_record_id, $item->user_id));
                        continue;
                    }

                    //扣款订单判断
                    if(in_array($item->status,[FinancialDebitRecord::STATUS_RECALL, FinancialDebitRecord::STATUS_SUCCESS])){
                        CommonHelper::stdout(\sprintf("[%s] FinancialDebitRecord_status_%s_%s.\n", \date('ymd H:i'), $item->id, $item->user_id));
                        continue;
                    }
                    $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne($item->repayment_id);
                    if ($UserLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                        $item->status = FinancialDebitRecord::STATUS_REFUSE;
                        $item->updated_at = time();
                        $item->save();
                        CommonHelper::stdout(\sprintf("[%s] FinancialDebitRecord_REFUSE_%s.\n", \date('ymd H:i'), $item->repayment_id));
                        continue;
                    }

                    $item->status = FinancialDebitRecord::STATUS_PAYING;
                    $item->repayment_time = 0;
                    $item->remark = $item->remark."重新发起扣款(".date("y-m-d H:i", time()).")";
                    if(!$item->save()){
                        CommonHelper::stdout(\sprintf("[%s] FinancialDebitRecord_%s save_failed.\n", \date('ymd H:i'), $item->repayment_id));
                        continue;
                    }

                    $card = CardInfo::findOne(['id' => $item->debit_card_id]);
                    if(!$card){
                        CommonHelper::stdout(\sprintf("[%s] CardInfo_%s none.\n", \date('ymd H:i'), $item->debit_card_id));
                        continue;
                    }

                    $user = LoanPerson::findOne(['id'=>$item->user_id]);
                    if(!$user){
                        CommonHelper::stdout(\sprintf("[%s] LoanPerson_%s none.\n", \date('ymd H:i'), $item->user_id));
                        continue;
                    }

                    $amount = sprintf("%.2f", $item->plan_repayment_money / 100);
                    //如果类型为循环扣款，扣款金额置为100
                    if($type == self::TYPE_CIRCLE_DEBIT){
                        $amount = min($amount,sprintf("%.2f", 100));
                    }

                    $params = [
                        'amount' => $amount,
                        'id_card' => $user->id_number,
                        'bank_id' => $card->bank_id,
                        'card_no' => $card->card_no,
                        'stay_phone' => $card->phone.'',
                        'username' => 'auto shell'
                    ];
                    if($type == self::TYPE_CIRCLE_DEBIT){
                        print_r($params);
                    }

                    $res = $service->doDebitRecord($item->id,$params);
                    if($res){
                        $count++;
                        CommonHelper::stdout(\sprintf("[%s] auto_debit_success %s.\n", \date('ymd H:i'), $item->id));
                    }
                }
                catch (\Exception $e){
                    CommonHelper::stdout( \sprintf("[%s] %s, exception:%s.\n", \date('ymd H:i'), $id['id'], $e) );
                }
            }

            $mod = $id['id'];
            $financial_debit_record = Yii::$app->db_kdkj->createCommand($sql ,[':id'=>$mod])->queryAll();
        }

        CommonHelper::stdout( \sprintf("[%s] succ_count: %s.\n", \date('ymd H:i'), $count) );
        return self::EXIT_CODE_NORMAL;
    }
    public function actionTestDebit()
    {
        $this->_batchDebit([10,10]);
    }

    /**
     * 批量代扣脚本
     * @param $overdue_day array 逾期天数范围
     * @param $type integer  代扣类型
     * @param $fund_id integer 资方ID
     * @param $money_rate float 还款比率 0.1 - 1
     */
    private function _batchDebit($overdue_day=self::OVERDUE_TYPE_ONE, $type = self::TYPE_AUTO_DEBIT, $fund_id = LoanFund::ID_KOUDAI, $money_rate = null)
    {
        $batch_count = 1000;
        $current_time = time();
        $today_start = strtotime(date('Y-m-d',time())); // 当日零点
        $service = Yii::$container->get('financialService');
        $mod = 0;
        if ($type == self::TYPE_CIRCLE_DEBIT) {
            $sql = 'SELECT f.id FROM tb_financial_debit_record as f INNER JOIN tb_user_loan_order_repayment as u ON f.repayment_id = u.id
                INNER JOIN tb_user_loan_order as l ON l.id = f.loan_record_id
                WHERE f.status =' . FinancialDebitRecord::STATUS_PAYING . '
                AND l.fund_id = '.$fund_id.'
                AND f.id > :id AND f.created_at >= ' . $today_start . '
                AND f.id > :id AND f.created_at <= ' . $current_time . '
                AND u.overdue_day >=' . $overdue_day[0] . ' AND u.is_cuishou_do >= 0 AND  u.overdue_day <= ' . $overdue_day[1] . '
                ORDER BY f.id ASC  LIMIT 0 , '.$batch_count;
        } else {
            $sql = 'SELECT f.id FROM tb_financial_debit_record as f INNER JOIN tb_user_loan_order_repayment as u ON f.repayment_id = u.id
                INNER JOIN tb_user_loan_order as l ON l.id = f.loan_record_id
                WHERE f.status IN (' . FinancialDebitRecord::STATUS_FALSE . ',' . FinancialDebitRecord::STATUS_PAYING . ')
                AND l.fund_id = '.$fund_id.'
                AND f.id > :id AND f.created_at >= ' . $today_start . '
                AND f.id > :id AND f.created_at <= ' . $current_time . '
                AND u.overdue_day >=' . $overdue_day[0] . ' AND u.is_cuishou_do >= 0 AND  u.overdue_day <= ' . $overdue_day[1] . '
                ORDER BY  id ASC  LIMIT 0 , ' . $batch_count;

        }
        $financial_debit_record = Yii::$app->db_kdkj->createCommand($sql, [':id' => $mod])->queryAll();
        $circle_times = 0;
        $total_count = 0;
//        var_dump($financial_debit_record);exit();

        while ($financial_debit_record) {
            $i = 0;
            $total = count($financial_debit_record);
            if(YII_ENV_PROD){
                $params['project_name'] = FinancialService::KD_PROJECT_NAME;
            }else{
                $params['project_name'] = 'kdpay_test';
            }
            $params['batch_no'] = FinancialDebitRecord::generateBatchDebit();
            echo '本次拉取' . $total . '---批次数' . ++$circle_times . "\n";
            foreach ($financial_debit_record as &$id) {
                try {
                    $total_count++;
                    $item = FinancialDebitRecord::findOne($id);
                    if (!FinancialDebitRecord::addDebitLock($item->loan_record_id)) {
                        echo 'addDebitLock' . "\n";
                        continue;
                    }

                    if($type == self::TYPE_CIRCLE_DEBIT || $type == self::TYPE_TIME_DEBIT){
                        $money = 50000;
                        if ($overdue_day == self::OVERDUE_TYPE_THREE){
                            $money = 20000; //2-6天循环扣款200
                        }else if($overdue_day==self::OVERDUE_TYPE_TWO){
                            $money = 50000; //1天循环扣款500
                        }
                        //考虑到可能多扣借款用户款，需要跟实际应扣金额进行对比
                        if($money>$item->plan_repayment_money){
                            $money=$item->plan_repayment_money;
                            //防止代扣金额(分)有可能出现小数
                            $money=intval(strval($money));
                        }
                    }else{
                        //TODO
                        $money = $item->plan_repayment_money;
                        //对应逾期用户，如果$money_rate不为null，则按$money_rate比例进行代扣
                        if($money_rate!=null){
                            $money=$money*$money_rate;
                            //防止代扣金额(分)有可能出现小数
                            $money=intval(strval($money));
                        }
                    }

                    $item->status = FinancialDebitRecord::STATUS_PAYING;
                    $item->repayment_time = time();
                    $item->remark = $item->remark . " ** 重新发起扣款(" . date("y-m-d H:i", time()) . ")" . '-' .$item->order_id;
                    if (!$item->save()) {
                        continue;
                    }
                    $pre = $service->preCheckOrder($item);
                    if ($pre && isset($pre['code']) && $pre['code'] == 0) {


//                        if($pre['card_info']['card_no'] == 4){
//                            $money = min(500000,$money);
//                        }

                        echo '批量次数' . $circle_times . '---当前批量数目' . $batch_count . '---当前计数' . ++$i . "\n";
                        $item->order_id = FinancialDebitRecord::generateBatchDebitOrder($item->id);
                        $item->repayment_img .= '**' . $params['batch_no'];
                        $item->status = FinancialDebitRecord::STATUS_RECALL;
                        $item->apply_debit_money = $money;
//                        $temp_order = [
//                            'order_id' => $item->order_id,
//                            'stay_phone' => strval($pre['card_info']['phone']),
//                            'real_name' => $pre['user_info']['name'],
//                            'id_card' => $pre['user_info']['id_number'],
//                            'bank_id' => $pre['card_info']['bank_id'],
//                            'card_no' => $pre['card_info']['card_no'],
//                            'money' => $money,
//                            'user_ip' => '106.15.41.23',
//                            'pay_summary' => $params['batch_no'],
//                            'user_id' => $pre['user_info']['id']
//                        ];
                        $temp_order2 = [
                            'biz_order_no' => (string)$item->order_id,
                            'name'         => (string)$pre['user_info']['name'],
                            'id_card_no'   => (string)$pre['user_info']['id_number'],
                            'bank_card_no' => (string)$pre['card_info']['card_no'],
                            'bank_id'      => (string)$pre['card_info']['bank_id'],
                            'amount'       => (string)$money,
                            'phone'        => strval($pre['card_info']['phone']),
                        ];

                        $item->save();
                        $auto_debit_log = new AutoDebitLog();
                        $auto_debit_log->user_id = $item->user_id;
                        $auto_debit_log->order_id = $item->loan_record_id;
                        $auto_debit_log->order_uuid = $item->order_id;
                        $auto_debit_log->card_id = $item->debit_card_id;
                        $auto_debit_log->money = $money;
                        $auto_debit_log->status = AutoDebitLog::STATUS_WAIT;
                        if($type == self::TYPE_CIRCLE_DEBIT){
                            $auto_debit_log->debit_type = AutoDebitLog::DEBIT_TYPE_LITTLE;
                        }else{
                            $auto_debit_log->debit_type = AutoDebitLog::DEBIT_TYPE_SYS;
                        }
                        $auto_debit_log->save();

//                        $params['orders'][$i] = $temp_order;
                        $params['recordId'][] = $id;
                        $params['items'][$i] = $temp_order2;
                        $params['mark'] = '本次拉取共有' . $total . '--批量次数' . $circle_times . '--当前批量数目' . $batch_count;

                    }elseif ($pre && isset($pre['code']) && $pre['code'] != 0) {
                        $item->remark_two = $item->remark_two . $pre['msg'] . "(" . date("y-m-d H:i", time()) . ")";
                        $item->status = FinancialDebitRecord::STATUS_FALSE;
                        $item->updated_at = time();
                        if ($item->save()) {
                            //删除锁
                            FinancialDebitRecord::clearDebitLock($item->loan_record_id);
                            continue;
                        }

                    }else{
                        //删除锁
                        FinancialDebitRecord::clearDebitLock($item->loan_record_id);
                    }

                } catch (\Exception $e) {
                    \Yii::info('YgdRejectautodebitinfo' . $e->getMessage() . 'order_id:' . $id['id']);
                    echo $item->id . '**' . $e->getFile() . $e->getLine() . $e->getMessage() . "\n";
                }

            }
            try{
                if (isset($params['items'])) {
                    $ret = $this->batchDebitRecordJshb($params);
                    sleep(1);
                    if ($ret && isset($ret['code']) && $ret['code'] == 0) {
                        echo '批次号: ' . $params['batch_no'] . '处理完成，' . "\n" . $ret['msg'] . "\n";
                        $params['batch_no'] = FinancialDebitRecord::generateBatchDebit();
                        unset($params['items']);
                    }
                }

                $mod = $id['id'];
                $financial_debit_record = Yii::$app->db_kdkj->createCommand($sql, [':id' => $mod])->queryAll();
            }catch (\Exception $e){
                echo $e->getFile().$e->getLine().$e->getMessage();
            }
        }
        echo 'total: ' . $total_count . "\n";
    }

    /**
     * 查询所有的扣款订单的状态
     * ygd-reject/active-search-debit-status
     * [zhangyuliang]
     */
    public function actionActiveSearchDebitStatus($id = null) {
        $now_hour=date('H');
        $now_minute=date('i');
        $is_debit=true;

        if($now_hour==5){
            //5点到9点35分不执行
            if($now_minute<=35){
                $is_debit=false;
            }
        }else if($now_hour==6){
            //6点到6点10分不执行（自动扣款 0-2天）
            if($now_minute<=10){
                $is_debit=false;
            }
        }else if($now_hour==21){
            //21点到21点10分不执行（自动扣款 0-2天）
            if($now_minute<=10){
                $is_debit=false;
            }
        }
//        else if($now_hour==13){
//            //13点30到13点40分不执行(自动扣款 1-2天)
//            if($now_minute<40 && $now_minute>=30){
//                $is_debit=false;
//            }
//        }else if($now_hour==14){
//            //14点到14点10分不执行（自动扣款 当日还款）
//            if($now_minute<=10){
//                $is_debit=false;
//            }
//        }
//        else if($now_hour==16){
//            //16点30到16点40分不执行（自动扣款 当日还款）
//            if($now_minute<40 && $now_minute>=30){
//                $is_debit=false;
//            }
//        }else if($now_hour==19){
//            //19点30到19点40分不执行（自动扣款 当日还款）
//            if($now_minute<40 && $now_minute>=30){
//                $is_debit=false;
//            }
//        }else if($now_hour==20){
//            //20点到20点10分不执行（自动扣款 当日还款）
//            if($now_minute<=10){
//                $is_debit=false;
//            }
//        }else if($now_hour==22){
//            //22点30到22点40分不执行
//            if($now_minute<40 && $now_minute>=30){
//                $is_debit=false;
//            }
//        }

        if($is_debit==false){
            return false;
        }

//        @$time=date('h:i:s',time());
//        if(($time>='09:00:00'&&$time<='09:10:00')||($time>='14:00:00'&&$time<='14:05:00')||($time>='20:00:00'&&$time<='20:20:00')||($time>='22:30:00'&&$time<='22:40:00')){
//            return  false;
//        }
        $script_lock = CommonHelper::lock();
        if (!$script_lock) return self::EXIT_CODE_ERROR;
        $time = strtotime(date('Y-m-d',time())) - 86400 * 2;
        $endTime = time();
        if(is_null($id)){
            $sql = "SELECT id FROM tb_auto_debit_log
                WHERE status in (0,1)
                AND id > :id
                AND created_at >= {$time}
                AND updated_at <= {$endTime}
                AND debit_type <> 7
                ORDER BY  id ASC  LIMIT 100";
        }else{
            $sql = "SELECT id FROM tb_auto_debit_log
                WHERE status in (0,1)
                AND id > :id
                AND id = {$id}
                AND created_at >= {$time}
                AND updated_at <= {$endTime}
                AND debit_type <> 7
                ORDER BY  id ASC  LIMIT 100";
        }
        $mod = 0;
        $unStatusCodeCnt = 0;
        $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql ,[':id'=>$mod])->queryAll();
        $count = 0;
        $callbacking_count = 0;
        while ($autoDebitLogs) {
            $last_id = 0;
            foreach ($autoDebitLogs as $v){
                $last_id = $v['id'];
                $count++;
                $item = AutoDebitLog::findOne($v['id']);
                if (!$item || !$item->order_id)  continue;
                $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);
                switch ($userLoanOrder -> fund_id) {
                    case LoanFund::ID_KOUDAI:
                        $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                        break;
                    default :
                        MessageHelper::sendSMS(NOTICE_MOBILE,'扣款ID:'.$v['id'].' 无对应项目号!');
                        continue;
                        break;
                }
                $params['order_id'] = $item->order_uuid;
                echo "auto_debit_log_id:".$item->id . " ,order_uuid:".$item->order_uuid . " begin.\n";

                $service = new JshbService();

                $ret = $service->withholdQuery($params);

                $pay_channel = isset($ret['data']['pay_channel'])?$ret['data']['pay_channel']:0;
                if (isset(BankConfig::$platform_name[$pay_channel])){
                    $third_platform = BankConfig::$platform_name[$pay_channel];
                }else{
                    $third_platform = 0;
                }

                if ($ret && isset($ret['code']) && $ret['code'] == 0) {
                    if ($ret['data']['status'] == 'success') {
                        try {
                            echo '扣款成功auto_debit_log_id:'. $item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid."\n";
                            $isRepaymented = false;
                            $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                            $order_service = Yii::$container->get('financialCommonService');
                            $pay_order_id = isset($ret['data']['pay_order_no'])?$ret['data']['pay_order_no']:'';
                            $item->money = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                            switch ($item->debit_type) {
                                case AutoDebitLog::DEBIT_TYPE_COLLECTION:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_COLLECTION;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_BACKEND:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_BACKEND;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_LITTLE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT;
                                    break;
                            }
                            //添加回调与查询互斥锁
                            if(!FinancialDebitRecord::addCallBackDebitLock($item->order_uuid))
                            {
                                $conflictMsg = '查询脚本 order_uuid:'.$item->order_uuid.' 查询与主动回调相冲突!';
                                MessageHelper::sendSMS(NOTICE_MOBILE, $conflictMsg);
                                continue;
                            }
                            $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);
                            $userLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id' => $item -> order_id,'user_id' => $userLoanOrder->user_id]);
                            if ($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE || $userLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
                                $isRepaymented = true;
                                $order_result = [ 'code' => 3, 'message' => '该订单已还款,uuid:'.$item->order_uuid];
                            } else {
                                $order_result = $order_service->successCallbackDebitOrder($item, '扣款成功,查询操作更新', 'auto shell',[
                                    'debit_account'=>'',
                                    'repayment_id' => $userLoanOrderRepayment->id,
                                    'repayment_type' => $repaymentType,
                                    'pay_order_id'=>$pay_order_id,
                                    'third_platform'=>$third_platform,
                                    'order_uuid' => $item->order_uuid
                                ]);
                            }
                            if($order_result['code'] != 0 && $isRepaymented == false){
                                throw new Exception($order_result['message']);
                            }
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try { // 同步更新
                                //第一步 更新扣款日志列表
                                $item -> status = AutoDebitLog::STATUS_SUCCESS;
                                $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                $item -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                                $item -> callback_at = time();
                                $item -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->pay_order_id;
                                if (!$item -> save()) {
                                    throw new Exception("AutoDebitLog 更新失败!");
                                }

                                //第二步 如果扣款表中不能找到相关记录则更新
                                $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item->order_uuid, 'user_id'=>$item->user_id]);
                                if ($financialDebitRecord) {
                                    $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_SUCCESS;
                                    $financialDebitRecord -> pay_result  = json_encode($ret);
                                    $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                                    $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                    $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->order_id;
                                    $financialDebitRecord -> true_repayment_time  = time();
                                    $financialDebitRecord -> callback_result  = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                    $financialDebitRecord -> updated_at  = time();
                                    if (!$financialDebitRecord->save()) {
                                        $msg = "直连扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'];
                                        MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                                        throw new Exception("FinancialDebitRecord 记录更新失败!");
                                    }
                                }
//                                //第三步 如果订单在观察列表中则更新
                                $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['order_uuid'=>$item->order_uuid,'user_id'=>$item->user_id]);
                                if ($suspectDebitLostRecord) {
                                    $suspectDebitLostRecord -> status = $isRepaymented ? SuspectDebitLostRecord::STATUS_SUCCESS_REPAYMENTED : SuspectDebitLostRecord::STATUS_SUCCESS_UNREPAYMENT;
                                    $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> remark .= '查询脚本置为成功<br/>';
                                    $suspectDebitLostRecord -> operator .= 'console<br/>';
                                    $suspectDebitLostRecord -> updated_at = time();
                                    if (!$suspectDebitLostRecord -> save()) {
                                        throw new Exception("SuspectDebitLostRecord 记录更新失败");
                                    }
                                }
                                //第四步 如果借款订单是已还款状态，则将记录添加到 还款流水表 和 补单数据表中
                                if ($isRepaymented) {
                                    $loseDebitOrder = LoseDebitOrder::findOne(['user_id' => $item['user_id'],'order_id'=> $item['order_id'],'order_uuid' =>$item['order_uuid']]);
                                    if (!$loseDebitOrder) {
                                        $loseDebitOrder = new LoseDebitOrder();
                                        $loseDebitOrder -> order_id = $item -> order_id;
                                        $loseDebitOrder -> user_id = $item -> user_id;
                                        $loseDebitOrder -> order_uuid = $item -> order_uuid;
                                        $loseDebitOrder -> pay_order_id = $item -> pay_order_id;
                                        $loseDebitOrder -> pre_status = $item -> status;
                                        $loseDebitOrder -> status = $item -> status;
                                        $loseDebitOrder -> callback_result = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> type = LoseDebitOrder::TYPE_DEBIT;
                                        $loseDebitOrder -> debit_channel = $item -> platform;
                                        $loseDebitOrder -> remark = date('Ymd').'订单已还款';
                                        $loseDebitOrder -> staff_type = LoseDebitOrder::STAFF_TYPE_1;
                                        $loseDebitOrder -> updated_at = time();
                                        $loseDebitOrder -> created_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录添加失败!");
                                        }

                                        $money_log = UserCreditMoneyLog::findOne(['user_id'=>$item['user_id'], 'order_uuid'=>$item['order_uuid'], 'order_id'=>$item['order_id']]);
                                        if(is_null($money_log)){
                                            $pay_date = ($ret['data']['pay_date'] . 235959) ?? 0;
                                            $pay_time = min(strtotime($pay_date),time());

                                            $money_log = new UserCreditMoneyLog();
                                            $money_log->type = 2;
                                            $money_log->payment_type = $repaymentType;
                                            $money_log->status = UserCreditMoneyLog::STATUS_SUCCESS;
                                            $money_log->user_id = $item->user_id;
                                            $money_log->order_id = $item->order_id;
                                            $money_log->order_uuid = $item->order_uuid;
                                            $money_log->operator_money = $item->money;
                                            $money_log->operator_name = 'auto shell';
                                            $money_log->pay_order_id = $item->pay_order_id;
                                            $money_log->success_repayment_time = $pay_time;
                                            $money_log->card_id = 'auto shell';
                                            $money_log->debit_channel = $item->platform;
                                            if($money_log->save()){
                                                throw new Exception('UserCreditMoneyLog 保存失败');
                                            }
                                        }
                                    } else {
                                        $loseDebitOrder -> pre_status =  $loseDebitOrder -> status;
                                        $loseDebitOrder -> status =  $item -> status;
                                        $loseDebitOrder -> callback_result .= json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> remark .= date('Ymd').'订单还款成功时回调';
                                        $loseDebitOrder -> updated_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录修改失败!");
                                        }
                                    }


                                }
                                $transaction -> commit();
                                //判断还款金额是否大于0
                                $money = $userLoanOrderRepayment->total_money - $userLoanOrderRepayment->true_total_money;
                                if($money > 0){//微信推送还款金额大于0
                                    RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                                        'code' => 1001,
                                        'user_id' => $userLoanOrderRepayment->user_id,
                                        'order_id' => $userLoanOrderRepayment->order_id,
                                        'loan_money' => $money,
                                        'success' =>[
                                            'pay_person' => COMPANY_NAME,
                                            'pay_type' => '1'
                                        ]
                                    ])]);
                                }
                            } catch (Exception $ex) {
                                $transaction -> rollback();
                                echo "debit_order_id:".$item->id . " ,order_uuid:".$item->order_uuid . ' 记录更新失败,原因:'.$ex->getMessage().".\n";
                            }
                        } catch (Exception $ex) {
                            $msg = '扣款请求回调失败,原因:'.$ex->getMessage();
                            \Yii::error($msg,'ygdrejectnew');
                            MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                        }
                        FinancialDebitRecord::clearDebitLock($item->order_id);
                        FinancialDebitRecord::clearDebitLock('order_' . $item->order_id);
                        FinancialDebitRecord::clearCallBackDebitLock($item->order_uuid);

                        //用户借款展期还款，2018-08-10
                        $loan_service = Yii::$container->get('loanService');
                        @$loan_service->extendApplyLoan($item['order_id'],$item->user_id);

                        //部分还款后，生成剩余扣款记录
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $item['order_id'] ,'user_id' => $item->user_id])->one();
                        if($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                            Yii::info("扣款日志:{$item['id']},部分还款，生成剩余部分扣款记录",LogChannel::FINANCIAL_DEBIT);
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try{
                                $user_loan_order = UserLoanOrder::find()->where(['id' => $item['order_id']])->one();
                                $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                                $user_loan_order->operator_name = 'auto shell';
                                $user_loan_order->updated_at = time();
                                if(!$user_loan_order->save()){
                                    throw new \Exception('UserLoanOrder保存失败');
                                }
                                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                                $user_loan_order_repayment->operator_name =  'auto shell';
                                $user_loan_order_repayment->updated_at = time();
                                if(!$user_loan_order_repayment->save()){
                                    throw new \Exception('UserLoanOrderRepayment保存失败');
                                }
                                $orders_service = Yii::$container->get('orderService');
                                $result = $orders_service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                                if(!$result){
                                    throw new \Exception('生成扣款记录失败');
                                }
                                $transaction->commit();
                            }catch(\Exception $e){
                                $transaction->rollback();
                            }
                        }
                    } elseif ($ret['data']['status'] == 'fail') {
                        //扣款失败
                        echo '扣款失败,auto_debit_log_id:'.$item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid." begin \n";
                        if (!FinancialDebitRecord::addCallBackDebitLock($item->order_uuid))
                        {
                            $conflictMsg = '查询脚本 order_uuid:'.$item->order_uuid.' 查询与主动回调相冲突!';
                            MessageHelper::sendSMS(NOTICE_MOBILE, $conflictMsg);
                            continue;
                        }

                        $UserLoanOrder = UserLoanOrder::findOne($item['order_id']);
                        $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id'=>$item['order_id'],'user_id'=>$item['user_id']]);
                        if($UserLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE || $UserLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                            $alterStatus = true;
                        } else {
                            $alterStatus = UserLoanOrderRepayment::alterOrderStatus($item['order_id'],$UserLoanOrderRepayment['id']);
                        }
                        if ($alterStatus) {
                            $msg = '您在'.APP_NAMES.'本次还款失败，你可以通过选用支付宝还款重新提交。';
                            $loan_person = LoanPerson::find()->where(['id'=>$UserLoanOrder->user_id])->one();
                            //借款用户主动还款失败，将发送还款失败提示短信
                            if($item->debit_type==AutoDebitLog::DEBIT_TYPE_ACTIVE){
                                MessageHelper::sendSMS($loan_person->phone,$msg,'smsService_TianChang_HY',21);//添加短信通道和source_Id
                            }
                            $money = $UserLoanOrderRepayment->total_money - $UserLoanOrderRepayment->true_total_money;
                            if($money > 0){//微信推送还款金额大于0
                                RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                                    'code' => 1002,
                                    'user_id' => $UserLoanOrderRepayment->user_id,
                                    'order_id' => $UserLoanOrderRepayment->order_id,
                                    'loan_money' => $money,
                                    'error' =>[
                                        'error_info' => $msg,
                                        'pay_type' => 1
                                    ]
                                ])]);
                            }
                        }
                        if ($alterStatus) {
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try {
                                //第一步 更新日志列表
                                $item -> status = AutoDebitLog::STATUS_FAILED;
                                $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                $item -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                                $item -> callback_at = time();
                                $item -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->pay_order_id;
                                $item -> error_code = isset($ret['data']['error_code']) ? $ret['data']['error_code']:$item->error_code;
                                if (!$item->save()) {
                                    throw new Exception("AutoDebitLog 保存失败");
                                }
                                //第二步 更新suspectDebitLostRecord 记录
                                $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['order_uuid' => $item->order_uuid,'user_id' => $item->user_id]);
                                if ($suspectDebitLostRecord) {
                                    $suspectDebitLostRecord -> status = SuspectDebitLostRecord::STATUS_FAILED_QUERY;
                                    $suspectDebitLostRecord -> debit_type = (strlen($item->order_uuid) > 14)?SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM:SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                                    $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> remark .= '扣款查询置为失败<br/>';
                                    $suspectDebitLostRecord -> operator .= 'console<br/>';
                                    $suspectDebitLostRecord -> updated_at = time();
                                    if (!$suspectDebitLostRecord -> save()) {
                                        throw new Exception("SuspectDebitLostRecord 保存失败");
                                    }
                                }
                                //第三步 更新扣款记录表
                                $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item['order_uuid'], 'user_id' => $item['user_id']]);
                                if ($financialDebitRecord) {
                                    $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_FALSE;
                                    $financialDebitRecord -> pay_result  = json_encode($ret,JSON_UNESCAPED_UNICODE);
                                    $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money']) ? $ret['data']['money'] : $financialDebitRecord -> true_repayment_money;
                                    $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : 0;
                                    $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
                                    $financialDebitRecord -> true_repayment_time  = time();
                                    $financialDebitRecord -> updated_at  = time();
                                    if (!$financialDebitRecord->save()) {
                                        throw new Exception("FinancialDebitRecord 记录更新失败!");
                                    }
                                }
                                try {
                                    $user_loan_order = UserLoanOrder::findOne($item['order_id']);
                                    $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_REPAY_FAIL, new \common\base\Event(['custom_data'=>[]]));
                                } catch (Exception $e) {}
                                $transaction -> commit();
                            } catch (Exception $ex) {
                                echo '扣款失败,debit_order_id:'.$item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_id." 操作失败,原因:".$ex->getMessage()." \n";
                                $transaction -> rollback();
                            }
                        }
                        FinancialDebitRecord::clearDebitLock($item->order_id);
                        FinancialDebitRecord::clearCallBackDebitLock($item->order_uuid);
                    } elseif ($ret['data']['status'] == 'start') {
                        //处理中
                        $autoDebitService = Yii::$container->get('autoDebitService');
                        try {
                            $autoDebitService->handleDebitingOrder($item->order_uuid,['remark'=>'查询脚本更改','type'=> AutoDebitService::TYPE_SHELL]);
                        } catch (Exception $ex) {
                            MessageHelper::sendSMS(NOTICE_MOBILE,$ex->getMessage().' order_id:'.$item->order_id);
                        }
                        if ((time() -$item->updated_at) > 3600) {
                            $callbacking_count += 1;
                        }
                        continue;
                    }
                }elseif ($ret['code'] ==  1) {
                    if(RedisXLock::lock($item->order_id,7200)){
                        $msg = '回调失败,回调结果:'.print_r($ret,1).'订单号:'.$item->order_id.',查询时间:'.date("Y-m-d H:i:s");
                        MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                        continue;
                    }
                }elseif ($ret && isset($ret['code']) && $ret['code'] == 104){
                    //考虑到代扣订单跟代扣在同一个时间处理，导致交易不存在
                    $created_at=$item->created_at;
                    //必须要大于5分钟（300秒）
                    if(time()-$created_at > 300){
                        $unStatusCodeCnt ++;
                        //第一步 更新还款日志列表
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $item->order_id ,'user_id' => $item->user_id])->one();
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try {
                            $alterStatus = UserLoanOrderRepayment::alterOrderStatus($item->order_id, $user_loan_order_repayment->id);
                            if (!$alterStatus) {
                                throw new Exception('更新两表状态失败,order_id:'.$item->order_id);
                            }
                            $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : '';
                            $item -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                            $item -> callback_at = time();
                            $item -> status = AutoDebitLog::STATUS_FAILED;
                            $item -> updated_at = time();
                            if (!$item->save()) {
                                throw new Exception('扣款日志列表状态更新失败,扣款auto_debit_log_id:'.$item->id);
                            }
                            //第二步 如果扣款日志列表有记录则更新
                            $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item['order_uuid'], 'user_id' => $item['user_id']]);
                            if ($financialDebitRecord) {
                                $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_FALSE;
                                $financialDebitRecord -> pay_result  = json_encode($ret,JSON_UNESCAPED_UNICODE);
                                $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money']) ? $ret['data']['money'] : $financialDebitRecord -> true_repayment_money;
                                $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : 0;
                                $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
                                $financialDebitRecord -> true_repayment_time  = time();
                                $financialDebitRecord -> updated_at  = time();
                                if (!$financialDebitRecord->save()) {
                                    throw new Exception("FinancialDebitRecord 记录更新失败,financial_debit_record_id:".$financialDebitRecord->id);
                                }
                            }
                            $transaction -> commit();
                        } catch (Exception $ex) {
                            $transaction -> rollback();
                        }
                    }
                }
            }
            if ($last_id) {
                $mod = $last_id;
                $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql, [':id' => $mod])->queryAll();
            }
        }
        if ($unStatusCodeCnt > 0) {
            $message = '扣款订单未生成返回状态为码为:104,总数为：'.$unStatusCodeCnt;
            MessageHelper::sendSMS(NOTICE_MOBILE, $message);
        }
        echo "共有:{$count}\n";
    }


    /**
     * 查询汇潮所有的扣款订单的状态
     * ygd-reject/hc-debit-status
     */
    public function actionHcDebitStatus($id = null) {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) return self::EXIT_CODE_ERROR;
        $time = strtotime(date('Y-m-d',time())) - 86400 * 2;
        $endTime = time();
        if(is_null($id)){
            $sql = "SELECT id FROM tb_auto_debit_log
                WHERE status in (0,1)
                AND id > :id
                AND created_at >= {$time}
                AND updated_at <= {$endTime}
                AND debit_type = 7
                ORDER BY  id ASC  LIMIT 100";
        }else{
            $sql = "SELECT id FROM tb_auto_debit_log
                WHERE status in (0,1)
                AND id > :id
                AND id = {$id}
                AND created_at >= {$time}
                AND updated_at <= {$endTime}
                AND debit_type = 7
                ORDER BY  id ASC  LIMIT 100";
        }
        $mod = 0;
        $unStatusCodeCnt = 0;
        $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql ,[':id'=>$mod])->queryAll();
        $count = 0;
        $callbacking_count = 0;
        while ($autoDebitLogs) {
            $last_id = 0;
            foreach ($autoDebitLogs as $v){
                $last_id = $v['id'];
                $count++;
                $item = AutoDebitLog::findOne($v['id']);
                if (!$item || !$item->order_id)  continue;
                $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);
                switch ($userLoanOrder -> fund_id) {
                    case LoanFund::ID_KOUDAI:
                        $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                        break;
                    default :
                        MessageHelper::sendSMS(NOTICE_MOBILE,'扣款ID:'.$v['id'].' 无对应项目号!');
                        continue;
                        break;
                }
                $params['order_id'] = $item->order_uuid;
                echo "auto_debit_log_id:".$item->id . " ,order_uuid:".$item->order_uuid . " begin.\n";


                $Hc_params = [
                    'merchantOutOrderNo' => $item->order_uuid,
                    'merid' => FinancialService::KD_HC_MERID,
                    'noncestr' => 'hc'.\common\helpers\StringHelper::generateUniqid(),
                ];

                $Hc_str = '';
                foreach ($Hc_params as $k => $v) { $Hc_str .= $k.'='.$v.'&';}
                $sign = \common\models\Order::genHcSign($Hc_params);
                $Hc_str .= 'sign='.$sign;
                $url = FinancialService::KD_HC_QUERY_URL;
                $hc_ret = CurlHelper::curl($url, $Hc_str);
                $res = json_decode($hc_ret);
                $third_platform = 23;


                if ($res && (!isset($res->code) || $res->code == '0000' ) && isset($res->payResult)) {
                    $hc_orderMoney=$res->orderMoney;
                    $hc_orderMoney=$hc_orderMoney*100;
                    //防止浮点数出问题
                    $hc_orderMoney=intval(strval($hc_orderMoney));
                    $ret['data'] = [
                        'pay_channel' => $third_platform,
                        'pay_order_no' => $res->orderNo,
//                        'money' => intval($res->orderMoney)*100,
                        'money' => $hc_orderMoney,
                        'pay_date' => $res->payTime
                    ];
                    if ($res->payResult == 1) {
                        try {
                            echo '扣款成功auto_debit_log_id:'. $item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid."\n";

                            $isRepaymented = false;
                            $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                            $order_service = Yii::$container->get('financialCommonService');
                            $pay_order_id = isset($ret['data']['pay_order_no'])?$ret['data']['pay_order_no']:'';
                            $item->money = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                            switch ($item->debit_type) {
                                case AutoDebitLog::DEBIT_TYPE_COLLECTION:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_COLLECTION;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_BACKEND:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_BACKEND;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_LITTLE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT;
                                    break;
                                case AutoDebitLog::DEBIT_TYPE_ACTIVE_HC:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_HC;
                                    break;
                            }
                            //添加回调与查询互斥锁
                            if(!FinancialDebitRecord::addCallBackDebitLock($item->order_uuid))
                            {
                                $conflictMsg = '查询脚本 order_uuid:'.$item->order_uuid.' 查询与主动回调相冲突!';
                                MessageHelper::sendSMS(NOTICE_MOBILE, $conflictMsg);
                                continue;
                            }
                            $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);
                            $userLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id' => $item -> order_id,'user_id' => $userLoanOrder->user_id]);
                            if ($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE || $userLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
                                $isRepaymented = true;
                                $order_result = [ 'code' => 3, 'message' => '该订单已还款,uuid:'.$item->order_uuid];
                            } else {
                                $order_result = $order_service->successCallbackDebitOrder($item, '扣款成功,查询操作更新', 'auto shell',[
                                    'debit_account'=>'',
                                    'repayment_id' => $userLoanOrderRepayment->id,
                                    'repayment_type' => $repaymentType,
                                    'pay_order_id'=>$pay_order_id,
                                    'third_platform'=>$third_platform,
                                    'order_uuid' => $item->order_uuid
                                ]);
                            }
                            if($order_result['code'] != 0 && $isRepaymented == false){
                                throw new Exception($order_result['message']);
                            }
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try { // 同步更新
                                //第一步 更新扣款日志列表
                                $item -> status = AutoDebitLog::STATUS_SUCCESS;
                                $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                $item -> callback_remark = json_encode($res,JSON_UNESCAPED_UNICODE);
                                $item -> callback_at = time();
                                $item -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->pay_order_id;
                                if (!$item -> save()) {
                                    throw new Exception("AutoDebitLog 更新失败!");
                                }

                                //第二步 如果扣款表中不能找到相关记录则更新
                                $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item->order_uuid, 'user_id'=>$item->user_id]);
                                if ($financialDebitRecord) {
                                    $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_SUCCESS;
                                    $financialDebitRecord -> pay_result  = json_encode($res);
                                    $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                                    $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                    $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->order_id;
                                    $financialDebitRecord -> true_repayment_time  = time();
                                    $financialDebitRecord -> callback_result  = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                    $financialDebitRecord -> updated_at  = time();
                                    if (!$financialDebitRecord->save()) {
                                        $msg = "直连扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'];
                                        MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                                        throw new Exception("FinancialDebitRecord 记录更新失败!");
                                    }
                                }
//                                //第三步 如果订单在观察列表中则更新
                                $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['order_uuid'=>$item->order_uuid,'user_id'=>$item->user_id]);
                                if ($suspectDebitLostRecord) {
                                    $suspectDebitLostRecord -> status = $isRepaymented ? SuspectDebitLostRecord::STATUS_SUCCESS_REPAYMENTED : SuspectDebitLostRecord::STATUS_SUCCESS_UNREPAYMENT;
                                    $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> remark .= '查询脚本置为成功<br/>';
                                    $suspectDebitLostRecord -> operator .= 'console<br/>';
                                    $suspectDebitLostRecord -> updated_at = time();
                                    if (!$suspectDebitLostRecord -> save()) {
                                        throw new Exception("SuspectDebitLostRecord 记录更新失败");
                                    }
                                }
                                //第四步 如果借款订单是已还款状态，则将记录添加到 还款流水表 和 补单数据表中
                                if ($isRepaymented) {
                                    $loseDebitOrder = LoseDebitOrder::findOne(['user_id' => $item['user_id'],'order_id'=> $item['order_id'],'order_uuid' =>$item['order_uuid']]);
                                    if (!$loseDebitOrder) {
                                        $loseDebitOrder = new LoseDebitOrder();
                                        $loseDebitOrder -> order_id = $item -> order_id;
                                        $loseDebitOrder -> user_id = $item -> user_id;
                                        $loseDebitOrder -> order_uuid = $item -> order_uuid;
                                        $loseDebitOrder -> pay_order_id = $item -> pay_order_id;
                                        $loseDebitOrder -> pre_status = $item -> status;
                                        $loseDebitOrder -> status = $item -> status;
                                        $loseDebitOrder -> callback_result = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> type = LoseDebitOrder::TYPE_DEBIT;
                                        $loseDebitOrder -> debit_channel = $item -> platform;
                                        $loseDebitOrder -> remark = date('Ymd').'订单已还款';
                                        $loseDebitOrder -> staff_type = LoseDebitOrder::STAFF_TYPE_1;
                                        $loseDebitOrder -> updated_at = time();
                                        $loseDebitOrder -> created_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录添加失败!");
                                        }

                                        $money_log = UserCreditMoneyLog::findOne(['user_id'=>$item['user_id'], 'order_uuid'=>$item['order_uuid'], 'order_id'=>$item['order_id']]);
                                        if(is_null($money_log)){
                                            $pay_date = $ret['data']['pay_date'];
                                            $pay_time = min(strtotime($pay_date),time());

                                            $money_log = new UserCreditMoneyLog();
                                            $money_log->type = 2;
                                            $money_log->payment_type = $repaymentType;
                                            $money_log->status = UserCreditMoneyLog::STATUS_SUCCESS;
                                            $money_log->user_id = $item->user_id;
                                            $money_log->order_id = $item->order_id;
                                            $money_log->order_uuid = $item->order_uuid;
                                            $money_log->operator_money = $item->money;
                                            $money_log->operator_name = 'auto shell';
                                            $money_log->pay_order_id = $item->pay_order_id;
                                            $money_log->success_repayment_time = $pay_time;
                                            $money_log->card_id = 'auto shell';
                                            $money_log->debit_channel = $item->platform;
                                            if($money_log->save()){
                                                throw new Exception('UserCreditMoneyLog 保存失败');
                                            }
                                        }
                                    } else {
                                        $loseDebitOrder -> pre_status =  $loseDebitOrder -> status;
                                        $loseDebitOrder -> status =  $item -> status;
                                        $loseDebitOrder -> callback_result .= json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> remark .= date('Ymd').'订单还款成功时回调';
                                        $loseDebitOrder -> updated_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录修改失败!");
                                        }
                                    }


                                }
                                $transaction -> commit();
                                //判断还款金额是否大于0
                                $money = $userLoanOrderRepayment->total_money - $userLoanOrderRepayment->true_total_money;
                                if($money > 0){//微信推送还款金额大于0
                                    RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                                        'code' => 1001,
                                        'user_id' => $userLoanOrderRepayment->user_id,
                                        'order_id' => $userLoanOrderRepayment->order_id,
                                        'loan_money' => $money,
                                        'success' =>[
                                            'pay_person' => COMPANY_NAME,
                                            'pay_type' => '1'
                                        ]
                                    ])]);
                                }
                            } catch (Exception $ex) {
                                $transaction -> rollback();
                                echo "debit_order_id:".$item->id . " ,order_uuid:".$item->order_uuid . ' 记录更新失败,原因:'.$ex->getMessage().".\n";
                            }
                        } catch (Exception $ex) {
                            $msg = '扣款请求回调失败,原因:'.$ex->getMessage();
                            MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                        }
                        FinancialDebitRecord::clearDebitLock($item->order_id);
                        FinancialDebitRecord::clearDebitLock('order_' . $item->order_id);
                        FinancialDebitRecord::clearCallBackDebitLock($item->order_uuid);

                        //用户借款展期还款，2018-08-10
                        $loan_service = Yii::$container->get('loanService');
                        @$loan_service->extendApplyLoan($item['order_id'],$item->user_id);

                        //部分还款后，生成剩余扣款记录
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $item['order_id'] ,'user_id' => $item->user_id])->one();
                        if($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                            Yii::info("扣款日志:{$item['id']},部分还款，生成剩余部分扣款记录",LogChannel::FINANCIAL_DEBIT);
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try{
                                $user_loan_order = UserLoanOrder::find()->where(['id' => $item['order_id']])->one();
                                $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                                $user_loan_order->operator_name = 'auto shell';
                                $user_loan_order->updated_at = time();
                                if(!$user_loan_order->save()){
                                    throw new \Exception('UserLoanOrder保存失败');
                                }
                                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                                $user_loan_order_repayment->operator_name =  'auto shell';
                                $user_loan_order_repayment->updated_at = time();
                                if(!$user_loan_order_repayment->save()){
                                    throw new \Exception('UserLoanOrderRepayment保存失败');
                                }
                                $orders_service = Yii::$container->get('orderService');
                                $result = $orders_service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                                if(!$result){
                                    throw new \Exception('生成扣款记录失败');
                                }
                                $transaction->commit();
                            }catch(\Exception $e){
                                $transaction->rollback();
                            }
                        }
                    } elseif ($res->payResult == 0) {
                        //扣款失败
                        echo '扣款失败,auto_debit_log_id:'.$item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid." begin \n";
                        if (!FinancialDebitRecord::addCallBackDebitLock($item->order_uuid))
                        {
                            $conflictMsg = '查询脚本 order_uuid:'.$item->order_uuid.' 查询与主动回调相冲突!';
                            MessageHelper::sendSMS(NOTICE_MOBILE, $conflictMsg);
                            continue;
                        }

                        $UserLoanOrder = UserLoanOrder::findOne($item['order_id']);
                        $UserLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id'=>$item['order_id'],'user_id'=>$item['user_id']]);
                        if($UserLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE || $UserLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                            $alterStatus = true;
                        } else {
                            $alterStatus = UserLoanOrderRepayment::alterOrderStatus($item['order_id'],$UserLoanOrderRepayment['id']);
                        }
                        if ($alterStatus) {
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try {
                                //第一步 更新日志列表
                                //考虑到汇潮支付，回调高度重合，如果失败，则调用二次
                                $hc_pay_callback_at=intval($item -> callback_at);
                                if($hc_pay_callback_at>0){
                                    $item -> status = AutoDebitLog::STATUS_FAILED;
                                }
                                $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                $item -> callback_remark = json_encode($res,JSON_UNESCAPED_UNICODE);
                                $item -> callback_at = time();
                                $item -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->pay_order_id;
                                $item -> error_code = 0;
                                if (!$item->save()) {
                                    throw new Exception("AutoDebitLog 保存失败");
                                }
                                //第二步 更新suspectDebitLostRecord 记录
                                $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['order_uuid' => $item->order_uuid,'user_id' => $item->user_id]);
                                if ($suspectDebitLostRecord) {
                                    $suspectDebitLostRecord -> status = SuspectDebitLostRecord::STATUS_FAILED_QUERY;
                                    $suspectDebitLostRecord -> debit_type = (strlen($item->order_uuid) > 14)?SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM:SuspectDebitLostRecord::DEBIT_TYPE_ACTIVE;
                                    $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> remark .= '扣款查询置为失败<br/>';
                                    $suspectDebitLostRecord -> operator .= 'console<br/>';
                                    $suspectDebitLostRecord -> updated_at = time();
                                    if (!$suspectDebitLostRecord -> save()) {
                                        throw new Exception("SuspectDebitLostRecord 保存失败");
                                    }
                                }
                                //第三步 更新扣款记录表
                                $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item['order_uuid'], 'user_id' => $item['user_id']]);
                                if ($financialDebitRecord) {
                                    $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_FALSE;
                                    $financialDebitRecord -> pay_result  = json_encode($res,JSON_UNESCAPED_UNICODE);
                                    $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money']) ? $ret['data']['money'] : $financialDebitRecord -> true_repayment_money;
                                    $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : 0;
                                    $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : '';
                                    $financialDebitRecord -> true_repayment_time  = time();
                                    $financialDebitRecord -> updated_at  = time();
                                    if (!$financialDebitRecord->save()) {
                                        throw new Exception("FinancialDebitRecord 记录更新失败!");
                                    }
                                }
                                try {
                                    $user_loan_order = UserLoanOrder::findOne($item['order_id']);
                                    $user_loan_order->trigger(UserLoanOrder::EVENT_AFTER_REPAY_FAIL, new \common\base\Event(['custom_data'=>[]]));
                                } catch (Exception $e) {}
                                $transaction -> commit();
                                $money = $UserLoanOrderRepayment->total_money - $UserLoanOrderRepayment->true_total_money;
                                $msg = '您在'.APP_NAMES.'本次还款失败，你可以通过检查银行卡余额或者更换银行卡或者选用支付宝重新提交。';
                                if($money > 0){//微信推送还款金额大于0
                                    RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                                        'code' => 1002,
                                        'user_id' => $UserLoanOrderRepayment->user_id,
                                        'order_id' => $UserLoanOrderRepayment->order_id,
                                        'loan_money' => $money,
                                        'error' =>[
                                            'error_info' => $msg,
                                            'pay_type' => 1
                                        ]
                                    ])]);
                                }
                            } catch (Exception $ex) {
                                echo '扣款失败,debit_order_id:'.$item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_id." 操作失败,原因:".$ex->getMessage()." \n";
                                $transaction -> rollback();
                            }
                        }
                        FinancialDebitRecord::clearDebitLock($item->order_id);
                        FinancialDebitRecord::clearCallBackDebitLock($item->order_uuid);
                    }
                }elseif ($res && isset($res->code) && $res->code == '9999') {
                    if(RedisXLock::lock($item->order_id,7200)){
                        $msg = '回调失败,回调结果:'.print_r($ret,1).'订单号:'.$item->order_id.',查询时间:'.date("Y-m-d H:i:s");
                        MessageHelper::sendSMS(NOTICE_MOBILE, $msg);
                        continue;
                    }
                }elseif ($res && isset($res->code) && $res->code != '0000'){
                    $unStatusCodeCnt ++;
                    //第一步 更新还款日志列表
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $item->order_id ,'user_id' => $item->user_id])->one();
                    $transaction = Yii::$app->db_kdkj->beginTransaction();
                    try {
                        $alterStatus = UserLoanOrderRepayment::alterOrderStatus($item->order_id, $user_loan_order_repayment->id);
                        if (!$alterStatus) {
                            throw new Exception('更新两表状态失败,order_id:'.$item->order_id);
                        }
                        $item -> platform = '';
                        $item -> callback_remark = json_encode($res,JSON_UNESCAPED_UNICODE);
                        $item -> callback_at = time();
                        $item -> status = AutoDebitLog::STATUS_FAILED;
                        $item -> updated_at = time();
                        if (!$item->save()) {
                            throw new Exception('扣款日志列表状态更新失败,扣款auto_debit_log_id:'.$item->id);
                        }
                        //第二步 如果扣款日志列表有记录则更新
                        $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item['order_uuid'], 'user_id' => $item['user_id']]);
                        if ($financialDebitRecord) {
                            $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_FALSE;
                            $financialDebitRecord -> pay_result  = json_encode($res,JSON_UNESCAPED_UNICODE);
                            $financialDebitRecord -> platform  = 0;
                            $financialDebitRecord -> third_platform_order_id  = '';
                            $financialDebitRecord -> true_repayment_time  = time();
                            $financialDebitRecord -> updated_at  = time();
                            if (!$financialDebitRecord->save()) {
                                throw new Exception("FinancialDebitRecord 记录更新失败,financial_debit_record_id:".$financialDebitRecord->id);
                            }
                        }
                        $transaction -> commit();
                    } catch (Exception $ex) {
                        $transaction -> rollback();
                    }
                }
            }
            if ($last_id){
                $mod = $last_id;
                $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql ,[':id'=>$mod])->queryAll();
            }

        }
        if ($unStatusCodeCnt > 0) {
            $message = '扣款状态异常,总数为：'.$unStatusCodeCnt;
            MessageHelper::sendSMS(NOTICE_MOBILE, $message);
        }
        echo "共有:{$count}\n";
    }


    /**
     * 更新代扣记录的第三方通道字段
     * ygd-reject/get-debit-platform
     */
    public function actionGetDebitPlatform($id = null) {
        $script_lock = CommonHelper::lock();
        if (!$script_lock) {
            return self::EXIT_CODE_ERROR;
        }

        $mod = 0;
        $limit = 1000;
        if(is_null($id)){
            $where = ['status' => AutoDebitLog::STATUS_WAIT, 'platform' => 0];
        }else{
            $where = ['status' => AutoDebitLog::STATUS_WAIT, 'platform' => 0 , 'id' => $id];
        }
        $db = Yii::$app->get('db_kdkj_rd_new');
        $sql = AutoDebitLog::find()->where($where);
        $logs = $sql->andWhere(['>' , 'id', $mod])->limit($limit)->all($db);

        while ($logs){
            foreach ($logs as $v){
                $mod = $v['id'];
                $item = AutoDebitLog::findOne($v['id']);
                if (!$item) {
                    continue;
                }
                if (($item->status != AutoDebitLog::STATUS_WAIT) || ($item->platform != 0)) {
                    continue;
                }

                $params['order_id'] = $item->order_uuid;
                $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                $service = new JshbService();
                $ret = $service->withholdQuery($params);
                if($ret && $ret['code'] == 0){
                    $pay_channel = isset($ret['data']['pay_channel'])?$ret['data']['pay_channel']:0;
                    if (isset(BankConfig::$platform_name[$pay_channel])){
                        $third_platform = BankConfig::$platform_name[$pay_channel];
                    }else{
                        $third_platform = 0;
                    }
                    if(isset($ret['data']['pay_channel'])){
                        $item->platform = $third_platform;
                        $item->save();
                    }
                }

            }

            $logs = $sql->andWhere(['>' , 'id', $mod])->limit($limit)->all($db);
        }

    }

    /*
     * 生成扣款记录
     * ygdrejectnew/apply-to-financial-debit
    */
    public function actionApplyToFinancialDebit($limit=1000, $mod_base = 0, $mod_left = 0) {
        ini_set('memory_limit', '512M');
        $start_id = 0;
        $tbl = UserLoanOrderRepayment::tableName();
        $query = UserLoanOrderRepayment::find()
            ->select(['order_id', $tbl.'.id'])
            ->where([$tbl.'.status' => UserLoanOrderRepayment::STATUS_CHECK])
            ->andWhere(['>=',$tbl.'.is_cuishou_do',0])
            ->joinWith([
                'userLoanOrder' => function (Query $query) {
                    $query->select(['order_type']);
                }
            ])->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD.' and ' . $tbl . '.id > ' . $start_id)
            ->orderBy($tbl.'.id asc')
            ->limit($limit);

        if ($mod_base > 0) {
            $query->andWhere($tbl.".id % {$mod_base} = {$mod_left} ");
        }
        $all = $query->all();

        $all_count = count($all);
        $i = 0;
        $service = Yii::$container->get('orderService');
        $operator_name = Util::short(__CLASS__, __FUNCTION__);
        $error_list = [];
        while($all) {
            foreach($all as $key => $model) {
                CommonHelper::stdout( \sprintf("[%s] order_%s proc.\n", date('ymd H:i'), $model['order_id']) );

                if (!FinancialDebitRecord::addDebitLock('order_' . $model['order_id'])) { //避免重复申请扣款
                    CommonHelper::stdout( \sprintf("[%] order_%s lock.\n", date('ymd H:i'), $model['order_id']) );
                    continue;
                }

                $user_loan_order = UserLoanOrder::find()->where(['id'=>$model['order_id']])->one();
                $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['id'=>$model['id']])->one();
                if ($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_CHECK) {
                    CommonHelper::stdout( \sprintf("[%] order_%s not_check.\n", date('ymd H:i'), $model['order_id']) );
                    continue;
                }

                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try {
                    $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                    $user_loan_order->operator_name = $operator_name;
                    $user_loan_order->updated_at = time();
                    if (!$user_loan_order->save()) {
                        CommonHelper::stdout( \sprintf("[%] order_%s UserLoanOrder_save_failed.\n", date('ymd H:i'), $model['order_id']) );
                        throw new \Exception("");
                    }

                    $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                    $user_loan_order_repayment->operator_name = $operator_name;
                    $user_loan_order_repayment->updated_at = time();
                    if(!$user_loan_order_repayment->save()){
                        CommonHelper::stdout( \sprintf("[%] order_%s UserLoanOrderRepayment_save_failed.\n", date('ymd H:i'), $model['order_id']) );
                        throw new \Exception("UserLoanOrderRepayment_save_failed");
                    }

                    $result = $service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                    if (!$result) {
                        CommonHelper::stdout( \sprintf("[%] order_%s LqRepayInfo get_failed.\n", date('ymd H:i'), $model['order_id']) );
                        throw new \Exception("LqRepayInfo get_failed");
                    }

                    $log = new UserOrderLoanCheckLog();
                    $log->order_id = $user_loan_order['id'];
                    $log->repayment_type = UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD;
                    $log->repayment_id = $user_loan_order_repayment->id;
                    $log->before_status = $user_loan_order_repayment->status;
                    $log->after_status = UserLoanOrderRepayment::STATUS_WAIT;
                    $log->operator_name = $operator_name;
                    $log->type = UserOrderLoanCheckLog::TYPE_REPAY;
                    $log->remark = "一键审核";
                    $log->operation_type = UserOrderLoanCheckLog::REPAY_CS;
                    if(!$log->save()){
                        CommonHelper::stdout( \sprintf("[%] order_%s UserOrderLoanCheckLog save_failed.\n", date('ymd H:i'), $model['order_id']) );
                        throw new \Exception("UserOrderLoanCheckLog save_failed");
                    }

                    $transaction->commit();
                }
                catch(\Exception $e){
                    $transaction->rollback();
                    $error_list[] = $model['order_id'];
                    $i++;
                    CommonHelper::error('applytofinancialdebit:exception数'.$i.'异常order_id' . print_r($error_list, true) . $e->getMessage());
                }
            }

            $start_id = $model['id'];
            $query = UserLoanOrderRepayment::find()
                ->select(['order_id', $tbl.'.id'])
                ->where([$tbl.'.status' => UserLoanOrderRepayment::STATUS_CHECK])
                ->joinWith([
                    'userLoanOrder' => function (Query $query) {
                        $query->select(['order_type']);
                    }
                ])->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD.' and ' . $tbl . '.id > ' . $start_id)
                ->orderBy($tbl.'.id asc')
                ->limit($limit);
            if ($mod_base > 0) {
                $query->andWhere($tbl.".id % {$mod_base} = {$mod_left} ");
            }
            $all = $query->all();
            $all_count += count($all);
        }

        if (YII_ENV_PROD) {
            $warning_reg_emails = [
                NOTICE_MAIL,
            ];
            $log = date('Y-m-d H:i:s')." 扣款记录生成订单总数:".$all_count.",失败总数：".count($error_list);
            foreach ($warning_reg_emails as $email) {
                \common\helpers\MailHelper::send($email, \sprintf('[%s] '.APP_NAMES.'扣款记录生成脚本', date('Y-m-d')), $log);
            }
        }
        return true;
    }


    /**
     *  批量代扣--发起
     */
    public function batchDebitRecordJshb($params){
        $service = new JshbService();
        $ret = $service->batchWithhold($params);
        $success_ids = '';
        $fail_ids = '';
        $success_msg = '';
        $fail_msg = '';
        if($ret && isset($ret['code'])){
            if($ret['code'] == 0){
                foreach ($params['recordId'] as  $item){
                    $record_id = $item;
                    $record = FinancialDebitRecord::findOne($record_id);
                    $record->repayment_time = time();
                    $record->third_platform_order_id =  '';
                    $record->updated_at = time();
                    if($record->save()){
                        $success_ids .= ','.$record->id;
                        $auto_debit_log = AutoDebitLog::find()
                            ->where([
                                'order_uuid'=> $record->order_id,
                                'user_id'=> $record->user_id
                            ])->one();
                        if($auto_debit_log){
                            $auto_debit_log->pay_order_id = '';
                            $auto_debit_log->remark = json_encode($ret['msg'],JSON_UNESCAPED_UNICODE);
                            $auto_debit_log->save();
                        }
                    }
                }
                $success_msg = '成功订单'.$success_ids;
            }
            if($ret['code'] == 1){
                foreach ($params['recordId'] as  $item){
                    $record_id = $item;
                    $record = FinancialDebitRecord::findOne($record_id);
                    $record->status = FinancialDebitRecord::STATUS_FALSE;
                    $record->updated_at = time();
                    if($record->save()){
                        $auto_debit_log = AutoDebitLog::find()
                            ->where([
                                'order_uuid'=> $record->order_id,
                                'user_id'=> $record->user_id
                            ])->one();
                        if($auto_debit_log){
                            $auto_debit_log->status = AutoDebitLog::STATUS_REJECT;
                            $auto_debit_log->remark = json_encode($ret['msg'],JSON_UNESCAPED_UNICODE);
                            $auto_debit_log->save();
                        }
                        $fail_ids .= ','.$record->id;
                    }
                }
            }
            $fail_msg = '失败订单'.$fail_ids;
        }else{
            //先人工处理  然后观察
            if(YII_ENV==='prod'){
                $response = \common\helpers\CurlHelper::$http_info;
                Yii::error('错误信息:'.print_r($response,1),'financial_debit_batch');
                MessageHelper::sendSMS(NOTICE_MOBILE,'[批量处理] 异常批次号'.$params['batch_no'].' 返回值'.print_r($ret,true));
            }
        }
        $msg = $success_msg.'--'.$fail_msg;
        return [
            'code'=>'0',
            'msg'=>$msg,
        ];
    }


    /**
     * 订单记录   订单结果待确认  复查
     */
    public function actionReviewNotConfirmedOrder(){

        $time = strtotime('-1 day');

        $sql="SELECT id FROM tb_auto_debit_log
        WHERE status in (2,-2)
        AND created_at >= {$time}
        AND debit_type <> 7
        AND callback_remark like '%交易处理中%'
        ORDER BY  id ASC  LIMIT 100 " ;

        $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

        $offset = 0;

        while($autoDebitLogs){

            foreach($autoDebitLogs  as $v){

                $item = AutoDebitLog::findOne($v['id']);

                $params['order_id'] = $item->order_uuid;

                $service = new JshbService();

                $ret = $service->withholdQuery($params,1);

                if ($ret && isset($ret['code']) && $ret['code'] == 0){
                    $item -> callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
//                    $item -> callback_at = time();
//                    $item -> updated_at =  time();
                    if (!$item -> save()) {
                        echo '更新失败：'.$item->order_uuid."\n";
//                        throw new Exception("AutoDebitLog 更新失败!");
                    }else{
                        echo '更新成功：'.$item->order_uuid."\n";
                    }
                }
            }

            $offset++;
            $next_wait = $offset*100;

            $sql="SELECT id FROM tb_auto_debit_log
            WHERE status in (2,-2)
            AND created_at >= {$time}
            AND debit_type <> 7
            AND callback_remark like '%交易处理中%'
            ORDER BY  id ASC  LIMIT {$next_wait},100 ";

            $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql)->queryAll();
        }

    }

    /**
     * 汇潮失败订单 复查
     */
    public function actionReviewHcOrder(){

        $time = strtotime('-6 hours');

        $sql = "select id from tb_auto_debit_log where status = -2 and
        debit_type = 7 and created_at >={$time} ORDER BY  id ASC  LIMIT 100 ";

        $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql)->queryAll();

        $offset = 0;

        while ($autoDebitLogs){

            foreach ($autoDebitLogs as $v){

                $item = AutoDebitLog::findOne($v['id']);
                if (!$item || !$item->order_id)  continue;
                $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);

                $params['order_id'] = $item->order_uuid;
                echo "auto_debit_log_id:".$item->id . " ,order_uuid:".$item->order_uuid . " begin.\n";


                $Hc_params = [
                    'merchantOutOrderNo' => $item->order_uuid,
                    'merid' => FinancialService::KD_HC_MERID,
                    'noncestr' => 'hc'.\common\helpers\StringHelper::generateUniqid(),
                ];

                $Hc_str = '';
                foreach ($Hc_params as $k => $v) { $Hc_str .= $k.'='.$v.'&';}
                $sign = \common\models\Order::genHcSign($Hc_params);
                $Hc_str .= 'sign='.$sign;
                $url = FinancialService::KD_HC_QUERY_URL;
                $hc_ret = CurlHelper::curl($url, $Hc_str);
                $res = json_decode($hc_ret);
                $third_platform = 23;

                if(isset($res->payResult)){

                    $hc_orderMoney=$res->orderMoney;
                    $hc_orderMoney=$hc_orderMoney*100;
                    //防止浮点数出问题
                    $hc_orderMoney=intval(strval($hc_orderMoney));
                    $ret['data'] = [
                        'pay_channel' => $third_platform,
                        'pay_order_no' => $res->orderNo,
                        //                        'money' => intval($res->orderMoney)*100,
                        'money' => $hc_orderMoney,
                        'pay_date' => $res->payTime
                    ];

                    if ($res->payResult == 1) {
                        try {
                            echo '扣款成功auto_debit_log_id:'. $item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid."\n";

                            $isRepaymented = false;
                            $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                            $order_service = Yii::$container->get('financialCommonService');
                            $pay_order_id = isset($ret['data']['pay_order_no'])?$ret['data']['pay_order_no']:'';
                            $item->money = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                            switch ($item->debit_type) {
                                case AutoDebitLog::DEBIT_TYPE_COLLECTION:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_COLLECTION;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_BACKEND:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_BACKEND;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_LITTLE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE_YMT:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP;
                                    break;

                                case AutoDebitLog::DEBIT_TYPE_ACTIVE:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT;
                                    break;
                                case AutoDebitLog::DEBIT_TYPE_ACTIVE_HC:
                                    $repaymentType = UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_HC;
                                    break;
                            }
                            //添加回调与查询互斥锁
                            if(!FinancialDebitRecord::addCallBackDebitLock($item->order_uuid))
                            {
                                $conflictMsg = '查询脚本 order_uuid:'.$item->order_uuid.' 查询与主动回调相冲突!';
                                MessageHelper::sendSMS('18616932561', $conflictMsg);
                                continue;
                            }
                            $userLoanOrder = UserLoanOrder::findOne(['id' => $item -> order_id]);
                            $userLoanOrderRepayment = UserLoanOrderRepayment::findOne(['order_id' => $item -> order_id,'user_id' => $userLoanOrder->user_id]);
                            if ($userLoanOrderRepayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE || $userLoanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {
                                $isRepaymented = true;
                                $order_result = [ 'code' => 3, 'message' => '该订单已还款,uuid:'.$item->order_uuid];
                            } else {
                                $order_result = $order_service->successCallbackDebitOrder($item, '扣款成功,查询操作更新', 'auto shell',[
                                    'debit_account'=>'',
                                    'repayment_id' => $userLoanOrderRepayment->id,
                                    'repayment_type' => $repaymentType,
                                    'pay_order_id'=>$pay_order_id,
                                    'third_platform'=>$third_platform,
                                    'order_uuid' => $item->order_uuid
                                ]);
                            }
                            if($order_result['code'] != 0 && $isRepaymented == false){
                                throw new Exception($order_result['message']);
                            }
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try { // 同步更新
                                //第一步 更新扣款日志列表
                                $item -> status = AutoDebitLog::STATUS_SUCCESS;
                                $item -> platform = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                $item -> callback_remark = json_encode($res,JSON_UNESCAPED_UNICODE);
                                $item -> callback_at = time();
                                $item -> pay_order_id = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->pay_order_id;
                                if (!$item -> save()) {
                                    throw new Exception("AutoDebitLog 更新失败!");
                                }

                                //第二步 如果扣款表中不能找到相关记录则更新
                                $financialDebitRecord = FinancialDebitRecord::findOne([ 'order_id' => $item->order_uuid, 'user_id'=>$item->user_id]);
                                if ($financialDebitRecord) {
                                    $financialDebitRecord -> status  = FinancialDebitRecord::STATUS_SUCCESS;
                                    $financialDebitRecord -> pay_result  = json_encode($res);
                                    $financialDebitRecord -> true_repayment_money  = isset($ret['data']['money'])?$ret['data']['money']:$item->money;
                                    $financialDebitRecord -> platform  = isset($ret['data']['pay_channel']) ? $third_platform : $item->platform;
                                    $financialDebitRecord -> third_platform_order_id  = isset($ret['data']['pay_order_no']) ? $ret['data']['pay_order_no'] : $item->order_id;
                                    $financialDebitRecord -> true_repayment_time  = time();
                                    $financialDebitRecord -> callback_result  = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                    $financialDebitRecord -> updated_at  = time();
                                    if (!$financialDebitRecord->save()) {
                                        $msg = "直连扣款成功，更新用户扣款订单失败！order_id：" . $params['order_id'];
                                        MessageHelper::sendSMS('18616932561', $msg);
                                        throw new Exception("FinancialDebitRecord 记录更新失败!");
                                    }
                                }
                                //                                //第三步 如果订单在观察列表中则更新
                                $suspectDebitLostRecord = SuspectDebitLostRecord::findOne(['order_uuid'=>$item->order_uuid,'user_id'=>$item->user_id]);
                                if ($suspectDebitLostRecord) {
                                    $suspectDebitLostRecord -> status = $isRepaymented ? SuspectDebitLostRecord::STATUS_SUCCESS_REPAYMENTED : SuspectDebitLostRecord::STATUS_SUCCESS_UNREPAYMENT;
                                    $suspectDebitLostRecord -> debit_type = SuspectDebitLostRecord::DEBIT_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> mark_type = SuspectDebitLostRecord::MARK_TYPE_SYSTEM;
                                    $suspectDebitLostRecord -> remark .= '查询脚本置为成功<br/>';
                                    $suspectDebitLostRecord -> operator .= 'console<br/>';
                                    $suspectDebitLostRecord -> updated_at = time();
                                    if (!$suspectDebitLostRecord -> save()) {
                                        throw new Exception("SuspectDebitLostRecord 记录更新失败");
                                    }
                                }
                                //第四步 如果借款订单是已还款状态，则将记录添加到 还款流水表 和 补单数据表中
                                if ($isRepaymented) {
                                    $loseDebitOrder = LoseDebitOrder::findOne(['user_id' => $item['user_id'],'order_id'=> $item['order_id'],'order_uuid' =>$item['order_uuid']]);
                                    if (!$loseDebitOrder) {
                                        $loseDebitOrder = new LoseDebitOrder();
                                        $loseDebitOrder -> order_id = $item -> order_id;
                                        $loseDebitOrder -> user_id = $item -> user_id;
                                        $loseDebitOrder -> order_uuid = $item -> order_uuid;
                                        $loseDebitOrder -> pay_order_id = $item -> pay_order_id;
                                        $loseDebitOrder -> pre_status = $item -> status;
                                        $loseDebitOrder -> status = $item -> status;
                                        $loseDebitOrder -> callback_result = json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> type = LoseDebitOrder::TYPE_DEBIT;
                                        $loseDebitOrder -> debit_channel = $item -> platform;
                                        $loseDebitOrder -> remark = date('Ymd').'订单已还款';
                                        $loseDebitOrder -> staff_type = LoseDebitOrder::STAFF_TYPE_1;
                                        $loseDebitOrder -> updated_at = time();
                                        $loseDebitOrder -> created_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录添加失败!");
                                        }

                                        $money_log = UserCreditMoneyLog::findOne(['user_id'=>$item['user_id'], 'order_uuid'=>$item['order_uuid'], 'order_id'=>$item['order_id']]);
                                        if(is_null($money_log)){
                                            $pay_date = $ret['data']['pay_date'];
                                            $pay_time = min(strtotime($pay_date),time());

                                            $money_log = new UserCreditMoneyLog();
                                            $money_log->type = 2;
                                            $money_log->payment_type = $repaymentType;
                                            $money_log->status = UserCreditMoneyLog::STATUS_SUCCESS;
                                            $money_log->user_id = $item->user_id;
                                            $money_log->order_id = $item->order_id;
                                            $money_log->order_uuid = $item->order_uuid;
                                            $money_log->operator_money = $item->money;
                                            $money_log->operator_name = 'auto shell';
                                            $money_log->pay_order_id = $item->pay_order_id;
                                            $money_log->success_repayment_time = $pay_time;
                                            $money_log->card_id = 'auto shell';
                                            $money_log->debit_channel = $item->platform;
                                            if($money_log->save()){
                                                throw new Exception('UserCreditMoneyLog 保存失败');
                                            }
                                        }
                                    } else {
                                        $loseDebitOrder -> pre_status =  $loseDebitOrder -> status;
                                        $loseDebitOrder -> status =  $item -> status;
                                        $loseDebitOrder -> callback_result .= json_encode($order_result,JSON_UNESCAPED_UNICODE);
                                        $loseDebitOrder -> remark .= date('Ymd').'订单还款成功时回调';
                                        $loseDebitOrder -> updated_at = time();
                                        if (!$loseDebitOrder -> save()) {
                                            throw new Exception("LoseDebitOrder 记录修改失败!");
                                        }
                                    }


                                }
                                $transaction -> commit();
                                //判断还款金额是否大于0
                                $money = $userLoanOrderRepayment->total_money - $userLoanOrderRepayment->true_total_money;
                                if($money > 0){//微信推送还款金额大于0
                                    RedisQueue::push([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO,json_encode([
                                        'code' => 1001,
                                        'user_id' => $userLoanOrderRepayment->user_id,
                                        'order_id' => $userLoanOrderRepayment->order_id,
                                        'loan_money' => $money,
                                        'success' =>[
                                            'pay_person' => COMPANY_NAME,
                                            'pay_type' => '1'
                                        ]
                                    ])]);
                                }
                            } catch (Exception $ex) {
                                $transaction -> rollback();
                                echo "debit_order_id:".$item->id . " ,order_uuid:".$item->order_uuid . ' 记录更新失败,原因:'.$ex->getMessage().".\n";
                            }
                        } catch (Exception $ex) {
                            $msg = '扣款请求回调失败,原因:'.$ex->getMessage();
                            MessageHelper::sendSMS('18616932561', $msg);
                        }
                        FinancialDebitRecord::clearDebitLock($item->order_id);
                        FinancialDebitRecord::clearDebitLock('order_' . $item->order_id);
                        FinancialDebitRecord::clearCallBackDebitLock($item->order_uuid);

                        //用户借款展期还款，2018-08-10
                        $loan_service = Yii::$container->get('loanService');
                        @$loan_service->extendApplyLoan($item['order_id'],$item->user_id);

                        //部分还款后，生成剩余扣款记录
                        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['order_id' => $item['order_id'] ,'user_id' => $item->user_id])->one();
                        if($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
                            Yii::info("扣款日志:{$item['id']},部分还款，生成剩余部分扣款记录",LogChannel::FINANCIAL_DEBIT);
                            $transaction = Yii::$app->db_kdkj->beginTransaction();
                            try{
                                $user_loan_order = UserLoanOrder::find()->where(['id' => $item['order_id']])->one();
                                $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                                $user_loan_order->operator_name = 'auto shell';
                                $user_loan_order->updated_at = time();
                                if(!$user_loan_order->save()){
                                    throw new \Exception('UserLoanOrder保存失败');
                                }
                                $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                                $user_loan_order_repayment->operator_name =  'auto shell';
                                $user_loan_order_repayment->updated_at = time();
                                if(!$user_loan_order_repayment->save()){
                                    throw new \Exception('UserLoanOrderRepayment保存失败');
                                }
                                $orders_service = Yii::$container->get('orderService');
                                $result = $orders_service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                                if(!$result){
                                    throw new \Exception('生成扣款记录失败');
                                }
                                $transaction->commit();
                            }catch(\Exception $e){
                                $transaction->rollback();
                            }
                        }
                    }else{
                        echo '复查未成功auto_debit_log_id:'. $item->id .' user_id:'.$item->user_id.' order_uuid:'.$item->order_uuid.' res：'.json_encode($res)."\n";
                    }
                }

            }
            $offset++;
            $next_wait = $offset*100;

            $sql = "select id from tb_auto_debit_log where status = -2 and
            debit_type = 7 and created_at >={$time} ORDER BY  id ASC  LIMIT {$next_wait},100 ";

            $autoDebitLogs = Yii::$app->db_kdkj->createCommand($sql)->queryAll();
        }

    }

    /**
     * 更新  未回调 脚本置为失败的查询次数
     */
    public function  actionUpdateQueryNumber(){
        $time = time()-3600*6;
        $sql='SELECT id,order_uuid FROM `xjdai`.`tb_auto_debit_log` WHERE status=-2 and callback_remark LIKE "%订单未回调,查询脚本置为失败%"  AND created_at >='.$time;
        $autoDebitLogs = Yii::$app->db->createCommand($sql)->queryAll();
        if($autoDebitLogs){
            foreach($autoDebitLogs as $value){
                $sql='SELECT id FROM  `hyper-pay`.`payorder` WHERE biz_order_no ="'.$value['order_uuid'].'" ';
                $pay_id =  Yii::$app->db->createCommand($sql)->queryOne();
                if($pay_id){
                    $sql='SELECT id,query_cnt FROM  `hyper-pay`.`queryqueue` WHERE pay_order_id = '.$pay_id['id'] ;
                    $query = Yii::$app->db->createCommand($sql)->queryOne();
                    if($query && $query['query_cnt'] == 5){
                        Yii::$app->db->createCommand()->update('`xjdai`.`tb_auto_debit_log`', ['status' => 1], 'id = '.$value['id'])->execute();
                        $result = Yii::$app->db->createCommand()->update('`hyper-pay`.`queryqueue`', ['query_cnt' => 1,'query_status'=>'unfinished'], 'id = '.$query['id'])->execute();
                        echo '更新 '.$value['order_uuid'].' '. ($result > 0 ? '成功' : '失败')."\r\n";
                    }
                }
            }
        }
    }
}
