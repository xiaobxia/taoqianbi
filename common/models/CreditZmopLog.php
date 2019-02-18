<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditZmopLog extends  ActiveRecord
{
    /**
     * @inheritdoc
     */
    //芝麻信用项目代码
    const PRODUCT_ZM = '1';
    const PRODUCT_RAIN = '2';
    const PRODUCT_WATCH = '3';
    const PRODUCT_IVS = '4';
    const PRODUCT_DAS = '5';

    public static $prodcut_list = [
        self::PRODUCT_ZM => '芝麻积分',
        self::PRODUCT_RAIN => 'rain积分',
        self::PRODUCT_WATCH => '行业关注',
        self::PRODUCT_IVS => 'ivs积分',
        self::PRODUCT_DAS =>'das信息',
    ];


    public static function tableName()
    {
        return '{{%credit_zmop_log}}';
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