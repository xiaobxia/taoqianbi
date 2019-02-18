<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/3
 * Time: 11:04
 */
namespace common\models;
use yii;
use yii\db\ActiveRecord;
class DeductMoneyLog extends BaseActiveRecord{

    public static $platforms = array(
        BankConfig::PLATFORM_UMPAY => "联动优势",
        BankConfig::PLATFORM_YEEPAY => "易宝支付",
    );

    public static function tableName(){
        return '{{%deduct_money_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public static function saveRecord($financialDebitRecord,$money=0){
        try{
            $params = [
                'user_id' => $financialDebitRecord['user_id'],
                'order_id' => $financialDebitRecord['loan_record_id'],
                'order_uuid' => $financialDebitRecord['order_id'],
                'platform' => $financialDebitRecord['platform'],
                'money' => $money,
                'debit_name' => $financialDebitRecord['admin_username'],
                'card_id' => $financialDebitRecord['debit_card_id'],
            ];
            return parent::saveRecord($params);
        }catch(\Exception $e){
        }
        return false;
    }
}