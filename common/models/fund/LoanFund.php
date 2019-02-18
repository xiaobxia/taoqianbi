<?php

namespace common\models\fund;

use Yii;
use common\helpers\CurlHelper;
use common\services\FinancialService;
use common\services\fundChannel\JshbService;
use common\helpers\Util;
use common\helpers\Lock;

/**
 * 借款资金表
 * This is the model class for table "{{%loan_fund}}".
 *
 * @property string $id
 * @property string $name 资金方名称
 * @property string $company_name 资金方公司名称
 * @property string $id_number 身份证号码
 * @property string $day_quota_default 金额限制  单位为分
 * @property integer $score 优先分值 越高越大
 * @property integer $status 状态
 * @property integer $pre_sign_type 预签约类型
 * @property float $interest_rate 利率
 * @property float $deposit_rate 保证金比例
 * @property float $fund_service_fee_rate 资方服务费比例
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $pay_account_id 支付账号ID
 * @property integer $repay_account_id 扣款账号ID
 * @property integer $type 类型
 * @property integer $quota_type 配额类型
 * @property integer $can_use_quota 可用额度 用于循环额度
 * @property integer $loan_term_add_day 利息添加天数
 *
 * @property FundAccount $payAccount 支付账号
 * @property FundAccount $debitAccount 扣款账号
 *
 *
 */
class LoanFund extends \yii\db\ActiveRecord {
    const CREDIT_VERIFICATION_FEE = 30;//默认收到30元一笔征信费用

    const ID_DEFAULT = 0;   //全部资方
    const ID_KOUDAI = 3;    //口袋资方ID
    const ID_WZDAI = 4;     //温州贷

    const STATUS_ENABLE = 0;//启用
    const STATUS_DISABLE = -1;//禁用

    const PRE_SIGN_SKIP = 0;//跳过预签约 （口袋理财）
    const PRE_SIGN_1 = 1;//预签约类型1  （51资金）

    const PRE_SIGN_LIST = [
        self::PRE_SIGN_SKIP => '不需预签约(类似口袋理财)',
        self::PRE_SIGN_1 => '预签约（类似51）',
    ];

    const TYPE_P2P = 0;//P2P平台
    const TYPE_BIG_CLIENT = 1;//大客户

    const TYPE_LIST = [
        self::TYPE_P2P => 'P2P平台',
        self::TYPE_BIG_CLIENT => '大客户'
    ];

    const QUOTA_TYPE_DAY = 0;//按日来计算额度
    const QUOTA_TYPE_TOTAL = 1;//循环额度

    const QUOTA_TYPE_LIST = [
        self::QUOTA_TYPE_DAY => '按日额度',
        self::QUOTA_TYPE_TOTAL => '循环额度',
    ];

    const STATUS_LIST = [
        self::STATUS_ENABLE => '启用',
        self::STATUS_DISABLE => '禁用',
    ];

    //当前放款的资方
    public static $loan_source = [
        //self::ID_DEFAULT => '无资方',
        self::ID_KOUDAI => APP_NAMES,
    ];
    /**
     * 服务类
     * @var \common\services\fundChannel\BaseService
     */
    private $service;

    /**
     * 获取允许付款的ID数组
     * @return []
     */
    public static function getAllowPayIds()
    {
        $rows = static::find()->select('id')->where('`type`=' . self::TYPE_BIG_CLIENT)->all();
        $ids = [0, self::ID_KOUDAI];
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_fund}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'day_quota_default', 'deposit_rate', 'interest_rate', 'fund_service_fee_rate', 'pay_account_id', 'repay_account_id'], 'required'],
            [['day_quota_default'], 'integer', 'min' => 1000000],
            [['can_use_quota'], 'integer', 'min' => 0],
            [['name'], 'string', 'max' => 32],
            [['company_name', 'id_number'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['status', 'score', 'pre_sign_type'], 'integer'],
            [['deposit_rate', 'interest_rate', 'fund_service_fee_rate'], 'number'],
            [['status', 'loan_term_add_day'], 'integer'],
            [['pay_account_id'], 'in', 'range' => array_keys(FundAccount::getSelectOptions(FundAccount::TYPE_PAY))],
            [['repay_account_id'], 'in', 'range' => array_keys(FundAccount::getSelectOptions(FundAccount::TYPE_REPAY))],
            [['type'], 'in', 'range' => array_keys(self::TYPE_LIST)],
            [['quota_type'], 'in', 'range' => array_keys(self::QUOTA_TYPE_LIST)],
        ];
    }

    public function attributeHints()
    {
        return [
            'deposit_rate' => '保证金和本金的百分比。计算公式：（保证金/本金）*100',
            'interest_rate' => '利息和本金的百分比。计算公式：（保证金/本金）*100',
            'fund_service_fee_rate' => '资方服务费和本金的百分比。计算公式：（保证金/本金）*100',
            'score' => '优先级越高 越优先',
            'can_use_quota' => '循环额度适用'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'day_quota_default' => '每日默认配额（分）',
            'can_use_quota' => '可用额度（分）',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'status' => '状态',
            'deposit_rate' => '保证金率',
            'fund_service_fee_rate' => '资方服务费率',
            'interest_rate' => '利率',
            'score' => '优先级',
            'pre_sign_type' => '预签约类型',
            'pay_account_id' => '放款主体ID',
            'repay_account_id' => '还款主体ID',
            'type' => '类型',
            'quota_type' => '配额类型',
            'loan_term_add_day' => '借款期限添加天数',
            'company_name' => '公司名称',
            'id_number' => '大客户身份证号码',

        ];
    }

    /**
     * 获取指定日期的余下配额
     * @param string $date 日期
     */
    public function getDayRemainingQouta($date)
    {
        $quota_model = LoanFundDayQuota::findOne([
            'fund_id' => $this->id,
            'date' => $date
        ]);
        /* @var $quota_model LoanFundDayQuota */
        if (!$quota_model) {
            if ($this->quota_type == self::QUOTA_TYPE_TOTAL) {
                if (!($lock = Lock::get(($lock_name = 'fundGetDayRemainingQuota' . $this->id . ':' . $date), 30, 3))) {
                    throw new \Exception('获取资方每日额度锁失败');
                }

                //获取到锁后  再获取一次, 防止获取锁时已经插入了配额记录
                if (($quota_model = LoanFundDayQuota::findOne([
                    'fund_id' => $this->id,
                    'date' => $date
                ]))) {
                    Lock::del($lock_name);
                    return $quota_model;
                }

                $pre_quota = LoanFundDayPreQuota::findOne([
                    'fund_id' => $this->id,
                    'date' => $date
                ]);
                $change_amount = $pre_quota ? ($pre_quota->incr_amount - $pre_quota->decr_amount) : 0;
                if ($change_amount) {
                    static::getDb()->createCommand('UPDATE ' . static::tableName() . ' SET `can_use_quota`=`can_use_quota`+' . $change_amount .
                        ' WHERE id=' . $this->id)->execute();
                    $can_use_quota = static::find()->select('`can_use_quota`')->where('`id`=' . (int)$this->id)->scalar();
                    $this->can_use_quota = (int)$can_use_quota;
                    $this->setOldAttribute('can_use_quota', $can_use_quota);
                }
                LoanFundDayQuota::add($this->id, $date, $this->can_use_quota > 0 ? $this->can_use_quota : 0);
                if ($lock) {
                    Lock::del($lock_name);
                }
            } else {
                LoanFundDayQuota::add($this->id, $date, $this->day_quota_default);
            }
            $quota_model = LoanFundDayQuota::findOne([
                'fund_id' => $this->id,
                'date' => $date
            ]);
            if (!$quota_model) {
                Yii::error("异常：获取不到资金 {$this->id} {$date}日配额");
                return 0;
            }
        }
        return $quota_model->remaining_quota;
    }

    /**
     * 获取服务
     * @param integer $id 资方ID
     * @param integer $type 类型
     * @return \common\services\fundChannel\BaseService
     * @throws \Exception
     */
    public static function getServiceById($id, $type) {
        if ($type == self::TYPE_BIG_CLIENT) {
            throw new \Exception('未定义资金' . $id . '对应的服务类');
        } else if ($type == self::TYPE_P2P) {
            switch ($id) {
                case self::ID_KOUDAI:
                    $service = new JshbService();
                    break;
                default:
                    throw new \Exception('未定义资金' . $id . '对应的服务类');
                    break;
            }
        } else {
            throw new \Exception('未定义资金' . $id . '对应的服务类');
        }
        return $service;
    }

    /**
     * 获取资方Service
     * @return \common\services\fundChannel\BaseService
     */
    public function getService()
    {
        if ($this->service === null) {
            $this->service = static::getServiceById($this->id, $this->type);
        }
        return $this->service;
    }

    /**
     * 获取所有资金方
     * @param integer $status 状态值 默认为null 获取所有状态值的资方
     * @return static[]
     */
    public static function getAll($status = null, $pre_sign_type = null)
    {
        $conditions = [];
        if ($status !== null) {
            $conditions[] = '`status`=' . (int)$status;
        }
        if ($pre_sign_type !== null) {
            $conditions[] = '`pre_sign_type`=' . (int)$pre_sign_type;
        }
        return static::find()->where(implode(' AND ', $conditions))->orderBy('`score` DESC')->all();
    }

    /**
     * 获取所有资金方
     * @param integer $status 状态值 默认为null 获取所有状态值的资方
     * @return []
     */
    public static function getAllFundArray()
    {
        $res = static::find()->select('id,name')->asArray()->all();
        $tmp = [];
        foreach ($res as $val) {
            $tmp[$val['id']] = $val['name'];
        }

        return $tmp;
    }

    /**
     * 获取所有大客户
     * @param integer $status 状态值 默认为null 获取所有状态值的资方
     * @return static[]
     */
    public static function getBigClientFundArray()
    {
        $res = static::find()->where('type = ' . self::TYPE_BIG_CLIENT)->select('id,name')->asArray()->all();
        $tmp = [];
        foreach ($res as $val) {
            $tmp[$val['id']] = $val['name'];
        }
        return $tmp;

    }

    /**
     * 是否要求预签约
     * @return boolean
     */
    public function requirePreSign()
    {
        $ret = false;
        switch ($this->pre_sign_type) {
            case static::PRE_SIGN_1:
                $ret = true;
                break;

            default:
                break;
        }
        return $ret;
    }

    /**
     * 获取费用列表
     * @param integer $day 借款天数
     * @param integer $principal 本金
     * @param integer $card_type 卡类型
     * @return []
     */
    public function getFeeList($day, $principal, $card_type, $user_id = null)
    {
        $service = $this->getService();
        if (method_exists($service, 'getFeeList')) {
//            return $service->getFeeList($this, $day, $principal, $card_type,$user_id);
            $loan_info = $service->getFeeList($day, $principal, $user_id);
        } else {
            $loan_info = Util::calcLoanInfo($day, $principal, $card_type, $user_id);
        }

        $interest = $this->getInterest($principal, $day);
        $total_service_fee = $loan_info['counter_fee'] - $interest;
        $fund_service_fee = $this->getFundServiceFee($principal, $day);
        $credit_verification_fee = $this->getCreditVerificationFee($principal);
        $deposit = $this->getDeposit($principal);
        $remain = $total_service_fee;
        $fees = [
            'arrival_amount' => $principal - $interest - $total_service_fee,//到账金额
            'interest' => $interest,//利息
            'total_service_fee' => $total_service_fee,//除利息以外的手续费
            'fund_service_fee' => (($remain -= $fund_service_fee) >= 0) ? $fund_service_fee : $fund_service_fee + $remain,//资方手续费
            'deposit' => (($remain -= $deposit) >= 0) ? $deposit : $deposit + $remain,//保证金
            'credit_verification_fee' => (($remain -= $credit_verification_fee) >= 0) ? $credit_verification_fee : $credit_verification_fee + $remain,//征信费用
            'service_fee' => ($remain >= 0) ? $remain : 0,//我方服务费
        ];
        return $fees;
    }

    /**
     * 获取资方服务费用 单位为分
     * @param integer $principal 本金 单位为分
     * @param integer $day 天数
     * @return integer 服务费用 单位为分
     */
    protected function getFundServiceFee($principal, $day)
    {
        //如果服务中有定义方法 调用服务中的方法
        $service = $this->getService();
        if (method_exists($service, 'getFundServiceFee')) {
            return (int)$service->getFundServiceFee($this, $principal, $day);
        } else {
            return (int)($principal * ($this->fund_service_fee_rate / 100));
        }
    }

    /**
     * 获取利息 单位为分 需要注意，一般计算利息天数应该比借款订单天数多一天
     * @param integer $principal 本金 单位为分
     * @return integer 本金 单位为分
     */
    public function getInterest($principal, $day)
    {
        $day += $this->loan_term_add_day;
        return round($principal * ($this->interest_rate / 100) * ($day / 360));
    }

    /**
     * 获取逾期利息
     * @param integer $principal 本金
     * @param integer $overdue_day 逾期天数
     * @return integer
     */
    public function getOverdueInterest($principal, $overdue_day)
    {
        if ($overdue_day <= 0) {
            return 0;
        }
        //如果服务中有定义方法 调用服务中的方法
        $service = $this->getService();
        if (method_exists($service, 'getOverdueInterest')) {
            return (int)$service->getOverdueInterest($this, $principal, $overdue_day);
        } else {
            return round($principal * ($this->interest_rate / 100) * ($overdue_day / 360));
        }
    }

    /**
     * 获取征信费用 单位为分
     */
    protected function getCreditVerificationFee($principal)
    {
        //如果服务中有定义方法 调用服务中的方法
        $service = $this->getService();
        if (method_exists($service, 'getCreditVerificationFee')) {
            return (int)$service->getCreditVerificationFee($this, $principal);
        } else {
            return 3000;
        }
    }

    /**
     * 获取保证金 单位为分
     * @param integer $principal 本金 单位为分
     * @return integer 保证金 单位为分
     */
    protected function getDeposit($principal)
    {
        //如果服务中有定义方法 调用服务中的方法
        $service = $this->getService();
        if (method_exists($service, 'getDeposit')) {
            return (int)$service->getDeposit($this, $principal);
        } else {
            return (int)($principal * ($this->deposit_rate / 100));
        }
    }

    /**
     * 判断是否支持银行
     * @param string $bank_name 银行名称
     * @param integer $bank_id 银行ID
     * @return []
     */
    public function supportBank($bank_name)
    {
        $service = $this->getService();
        $support_bank_list = $service->getSupportBankList();
        return in_array($bank_name, $support_bank_list);
    }

    /**
     * 判断是否支持银行ID
     * @param integer $bank_id 银行ID
     * @return bool
     */
    public function supportBankId($bank_id)
    {
        $service = $this->getService();
        $support_bank_ids = $service->getSupportBankIds();
        return in_array($bank_id, $support_bank_ids);
    }

    public function getPayAccount()
    {
        return $this->hasOne(FundAccount::className(), ['id' => 'pay_account_id']);
    }

    public function getDebitAccount()
    {
        return $this->hasOne(FundAccount::className(), ['id' => 'repay_account_id']);
    }

    /**
     * 获取可用资方(循环额度)
     * @param unknown $status
     * @return array
     * @author czd
     */
    public static function getCanUseCycleLoan($status = null)
    {
        $conditions = [];
        if ($status !== null) {
            $conditions[] = '`status`=' . (int)$status;
        }
        $conditions[] = '`quota_type`=' . (int)self::QUOTA_TYPE_TOTAL;
        $conditions[] = '`can_use_quota`>=' . '20000';

        return static::find()->where(implode(' AND ', $conditions))->orderBy('`score` DESC')->all();
    }

    /**
     * 按一定权重随机排序资方
     * @param type $funds
     */
    public static function randSortFunds($funds)
    {
        $sort_funds = [];
        foreach ($funds as $fund) {
            $rand_score = $fund->score > 0 ? rand(0, $fund->score) : 0;
            $sort_funds[$rand_score] = $fund;
        }
        krsort($sort_funds);
        return $sort_funds;
    }

    /**
     * 获取可用资方(每日)
     * @param unknown $status
     * @return array
     * @author czd
     */
    public static function getCanUseDayLoan($status = null, $pre_sign_type = null)
    {
        $conditions = [];
        if ($status !== null) {
            $conditions[] = '`status`=' . (int)$status;
        }
        if ($pre_sign_type !== null) {
            $conditions[] = '`pre_sign_type`=' . (int)$pre_sign_type;
        }

        $conditions[] = '`quota_type` = ' . (int)self::QUOTA_TYPE_DAY;
        return static::find()->where(implode(' AND ', $conditions))->orderBy('`score` DESC')->all();
    }

    /**
     * 获取可用配额
     * @param unknown $status
     * @param unknown $pre_sign_type
     */
    public static function canUserLoan($status = null, $pre_sign_type = null)
    {
        $conditionsCycle = [];
        $conditionsDay = [];

        if ($status !== null) {
            $conditionsDay[] = '`status`=' . (int)$status;
            $conditionsCycle[] = '`status`=' . (int)$status;
        }
        if ($pre_sign_type !== null) {
            $conditionsDay[] = '`pre_sign_type`=' . (int)$pre_sign_type;
        }

        $conditionsDay[] = '`quota_type` = ' . (int)self::QUOTA_TYPE_DAY;

        $conditionsCycle[] = '`quota_type`=' . (int)self::QUOTA_TYPE_TOTAL;
        $conditionsCycle[] = '`can_use_quota`>=' . '20000';

        return static::find()->where(implode(' AND ', $conditionsDay))->orWhere(implode(' AND ', $conditionsCycle))->orderBy('`score` DESC')->all();
    }

    /**
     * 获取当天余下额度
     * @return integer
     * @throws \Exception
     */
    public function getTodayRemainingQouta()
    {
        return $this->getDayRemainingQouta(date('Y-m-d'));
    }

    /**
     * 减少资方额度
     * @param integer $amount 金额 （分）
     * @param string $day 日期
     * @param integer $incr_loan_amount 增加的放款金额
     */
    public function decreaseQuota($amount, $day, $incr_loan_amount)
    {
        //变更配额
        if ($this->quota_type == LoanFund::QUOTA_TYPE_TOTAL) {
            $sql = 'UPDATE ' . static::tableName() . ' SET `can_use_quota`=(cast(`can_use_quota` as signed)- :decr_quota) WHERE `id`=:fund_id ';
            static::getDb()->createCommand($sql, [
                ':fund_id' => (int)$this->id,
                ':decr_quota' => (int)$amount,
            ])->execute();
            LoanFundDayQuota::decreaseTotalQuota($this->id, $day, $amount, $incr_loan_amount);
        } else {
            LoanFundDayQuota::decreaseDayQuota($this->id, $day, $amount);
        }
    }

    /**
     * 增加资方额度
     * @param integer $amount 金额（分）
     * @param string $day 日期
     * @param integer $decr_loan_amount 减少的借款金额记录
     */
    public function increaseQuota($amount, $day, $decr_loan_amount)
    {
        //变更配额
        if ($this->quota_type == LoanFund::QUOTA_TYPE_TOTAL) {
            $sql = 'UPDATE ' . static::tableName() . ' SET `can_use_quota`=(cast(``can_use_quota` as signed) + :incr_quota) WHERE `id`=:fund_id ';
            static::getDb()->createCommand($sql, [
                ':fund_id' => (int)$this->id,
                ':incr_quota' => (int)$amount,
            ])->execute();

            LoanFundDayQuota::increaseTotalQuota($this->id, $day, $amount, $decr_loan_amount);
        } else {
            LoanFundDayQuota::increaseDayQuota($this->id, $day, $amount, $decr_loan_amount);
        }
    }

    /**
     * 是否支持借款期限
     * @param integer $term 借款的期限
     * @return bool
     */
    public function supportLoanTerm($term)
    {
        return $this->getService()->supportLoanTerm($term);
    }


}
