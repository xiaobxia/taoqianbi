<?php
namespace console\soa;

use common\models\LoanPerson;
use common\models\WeixinUser;
use common\models\UserLoanOrder;
use common\models\LoanBlackList;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use common\models\UserLoanOrderRepayment;
use common\helpers\ArrayHelper;

class LoanTask extends \common\models\LoanTask
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
	        // $params = current($params);
	        // \common\helpers\Util::cliLimitChange(1024);
	        // set_time_limit(300);


			$db = \Yii::$app->db_kdkj_rd_new;

	        $arr_exports = [];
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

	        // $params = json_decode('{"r":"loan\/search-list","reg_begintime":"2017-08-01 15:05:00","reg_endtime":"2017-08-2 15:14:00","search_submit":"\u67e5\u8be2"}', true);
	        $order_num = ArrayHelper::getValue($params, 'order_num');
	        $weixin = ArrayHelper::getValue($params, 'weixin');

	        $step = 100000;
	        $id = 0;
	        $res_loan_person = [];
	        $have_condition = false;
	        if (empty($params['reg_begintime']) || empty($params['reg_endtime'])) {
	            throw new \Exception("搜索必须带时间范围条件", 3000);
	        }

	        // 注册时间
	        $where_regist = [
	            'and',
	            ['>=', 'created_at', strtotime($params['reg_begintime'])],
	            ['<=', 'created_at', strtotime($params['reg_endtime'])],
	        ];
	        $this->log('任务正在执行');

	        // $new_ids = UserRegisterInfo::find()
	        //     ->where($where_regist)
	        //     ->select(['user_id'])
	        //     ->indexBy('user_id')
	        //     ->column($db);

	        // 用户基本信息开始 ===================================
	        $this->log('正在查询用户基本信息');

	        $condition_info = [
	            'and',
	            [LoanPerson::tableName().".status" => LoanPerson::PERSON_STATUS_PASS]
	        ];
	        if (!empty($params['sex'])) {
	            $condition_info[] = ['=', LoanPerson::tableName().'.property', $params['sex']];
	        }
	        if (!empty($params['source_id'])) {
	            $condition_info[] = ['=', LoanPerson::tableName().'.source_id', $params['source_id']];
	        }

	        $select_person = [
	            LoanPerson::tableName() . '.phone',
	            LoanPerson::tableName() . '.id',
	        ];

	        // $having_age = [];
	        // if (!empty($params['age'])) {
	        //     $having_age['age'] = $params['age'];
	        //     $select_person[] = "year(from_days(datediff(now(), FROM_UNIXTIME(birthday,'%Y-%m-%d')))) as age";
	        // }

	        $new_ids = [];
            $idPhones = LoanPerson::find()
                ->select($select_person)
                ->filterWhere($condition_info)
                ->andFilterWhere($where_regist)
                // ->filterHaving($having_age)
	            ->indexBy('id')
                ->asArray()
                ->column($db);
            foreach ($idPhones as $key => $val) {
                $new_ids[$key] = $key;
            }
	        // 用户基本信息结束 ===================================

	        $this->log('原始数据查询完毕');
	        // 微信绑定条件==============================
	        $this->log('正在查询微信绑定条件');
	        $where_weixin = ['and'];
	        $where_weixin[] = ['in', UserRegisterInfo::tableName() . '.user_id', $new_ids];
	        $isweixin = false;
	        $having_weixin = [];
	        if ($weixin == 1) {
	            $isweixin = true;
	            $where_weixin[] = ['!=', WeixinUser::tableName().'.uid', 0];
	            $having_weixin = [];
	            $have_condition = true;
	        } else if ($weixin == 2) {

	            $having_weixin = [
	                'or',
	                ['=', 'uid', 0],
	                ['=', 'weixin_num', 0]
	            ];
	            $isweixin = true;
	            $have_condition = true;
	        }
	        if ($isweixin) {
	            for ($i = 0; $i <= 999; $i ++) {

	                $res = UserRegisterInfo::find()
	                    ->leftJoin(WeixinUser::tableName(), WeixinUser::tableName() . '.uid=' . UserRegisterInfo::tableName() . '.user_id')
	                    ->select([
	                        UserRegisterInfo::tableName().'.user_id',
	                        UserRegisterInfo::tableName().'.id',
	                        WeixinUser::tableName().'.uid as uid',
	                        "count(" . WeixinUser::tableName() . ".id) as weixin_num",
	                    ])
	                    ->filterWhere($where_weixin)
	                    ->filterHaving($having_weixin)
	                    ->andfilterWhere(['>', UserRegisterInfo::tableName().'.id', $id])
	                    ->groupBy(UserRegisterInfo::tableName().'.user_id')
	                    ->limit($step)
	                    ->orderBy([UserRegisterInfo::tableName().'.id' => SORT_ASC])
	                    ->asArray()
	                    ->indexBy('id')
	                    ->column($db);

	                foreach ($res as $key => $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                    $id = $key;
	                }
	                if ($res) {
	                    unset($res);
	                } else {
	                    break;
	                }
	            }
	        }
	        if (!empty($new_ids_rel)) {
	            $new_ids = $new_ids_rel;
	        }
	        $this->log('微信绑定条件查询结束');
	        //微信关注条件结束 ============================================================

	        // 用户状态条件开始 ===========================================
	        $this->log('正在查询用户状态条件');
	        $new_ids_rel = [];
	        if (isset($params['user_status_no_ver'])) { // 待认证状态

	            $where_verification = [
	                'or',
	                ['!=', 'real_verify_status', 1],
	                ['!=', 'real_contact_status', 1],
	                ['!=', 'real_bind_bank_card_status', 1],
//	                ['!=', 'real_zmxy_status', 1],
	                ['!=', 'real_jxl_status', 1],
	            ];
	            $and_where_ver = [];
	            if (!empty($new_ids)) {
	                $and_where_ver = [
	                    'and',
	                    ['in', 'user_id', $new_ids]
	                ];
	            }

	            if ($have_condition && empty($new_ids)) {
	                // 无数据
	            } else {
	                $res_ver = UserVerification::find()
	                    ->select(['user_id'])
	                    ->filterWhere($where_verification)
	                    ->andFilterWhere($and_where_ver)
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_ver as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_ver);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }
	            $have_condition = true;

	        }

	        $this->log('正在查询用户状态条件--待申请状态');
	        if (isset($params['user_status_wait'])) { //待申请状态 = 从来未借过款 + 当前还过款

	            $where_wait = ['and'];
	            if (!empty($new_ids)) {
	                $where_wait[] = ['in', UserRegisterInfo::tableName().'.user_id', $new_ids];
	            }

	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_wait = UserRegisterInfo::find()
	                    ->leftJoin(UserLoanOrder::tableName(), UserLoanOrder::tableName() . '.user_id=' . UserRegisterInfo::tableName() . '.user_id')
	                    ->select([
	                        UserRegisterInfo::tableName().'.user_id',
	                        UserLoanOrder::tableName().'.status as status',
	                        "count(" . UserLoanOrder::tableName() . ".id) as order_num",
	                    ])
	                    ->where($where_wait)
	                    ->asArray()
	                    ->having([
	                        'or',
	                        ['=', 'order_num', 0],
	                        ['in', 'status', [-8, -4, -3, 6]]
	                    ])
	                    ->groupBy(UserRegisterInfo::tableName().'.user_id')
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_wait as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_wait);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }

	            $have_condition = true;

	        }

	        $this->log('正在查询用户状态条件--借款中状态');
	        if (isset($params['user_status_loan'])) { // 借款中状态

	            $where_order = [
	                'and',
	                ['in', 'status', [-1, -7, -10, 0, 1, 7, 8, 2, 9, 10, 11, 12, 5, 13]]
	            ];
	            if (!empty($new_ids)) {
	                $where_order[] = ['in', 'user_id', $new_ids];
	            }
	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_order = UserLoanOrder::find()
	                    ->where($where_order)
	                    ->select(['user_id'])
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_order as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_order);
	            }

	            $have_condition = true;
	            if (!empty($new_ids_rel)) {
	                $new_ids = $new_ids_rel;
	                $new_ids_rel = [];
	            } else {
	                $new_ids = [];
	            }
	        }

	        $this->log('正在查询用户状态条件--逾期中状态类型');
	        if (isset($params['user_status_over'])) { // 逾期中状态类型
	            $where_over = [
	                'and',
	                ['=', 'is_overdue', 1],
	                ['!=', 'status', 4]
	            ];

	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_over = UserLoanOrderRepayment::find()
	                    ->select(['user_id'])
	                    ->where($where_over)
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_over as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_over);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }


	            $have_condition = true;
	        }

	        $this->log('正在查询用户状态条件--黑名单状态');
	        if (isset($params['user_status_black'])) { // 黑名单状态
	            $where_black = [
	                'and',
	                ['=', 'black_status', 1],
	            ];
	            if (!empty($new_ids)) {
	                $where_black[] = ['in', LoanBlackList::tableName().'.user_id', $new_ids];
	            }
	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_black = LoanBlackList::find()
	                    ->where($where_black)
	                    ->select(['user_id'])
	                    ->indexBy('user_id')
	                    ->column($db);

	                foreach ($res_black as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_black);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }
	            $have_condition = true;
	        }

	        $this->log('正在查询用户状态条件--认证状态');
	        if (isset($params['user_status_is_ver'])) {  // 已认证
	            $where_is_ver = [
	                'and',
	                ['=', 'real_verify_status', 1],
	                ['=', 'real_contact_status', 1],
	                ['=', 'real_bind_bank_card_status', 1],
//	                ['=', 'real_zmxy_status', 1],
	                ['=', 'real_jxl_status', 1],
	            ];
	            if (!empty($new_ids)) {
	                $where_is_ver[] = ['in', 'user_id', $new_ids];
	            }
	            if ($have_condition && empty($new_ids)) {
	                // 无数据
	            } else {
	                $res_is_ver = UserVerification::find()
	                    ->select(['user_id'])
	                    ->where($where_is_ver)
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_is_ver as $val) {
	                    $new_ids_rel[(int)$val] = (int)$val;
	                }

	                unset($res_is_ver);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }
	        }


	        $this->log('用户状态条件查询结束');
	        // 用户状态条件结束 ===========================================

	        // 成功借款次数开始 ==========================================
	        $this->log('正在查询成功借款次数');
	        $new_ids_rel = [];
	        if ($order_num) {

	            $having_order_num = ['and'];
	            if ($order_num == '9') {
	                $having_order_num[] = ['>', 'order_num', 8];
	            } else {
	                $having_order_num[] = ['=', 'order_num', $order_num];
	            }

	            $where_order_num = ['and'];
	            if (!empty($new_ids)) {
	                $where_order_num[] = ['in', LoanPerson::tableName().'.id', $new_ids];
	            }
	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_order_num = LoanPerson::find()
	                    ->leftJoin(UserLoanOrderRepayment::tableName(), LoanPerson::tableName() . '.id = ' . UserLoanOrderRepayment::tableName() . '.user_id')
	                    ->where($where_order_num)
	                    ->select([
	                        LoanPerson::tableName() . '.id',
	                        "count(" . UserLoanOrderRepayment::tableName() . ".id) as order_num",
	                    ])
	                    ->having($having_order_num)
	                    ->groupBy(LoanPerson::tableName().'.id')
	                    ->indexBy(LoanPerson::tableName() . '.id')
	                    ->column($db);

	                foreach ($res_order_num as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_order_num);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }

	            $have_condition = true;
	        }

	        // 成功借款次数结束 ==========================================

	        // 最后一次放款时间用户============================
	        $this->log('正在查询最后一次放款时间用户');
	        if (!empty($params['last_begintime']) && !empty($params['last_endtime'])) {

	            $condition_last_time = ['and'];
	            if (!empty($new_ids)) {
	                $condition_last_time[] = ['in', 'user_id', $new_ids];
	            }
	            $having_last_time = [
	                'and',
	                ['>=', 'etime', strtotime($params['last_begintime'])],
	                ['<', 'etime', strtotime($params['last_endtime'])],
	            ];
	            if ($have_condition && empty($new_ids)) {
	                // 无数据情况
	            } else {
	                $res_last_time = UserLoanOrderRepayment::find()
	                    ->where($condition_last_time)
	                    ->select([
	                        'user_id',
	                        "max(loan_time) as etime"
	                    ])
	                    ->asArray()
	                    ->groupBy('user_id')
	                    ->having($having_last_time)
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_last_time as $val) {
	                    if (isset($new_ids[$val])) {
	                        $new_ids_rel[(int)$val] = (int)$val;
	                    }
	                }
	                unset($res_last_time);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }

	            $have_condition = true;
	        }

	        // 最后一次放款时间用户结束 =================================================


	        // 曾经借款被拒绝============================
	        $this->log('正在查询是否曾经借款被拒绝');
	        if (isset($params['refuse'])) {

	            $where_is_ver = ['and'];
	        	if ($params['refuse'] == 1) {
		            $where_is_ver[] = ['=', 'status', -3];
	        	} else {
		            $where_is_ver[] = ['!=', 'status', -3];
	        	}

	            if (!empty($new_ids)) {
	                $where_is_ver[] = ['in', 'user_id', $new_ids];
	            }
	            if ($have_condition && empty($new_ids)) {

	            } else {
	                $res_is_ver = UserLoanOrder::find()
	                    ->select(['user_id'])
	                    ->where($where_is_ver)
	                    ->indexBy('user_id')
	                    ->column($db);
	                foreach ($res_is_ver as $val) {
	                    $new_ids_rel[(int)$val] = (int)$val;
	                }

	                unset($res_is_ver);
	                if (!empty($new_ids_rel)) {
	                    $new_ids = $new_ids_rel;
	                    $new_ids_rel = [];
	                } else {
	                    $new_ids = [];
	                }
	            }
	        }

	        // 曾经借款被拒绝结束 =================================================


	        // 正在查询整合最终信息 ===================================
	        $this->log('正在查询整合最终信息');

	        if (!empty($new_ids)) {
	        	$idPhones = array_intersect_key($idPhones, $new_ids);
	        }

	        // 用户基本信息结束 ===================================

	        if (empty($idPhones)) {
	        	$this->log('未有符合条件数据');
	        	return true;
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

	        $this->_exportSearchData($idPhones, $csv_file);

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

        foreach($datas as $key => $value){
            $items[] = [
                '用户id' => $key,
                '手机号' => $value,
            ];
        }
        return $this->_array2csv($items, $file);
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
