<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class CreditJxlQueue
 * @property integer $id
 * @property integer $user_id
 * @property string $service_code
 * @property string $token
 * @property string $website
 * @property string $captcha
 * @property integer $current_status
 * @property integer $error_code
 * @property string $message
 * @property string $query_pwd
 * @property integer $type
 * @property integer $channel
 * @property integer $created_at
 * @property integer $updated_at
 */
class CreditJxlQueue extends ActiveRecord {

    public static $error_code_list = [
        0 => 'success',
        1 => '系统错误，请重试',
        10001 => '再次输入短信验证码',
        10002 => '输入短信验证码',
        10003 => '密码错误',
        10004 => '短信验证码错误',
        10006 => '短信验证码失效系统已自动重新下发',
        10007 => '简单密码或初始密码无法登录,请先修改手机服务密码',
        10009 => '验证信息错误',
        30000 => '短信验证码发送次数超过上限，请明天再试',
        10017 => '暂不支持北京移动号码',
        10018 => '暂不支持北京移动号码',
        10022 => '暂不支持北京移动号码',
        10023 => '暂不支持北京移动号码',
    ];

    const STATUS_CAPTCHA_ERROR = -4;
    const STATUS_ORDER_ERROR = -3;
    const STATUS_PHONE_PWD_ERROR = -2;
    const STATUS_RESTART_PROCESS = -1;
    const STATUS_INPUT_PHONE_PWD = 1;
    const STATUS_WAIT_PHONE_PWD_RESULT = 2;
    const STATUS_INPUT_CAPTCHA = 3;
    const STATUS_WAIT_CAPTCHA_RESULT = 4;
    const STATUS_WAIT_RESEND_CAPTCHA = 5;
    const STATUS_PROCESS_FINISH = 6;
    const STATUS_INPUT_QUERY_PWD = 10;
    const STATUS_WAIT_QUERY_PWD_RESULT = 11;
    const STATUS_CAPTCHA_RESENDED = 14;

    public static $current_status_list = [
        self::STATUS_CAPTCHA_ERROR => '验证码错误',
        self::STATUS_ORDER_ERROR => '订单异常',
        self::STATUS_PHONE_PWD_ERROR => '服务密码错误',
        self::STATUS_RESTART_PROCESS => '重新开始流程',
        self::STATUS_INPUT_PHONE_PWD => '待输入手机服务密码',
        self::STATUS_WAIT_PHONE_PWD_RESULT => '输入手机服务密码后等待结果',
        self::STATUS_INPUT_CAPTCHA => '待输入验证码',
        self::STATUS_WAIT_CAPTCHA_RESULT => '输入验证码后等待结果',
        self::STATUS_WAIT_RESEND_CAPTCHA => '重发验证码后等待结果',
        self::STATUS_PROCESS_FINISH => '流程完成',
        self::STATUS_INPUT_QUERY_PWD => '待输入查询密码',
        self::STATUS_WAIT_QUERY_PWD_RESULT => '输入查询密码后等待结果',
        self::STATUS_CAPTCHA_RESENDED => '验证码已重发',
    ];

    const CHANNEL_WEALIDA = 1; //白卡渠道
    const CHANNEL_JSQB = 2; //极速钱包渠道


    public static function tableName() {
        return '{{%credit_jxl_queue}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj_risk');
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    public static function hasTheUser($user_id) {
        $queue = CreditJxlQueue::findOne(['user_id'=>$user_id]);
        return !\is_null($queue);
    }

}