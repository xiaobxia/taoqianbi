<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\UserCaptcha;
use common\helpers\CommonHelper;
use common\models\LoanPerson;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $verifyCode;
    public $phoneCaptcha;

    private $_user = false;

    /**
     * @inheritdoc
     */
    public function rules() {
        if (! YII_ENV_PROD) {
            return [
                [['username', 'password'], 'required'],
                ['password', 'validatePassword'],
            ];
        }

        return [
            [['username', 'password'], 'required'],
            //['verifyCode', 'captcha', 'captchaAction' => 'main/captcha'],
//            ['phoneCaptcha', 'validatePhoneCaptcha'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels() {
        return [
            'username' => '用户名',
            'password' => '密码',
            'verifyCode' => '验证码',
            'phoneCaptcha' => '验证码',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (CommonHelper::isLocal()) {
                return;
            }
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, '用户名或密码错误');
            }
        }

        if (!$this->hasErrors() && isset($this->getUser()->role) &&(!self::channel_redirect($this->getUser()->role)) && YII_ENV_PROD) {
            if (!isset($_POST['LoginForm']['phoneCaptcha']) || empty(isset($_POST['LoginForm']['phoneCaptcha']))){
                $this->addError($attribute, '验证码不能为空');
            }
            $userService = Yii::$container->get('userService');
            $this->phoneCaptcha = trim($_POST['LoginForm']['phoneCaptcha']);
            if (!$user || !$userService->validatePhoneCaptcha($user->phone, $this->phoneCaptcha, UserCaptcha::TYPE_ADMIN_LOGIN)) {
                $this->addError($attribute, '验证码错误');
            }
        }

    }

    /**
     * Validates the phoneCaptcha.
     */
    public function validatePhoneCaptcha($attribute, $params) {
        if (!$this->hasErrors() && (!self::channel_redirect($this->getUser()->role))) {
            $user = $this->getUser();
            $userService = Yii::$container->get('userService');
            if (!$user || !$userService->validatePhoneCaptcha($user->phone, $this->phoneCaptcha, UserCaptcha::TYPE_ADMIN_LOGIN)) {
                $this->addError($attribute, '验证码错误');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser());
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            if(is_numeric($this->username)){
                $this->_user = AdminUser::findOne(['phone'=>$this->username, 'open_status'=>1]);
            }else{
                $this->_user = AdminUser::findOne(['username'=>$this->username, 'open_status'=>1]);
            }

        }

        return $this->_user;
    }

    /**
     * 渠道跳转
     * @param string $role 角色权限
     * @return $this 链接跳转
     */
    public static function channel_redirect($role = ""){
        new LoanPerson();
        $arr = explode(",",$role);
        foreach ($arr as $val){
            if (!empty($role) && array_key_exists($val, LoanPerson::$user_agent_source)){
                return true;
            }
        }
        return false;
    }
}
