<?php
namespace common\services;
use common\models\LoanPerson;
use yii\base\Exception;
use yii\base\Component;
use common\models\asset\AssetLoadPlat;
use common\models\asset\AssetOrder;
use common\models\asset\AssetContract;
use common\models\asset\AssetOrderDeposit;
use common\models\asset\AssetRepaymentPeriod;
use common\models\asset\AssetRepayment;
use yii\base\UserException;
use common\models\CardInfo;
use common\models\asset\AssetOrderPartner;
use common\models\asset\AssetOrderLender;
use common\models\UserLoanOrderRepayment;
use common\models\asset\AssetRepayNotice;

class AssetService extends Component
{

    /********************************admin接口相关开始*************************************/
    /**
     * 查询订单相关信息
     * @param unknown $order_id
     */
    public function getOrderInfo($order_id)
    {
        return [];
    }
    /**
     * 初审订单
     * @param unknown $order_id
     */
    public function trailOrder($order_id,$result,$params=[]){
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if(!$order){//
            throw new UserException('订单不存在');
        }
        if($order->status != AssetOrder::STATUS_CHECK){//
            throw new UserException('订单状态异常');
        }
        $assetLoadPlat = AssetLoadPlat::createById($order->order_type);
        return $assetLoadPlat->trailOrder($order,$result,$params);
    }
    /**
     * 复审订单
     * @param unknown $order_id
     */
    public function retrailOrder($order_id,$result,$params=[]){
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if(!$order){//
            throw new UserException('订单不存在');
        }
        if($order->status != AssetOrder::STATUS_SIGN_SECCESS){//
            throw new UserException('订单状态异常');
        }
        $assetLoadPlat = AssetLoadPlat::createById($order->order_type);
        return $assetLoadPlat->retrailOrder($order,$result,$params);
    }
    /********************************admin接口相关结束*************************************/

    /********************************对外接口相关开始**************************************/
    private function _searchOrder($assetLoadPlat,$params){
        $order = null;
        if(isset($params['order_id']) && $params['order_id']){
            $order = $assetLoadPlat->findAssetOrder($params['order_id']);
        }else if(isset($params['out_trade_no']) && $params['out_trade_no']){
            $order = $assetLoadPlat->findAssetOrderByOutTradeNo($params['out_trade_no']);
        }
        return $order;
    }
    /**
     * 处理第三方平台推送过来的信息,保存用户信息，保存订单等
     * @param array $params
     */
    public function handlerLoanPlatOrder($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $ret = ['code'=>-1];
        if(!$assetLoadPlat->addLock('out_trade_no'.$params['order_base']['out_trade_no'])){//避免重复创建订单
            $ret['code'] = 1001;
            $ret['message'] = '该订单已在处理';
            return $ret;
        }
        try {
            if(!$assetLoadPlat->checkHandlerLoanPlatOrder($params)){
                return $ret;
            }
        } catch (Exception $e) {
            if($e->getCode() == 1001){
                $ret['code'] = $e->getCode();
            }
            $ret['message'] = $e->getMessage();
            return $ret;
        }
        $transaction = AssetOrder::getDb()->beginTransaction();
        try{
            if($params = $assetLoadPlat->handlerLoanPlatOrder()){
                $transaction->commit();
                $ret['code'] = 0;
                $ret['order_id'] = $params['order']->id;
                return $ret;
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 创建还款计划
     * @param array $params
     */
    public function handlerRepaymentPeriod($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $ret = ['code'=>-1];
        if(!$assetLoadPlat->checkHandlerRepaymentPeriod($params)){
            return $ret;
        }
        $transaction = AssetRepaymentPeriod::getDb()->beginTransaction();
        try{
            if($params = $assetLoadPlat->handlerRepaymentPeriod()){
                $transaction->commit();
                $ret['code'] = 0;
                return $ret;
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 查询订单状态
     * @param array $params
     */
    public function queryOrderStatus($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);

        $ret = ['code' => -1];
        try{
            $order = $this->_searchOrder($assetLoadPlat,$params);
            if(!$order){
                $ret['message'] = '该用户不存在该订单';
                return $ret;
            }
            $person = $assetLoadPlat->findLoadPerson($params['id_number'],$order->user_id);
	        if($person){
	        	$result = $assetLoadPlat->getQueryOrderInfoMsg($order->id);
	        	if($result){
	        		return $result;
	        	}
                $ret['code'] = 0;
                $ret['status'] = $order->status;
                $ret['loan_time'] = $order->loan_time ? date('Y-m-d H:i:s',$order->loan_time) : '';
	        }else{
	            $ret['message'] = '用户不存在';
	        }
    	}catch(\Exception $e){
    		$ret['message'] = '数据异常';
        }
        return $ret;
    }
    /**
     * 查询出借人信息
     * @param array $params
     */
    public function queryOrderRepaymentMsg($params){
        $ret = ['code'=>-1];
    	if(!isset($params['asset_ids']) || !$params['asset_ids']){
    		$ret['message'] = 'asset_ids不能为空';
    		return $ret;
    	}
    	$ids = explode(',', $params['asset_ids']);
        if('xjk' == $params['order_type']){
            $xjk_plat = AssetLoadPlat::instance('kdlc_xjk');
            $repay_plan = $xjk_plat->getRepaymentMsgByIds($ids);
        }else{
            $repay_plan = AssetLoadPlat::getRepaymentMsg($ids, $params['property_type']);
        }
        $ret['code'] = 0;
        $ret['repay_plan'] = $repay_plan;
        return $ret;
    }
    /**
     * 查询订单代扣状态
     * @param array $params
     */
    public function queryOrderRepaymentStatus($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $person = $assetLoadPlat->findLoadPerson($params['id_number']);
        $ret = ['code' => -1];
        if($person){
            $status = [];
            $periods = AssetRepaymentPeriod::findAll(['user_id'=>$person->id,'asset_order_id'=>$params['order_id']]);
            foreach($periods as $period){
                $status[] = ['period'=>$period->period,'status'=>$period->status];
            }
            $ret['code'] = 0;
            $ret['status'] = $status;
        }else{
            $ret['message'] = '用户不存在';
        }
        return $ret;
    }
    /**
     * 通知放款
     * @param array $params
     */
    public function notifyLoan($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $ret = ['code'=>-1];
        if(!$assetLoadPlat->checkHandlerNotifyLoan($params)){
            return $ret;
        }
        $transaction = AssetOrder::getDb()->beginTransaction();
        try{
            if($assetLoadPlat->handlerNotifyLoan()){
                $transaction->commit();
                $ret['code'] = 0;
                return $ret;
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 达飞发送待签署的协议
     * @param array $params
     */
    public function sendContract($params){
    	$ret = ['status' => -1];
    	$account  = $params['account'];
    	$assetLoadPlat = AssetLoadPlat::instance($account);

    	try {
    		$order = $this->_searchOrder($assetLoadPlat,$params);
    		if(!$order){
    			$ret['message'] = '该用户不存在该订单';
    			return $ret;
    		}
    		$person = $assetLoadPlat->findLoadPerson($params['id_number'],$order->user_id);

    		if($person){
    			if($order->status < AssetOrder::STATUS_LOAN_COMPLETE){
    				$ret['message'] = '订单未放款，暂时不能签署协议';
    			}else if($order){
    				if(AssetContract::saveContract($order,$params['out_trade_no'],$params)){
    					$ret['status'] = 0;
    					$ret['message'] = '发送成功';
    				}else{
    					$ret['message'] = '该订单已推送了合同';
    				}
    			}
    		}else{
    			$ret['message'] = '用户不存在';
    		}
    	} catch (\Exception $e) {
    		$ret['message'] = '数据异常';
    	}
        return $ret;
    }
    /**
     * 达飞查询签署协议
     * @param array $params
     */
    public function searchContract($params){
    	$ret = ['status' => -1];
    	try {
	    	if(!isset($params['out_trade_no_list'])||!$params['out_trade_no_list']){
	    		$ret['message'] = '无签署协议订单号列表';
	    		return $ret;
	    	}
	    	$out_trade_no_list = $params['out_trade_no_list'];
	    	if(is_array($out_trade_no_list)){
	    		$asset_contracts = AssetContract::find()->where(['contract_no' => $out_trade_no_list])->asArray()->all();
	    		if(!$asset_contracts){
	    			$ret['message'] = '无签署协议订单';
	    			return $ret;
	    		}
	    	}
	    	else{
	    		$ret['message'] = '签署协议订单不是数组';
	    		return $ret;
	    	}
	    	$user_ids = [];
	    	foreach ($asset_contracts as $asset_contract){
	    		$user_ids[] = $asset_contract['user_id'];
	    	}
	    	$loan_persons = LoanPerson::find()->where(['id' => $user_ids])->select(['id', 'id_number'])->asArray()->all();
	    	if($loan_persons){
	    		$id_number_list = [];
	    		foreach ($loan_persons as $loan_person){
	    			$id_number_list[$loan_person['id']] = $loan_person['id_number'];
	    		}
	    		$protocol_list = [];
	    		foreach ($asset_contracts as $asset_contract){
	    			$data = json_decode($asset_contract['data'], true);
	    			$return_data = [
	    					'protocol_type' => $asset_contract['asset_property_type'],
	    					'id_number' => $id_number_list[$asset_contract['user_id']],
	    					'out_trade_no' =>  $asset_contract['contract_no'],
	    			];

	    			if(!isset($data['protocol_url_done'])||!$data['protocol_url_done']){
	    				$return_data['protocol_url'] = '';
	    			}
	    			else{
	    				$return_data['protocol_url'] = $data['protocol_url_done'];
	    			}
	    			$protocol_list[] = $return_data;
	    		}
	    		$ret['status'] = 0;
	    		$ret['message'] = '查询成功';
	    		$ret['protocol_list'] = $protocol_list;
	    	}else{
	    		$ret['message'] = '用户不存在';
	    	}
    	}catch (\Exception $e){
    		$ret['message'] = '数据异常';
    	}
    	return $ret;
    }
    /**
     * 对账
     * @param array $params
     */
    public function reconciliation($params){
    	$ret['code'] = -1;
    	try {
    		$account = $params['account'];
    		$partner = AssetOrderPartner::find()->where(['account'=>$account])->one();
    		$start_time = $params['start_time'];
    		$end_time = $params['end_time'];
    		$where = 'order_time >= '.$start_time.' and order_time <= '.$end_time;
    		$orders = AssetOrder::find()->where(['order_type'=>$partner->id])->andWhere($where)->all();
    		if($orders){
    			foreach ($orders as $order){
    				$d['out_trade_no'] = $order->out_trade_no;
    				$d['order_id'] = $order->id;
    				$d['status'] = $order->status;
    				$d['loan_time'] = $order->loan_time ? date('Y-m-d H:i:s',$order->loan_time) : '';
    				$d['order_amount'] = $order->money_amount;

    				$ret['order_detail_list'][] = $d;
    			}
    			$ret['code'] = 0;
    			return $ret;
    		}else{
    			$ret['message'] = '该时间段无订单'.$where;
    			return $ret;
    		}
    	} catch (Exception $e) {
    		$ret['message'] = '数据异常';
    	}
    	return $ret;
    }

    /**
     * 查询出借人
     * @param unknown $params
     */
    public function queryLender($params){
    	$ret['code'] = -1;
    	try {
    		$asset_load_plat = AssetLoadPlat::instance($params['account']);
    		$order = $this->_searchOrder($asset_load_plat, $params);
    		if($order){
    			$result = $asset_load_plat->getLenderMsg($order);
    			if($result){
    				return $result;
    			}else{
    				$ret['message'] = '查询出借人信息失败';
    			}
    		}else{
    			$ret['message'] = '该用户不存在该订单';
    		}
    	}catch (Exception $e){
    		$ret['message'] = '数据异常';
    	}
    	return $ret;
    }

    /**
     * 查询订单池
     * @param unknown $params
     */
    public function searchOrder($params){
        $account  = $params['account'];
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $ret = ['code' => 0];
        $ret['data'] = $assetLoadPlat->searchOrder($params);
        return $ret;
    }
    /**
     * 更新订单池capital_trade_no属性
     * @param unknown $params
     */
    public function handlerCapitalTradeNo($params){
        $account  = $params['account'];
        $ret = ['code' => -1];
        if(!isset($params['order_ids']) || !$params['order_ids']){
            $ret['message'] = 'order_ids不能为空';
            return $ret;
        }
        if(!isset($params['sum_money']) || !intval($params['sum_money'])){
            $ret['message'] = 'sum_money不能为空';
            return $ret;
        }
        if(!isset($params['sum_interests']) || !intval($params['sum_interests'])){
            $ret['message'] = 'sum_interests不能为空';
            return $ret;
        }
        if(!isset($params['capital_trade_no']) || !$params['capital_trade_no']){
            $ret['message'] = 'capital_trade_no不能为空';
            return $ret;
        }
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $assetLoadPlat->checkHandlerCapitalTradeNo($params);
        $transaction = AssetOrder::getDb()->beginTransaction();
        try{
            if($assetLoadPlat->handlerCapitalTradeNo($params) && $assetLoadPlat->checkHandlerCapitalTradeNo($params,1)){
                $transaction->commit();
                $ret['code'] = 0;
                return $ret;
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 资金方定期同步状态给我们
     * @param unknown $params
     */
    public function syncOrderStatus($params){
        $account  = $params['account'];
        $status = isset($params['status']) ? intval($params['status']) : 0;
        $ret = ['code' => -1];
        if(!isset($params['order_ids']) || !$params['order_ids']){
            $ret['message'] = 'order_ids不能为空';
            return $ret;
        }
        if(!isset($params['capital_trade_no']) || !$params['capital_trade_no']){
            $ret['message'] = 'capital_trade_no不能为空';
            return $ret;
        }
        if($status != 1 && $status != 2){
            $ret['message'] = 'status非法';
            return $ret;
        }
        $assetLoadPlat = AssetLoadPlat::instance($account);
        $transaction = AssetOrder::getDb()->beginTransaction();
        try{
            if($assetLoadPlat->syncOrderStatus($params,$status)){
                $transaction->commit();
                $ret['code'] = 0;
                return $ret;
            }
            $transaction->rollBack();
        }catch (\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 资金方同步出借人信息
     * @param unknown $params
     */
    public function syncLenderMsg($params){
    	$ret = ['code'=>-1];
    	if(!isset($params['order_id']) || !$params['order_id']){
    		$ret['message'] = 'order_id不能为空';
    		return $ret;
    	}
    	if(!isset($params['lender_id']) || !$params['lender_id']){
    		$ret['message'] = 'lender_id不能为空';
    		return $ret;
    	}
    	if(!isset($params['lender_name']) || !$params['lender_name']){
    		$ret['message'] = 'lender_name不能为空';
    		return $ret;
    	}
    	if(!isset($params['lender_id_number']) || !$params['lender_id_number']){
    		$ret['message'] = 'lender_id_number不能为空';
    		return $ret;
    	}
    	if(!isset($params['lender_ca_number']) || !$params['lender_ca_number']){
    		$ret['message'] = 'lender_ca_number不能为空';
    		return $ret;
    	}
    	if(AssetOrderLender::saveRecord($params)){
    		$ret['code'] = 0;
    		$ret['meg'] = '推送成功';
    	}else{
    		$ret['meg'] = '推送失败';
    	}
    	return $ret;
    }
    /**
     * 接受资金方已满款通知
     * @param unknown $params
     */
    public function acceptFullMoney($params){
    	$ret = ['code'=>-1];
    	$fail_ids = [];
    	if(!isset($params['asset_ids']) || !$params['asset_ids']){
    		$ret['message'] = 'asset_ids不能为空';
    		return $ret;
    	}
    	$asset_ids = explode(',', $params['asset_ids']);
    	if(is_array($asset_ids)){
    		foreach ($asset_ids as $asset_id){
    			$order_lender = AssetOrderLender::findOne(['order_id'=>$asset_id]);
    			if($order_lender){
    				if($order_lender->is_full_money!=1){
    					$order_lender->is_full_money = 1;
    					$result = $order_lender->save();
    					if(!$result){
    						$fail_ids[] = $asset_id;
    					}
    				}
    			}else{
    				$order_lender = new AssetOrderLender();
    				$order_lender->order_id = $asset_id;
    				$order_lender->is_full_money = 1;
    				$result = $order_lender->save();
    				if(!$result){
    					$fail_ids[] = $asset_id;
    				}
    			}
    		}
    	}else{
    		$ret['message'] = 'asset_ids有误';
    		return $ret;
    	}
    	$ret['code']=0;
    	$ret['fail_ids'] = implode(',', $fail_ids);
    	$ret['message'] = '推送成功';
    	return $ret;
    }
    /**
     * 债权信息补全
     * @param unknown $params
     */
    public function fixOrder($params){
    	$ret = ['code'=>-1];
    	$data = [];
    	if(!isset($params['asset_ids']) || !$params['asset_ids']){
    		$ret['message'] = 'asset_ids不能为空';
    		return $ret;
    	}
    	$asset_ids = explode(',', $params['asset_ids']);
    	if(is_array($asset_ids)){
    		//借款用途
    		$partners = AssetOrderPartner::find()->asArray()->all();
    		foreach ($partners as $partner){
    			$loan_purposes[$partner['id']] = $partner['loan_purpose'];
    		}
    		$orders = AssetOrder::find()->from(AssetOrder::tableName().' as a ')->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')->where(['a.id'=>$asset_ids])->select(['a.id','a.order_type', 'b.id_number'])->asArray()->all();
    		foreach ($orders as $order){
    			$data[] = [
    				'asset_id' => $order['id'],
    				'asset_id_number' => $order['id_number'],
    				'loan_purpose' => $loan_purposes[$order['order_type']],
    			];
    		}
    		if(empty($data)){
    			$ret['message'] = '未找到对应数据';
    			return $ret;
    		}else{
    			$ret['code'] = 0;
    			$ret['meg'] = '查找成功';
    			$ret['data'] = $data;
    			return $ret;
    		}
    	}else{
    		$ret['message'] = 'asset_ids有误';
    		return $ret;
    	}
    }
    /**
     * 口袋获取房抵信息接口
     * @param unknown $params
     */
    public function getFdMsg($params){
    	$account = 'kdlc_fd';
    	$assetLoadPlat = AssetLoadPlat::instance($account);

    	if($params['asset_id']){
    		$data = $assetLoadPlat->getUniqueData($params['asset_id']);
    		if($data){
    			return ['code'=>0, 'msg'=>'获取信息成功', 'data'=>$data];
    		}else{
    			return ['code'=>-1, 'msg'=>'获取信息失败'];
    		}
    	}else{
    		return ['code'=>-1, 'msg'=>'asset_id不能为空'];
    	}
    }

    /**
     * 推送提前还款接口
     * @param unknown $params
     */
    public function sendEarlyRepayment($params){
    	return [];
    }

    /**
     * 接收还款通知
     * @param unknown $params
     * asset_id 债权ID
     * asset_period 期数
     * repay_type 还款类型
     * repay_time 还款时间
     * is_prepay 是否垫付
     * repay_amount 还款金额
     */
    public function getAssetNotice($params){
        $repay_data = $params['data'];
        if (empty($repay_data)) {
            throw new UserException('非法访问', 1002);
        }

        $repay_data = json_decode($repay_data, true);
        if (empty($repay_data) || !is_array($repay_data)) {
            throw new UserException('无效的参数', 1003);
        }
        $error_list = [];
        $insert_arr = [];
        $repay_data_third_no = array_column($repay_data,'asset_id');
        $order_type = AssetOrderPartner::find()->select('id')->where(['account'=>'kdlc_xjk'])->asArray()->limit(1)->one();
        foreach ($repay_data_third_no as $d){
            $out_trade_nos[] = strval($d);
        }
        $asset_order = AssetOrder::find()->select(['id','out_trade_no'])->where(['IN','out_trade_no',$out_trade_nos])->andWhere(['order_type'=>$order_type['id']])->asArray()->all();
        if(!empty($asset_order)) {
            $out_trade = array_column($asset_order, 'out_trade_no');
            $asset_order_use = array_combine($out_trade, $asset_order);
            foreach ($repay_data as $d) {
                if (isset($asset_order_use[$d['asset_id']]))
                    array_push($insert_arr, [$asset_order_use[$d['asset_id']]['id'], $d['asset_period'], $d['repay_type'], $d['repay_time'], $d['is_prepay'], $d['repay_amount'], time(), 3]);
                else
                    array_push($error_list, ['asset_id' => $d['asset_id'], 'period' => $d['asset_period']]);
            }
            if (!empty($insert_arr)) {
                $sql = \Yii::$app->db_kdkj->queryBuilder->batchInsert(AssetRepayNotice::tableName(), ['asset_id', 'asset_period', 'repay_type', 'repay_time', 'is_prepay', 'repay_amount', 'created_at', 'status'], $insert_arr);
                $sql_res = \Yii::$app->db_kdkj->createCommand($sql)->execute();
            }
        }else{
            return [
                'code' => 0,
                'error_list' => 'all',
            ];
        }
        return [
            'code' => 0,
            'error_list' => json_encode($error_list),
        ];
    }
    /********************************对外接口相关结束**************************************/

    /********************************财务回调接口相关开始**********************************/
    /**
     * 打款前对账
     * @param unknown $order_id
     */
    public function withdrawCheckLoanOrder($order_id){
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if(!$order){
            return [
                'code'=>-1,
                'message'=>'获取订单数据失败',
            ];
        }
        $data = array();
        if($order->property_type == AssetOrderPartner::PROTERTY_ZHAIQUAN){
            try{
                $datas = AssetLoadPlat::getFinancialLoanRecordDatas($order,AssetOrder::getRealMoneyAmount($order->id));
                $data = $datas['data'];
                $data['status']= $order->status;
            }catch (\Exception $e){
                return [
                    'code'=>-1,
                    'message'=>$e->getMessage(),
                ];
            }
        }else{
            $data['money']= $order->money_amount;
            $data['counter_fee']=$order->counter_fee;
            $data['status']= $order->status;
            $data['user_id'] = $order->user_id;
            $card_id = $order->receive_card_id;
            $card_info = CardInfo::findOne([/*'user_id'=>$data['user_id'],*/'id'=>$card_id]);
            if(!$card_info){
                return [
                        'code'=>-2,
                        'message'=>'获取银行卡信息失败',
                ];
            }
            $data['bank_id']=$card_info->bank_id;
            $data['card_no']=$card_info->card_no;
        }
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];
    }
    public function withdrawCheckDebitOrder($order_id,$repayment_id, $repayment_period_id){
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if(!$order){
            return [
                'code'=>-1,
                'message'=>'获取订单数据失败',
            ];
        }
        $orderRepayment = AssetRepayment::findOne(['id'=>$repayment_id]);
        if(!$orderRepayment || $orderRepayment->status == AssetRepayment::STATUS_SUCCESS){
            return [
                'code'=>-2,
                'message'=>'获取分期总表数据失败',
            ];
        }
        $repaymentPeriod = AssetRepaymentPeriod::findOne(['id'=>$repayment_period_id,'asset_order_id'=>$order_id,'asset_repayment_id'=>$repayment_id,'status'=>AssetRepaymentPeriod::STATUS_DEBIT_ING]);
        if(!$repaymentPeriod){
            return [
                'code'=>-3,
                'message'=>'获取分期计划分期表数据失败',
            ];
        }

        $data = array();
        $data['plan_repayment_money']=$repaymentPeriod->plan_repayment_money;
        $data['plan_repayment_time']=$repaymentPeriod->plan_repayment_time;
        $data['user_id']=$repaymentPeriod->user_id;

        $card_id = $repaymentPeriod->card_id;
        $cardInfo = CardInfo::findOne(['user_id'=>$data['user_id'],'id'=>$card_id]);
        if(!$cardInfo){
            return [
                'code'=>-2,
                'message'=>'获取银行卡信息失败',
            ];
        }
        $data['debit_card_id']=$cardInfo->card_no;
        $data['repayment_id']=$repaymentPeriod->asset_repayment_id;
        $data['repayment_peroid_id']=$repaymentPeriod->id;
        $data['type']=AssetLoadPlat::getPlatFinancialType($order->order_type);
        $data['status']=$repaymentPeriod->status;
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];
    }

    /**
     * 财务打款结果通知回调接口
     * @param unknown $order_id
     * @param unknown $result 0驳回，1成功
     * @param string $username
     */
    public function loadCallbackPayMoney($order_id, $result, $username = '',$remark='银行返回',$params=[]){
        $ret = [
            'code'=>-1,
            'message'=>'处理失败',
        ];
        $status = $result ? AssetOrder::STATUS_LOAN_COMPLETE : AssetOrder::STATUS_LOAN_FAIL;
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if($order->status != AssetOrder::STATUS_LOAN_ING){
            return [
                'code'=>-1,
                'message'=>'订单状态不对，请尽快通知相关人员',
            ];
        }
        $moneyAmount = 0;
        $loanTime = isset($params['loanTime']) && $params['loanTime'] ? $params['loanTime'] : time();//放款时间
        $plat = AssetLoadPlat::createById($order->order_type);
        $transaction = isset($params['untransaction']) ? null : AssetOrder::getDb()->beginTransaction();
        try{
            if($order->property_type == AssetOrderPartner::PROTERTY_ZHAIQUAN){
                $where = AssetOrder::cmpBatchWhere($order);
                $moneyAmount = AssetOrder::getRealMoneyAmount($order->id);
                if(!$moneyAmount){
                    $moneyAmount = AssetOrder::find()->where($where)->select('sum(money_amount) as money_amount')->one()['money_amount'];
                }
                $attr = ['status'=>$status,'operator_name'=>$username,'reason_remark'=>$remark,'loan_time'=>$loanTime,'updated_at'=>time()];
                $res = AssetOrder::updateAll($attr,$where);
            }else{
                $moneyAmount = $order->money_amount;
                $res = AssetOrder::updateLoadNotify($order_id, $status,['operator_name'=>$username,'reason_remark'=>$remark,'loan_time'=>$loanTime]);
            }
            if($res){
                $order->status = $status;
                if($status == AssetOrder::STATUS_LOAN_COMPLETE){//将保证金和服务费记录修改成生效
                    $plat->updateRepaymentAndPeriod($order,$moneyAmount,date('Y-m-d H:i:s',$loanTime));
                    $plat->addDepositData($order, $moneyAmount,AssetOrderDeposit::TYPE_ADD,0,$loanTime);//新增风险保证金
                    $plat->addServiceChargeData($order, $moneyAmount,$loanTime);//计算平台对账服务费
                    $plat->addCounterFee($order,$moneyAmount,$loanTime);//计算手续费
                    AssetRepayment::updateCreditRepaymentTime($order->id,$order->user_id,$loanTime);
                }
                $plat->sendCallbackNotify($order,['loanTime'=>$loanTime]);
                $ret = [
                    'code'=>0,
                    'message'=>'处理成功',
                ];
                if($transaction){
                    $transaction->commit();
                }
            }else{
                throw new \Exception('loadCallbackPayMoney打款回调更新失败');
            }
        }catch(\Exception $e){
            if($transaction){
                $transaction->rollBack();
            }
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 财务扣款结果通知回调接口
     * @param unknown $order_id 订单ID
     * @param unknown $repayment_id 借款订单ID
     * @param unknown $repayment_period_id 分期还款ID
     * @param unknown $result 1成功，2失败，3驳回
     * @param string $remark 备注
     * @param string $username 操作用户
     * @param array $params 其他参数
     */
    public function debitCallbackPayMoney($order_id, $repayment_id, $repayment_period_id, $result, $remark = '', $username = '',$params=[]){
        $ret = [
            'code'=>-1,
            'message'=>'处理失败',
        ];
        if(1 == $result){
            $status = AssetRepaymentPeriod::STATUS_DEBIT_SUCCESS;
        }else if(2 == $result){
            $status = AssetRepaymentPeriod::STATUS_DEBIT_FAIL;
        }else{
            $status = AssetRepaymentPeriod::STATUS_DEBIT_REFUSE;
        }
        $order = AssetOrder::findOne(['id'=>$order_id]);
        if(!$order){
            return [
                    'code'=>-1,
                    'message'=>'获取订单数据失败',
            ];
        }
        if(!in_array($order->status, [AssetOrder::STATUS_LOAN_COMPLETE,AssetOrder::STATUS_REPAY_PART])){
            return [
                    'code'=>-1,
                    'message'=>'该订单状态无法进行扣款状态变更',
            ];
        }
        if($status != AssetRepaymentPeriod::STATUS_DEBIT_SUCCESS){
            $flag = AssetRepaymentPeriod::updateNotifyStatus($repayment_period_id,$status,['operator_name'=>$username,'reason_remark'=>$remark]);
            if($flag){
                if($status == AssetRepaymentPeriod::STATUS_DEBIT_FAIL){//扣款失败，需要统计扣款失败次数
                    AssetRepayment::updateDebitFailTimes($repayment_id);
                }
                return [
                        'code'=>0,
                        'message'=>'操作成功',
                ];
            }
            return $ret;
        }
        $repayment = AssetRepayment::findOne(['user_id'=>$order->user_id,'id'=>$repayment_id]);
        if(!$repayment){
            return [
                'code'=>-1,
                'message'=>'获取总表数据失败',
            ];
        }
        $period = AssetRepaymentPeriod::findOne(['user_id'=>$repayment->user_id,'asset_order_id'=>$order_id,'asset_repayment_id'=>$repayment_id,'id'=>$repayment_period_id]);
        if(!$period){
            return [
                'code'=>-1,
                'message'=>'获取分期计划表数据失败',
            ];
        }
        if($period->status == AssetRepaymentPeriod::STATUS_DEBIT_SUCCESS){
            return [
                    'code'=>-1,
                    'message'=>'该计划已还款',
            ];
        }
        if($repayment->next_period_repayment_id != $period->period){
            if(!(!$repayment->next_period_repayment_id && $period->period == 1)){
                return [
                        'code'=>-1,
                        'message'=>'还款顺序错误',
                ];
            }
        }
        $plat = AssetLoadPlat::createById($order->order_type);
        $transaction = AssetRepaymentPeriod::getDb()->beginTransaction();
        try{
            $orderAttr = [];
            $totalPeriod = $repayment->period;
            $currPeriod = $period->period;
            $period->updated_at = time();
            $period->status = $status;
            $period->true_repayment_money = isset($params['true_repayment_money']) ? $params['true_repayment_money'] : $period->plan_repayment_money;
            $period->true_repayment_time = isset($params['true_repayment_time']) ? $params['true_repayment_time'] : time();
            $period->true_repayment_principal = isset($params['true_repayment_principal']) ? $params['true_repayment_principal'] : $period->plan_repayment_principal;
            $period->true_repayment_interest = isset($params['true_repayment_interest']) ? $params['true_repayment_interest'] : $period->plan_repayment_interest;
            $period->true_late_fee = isset($params['true_late_fee']) ? $params['true_late_fee'] : $period->plan_late_fee;
            $period->admin_username = $username;
            if(isset($params['repayment_img']) && $params['repayment_img']){
                $period->repayment_img = $params['repayment_img'];
            }
            if(isset($params['order_id']) && $params['order_id']){
                $period->order_id = $params['order_id'];
            }
            if(!$period->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新分期计划表数据失败',
                ];
            }

            $repayment->updated_at = time();
            $repayment->repaymented_amount = $repayment->repaymented_amount+$period->true_repayment_money;
            $repayment->next_period_repayment_id = $currPeriod+1;
            if($totalPeriod == $currPeriod /*&& $repayment->repaymented_amount >= $repayment->repayment_amount*/){
                $repayment->next_period_repayment_id = 0;
                $repayment->status= AssetRepayment::STATUS_SUCCESS;
                $orderAttr['status'] = AssetOrder::STATUS_REPAY_COMPLETE;
            }else{
                $orderAttr['status'] = AssetOrder::STATUS_REPAY_PART;
            }
            if(!$repayment->save()){
                $transaction->rollBack();
                return [
                    'code'=>-1,
                    'message'=>'更新还款总表数据失败',
                ];
            }
            $orderAttr['updated_at'] = time();
            if($order->property_type == AssetOrderPartner::PROTERTY_ZHAIQUAN){
                $where = AssetOrder::cmpBatchWhere($order);
                $money_amount = AssetOrder::getRealMoneyAmount($order->id);
                if(!$money_amount){
                    $money_amount = AssetOrder::find()->where($where)->select('sum(money_amount) as money_amount')->one()['money_amount'];
                }
            }else{
                $money_amount = $order->money_amount;
                $where = ['id'=>$order->id];
            }
            if(!AssetOrder::updateAll($orderAttr,$where) && !($order->updated_at == $orderAttr['updated_at'])){
                $transaction->rollBack();
                return [
                        'code'=>-1,
                        'message'=>'更新订单总表数据失败',
                ];
            }
            $order->status = $orderAttr['status'];
            $plat->addDepositData($order, round($money_amount/$totalPeriod),AssetOrderDeposit::TYPE_SUB,$currPeriod);
            if($order->status == AssetOrder::STATUS_REPAY_COMPLETE){//减少保证金
                $plat->sendCallbackNotify($order);
            }


            $asset_repay_notice = new AssetRepayNotice();

            $asset_repay_notice->asset_id = $order_id;
            $asset_repay_notice->repay_type = $repayment['repayment_type'];
            $asset_repay_notice->repay_amount = $period['plan_repayment_principal'];
            $asset_repay_notice->repay_time = $period['plan_repayment_time'];
            $asset_repay_notice->created_at = time();
            $asset_repay_notice->status = 3;
            $asset_repay_notice->is_prepay = 0;
            $asset_repay_notice->asset_period = $period['period'];
            if($period['plan_repayment_principal']!=0)
                $asset_repay_notice->save();

            $transaction->commit();
            return [
                'code'=>0,
                'message'=>'操作成功',
            ];
        }catch(\Exception $e){
            $transaction->rollBack();
            $ret['message'] = $e->getMessage();
            \Yii::error($e->getMessage().$e->getFile().$e->getLine());
        }
        return $ret;
    }
    /**
     * 财务扣款失败通知统计回调接口
     * @param unknown $order_id 订单ID
     * @param unknown $repayment_id 借款订单ID
     * @param unknown $repayment_period_id 分期还款ID
     * @param array $params 其他参数
     */
    public function debitFalseCallbackSt($order_id, $repayment_id, $repayment_period_id, $params=[]){
        AssetRepayment::updateDebitFailTimes($repayment_id);
        return [
                'code'=>0,
                'message'=>'操作成功',
        ];
    }
    /********************************财务回调接口相关结束**********************************/

    //获取中智诚所需的订单信息
    public function getOrderZzcParams($order_id){
        $assetOrder = AssetOrder::findOne($order_id);
        if(is_null($assetOrder)){
            throw new Exception('订单不存在');
        }
        $loanPerson = LoanPerson::findOne($assetOrder->user_id);
        if(is_null($loanPerson)){
            throw new Exception('借款人信息不存在');
        }
        $info = [];
        $info['name'] = trim($loanPerson->name);
        $info['pid'] = trim($loanPerson->id_number);
        $info['mobile'] = trim($loanPerson->phone);
        $info['loan_term'] = intval($assetOrder->loan_term);
        foreach ($info as $k=>$item) {
            if(empty($item)){
                throw new Exception("{$k}不能为空");
            }
        }
        $params = [
            'loan_type' => '消费金融',
            'loan_purpose' => '消费',
            'loan_term' => $info['loan_term'],
            'applicant' => [
                'name' => $info['name'],
                'pid' => $info['pid'],
                'mobile' => $info['mobile'],
            ]
        ];
        return $params;

    }

}