<?php

namespace common\models;
use Yii;
use common\exceptions\UserExceptionExt;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%tuser_quota_person_info}}".
 */
class UserQuotaPersonInfo extends ActiveRecord
{

    const LIVE_TIME_BN_LOW = 1;
    const LIVE_TIME_YN_LOW = 2;
    const LIVE_TIME_YN_ABOVE = 3;
    public static $live_time_type = [
        self::LIVE_TIME_BN_LOW=>'半年以内',
        self::LIVE_TIME_YN_LOW=>'半年到一年',
        self::LIVE_TIME_YN_ABOVE=>'一年以上',
    ];

    const DOCTOR = 1;
    const MASTER = 2;
    const UNDERGRADUATE = 3;
    const JUNIOR_COLLEGE = 4;
    const SECONDARY_SPECIALIZED_SCHOOL=5;
    const HIGH_SCHOOL = 6;
    const JUNIOR_MIDDLE_SCHOOL = 7;
    const JUNIOR_HIGH_SCHOOL=8;
    const OTHER_SCHOOL = 9;


    public static $bairong_degrees = [
        30001 => self::DOCTOR,
        30002 => self::MASTER,
        30003 => self::UNDERGRADUATE,
        30004 => self::JUNIOR_COLLEGE,
        30005 => self::SECONDARY_SPECIALIZED_SCHOOL,
        30006 => self::JUNIOR_MIDDLE_SCHOOL,
        30007 => self::JUNIOR_HIGH_SCHOOL,

    ];

    public static $degrees = [
        self::DOCTOR =>'博士',
        self::MASTER =>'硕士',
        self::UNDERGRADUATE =>'本科',
        self::JUNIOR_COLLEGE =>'大专',
        self::SECONDARY_SPECIALIZED_SCHOOL =>'中专',
        self::HIGH_SCHOOL =>'高中',
        self::JUNIOR_MIDDLE_SCHOOL =>'初中',
        self::JUNIOR_HIGH_SCHOOL =>'初中以下',
        self::OTHER_SCHOOL =>'未知',
    ];

    const UNMARRIED = 1;
    const MARRIED_NOT_BEAR = 2;
    const MARRIED_BEAR = 3;
    const DIVORCE = 4;
    const WIDOWED = 5;
    const OTHER = 100;
    public static $marriage = [
        self::UNMARRIED=>'未婚',
        self::MARRIED_NOT_BEAR=>'已婚未育',
        self::MARRIED_BEAR=>'已婚已育',
        self::DIVORCE=>'离异',
      //  self::WIDOWED=>'丧偶',
        self::OTHER=>'其他',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_quota_person_info}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public static function getLiveTimeDict(){
        $data = [];
        $all = self::$live_time_type;
        foreach($all as $key=>$item){
            $data[]=[
                'live_time_type'=>$key,
                'name'=>$item,
            ];
        }
        return $data;
    }
    public static function getDegreeDict(){
        $data = [];
        $all = self::$degrees;
        foreach($all as $key=>$item){
            $data[]=[
                'degrees'=>$key,
                'name'=>$item,
            ];
        }
        return $data;
    }
    public static function getMarriageDict(){
        $data = [];
        $all = self::$marriage;
        foreach($all as $key=>$item){
            $data[]=[
                'marriage'=>$key,
                'name'=>$item,
            ];
        }
        return $data;
    }
    public static function saveUserQuotaPersonInfo($attrs){
        $user_id = $attrs['user_id'];
        $user_quota_person_info = UserQuotaPersonInfo::findOne(['user_id'=>$user_id]);
        if(empty($user_quota_person_info)){
            $user_quota_person_info = new UserQuotaPersonInfo();
            $user_quota_person_info->user_id = $user_id;
            $user_quota_person_info->created_at = time();
        }
        if(isset($attrs['address_distinct'])){
            $user_quota_person_info->address_distinct = trim($attrs['address_distinct']);
        }
        if(isset($attrs['address'])){
            $user_quota_person_info->address =  trim($attrs['address']);
        }
        if(isset($attrs['live_time_type'])){
            $user_quota_person_info->live_time_type =  trim($attrs['live_time_type']);
        }
        if(isset($attrs['degrees'])){
            $user_quota_person_info->degrees =  trim($attrs['degrees']);
        }
        if(isset($attrs['marriage'])){
            $user_quota_person_info->marriage =  trim($attrs['marriage']);
        }
        if(isset($attrs['longitude']) && $attrs['longitude']){
            $user_quota_person_info->longitude =  trim($attrs['longitude']);
        }
        if(isset($attrs['latitude'])  && $attrs['latitude']){
            $user_quota_person_info->latitude =  trim($attrs['latitude']);
        }

        $user_quota_person_info->updated_at = time();
        if(!$user_quota_person_info->save()){
            return false;
        }
        return $user_quota_person_info;
    }
}