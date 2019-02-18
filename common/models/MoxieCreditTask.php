<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 摩蝎数据任务
 * @property integer $id
 * @property integer $user_id
 * @property string $task_id
 * @property string $email
 * @property integer $status
 * @property string $message
 * @property integer $created_at
 * @property integer $updated_at
 */
class MoxieCreditTask extends ActiveRecord
{
    const STATUS_BILL_ING = 1;    //  认证中
    const STATUS_BILL_FAILED = 2; // 认证失败
    const STATUS_BILL_SUCCESS = 3; // 认证成功
    const STATUS_CALLBACK_SUCCESS = 4; // 收到账单回调通知

    const CREDIT_TYPE_EMAIL = 1;  // 邮箱
    const CREDIT_TYPE_ONLINE_CARD = 2;  // 网银

    static $status = [
        self::STATUS_BILL_FAILED => '点击重新认证',  // 认证失败
        self::STATUS_BILL_ING => '待认证',
        self::STATUS_BILL_SUCCESS => '已填写',
    ];

    public static function tableName()
    {
        return '{{%moxie_credit_task}}';
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
     * 功    能: 查询最后一条认证数据
     * @param type $userId      用户ID
     * @param type $type        认证类型
     */
    static public function queryUserLast($userId, $type){
        $query = static::find()
                ->select(["task_id", "email", "message", "status", "updated_at", "created_at"])
                ->where(["user_id" => intval($userId), "credit_type" => intval($type)])
                ->orderBy("id DESC")->limit("0, 1");
        return $query->asArray()->one();
    }

}