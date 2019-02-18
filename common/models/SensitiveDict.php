<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class SensitiveDict extends ActiveRecord {
    
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%sensitive_dict}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    // /**
    //  * @inheritdoc
    //  */
    // public function behaviors() {
    //     return [
    //         TimestampBehavior::className(),
    //     ];
    // }

}
