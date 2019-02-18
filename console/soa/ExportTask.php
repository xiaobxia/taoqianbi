<?php
namespace console\soa;

use common\models\fund\LoanFund;
use common\models\LoanPerson;
use common\models\UserCreditMoneyLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;

class ExportTask extends \common\models\LoanTask
{
	public $task;

	public function log($message, $prefix = '') {

        // GlobalHelper::connectDb('db_kdkj_rd_new');
		echo date('Y-m-d H:i:s') .'---' . $this->task->name . '---id:' . $this->task->id . '---' . $message . PHP_EOL;
       	\Yii::$app->db_kdkj_rd_new->close();
	}

	/**
	 * 执行任务
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	public function runExport($params)
	{
		$taskModel = '';
		try {
	        $params = current($params);
	        \common\helpers\Util::cliLimitChange(1024);

			$db = \Yii::$app->db_kdkj_rd_new;

	        if (!isset($params['id'])) {
	            throw new \Exception("参数错误", 3000);
	        }

	        $taskModel = self::findOne($params['id']);
	        if (!$taskModel) {
	            throw new \Exception("未找到对应模型数据", 3000);
	        }
	        $this->task = $taskModel;

	        $taskModel->status = self::STATUS_ING;
	        $taskModel->updated_at = time();
	        $taskModel->save();
	        $params = json_decode($taskModel->data, true);

            $condition = '1=1';
            $search = $params;
            if (!empty($search['id'])) {
                $condition .= " AND l.id = ".intval($search['id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.order_id = ".intval($search['order_id']);
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = ".intval($search['user_id']);
            }
            if (!empty($search['user_name'])) {
                $user_info = LoanPerson::find()->select(['id'])->where(['phone' => $search['user_name']])->asArray()->all();
                if(!empty($user_info)){
                    $user_list = [];
                    foreach($user_info as $v){
                        $user_list[] = $v['id'];
                    }
                }else{
                    $user_list = [0];
                }
                $user_list = implode(',',$user_list);
                $condition .= " AND l.user_id in ({$user_list})";

            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND l.order_uuid = '".trim($search['order_uuid'])."'";
            }
            if (isset($search['status']) && $search['status'] !== '') {
                $condition .= " AND l.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] !== '') {
                $condition .= " AND l.type = " . intval($search['type']);
            }
            if (isset($search['debit_channel']) && $search['debit_channel'] !== '') {
                $condition .= " AND l.debit_channel = " . intval($search['debit_channel']);
            }
            if (!empty($search['payment_type'])) {
                $condition .= " AND l.payment_type = " . intval($search['payment_type']);
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND l.pay_order_id = '".trim($search['pay_order_id'])."'";
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at <= " . strtotime($search['endtime']);
            }
            //若更新时间为空，则取创建时间以及还款状态成功的单据；
            if(!empty($search['success_begin_time'])||!empty($search['success_end_time'])){
                if (!empty($search['success_begin_time'])) {
                    $condition .= " AND  l.success_repayment_time >= " . strtotime($search['success_begin_time']);
                }
                if (!empty($search['success_end_time'])) {
                    $condition .= " AND l.success_repayment_time <= " . strtotime($search['success_end_time']);
                }
                $condition .=" AND l.status=".UserCreditMoneyLog::STATUS_SUCCESS;
            }
            if(isset($search['fund_id'])&& !empty($search['fund_id']) && $search['fund_id'] > 0 ){
                $condition  .= " AND userLoanOrder.fund_id = ".(int)$search['fund_id'];
            }

            $this->log('开始查询数据');
            $max_id = 0;
            $query = UserCreditMoneyLog::find()->from(UserCreditMoneyLog::tableName().' as l')->
            select(['l.debit_channel','l.type','l.id','l.payment_type','l.remark','l.order_uuid','l.pay_order_id','l.operator_money','l.operator_name',
                'p.name','p.phone','l.updated_at',
                'userLoanOrder.fund_id','l.order_id'])->orderBy(['l.id' => SORT_DESC])
                ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
                ->leftJoin(UserLoanOrderRepayment::tableName().' as uo','l.order_id=uo.order_id')
                ->leftJoin(UserLoanOrder::tableName() .' as userLoanOrder','userLoanOrder.id=l.order_id')
                ->andwhere($condition);
            $datas = $query->andWhere(['>','l.id',$max_id])->limit(10000)->asArray()->orderBy(['l.id'=>SORT_ASC])->all($db);
            $items = [];
            $payment_type=UserCreditMoneyLog::$payment_type;

            $fund  =  LoanFund::getAllFundArray();
            while ($datas)
            {
                foreach($datas as $value){

                    if($value['debit_channel']){
                        $debit_channel =  isset(UserCreditMoneyLog::$third_platform_name[$value['debit_channel']]) ? UserCreditMoneyLog::$third_platform_name[$value['debit_channel']] : "---";
                    }else{
                        $debit_channel = isset(UserCreditMoneyLog::$type[$value['type']]) ? UserCreditMoneyLog::$type[$value['type']] : "---";
                    }
                    $items[] = [
                        '资方'=>  !empty($value['fund_id'])?$fund[$value['fund_id']]:"口袋理财",
                        '姓名' => $value['name'],
                        '订单id' => $value['order_id'],
                        '通道'=> $debit_channel,
                        '还款金额/元' => sprintf('%.2f', $value['operator_money'] / 100),
                        '成功时间' =>$value['updated_at'] ? date('Y-m-d H:i', $value['updated_at']) : '-',
                        '还款方式' => isset($payment_type[$value['payment_type']]) ? $payment_type[$value['payment_type']] : $value['payment_type'],
                        '银行流水号'=> "\t".$value['order_uuid'],
                        '流水订单号'=>"\t".$value['pay_order_id'],
                        '备注' => $value['remark'],
                        '操作人'=>$value['operator_name']
                    ];
                    $max_id = $value['id'];
                }
                unset($datas);
                $datas = $query->andWhere(['>','l.id',$max_id])->limit(10000)->asArray()->orderBy(['l.id'=>SORT_ASC])->all($db);
            }


	        $this->log('正在导出数据');
	        if (!$taskModel->file) {
		        $file = strtoupper(md5(uniqid(mt_rand(), true))) . '.csv';
		        $path = \Yii::getAlias('@backend/web/tmp/');
		        $csv_file = $path . $file;

		        if (!file_exists($path)) {
		            \yii\helpers\BaseFileHelper::createDirectory($path);
		        }
	        } else {
		        $csv_file = $taskModel->file;
	        }

	        $this->_exportSearchData($items, $csv_file);

	        $taskModel->file = $csv_file;
	        $taskModel->status = LoanTask::STATUS_FINISH;
	        $taskModel->updated_at = time();
	        $taskModel->save();
	        $this->log('任务执行完成');

	        unset($new_ids, $idPhones);
	        return true;
		} catch (\Exception $ex) {
            if (!empty($taskModel)) {
	        	$taskModel->status = LoanTask::STATUS_FAILD;
	        	$taskModel->updated_at = time();
	        	$taskModel->save();
            }
            throw $ex;
        }
	}

    /**
     * 导出方法
     * @param $datas
     * @param int $i
     */
    private function _exportSearchData($datas, $file) {
        return $this->_array2csv($datas, $file);
    }

    protected function _array2csv(array &$array, $file)
    {
        if (count($array) == 0) {
            return null;
        }
        // set_time_limit(0);//响应时间改为60秒
        // ini_set('memory_limit', '512M');
        ob_start();
        // $df = fopen("php://output", 'w');
        $df = fopen($file, 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }


}
