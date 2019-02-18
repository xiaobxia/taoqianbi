<?php
namespace common\models;

use yii\behaviors\TimestampBehavior;
use Yii;
/**
 * BlackList model
 * This is the model class for table "{{%black_list}}".
 *
 */
class BlackList extends \yii\db\ActiveRecord
{
    const TYPE_VISIT = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%black_list}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
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
    
    /**
     * 增加次数
     * @return boolean
     */
    public static function addCount($value, $type)
    {
        $model = self::findOne(['svalue' => $value, 'type' => $type]);
        if ($model) {
            $model->count += 1;
        } else {
            $model = new BlackList();
            $model->svalue = $value;
            $model->count = 1;
            $model->type = $type;
        }
        return $model->save();
    }
}