<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class FinancialExpense extends  ActiveRecord
{


    const EXPENSE_COUNTER_FEE = 1;//打款手续费
    const FACE_MONEY = 2;//人脸识别收费
    const ZMOP_MONEY = 3;//芝麻信用收费
    const JXL_MONEY = 4;//聚信力收费
    const TD_MONEY = 5;//同盾收费
    const NOTICE_MONEY = 6;//短信收费
    const EXPENSE_JJP = 7;//拒就陪红包
    const EXPENSE_INVITE = 8;//邀请返现
    const EXPENSE_DEDUCT = 9;//抵扣券
    
    public static function tableName()
    {
        return "{{%financial_expense}}";
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}