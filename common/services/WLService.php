<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\CurlHelper;
use common\models\CreditJsqbBlacklist;

class WLService extends Component
{
    const TOKEN_URL = 'https://credit.qianchengdata.com/client/authorize/token';
    const QUERY_URL = 'https://credit.qianchengdata.com/v2/blacklist/black-list/search';
    const USERNAME = 'dichangjinrong';
    const USER_PWD = 'D45O6LG1NAXX03GGJI87WE6HGRYXA7CU';

    public static function getToken(){
        $data['username'] = self::USERNAME;
        $data['password'] = self::USER_PWD;
        $res_json = CurlHelper::curlHttp(self::TOKEN_URL,'post',$data);
        $token = isset($res_json['data']['token'])?$res_json['data']['token']:'error';
        if($token == 'error'){
            Yii::error('wl_token_error','wl_token_error');
            return $token;
        }
        return $token;
    }

    /**
     * @name 黑名单查询
     */
    public static function getIsBlack($params){
        $token = self::getToken();
        if($token == 'error'){
            return false;
        }else{
            $data['name'] = $params->name;
            $data['id_card'] = $params->id_number;
            $data['mobile'] = $params->phone;
            $data['token'] = $token;
            $res_arr = CurlHelper::curlHttp(self::QUERY_URL,'post',$data);
            if($res_arr['code'] == 0 &&
                isset($res_arr['data'][0]['is_in']) &&
                $res_arr['data'][0]['is_in'] == 'true' &&
                isset($res_arr['data'][0]['create_time'])
            ){
                CreditJsqbBlacklist::addData($params);
                return true;
            }else{
                return false;
            }
        }
    }
}