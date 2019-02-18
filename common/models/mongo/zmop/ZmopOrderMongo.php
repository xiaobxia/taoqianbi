<?php
namespace common\models\mongo\zmop;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\mongodb\ActiveRecord;

class ZmopOrderMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb_user_message');
    }

    public static function collectionName(){
        return 'zmop_order_mongo';
    }

    public function rules(){
        return [
            [['person_id', 'zmop_id','created_at', 'updated_at'], 'safe']
        ];
    }

    public function attributes(){
        return [
            '_id',
            'zmop_id',
            'order_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            TimestampBehavior::className(),
        ];
    }

    public static function add($zmop_id, $order_id) {
        $zmop = self::findOne(['zmop_id' => $zmop_id, 'order_id' => $order_id]);
        if (!$zmop) {
            $zmop = new self(['zmop_id' => $zmop_id, 'order_id' => $order_id]);
        }

        return $zmop->save();
    }

    public static function findLatestOne($params,$dbName = null) {
        $db = $dbName ? Yii::$app->get($dbName) : self::getDb();
        return self::findByCondition($params)->orderBy('id Desc')->one($db);
    }

}
