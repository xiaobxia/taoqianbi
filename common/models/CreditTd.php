<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditTd extends ActiveRecord {
    const STATUS_0 = 0; //未获取
    const STATUS_1 = 1; //已提交
    const STATUS_2 = 2; //已获取
    const STATUS_3 = 3; //获取失败

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    public static function tableName() {
        return 'tb_credit_td';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb() {
        return Yii::$app->get('db_kdkj_risk');
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    public static function findLatestOne($params,$dbName = null) {
        if(is_null($dbName))
            $creditTd = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditTd = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));

        return $creditTd;
    }
}
