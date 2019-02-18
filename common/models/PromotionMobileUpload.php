<?php

namespace common\models;

use Yii;
use common\helpers\ArrayHelper;
use common\api\RedisQueue;
use common\traits\RelationTrait;

/**
 * This is the model class for table "tb_promotion_mobile_upload".
 *
 * @property integer $id
 * @property integer $mobile
 * @property string $channel
 * @property integer $status
 * @property integer $created_by
 * @property string $created_at
 * @property string $updated_at
 */
class PromotionMobileUpload extends \yii\db\ActiveRecord {
    use RelationTrait;

    const STATUS_WAIT         = 0;     //等待信息发送
    const STATUS_SEND_SUCCESS = 1;     //信息发送成功
    const STATUS_SEND_FAIL    = 2;     //信息发送失败
    public static $status_label = [
        self::STATUS_WAIT         => '等待发送',
        self::STATUS_SEND_SUCCESS => '发送成功',
        self::STATUS_SEND_FAIL    => '发送失败',
    ];

    const ISTATUS_NO_CLICK = -1;     //未点击
    const ISTATUS_DEFAULT = 0;       //已点击
    const ISTATUS_REG = 1;          //注册
    const ISTATUS_SX_SUCCESS = 2;   //授信成功
    const ISTATUS_SX_FAIL = 3;      //授信失败 (end)
    const ISTATUS_DEBIT_FAIL = 4;   //初审驳回(end)
    const ISTATUS_DEBIT_SUCCESS = 5; //还款中
    const ISTATUS_OVERDUE_YES = 6;  //逾期(end)
    const ISTATUS_REPAYMENT_SUCCESS = 7; //正常还款(end)
    const ISTATUS_DEBIT_ING = 8;  //借款中
    const ISTATUS_DEBIT_CHECK = 9;  //借款审核中
    const ISTATUS_BLACK_LIST = 10;   //黑名单
    public static $istatus_label = [
        self::ISTATUS_NO_CLICK          => '未点击',
        self::ISTATUS_DEFAULT           => '已点击',
        self::ISTATUS_REG               => '已注册',
        self::ISTATUS_SX_SUCCESS        => '授信成功',
        self::ISTATUS_SX_FAIL           => '未授信',
        self::ISTATUS_DEBIT_FAIL        => '借款被拒',
        self::ISTATUS_DEBIT_SUCCESS     => '借款成功',
        self::ISTATUS_OVERDUE_YES       => '逾期',
        self::ISTATUS_REPAYMENT_SUCCESS => '正常还款',
        self::ISTATUS_DEBIT_ING         => '借款中',
        self::ISTATUS_DEBIT_CHECK       => '借款审核中',
        self::ISTATUS_BLACK_LIST        => '黑名单',
    ];
    const CREATED_BY_ONE = 0;
    const CREATED_BY_TWO = 1;
    public static $created_by = [
        self::CREATED_BY_ONE     => '落地页',
        self::CREATED_BY_TWO     => '短信',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'tb_promotion_mobile_upload';
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['mobile', 'channel', 'created_by', ], 'required'],
            [['status', 'internal_status', 'created_by', ], 'integer'],
            [['mobile'], 'string', 'length' => 11],
            [['channel'], 'string', 'max' => 50],
            [['status', 'internal_status'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'mobile' => '手机号',
            'channel' => '渠道名',
            'tpl_id' => '短信模版id',
            'status' => '状态',
            'internal_status' => '内部状态',
            'created_by' => '创建者',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 短信模版
     * $this->tpl
     */
    public function getTpl() {
        return $this->tHasOne(\common\models\SmsTemplate::class, 'id', 'tpl_id');
    }

    /**
     * 返回全部已存在的channel
     * @return array
     */
    public static function channels() {
        $ret = self::find()->select(['channel'])->distinct()->asArray()->all();
        return ArrayHelper::getColumn($ret, 'channel');
    }

    /**
     * 批量插入
     * @param array $columns
     * @param array $values
     * @return number
     */
    public static function batchInsert(array $columns, array $values) {
        if (empty($columns) || empty($values)) {
            return null;
        }

        $sql = \yii::$app->db->getQueryBuilder()->batchInsert(self::tableName(), $columns, $values);
        return \yii::$app->db->createCommand($sql)->execute();
    }

    /**
     * 批量插入到redis待发队列
     * @param array $phones_info   [mobile => tpl_id]
     * @return mixed
     */
    public static function sendPromotionSms(array $phones_info) {
        if (empty($phones_info)) {
            return false;
        }

        $count = 0;
        foreach($phones_info as $item) {
            $_ret = RedisQueue::push([RedisQueue::LIST_USER_PROMOTION_SMS, \json_encode([
                'phone' => $item['phone'],
                'tpl_id' => $item['tpl_id'],
                'url' =>$item['url'],
                'type' => 'smsServiceXQB_XiAo_YX', //能发送营销类短信即可 TODO 暂时只有“希奥”  换成希奥营销通道
                'db_log' => 1, //录入db
            ])]);
            if ($_ret) {
                $count ++;
            }
        }

        return $count;
    }

}
