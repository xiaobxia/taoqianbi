<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditSauron extends ActiveRecord
{
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_sauron}}';
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

    public static function findLatestOne($params, $dbName = null)
    {
        return self::find()->where($params)->orderBy('id Desc')->one();
       /* if (is_null($dbName))
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditMg;*/
    }


    public function rules()
    {
        return [
            [['id', 'person_id', 'data', 'update_time', 'created_at', 'updated_at', 'is_overdue'], 'safe'],
        ];
    }

}