<?php

namespace common\models\credit_line;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditLineActiveRecord extends ActiveRecord{

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