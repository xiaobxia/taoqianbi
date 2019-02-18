<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditMxBill extends  ActiveRecord
{
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const TYPE_CREDIT_EMAIL_ALL = 1;  // 信用卡邮箱全部账单
    const TYPE_CREDIT_ONLINE_BANK = 2;  // 网银

//1:一手账单,2:疑似一手账单,3:疑似假账单,-1:邮件来源未知,-2:发件人无效,-3:IP无效,4:异常
    public static $original = [
        1 => '一手账单',
        2 => '疑似一手账单',
        3 => '疑似假账单',
        -1 => '邮件来源未知',
        -2 => '发件人无效',
        -3 => 'IP无效',
        4 => '异常',
    ];

    /**
     * @inheritdoc
    */
    public static function tableName()
    {
        return '{{%credit_mx_bill}}';
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

    public static function findLatestOne($params,$dbName = null)
    {
        if(is_null($dbName))
            $creditMx = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditMx = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditMx;
    }


    public function rules(){
        return [
            [[ 'id', 'user_id', 'type', 'data', 'created_at', 'updated_at', 'is_overdue'], 'safe'],
        ];
    }

}