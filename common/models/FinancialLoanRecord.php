<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 17:30
 */
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;


class FinancialLoanRecord extends ActiveRecord
{

    const UMP_PAYING = 1;
    const UMP_PAY_FAILED = 3;
    const UMP_PAY_SUCCESS = 4;
    const UMP_CMB_PAYING = 5;
    const UMP_PAY_DOUBLE_FAILED= 6;
    const UMP_PAY_HANDLE_FAILED= 7;

    public static $ump_pay_status = array(
        self::UMP_PAYING => '申请中',
        self::UMP_PAY_FAILED => '打款失败',
        self::UMP_PAY_SUCCESS => '打款成功',
        self::UMP_CMB_PAYING => '打款中',
        self::UMP_PAY_DOUBLE_FAILED => '打款失败等待中',
        self::UMP_PAY_HANDLE_FAILED => '需人工处理',
    );

    const NOTIFY_WAITING = 0;
    const NOTIFY_SUCCESS = 1;
    const NOTIFY_FALSE = 2;

    public static $notify = array(
        self::NOTIFY_WAITING => '待回调',
        self::NOTIFY_SUCCESS => '回调成功',
        self::NOTIFY_FALSE => '回调失败',
    );

    /**
     * 审核状态
     */
    const REVIEW_STATUS_NO = 0;
    const REVIEW_STATUS_APPROVE = 1;
    const REVIEW_STATUS_REJECT = 2;
    const REVIEW_STATUS_MANUAL = 3;
    const REVIEW_STATUS_CMB_FAILED = 6;
    const REVIEW_STATUS_FORCE_TO_LL = 7;
    const REVIEW_STATUS_FORCE_TO_YEE = 8;   //转易宝付款

    public static $review_status = array(
        self::REVIEW_STATUS_NO => '未审核',
        self::REVIEW_STATUS_APPROVE => '审核通过',
        self::REVIEW_STATUS_REJECT => '审核驳回',
        self::REVIEW_STATUS_MANUAL => '人工打款',
        self::REVIEW_STATUS_CMB_FAILED => '直连失败',
    );

    /**
     * 直连失败操作
     */
    const CMB_FAILED_PAYING = 1;    // 直连已打款
    const CMB_FAILED_MANUAL = 2;    // 转人工打款
    const CMB_FAILED_RESET = 3;     // 重置状态

    public static $cmb_failed_list = array(
        self::CMB_FAILED_PAYING => '直连已打款',
        self::CMB_FAILED_MANUAL => '转人工打款',
        self::CMB_FAILED_RESET => '重置状态',
    );

    //审核通过包含的审核状态
    public static $review_success_status = array(
        self::REVIEW_STATUS_APPROVE,
        self::REVIEW_STATUS_FORCE_TO_LL,
        self::REVIEW_STATUS_FORCE_TO_YEE,
    );

    //打款类型
    const PAYMENT_TYPE_MANUAL = 3;
    const PAYMENT_TYPE_CMB = 4;
    public static $payment_types = array(
        self::PAYMENT_TYPE_MANUAL => "人工打款",
        self::PAYMENT_TYPE_CMB => "直连打款",
    );

    //业务发起打款类型
    const TYPE_LQD=1;
    const TYPR_FZD=2;
    const TYPE_FQSC = 3;
    const TYPE_YGB = 4;
    const TYPE_AST = 5;
    const TYPE_AST_SM = 6;
    const TYPE_AST_LHP = 7;
    const TYPE_AST_FQL = 8;
    const TYPE_AST_QFQ = 9;
    const TYPE_AST_MM = 10;
    const TYPE_AST_DQD = 11;
    const TYPE_AST_CD = 12;
    const TYPE_AST_PPD = 13;
    const TYPE_AST_HFQ = 14;
    const TYPE_AST_RRZL = 15;
    const TYPE_AST_MDX = 16;
    const TYPE_AST_JSY = 17;
    const TYPE_AST_DF = 18;
    const TYPE_AST_ZJD = 19;
    const TYPE_AST_SDJK = 20;
    const TYPE_AST_XXD = 21;
    const TYPE_AST_SD = 22;
    const TYPE_YGD = 23;
    const TYPE_XJK = 23;
    const TYPE_AST_MBD = 24;
    const TYPE_AST_QNN = 25;
    const TYPE_FD = 26;
    const TYPE_XT = 27;
    const TYPE_JUPEI = 28;
    const TYPE_AST_YQB = 29;
    const TYPE_AST_MO9 = 30;
    const TYPE_INVITE_REBATES = 31;  //邀请返现
    const TYPE_AST_JQK = 32;
    const TYPE_AST_LLD = 33;
    const TYPE_AST_XJX = 34;
    const TYPE_AST_SDKD = 35;
    const TYPE_AST_XQD = 36;

    public static $kd_platform_type = [
        self::TYPE_LQD,
        self::TYPR_FZD,
        self::TYPE_FQSC,
        self::TYPE_YGB,
        self::TYPE_YGD,
        self::TYPE_XJK,
        self::TYPE_FD,
        self::TYPE_XT,
        self::TYPE_JUPEI, // 拒就赔红包
        self::TYPE_INVITE_REBATES, // 邀请返现
    ];

    //红包
    public static $kd_platform_cash_type = [
        self::TYPE_JUPEI, // 拒就赔红包
        self::TYPE_INVITE_REBATES, // 邀请返现
    ];

    public static $other_platform_type = [
        self::TYPE_AST,
        self::TYPE_AST_SM,
        self::TYPE_AST_LHP,
        self::TYPE_AST_FQL,
        self::TYPE_AST_QFQ,
        self::TYPE_AST_MM,
        self::TYPE_AST_DQD,
        self::TYPE_AST_CD,
        self::TYPE_AST_PPD,
        self::TYPE_AST_HFQ,
        self::TYPE_AST_RRZL,
        self::TYPE_AST_MDX,
        self::TYPE_AST_JSY,
        self::TYPE_AST_DF,
        self::TYPE_AST_ZJD,
        self::TYPE_AST_SDJK,
        self::TYPE_AST_XXD,
        self::TYPE_AST_SD,
        self::TYPE_AST_MBD,
        self::TYPE_AST_QNN,
        self::TYPE_AST_YQB,
        self::TYPE_AST_MO9,
        self::TYPE_AST_JQK,
        self::TYPE_AST_LLD,
        self::TYPE_AST_XJX,
        self::TYPE_AST_SDKD,
        self::TYPE_AST_XQD,
    ];

    public static $types = array(
        self::TYPE_LQD=>'零钱包',
        self::TYPR_FZD=>'房租宝',
        self::TYPE_FQSC =>'分期购',
        self::TYPE_YGB => '员工帮',
        self::TYPE_YGD => '小钱包',
        self::TYPE_XJK => APP_NAMES,
        self::TYPE_FD => '房抵',
        self::TYPE_XT => '信托',

        self::TYPE_AST => '第三方合作',
        self::TYPE_AST_SM => '第三方合作-什马',
        self::TYPE_AST_LHP => '第三方合作-量化派',
        self::TYPE_AST_FQL => '第三方合作-分期乐',
        self::TYPE_AST_QFQ => '第三方合作-趣分期',
        self::TYPE_AST_MM => '第三方合作-米么',
        self::TYPE_AST_DQD => '第三方合作-51短期贷',
        self::TYPE_AST_CD => '第三方合作-虫洞',
        self::TYPE_AST_PPD => '第三方合作-拍拍贷',
        self::TYPE_AST_HFQ => '第三方合作-会分期',
        self::TYPE_AST_RRZL => '第三方合作-人人租赁',
        self::TYPE_AST_MDX => '第三方合作-买单侠',
        self::TYPE_AST_JSY => '第三方合作-极速云',
        self::TYPE_AST_DF => '第三方合作-达飞',
        self::TYPE_AST_ZJD => '第三方合作-指尖贷',
        self::TYPE_AST_SDJK => '第三方合作-闪电借款',
        self::TYPE_AST_XXD => '第三方合作-咻咻贷',
        self::TYPE_AST_SD => '第三方合作-闪贷',
        self::TYPE_AST_MBD => '第三方合作-秒白贷',
        self::TYPE_AST_QNN => '第三方合作-钱牛牛',
        self::TYPE_JUPEI => '拒就赔红包',
        self::TYPE_AST_YQB => '第三方合作-用钱宝',
        self::TYPE_AST_MO9 => '第三方合作-mo9',
        self::TYPE_INVITE_REBATES => '邀请返现',  //红包返利
        self::TYPE_AST_JQK => '第三方合作-借钱快',
        self::TYPE_AST_LLD => '第三方合作-蓝领贷',
        self::TYPE_AST_XJX => '第三方合作-现金侠',
        self::TYPE_AST_SDKD => '第三方合作-闪电快贷',
        self::TYPE_AST_XQD => '第三方合作-向前贷',

    );

    //财务小组成员
    public static $FinancialManagerList = [
        "yuchen" => NOTICE_MOBILE,
    ];


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%financial_loan_record}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['review_result', 'payment_type', 'review_remark', 'success_time', 'remit_status_code'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '用户提现ID',
            'order_id' => '订单ID',
            'batch_No' => '打款批次号（易宝）',
            'user_id' => '用户ID',
            'money' => '提现金额',
            'user_fee' => '手续费',
            'status' => '状态',
            'review_username' => '审核人',
            'review_time' => '审核时间',
            'review_result' => '审核结果',
            'review_remark' => '审核备注',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'type' => '提现类型',
            'third_platform' => '第三方平台',
            'bank_id' => '提现银行ID',
            'bank_name' => '提现银行名称',
            'card_no' => '绑定银行卡编号',
            'bind_card_id' => '绑卡表ID',
            'platform_fee' => '手续费',
            'auto_review_result' => '自动审核结果',
            'request_at' => '向第三方发起请求的时间',
            'payment_type' => '打款渠道',
        ];
    }

    /**
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

    /**
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), array('id' => 'bind_card_id'));
    }
    /**
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getUserLoanOrder(){
        return $this->hasOne(UserLoanOrder::className(), array('id' => 'business_id'));
    }
    public static function addLock($id){
        $key = "FinancialLoanRecord_lock_$id";
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 30]);
            return true;
        }else{
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 30]);
        }
        return false;
    }
}