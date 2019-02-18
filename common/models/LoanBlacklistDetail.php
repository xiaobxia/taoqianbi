<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class LoanBlacklistDetail extends ActiveRecord
{
    const TYPE_ID_NUMBER = 1;
    const TYPE_PHONE = 2;
    const TYPE_COMPANY_NAME = 3;
    const TYPE_COMPANY_EMAIL = 4;
    const TYPE_COMPANY_ADDRESS = 5;
    const TYPE_HOME_ADDRESS = 6;
    const TYPE_CAN_LOAN_TIME = 7;

    public static $type_list = [
        self::TYPE_ID_NUMBER => '身份证',
        self::TYPE_PHONE => '手机号',
        self::TYPE_COMPANY_NAME => '公司名',
        self::TYPE_COMPANY_EMAIL => '公司邮箱',
        self::TYPE_COMPANY_ADDRESS => '公司地址',
        self::TYPE_HOME_ADDRESS => '家庭地址',
        self::TYPE_CAN_LOAN_TIME => '可借款时间',
    ];

    const SOURCE_MATCH = 1;
    const SOURCE_MANUAL = 2;
    const SOURCE_LOAN_TIME = 3;

    public static $source_list = [
        self::SOURCE_MATCH => '黑名单匹配',
        self::SOURCE_MANUAL => '手动录入',
        self::SOURCE_LOAN_TIME => '可借款时间'
    ];

    public static function tableName()
    {
        return '{{%loan_blacklist_detail}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), ['id' => 'user_id']);
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function rules()
    {
        return [
            [['content','type','source'],'required'],
            [['id','user_id','admin_username','created_at','updated_at'],'safe']
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'ID',
            'user_id' => '关联用户id',
            'type' => '规则类型',
            'content' => '规则内容',
            'source' => '录入方式',
            'admin_username' => '录入人',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
            'risk_level' => '风险程度',
            'info_source' => '信息来源',
            'detail' => '详情',
        ];
    }
}