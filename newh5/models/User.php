<?php
namespace newh5\models;

use Yii;

class User extends \common\models\User{
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => [self::STATUS_TO_REGISTER,self::STATUS_ACTIVE]]);
    }
}