<?php
namespace common\models;

use yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * UserPassword model
 *
 * @property integer $id
 * @property string $user_id
 * @property string $password
 */
class UserPayPassword extends BaseActiveRecord
{
    //交易密码弹框校验相关
    const PAY_PWD_CHECK_KEY = 'koudaikj:pay_pwd_check_key';
    const PAY_PWD_CHECK_VALUE = 'Z@J&CAz&JL*WE8ua';
    const PAY_PWD_LEN = 6;//密码长度
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%user_pay_password}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['password', 'required', 'message' => '密码不能为空'],
//			['password', 'match', 'pattern' => '/^[0-9]{6}$/i', 'message' => '密码只能是6位数字'],
		];
	}
}