<?php

namespace common\services;

use yii\base\Component;
use common\helpers\CurlHelper;

class RealNameYouFenAuthService extends Component
{

    public $post_url = 'https://api.acedata.com.cn:2443/oreo/personal/validation/name/idcard';
    protected $loginName = 'dichang123';

    /**
     * 实名验证
     * @param $name
     * @param $id_number
     */
    public function realNameAuth($name,$id_number){
        try{
            $url = $this->post_url;
            $post_data = [
                'account'=> $this->loginName,
                'name' => $name,
                'idcard' => $id_number,
            ];
            $result = CurlHelper::curlHttp($url, 'GET',$post_data);
            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }


}