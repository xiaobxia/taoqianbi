<?php
namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * AdminUserRole model
 */
class AdminLoginLog extends \yii\db\ActiveRecord
{


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_login_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {

    }

}