<?php

namespace common\models\credit_line;

use Yii;
use yii\base\Exception;

class CreditLineTimeLog extends CreditLineActiveRecord
{
    const CREDIT_STATUS_0 = 0;  //审核中
    const CREDIT_STATUS_1 = 1;  //成功
    const CREDIT_STATUS_2 = 2;  //失败

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_line_time_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id','status','credit_status'], 'integer'],
            [['create_time', 'update_time', 'begin_time', 'end_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', '用户 id'),
            'begin_time' => Yii::t('app', '审核开始时间'),
            'end_time' => Yii::t('app', '审核结束时间'),
            'status' => Yii::t('app', '记录状态'),
            'credit_status' => Yii::t('app', '记录认证状态'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * 更新用户的最后一条log
     * @param int $user_id
     * @param int $credit_status
     * @throws Exception
     */
    public static function updateEndTime($user_id, $credit_status) {
        $model = self::find()
            ->where(['user_id'=>$user_id, 'status'=>self::STATUS_ACTIVE])
            ->orderBy('id DESC')
            ->limit(1)
            ->one();
        if (!empty($model)) {
            $model->credit_status = $credit_status;
            $model->end_time = date('Y-m-d H:i:s');
            $model->save();
        }
        else {
            throw new \Exception("No active History Log Found user_id: {$user_id}");
        }
    }
}