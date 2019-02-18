<?php
namespace common\models\mongo\mobileInfo;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 *

 *
 * UserInstalledAppsMongo model
 *
 */
class UserInstalledAppsMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb_user_message');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'user_installed_apps';
    }

    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'app_name',
            'package_name',
            'version_code',
            'clientType',
            'osVersion',
            'appVersion',
            'deviceName',
            'appMarket',
            'deviceId',
            'created_at'
        ];
    }

    public static function addInstalledApp($data){
        $message = self::find()->where([
                    'user_id' => $data['user_id'],
                    'package_name' => $data['package_name'],
                ])->one();

        if (!empty($message)) {
            return false;
        }

        $message = self::find()->where([
                    'deviceId' => $data['deviceId'],
                    'package_name' => $data['package_name'],
                ])->one();

        if (!empty($message)) {
            return false;
        }

        $message = new self();
        $message->user_id = $data['user_id'];
        $message->app_name = $data['app_name'];
        $message->package_name = $data['package_name'];
        $message->version_code = $data['version_code'];
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
