<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class LoanBlackList extends  ActiveRecord
{

    const STATUS_YES = 1;
    const STATUS_NO = 0;

    public static $status_list = [
        self::STATUS_YES => '是',
        self::STATUS_NO => '否'
    ];

    public static function tableName()
    {
        return '{{%loan_black_list}}';
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
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

    /**
     * 判断用户是否在黑名单
     * @param LoanPerson $loanPerson
     * @return bool
     */
    public static function isInBlacklist(LoanPerson $loanPerson)
    {
        $sql = \sprintf("(user_id = %s OR phone = '%s' OR id_number = '%s') AND black_status = %s", $loanPerson->id, $loanPerson->phone, $loanPerson->id_number, self::STATUS_YES);
        $count = self::find()->where($sql)->count();

        return $count > 0 ? true : false;
    }


}