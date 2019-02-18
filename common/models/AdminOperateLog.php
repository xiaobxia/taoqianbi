<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/5/24
 * Time: 13:23
 */
namespace common\models;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class AdminOperateLog extends Activerecord {
    public static function tableName() {
        return '{{%admin_operate_log}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }
}
