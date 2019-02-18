<?php

namespace backend\models;

use Yii;
/**
 * This is the model class for table "{{%loan_collection_statistic}}".
 *
 */
class LoanCollectionStatistics extends \yii\db\ActiveRecord
{
    /**
     *
     *
     */
    public static function user_level_after($admin_user_id, $order_level, $unixtime){
        return self::find()->select("*")->where(" admin_user_id={$admin_user_id} AND order_level={$order_level} AND created_at>={$unixtime}")->one();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_statistic}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
     public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['admin_user_id', 'outside', 'total_money', 'loan_total', 'today_finish_total_money', 'finish_total_money', 'no_finish_total_money', 'operate_total', 'today_finish_total', 'finish_total', 'finish_late_fee', 'late_fee_total','today_get_loan_total','today_get_total_money','leave_principal','get_principal'], 'integer'],
            ['username', 'string', 'max' => 64],
            ['created_at','default', 'value'=>time()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'admin_user_id' => Yii::t('app', 'admin用户uid'),
            'username' => Yii::t('app', '姓名'),
            'outside' => Yii::t('app', '机构'),
            'total_money' => Yii::t('app', '总本金'),
            'loan_total' => Yii::t('app', '总单数'),
            'today_get_loan_total' => Yii::t('app', '今日入催单数 单位为分'),
            'today_get_total_money' => Yii::t('app', '今日入催本金总额 单位为分'),
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
            'leave_principal' => Yii::t('app', '转出本金'),
            'get_principal' => Yii::t('app', '转入本金'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    //查出指定条件的个人统计表信息
    public static function getPersonStatistic($condition,$orderBy=['id'=>SORT_DESC]){
        return self::find()->where($condition)->orderBy($orderBy)->all();
    }
    //通过月份 分组 订单级别获取当月的个人每天统计数据
    public static function getStatisticByMonth($month,$outside,$loan_group,$order_level){
        $this_month = strtotime($month);
        $next_month = strtotime($month.'+1 month');
        $condition = " outside={$outside} AND loan_group={$loan_group} AND order_level={$order_level} AND created_at>={$this_month} AND created_at<{$next_month}";
        return self::find()->where($condition)->asArray()->all();
    }
}
