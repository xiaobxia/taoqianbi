<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class UserRealnameVerify extends ActiveRecord
{
    const STATUS_NO = 0;
    const STATUS_YES = 1;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_realname_verify}}';
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * 关联对象：借款记录表
     * @return UserCreditTotal|null
     */
    public function getInviteUserLoanOrder()
    {
        return $this->hasMany(UserLoanOrder::className(), ['user_id' => 'user_id'])->from(['order' => UserLoanOrder::tableName()]);
    }

}
