<?php

namespace common\models;

use common\helpers\MessageHelper;
use Yii;
use yii\base\Exception;


class LoanOrderDayQuota extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_order_day_quota}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => '日期',
            'norm_orders' => '普通订单',
            'gjj_orders' => '公积金订单',
            'other_orders' => '第三方订单',
            'old_user_orders' => '老用户订单',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }


    public function rules()
    {
        return [
            [['norm_orders', 'gjj_orders', 'other_orders', 'date', 'old_user_orders'], 'required'],
            [['norm_orders', 'gjj_orders', 'other_orders', 'old_user_orders'], 'integer', 'min'=>0],
        ];
    }

    /**
     * 获取当订单额度
     * @return integer
     * @throws \Exception
     */
    public function getTodayRemainingQouta() {
        return $this->getDayRemainingQouta(date('Y-m-d'));
    }


    /**
     * 获取指定日期的配额
     * @param string $date 日期
     */
    public function getDayRemainingQouta($date) {
        $quota_model = static::findOne([
            'date'=>$date
        ]);
        if(!$quota_model) {
            $this->add($date);
            $quota_model = static::findOne([
                'date'=>$date
            ]);
            if(!$quota_model) {
                $msg = "异常：获取不到放款订单{$date}日配额";
                MessageHelper::sendSMS(NOTICE_MOBILE,$msg);
                return [
                    'norm' => 2000000,
                    'gjj' => 2000000,
                    'other' => 2000000,
                    'old_user' => 2000000,
                ];
            }
        }

        return [
            'norm' => $quota_model->norm_orders,
            'gjj' => $quota_model->gjj_orders,
            'other' => $quota_model->other_orders,
            'old_user' => $quota_model->old_user_orders,
        ];
    }


    public function add($date)
    {
        $default = LoanOrderDefaultQuota::findOne(1);
        if(!$default) throw new Exception('无默认放款额度信息');
        $day_quota = new LoanOrderDayQuota();
        $day_quota->date = $date;
        $day_quota->norm_orders = $default->norm_orders;
        $day_quota->gjj_orders = $default->gjj_orders;
        $day_quota->other_orders = $default->other_orders;
        $day_quota->old_user_orders = $default->old_user_orders;
        $ret = $day_quota->save();
        return $ret;
    }
}
