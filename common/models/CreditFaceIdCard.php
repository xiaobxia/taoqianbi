<?php
/**
 * Created by PhpStorm.
 * User: leishuanghe
 * Date: 2017/7/5
 * Time: 16:30
 */

namespace common\models;


use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Face++ 身份证识别结果
 * @property integer $id
 * @property integer $user_id
 * @property integer $type //类型（正反面）
 * @property string $data
 * @property integer $created_at
 * @property integer $updated_at
 */
class CreditFaceIdCard extends ActiveRecord
{
    public static function tableName()
    {
        return "{{%credit_face_id_card}}";
    }


    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    const TYPE_FRONT = 1; //正面
    const TYPE_BACK = 2; //反面

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * 获取最新一条记录
     * @param $condition
     * @param null $dbName
     * @return array|null|ActiveRecord
     */
    public static function findLatestOne($condition, $dbName = null)
    {
        if(is_null($dbName)) {
            return self::find()->where($condition)->orderBy('id desc')->one();
        } else {
            return self::find()->where($condition)->orderBy('id desc')->one(Yii::$app->get($dbName));
        }
    }
}