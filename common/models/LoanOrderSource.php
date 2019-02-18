<?php

namespace common\models;

use Yii;
use common\api\RedisQueue;

/**
 * 订单来源 记录来自融360 借了吗 闪贷 等等的订单记录
 * This is the model class for table "{{%loan_order_source}}".
 *
 * @property string $id
 * @property string $user_id 用户ID
 * @property string $order_id 订单ID
 * @property string $source_order_id 来源订单ID
 * @property string $source 来源
 * @property integer $status 状态
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 *
 * @property UserLoanOrder $order 订单
 */
class LoanOrderSource extends \yii\db\ActiveRecord
{
    const STATUS_NONE = 0;//无状态
    const STATUS_CANCEL = 30;//订单已取消
    const STATUS_MISS_FILES = 50; //待补充材料
    const STATUS_PRE_APPROVE_PASS = 60;//预审通过
    const STATUS_PRE_APPROVE_REJECTED = 61;//预审失败
    const STATUS_WAIT_FOR_REVIEW = 70;//补充资料完成
    // const STATUS_MISS_FILES = 80; //待补充材料
    const STATUS_BING_CARD = 80; //绑卡成功
    const STATUS_CONFIRM_LOAN = 85; //确认借款
    const STATUS_INREVIEW = 90;//审核中
    const STATUS_REVIEW_PASS = 100;//审核通过
    const STATUS_REVIEW_REJECTED = 110;//审核拒绝
    const STATUS_WAIT_FOR_CONTRACT = 140;//待签合同
    const STATUS_WAIT_FOR_LOAN = 150;//等待放款
    const STATUS_LOANED_FAIL = 160;//放款失败
    const STATUS_LOANED_SUCCESS = 170;//放款成功
    const STATUS_REPAY_APPLY = 180;//申请还款
    const STATUS_REPAYING = 190;//正常还款中
    const STATUS_FINISH = 200;//贷款结清
    const STATUS_REPAY_FAIL = 220;//还款失败
    const STATUS_EXPIRED = 210;//逾期

    /**
     * 状态列表
     * @var array
     */
    public static $status_list = [
        self::STATUS_NONE => '无',
        self::STATUS_CANCEL => '已取消',
        self::STATUS_MISS_FILES => '待补充材料',
        self::STATUS_PRE_APPROVE_PASS => '预审通过',
        self::STATUS_PRE_APPROVE_REJECTED => '预审失败',
        self::STATUS_WAIT_FOR_REVIEW => '补充资料完成',
        self::STATUS_BING_CARD => '绑卡成功',
        self::STATUS_CONFIRM_LOAN => '确认借款中',
        self::STATUS_INREVIEW => '审核中',
        self::STATUS_REVIEW_PASS => '审核通过',
        self::STATUS_REVIEW_REJECTED => '审核拒绝',
        self::STATUS_WAIT_FOR_CONTRACT => '待签合同',
        self::STATUS_WAIT_FOR_LOAN => '等待放款',
        self::STATUS_LOANED_SUCCESS => '放款成功',
        self::STATUS_LOANED_FAIL => '放款失败',
        self::STATUS_REPAY_APPLY => '申请还款',
        self::STATUS_REPAYING => '正常还款中',
        self::STATUS_FINISH => '贷款结清',
        self::STATUS_EXPIRED => '逾期',
        self::STATUS_REPAY_FAIL => '还款失败',
    ];

    public static $status_youyu = [
        self::STATUS_MISS_FILES => 50,
        self::STATUS_WAIT_FOR_REVIEW => 60,
        self::STATUS_BING_CARD => 60,
        self::STATUS_INREVIEW => 60,
        self::STATUS_CONFIRM_LOAN => 60,
        self::STATUS_REVIEW_REJECTED => 70,
        self::STATUS_REVIEW_PASS => 80,
        self::STATUS_WAIT_FOR_LOAN => 90,
        self::STATUS_LOANED_SUCCESS => 100,
        self::STATUS_LOANED_FAIL => 70,
        self::STATUS_REPAY_APPLY => 110,
        self::STATUS_REPAYING => 110,
        self::STATUS_FINISH => 130,
        self::STATUS_EXPIRED => 120,
        self::STATUS_REPAY_FAIL => 110,
    ];
    
    public static $status_bairong = [
        // self::STATUS_CANCEL => '已取消',
        // self::STATUS_MISS_FILES => '待补充材料',
        // self::STATUS_PRE_APPROVE_PASS => '预审通过',
        // self::STATUS_PRE_APPROVE_REJECTED => '预审失败',
        self::STATUS_WAIT_FOR_REVIEW => 50001,
        self::STATUS_BING_CARD => 50016,
        // self::STATUS_INREVIEW => '审核中',
        self::STATUS_REVIEW_PASS => 50004,
        self::STATUS_REVIEW_REJECTED => 50005,
        // self::STATUS_WAIT_FOR_CONTRACT => '待签合同',
        self::STATUS_WAIT_FOR_LOAN => 50007,
        self::STATUS_LOANED_SUCCESS => 50008,
        self::STATUS_LOANED_FAIL => 50009,
        self::STATUS_REPAY_APPLY => 50018,
        self::STATUS_REPAYING => 50018,
        self::STATUS_FINISH => 50010,
        self::STATUS_EXPIRED => 50011,
        // self::STATUS_EXPIRED => 50012, //逾期还款
        self::STATUS_REPAY_FAIL => 50020,
    ];
    
    const SOURCE_ALL = UserLoanOrder::SUB_TYPE_ALL;//所有平台
    const SOURCE_YGD = UserLoanOrder::SUB_TYPE_YGD;//小钱包
    const SOURCE_XJD = UserLoanOrder::SUB_TYPE_XJD;//极速荷包
    const SOURCE_RONG360 = UserLoanOrder::SUB_TYPE_RONG360;//融360
    const SOURCE_SD = UserLoanOrder::SUB_TYPE_SD;//闪贷
    const SOURCE_JDQ = UserLoanOrder::SUB_TYPE_JDQ;//借点钱
    const SOURCE_JLM = UserLoanOrder::SUB_TYPE_JLM;//借了吗
    const SOURCE_XJX = UserLoanOrder::SUB_TYPE_XJX;//英科-现金侠
    const SOURCE_LYQB = UserLoanOrder::SUB_TYPE_LYQB;//零用钱包
    const SOURCE_RDZDB = UserLoanOrder::SUB_TYPE_RDZDB;//融都-掌贷宝
    const SOURCE_WUYAO = UserLoanOrder::SUB_TYPE_WUYAO;//51公积金
    const SOURCE_WUYAO_CREDIT = UserLoanOrder::SUB_TYPE_WUYAO_CREDIT;//51信用卡
    const SOURCE_WUBA = UserLoanOrder::SUB_TYPE_WUBA;//58消费贷
    const SOURCE_WYXYK = UserLoanOrder::SUB_TYPE_WYXYK;//51信用卡
    const SOURCE_BAIXING = UserLoanOrder::SUB_TYPE_BAIXING;// 百姓金融
    const SOURCE_BAIRONG = UserLoanOrder::SUB_TYPE_BAIRONG;// 百融
    const SOURCE_YOUYU = UserLoanOrder::SUB_TYPE_YOUYU;// 有鱼
    const SOURCE_OTHER = UserLoanOrder::SUB_TYPE_OTHER;// 其他
    
    public static $source_list = [
        self::SOURCE_ALL => '全部',
        self::SOURCE_YGD => '小钱包',
        self::SOURCE_XJD => APP_NAMES,
        self::SOURCE_RONG360 => '融360',
        self::SOURCE_SD => '闪贷',
        self::SOURCE_JDQ => '借点钱',
        self::SOURCE_JLM => '借了吗',
        self::SOURCE_XJX => '英科-现金侠',
        self::SOURCE_LYQB => '零用钱包',
        self::SOURCE_RDZDB => '融都-掌贷宝',
        self::SOURCE_WUYAO => '51公积金',
        self::SOURCE_WUYAO_CREDIT => '51信用卡',
        self::SOURCE_WUBA => '58消费贷',
        self::SOURCE_OTHER => '其他',
        self::SOURCE_WYXYK => '51信用卡借贷渠道',
        self::SOURCE_BAIXING => '百姓金融借贷渠道',
        self::SOURCE_BAIRONG => '百融',
        self::SOURCE_YOUYU => '有鱼',
    ];

    #source与UserLoanOrder的sub_order_type的对应字典
    public static $source_to_subOrderType_list = [
        // self::SOURCE_ALL => [
        //     UserLoanOrder::SUB_TYPE_RONG360,
        //     UserLoanOrder::SUB_TYPE_JLM,
        //     UserLoanOrder::SUB_TYPE_SD,
        //     UserLoanOrder::SUB_TYPE_WUYAO,
        //     UserLoanOrder::SUB_TYPE_WUBA,
        //     UserLoanOrder::SUB_TYPE_WYXYK,
        //     UserLoanOrder::SUB_TYPE_BAIXING,
        //     UserLoanOrder::SUB_TYPE_JDQ,
        // ],
        self::SOURCE_RONG360 => UserLoanOrder::SUB_TYPE_RONG360,
        self::SOURCE_JLM => UserLoanOrder::SUB_TYPE_JLM,
        self::SOURCE_SD => UserLoanOrder::SUB_TYPE_SD,
        self::SOURCE_WUYAO => UserLoanOrder::SUB_TYPE_WUYAO,
        self::SOURCE_WUBA => UserLoanOrder::SUB_TYPE_WUBA,
        self::SOURCE_WYXYK => UserLoanOrder::SUB_TYPE_WYXYK,
        self::SOURCE_BAIXING => UserLoanOrder::SUB_TYPE_BAIXING,
        self::SOURCE_JDQ => UserLoanOrder::SUB_TYPE_JDQ,
    ];

    const EVENT_AFTER_CHANGE_STATUS = 'afterChangeStatus';//在更新状态后

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_order_source}}';
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

    public function init()
    {
        parent::init();

        $this->on(static::EVENT_AFTER_CHANGE_STATUS, [$this, 'afterChangeStatus']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'order_id', 'source_order_id', 'status'], 'required'],
            [['user_id', 'order_id', 'status'], 'integer'],
            [['source_order_id'], 'string', 'max' => 48],
            [['status'], 'in', 'range' => self::$status_list],
            [['source'], 'in', 'range' => self::$source_list],
            [['order_id'], 'unique'],
            [['source', 'source_order_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '借款人ID',
            'order_id' => '订单ID',
            'source' => '订单来源',
            'source_order_id' => '外来订单ID',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @return UserLoanOrder
     */
    public function getOrder() {
        return $this->hasOne(UserLoanOrder::className(), ['id'=>'order_id']);
    }

    /**
     * @return UserLoanOrderRepayment
     */
    public function getOrderRepayment() {
        return $this->hasOne(UserLoanOrderRepayment::className(), ['order_id'=>'order_id']);
    }

    /**
     * @return LoanPerson
     */
    public function getUser() {
        return $this->hasOne(LoanPerson::className(), ['id'=>'user_id']);
    }

    /**s
     * 添加一个记录
     * @param integer $user_id
     * @param integer $order_id
     * @param string $source_order_id
     * @param integer $status
     * @return \static|boolean
     */
    public static function add($user_id, $order_id, $source, $source_order_id, $status)
    {
        $model = new static;
        $model->order_id = (int)$order_id;
        $model->user_id = (int)$user_id;
        $model->source = (int)$source;
        $model->source_order_id = trim($source_order_id);
        $model->status = (int)$status;
        $model->save(false);
        return $model;
    }

    /**
     * 判断 是否可以设置为对应 的状态
     * @param integer $status 新的状态值
     * @return boolean
     */
    // public function canSetStatus($status)
    // {
    //     $allow = true;
    //     switch ($status) {
    //         case static::STATUS_WAIT_FOR_REVIEW:
    //             if (!in_array($this->status, [static::STATUS_CANCEL, static::STATUS_MISS_FILES, static::STATUS_WAIT_FOR_REVIEW])) {
    //                 $allow = false;
    //             }
    //             break;
    //         case static::STATUS_REPAY_APPLY:
    //             if (!in_array($this->status, [static::STATUS_LOANED_SUCCESS])) {
    //                 $allow = false;
    //             }
    //             break;
    //         case static::STATUS_REPAYING:
    //             if (!in_array($this->status, [static::STATUS_REPAY_APPLY])) {
    //                 $allow = false;
    //             }
    //             break;
    //         case static::STATUS_FINISH:
    //             if (!in_array($this->status, [static::STATUS_REPAYING])) {
    //                 $allow = false;
    //             }
    //             break;
    //         default:
    //             break;
    //     }
    //     return $allow;
    // }


    public function canSetStatus($status)
    {
        if ($this->status < $status) {
            return $this->changeStatus($status);
        } else {
            return false;
        }
    }

    /**
     * 修改订单状态
     * @param integer $status 新的状态值
     * @return bool
     */
    public function changeStatus($status)
    {
        if ($this->status != $status) {
            $old_status = $this->status;
            $this->status = (int)$status;
            $this->updateAttributes(['status']);
            //触发修改订单状态事件
            $this->trigger(static::EVENT_AFTER_CHANGE_STATUS, new \common\base\Event(['custom_data' => [
                'old_status' => $old_status,
                'new_status' => $status
            ]]));
            return true;
        }
        return false;
    }

    /**
     * 在更改状态后触发
     * @param \common\base\Event $event 事件
     */
    public function afterChangeStatus($event)
    {
        $custom_data = $event->custom_data;
        switch ($custom_data['new_status']) {
            default:
                break;
        }
    }

    public static function findOrderId($orderId)
    {
        return self::find()->where(['order_id' => $orderId])->limit(1)->one();
    }

    public static function findUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->limit(1)->one();
    }

    public static function genOrderId($source, $order_id)
    {
        return strtoupper($source . '_' . $order_id);
    }

    public function getSourceOrderId()
    {
        return strtoupper($this->source . '_' . $this->source_order_id);
    }

    public function getBairongCode()
    {
        return isset(self::$status_bairong[$this->status]) ? self::$status_bairong[$this->status] : 0;
    }

}
