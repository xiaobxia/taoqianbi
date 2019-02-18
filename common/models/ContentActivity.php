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

class ContentActivity extends ActiveRecord {
    const STATUS_FALSE   = 0;
    const STATUS_EDIT    = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_TIMEOUT = 3;
    const STATUS_SHELF   = 4;
    const STATUS_DELETE  = -1;

    public static $status = [
        self::STATUS_EDIT    => "草稿",
        self::STATUS_SUCCESS => "已发布",
        self::STATUS_TIMEOUT => "已结束",
        self::STATUS_SHELF   => "已下架",
        self::STATUS_DELETE  => "已删除",
    ];

    const CASE_TYPE_BANNER   = 1;
    const CASE_TYPE_NOTICE   = 2;

    public static $use_case = [
        self::CASE_TYPE_BANNER => "活动公告", // "banner",
        self::CASE_TYPE_NOTICE => "系统公告", // "系统通知",
    ];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%message_activity}}';
    }

    public static function getDb() {
        return Yii::$app->get('db');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'use_case','status','start_time','end_time'], 'required'],
            [ ['subtitle','link','banner','content','user_admin','count','remark'] ,'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * 获取热点消息的发布时间
     */
    public static function getHotMessage() {
        $condition = sprintf(" status=%s ",static::STATUS_SUCCESS);
        $item = self::find()->where($condition)->orderBy('id desc')->one();
        if ($item) {
            return $item->created_at;
        }

        return 0;
    }
}
