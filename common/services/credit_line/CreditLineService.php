<?php
namespace common\services\credit_line;

use Yii;
use yii\base\Component;
use common\api\RedisQueue;
use common\models\AccumulationFund;
use common\models\credit_line\CreditLine;
use common\models\credit_line\CreditLineLog;
use common\models\credit_line\CreditLineTimeLog;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\services\RiskControlTreeService;
use common\services\UserService;
use common\helpers\CommonHelper;

class CreditLineService extends Component
{
    const NORMAL_CREDIT_MONEY = 1000;
    const RULE_ID = 268;

    public $risk_control_tree_service;

    public function __construct()
    {
        $this->risk_control_tree_service = new RiskControlTreeService();
    }

    /**
     * 将用户提额申请压入redis, 落地db
     * @param int $user_id
     * @return boolean
     */
    public static function checkUserCreditLines($user_id) {
        $time_log = new CreditLineTimeLog();
        $time_log->user_id = $user_id;
        $time_log->begin_time = date('Y-m-d H:i:s');
        if ($time_log->save() && RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $user_id])) {
            return true;
        }

        CommonHelper::error(\sprintf('[%s][%s] failed %s', __CLASS__, __FUNCTION__, $user_id));
        return false;
    }

    /**
     * 获取用户的“授信额度”，“服务费率”。
     * @param \common\models\LoanPerson $user
     * @param \common\models\credit_line\CreditLine $credit_line
     * @param unknown $rule_id
     * @return multitype :NULL string |multitype:NULL unknown
     */
    public function getCreditLines(\common\models\LoanPerson $user, $credit_line, $rule_id) {
        // 初次查询则新建
        if (empty($credit_line)) {
            return $this->createCreditLine($user, $rule_id);
        }
        $valid_time = $credit_line->valid_time;
        // 公积金
        $accumulation = AccumulationFund::findLatestOne([
            'user_id' => $user->id,
            'status' => AccumulationFund::STATUS_SUCCESS,
        ]);
        if ($valid_time <= \date('Y-m-d H:i:s') ||
            ($accumulation && $accumulation->updated_at > $credit_line->update_time)) { //超过有效期则更新
            return $this->createCreditLine($user, $rule_id, $credit_line);
        }

        return [
            'credit_line' => $credit_line->credit_line,
            'time_limit' => $credit_line->time_limit,
            'valid_time' => $valid_time,
        ];
    }

    /**
     * 生成用户“授信额度”，“服务费率”
     * @param \common\models\LoanPerson $user
     * @param unknown $rule_id
     * @param string $credit_line
     * @return multitype:NULL string
     */
    public function createCreditLine(\common\models\LoanPerson $user, $rule_id, $credit_line = null) {
        // 决策树结果
        $result = $this->risk_control_tree_service->runDesicionTree([$rule_id], $user);

        if (empty($credit_line)) {
            $credit_line = new CreditLine();
            $credit_line->user_id = $user->id;
        }

        $value = $result[$rule_id]['value'];
//        $credit_line->credit_line = $value['credit_line'];//总额度

        //去掉公积金的额度
        $all =  $value['credit_line'] - isset($value['credit_line_detail']['gjj']) ? ($value['credit_line_detail']['gjj'] ? : 0) : 0;
        $credit_line->credit_line = $all;//总额度

        $credit_line->credit_line_base = isset($value['credit_line_detail']['base']) ? ($value['credit_line_detail']['base'] ? : 0) : 0; // 基础额度
        $credit_line->credit_line_gjj = isset($value['credit_line_detail']['gjj']) ? ($value['credit_line_detail']['gjj'] ? : 0) : 0; // 公积金额度
        $credit_line->credit_line_kdjz = isset($value['credit_line_detail']['kdjz']) ? ($value['credit_line_detail']['kdjz'] ? : 0) : 0; // 公积金额度


        $credit_line->time_limit = $value['time_limit']; // 借款期限

        // 额度决策树说明
        $tree = $value['tree']; # $tree = 'TreeZeus';

        // 额度有效期
        $interval = $value['valid_time']; # $interval = 300;

        //降息
        $low_rate = $value['low_rate'];
        //这里不要改
        $date = \date('Y-m-d H:i:s');

        $valid_time = date('Y-m-d H:i:s', \strtotime('+' . $interval . ' day', strtotime($date)));
        $credit_line->valid_time = $valid_time;
        $cl_save = $credit_line->save();
        if (!$cl_save) {
            throw new \Exception('额度记录保存失败');
        }

        $credit_line_id = $credit_line->id;

        //额度流水
        $log = [];

        //判断是否已经存在
        $tempModel = CreditLineLog::findOne([
            'user_id' => $user->id,
            'credit_line_id' => $credit_line_id,
            'root_id' => $tree,
            'status' => CreditLineLog::STATUS_ACTIVE,
        ]);
        if ($tempModel) {
            CreditLineLog::updateAll([
                'status' => CreditLineLog::STATUS_DELETED
            ], [
                'user_id' => $user->id,
                'credit_line_id' => $credit_line_id,
                'root_id' => $tree,
                'status' => CreditLineLog::STATUS_ACTIVE,
            ]);
        }

        //格式化存储
        foreach ($result as $key => $value) {
            $temp = [];
            $temp['user_id'] = $user->id;
            $temp['credit_line_id'] = $credit_line_id;
            $temp['root_id'] = $tree;
            $temp['rule_id'] = $key;
            $temp['rule_detail'] = $value['detail'];
            $temp['rule_value'] = is_array($value['value']) ? json_encode($value['value']) : $value['value'];
            $temp['create_time'] = $date;
            $temp['update_time'] = $date;
            $temp['status'] = CreditLineLog::STATUS_ACTIVE;
            $log[] = $temp;
        }

        if (!empty($log)) {
            CreditLineLog::getDb()->createCommand()
                ->batchInsert(CreditLineLog::tableName(),
                    ['user_id', 'credit_line_id', 'root_id', 'rule_id', 'rule_detail', 'rule_value', 'create_time', 'update_time', 'status'], $log)
                ->execute();
        }

        return [
            'credit_line' => $credit_line->credit_line,
            'time_limit' => $credit_line->time_limit,
            'valid_time' => $valid_time,
            'low_rate' => $low_rate,
        ];
    }

    /**
     * 更新用户授信额度
     * @param LoanPerson $user
     * @param $rule_id
     * @return bool
     */
    public function updateCreditLines(\common\models\LoanPerson $user, $rule_id) {
        $user_id = $user->id;
        $credit_line = CreditLine::findLatestOne([
            'user_id' => $user_id,
            'status' => CreditLine::STATUS_ACTIVE,
        ]);
        if (empty($credit_line)) { // 无授信记录
            return false;

        }
        $result = $this->createCreditLine($user, $rule_id, $credit_line);
        $credit_line = $result['credit_line'];
        $valid_time = strtotime($result['valid_time']);

        /** @var UserService $userServiceInst */
        $userServiceInst = Yii::$container->get('userService');

        return $userServiceInst->setUserCreditDetail($user_id, $credit_line, $valid_time);
    }

}