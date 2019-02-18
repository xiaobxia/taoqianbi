<?php

/**
 * Created by PhpStorm.
 * User: lijia
 * Date: 16-11-1
 * Time: 12:00
 */

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

class RepaymentConfig extends \yii\db\ActiveRecord {

    // 任务状态
    const STATUS_VALID = 1; // 有效
    const STATUS_INVALID = 2; // 无效


    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%repayment_config}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['percent','max'], 'required', 'message' => '不能为空']
        ];
    }

    /**
     * 活动期间显示且状态 为 发送状态的
     */
    public static function isCanTotalShowPop($show_site=1){
        $now = time();
        $condition = sprintf(" status=%s AND show_site=%s AND expect_time <= %s AND expire_time > %s ",PopBox::STATUS_SUCC,$show_site,$now,$now);
        return static::find()->where($condition)->count();
    }

    public static function getTopShowPop($show_site=1){
        $now = time();
        $condition = sprintf(" status=%s AND show_site=%s AND expect_time <= %s AND expire_time > %s ",PopBox::STATUS_SUCC,$show_site,$now,$now);
        return static::find()->where($condition)->orderBy('id desc')->limit(1)->one();
    }

    

}
