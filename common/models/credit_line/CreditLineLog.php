<?php

namespace common\models\credit_line;

use Yii;

/**
 * CreditLineLog
 * 用户额度生成流水
 * @package common\models\credit_line
 */
class CreditLineLog extends CreditLineActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_line_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'credit_line_id', 'rule_id', 'rule_value'], 'required'],
            [['user_id', 'credit_line_id', 'rule_id', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['rule_detail'], 'string', 'max' => 512],
            [['rule_value'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'credit_line_id' => Yii::t('app', '额度id'),
            'root_id' => Yii::t('app', '决策树id'),
            'rule_id' => Yii::t('app', '特征id'),
            'rule_detail' => Yii::t('app', '特征描述'),
            'rule_value' => Yii::t('app', '特征值'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '0 正常 1删除'),
        ];
    }


}