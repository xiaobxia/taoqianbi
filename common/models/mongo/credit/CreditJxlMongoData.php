<?php
namespace common\models\mongo\credit;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\mongodb\ActiveRecord;

class CreditJxlMongoData extends  ActiveRecord
{

    const STATUS_TURE = 1;//数据生效
    const STATUS_FALSE = 0;//数据失效

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const TYPE_BASE_REPORT = 1;

    public static function collectionName() {
        return 'credit_jxl_mongo_data';
    }

    public static function getDb() {
        return Yii::$app->get('mongodb_user_message');
    }

    public function attributes()
    {
        return [
            '_id', // id
            'status', // 数据是否失效
            'id_number', // 借款人编号
            'token', //  用户报表token
            'person_id', //  借款人id
            'created_at', // 创建时间
            'updated_at', // 更新时间
            'updt',
            'log_id',
            'is_overdue',
            'data',
            'raw_status',
            'raw_data'
        ];
    }

    public static function findLatestOne($params, $dbName = null) {
        $db = empty($dbName) ? self::getDb() : \yii::$app->get($dbName);
        return self::findByCondition($params)->orderBy('id Desc')->one( $db );
    }
}
