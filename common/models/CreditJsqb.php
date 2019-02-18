<?php
namespace common\models;

use common\soa\KoudaiSoa;
use Yii;
use yii\db\ActiveRecord;

class CreditJsqb extends ActiveRecord
{
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const VALID_TIME = 86400 * 7;//有效时间

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_jsqb}}';
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
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public static function findLatestOne($params, $dbName = null)
    {
        if (is_null($dbName)) {
            $creditMg = self::find()->where($params)->orderBy('id DESC')->one();
        } else {
            $creditMg = self::find()->where($params)->orderBy('id DESC')->one(Yii::$app->get($dbName));
        }

//        if ($creditMg && (time() > $creditMg->updated_at + self::VALID_TIME)) {
//            $soa_client = KoudaiSoa::instance("Loaner");
//            $loanPerson = LoanPerson::findOne($creditMg->person_id);
//            $isWhite = $soa_client->isWhitelist($loanPerson->phone,$loanPerson->id_number);
//            $isBlack = $soa_client->isBlacklist($loanPerson->phone,$loanPerson->id_number);
//            $creditMg->is_white = $isWhite['data']['is_whitelist'] ?? 0;
//            $creditMg->is_black = $isBlack['data']['is_blacklist'] ?? 0;
//            $creditMg->updated_at = time();
//            $creditMg->save();
//        }

        return $creditMg;
    }


    public function rules()
    {
        return [
            [['id', 'person_id', 'is_white', 'is_black', 'created_at', 'updated_at', 'is_overdue'], 'safe'],
        ];
    }

}