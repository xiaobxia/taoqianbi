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
class UserPassword extends BaseActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%user_password}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
	        //['user_id',  'unique', 'message' => '用户ID唯一'],
			['password', 'required', 'message' => '密码不能为空'],
//			['password', 'string', 'length' => [6, 16], 'message' => '密码为6-16位字符或数字', 'tooShort'=>'密码为6-16位字符或数字', 'tooLong'=>'密码为6-16位字符或数字'],
		];
	}
}