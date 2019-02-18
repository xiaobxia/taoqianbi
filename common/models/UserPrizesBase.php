<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/3
 * Time: 16:08
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class UserPrizesBase extends BaseActiveRecord {

    const GLOBAL_STATUS_CLOSE = 0; // 全局关闭
    const GLOBAL_STATUS_SUCCESS = 1; // 全局启用

    public static $status = [
        self::GLOBAL_STATUS_SUCCESS => "启用",
        //self::GLOBAL_STATUS_CLOSE => "关闭",
    ];

    const PRIZE_TYPE_VIRTUAL = 1; // 虚拟奖品
    const PRIZE_TYPE_REAL = 2; // 实物奖品

    // 奖品类型
    public static $prize_type = [
        self::PRIZE_TYPE_VIRTUAL => '虚拟奖品',
        self::PRIZE_TYPE_REAL => '实物奖品',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_prizes_base}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name', 'prize_type'], 'required', 'message' => '不能为空'],
            [['name', 'prize_type', 'coupon_id', 'text', 'status', 'image_url'], 'safe'],
            ['name', 'string', 'max' => 64, 'min' => 2],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

}
