<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditHouseFundLog extends  ActiveRecord
{


    public static function tableName()
    {
        return '{{%credit_house_fund_log}}';
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

    public function getLoanPerson(){
        return $this->hasOne(LoanPerson::className(), ['id' => 'person_id']);
    }


}