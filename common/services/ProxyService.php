<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/11/14
 * Time: 17:59
 */

namespace common\services;

use yii\base\Component;
use common\helpers\CurlHelper;

class ProxyService extends Component
{
    const URL = 'http://api.xybaitiao.com/proxy/third?channel=jshb';

    private $service;
    private $action;
    private $params;

    public function __construct($service, $action, $params)
    {
        $this->service = $service;
        $this->action = $action;
        $this->params = $params;
    }

    public function getData()
    {
        $post_data = [
            'service' => $this->service,
            'action' => $this->action,
            'params' => $this->params,
        ];
        $ret = CurlHelper::curlHttp(self::URL, 'POST', http_build_query($post_data), 30);
        return $ret;
    }
}
