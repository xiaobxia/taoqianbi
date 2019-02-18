<?php
namespace common\soa;

use common\helpers\CommonHelper;

use Curl\Curl;

class KoudaiSoa {

    const URL = 'http://api.xybaitiao.com/proxy/koudai?channel=jshb';

    protected static $instances = array();

    protected $service_name;

    public static function instance($service_name) {
        if (!isset(self::$instances[$service_name])) {
            self::$instances[$service_name] = new self($service_name);
        }

        return self::$instances[$service_name];
    }

    private function __construct($service_name) {
        $this->service_name = $service_name;
    }

    public function __call($method, $arguments) {
        $curl = new Curl();

        $data = [ //init
            'mod' => $this->service_name,
            'action' => $method,
        ];
        $idx = 0;
        foreach($arguments as $val) {
            $data["params[{$idx}]"] = $val;
            $idx ++;
        }

        $curl->post(self::URL, $data);
        if ($curl->error) {
            return CommonHelper::resp([], -1, sprintf('Error(%s):%s', $curl->errorCode, $curl->errorMessage));
        }

        $data = json_decode($curl->rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return CommonHelper::resp([], -1, 'Error： json解析错误');
        };

        return $data;
    }
}

