<?php

namespace common\models\info;

use Yii;

use common\models\encrypt\RsaEncrypt;
use common\models\encrypt\DeviceMappingKeys;

class InfoCaptureUtil
{
    public static function initConfig($deviceId){

        $rsaEncrypt = new RsaEncrypt();

        $deviceMappingKeys = DeviceMappingKeys::findByImei($deviceId);

        if(empty($deviceMappingKeys) || 
            date('Y-m-d H:i:s', strtotime('+3 day', strtotime($deviceMappingKeys->start_time))) <= date('Y-m-d H:i:s')){

            $hash = $rsaEncrypt->BKDRHash($deviceId);
            $keys = $rsaEncrypt->getKeysByHash($hash);
            if(empty($keys)){
                self::errorMessage("数据采集模块初始化失败:获取密钥异常");
            }else{
                if(empty($deviceMappingKeys)){
                    $deviceMappingKeys = new DeviceMappingKeys();
                    $deviceMappingKeys->device_imei = $deviceId;
                }
                $deviceMappingKeys->encrypt_key_id = $keys['id'];
                $deviceMappingKeys->start_time = date('Y-m-d H:i:s');
                $deviceMappingKeys->save();
            }
        }else{
            $keys = $rsaEncrypt->getKeysById($deviceMappingKeys->encrypt_key_id);
        }

        $private_key = $keys['private_key'];
        $public_key = $keys['public_key'];

        $private_key_content = $keys['private_key_content'];
        $public_key_content = $keys['public_key_content'];

        $symbols = self::symbols();

        $script_path = dirname(__FILE__)."/../../../credit/web/js/info_capture/info_capture.js";

        $script_content = file_get_contents($script_path);

        foreach ($symbols as $key => $value) {
            $script_content = str_replace($key, $value, $script_content);
        }

        $encryptJS = $rsaEncrypt->encrypt($private_key, RsaEncrypt::ENCRYPT_TYPE_PRIVATE_KEY, RsaEncrypt::ENCRYPT_SEGMENT_BITS_2048, $script_content);
        $symbols = $rsaEncrypt->encrypt($private_key, RsaEncrypt::ENCRYPT_TYPE_PRIVATE_KEY, RsaEncrypt::ENCRYPT_SEGMENT_BITS_2048, json_encode($symbols));

        // $private_key_content = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $private_key_content);
        // $private_key_content = str_replace("-----END RSA PRIVATE KEY-----", "", $private_key_content);
        $public_key_content = str_replace("-----BEGIN PUBLIC KEY-----", "", $public_key_content);
        $public_key_content = str_replace("-----END PUBLIC KEY-----", "", $public_key_content);

        //$js = RsaHelper::decrypt($private_key, RsaHelper::DECRYPT_TYPE_PRIVATE_KEY, RsaHelper::DECRYPT_SEGMENT_BITS_2048, $encryptJS);

        return $public_key_content."*/y2H1Aq*/".$encryptJS."*/y2H1Aq*/".$symbols;
    }

    private static function symbols(){

        $symbols = ['nativeMethod'];

        $count = count($symbols);

        $result = [];

        for($i = 0; $i < $count; $i++){

            $length = rand(5, 10);

            $variable = chr(ord('A') + rand(0, 25));

            for($j = 0; $j < $length; $j++){

                if(rand(1, 20) % 2 == 0){
                    $variable .= rand(0, 9);
                }else{
                    $index = (rand(0, 9) % 2 == 0)?'a':'A';
                    $variable .= chr(ord($index) + rand(0, 25));
                }
            }

            $result[$symbols[$i]] = $variable;
        }

        return $result;
    }

    private static function errorMessage($msg){

        Yii::$app->uniform_alarm->send([
            "markerId" => 29,
            "content" => $msg,
        ]);

        $info = [
            'code' => -1,
            'message' => $msg,//ios
            'data' => $msg,//android
        ];
        return json_encode($info);
    }
}