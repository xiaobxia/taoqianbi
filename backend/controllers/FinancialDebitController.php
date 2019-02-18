<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/7/26
 * Time: 下午4:03
 */

namespace backend\controllers;


use api\models\LoanPerson;
use common\models\AutoDebitLog;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use common\models\Order;
use common\api\RedisXLock;
use common\base\LogChannel;
use common\helpers\CurlHelper;
use common\models\UserLoanOrder;
use common\models\LoseDebitOrder;
use common\models\UserCreditMoneyLog;
use common\services\FinancialService;
use common\models\FinancialDebitRecord;
use common\models\UserLoanOrderRepayment;
use common\models\RidOverdueLog;


class FinancialDebitController extends BaseController
{

    private function getFilter()
    {
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND l.id = " . intval($search['id']);
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = " . intval($search['user_id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.order_id = " . intval($search['order_id']);
            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND l.order_uuid = '" . trim($search['order_uuid'])."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND l.pay_order_id = '" . trim($search['pay_order_id'])."'";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at <= " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }

    /**
     * @name 补单数据列表
     */
    public function actionLoseDebitOrder()
    {
        $condition = $this->getFilter();
        $query = LoseDebitOrder::find()
            ->from(LoseDebitOrder::tableName().' as l')
            ->andwhere($condition)
            ->orderBy(['l.id'=>SORT_DESC]);
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportLoseDebitOrder($query);
        }
        $db = Yii::$app->get('db_kdkj_rd');
        $pages = new Pagination(['totalCount' => 9999]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $datas = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
        return $this->render('lose-debit-order-list',['pages'=>$pages,'datas'=>$datas]);
    }

    /**
     * @name 补单数据列表导出
     */
    private function _exportLoseDebitOrder($query){
        $this->_setcsvHeader('补单数据'.time().'.csv');
        $datas = $query->all(Yii::$app->get('db_kdkj_rd'));
        $items = [];
        foreach($datas as $value){
            $items[] = [
                'ID' => $value['id'],
                '用户ID' => $value['user_id'],
                '订单ID' => $value['order_id'],
                '银行流水' => $value['order_uuid'],
                '第三方支付号' => $value['pay_order_id'],
                '前状态' => ($value['type'] == UserCreditMoneyLog::TYPE_DEBIT) ? \common\models\AutoDebitLog::$status_list[$value['pre_status']] : (isset(UserCreditMoneyLog::$status[$value['pre_status']])?UserCreditMoneyLog::$status[$value['pre_status']]:'未知状态'),
                '回调状态'=> $value['status'],
                '类型' => UserCreditMoneyLog::$type[$value['type']]??'未知类型',
                '通道' =>UserCreditMoneyLog::$third_platform_name[$value['debit_channel']]??'未知通道',
                '处理类型' =>LoseDebitOrder::$STAFF_TYPE[$value['staff_type']]??'未知状态',
                '备注' =>$value['remark'],
                '创建时间' =>date('Y-m-d H:i:s',$value['created_at']),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

    /**
     * @name 补单操作
     */
    public function actionActivateOrder() {
        try {
            $order_id = $this->request->post('order_id');
            $id = $this->request->post('id');
            $remark = $this->request->post('remark');
            if (!$id) throw new Exception('操作失败: id 值不能为空',self::MSG_ERROR);
            if (!$order_id) throw new Exception('操作失败:order_id 值不能为空',self::MSG_ERROR);
            if (!$remark) throw new Exception('操作失败:备注不能为空',self::MSG_ERROR);
            $loseDebitOrder = LoseDebitOrder::findOne($id); $loanOrder = UserLoanOrder::findOne($order_id);
            //查询最新的扣款结果
            $url = "http://jspay.koudailc.com:8081/debit-api/debit/query-one";
            $params['order_id'] = $loseDebitOrder->order_uuid;
            if ($loseDebitOrder->type == UserCreditMoneyLog::TYPE_PLAY) { //主动还款补单
                $userCreditMoneyLog = UserCreditMoneyLog::findOne(['order_uuid'=>$loseDebitOrder->order_uuid]);
                if (!$userCreditMoneyLog) throw new Exception('操作失败:还款日志表未找到相应数据!',self::MSG_ERROR);
                if ($userCreditMoneyLog->payment_type == UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_APP) {
                    $params['project_name'] = FinancialService::KD_PROJECT_NAME_ALIPAY;
                } else {
                    $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                }
                $params['sign'] = Order::getPaySign($params,$params['project_name']);
                $res = CurlHelper::curlHttp($url, 'POST', $params);
                //$res['data'] = json_decode($loseDebitOrder->callback_result,1);
                if (!isset($res['data']['state'])) throw new Exception('操作失败:返回记录无状态',self::MSG_ERROR);
                if ($res['data']['state'] != 2) throw new Exception('操作失败:最新处理结果为失败',self::MSG_ERROR);
                if ($loanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE) { //如果订单状态为已还款
                    $userCreditMoneyLog->status = UserCreditMoneyLog::STATUS_SUCCESS;
                    $userCreditMoneyLog->remark .= ',订单已还款,手动该记录标记为成功状态!';
                    $userCreditMoneyLog->save();
                    throw new Exception('订单已还款,手动该记录标记为成功状态!',self::MSG_SUCCESS);
                } else { //如果订单状态为未还款
                    $result = '';
                    $userCreditMoneyLog->status = UserCreditMoneyLog::STATUS_ING;
                    $userCreditMoneyLog->save();
                    $key = 'PayDebitCheck'.$loseDebitOrder->order_uuid;
                    if (!RedisXLock::lock($key,10)) throw new Exception ('操作失败:请求正在处理中!',self::MSG_ERROR);
                    if (isset($res['data']['pay_date']) || isset($params['result'])) {
                        $result = $res['data']['pay_date'].$res['data']['result'];
                    }
                    $verify_rs = [
                        'order_id' => isset($userCreditMoneyLog->order_uuid) ? $userCreditMoneyLog->order_uuid : '',
                        'amount' => isset($res['data']['money']) ? $res['data']['money']: '',
                        'third_order_id' => isset($res['data']['pay_order_id']) ? $res['data']['pay_order_id'] : '',
                        'status' => isset($res['data']['state']) ? $res['data']['state'] : '',
                        'result' => $result.'还款成功',
                    ];
                    $loanService = Yii::$container->get('loanService');
                    if ($loanService->debitResult($verify_rs,1)) {
                        $where = [
                            'status' => [FinancialDebitRecord::STATUS_PAYING,FinancialDebitRecord::STATUS_FALSE],
                            'user_id' => $userCreditMoneyLog->user_id,
                            'loan_record_id' => $userCreditMoneyLog->order_id,
                        ];
                        $FinancialDebitRecord = FinancialDebitRecord::find()->where($where)->andWhere(['>=','created_at',strtotime(date('Y-m-d',time()))])->orderBy('id desc')->one();
                        $userCreditMoneyLog -> remark = $verify_rs['result'];
                        $userCreditMoneyLog -> success_repayment_time = time();
                        $userCreditMoneyLog -> save();
                        if($FinancialDebitRecord){
                            $FinancialDebitRecord->updated_at = time();
                            $FinancialDebitRecord->true_repayment_time = time();
                            $FinancialDebitRecord->status = FinancialDebitRecord::STATUS_REFUSE;
                            $FinancialDebitRecord->remark = '订单手动置为已扣款';
                            $FinancialDebitRecord->callback_result = json_encode(['code' => 0,'message'  =>'订单手动置为已扣款',]);
                            if(!$FinancialDebitRecord->save()) throw new Exception("操作失败:更新 FinancialDebitRecord 失败",self::MSG_ERROR);
                        }
                        throw new Exception("操作成功:该订单重新启用成功!",self::MSG_SUCCESS);
                    } else {
                        throw new Exception("操作失败:启用该订单失败",self::MSG_ERROR);
                    }
                }
            } elseif ($loseDebitOrder->type == UserCreditMoneyLog::TYPE_DEBIT) {
                // 代扣需要补单情况 如果是代扣 进入到这个表中说明该订单已还款,且回调状态为成功 那么在进入到该表之前相关数据已修改
                // 那么现在只需要添加进UserCreditMoneyLog中就行
                $autoDebitLog = AutoDebitLog::findOne(['order_uuid' => $loseDebitOrder->order_uuid]);
                if (!$autoDebitLog) {
                    throw new Exception('操作失败:自动扣款日志列表未找到相应数据!',self::MSG_ERROR);
                }
                $params['project_name'] = FinancialService::KD_PROJECT_NAME;
                $params['sign'] = Order::getPaySign($params,$params['project_name']);
                $res = CurlHelper::curlHttp($url, 'POST', $params);
                if (!isset($res['data']['state'])) throw new Exception('操作失败:返回记录无状态',self::MSG_ERROR);
                if ($res['data']['state'] != 2) throw new Exception('操作失败:最新回调查询结果为失败',self::MSG_ERROR);
                if ($loanOrder->status == UserLoanOrder::STATUS_REPAY_COMPLETE) {  //添加还款表数据
                    $userCreditMoneyLog = UserCreditMoneyLog::findOne(['order_uuid' => $loseDebitOrder->order_uuid,'order_id' => $loseDebitOrder->order_id]);
                    if ($userCreditMoneyLog && $userCreditMoneyLog -> status = UserCreditMoneyLog::STATUS_SUCCESS) {
                        throw new Exception("操作失败:该记录已存在,不能重复添加!");
                    } else {
                        $userCreditMoneyLog = new UserCreditMoneyLog();
                        $userCreditMoneyLog -> type = UserCreditMoneyLog::TYPE_DEBIT;
                        $userCreditMoneyLog -> payment_type = UserCreditMoneyLog::PAYMENT_TYPE_AUTO;
                        $userCreditMoneyLog -> status = UserCreditMoneyLog::STATUS_SUCCESS;
                        $userCreditMoneyLog -> user_id = $autoDebitLog -> user_id;
                        $userCreditMoneyLog -> order_id = $autoDebitLog -> order_id;
                        $userCreditMoneyLog -> order_uuid = $autoDebitLog -> order_uuid;
                        $userCreditMoneyLog -> operator_money = $autoDebitLog -> money;
                        $userCreditMoneyLog -> operator_name = Yii::$app->user->identity->username;;
                        $userCreditMoneyLog -> success_repayment_time = $loseDebitOrder -> created_at;
                        $userCreditMoneyLog -> created_at = time();
                        $userCreditMoneyLog -> updated_at = time();
                        $userCreditMoneyLog -> card_id = $autoDebitLog -> card_id;
                        $userCreditMoneyLog -> debit_channel = $autoDebitLog -> platform;
                        $userCreditMoneyLog -> remark = '来自于补单的数据!';
                        if (!$userCreditMoneyLog -> save()) {
                            throw new Exception(" UserCreditMoneyLog 数据添加失败!");
                        }
                    }
                    throw new Exception("操作成功:还款日志数据添加成功!",self::MSG_SUCCESS);
                    /*$financiaDebitRecord = FinancialDebitRecord::findOne(array('order_id'=>$loseDebitOrder->order_uuid,'loan_order_id'=>$loanOrder->id));
                    $transaction = Yii::$app->db_kdkj->beginTransaction();
                    try {
                        if ($financiaDebitRecord) { //如果有扣款记录则更新扣款记录状态为成功
                            $financiaDebitRecord -> status =  FinancialDebitRecord::STATUS_SUCCESS;
                            $financiaDebitRecord -> remark_two .=  '订单已还款,手动该记录标记为成功状态';
                            $financiaDebitRecord -> callback_result = json_encode($res);
                            $financiaDebitRecord -> admin_username = Yii::$app->user->identity->username;
                            $financiaDebitRecord -> updated_at = time();
                            if (!$financiaDebitRecord -> save()) throw new Exception('操作失败:扣款记录状态更改失败!',self::MSG_ERROR);
                        }
                        //更改自动扣款日志列表状态为成功
                        $autoDebitLog -> error_code = 0;//成功状态码
                        $autoDebitLog -> updated_at = time();
                        $autoDebitLog -> callback_remark = json_encode($res);
                        $autoDebitLog -> status = AutoDebitLog::STATUS_SUCCESS;
                        $autoDebitLog -> callback_at = strtotime(date("Y-m-d 23:59:59",strtotime('20170804')));
                        if (!$autoDebitLog->save()) throw new Exception('操作失败:扣款日志列表状态更改失败!',self::MSG_ERROR);
                        $transaction -> commit();
                        throw new Exception('订单已还款,手动该记录标记为成功状态!',self::MSG_SUCCESS);
                    } catch(Exception $ex) {
                        $transaction->rollback();
                        throw new Exception($ex->getMessage(),$ex->getCode());
                    }*/
                } else {
                    throw new Exception('操作失败:数据异常,该订单状态非已还款!',self::MSG_ERROR);
                    /*$financiaDebitRecord = FinancialDebitRecord::findOne(array('order_id'=>$loseDebitOrder->order_uuid,'loan_order_id'=>$loanOrder->id));
                    if (!$financiaDebitRecord) throw new Exception("扣款订单不存在!");
                    $order_service = Yii::$container->get('financialCommonService');
                    $username = Yii::$app->user->identity->username;
                    $third_platform = $res['data']['third_platform'];
                    $pay_order_id = $res['data']['pay_order_id'];
                    $financiaDebitRecord->true_repayment_money = $res['data']['money'];
                    $order_result = $order_service->successDebitOrder($financiaDebitRecord, '扣款成功,后台操作回调', $username,['debit_account'=>'','pay_order_id'=>$pay_order_id,'third_platform'=>$third_platform]);
                    if ($order_result['code'] == 0) $callback_result = [ 'code' => 0,'message' => '通知成功'];
                    else $callback_result = [ 'code' => $order_result['code'], 'message' => "通知失败：".$order_result['message']];
                    $financiaDebitRecord->status = FinancialDebitRecord::STATUS_SUCCESS;
                    $financiaDebitRecord->pay_result = json_encode($res);
                    $financiaDebitRecord->true_repayment_time = time();
                    $financiaDebitRecord->callback_result = json_encode($callback_result);
                    $financiaDebitRecord->updated_at = time();
                    if (!$financiaDebitRecord->save()) throw new Exception("操作失败:扣款列表记录,保存失败!",self::MSG_ERROR);
                    $autoDebitLog->status = AutoDebitLog::STATUS_SUCCESS;
                    $autoDebitLog->platform = isset($ret['data']['third_platform']) ? $ret['data']['third_platform'] : $autoDebitLog->platform;
                    $autoDebitLog->callback_remark = json_encode($ret,JSON_UNESCAPED_UNICODE);
                    $autoDebitLog->callback_at = time();
                    $autoDebitLog->pay_order_id = isset($ret['data']['pay_order_id']) ? $ret['data']['pay_order_id'] : $autoDebitLog->pay_order_id;
                    if ($autoDebitLog->save()) throw new Exception("操作失败:扣款日志列表,保存失败!");
                    FinancialDebitRecord::clearDebitLock($financiaDebitRecord->loan_record_id);
                    FinancialDebitRecord::clearDebitLock('order_' . $financiaDebitRecord->loan_record_id);
                    FinancialDebitRecord::clearCallBackDebitLock($financiaDebitRecord->order_id);
                    //如果部分还款,再次生成扣款记录
                    $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['id' => $financiaDebitRecord['repayment_id']])->one();
                    if ($user_loan_order_repayment->status != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                        Yii::info("扣款订单号：{$financiaDebitRecord['id']},部分还款，生成剩余部分扣款记录",LogChannel::FINANCIAL_DEBIT);
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try{
                            $user_loan_order = UserLoanOrder::find()->where(['id'=>$financiaDebitRecord['loan_record_id']])->one();
                            $user_loan_order->status = UserLoanOrder::STATUS_REPAYING;
                            $user_loan_order->operator_name = 'auto shell';
                            $user_loan_order->updated_at = time();
                            if (!$user_loan_order->save()) throw new Exception('UserLoanOrder保存失败');
                            $user_loan_order_repayment->status = UserLoanOrderRepayment::STATUS_WAIT;
                            $user_loan_order_repayment->operator_name =  'auto shell';
                            $user_loan_order_repayment->updated_at = time();
                            if (!$user_loan_order_repayment->save()) throw new Exception('UserLoanOrderRepayment保存失败');
                            $orders_service = Yii::$container->get('orderService');
                            $result = $orders_service->getLqRepayInfo($user_loan_order_repayment['id']); #创建扣款记录
                            if (!$result) throw new \Exception('生成扣款记录失败');
                            $transaction->commit();
                        }catch(\Exception $e) {
                            $transaction->rollback();
                        }
                    }
                    throw new Exception("操作成功:该订单重新启用成功!",self::MSG_SUCCESS);*/
                }
            } else {
                throw new Exception('操作失败:该记录扣款状态不明确',self::MSG_ERROR);
            }
        } catch(Exception $ex) {
            if ($ex->getCode() == self::MSG_SUCCESS)
            {
                $loseDebitOrder->staff_type =  LoseDebitOrder::STAFF_TYPE_1;
                $loseDebitOrder->remark =  $remark;
                $loseDebitOrder->updated_at =  time();
                $loseDebitOrder->save();
            }
            return $this->redirectMessage($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @name 减免滞纳金列表
     */
    public function actionRidOverdueLogList()
    {
        $condition = '1=1 ';
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            if(isset($search['id'])&&!empty($search['id'])){
                $condition = $condition." and id='".$search['id']."' ";
            }
            if(isset($search['type'])&&!empty($search['type'])){
                $condition = $condition." and type='".$search['type']."' ";
            }
            if(isset($search['order_id'])&&!empty($search['order_id'])){
                $condition = $condition." and order_id='".$search['order_id']."' ";
            }
            if(isset($search['repayment_id'])&&!empty(intval($search['repayment_id']))){
                $condition = $condition." and  repayment_id=".$search['repayment_id']." ";
            }
            if(isset($search['operator_id'])&&!empty(intval($search['operator_id']))){
                $condition = $condition." and operator_id=".$search['operator_id']." ";
            }
            if(isset($search['operator_name'])&&!empty(intval($search['operator_name']))){
                $condition = $condition." and operator_name=".$search['operator_name']." ";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND created_at <= " . strtotime($search['endtime']);
            }
        }
        $query = RidOverdueLog::find()->where($condition);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->orderBy(['id' => SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));


        if(!empty($data)){
            $repayment_ids = [];
            foreach ($data as $k=>$v){
                $repayment_ids[] = $v['repayment_id'];
            }
            $repayment = UserLoanOrderRepayment::find()->where(['id'=>$repayment_ids])->asArray()->all(Yii::$app->get('db_kdkj_rd'));

            $user_ids = [];
            if($repayment){
                foreach ($repayment as  $k=>$v){
                    $user_ids[$v['id']] = $v['user_id'];
                }
            }

            foreach ($data as $k=>$v){
                $data[$k]['user_id'] = $user_ids[$v['repayment_id']];
            }
        }


        return $this->render('rid-overdue-log-list',array(
            'data' => $data,
            'pages' => $pages,
        ));
    }

}