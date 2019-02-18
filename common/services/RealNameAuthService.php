<?php

namespace common\services;

use yii\base\Component;
use common\helpers\CurlHelper;

class RealNameAuthService extends Component
{

    public $post_url = 'https://www.miniscores.cn:8313/CreditFunc/v2.1/IDNameCheck';
    protected $loginName = 'shandianhebao';
    protected $pwd = 'shandianhebao0313';
    protected $serviceName = 'IDNameCheck';

    /**
     * 实名验证
     * @param $name
     * @param $id_number
     */
    public function realNameAuth($name,$id_number){
        try{
            $url = $this->post_url;
            $post_data = [
                'loginName'=> $this->loginName,
                'pwd'=> $this->pwd,
                'serviceName'=> $this->serviceName,
                'param'=>[
                    'name' => $name,
                    'idCard' => $id_number,
                ],

            ];
            $result = CurlHelper::curlHttp($url, 'POST', json_encode($post_data), 300);
            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }


}