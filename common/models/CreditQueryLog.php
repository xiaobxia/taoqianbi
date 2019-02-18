<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;


class CreditQueryLog extends  ActiveRecord
{
    const Credit_ZZC = 1;
    const Credit_YXZC = 2;
    const Credit_HD = 3;
    const Credit_YD = 4;
    const Credit_BQS = 5;

    const Credit_SH = 6;
    const Credit_JXL = 7;
    const Credit_ZMOP = 8;
    const Credit_ZS = 9;
    const Credit_JY = 10;
    const Credit_YYS = 11;

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    public static $credit_list = [
        self::Credit_ZZC => '中智诚',
        self::Credit_YXZC => '宜信',
        self::Credit_HD => '华道',
        self::Credit_YD => '有盾',
        self::Credit_BQS => '白骑士',
        self::Credit_SH => '算话',
        self::Credit_JXL => '聚信立',
        self::Credit_ZMOP => '芝麻信用',
        self::Credit_ZS => '甄视科技',
        self::Credit_JY => '91征信',
        self::Credit_YYS => '葫芦金融'
    ];

    const PRODUCT_ASSET_PARTHER = 1;
    const PRODUCT_YGD = 2;

    public static $product = [
        self::PRODUCT_ASSET_PARTHER => '合作资产',
        self::PRODUCT_YGD => '小钱包'
    ];

    public static $product_pre = [
        self::PRODUCT_ASSET_PARTHER => 'A',
        self::PRODUCT_YGD => 'Y'
    ];

    public static function tableName()
    {
        return '{{%credit_query_log}}';
    }


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

    public static function getCreditType($credit_id,$type){
        $type_name = '';
        switch ($credit_id){
            case self::Credit_ZZC:
                $type_name = CreditZzc::$type_list[$type];
                break;
            case self::Credit_YXZC:
                $type_name = CreditYxzc::$type_list[$type];
                break;
            case self::Credit_HD:
                $type_name = CreditHd::$type_list[$type];
                break;
        }
        return $type_name;
    }

    public function getLoanPerson(){
        return $this->hasOne(LoanPerson::className(), ['id' => 'person_id']);
    }

    public static function findLatestOne($params,$dbName = null)
    {
        if(is_null($dbName))
            $creditQueryLog = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditQueryLog = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditQueryLog;
    }
}
