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


class FinancialDebitRecord extends ActiveRecord
{
    const STATUS_PAYING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FALSE = 2;
    const STATUS_REFUSE = 3;
    const STATUS_CSDEBIT = 4;
    const STATUS_RECALL = 9;

    public static $status = array(
        self::STATUS_PAYING  => '待扣款',
        self::STATUS_SUCCESS => '扣款成功',
        self::STATUS_FALSE   => '扣款失败',
        self::STATUS_REFUSE  => '扣款驳回',
        self::STATUS_CSDEBIT => '尝试代扣',
        self::STATUS_RECALL  => '扣款回调中',
    );
    
    public static $platforms = array(
            BankConfig::PLATFORM_UMPAY => "联动优势",
            BankConfig::PLATFORM_YEEPAY => "易宝支付",
    );

    //业务发起打款类型
    const TYPE_YGB = 1;
    const TYPE_YGB_LQB = 5;
    const TYPE_AST = 2;
    const TYPE_YGD = 4;
    
    
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
    const TYPE_XJK = 23;
    const TYPE_AST_MBD = 24;
    const TYPE_AST_QNN = 25;
    const TYPE_FD = 26;
    const TYPE_XT = 27;
    const TYPE_AST_YQB = 28;
    const TYPE_AST_MO9 = 29;
    const TYPE_AST_JQK = 30;
    const TYPE_AST_LLD = 31;
    const TYPE_AST_XJX = 32;
    const TYPE_AST_SDKD = 33;
    const TYPE_AST_XQD = 34;

    public static $kd_platform_type = [
        self::TYPE_YGB,
        self::TYPE_YGB_LQB,
    	self::TYPE_YGD,
    	self::TYPE_XJK,
    	self::TYPE_FD,
    	self::TYPE_XT,
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
        self::TYPE_YGB => '员工帮',
        self::TYPE_YGB_LQB => '零钱包',
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
    	self::TYPE_AST_YQB => '第三方合作-用钱宝',
    	self::TYPE_AST_MO9 => '第三方合作-mo9',
        self::TYPE_AST_JQK => '第三方合作-借钱快',
        self::TYPE_AST_LLD => '第三方合作-蓝领贷',
        self::TYPE_AST_XJX => '第三方合作-现金侠',
        self::TYPE_AST_SDKD => '第三方合作-闪电快贷',
        self::TYPE_AST_XQD => '第三方合作-向前贷',
    );

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%financial_debit_record}}';
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
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
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
     * 获得扣款订单信息
     * @return \yii\db\ActiveQuery
     */
    public function getUserLoanOrder() {
        return $this->hasOne(UserLoanOrder::className(), array('id' => 'loan_record_id'));
    }

    /**
     * 获得扣款订单信息
     * @return \yii\db\ActiveQuery
     */
    public function getUserVerification() {
        return $this->hasOne(UserVerification::className(), array('user_id' => 'user_id'));
    }
    /**
     *  生成代扣订单号
     */
    public static function generateOrderDebit($type = 0){
        if($type){
            return  date('His').'_'.base_convert(bcmul(microtime(true), '1000', 0), 10, 32).substr(md5(uniqid(mt_rand(0,100))), 0, 8);
        }
        return  date('YmdHis').'_'.base_convert(bcmul(microtime(true), '1000', 0), 10, 32).substr(md5(uniqid(mt_rand(0,100))), 0, 8);
    }

    /**
     * 获取用户银行卡
     * @return \yii\db\ActiveQuery
     */
    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), array('id' => 'debit_card_id'));
    }
    
    public static function addDebitLock($order_id=0 , $user_id=0){
        $key = "FinancialDebitRecord_lock_".$order_id.$user_id;
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 180]);
            return true;
        }
        return false;
    }
    public static function addDebitLock_mhk($order_id=0 , $user_id=0){
        $key = "Mhk_FinancialDebitRecord_lock_".$order_id.$user_id;
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 180]);
            return true;
        }
        return false;
    }
    public static function clearDebitLock($order_id=0 , $user_id=0){
        $key = "FinancialDebitRecord_lock_".$order_id.$user_id;
        \Yii::$app->redis->executeCommand('DEL', [$key]);
    }

    //生成批量代扣批次号
    public static function generateBatchDebit()
    {
        $date = date('YmdHis');
        $day = date('Ymd');
        $key = "financial_generate_batch_debit_order_".$day;
        $number = \Yii::$app->redis->executeCommand('INCRBY', [$key, 1]);
        if(empty($number)){
            $number = mt_rand(1000,9999);
        }
        \Yii::$app->redis->executeCommand('EXPIRE', [$key, 86400]);
        $batch_order = $date . '_' . $number;
        return $batch_order;
    }

    //生成批量代扣订单号
    public static function generateBatchDebitOrder($id)
    {
        $order_id = date('YmdHis').'_'.$id;
        return $order_id;
    }
    //代扣回调时加锁
    public static function addCallBackDebitLock($order_uuid){
        $key = "FinancialDebitRecord_callback_lock_".$order_uuid;
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 120]);
            return true;
        }
        return false;
    }
    public static function clearCallBackDebitLock($order_uuid){
        $key = "FinancialDebitRecord_callback_lock_".$order_uuid;
        \Yii::$app->redis->executeCommand('DEL', [$key]);
    }

}