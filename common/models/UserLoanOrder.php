<?php

namespace common\models;

use common\services\activity\ActivityCommonService;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\base\UserException;
use yii\db\ActiveRecord;
use common\helpers\MessageHelper;
use common\api\RedisQueue;
use common\services\OrderService;
use common\models\loan\LoanCollectionOrder;
use common\helpers\Util;
use common\models\fund\LoanFund;
use common\helpers\Lock;
use common\models\fund\OrderFundInfo;


/**
 * This is the model class for table "{{%user_loan_order}}".
 * @property integer $id 自增ID
 * @property integer $user_id 用户id
 * @property integer $order_type 订单类型：1、零钱袋，2、房租贷,3、分期商城
 * @property integer $money_amount 金额，单位为分
 * @property float $apr 利率
 * @property integer $loan_method  0:按天，1,：按月，2：按年
 * @property integer $loan_term 根据loan_method确定，几天、几月、几年
 * @property integer $loan_interests 借款利息
 * @property string $operator_name
 * @property string $remark 备注
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property integer $status 状态：-3、已作废、不通过；-2、已坏账；-1、已逾期；0、待审核；1、审核通过；2、已放款；3、还款中；4、部分还款；5、已还款
 * @property integer $order_time 下单时间
 * @property integer $loan_time 放款时间，用于计算利息的起止时间
 * @property integer $trail_time 订单审核时间
 * @property integer $current_interests 单位：分，该订单目前为止产生的利息，用脚本跑出来，再还款的时候，可以核对一遍，提高准确度
 * @property integer $late_fee 单位：分，滞纳金，脚本跑出来，当还款的时候重新计算进行核对
 * @property integer $late_fee_apr 滞纳金利率，单位为万分之几
 * @property integer $card_id 银行卡ID
 * @property integer $counter_fee 手续费
 * @property string $reason_remark
 * @property integer $is_first 是否是首单，0，不是；1，是
 * @property integer $auto_risk_check_status  自动风控检测状态:-1失败，0未检测，1已检测
 * @property integer $is_hit_risk_rule 是否命中风险策略
 * @property integer $sub_order_type 订单子类型(-1:全部,0:小钱包,1:极速钱包)
 * @property integer $is_user_confirm 用户是否查看了审核结果
 * @property integer $from_app 订单来源app (-1:全部,0:小钱包,1:极速钱包,2:现金侠,3...)
 * @property integer $fund_id 来源资金ID
 * @property integer $card_type 卡类型
 *
 * @property LoanPerson $loanPerson 借款用户
 * @property LoanFund $loanFund 借款来源资金
 * @property UserLoanOrderRepayment $userLoanOrderRepayment 用户借款订单还款
 * @property CardInfo $cardInfo 银行卡信息
 * @property OrderFundInfo $orderFundInfo 订单资方信息
 */
class UserLoanOrder extends \common\components\ARModel {
    const AUTO_STATUS_ALL = -2;
    const AUTO_STATUS_FAILED = -1; # 审核失败
    const AUTO_STATUS_DEFAULT = 0; # 待采集数据
    const AUTO_STATUS_DATA = 0; # 待采集数据
    const AUTO_STATUS_SUCCESS = 1; # '已审核' （采集数据完成）
    const AUTO_STATUS_ANALY = 2; # 待分析数据
    const AUTO_STATUS_REVIEW = 3; # 待系统审批

    const SUB_TYPE_ALL = -1;
    const SUB_TYPE_YGD = 0;
    const SUB_TYPE_XJD = 1;
    const SUB_TYPE_RONG360 = 2;
    const SUB_TYPE_SD = 3;
    const SUB_TYPE_JDQ = 4;
    const SUB_TYPE_JLM = 5;
    const SUB_TYPE_XJX = 6;
    const SUB_TYPE_LYQB = 7;
    const SUB_TYPE_RDZDB = 8;
    const SUB_TYPE_WUYAO = 9;
    const SUB_TYPE_WUBA = 10;
    const SUB_TYPE_WUYAO_CREDIT = 11;
    const SUB_TYPE_WYXYK = 12;
    const SUB_TYPE_BAIXING = 13;
    const SUB_TYPE_BAIRONG = 14;
    const SUB_TYPE_YOUYU = 15;
    const SUB_TYPE_XINWANG = 16;//新网订单
    const SUB_TYPE_OTHER = 100;


    //限单类型
    const PASS_TYPE_NORMAL = 1;//普通用户（新用户）限单  normal
    const PASS_TYPE_GJJ = 2;//公积金用户限单   gjj
    const PASS_TYPE_THIRD = 3;//第三方用户限单   third
    const PASS_TYPE_OLD = 4;//老用户限单 old_user
    const PASS_TYPE_GJJ_OLD = 5;//公积金老用户限单 gjj_old_user

    public static $sub_order_type = [
        self::SUB_TYPE_ALL => '全部',
        self::SUB_TYPE_YGD => '小钱包',
        self::SUB_TYPE_RONG360 => '融360',
        self::SUB_TYPE_BAIRONG => '百融',
        self::SUB_TYPE_YOUYU => '有鱼',
        self::SUB_TYPE_XJD => APP_NAMES,
        self::SUB_TYPE_SD => '闪贷',
        self::SUB_TYPE_JDQ => '借点钱',
        self::SUB_TYPE_JLM => '借了吗',
        self::SUB_TYPE_XJX => '英科-现金侠',
        self::SUB_TYPE_LYQB => '零用钱包',
        self::SUB_TYPE_RDZDB => '融都-掌贷宝',
        self::SUB_TYPE_WUYAO => '51公积金',
        self::SUB_TYPE_WUYAO_CREDIT => '51信用卡',
        self::SUB_TYPE_WUBA => '58消费贷',
        self::SUB_TYPE_OTHER => '其他',
        self::SUB_TYPE_WYXYK => '51信用卡借贷渠道',
        self::SUB_TYPE_BAIXING => '百姓金融借贷渠道',
        self::SUB_TYPE_XINWANG => '新网银行借贷渠道',
    ];

    // 是没有通讯录的订单变量
    const ORDER_CONTACT_STATUS = 1;
    // M版没有通讯录的问题
    const ORDER_CONTACT_STATUS_M = 2;

    public static $order_no_contact = [
        self::ORDER_CONTACT_STATUS,
        self::ORDER_CONTACT_STATUS_M
    ];

    // 选择订单渠道的类型
    public static $sel_order_type = [
        self::SUB_TYPE_XJD => APP_NAMES,
        self::SUB_TYPE_YGD => '小钱包',
        self::SUB_TYPE_RONG360 => '融360',
        self::SUB_TYPE_SD => '闪贷',
        self::SUB_TYPE_JDQ => '借点钱',
        self::SUB_TYPE_JLM => '借了吗',
        self::SUB_TYPE_XJX => '英科-现金侠',
        self::SUB_TYPE_LYQB => '零用钱包',
        self::SUB_TYPE_RDZDB => '融都-掌贷宝',
        self::SUB_TYPE_WUYAO => '51公积金',
    ];

    //外部app订单类型
    public static $out_app_sub_order_type = [
        //self::SUB_TYPE_XJX,
        self::SUB_TYPE_LYQB,
        self::SUB_TYPE_RDZDB
    ];

    public static $auto_check_status_list = [
        self::AUTO_STATUS_ALL => '全部',
        self::AUTO_STATUS_DATA => '待采集数据',
        self::AUTO_STATUS_ANALY => '待分析数据',
        self::AUTO_STATUS_REVIEW => '待系统审批',
        self::AUTO_STATUS_SUCCESS => '已审核',
        self::AUTO_STATUS_FAILED => '审核失败',
    ];

    //滞纳金
    const LATE_FEE_APR = 0.1;

    const LOAN_TYPE_LQD = 1;
    const LOAN_TYPR_FZD = 2;
    const LOAN_TYPE_FQSC = 3;

    public static $loan_type = [
        self::LOAN_TYPE_LQD => '零钱包',
        self::LOAN_TYPR_FZD => '房租宝',
        self::LOAN_TYPE_FQSC => '分期购',
    ];

    const LOAN_METHOD_DAY = 0;
    const LOAN_METHOD_MONTH = 1;
    const LOAN_METHOD_YEAR = 2;
    public static $loan_method = [
        self::LOAN_METHOD_DAY => '天',
        self::LOAN_METHOD_MONTH => '月',
        self::LOAN_METHOD_YEAR => '年',
    ];

    const FIRST_LOAN = 1;
    const FIRST_LOAN_NO = 0;

    public static $is_first = [
        self::FIRST_LOAN => '第一单',
        self::FIRST_LOAN_NO => '非第一单',
    ];

    const FROM_APP_ALL = -1;
    const FROM_APP_YGD = 0;
    const FROM_APP_XJK = 1;
    const FROM_APP_XJX = 2;
    const FROM_APP_LYQB = 3;
    const FROM_APP_RDZDB = 4;
    const FROM_APP_H5 = 5;

    public static $from_apps = [
        self::FROM_APP_ALL => '全部',
        self::FROM_APP_YGD => '小钱包',
        self::FROM_APP_XJK => APP_NAMES,
        self::FROM_APP_XJX => '现金侠',
        self::FROM_APP_LYQB => '零用钱包',
        self::FROM_APP_RDZDB => '掌贷宝',
        self::FROM_APP_H5 => 'M站',
    ];

    /**
     * 按日放款额度标的周期
     *
     * @var array
     */
    public static $day_cycle = [7, 14, 21, 30];

    const STATUS_ALL = -100;
    const STATUS_ELSE = -1000;
    const STATUS_MISS_FILES = -11;
    const STATUS_PENDING_LOAN_CANCEL = -10;
    const STATUS_PENDING_CANCEL = -9;
    const STATUS_REPAYING_CANCEL = -8;
    const STATUS_DEBIT_FALSE = -7;
    const STATUS_REPAY_REPEAT_CANCEL = -6;
    const STATUS_REPAY_CANCEL = -5;
    const STATUS_REPEAT_CANCEL = -4;
    const STATUS_CANCEL = -3;
    const STATUS_MACHINE = 200;//机审拒绝
    const STATUS_PERSON = 100;//人工拒绝
    const STATUS_BAD_DEBT = -2;
    const STATUS_OVERDUE = -1;
    const STATUS_CHECK = 0; #待初审
    const STATUS_PASS = 1;
    const STATUS_PAY = 2;
    const STATUS_LOAN_COMPLETE = 3;
    const STATUS_LOAN_COMPLING = 3;
    const STATUS_PARTIALREPAYMENT = 5;
    const STATUS_REPAY_COMPLETE = 6;
    const STATUS_REPEAT_TRAIL = 7;
    const STATUS_PENDING_LOAN = 8;
    const STATUS_APPLY_REPAY = 9;
    const STATUS_REPAY_TRAIL = 10;
    const STATUS_APPLY_RETRAIL = 11;
    const STATUS_REPAYING = 12;
    const STATUS_REVIEW_PASS = 13;//审核通过
    const STATUS_FUND_CONTRACT = 14;//资方签约
    const STATUS_WAIT_FOR_CONTACTS = 15; // 处理M版本订单状态

    public static $status = [
        self::STATUS_ALL => '全部',
        self::STATUS_BAD_DEBT => '已坏账',
        self::STATUS_OVERDUE => '已逾期',
        self::STATUS_REPAYING_CANCEL => '扣款驳回',
        self::STATUS_DEBIT_FALSE => '扣款失败',
        self::STATUS_REPAY_REPEAT_CANCEL => '还款复审驳回',
        self::STATUS_REPAY_CANCEL => '还款初审驳回',
        self::STATUS_MISS_FILES => '待补充材料',
        self::STATUS_PENDING_LOAN_CANCEL => '放款失败',
        self::STATUS_PENDING_CANCEL => '放款驳回',
        self::STATUS_REPEAT_CANCEL => '复审驳回',
        self::STATUS_CANCEL => '初审驳回',
        self::STATUS_CHECK => '待初审',
        self::STATUS_PASS => '初审通过',//不需要
        self::STATUS_REPEAT_TRAIL => '待复审',
        self::STATUS_PENDING_LOAN => '待放款',
        self::STATUS_PAY => '打款中',
        self::STATUS_LOAN_COMPLETE => '已放款',
        self::STATUS_LOAN_COMPLING => '还款中',
        self::STATUS_APPLY_REPAY => '申请还款',  //不需要 申请还款
        self::STATUS_MACHINE => '机审驳回',
        self::STATUS_PERSON => '人工初审驳回',
        self::STATUS_REPAY_TRAIL => '还款初审',
        self::STATUS_APPLY_RETRAIL => '还款复审',
        self::STATUS_REPAYING => '扣款中',
        self::STATUS_PARTIALREPAYMENT => '部分还款',
        self::STATUS_REPAY_COMPLETE => '已还款',
        self::STATUS_REVIEW_PASS => '审核通过',
        self::STATUS_FUND_CONTRACT => '待签约',
        self::STATUS_WAIT_FOR_CONTACTS => '待补充联系人', //处理M版本
        10000 => '人工拒绝',
        10001 => '机审拒绝',
    ];

    public static $order_status = [
        self::STATUS_ALL => '全部',
        self::STATUS_REPAY_COMPLETE => '已还款',
        self::STATUS_PARTIALREPAYMENT => '部分还款',
        self::STATUS_ELSE => '其他',
    ];
    public static $checkStatus = [
        self::STATUS_REPEAT_TRAIL, self::STATUS_PENDING_LOAN, self::STATUS_CHECK, self::STATUS_PASS, self::STATUS_WAIT_FOR_CONTACTS, self::STATUS_MISS_FILES
    ];

    //前台审核中状态集合
    public static $pre_status_trail = [
        self::STATUS_BAD_DEBT => '已逾期',
        self::STATUS_OVERDUE => '已逾期',
        self::STATUS_REPAYING_CANCEL => '扣款失败',
        self::STATUS_DEBIT_FALSE => '还款失败',
        self::STATUS_REPAY_REPEAT_CANCEL => '审核不通过',
        self::STATUS_REPAY_CANCEL => '审核不通过',
        self::STATUS_PENDING_LOAN_CANCEL => '借款失败',
        self::STATUS_PENDING_CANCEL => '审核不通过',
        self::STATUS_REPEAT_CANCEL => '审核不通过',
        self::STATUS_CANCEL => '审核不通过',
        self::STATUS_MISS_FILES => '审核中',
        self::STATUS_CHECK => '审核中',
        self::STATUS_PASS => '审核中',
        self::STATUS_REPEAT_TRAIL => '审核中',
        self::STATUS_PENDING_LOAN => '审核中',
        self::STATUS_PAY => '打款中',
        self::STATUS_LOAN_COMPLETE => '生息中',
        self::STATUS_LOAN_COMPLING => '生息中',
        self::STATUS_APPLY_REPAY => '还款中',
        self::STATUS_REPAY_TRAIL => '还款中',
        self::STATUS_APPLY_RETRAIL => '还款中',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_PARTIALREPAYMENT => '生息中',
        self::STATUS_REPAY_COMPLETE => '已还款',
        self::STATUS_FUND_CONTRACT => '待签约',
        self::STATUS_WAIT_FOR_CONTACTS => '审核中',
    ];

    //分期购前台审核中状态集合
    public static $installment_pre_status_trail = [
        self::STATUS_BAD_DEBT => '已逾期',
        self::STATUS_OVERDUE => '已逾期',
        self::STATUS_REPAYING_CANCEL => '扣款失败',
        self::STATUS_DEBIT_FALSE => '还款失败',
        self::STATUS_REPAY_REPEAT_CANCEL => '审核不通过',
        self::STATUS_REPAY_CANCEL => '审核不通过',
        self::STATUS_PENDING_LOAN_CANCEL => '借款失败',
        self::STATUS_PENDING_CANCEL => '审核不通过',
        self::STATUS_REPEAT_CANCEL => '审核不通过',
        self::STATUS_CANCEL => '审核不通过',
        self::STATUS_MISS_FILES => '审核中',
        self::STATUS_CHECK => '审核中',
        self::STATUS_PASS => '审核中',
        self::STATUS_REPEAT_TRAIL => '审核中',
        self::STATUS_PENDING_LOAN => '审核中',
        self::STATUS_PAY => '发货中',
        self::STATUS_LOAN_COMPLETE => '已发货',
        self::STATUS_LOAN_COMPLING => '生息中',
        self::STATUS_APPLY_REPAY => '还款中',
        self::STATUS_REPAY_TRAIL => '还款中',
        self::STATUS_APPLY_RETRAIL => '还款中',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_PARTIALREPAYMENT => '生息中',
        self::STATUS_REPAY_COMPLETE => '已还款',
        self::STATUS_FUND_CONTRACT => '待签约',
    ];

    //分期购允许申请的状态
    public static $fqsc_allow_status = [
        self::STATUS_REPAY_COMPLETE,
        self::STATUS_CANCEL,
        self::STATUS_REPEAT_CANCEL,
        self::STATUS_PENDING_CANCEL
    ];

    //订单借款申请状态列表
    public static $loan_apply_status = [
        self::STATUS_CHECK => '待初审',
        self::STATUS_PASS => '初审通过',//不需要
        self::STATUS_REPEAT_TRAIL => '待复审',
        self::STATUS_PENDING_LOAN => '待放款',
        self::STATUS_PAY => '打款中',
    ];

    //订单借款拒绝状态列表
    public static $loan_reject_status = [
        self::STATUS_PENDING_LOAN_CANCEL => '放款失败',
        self::STATUS_PENDING_CANCEL => '放款驳回',
        self::STATUS_REPEAT_CANCEL => '复审驳回',
        self::STATUS_CANCEL => '初审驳回',
    ];

    //放款以后的状态
    public static $loan_after_status = [
        self::STATUS_BAD_DEBT => '已坏账',
        self::STATUS_OVERDUE => '已逾期',
        self::STATUS_REPAYING_CANCEL => '扣款驳回',
        self::STATUS_DEBIT_FALSE => '扣款失败',
        self::STATUS_REPAY_REPEAT_CANCEL => '还款复审驳回',
        self::STATUS_REPAY_CANCEL => '还款初审驳回',
        self::STATUS_LOAN_COMPLETE => '已放款',
        self::STATUS_LOAN_COMPLING => '还款中',
        self::STATUS_APPLY_REPAY => '申请还款',  //不需要 申请还款
        self::STATUS_REPAY_TRAIL => '还款初审',
        self::STATUS_APPLY_RETRAIL => '还款复审',
        self::STATUS_REPAYING => '扣款中',
        self::STATUS_PARTIALREPAYMENT => '部分还款',
        self::STATUS_REPAY_COMPLETE => '已还款',
    ];

    /**
     * 允许切换资方的状态
     * @var type
     */
    public static $allow_switch_fund_status = [
         self::STATUS_FUND_CONTRACT, self::STATUS_PENDING_LOAN
    ];

    /**
     * 允许垫付状态
     * @var type
     */
    public static $allow_prepay_status = [
        self::STATUS_BAD_DEBT,
        self::STATUS_OVERDUE,
        self::STATUS_REPAYING_CANCEL,
        self::STATUS_DEBIT_FALSE,
        self::STATUS_REPAY_REPEAT_CANCEL,
        self::STATUS_REPAY_CANCEL,
        self::STATUS_LOAN_COMPLETE,
        self::STATUS_LOAN_COMPLING,
        self::STATUS_APPLY_REPAY,
        self::STATUS_REPAY_TRAIL,
        self::STATUS_APPLY_RETRAIL,
        self::STATUS_REPAYING,
        self::STATUS_PARTIALREPAYMENT,
        self::STATUS_REPAY_COMPLETE,
    ];

    //cps统计需求状态集合
    public static $operate_status = [
        self::STATUS_BAD_DEBT => '已逾期',
        self::STATUS_OVERDUE => '已逾期',
        self::STATUS_REPAYING_CANCEL => '扣款失败',
        self::STATUS_DEBIT_FALSE => '还款失败',
        self::STATUS_LOAN_COMPLETE => '生息中',
        self::STATUS_LOAN_COMPLING => '生息中',
        self::STATUS_APPLY_REPAY => '还款中',
        self::STATUS_REPAY_TRAIL => '还款中',
        self::STATUS_APPLY_RETRAIL => '还款中',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_PARTIALREPAYMENT => '生息中',
        self::STATUS_REPAY_COMPLETE => '已还款',
    ];

    const EVENT_AFTER_APPLY_ORDER = 'afterApplyOrder';//成功提交订单
    const EVENT_AFTER_REVIEW_PASS = 'afterReviewPass';//审核通过
    const EVENT_AFTER_REVIEW_REJECTED = 'afterReviewRejected';//审核被拒绝
    const EVENT_AFTER_PAY_READY = 'afterPayReady';//待放款
    const EVENT_AFTER_PAY_SUCCESS = 'afterPaySuccess';//放款成功事件
    const EVENT_AFTER_PAY_REJECTED = 'afterPayRejected';//放款拒绝
    const EVENT_AFTER_REPAY_SUCCESS = 'afterRepaySuccess';//还款成功事件
    const EVENT_AFTER_REPAY_FAIL = 'afterRepayFail';//还款失败事件
    const EVENT_AFTER_REPAY_OVERDUE = 'afterRepayOverdue';//逾期事件
//     const EVENT_AFTER_REVIEW_PASS_SLOW = 'afterReviewPassSlow';//慢就赔红包
    const EVENT_AFTER_SIGN_FUND = 'afterSignFund';//在签约了资方后调用
    const EVENT_CHECK_FUND_SIGN = 'checkFundSign';//需要签约资方的时候，一段时间后检查签约状态
    const EVENT_AFTER_BIND_CARD = 'afterBindCard';//绑卡事件
    const EVENT_AFTER_DELAY_LQB = 'afterDelayLqb';//渠道订单展期成功之后给渠道反馈还款计划详情

    //活动页订单状态
    const ACTIVITY_ORDER_EXITS = 1;//存在进行的订单
    const ACTIVITY_ORDER_NO = 0;//不存在进行的订单

    const CUSTOMER_TYPE_NEW_YES  = 1;
    const CUSTOMER_TYPE_NEW_NO  = 0;
    public static $is_black_type=[
        self::CUSTOMER_TYPE_NEW_YES=>'是',
        self::CUSTOMER_TYPE_NEW_NO=>'否',
    ];

    const IS_EXTEND_LOAN = 1;//借款展期
    const IS_NOT_EXTEND_LOAN = 0;//不是借款展期
    const EXTENDREASONREMARK = '借款展期，审核通过';

    public function getCreditJsqb(){
        return $this->hasOne(CreditJsqb::className(), array('person_id' => 'user_id'));
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_loan_order}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public static function getDb_rd()
    {
        return Yii::$app->get('db_kdkj_rd');
    }

    public static function getDbMhk()
    {
        return Yii::$app->get('db_mhk');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function init()
    {
        parent::init();

        $this->on(static::EVENT_AFTER_APPLY_ORDER, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_REVIEW_PASS, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_REVIEW_REJECTED, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_PAY_SUCCESS, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_REPAY_SUCCESS, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_REPAY_FAIL, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_REPAY_OVERDUE, ['common\services\OrderService', 'orderEventHandler']);
//         $this->on(static::EVENT_AFTER_REVIEW_PASS_SLOW, ['common\services\OrderService', 'orderCouponEventHandler']);
        $this->on(static::EVENT_AFTER_PAY_REJECTED, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_SIGN_FUND, ['common\services\OrderService', 'orderEventHandler']);//签约资方后调用
        $this->on(static::EVENT_AFTER_DELAY_LQB, ['common\services\OrderService', 'orderEventHandler']);
        $this->on(static::EVENT_AFTER_BIND_CARD, ['common\services\OrderService', 'orderEventHandler']);

    }

    /**
     * 根据条件查询数量
     * @param $condition
     */
    public function getCountLoan($condition)
    {
        $rs = self::find()->where($condition)->count();
        return is_null($rs) ? 0 : $rs;
    }

    /**
     * 统计总金额
     * @param $condition
     */
    public function getLoanTotalMoney($condition)
    {
        $rs = self::find()->where($condition)->sum('money_amount');
        return is_null($rs) ? 0 : $rs;
    }

    /**
     *根据订单ID，返回对应的来源
     */
    public static function fromApp_ids($ids = array())
    {
        $result = array();
        $res = self::find()->select(['from_app', 'id'])->where("`id` IN (" . implode(',', $ids) . ")")->all(self::getDb_rd());
        if (!empty($res)) {
            foreach ($res as $key => $item) {
                $result[$item['id']] = self::DistinguishFrom($item['from_app']);
            }
        }
        return $result;
    }

    public function getFinancialLoanRecord()
    {
        return $this->hasOne(FinancialLoanRecord::className(), ['business_id' => 'id']);
    }

    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), ['id' => 'user_id']);
    }

    public function getUserLoanCollection()
    {
        return UserLoanCollection::find()->where(['status' => 4, 'loan_id' => $this->id])->one();
    }

    public function getLoanCollectionOrder()
    {
        return $this->hasOne(LoanCollectionOrder::className(), ['user_loan_order_id' => 'id']);
    }

    public function getUserLoanOrderRepayment()
    {
        return $this->hasOne(UserLoanOrderRepayment::className(), ['order_id' => 'id']);
    }

    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), ['id' => 'card_id']);
    }

    public function getLoanFund()
    {
        return $this->hasOne(LoanFund::className(), ['id' => 'fund_id']);
    }

    public function getUserDetail()
    {
        return $this->hasOne(UserDetail::className(), ['user_id' => 'user_id']);
    }

    public function getOrderFundInfo()
    {
        return $this->hasOne(OrderFundInfo::className(), ['order_id' => 'id'])->andWhere('`status`>=0')->orderBy('`id` DESC');
    }

    public function getUserLoanOrderDelay()
    {
        return $this->hasOne(UserLoanOrderDelay::className(), ['order_id' => 'id']);
    }
    /**
     * 根据订单来源区分是小钱包还是极速钱包短信
     * @param unknown $phone
     * @param unknown $message
     * @param unknown $type
     * @param string $sms_channel
     */
    public static function sendSms($phone, $message, $type = null, &$sms_channel = "")
    {
//         if(YII_ENV_PROD){
//             return self::sendSyncSms($phone,$message,$type);
//         }
        $obj = null;
        if ($type && !is_object($type) && !is_array($type)) {
            $obj = self::findOne(['id' => $type]);
        } elseif ($type) {
            $obj = $type;
        }
        if ($obj && isset($obj['sub_order_type']) && $obj['sub_order_type']) { //信用卡
            $channel = self::getSmsService($obj['sub_order_type']);
        } else {
            $channel = "smsService_TianChang_HY";
        }

        return MessageHelper::sendSMS($phone, $message, $channel, true, $sms_channel);
    }

    /**
     * 根据 sub_order_type 返回短信通道
     * @param int $sub_order_type
     * @return string
     */
    public static function getSmsService($sub_order_type) {
        return 'smsServiceXQB_XiAo';

//         if ($sub_order_type) {
//             if (self::SUB_TYPE_XJX == $sub_order_type) {
//                 return 'smsServiceYKXJX';
//             } elseif (self::SUB_TYPE_LYQB == $sub_order_type) {
//                 return 'smsServiceYzdLyqb';
//             } elseif (self::SUB_TYPE_RDZDB == $sub_order_type) {
//                 return 'smsServiceRdZdb';
//             }
//             return 'smsServiceXJK';
//         }
//         return '';
    }

    /**
     * 异步发送短信 -- 根据订单来源区分是小钱包还是极速钱包短信
     * @param string $phone
     * @param string $message
     * @param string $type
     */
    public static function sendSyncSms($phone, $message, $type = null)
    {
        $obj = null;
        if ($type && !is_object($type) && !is_array($type)) {
            $obj = self::findOne(['id' => $type]);
        } elseif ($type) {
            $obj = $type;
        }
        if ($obj && isset($obj['sub_order_type']) && $obj['sub_order_type']) { //信用卡
            return MessageHelper::sendSyncSMS($phone, $message, self::getSmsService($obj['sub_order_type']));
        } else {
            return MessageHelper::sendSyncSMS($phone, $message);
        }
    }

    /**
     * 判断是否有未完成的订单
     * @param unknown $user_id
     */
    public static function checkHasUnFinishedOrder($user_id)
    {
        $hasRepayment = UserLoanOrderRepayment::find()->where(['user_id' => $user_id])->andWhere('status<>' . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->one();
        if ($hasRepayment) {
            return true;
        }
        $updateStatus = [];
        $updateStatus[] = UserLoanOrder::STATUS_CHECK;
        $updateStatus[] = UserLoanOrder::STATUS_MISS_FILES;
        $updateStatus[] = UserLoanOrder::STATUS_PASS;
        $updateStatus[] = UserLoanOrder::STATUS_REPEAT_TRAIL;
        $updateStatus[] = UserLoanOrder::STATUS_PENDING_LOAN;
        $updateStatus[] = UserLoanOrder::STATUS_PAY;
        $updateStatus[] = UserLoanOrder::STATUS_REVIEW_PASS;
        $hasOrder = UserLoanOrder::find()->where(['user_id' => $user_id, 'status' => $updateStatus])->one();
        if ($hasOrder) {
            return true;
        }
        return false;
    }


    /**
     * mhk判断是否有未完成的订单
     * @param unknown $user_id
     */
    public static function checkHasUnFinishedOrderMhk($user_ids)
    {
        $hasRepayment = UserLoanOrderRepayment::find()
                ->where(['in', 'user_id', $user_ids])
                ->andWhere('status<>' . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->one(self::getDbMhk());
        if ($hasRepayment) {
            return true;
        }
        $updateStatus = [];
        $updateStatus[] = UserLoanOrder::STATUS_CHECK;
        $updateStatus[] = UserLoanOrder::STATUS_MISS_FILES;
        $updateStatus[] = UserLoanOrder::STATUS_PASS;
        $updateStatus[] = UserLoanOrder::STATUS_REPEAT_TRAIL;
        $updateStatus[] = UserLoanOrder::STATUS_PENDING_LOAN;
        $updateStatus[] = UserLoanOrder::STATUS_PAY;
        $updateStatus[] = UserLoanOrder::STATUS_REVIEW_PASS;
        $hasOrder = UserLoanOrder::find()->where(['in', 'user_id', $user_ids])->andWhere(['status' => $updateStatus])->one(self::getDbMhk());
        if ($hasOrder) {
            return true;
        }
        return false;
    }

    /**
     * @remark 判断借款人借款订单状态是否是结束状态（拒绝、驳回、已还款）
     *
     **/
    public static function checkLoanOrderIsOK($user_id)
    {

        //客服系统允许提交申请操作的订单状态
        $isPass_arr = array(
            self::STATUS_BAD_DEBT, self::STATUS_REPAYING_CANCEL, self::STATUS_DEBIT_FALSE, self::STATUS_REPAY_REPEAT_CANCEL, self::STATUS_REPAY_CANCEL,
            self::STATUS_PENDING_LOAN_CANCEL, self::STATUS_PENDING_CANCEL, self::STATUS_REPEAT_CANCEL, self::STATUS_CANCEL,
            self::STATUS_REPAY_COMPLETE
        );
        //echo UserLoanOrder::find()->where(['user_id' => $user_id])->andWhere("status not IN(".implode(',', $isPass_arr).")")->createCommand()->getRawSql();die;
        $hasLoanOrder = UserLoanOrder::find()->where(['user_id' => $user_id])->andWhere("status not IN(" . implode(',', $isPass_arr) . ")")->one();
        if ($hasLoanOrder) {
            return true;
        }
        return false;
    }

    public static function getOrderRepaymentCard($id, $user_id = 0, $card_id = 0)
    {
        $where = ['id' => $id, 'order_type' => UserLoanOrder::LOAN_TYPE_LQD];
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        $order = UserLoanOrder::find()->where($where)->asArray()->one();
        if (!$order) {
            throw new UserException("订单不存在");
        }
        //实际到账
        $order['true_money_amount'] = $order['money_amount'] - $order['counter_fee'];

        //收款银行
        $card_info = CardInfo::findOne(['id' => $card_id ? $card_id : $order['card_id'], 'user_id' => $order['user_id']]);
        if ($card_info) {
            $order['bank_info'] = $card_info->bank_name . '(' . substr($card_info->card_no, -4) . ')';
        } else {
            $order['bank_info'] = '';
        }
        $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $id])->asArray()->one();
        if ($repayment) {
            $loan_person = LoanPerson::find()->select(['source_id'])->where(['id'=>$repayment['user_id']])->one();
            //口袋记账和加班管家订单前三天逾期自动减免滞纳金
//            if(!empty($loan_person) && in_array($loan_person->source_id,[LoanPerson::PERSON_SOURCE_KDJZ, LoanPerson::PERSON_SOURCE_JBGJ]) && ($repayment['overdue_day'] <= 3)){
//                $repayment['remain_money_amount'] = $repayment['principal'] - $repayment['true_total_money'];
//            }else{
                $repayment['remain_money_amount'] = $repayment['principal'] + $repayment['interests'] + $repayment['late_fee'] - $repayment['true_total_money'];
//            }

            $repayment['remain_principal'] = $repayment['principal'] - max($repayment['true_total_money'] - $repayment['interests'] - $repayment['late_fee'] - $repayment['coupon_money'], 0);
        }
        return ['order' => $order, 'repayment' => $repayment, 'card_info' => $card_info];
    }

    /**
     * 添加用户借款锁
     */
    public static function lockUserApplyLoanRecord($user_id)
    {
        $lock_key = sprintf("%s%s:%s", RedisQueue::USER_OPERATE_LOCK, "user:loanorder", $user_id);

        if (1 == RedisQueue::inc([$lock_key, 1])) {
            RedisQueue::expire([$lock_key, 10]);
            return true;
        } else {
            RedisQueue::expire([$lock_key, 10]);
        }
        return false;
    }

    /**
     * 释放用户借款锁
     */
    public static function releaseApplyLoanLock($user_id)
    {
        $lock_key = sprintf("%s%s:%s", RedisQueue::USER_OPERATE_LOCK, "user:loanorder", $user_id);
        RedisQueue::del(["key" => $lock_key]);
    }

    /**
     * 添加用户参与免息活动锁
     */
    public static function lockUserApplyCoupon($user_id)
    {
        $lock_key = sprintf("%s%s:%s", RedisQueue::USER_OPERATE_LOCK, "user:apply:free:coupon", $user_id);

        if (1 == RedisQueue::inc([$lock_key, 1])) {
            RedisQueue::expire([$lock_key, 60]);
            return true;
        } else {
            RedisQueue::expire([$lock_key, 60]);
        }
        return false;
    }

    /**
     * 释放抽奖的锁
     */
    public static function releaseUserApplyCoupon($user_id)
    {
        $lock_key = sprintf("%s%s:%s", RedisQueue::USER_OPERATE_LOCK, "user:apply:free:coupon", $user_id);
        RedisQueue::del(["key" => $lock_key]);
    }

    /**
     * 获取来自渠道
     * @return string|null
     */
    public function getFromChannel()
    {
        switch ($this->sub_order_type) {
            case static::SUB_TYPE_RONG360:
                $channel = 'RONG360';
                break;
            case static::SUB_TYPE_SD:
                $channel = 'SD';
                break;
            case static::SUB_TYPE_JLM:
                $channel = 'JLM';
                break;
            case static::SUB_TYPE_WUYAO_CREDIT:
                $channel = 'WUYAO_CREDIT';
                break;
            case static::SUB_TYPE_WUBA:
                $channel = 'WB';
                break;
            case static::SUB_TYPE_WUYAO:
                $channel = 'WY';
                break;
            case static::SUB_TYPE_WYXYK:
                $channel = 'WYXYK';
                break;
            case static::SUB_TYPE_BAIXING:
                $channel = 'BAIXING';
                break;
            case static::SUB_TYPE_JDQ:
                $channel = 'JDQ';
                break;
            case static::SUB_TYPE_BAIRONG:
                $channel = 'BAIRONG';
                break;
            case static::SUB_TYPE_YOUYU:
                $channel = 'YOUYU';
                break;
            default:
                $channel = null;
                break;
        }
        if (!$channel) {
            $register_info = UserRegisterInfo::findOne(['user_id' => $this->user_id]);
            if ($register_info && $register_info->appMarket == 'H5-dm3') {
                $channel = 'DM';
            } else if ($register_info && $register_info->appMarket == 'H5-xyqb') {
                $num = rand(1, 8);
                if ($num == 8) {
                    $register_info->updateAttributes([
                        'appMarket' => 'xxyqb'
                    ]);
                    $channel = null;
                } else {
                    $channel = 'XYQB';
                }
            }
        }

        return $channel;
    }

    /**
     * 是否能够机审
     * @return bool
     */
    public function canAutoCheck()
    {
        if ($this->sub_order_type == static::SUB_TYPE_RONG360) {
            $rong360_order = Rong360LoanOrder::find()->where([
                'order_id' => (int)$this->id
            ])->limit(1)->one();
            if (!$rong360_order || $rong360_order->status != Rong360LoanOrder::STATUS_APPROVING) {
                return false;
            }
        } else {
            if (!isset(LoanOrderSource::$source_list[$this->sub_order_type])) {
                return false;
            }
            $loan_order_source = LoanOrderSource::findOne(['order_id' => $this->id]);
            if (!$loan_order_source || $loan_order_source->status != LoanOrderSource::STATUS_INREVIEW) {
                return false;
            }
        }
        return true;
    }

    public function logCardId()
    {
        if ($this->sub_order_type == static::SUB_TYPE_RONG360) {
            $rong360_order = Rong360LoanOrder::find()->where([
                'order_id' => (int)$this->id
            ])->limit(1)->one();
            //Yii::info("审核时订单card_id为".$this->card_id, 'kdkj.channelorder.rong360.'.$rong360_order->rong360_order_id);
        }
    }

    /**
     * 获取还款时间
     * @return integer
     */
    public function getRepayTime()
    {
        return ($this->loan_time ? $this->loan_time : $this->created_at) + $this->loan_term * 86400;
    }

    public static function getOutAppSubOrderTypeWhere($params)
    {
        $sub_order_type = isset($params['sub_order_type']) ? $params['sub_order_type'] : self::SUB_TYPE_XJD;
        if (in_array($sub_order_type, self::$out_app_sub_order_type)) {
            return ['sub_order_type' => self::$out_app_sub_order_type];
        }
        $types = self::$out_app_sub_order_type;
        $types[] = self::SUB_TYPE_OTHER;
        return ['not in', 'sub_order_type', $types];
    }


    /**
     * 区分订单来源(from_app)
     */
    public static function DistinguishFrom($from_app)
    {
        return isset(self::$from_apps[$from_app]) ? self::$from_apps[$from_app] : '--';
    }

    /**
     * 获取合同数据
     * @param LoanPerson $user 借款用户
     * @return []
     */
    public function getContractData($user = null)
    {
        if (!$user) {
            $user = $this->loanPerson;
        }
        if ($this->loan_time != 0) {
            $time = date('Y 年 m 月 d 日', $this->loan_time);
        } else if ($this->status == -3) {
            $time = '审核未通过';
        } else {
            $time = date('Y 年 m 月 d 日', time());
        }
        $unsure_text = '**订单生成后可见**';
        $idd = 'XYBT' . date('mdHis', $this->loan_time) . substr(md5($this->id), -3);
        $money_da = Util::numToMoney($this->money_amount / 100);
        return [
            'name' => $user->name,
            'id_number' => $user->id_number,
            'lender' => '上海鱼耀金融信息服务有限公司', // 出借方
            'phone' => $user->phone,
            'day' => $this->loan_term + 1,
            'money' => sprintf("%0.2f", $this->money_amount / 100),
            'money_da' => $money_da,
            'time' => $this->loan_time ? date("Y年m月d日", $this->loan_time) : $unsure_text,
            'time_end' => $this->loan_time ? date("Y年m月d日", $this->loan_time + $this->loan_term * 86400) : $unsure_text,
            'service_fee' => sprintf("%0.2f", $this->counter_fee / 100),
            'service_fee_rate' => ($this->counter_fee / $this->money_amount) * 100,
            'interest_rate' => ($this->fund_id && $this->loanFund) ? $this->loanFund->interest_rate : '6.6',
            'time_two' => $time,
            'id' => $idd,
        ];
    }

    /**
     * 获取费用列表 仅当分配了资方的时候有值
     * @return []|null 有资方返回数组  没有资方返回NULL
     */
    public function getFees()
    {
        if ($this->loanFund) {
            $fees = $this->loanFund->getFeeList($this->loan_term, $this->money_amount, $this->card_type, $this->user_id);
        } else {
            $fees = null;
        }
        return $fees;
    }

    /**
     * 是否能够修改状态 在changeStatus前调用
     * @param integer $status 新状态值
     * @return bool 允许修更新为对应状态结果为true 否则为false
     */
    public function canChangeStatus($status)
    {
        $allow = true;
        switch ($this->status) {
            default:
                break;
        }
        return $allow;
    }

    /**
     * 获取改变状态锁名称
     * @param type $id
     * @return type
     */
    public static function getChangeStatusLockName($id)
    {
        return 'changeOrderStatus' . $id;
    }

    /**
     * 改变订单状态
     * @param integer $status 新状态值
     * @param UserOrderLoanCheckLog $log 检查日志
     * @param [] $update_attributes 其他更新的值
     * @param bool $get_lock 是否获取锁
     * @param string $fail_msg 更新失败的信息
     * @return bool 返回成功或失败
     */
    public function changeStatus($status, $log, $update_attributes = [], $get_lock = true, &$fail_msg = '')
    {

        $status = (int)$status;
        $table = static::tableName();

        $update_attributes['status'] = $status;
        $update_attributes['updated_at'] = time();

        $lock_name = static::getChangeStatusLockName($this->id);
        $lock = null;
        if (!$get_lock || ($lock = Lock::get($lock_name, 30))) {
            $old_status = static::find()->select('status')->where('`id`=' . (int)$this->id)->scalar();
            if ($old_status == $this->status) {
                $this->updateAttributes($update_attributes);
                $log->save(false);
                $ret = true;
            } else {
                $fail_msg = "数据库中订单状态为 {$old_status} 当前订单状态为 {$this->status} 已发生变化";
                $ret = false;
            }
        } else {
            $fail_msg = "获取锁失败";
            $ret = false;
        }
        RETURN_RET:
        if ($lock) {
            Lock::del($lock_name);
        }
        return $ret;
    }

    /**
     * 根据日期调用订单金额总量
     * @param $date 具体日期（Y-m-d）
     * @param int $auto 自动审核/人工审核（1/0）
     * @return array|null|ActiveRecord
     *
     */
    public static function getApplyMoney($date, $auto = 1)
    {
        if ($auto) {
            $map = ['=', 'operator_name', 'auto shell'];
        } else {
            $map = ['<>', 'operator_name', 'auto shell'];
        }
        $result = self::find()
            ->select('sum(`money_amount`) as money_amount')
            ->where(['between', 'order_time', strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59')])
            ->andWhere($map)
            ->asArray()->one();

        return $result['money_amount'] ?: 0;
    }

    /**
     * 根据日期调用订单数量
     * @param $date
     * @param int $auto
     * @return array|null|ActiveRecord
     */
    public static function getApplyCount($date, $auto = 1)
    {
        if ($auto) {
            $map = ['=', 'operator_name', 'auto shell'];
        } else {
            $map = ['<>', 'operator_name', 'auto shell'];
        }
        $result = self::find()
            ->select('count(`id`) as count')
            ->where(['between', 'order_time', strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59')])
            ->andWhere($map)
            ->asArray()->one();
        return $result['count'] ?: 0;
    }

    /**
     * 检查用户是否存在被拒订单
     * @param $user_id
     * @return bool
     */
    public static function checkRejectOrder($user_id) {
        $order = self::findOne(['user_id' => $user_id, 'status' => [self::STATUS_CANCEL, self::STATUS_REPEAT_CANCEL, 10000, 10001]]);

        return !empty($order);
    }
    /**
     * 查询用户的订单状态和是否被拒绝
     */
    public static function checkUserOrderStatus($user_id){
        $loan_msg = "您当前有一笔借款正在进行中，请还款后再试";
        $refuse_msg = "很遗憾，您目前的信用评分不足，请于%s天之后再尝试申请";
        $order = self::find()
            ->where(['user_id'=>$user_id])->select(['status'])->one();
        $user_info = LoanPerson::find()
            ->where(['id'=>$user_id,'source_id'=>LoanPerson::PERSON_SOURCE_MOBILE_CREDIT])
            ->select(['can_loan_time'])->one();
        if(isset($user_info->can_loan_time) && $user_info->can_loan_time >0
            && ($user_info->can_loan_time-time()>0 )
        ){
            $day = \ceil(($user_info->can_loan_time - time())/86400);
            return sprintf($refuse_msg,$day);
        }
        $status_list = [
            UserLoanOrder::STATUS_REPAY_COMPLETE,//已完成
            UserLoanOrder::STATUS_REPEAT_CANCEL,//复审被拒
            UserLoanOrder::STATUS_CANCEL,//复审被拒
            //UserLoanOrder::STATUS_OVERDUE,//逾期
            //UserLoanOrder::STATUS_BAD_DEBT,//坏账
         ];
        if($order && !in_array($order->status,$status_list)){
            return $loan_msg;
        }
        return false;
    }
}
