<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_contacts_record}}".
 */
class UserContactsRecord extends \yii\db\ActiveRecord
{

    // 未上传 
    const STATUS_OFF = 0;
    const STATUS_ON  = 1;
    const STATUS_ERR = 2;

    static $user_contact_status = [
        self::STATUS_OFF => '未上传',
        self::STATUS_ON  => '已上传',
        self::STATUS_ERR => '上传失败',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_contacts_record}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
}