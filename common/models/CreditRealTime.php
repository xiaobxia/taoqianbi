<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class CreditRealTime extends ActiveRecord
{

     const REAL_VERIFY_STATUS = 1 ;//个人信息认证 REAL_VERIFY_STATUS
     const REAL_CONTACT_STATUS =2 ;//紧急联系人认证
     const REAL_BIND_BANK_CARD_STATUS = 3 ;//银行卡认证
     const REAL_ZMXY_STATUS = 4; //芝麻信用认证
     const REAL_JXL_STATUS = 5;  //手机运营商认证
     const REAL_ACCMULATION_FOUND = 6; //公积金认证

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_real_time}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('db_kdkj');
    }


    /**
     * 添加认证时间
     * @inheritdoc
     */
    public static function addRealTime($user_id,$real_type){
            $creditrealtime = new CreditRealTime();
            $creditrealtime->user_id = $user_id;
            $creditrealtime->real_type = $real_type;
            $creditrealtime->real_register_time = time();
            return $creditrealtime->save();
    }
    /**
     * 修改认证时间
     * @inheritdoc
     */
    public static  function updateRealTime($real_id){
            $creditrealtime = CreditRealTime::findOne($real_id);
            $creditrealtime->real_update_time = time();
            return $creditrealtime->save();

    }
    public static function real_time(){

    }
    /**
     * 关联对象：用户表
     * @return LoanPerson|null
     */
//    public function getInviteUserLoanOrder()
//    {
//        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
//    }

}
