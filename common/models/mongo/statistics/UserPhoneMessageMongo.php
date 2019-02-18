<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

class UserPhoneMessageMongo extends ActiveRecord {

    public static function getDb(){
        return Yii::$app->get('mongodb_user_message');
    }
    
    public static function collectionName(){
        return 'user_phone_message';
    }

    public function attributes(){
        return [
            '_id',
            'user_id',              //用户id
            'message_content',      //短信内容
            'phone',                //短信发送手机号
            'message_date',         //短信发送日期
            'clientType',
            'osVersion',
            'appVersion',
            'deviceName',
            'created_at',
            'appMarket',            //应用市场
            'deviceId'              //设备码
        ];
    }

    /**
     * 新增数据
     * @param int $user_id
     * @param int $deviceId
     * @param string $messageContent
     * @param string $messageDate
     * @param string $phone
     * @param array $data
     */
    public static function addData($user_id, $deviceId, $messageContent, $messageDate, $phone, $data = []){
        if($user_id){
            $_id = trim($user_id) . '_' . trim($messageContent) . '_' . trim($messageDate) . '_' . trim($phone);
        }else{
            $_id = trim($deviceId) . '_' . trim($messageContent) . '_' . trim($messageDate) . '_' . trim($phone);
        }
        $_id = md5($_id);
        
        $model = self::findOne(['_id' => $_id]);
        if (!$model) {
            $model = new self(['_id' => $_id]);
            $model->created_at = date('Y-m-d H:i:s');
        }
        $model->user_id = (string)$user_id;
        $model->deviceId = trim($deviceId);
        $model->message_content = trim($messageContent);
        $model->message_date = trim($messageDate);
        $model->phone = trim($phone);
        $model->created_at = date('Y-m-d H:i:s');
    
        $fieldArr = [
            'clientType',
            'osVersion',
            'appVersion',
            'deviceName',
            'appMarket', 
        ];
        foreach ($data as $key => $item) {
            if (in_array($key, $fieldArr)) {
                $model->$key = $item;
            }
        }
        $res = $model->save();
        if ($res) {
            return $_id;
        } else {
            Yii::error("SAVE user_phone_message_mongo ERROR. user_id: {$user_id}, deviceId:{$deviceId}, data:". var_export($data, true));
            return false;
        }
    }
}
