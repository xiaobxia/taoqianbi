<?php
namespace common\models\risk;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class RuleVersion extends ActiveRecord{

    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;
    const STATUS_DEBUG = 2;

    static $status_desc = [
        self::STATUS_DISABLE => '不可用',
        self::STATUS_ENABLE => '可用',
        self::STATUS_DEBUG => '调试',
    ];

    public static function tableName()
    {
        return '{{%rule_version}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', '版本名称'),
            'remark' => Yii::t('app', '备注'),
            'pkgname' => Yii::t('app','APP包名'),
            'created_by' => Yii::t('app', '创建者'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '状态 0：不可用 1：可用 2：调试'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'remark', 'pkgname','created_by', 'status'], 'required', 'message' => '不能为空'],
            ['status', 'default', 'value' => 1],
        ];
    }
}
