<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class UserPhysicalPrizes extends ActiveRecord {

    // 记录次数的缓存 用于判断黑名单奖品
    const USER_BLACK_COUNT = "user:black:count:"; // 用户红包总个数
    const USER_ANTA_COUNT  = "user:anta:count:"; // 用户安踏劵总个数
    
    const PHYSICAL_MAKE_NEW_YEAR = 1;           // 新春活动 New Year
    const PHYSICAL_MAKE_YUAN_XIAO = 2;           // 元宵活动 New Year

    public static $case_id = [
        self::PHYSICAL_MAKE_NEW_YEAR => "新春活动",
        self::PHYSICAL_MAKE_YUAN_XIAO => "元宵活动",
    ];

    const PHYSICAL_GLOBAL_USB_DRIVE = 1; // U盘
    const PHYSICAL_GLOBAL_PORTABLE_BATTERY = 2; // 充电宝
    const PHYSICAL_GLOBAL_PHYSICAL_EXAMINATION = 3; // 体检劵 Physical examination
    const PHYSICAL_GLOBAL_COOPERATION = 4;  // 异步合作劵
    const PHYSICAL_GLOBAL_ANTA = 5;  // 安踏合作劵

    public static $type = [
        self::PHYSICAL_GLOBAL_USB_DRIVE => "U盘",
        self::PHYSICAL_GLOBAL_PORTABLE_BATTERY => "充电宝",
        self::PHYSICAL_GLOBAL_PHYSICAL_EXAMINATION => "体检劵",
        self::PHYSICAL_GLOBAL_COOPERATION => "创意抱枕",
        self::PHYSICAL_GLOBAL_ANTA => "安踏兑换劵",
    ];

    const PHYSICAL_GLOBAL_NO_OPERATION = 0; // 未操作
    const PHYSICAL_GLOBAL_YES_OPERATION = 1; // 已操作

    public static $status = [
        self::PHYSICAL_GLOBAL_NO_OPERATION => "未发放",
        self::PHYSICAL_GLOBAL_YES_OPERATION => "已发放",
    ];

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['user_id', 'case_id', 'type'], 'required', 'message' => '不能为空'],
            [[ 'status'], 'safe'],
            ['text', 'string', 'max' => 64, 'min' => 2],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_physical_prizes}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 根据用户返回中奖列表
     * @param $user_id
     * @param $activity_id
     * @return array|ActiveRecord[]
     */
    public static function getPrizeListByUser($user_id,$activity_id){
        return self::find()
            ->leftJoin('tb_user_coupon_prizes', 'tb_user_physical_prizes.type = tb_user_coupon_prizes.id')
            ->where(['tb_user_physical_prizes.user_id'=>$user_id,'tb_user_physical_prizes.case_id'=>$activity_id])
            ->select(['tb_user_physical_prizes.created_at','tb_user_coupon_prizes.title'])
            ->asArray()
            ->all();
    }

}
