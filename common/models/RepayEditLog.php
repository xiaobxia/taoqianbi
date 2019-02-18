<?php

/**
 * Created by phpDesigner.
 * User: user
 * Date: 2017/1/12
 * Time: 10:30
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class RepayEditLog extends ActiveRecord {
    
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%repay_edit_log}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

}
