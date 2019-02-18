<?php
namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "tb_capital".
 */
class Capital extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%capital}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required',],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '记录ID',
            'name' => '资方名字',
            'created_at' => '创建时间',
            'add_name' => '添加人名字',
        ];
    }

    public static function getnamebyid($id){
        $capital = self::findOne($id);
        if($capital)
            return $capital['name'];
        else
            return '';
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

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}