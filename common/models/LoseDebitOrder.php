<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/7/18
 * Time: 上午10:39
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;


class LoseDebitOrder extends ActiveRecord{

    const TYPE_PAY   = 1;  //主动扣款
    const TYPE_DEBIT = 2;  //系统代扣

    const MODE_DEFAULT = 0; //默认
    const MODE_UNPAY   = 1; //订单未付款
    const MODE_PART    = 2; //订单部分支付
    const MODE_PAYED   = 3; //订单已付款

    const STAFF_TYPE_0 = 0; //未处理
    const STAFF_TYPE_1 = 1; //已处理

    public static $STAFF_TYPE = array(
        self::STAFF_TYPE_0 => '未处理',
        self::STAFF_TYPE_1 => '已处理',
    );

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%lose_debit_order}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }


}