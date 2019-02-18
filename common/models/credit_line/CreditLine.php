<?php

namespace common\models\credit_line;

use Yii;

use common\models\LoanPerson;

/**
 * CreditLine
 * 用户额度表
 * @package common\models\credit_line
 */
class CreditLine extends CreditLineActiveRecord
{

    // public static $rule_id = 210;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_line}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'credit_line', 'credit_line_base', 'credit_line_gjj', 'credit_line_kdjz', 'status', 'time_limit'], 'integer'],
            [['valid_time', 'create_time', 'update_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', '用户id'),
            'credit_line' => Yii::t('app', '提额结果'),
            'credit_line_base' => Yii::t('app', '提额结果-基础额度'),
            'credit_line_gjj' => Yii::t('app', '提额结果-公积金'),
            'credit_line_kdjz' => Yii::t('app', '提额结果-口袋记账'),
            'time_limit' => Yii::t('app', '借款期限'),
            'valid_time' => Yii::t('app', '有效期限'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '0 正常 1删除'),
        ];
    }

    public function getUser()
    {
        return LoanPerson::findOne($this->user_id);
    }

    public function getTree()
    {
        $model =  CreditLineLog::find()->where(['user_id'=>$this->user_id, 'credit_line_id'=>$this->id, 'status'=>self::STATUS_ACTIVE])->orderBy('id DESC')->one();

        if(empty($model)) return "";
        return $model->root_id;
    }

    /**
     * 获取最新的授信记录
     * @param $condition
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findLatestOne($condition)
    {
        return static::find()->where($condition)->limit(1)->one();
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