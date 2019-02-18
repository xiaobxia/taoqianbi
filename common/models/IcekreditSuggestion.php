<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 冰鉴评分
 * @property integer $id
 * @property integer $user_id
 * @property string $suggestion
 * @property integer $comm_score
 * @property integer $comm_cutoff
 * @property integer $alipay_score
 * @property integer $alipay_cutoff
 * @property string $hit_details
 * @property integer $amount
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class IcekreditSuggestion extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%icekredit_suggestion}}';
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

    public static function findLatestOne($params, $dbName = null)
    {
        if (is_null($dbName))
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditMg;
    }

}