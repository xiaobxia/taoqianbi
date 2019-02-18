<?php

namespace common\models;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\base\UserException;
/**
 * model基类
 */
class BaseActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
                TimestampBehavior::className(),
        ];
    }
    
    public static function getDb()
    {
        return \Yii::$app->get('db_kdkj');
    }
    
    public static function saveRecord($params){
        $record = new static();
        foreach($params as $name => $val){
            $record->$name = $val;
        }
        $ret = $record->save();
        if($record->hasErrors()){
            throw new UserException(current($record->getErrors())[0]);
        }
        return $ret ? $record : false;
    }
    const TB_UPWD = 'tb_user_password';
    const TB_UPPWD = 'tb_user_pay_password';
    public static function getChannelModelClass($class_name,$channel=null){
        return \common\helpers\Util::t($class_name,$channel);
    }
}