<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%user_detail}}".
 */
class UserDetail extends \yii\db\ActiveRecord
{
	const INVITE_TYPE_NORMAL = 0;
// 	const INVITE_TYPE_PARTTIME = 1;
//  const INVITE_TYPE_FULLTIME = 2;
    // 分档邀请
    const INVITE_TYPE_GRADE_ONE = 3;
    const INVITE_TYPE_GRADE_TWO = 4;
    const INVITE_TYPE_GRADE_THREE = 5;
    const INVITE_TYPE_GRADE_FOUR = 6;
	const INVITE_TYPE_GRADE_FIVE = 7;

    const EXCLUSIVE_DEFAULT = 0; //普通用户
    const EXCLUSIVE_ENTERPRISE = 1; //企业代表
    const EXCLUSIVE_CHANNEL = 2; //渠道商用户

    public static $invite_type = [
        self::INVITE_TYPE_NORMAL => '普通首投返利',
//         self::INVITE_TYPE_PARTTIME => '兼职用户邀请',
//         self::INVITE_TYPE_FULLTIME => '全职用户邀请',
        self::INVITE_TYPE_GRADE_ONE => '第一档邀请',
        self::INVITE_TYPE_GRADE_TWO => '第二档邀请',
        self::INVITE_TYPE_GRADE_THREE => '第三档邀请',
        self::INVITE_TYPE_GRADE_FOUR => '第四档邀请',
        self::INVITE_TYPE_GRADE_FIVE => '第五档邀请',
    ];

    public static $exclusive_type = [
        self::EXCLUSIVE_DEFAULT => '普通用户',
        self::EXCLUSIVE_ENTERPRISE => '企业代表',
        self::EXCLUSIVE_CHANNEL => '渠道商用户',
    ];

    // 渠道负责人集合
    public static $channel_leaders_ids = [42030, 43799, 73123, 53657, 83934, 208443, 24185,44757];

     // 口袋君出资请吃饭-店长、店名集合
    public static $shopowner_ids = [
        //线上测试用户
        '18' => 'A餐厅',
        '14' => 'B餐厅',
        '82' => 'C餐厅',
        '38296' => 'D餐厅',
        '71277' => 'E餐厅',
        '92308' => '泗喜屋',
        '79863' => '侬好蛙何店长',
        '127645' => '东北水饺馆',
        '113106' => '77川香面馆',
        '95999' => '酷寿司',
        '143820' => '三顾坊',
    ];

    // 用户地址信息处理来源
    const ADDRESS_INTERGRATION = 1; // 积分商城
    const ADDRESS_LOTTERY = 2; // 抽奖
    const ADDRESS_DAILY_SHAKE = 3; // 天天摇
    const ADDRESS_EXTRA = 4; // 其他（暂未使用）

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_detail}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
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
            ['invite_key', 'unique', 'on' => ['edit'], 'message' => '已经存在该邀请码'],
            [['invite_type', 'exclusive_type'], 'safe'],
        ];
    }

    /**
     * 通过邀请码获取
     */
    public static function getUserDetail($invite_key)
    {
        return static::findOne(['invite_key' => $invite_key]);
    }
    public static function getUserInfo($id){
        return static::findOne(['user_id' =>$id]);
    }
    
    public static function saveUserDetail($attrs){
        $user_id = $attrs['user_id'];
        $user_detail = UserDetail::find()->where(['user_id'=>$user_id])->one();
        if(empty($user_detail)){
            $user_detail = new UserDetail();
            $user_detail->user_id = $user_id;
            $user_detail->created_at = time();
        }
        if(isset($attrs['company_name'])){
            $user_detail->company_name =  trim($attrs['company_name']);
        }
        if(isset($attrs['company_phone'])){
            $user_detail->company_phone =  trim($attrs['company_phone']);
        }
        if(isset($attrs['company_address'])){
            $user_detail->company_address =  trim($attrs['company_address']);
        }
        if($user_detail->save(false)) {
            return $user_detail;
        } else {
            return false;
        }
    }
}