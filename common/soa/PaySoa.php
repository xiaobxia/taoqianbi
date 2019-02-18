<?php

namespace common\soa;

/**
 * 用法 PaySoa::instance('className')->methodName($args);

  代扣 PaySoa::instance('Debit')->directPay($args);
  代扣参数 array(
    'card_no' => '',//银行卡号
    'bank_id' => 0, //银行编号
    "stay_phone" => '', //预留手机号
    "realname" => '', //真实姓名
    "idcard" => '', //身份证
    "amount" => 1, //金额
    "order_id" => '', //订单号
    "user_id" => 0, //用户ID
    "platform" => 0, //通道（可空）
    "merchant_id" => 0 //商户号（可空）
  )
 返回结果为一个数组，其中code为0表示成功，其他值表示失败
 **/
class PaySoa extends HttpRpcClient {

    public static function instance($service_name) {

        $env = get_cfg_var('env');
        if ($env == 'prod') {
            parent::config(['http://pay.kdqugou.com/']);
        } else {
            $ip_address = "42.96.204.114";
            if(YII_ENV_TEST == "69_test"){
                $ip_address = "121.42.12.69";
            }
            parent::config([sprintf('tcp://%s:2015',$ip_address)]);
            // parent::config(['http://42.96.204.114:8021/']);
        }

        return parent::instance($service_name);
    }

}

class HttpRpcClient {

    const TIME_OUT = 10;

    protected static $addressArray = array();
    protected static $instances = array();
    protected $connection = null;
    protected $serviceName = '';

    public static function config($address_array = array()) {
        if (!empty($address_array)) {
            self::$addressArray = $address_array;
        }
        return self::$addressArray;
    }

    public static function instance($service_name) {
        $service_name = 'Api'.$service_name;
        if (!isset(self::$instances[$service_name])) {
            self::$instances[$service_name] = new self($service_name);
        }
        return self::$instances[$service_name];
    }

    protected function __construct($service_name) {
        $this->serviceName = $service_name;
    }

    public function __call($method, $arguments) {
        $real_method = $method;
        $instance_key = $real_method . serialize($arguments);
        if (isset(self::$instances[$instance_key])) {
            throw new \Exception($this->serviceName . "->$method(" . implode(',', $arguments) . ") have already been called");
        }
        self::$instances[$instance_key] = new self($this->serviceName);
        return self::$instances[$instance_key]->sendData($real_method, $arguments[0]);

    }

    public function sendData($method, $arguments) {
        $address = self::$addressArray[array_rand(self::$addressArray)];
        $url = $address . '?c=' . $this->serviceName . '&a=' . $method;
        $arguments['sign'] = self::md5Sign($arguments);
        //var_dump($arguments, $url);
        return json_decode(self::http($url, $arguments), TRUE);
    }

    public function recvData() {
        $ret = $this->connection;
        $this->connection = null;
        if (!$ret) {
            throw new \Exception("recvData empty or request timeout", 504);
        }
        return $ret;
    }

    public static function md5Sign($params, $key = '*_*kdqugou.com*_*') {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $signStr = http_build_query($params) . $key;
        return md5($signStr);
    }

    public static function http($url, $vars = array(), $CA = false, $cacert = '') {
        $timeout = self::TIME_OUT;
        $with_header = false;
        if (substr($url, 0, 1) == '#') {
            $with_header = true;
            $url = substr($url, 1);
        }

        $method = is_array($vars) ? 'POST' : 'GET';
        $SSL = substr($url, 0, 8) == "https://" ? true : false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($with_header) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: {$method}"));

        if ($SSL && $CA && $cacert) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($vars));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长
        }
        $result = curl_exec($ch);

        $error_no = curl_errno($ch);
        if ($error_no) {
            $result = $error_no;
        }

        if ($with_header && !$error_no) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $headerSize);
            $body = substr($result, $headerSize);
            curl_close($ch);
            return [$header, $body];
        }
        curl_close($ch);
        return $result;
    }

}

class JsonRpcClient {

    const TIME_OUT = 10;
    const ASYNC_SEND_PREFIX = 'asend_';
    const ASYNC_RECV_PREFIX = 'arecv_';

    protected static $addressArray = array();
    protected static $asyncInstances = array();
    protected static $instances = array();
    protected $connection = null;
    protected $serviceName = '';

    public static function config($address_array = array()) {
        if (!empty($address_array)) {
            self::$addressArray = $address_array;
        }
        return self::$addressArray;
    }

    public static function instance($service_name) {
        if (!isset(self::$instances[$service_name])) {
            self::$instances[$service_name] = new self($service_name);
        }
        return self::$instances[$service_name];
    }

    protected function __construct($service_name) {
        $this->serviceName = $service_name;
    }

    public function __call($method, $arguments) {
        // 判断是否是异步发送
        if (0 === strpos($method, self::ASYNC_SEND_PREFIX)) {
            $real_method = substr($method, strlen(self::ASYNC_SEND_PREFIX));
            $instance_key = $real_method . serialize($arguments);
            if (isset(self::$asyncInstances[$instance_key])) {
                throw new \Exception($this->serviceName . "->$method(" . implode(',', $arguments) . ") have already been called");
            }
            self::$asyncInstances[$instance_key] = new self($this->serviceName);
            return self::$asyncInstances[$instance_key]->sendData($real_method, $arguments);
        }
        // 如果是异步接受数据
        if (0 === strpos($method, self::ASYNC_RECV_PREFIX)) {
            $real_method = substr($method, strlen(self::ASYNC_RECV_PREFIX));
            $instance_key = $real_method . serialize($arguments);
            if (!isset(self::$asyncInstances[$instance_key])) {
                throw new \Exception($this->serviceName . "->asend_$real_method(" . implode(',', $arguments) . ") have not been called");
            }
            return self::$asyncInstances[$instance_key]->recvData();
        }
        // 同步发送接收
        $this->sendData($method, $arguments);
        return $this->recvData();
    }

    public function sendData($method, $arguments) {
        $this->openConnection();
        $bin_data = self::encode(array(
                    'class' => $this->serviceName,
                    'method' => $method,
                    'params' => $arguments
        ));
        if (fwrite($this->connection, $bin_data) !== strlen($bin_data)) {
            throw new \Exception('Can not send data');
        }
        return true;
    }

    public function recvData() {
        $ret = fgets($this->connection);
        $this->closeConnection();
        if (!$ret) {
            //throw new Exception("recvData empty or request timeout", 504);
        }
        return self::decode($ret);
    }

    protected function openConnection() {
        $address = self::$addressArray[array_rand(self::$addressArray)];
        $this->connection = stream_socket_client($address, $err_no, $err_msg);
        if (!$this->connection) {
            throw new \Exception("can not connect to $address , $err_no:$err_msg");
        }
        stream_set_blocking($this->connection, true);
        stream_set_timeout($this->connection, self::TIME_OUT);
    }

    protected function closeConnection() {
        fclose($this->connection);
        $this->connection = null;
    }

    public static function encode($data) {
        return json_encode($data) . "\n";
    }

    public static function decode($bin_data) {
        return json_decode(trim($bin_data), true);
    }

}
