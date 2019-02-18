<?php
namespace common\services;

use common\models\loan\LoanCollectionOrder;
use Yii;
use yii\base\Component;
use yii\base\UserException;
use yii\base\Exception;
use yii\helpers\Url;
use common\models\loan\LoanCollectionStatusChangeLog;
use common\models\UserLoanOrderRepayment;
use common\models\loan\LoanCollectionRecord;
use common\helpers\MailHelper;
use common\api\RedisQueue;
use common\api\RedisMQ;

class CollectionService extends Component
{

    /**
     * 续借出催
     * @param integer $order_id 订单ID
     * @param string $operator_name 操作人
     */
    public function collectionRenewOut($order_id,$operator_name=''){
        try{
            RedisMQ::push(RedisQueue::LIST_LOAN_RENEW_OUT, json_encode(array('order_id'=>$order_id, 'operator_name'=>$operator_name)));
            return [
                'code'=>0,
                'message'=>'操作成功'
            ];

        }catch(\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public static function collectionRenewOutAction($order_id,$operator_name){
        try{
            $loan_collection_order = LoanCollectionOrder::find()->where(['user_loan_order_id'=>$order_id])->andWhere(" status !=".LoanCollectionOrder::STATUS_COLLECTION_FINISH)->all();
            if(empty($loan_collection_order)){
                return [
                    'code'=>0,
                    'message'=>'操作成功'
                ];
            }
            //更新状态，全部变成催收完成
            foreach($loan_collection_order as &$item){
                $before_status = $item->status;

                $item->status = LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                $item->updated_at = time();
                $item->operator_name = $operator_name;
                $item->renew_status = LoanCollectionOrder::RENEW_STATUS_YES;
                if(!$item->save()){
                    return [
                        'code'=>-1,
                        'message'=>'操作失败'
                    ];
                }
                //添加流转状态：
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                $loan_collection_status_change_log->loan_collection_order_id = $item->id;
                $loan_collection_status_change_log->before_status = $before_status;
                $loan_collection_status_change_log->after_status = LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                $loan_collection_status_change_log->type = LoanCollectionOrder::RENEW_STATUS_YES;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = empty(Yii::$app->user->identity->username) ? '人工' : (Yii::$app->user->identity->username);
                $loan_collection_status_change_log->remark = "续借出催";
                if(!$loan_collection_status_change_log->save()) throw new Exception("续借出催时，添加订单流转状态失败，loan_collection_order_id:".$item->id);
            }

            return [
                'code'=>0,
                'message'=>'操作成功'
            ];

        }catch(\Exception $e){

            Yii::error($e->getMessage());
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }


    }

    /**
     * 还款出催
     * @param integer $order_id 订单ID
     * @param string $operator_name 操作人
     */
    public function collectionPaybackOut($order_id,$operator_name=''){
        try{
            RedisMQ::push(RedisQueue::LIST_LOAN_PAYBACK_OUT, json_encode(array('order_id'=>$order_id, 'operator_name'=>$operator_name)));
            return [
                'code'=>0,
                'message'=>'操作成功'
            ];

        }catch(\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public static  function collectionPaybackOutAction($order_id,$operator_name){
        try{
            $loan_collection_order = LoanCollectionOrder::find()->where(['user_loan_order_id'=>$order_id])->andWhere(" status !=".LoanCollectionOrder::STATUS_COLLECTION_FINISH)->all();
            if(empty($loan_collection_order)){
                return [
                    'code'=>-1,
                    'message'=>'该订单不处于催收状态'
                ];
            }
            //更新状态，全部变成催收完成
            foreach($loan_collection_order as &$item){
                $before_status = $item->status;
                if($before_status == LoanCollectionOrder::STATUS_COLLECTION_FINISH) continue;

                $item->status = LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                $item->updated_at = time();
                $item->operator_name = $operator_name;
                // $item->renew_status = LoanCollectionOrder::RENEW_STATUS_YES;
                if(!$item->save()){
                    throw new Exception("还款出催时，更新催单状态失败，loan_collection_order_id:".$item->id);

                }

                //添加流转状态：
                $loan_collection_status_change_log = new LoanCollectionStatusChangeLog();
                $loan_collection_status_change_log->loan_collection_order_id = $item->id;
                $loan_collection_status_change_log->before_status = $before_status;
                $loan_collection_status_change_log->after_status = LoanCollectionOrder::STATUS_COLLECTION_FINISH;
                $loan_collection_status_change_log->type = LoanCollectionOrder::TYPE_LEVEL_FINISH;
                $loan_collection_status_change_log->created_at = time();
                $loan_collection_status_change_log->operator_name = empty(Yii::$app->user->identity->username) ? '人工' : (Yii::$app->user->identity->username);
                $loan_collection_status_change_log->remark = "还款出催";
                if(!$loan_collection_status_change_log->save()) throw new Exception("还款出催时，添加订单流转状态失败，loan_collection_order_id:".$item->id);

                //提供催收建议：
                self::daemon_set_suggestion($order_id);
            }

            return [
                'code'=>0,
                'message'=>'操作成功'
            ];

        }
        catch(\Exception $e) {
            Yii::error($e->getMessage(), 'collection');
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
    }

    /**
     * 为指定的订单提供贷款建议
     * 异步操作，加入队列
     * @param int $order_id 订单ID
     */
    public static function daemon_set_suggestion($order_id) {
        try {
            \yii::info("提供催收建议，订单ID：{$order_id}", 'collection');
            $loan_collection_order = LoanCollectionOrder::find()->where(['user_loan_order_id'=>$order_id])->andWhere(" status =".LoanCollectionOrder::STATUS_COLLECTION_FINISH)->andWhere(" `next_loan_advice` = 0 ")->all();

            if (!empty($loan_collection_order)) {
                foreach ($loan_collection_order as $key => $item) {
                    \yii::info("提供催收建议-进入队列，催收ID：{$item['id']}", 'collection');
                    RedisMQ::push(RedisQueue::LIST_LOAN_SUGGESTION, json_encode(['collectionId'=>$item['id']]));
                }
            }
            else {
                \yii::error("订单不存在催收单，不给催收建议，订单ID：{$order_id}", 'collection');
            }
        }
        catch(Exception $e) {
            $msg = '设置催收建议的异步操作发生错误：' . $e->getMessage();
            \yii::error($msg, 'collection');
            if (YII_ENV_PROD) {
                MailHelper::send(NOTICE_MAIL, date('Y-m-d H:i:s')." 设置催收建议的异步操作发生错误", $msg . print_r($e, true));
            }
        }
    }

    /**
     *为指定的催单提供贷款建议
     *@param array $order 催单
     */
    public static function set_suggestion($order){
        $repayOrder = UserLoanOrderRepayment::id($order['user_loan_order_repayment_id']);

        // if($repayOrder['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){
        //     echo "未成功还款、不给催收建议, 催收ID：".$order['id']."\r\n";
        //     Yii::info("未成功还款、不给催收建议, 催收ID：".$order['id'], '催收建议');
        //     // continue;
        //     return;
        // }

        if($repayOrder['overdue_day'] >= 11){
            //建议拒绝
            echo '建议拒绝(逾期超过10天), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id']."\r\n";
            Yii::info('建议拒绝(逾期超过10天), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id'], '催收建议');
            LoanCollectionOrder::update_next_loan_advice($order['id'], LoanCollectionOrder::RENEW_REJECT, '逾期超过10天');
            // exit;
            // continue;
            return;
        }

        $promiseRecords = LoanCollectionRecord::promiseTime_collectionOrderId($order['id']);
        $breakAmount = 0;//爽约次数
        $rank = array();
        if(!empty($promiseRecords)){
            foreach ($promiseRecords as $key => $promiseTime) {
                $date = date("Y-m-d", $promiseTime);
                if(in_array($date, $rank))    continue;
                if($date < date("Y-m-d", $repayOrder['true_repayment_time']))    $breakAmount++;//同一天，最多算一次‘承诺还款’
                $rank[] = $date;
            }
        }
        if($breakAmount >= 3){
            //建议拒绝
            echo '建议拒绝(违约次数超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id']."\r\n";
            Yii::info('建议拒绝(违约次数超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id'], '催收建议' );
             LoanCollectionOrder::update_next_loan_advice($order['id'], LoanCollectionOrder::RENEW_REJECT, '违约次数超过2次');
            // exit;
            // continue;
            return;

        }

        if($repayOrder['overdue_day'] >=6 && $repayOrder['overdue_day'] <= 10 && $breakAmount <=2 ){
            //建议审核
            echo '建议审核(逾期天数在6~10天， 违约次数未超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id']."\r\n";
            Yii::info('建议审核(逾期天数在6~10天， 违约次数未超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id'],'催收建议');
             LoanCollectionOrder::update_next_loan_advice($order['id'], LoanCollectionOrder::RENEW_CHECK, '逾期天数在6~10天， 违约次数未超过2次');
             // exit;
            // continue;
             return;
        }

        if($repayOrder['overdue_day'] <= 5 && $breakAmount <=2){
            //建议通过
            echo '建议通过(逾期天数未超过5天，违约次数未超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id']."\r\n";
            Yii::info('建议通过(逾期天数未超过5天，违约次数未超过2次), 借款ID：'.$order['user_loan_order_id'].", 催收ID：".$order['id'], '催收建议');
             LoanCollectionOrder::update_next_loan_advice($order['id'], LoanCollectionOrder::RENEW_PASS, '逾期天数未超过5天，违约次数未超过2次');
             // exit;
            // continue;
             return;
        }
    }

    /**
     *根据订单ID，返回最新一条催收信息
     *@param int $orderId 订单ID
     *@return array 催单信息（admin_user_id=>'当前催收人ID'，outside=>'当前催收机构')
     */
    public static function order_id($orderId = 0){
        $res = LoanCollectionOrder::order_id($orderId);
        if(empty($res)) return array();
        $result = array();
        $result['admin_user_id'] = $res[0]['current_collection_admin_user_id'];
        $result['outside'] = $res[0]['outside'];
        return $result;
    }


}
