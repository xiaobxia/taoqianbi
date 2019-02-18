<?php

namespace backend\models;

use Yii;
/**
 * This is the model class for table "{{%loan_collection_group_statistic}}".
 *
 */
class LoanCollectionGroupStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_group_statistic}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['loan_group', 'total_money', 'loan_total', 'today_finish_total_money', 'finish_total_money', 'no_finish_total_money', 'operate_total', 'today_finish_total', 'finish_total', 'finish_late_fee', 'late_fee_total'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'loan_group' => Yii::t('app', '分组'),
            'total_money' => Yii::t('app', '总本金'),
            'loan_total' => Yii::t('app', '总单数'),
            'today_finish_total_money' => Yii::t('app', '今日还款本金总额 单位为分'),
            'finish_total_money' => Yii::t('app', '还款本金总额 单位为分'),
            'no_finish_total_money' => Yii::t('app', '剩余本金总额 单位为分'),
            'operate_total' => Yii::t('app', '处理过的订单个数'),
            'today_finish_total' => Yii::t('app', '当日还款单数'),
            'finish_total' => Yii::t('app', '还款总数'),
            'finish_total_rate' => Yii::t('app', '还款率'),
            'no_finish_rate' => Yii::t('app', '迁徙率'),
            'finish_late_fee' => Yii::t('app', '滞纳金收取金额'),
            'late_fee_total' => Yii::t('app', '本应缴纳的滞纳金总额'),
            'finish_late_fee_rate' => Yii::t('app', '滞纳金回收率'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
