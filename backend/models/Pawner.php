<?php
namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "tb_pawner".
 */
class Pawner extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%pawner}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name','capital_id'], 'required',],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '记录ID',
            'capital_id' => '资方',
            'name' => '抵押人名字',
            'created_at' => '创建时间',
            'add_name' => '添加人名字',
        ];
    }

    public static function getnamebyid($id){
        $pawner = self::findOne($id);
        if($pawner)
            return $pawner['name'];
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