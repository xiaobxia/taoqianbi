<?php
/**
 * Created by PhpStorm.
 * User: byl
 * Date: 2017/3/3
 * Time: 19:01
 */
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class CreditBrLog extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%credit_br_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }



    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
     * @inheritdoc
     */
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function rules(){
        return [
            [[ 'id','person_id','admin_username','price','type','created_at','updated_at'], 'safe'],
        ];
    }
}