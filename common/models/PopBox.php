<?php

/**
 * Created by PhpStorm.
 * User: lijia
 * Date: 16-11-1
 * Time: 12:00
 */

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

class PopBox extends \yii\db\ActiveRecord {

    // 任务状态
    const STATUS_INIT = 0; // 初始
    const STATUS_SUCC = 1; // 可弹出状态
    const STATUS_FAIL = 2; // 失效状态

    public static $statusDesc = array(
        self::STATUS_INIT => "初始",
        self::STATUS_SUCC => "可弹出状态",
        self::STATUS_FAIL => "失效状态",
    );

    // 跳转类型
    const ACTION_NONE = 0; // 无触发动作
    const ACTION_NAT = 1; // 跳转原生页面
    const ACTION_H5 = 2; // 跳转H5页面

    public static $actionDesc = array(
        self::ACTION_NONE => "无触发动作",
        self::ACTION_NAT => "跳转原生页面",
        self::ACTION_H5 => "跳转H5页面",
    );

    // 显示位置
    const SHOW_SITE_ONE = 1;
    const SHOW_SITE_TWO = 2;
    const SHOW_SITE_THREE = 3;
    const SHOW_SITE_FOUR  = 4;
    const SHOW_SITE_FIVE  = 5;
    const SHOW_SITE_SIX   = 6;

    public static $showSite = array(
        self::SHOW_SITE_ONE => "首页弹窗",
        self::SHOW_SITE_TWO => "启动弹窗",
        self::SHOW_SITE_THREE => "启动悬浮图标",
        self::SHOW_SITE_FOUR  => "认证悬浮图标",
        self::SHOW_SITE_FIVE  => "首页拉新弹窗",
        self::SHOW_SITE_SIX   => "首页拉新分渠道(H5-SDZJ)",
    );

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%pop_box}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['img_url'], 'required', 'message' => '不能为空'],
            [['img_url'], 'string', 'max' => 65535],
            [['status', 'expect_time', 'expire_time', 'action_url', 'action_type', 'remark', 'show_site','top', 'loan_search_public_list_id', 'source_id'], 'safe'],
        ];
    }

    /**
     * 活动期间显示且状态 为 发送状态的
     */
    public static function isCanTotalShowPop($show_site=1){
        $now = time();
        $condition = sprintf(" status=%s AND show_site=%s AND expect_time <= %s AND expire_time > %s ",PopBox::STATUS_SUCC,$show_site,$now,$now);
        return static::find()->where($condition)->count();
    }

    public static function getTopShowPop($show_site=1){
        $now = time();
        $condition = sprintf(" status=%s AND show_site=%s AND expect_time <= %s AND expire_time > %s ",PopBox::STATUS_SUCC,$show_site,$now,$now);
        return static::find()->where($condition)->orderBy('id desc')->limit(1)->one();
    }

    

}
