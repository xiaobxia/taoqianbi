<?php
namespace console\soa;

use common\models\CardInfo;
use common\models\FinancialLoanRecord;
use common\models\fund\LoanFund;
use common\models\LoanPerson;
use common\models\UserCreditMoneyLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;

class ExportLoanTask extends \common\models\LoanTask
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
            if (!empty($search['username'])) {
                $username = $search['username'];
                $result = LoanPerson::find()->select(['id'])->where(['name' => $username])->all();
                if ($result) {
                    $uid = [];
                    foreach($result as $id){
                        $uid[] = $id['id'];
                    }
                    $uid = implode(',',$uid);
                    $condition .= " AND l.user_id in ({$uid}) ";
                }else{
                    $condition .= " AND l.user_id = 0" ;
                }
            }
            if (!empty($search['phone'])) {
                $phone = $search['phone'];
                $result = LoanPerson::find()->select(['id'])->where(['phone' => $phone])->asArray()->all();
                if(!empty($result)){
                    $user_list = [];
                    foreach($result as $v){
                        $user_list[] = $v['id'];
                    }
                }else{
                    $user_list = [0];
                }
                $user_list = implode(',',$user_list);
                $condition .= " AND l.user_id in ({$user_list})";
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = " . intval($search['user_id']);
            }
            if (!empty($search['rid'])) {
                $condition .= " AND l.id = " . "'".$search['rid']."'";
            }
            if (!empty($search['loan_term'])) {
                $condition .= " AND u.loan_term = " . "'".$search['loan_term']."'";
            }
            if (!empty($search['loan_amount_min'])) {
                $condition .= " AND l.money >= " . $search['loan_amount_min'] * 100;
            }
            if (!empty($search['loan_amount_max'])) {
                $condition .= " AND l.money <= " . $search['loan_amount_max'] * 100;
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.business_id = " . "'".$search['order_id']."'";
            }
            if (!empty($search['source_id'])) {
                $condition .= " AND p.source_id = " . "'".$search['source_id']."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND l.order_id = " . "'".trim($search['pay_order_id'])."'";
            }
            if (!empty($search['status'])) {
                $condition .= " AND l.status = " . intval($search['status']);
            }
            if (isset($search['type']) && $search['type'] != null) {
                $condition .= " AND l.type = " . intval($search['type']);
            }
            if (!empty($search['payment_type'])) {
                $condition .= " AND l.payment_type = " . intval($search['payment_type']);
            }
            if (isset($search['review_result']) && $search['review_result'] !== '') {
                $condition .= " AND l.review_result = " . intval($search['review_result']);
                $is_review_result = true;
            }
            if (isset($search['callback_result']) && $search['callback_result'] !== '') {
                if($search['callback_result']){
                    $condition .= " AND l.callback_result like '{\"is_notify\":".intval($search['callback_result']).",%'" ;
                }else{
                    $condition .= " AND l.callback_result = '0'" ;
                }
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND l.created_at >= " . strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND l.created_at < " . strtotime($search['endtime']);
            }
            if (!empty($search['updated_at_begin'])) {
                $condition .= " AND l.success_time >= " . strtotime($search['updated_at_begin']);
            }
            if (!empty($search['updated_at_end'])) {
                $condition .= " AND l.success_time < " . strtotime($search['updated_at_end']);
            }
            if (isset($search['fund_id']) && !empty($search['fund_id']) && $search['fund_id'] >0) {
                if($search['fund_id'] == LoanFund::ID_KOUDAI){
                    $condition .= " AND u.fund_id IN (" . LoanFund::ID_KOUDAI .", 0 ) ";
                }else{
                    $condition .= " AND u.fund_id = " . (int)$search['fund_id'];
                }
            }

            $this->log('开始查询数据');
            $max_id = 0;
            $query = FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')
                ->where(['in', 'l.type', FinancialLoanRecord::TYPE_LQD])
                ->andwhere($condition)
                ->select(['l.id','l.business_id','l.order_id','l.money','l.counter_fee','l.bank_name','l.card_no','l.type','l.payment_type','l.status','l.created_at','l.success_time','p.id_number','p.phone','p.name as personName','u.loan_term','u.loan_method','u.fund_id','c.name as cardName'])
                ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')
                ->leftJoin(UserLoanOrder::tableName().' as u','l.business_id=u.id')
                ->leftJoin(CardInfo::tableName().' as c','l.bind_card_id=c.id')
                ->orderBy(['l.id' => SORT_ASC]);
            $count = 0;
            $datas = $query->andWhere(['>','l.id',$max_id])->limit(5000)->asArray()->all($db);
            $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
            $all_funds = \common\models\fund\LoanFund::getAllFundArray();
            $items = [];
            while($datas){
                foreach($datas as $key=>$value){
                    $items[$count] = [
                        '订单ID' => $value['order_id'],
                        '资方' =>  isset($value['fund_id']) && !empty($all_funds[$value['fund_id']]) ? $all_funds[$value['fund_id']] :$fund_koudai->name,
                        '打款ID' => $value['id'],
                        '业务订单ID' => $value['business_id'],
                        '姓名' => $value['personName'],
                        '申请金额' => sprintf('%.2f', $value['money'] / 100),
                        '手续费' => sprintf('%.2f', $value['counter_fee'] / 100),
                        '实际打款金额' => sprintf('%.2f',  ($value['money'] - $value['counter_fee']) / 100),
                        '持卡人姓名' => $value['cardName'],
                        '绑卡银行' => $value['bank_name'],
                        '手机号' => substr_replace($value['phone'],'****',3,4),
                        '身份证号' => substr_replace($value['id_number'],'********',6,8),
                        '银行卡号' => "\t".$value['card_no'],
                        '业务类型' => isset(FinancialLoanRecord::$types[$value['type']]) ? FinancialLoanRecord::$types[$value['type']] : "---",
                        '打款状态' => empty($value['status']) ? "---" : FinancialLoanRecord::$ump_pay_status[$value['status']],
                        '打款渠道' => isset(FinancialLoanRecord::$payment_types[$value['payment_type']]) ? FinancialLoanRecord::$payment_types[ $value['payment_type']] : "-----",
                        '申请时间' => date('Y-m-d H:i', $value['created_at']),
                        '成功时间' => $value['success_time'] ? date('Y-m-d H:i', $value['success_time']) : '',
                    ];
                    if($value['loan_method']==0)
                    {
                        $items[$count]['借款期限']=empty($value['loan_term']) ? 0 : $value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                    }
                    elseif($value['loan_method']==1)
                    {
                        $items[$count]['借款期限']=$value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                    }else
                    {
                        $items[$count]['借款期限']=$value['loan_term'].UserLoanOrder::$loan_method[$value['loan_method']];
                    }
                    $count++;
                    $max_id = $value['id'];
                }
                if($count > 80000){
                    break;
                }
                unset($datas);
                $datas = $query->andWhere(['>','l.id',$max_id])->limit(5000)->asArray()->all();
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
            var_dump($ex->getTraceAsString());
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
