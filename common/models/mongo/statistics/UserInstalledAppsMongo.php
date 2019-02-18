<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

class UserInstalledAppsMongo extends ActiveRecord {

    public static function getDb(){
        return Yii::$app->get('mongodb_user_message');
    }
    
    public static function collectionName(){
        return 'user_installed_apps';
    }

    public function attributes(){
        return [
            '_id',
            'user_id',              //用户id
            'app_name',             //app名称
            'package_name',         //app包名
            'version_code',         //app版本
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
     * @param int $user_id          //用户id
     * @param string $deviceId      //设备号
     * @param string $packageName   //包名称
     * @param array $data           //其他参数
     * @return \common\models\mongo\statistics\UserPhoneMessageMongo|boolean
     */
    public static function addData($user_id, $deviceId, $packageName, $data = []){
        $_id = trim($user_id) . '_' . trim($deviceId) . '_' . $packageName;
        $model = self::findOne(['_id' => $_id]);
        if (!$model) {
            $model = new self(['_id' => $_id]);
            $model->created_at = date('Y-m-d H:i:s');
        }
        $model->user_id = (string)$user_id;
        $model->deviceId = trim($deviceId);
        $model->package_name = trim($packageName);
        $model->created_at = date('Y-m-d H:i:s');
        
        $fieldArr = [
            'app_name', 
            'version_code',
            'clientType',
            'osVersion',
            'appVersion',
            'deviceName',
            'appMarket'
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
            Yii::error("SAVE user_installed_apps_mongo ERROR. user_id: {$user_id}, deviceId:{$deviceId}, data:". var_export($data, true));
            return false;
        }
    }
}
