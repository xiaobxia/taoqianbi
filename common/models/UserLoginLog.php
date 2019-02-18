<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * UserLoginLog model
 *
 * @property integer $id
 * @property integer $user_id
 */
class UserLoginLog extends ActiveRecord {
    //登录类型
    const TYPE_NORMAL  = 1;
    const TYPE_QQUNION = 2;
    const TYPE_KDCP    = 3;
    const TYPE_CAPTCHA = 4;

    public static $types = array(
        self::TYPE_NORMAL => '用户名密码登录',
        self::TYPE_QQUNION => 'qq联合登录',
        self::TYPE_KDCP => '口袋超盘接入登录',
        self::TYPE_CAPTCHA => '验证码登录',
    );

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb() {
        return \yii::$app->db;
    }

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'tb_user_login_log';
    }
}
