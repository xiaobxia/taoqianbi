<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/10/17
 * Time: 10:52
 */

namespace backend\models;


use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ManualOrderDispatch
 * @package common\models
 * @property integer $id
 * @property integer $order_id 订单ID
 * @property integer $admin_user_id
 * @property integer $created_at
 * @property integer $updated_at
 */
class ManualOrderDispatch extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%manual_order_dispatch}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
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
     * 人工审核派单
     * @param $order_id
     * @param $admin_user_id
     * @return bool
     */
    public static function dispatchManualOrder($order_id, $admin_user_id)
    {
        if (ManualOrderDispatch::findOne(['order_id' => $order_id])) {
           return false;
        }

        $record = new self();
        $record->order_id = $order_id;
        $record->admin_user_id = $admin_user_id;

        return $record->save();
    }
}
