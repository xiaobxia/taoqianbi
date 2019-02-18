<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * “手动”短信发送记录
 */
class PushSms extends ActiveRecord {

    // 来源
    const SOURCE_BACKEND = 1; // 内容管理_手动发送短信
    const SOURCE_SHOP_PERIOD = 2; // 商城分期管理
    const SOURCE_YYTG = 3; // 运营推广
    public static $source = [
        self::SOURCE_BACKEND => '内容管理_手动发送短信',
        self::SOURCE_SHOP_PERIOD => '商城分期管理',
        self::SOURCE_YYTG => '运营推广',
    ];

    // 短信发送状态
    const STATUS_FAIL = 0; // 发送失败
    const STATUS_SUCC = 1; // 发送成功
    const STATUS_DELETE = -1; // 删除
    
    const SMS_STATUS_SUCCESS = 'DELIVRD';

    //列表展示状态
    public static $status = [
        self::STATUS_FAIL => '发送失败',
        self::STATUS_SUCC => '发送成功',
    ];

    public static function getDb() {
        return Yii::$app->db_kdkj;
    }

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'tb_sms_record';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['phone', 'source', 'source_id', 'status', 'sms_id'], 'integer'],
            [['content'], 'required'],
            [['content'], 'string'],
            [['remark'], 'string', 'max' => 255],
            [['channel'], 'string', 'max' => 100],
            [['audit_person', 'sms_status'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'phone' => '用户手机号码',
            'content' => '短信内容',
            'source' => '发送短信来源',
            'source_id' => '来源关联ID',
            'sms_id' => '短信发送id',
            'status' => '发送状态',
            'remark' => '备注',
            'channel' => '通道',
            'sms_status' => '短信到达状态',
            'audit_person' => '操作人',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
