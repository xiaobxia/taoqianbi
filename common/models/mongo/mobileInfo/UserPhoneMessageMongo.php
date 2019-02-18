<?php
namespace common\models\mongo\mobileInfo;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 *

 *
 * UserPhoneMessageMongo model
 *
 */
class UserPhoneMessageMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb_user_message');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'user_phone_message';
    }

    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'message_content',
            'message_date',
            'phone',
            'clientType',
            'osVersion',
            'appVersion',
            'deviceName',
            'appMarket',
            'deviceId',
            'created_at'
        ];
    }

    public static function addPhoneMessage($data){
        $message = self::find()->where([
                    'user_id' => $data['user_id'],
                    'message_content' => $data['message_content'],
                    'message_date' => $data['message_date'],
                    'phone' => $data['phone']
                ])->one();

        if (!empty($message)) {
            return false;
        }

        $message = self::find()->where([
                    'deviceId' => $data['deviceId'],
                    'message_content' => $data['message_content'],
                    'message_date' => $data['message_date'],
                    'phone' => $data['phone']
                ])->one();

        if (!empty($message)) {
            return false;
        }

        $message = new self();
        $message->user_id = $data['user_id'];
        $message->message_content = $data['message_content'];
        $message->message_date = $data['message_date'];
        $message->phone = $data['phone'];
        $message->clientType = $data['clientType'];
        $message->osVersion = $data['osVersion'];
        $message->appVersion = $data['appVersion'];
        $message->deviceName = $data['deviceName'];
        $message->appMarket = $data['appMarket'];
        $message->deviceId = $data['deviceId'];
        $message->created_at = time();
        return $message->save();
    }
}
