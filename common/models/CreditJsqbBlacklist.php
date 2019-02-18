<?php
/**
 * Created by PhpStorm.
 * User: leishuanghe
 * Date: 2017/9/14
 * Time: 17:55
 */

namespace common\models;


use Yii;
use yii\db\ActiveRecord;

/**
 * Class CreditJsqbBlacklist
 * @package common\models
 * @property integer $id
 * @property integer $user_id
 * @property integer $is_in
 * @property integer $created_at
 * @property integer $updated_at
 */
class CreditJsqbBlacklist extends ActiveRecord
{
    const VALID_TIME = 86400 * 7;//有效时间

    const IN_BLACKLIST = 1;
    const NOT_IN_BLACKLIST = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_jsqb_blacklist}}';
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

    /**
     * 获取最新一条记录
     * @param $params
     * @param null $dbName
     * @return array|null|ActiveRecord
     */
    public static function findLatestOne($params, $dbName = null)
    {
        if (is_null($dbName)) {
            $record = self::find()->where($params)->orderBy('id DESC')->one();
        } else {
            $record = self::find()->where($params)->orderBy('id DESC')->one(Yii::$app->get($dbName));
        }

        return $record;
    }

    /**
     * @name 添加一条记录
     */
    public static function addData($params){
        $data = self::find()->where(['user_id'=>$params->id])->one();
        if(!$data){
            $b_list = new CreditJsqbBlacklist();
            $b_list->user_id = $params->id;
            $b_list->is_in = 1;
            $b_list->created_at = time();
            $b_list->updated_at = time();
            if($b_list->save()){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }


    public function rules()
    {
        return [
            [['id', 'user_id', 'is_in', 'created_at', 'updated_at'], 'safe'],
        ];
    }
}