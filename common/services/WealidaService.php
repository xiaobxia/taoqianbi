<?php

namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\ErrorMessage;
use common\helpers\CurlHelper;

/**
 *
 * @author Shayne Song
 * @function Get Wealida's access token.
 * @date 2017-01-09
 *
 */
class WealidaService extends Component
{
    public function getToken($username, $password, $token_hours = 0, $force = false){
        $token = "WealidaToken".$username.$password;
        $redis = Yii::$app->redis;
        if($force){
            if(($token_hours * 3600 - 1800) > 0){
                $validity = $token_hours * 3600 - 1800;
            } else {
                $validity = 24 * 3600 - 1800;
                $token_hours = 24;
            }
            $result = $this->updateToken($username, $password, $token_hours);
            if($result['code'] == 0){
                $redis->setex($token, $validity, $result['token']);
            }
            return $result;
        }else{
            if(!$redis->exists($token)){
                if(($token_hours * 3600 - 1800) > 0){
                    $validity = $token_hours * 3600 - 1800;
                } else {
                    $validity = 24 * 3600 - 1800;
                    $token_hours = 24;
                }
                $result = $this->updateToken($username, $password, $token_hours);
                if($result['code'] == 0){
                    $redis->setex($token, $validity, $result['token']);
                }
                return $result;
            } else {
                return [
                    'code' => 0,
                    'token' => $redis->get($token),
                    'message' => 'get token from redis',
                ];
            }
        }
    }

    private function updateToken($username, $password, $token_hours){
        $url = "https://credit.wealida.com/client/authorize/token";
//         $url = "https://credit-test.wealida.com/client/authorize/token";
        $param = [
            'username' => $username,
            'password' => $password,
            'hours' => $token_hours,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $param, 300);

        \Yii::info(var_export($result, true));

        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'token' => $result['data']['token'],
                    'message' => 'Wealida token获取成功',
                ];
            }

            if(!isset($result['msg'])){
                return [
                    'code' => $result['code'],
                ];
            }else{
                return [
                    'code' => $result['code'],
                    'message' =>$result['msg'],
                ];
            }
        }else{

            return [
                'code' => -1,
                'message' => 'Wealida token获取失败，请求发送失败',
            ];
        }
    }
}