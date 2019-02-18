<?php
namespace common\models\risk;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 *

 *
 * base model
 *
 */
class MActiveRecord extends ActiveRecord{

    const STATUS_NORMAL = 0;
    const STATUS_ACTIVE = 0;
    const STATUS_DELETED = 1;

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
                'value' => function() {
                    return date('Y-m-d H:i:s');
                }
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }

    // 重写delete方法
    public function delete(){
        if ($this->status == self::STATUS_ACTIVE) {
            $this->status = self::STATUS_DELETED;
            return $this->save();
        }
        return false;
    }

    public static function getDb(){
        return Yii::$app->get('db_kdkj');
    }

}
