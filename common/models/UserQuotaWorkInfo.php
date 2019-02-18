<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%tuser_quota_person_info}}".
 */
class UserQuotaWorkInfo extends ActiveRecord
{

    const ENTRY_TIME_ONE = 1;
    const ENTRY_TIME_TWO = 2;
    const ENTRY_TIME_THREE = 3;
    const ENTRY_TIME_FOUR = 4;
    const ENTRY_TIME_UNDEFINE = 5;

    public static $entry_time_type = [
        self::ENTRY_TIME_ONE=>'一年以内',
        self::ENTRY_TIME_TWO=>'一到三年',
        self::ENTRY_TIME_THREE=>'三到五年',
        self::ENTRY_TIME_FOUR=>'五年以上',
        self::ENTRY_TIME_UNDEFINE=>'未知',
    ];

    const SALARY_TYPE_ONE = 1;
    const SALARY_TYPE_TWO = 2;
    const SALARY_TYPE_THREE = 3;
    const SALARY_TYPE_FOUR = 4;

    public static $salary_type = [
        self::SALARY_TYPE_ONE=>'3000及以下',
        self::SALARY_TYPE_TWO=>'3000-5000',
        self::SALARY_TYPE_THREE=>'5000-10000',
        self::SALARY_TYPE_FOUR=>'10000以上',
    ];

    const WORK_TYPE_NO = 0;
    const WORK_TYPE_WORK = 1;
    const WORK_TYPE_NO_WORK = 2;
    const WORK_TYPE_STUDENT = 3;
    public static $work_type_pc = [
        self::WORK_TYPE_NO=>'未知',
        self::WORK_TYPE_WORK=>'上班族',
        self::WORK_TYPE_NO_WORK=>'自由职业',
        self::WORK_TYPE_STUDENT=>'学生',
    ];

    public static $work_app_type = [
        self::WORK_TYPE_WORK=>'上班族',
        self::WORK_TYPE_NO_WORK=>'自由职业',
        self::WORK_TYPE_STUDENT=>'学生',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_quota_work_info}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public static function saveUserQuotaWorkInfo($attrs){
        $user_id = $attrs['user_id'];
        $user_quota_work_info = UserQuotaWorkInfo::findOne(['user_id'=>$user_id]);
        if (empty($user_quota_work_info)) {
            $user_quota_work_info = new UserQuotaWorkInfo();
            $user_quota_work_info->user_id = $user_id;
            $user_quota_work_info->created_at = time();
        }
        if(isset($attrs['work_address'])){
            $user_quota_work_info->work_address =  trim($attrs['work_address']);
        }
        if(isset($attrs['work_address_distinct'])){
            $user_quota_work_info->work_address_distinct =  trim($attrs['work_address_distinct']);
        }
        if(isset($attrs['entry_time_type'])){
            $user_quota_work_info->entry_time_type =  trim($attrs['entry_time_type']);
        }
        if(isset($attrs['work_type'])){
            $user_quota_work_info->work_type =  trim($attrs['work_type']);
        }
        if(isset($attrs['longitude']) && $attrs['longitude']){
            $user_quota_work_info->longitude =  trim($attrs['longitude']);
        }
        if(isset($attrs['latitude']) && $attrs['latitude']){
            $user_quota_work_info->latitude =  trim($attrs['latitude']);
        }
        if(isset($attrs['company_payday']) && $attrs['company_payday']){
            $user_quota_work_info->pay_day =  trim($attrs['company_payday']);
        }

        $user_quota_work_info->updated_at = time();
        if(!$user_quota_work_info->save(false)){
            return false;
        }
        return $user_quota_work_info;
    }
}