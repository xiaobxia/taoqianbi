<?php

namespace common\models\message;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\NotFoundHttpException;
/**
 * This is the model class for table "{{%message_log}}".
 */
class MessageLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%message_log}}';
    }

    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * 用户查看消息详情，更新阅读状态
     * @param  integer $message_id 通知消息ID
     */
    public static function update_read_user($message_id){
        $read_user  = Yii::$app->user->identity->username;
        $findMessageLog = self::find()->where(['read_user' => $read_user, 'message_id' => $message_id])->one(self::getDb_rd());
        $message_log_model = new MessageLog();
        if (!$findMessageLog) {
            $message_log_model->read_user    = $read_user;
            $message_log_model->message_id   = $message_id;
            $message_log_model->read_status  = 1;
            $message_log_model->read_time    = time();
            $message_log_model->save();
        }else{
            $message_log_model = self::findOne($findMessageLog->message_log_id);
            $message_log_model->read_time    = time();
            $message_log_model->save();
        }
    }

    public static function getMessageLogList(){
        $read_user = Yii::$app->user->identity->username;
        $findMessageLogByUsername = self::find()->where(['read_user' => $read_user])->asArray()->all(self::getDb_rd());
        return $findMessageLogByUsername;
    }
}
