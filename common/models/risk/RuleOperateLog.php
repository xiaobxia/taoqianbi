<?php
namespace common\models\risk;
use Yii;


class RuleOperateLog extends MActiveRecord{

    //启用与停用
    const OPERATE_USE = 0;
    const OPERATE_DISUSE = 1;
    const OPERATE_DEBUG = 2;
    const OPERATE_ADD = 3;
    const OPERATE_UPDATE = 4;
    const OPERATE_EXTEND_ADD = 5;
    const OPERATE_EXTEND_UPDATE = 6;
    const OPERATE_EXTEND_USE = 7;
    const OPERATE_EXTEND_DISUSE = 8;
    const OPERATE_EXTEND_DELETE = 9;

    static $label_state = [
        self::OPERATE_USE  => '启用特征',
        self::OPERATE_DISUSE => '停用特征',
        self::OPERATE_DEBUG   => '调试特征',
        self::OPERATE_ADD =>'新建特征',
        self::OPERATE_UPDATE =>'修改特征',
        self::OPERATE_EXTEND_ADD =>'添加映射',
        self::OPERATE_EXTEND_UPDATE =>'修改映射',
        self::OPERATE_EXTEND_USE =>'启用映射',
        self::OPERATE_EXTEND_DISUSE =>'停用映射',
        self::OPERATE_EXTEND_DELETE =>'调试映射',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule_operate_log}}';
    }
    
    public function rules()
    {
        return [
            [['rule_id', 'user_id', 'operate', 'status'], 'integer'],
            [['remark','create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'rule_id' => Yii::t('app', '特征id'),
            'user_id' => Yii::t('app', '操作者id'),
            'operate' => Yii::t('app', '操作'),
            'remark' => Yii::t('app', '备注'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '存储状态 0 可用 1删除'),
        ];
    }
}


?>