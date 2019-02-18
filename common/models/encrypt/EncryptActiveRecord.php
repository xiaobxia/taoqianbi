<?php

namespace common\models\encrypt;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 *

 *
 * base model
 *
 */
class EncryptActiveRecord extends ActiveRecord{

    const STATUS_ACTIVE = 0;
    const STATUS_DELETED = 1;

    const STATE_USABLE = 0;
    const STATE_DISABLE = 1;

    static $label_state = [
        self::STATE_USABLE  => '启用',
        self::STATE_DISABLE => '停用',
    ];

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

    /**
     * @override 逻辑删除
     */
    public function delete() {
        $result = false;
        if($this->beforeDelete()) {
            $this->status = self::STATUS_DELETE;
            $result = $this->save();
            if($result) {
                $this->afterDelete();
            }
        }

        return $result;
    }

    public static function getDb(){
        return Yii::$app->get('db_kdkj');
    }

}
