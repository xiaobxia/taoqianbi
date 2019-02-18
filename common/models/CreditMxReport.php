<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditMxReport extends  ActiveRecord
{
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const TYPE_CREDIT_EMAIL = 1;  // 信用卡邮箱
    const TYPE_CREDIT_ONLINE_BANK = 2;  // 网银

    /**
 * @inheritdoc
 */
    public static function tableName()
    {
        return '{{%credit_mx_report}}';
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