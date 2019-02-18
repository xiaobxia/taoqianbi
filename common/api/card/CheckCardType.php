<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/14
 * Time: 11:30
 */
namespace common\api\card;
use common\exceptions\UserExceptionExt;

/**
 * 发送请求类
 *
 */
class HttpRequest
{
    // CURL 参数
    public $http_info;
    public $http_header = array();
    public $http_code;
    public $useragent = 'KoudaiPay PHPSDK v1.0';
    public $connecttimeout = 30;
    public $timeout = 30;
    public $ssl_verifypeer = FALSE;

    private static $http = null;
    public static function instance() {
        if (self::$http == null) {
            self::$http = new self();
        }
        return self::$http;
    }

    const BaseUrl = 'http://42.96.204.114/pay_v1/pay.api.koudailc.com/web/card/bin';

    public function post($url, $query) {
        $data = $this->http($url, 'POST', http_build_query($query));
        if ($this->http_info['http_code'] == 405) {
            return UserExceptionExt::throwCodeAndMsgExt('此接口不支持使用POST方法请求', 1004);
        }
        return json_decode($data, true);
    }

    /**
     * 模拟HTTP协议
     * @param string $url
     * @param string $method
     * @param string $postfields
     * @return mixed
     */
    private function http($url, $method, $postfields = NULL) {
        $url = HttpRequest::BaseUrl . $url;
        $this->http_info = array();
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        $method = strtoupper($method);
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);
        return $response;
    }
}