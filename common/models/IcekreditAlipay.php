<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 冰鉴获取的支付宝信息
 * @property integer $id
 * @property integer $user_id
 * @property string $data
 * @property integer $status
 * @property string $message
 * @property integer $report_status //是否获取冰鉴评分报告
 * @property integer $created_at
 * @property integer $updated_at
 */
class IcekreditAlipay extends ActiveRecord
{
    const STATUS_INIT = 0;
    const STATUS_CALLBACKING = 1;
    const STATUS_FAILED = 2;
    const STATUS_SUCCESS = 3;

    const REPORT_STATUS_INIT = 0; //未获取评分报告
    const REPORT_STATUS_FAILED = 1; //获取评分报告失败
    const REPORT_STATUS_SUCCESS = 2; //获取评分报告成功


    static $status_desc = [
        self::STATUS_INIT => '未完善',
        self::STATUS_CALLBACKING => '认证中',
        self::STATUS_FAILED => '认证失败',
        self::STATUS_SUCCESS => '已填写',
    ];

    public static function tableName()
    {
        return '{{%icekredit_alipay}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
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
            ['status', 'default', 'value' => self::STATUS_INIT],
            ['report_status', 'default', 'value' => self::REPORT_STATUS_INIT],
            ['status', 'in', 'range' => [self::STATUS_INIT, self::STATUS_CALLBACKING, self::STATUS_FAILED, self::STATUS_SUCCESS]],
            ['report_status', 'in', 'range' => [self::REPORT_STATUS_INIT, self::REPORT_STATUS_FAILED, self::REPORT_STATUS_SUCCESS]],
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

}