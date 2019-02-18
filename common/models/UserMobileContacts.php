<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\mongo\statistics\UserMobileContactsMongo;
/**
 * This is the model class for table "{{%user_mobile_contacts}}".
 */
class UserMobileContacts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_mobile_contacts}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 获取借款人信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

    //获取通讯录数据
    public static function getContactData($user_id) {
        return UserMobileContactsMongo::find()->where(['user_id' => $user_id . '' ])->asArray()->all();
    }
    //获取该用户的手机号对不对
    public static function getUserPhoneContactData($loanPerosn,$phone){
        return UserMobileContactsMongo::find()->where(['_id' => $loanPerosn->id.'_'.$phone . '' ])->asArray()->all();
    }

    //判断通讯录是否获取
    public static function getCheckContactResult($user_id)
    {

        $mobile_contact_count = UserMobileContactsMongo::find()->where(['user_id' =>  $user_id . ''])->asArray()->count();
        if ($mobile_contact_count != 0) {
            return true;
        }

        return false;
    }
}