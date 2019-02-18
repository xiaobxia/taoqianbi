<?php
namespace common\models;

use common\base\LogChannel;
use Yii;
use yii\db\ActiveRecord;

/**
 * UserCaptcha model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $phone
 * @property string $captcha
 * @property string $type
 * @property integer $generate_time
 * @property integer $expire_time
 */
class UserCaptcha extends ActiveRecord
{
    // 验证码30分钟有效期
    const EXPIRE_SPACE = 1800;

    // 验证码类型
    const TYPE_REGISTER = 'register';
    const TYPE_FIND_PWD = 'find_pwd';
    const TYPE_FIND_PAY_PWD = 'find_pay_pwd';
    const TYPE_CHANGE_PAY_PWD = 'change_pay_pwd';
    const TYPE_INVEST_KDB = 'invest_kdb';
    const TYPE_INVEST_PROJ = 'invest_proj';
    const TYPE_INVEST_CREDIT = 'invest_credit';
    const TYPE_UMP_PAY_CHARGE = 'ump_pay_charge';
    const TYPE_UMP_BIND_CARD = 'ump_bind_card';
    const TYPE_JTY_PAY_CHARGE = 'jyt_pay_charge';
    const TYPE_YEEPAY_BIND_CARD = 'yee_bind_card';
    const TYPE_BAOFU_BIND_CARD = 'baofu_bind_card';
    const TYPE_ADMIN_LOGIN = 'admin_login';
    const TYPE_CHANGE_PHONE = 'change_phone';
    const TYPE_YYDB_INDIANA = 'yydb_indiana';
    const TYPE_USER_LOGIN_CAPTCHA = 'captcha_login';
    const TYPE_BIND_ALIPAY_AUTH   = 'bind_alipay';
    const TYPE_EMAIL_COMPANY_CHECK = 'eamil_com_check';//验证公司邮箱
    const TYPE_BIND_BANK_CARD = 'bind_bank_card';//绑定银行卡
    const TYPE_BIND_CREDIT_CARD = 'bind_credit_car';//绑定信用卡
    const TYPE_QIANCHENG_DATA_CAPTCHA = 'qianchen_data'; //前程数据短信验证码

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_captcha}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 获得验证码的短信内容
     */
    public function getSMS($channel = false) {
        $effective = intval(self::EXPIRE_SPACE / 60);
        if ($this->type == self::TYPE_REGISTER) {
            return "您的验证码为:{$this->captcha} (有效期{$effective}分钟)";
        }

        return "您的验证码为:{$this->captcha} (此验证码有效期为{$effective}分钟)";
    }

    /**
     * 短信验证
     *
     * @param string $phone
     * @param string $code
     * @param int    $type
     */
    public static function validateCaptcha($phone, $code, $type, $source = null) {
        if (empty($source)) {
            \yii::warning(sprintf(
                'user_captcha_validateCaptcha %s,%s,%s,%s', $phone, $code, $type, $source
            ), LogChannel::USER_CARD);
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }

        $sql = "select expire_time from {{%user_captcha}}
                 where phone=:phone 
                   and captcha=:captcha 
                   and type=:type 
                   and source_id=:source_id 
                 limit 1";
        $result = self::getDb()->createCommand($sql, [
            ':phone'=>$phone,
            ':captcha'=>$code,
            ':type'=>$type,
            ':source_id'=>$source,
        ])->queryOne();

        if ($result) {
            return time() <= $result['expire_time'];
        }
        return false;
    }
    /**
     * 通过手机号获取登录验证码
     */
    public static function getUserLoginCaptcha($phone) {
        $captcha = self::find()->where(['type'=>self::TYPE_ADMIN_LOGIN, 'phone'=>$phone])->limit(1)->one();
        if (!empty($captcha)) {
            return $captcha;
        }
        return false;
    }


    /**
     * 通过手机号获取登录验证码 | 催收
     */
    public static function getUserLoginCaptchaCall($phone) {
        $res = \common\models\loan\LoanCollection::find()->where(['phone' => $phone])->one();
        if (!$res) {
            return false;
        }
        $captcha = self::find()->where(['type'=>self::TYPE_ADMIN_LOGIN, 'phone'=>$phone])->limit(1)->one();
        if (!empty($captcha)) {
            return $captcha;
        }
        return false;
    }
}
