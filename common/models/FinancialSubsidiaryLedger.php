<?php
/**
 * Created by phpDesigner.
 * User: user
 * Date: 2016/1109
 * Time: 15:30
 */
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;


class FinancialSubsidiaryLedger extends ActiveRecord
{

    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%financial_subsidiary_ledger}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
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
}