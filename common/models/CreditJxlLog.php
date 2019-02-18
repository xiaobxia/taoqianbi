<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditJxlLog extends  ActiveRecord
{


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_jxl_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
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

    public function rules(){
        return [
            [[ 'id','person_id','data'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'person_id' => '借款人id',
            'report_update' => '报表更新时间',
            'data'    => 'json数据',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    public function getLoanPerson(){
        return $this->hasOne(LoanPerson::className(), ['id' => 'person_id']);
    }


}