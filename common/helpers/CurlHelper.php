<?php

namespace common\helpers;
use yii\base\Exception;

/**
 * CURL助手
 */
class CurlHelper
{
	//连接超时
	private static $connect_timeout = 20;
	//错误编码
	public static $err_code = 0;
	 //错误信息,无错误为''
	public static $err_msg  = '';

	public static $http_info;
	public static $http_code;
	public static $http_header;

	/**
	 * 初始化信息
	 */
	private static function init()
	{
		self::$err_code = 0;
		self::$err_msg  = '';
		self::$http_code = 200;
		self::$http_header = array();
		self::$http_info = array();
	}
	/**
	 * CURL模拟HTTP协议
	 * @param string $url
	 * @param string $method
	 * @param string $post_data
	 * @param number $timeout
	 * @param string $json
	 * @param string $useragent
	 * @param string $cookie
	 * @return boolean|mixed
	 */
	public static function curlHttp($url, $method, $post_data = null, $timeout = 5, $json = true, $useragent = null, $cookie = null,$token_key = null)
	{
		self::init();
		$c_timeout = $timeout;
		$curl_handle = curl_init();
		if (!empty($useragent)) {
			curl_setopt($curl_handle, CURLOPT_USERAGENT, $useragent);
		}
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, $c_timeout);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
		//以下两行，忽略https证书
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);

		//如果用单例模式调用方法 curlHttp，则new self()可以换成$this；单例模式、静态方法各有优缺点，暂时用静态方法
		curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array(new self(), 'getHeader'));
		curl_setopt($curl_handle, CURLOPT_HEADER, false);
		$method = strtoupper($method);
		switch ($method) {
			case 'CS_GET':
				if(!is_null($post_data) && is_array($post_data)){
					$param = [];
					foreach($post_data as $k => $v){
						$param[] = "${k}=${v}";
					}
					$param = implode('&',$param);
					$url = "${url}?${param}";
				}
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));

				break;
			case 'CS_POST':
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
				if (!empty($post_data))	 {
					curl_setopt($curl_handle, CURLOPT_POST, true);
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'GET':
				if(!is_null($post_data) && is_array($post_data)){
					$param = [];
					foreach($post_data as $k => $v){
						$param[] = "${k}=${v}";
					}
					$param = implode('&',$param);
					$url = "${url}?${param}";
				}
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded;charset=UTF-8'));
				break;
			case 'JXL':
				// 聚信立请求对时间有额外要求，可能大于10秒
				curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
//				curl_setopt($curl_handle, CURLOPT_PROXY, '106.14.60.165:3128');
				if (!empty($post_data))	 {
					curl_setopt($curl_handle, CURLOPT_POST, true);
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'GJJ':
				curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($curl_handle, CURLOPT_POST, true);
				if (!empty($post_data)) {
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'TONGDUN':
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded;charset=UTF-8'));
				if (!empty($post_data))	 {
					curl_setopt($curl_handle, CURLOPT_POST, true);
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'POST':
				curl_setopt($curl_handle, CURLOPT_POST, true);
				if (!empty($post_data)) {
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'DELETE':
				curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($post_data)) {
					$url = "{$url}?{$post_data}";
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'XIAOYUSAN':
				curl_setopt($curl_handle, CURLOPT_POST, true);
				curl_setopt($curl_handle, CURLOPT_SSLCERT, __DIR__."/../config/cert/clientall.pem"); // 小雨伞接口专用证书

				if (!empty($post_data)) {
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'YXZC':
				$headers = array(
					'Authorization: Basic '. base64_encode('gurenye@koudailc.com:Zzc$20150708'),
					'Content-Type: application/json; charset=utf-8'
				);
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl_handle, CURLOPT_POST, true);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($post_data));
				self::$http_info['post_data'] = $post_data;
				break;
			case 'KDSL'://口袋世联房产
				$headers = array(
					'Authorization: Basic '. $token_key,
					'Content-Type'=>'application/json; charset=utf-8',
					'Content-Length'=> strlen($post_data)
				);
				//curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl_handle, CURLOPT_POST, true);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
				self::$http_info['post_data'] = $post_data;


				break;
			case 'YXZCREPORT':
				$headers = array(
					'Authorization: Basic '. base64_encode('gurenye@koudailc.com:Zzc$20150708'),
					'Content-type:application/json'
				);
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
				if(!is_null($post_data)){
					curl_setopt($curl_handle, CURLOPT_POST, true);
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
                        case 'YYS':
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
				if (!empty($post_data))	 {
					curl_setopt($curl_handle, CURLOPT_POST, true);
                                        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 300);
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
					self::$http_info['post_data'] = $post_data;
				}
				break;
			case 'WEALIDA':
				// 聚信立请求对时间有额外要求，可能大于10秒
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($curl_handle, CURLOPT_PROXY, '106.14.60.165:3128');
				if(!empty($post_data['options'])){
					curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data;charset=UTF-8'));
					curl_setopt($curl_handle, CURLOPT_POST, true);
					self::$http_info['post_data'] = $post_data;
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
				} else{
					if (!empty($post_data))	 {
						curl_setopt($curl_handle, CURLOPT_POST, true);
						curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
						self::$http_info['post_data'] = $post_data;
					}
				}
				break;
		}
		if (isset($cookie['SESSIONID'])) {
			curl_setopt($curl_handle, CURLOPT_COOKIE, "SESSIONID=" . $cookie['SESSIONID']);
		}
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		$response = curl_exec($curl_handle);
		self::$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		if (!curl_errno($curl_handle)) {
			self::$http_info['errno'] = curl_errno($curl_handle);
			self::$http_info['error'] = curl_error($curl_handle);
		}
		self::$http_info['response'] = $response;
		if ($response === false) {

			self::$err_code = 10616;
			self::$err_msg = 'cURL errno: ' . curl_errno($curl_handle) . '; error: ' . curl_error($curl_handle);
			// 关闭连接
			curl_close($curl_handle);
			return false;
		} else if (!empty($response) && $json) {
			$response = json_decode($response, true);
		}

		self::$http_info = array_merge(self::$http_info, curl_getinfo($curl_handle));
		curl_close($curl_handle);
		return $response;

	}

	/**
	 * Get the header info to store.
	 * @param type $ch
	 * @param type $header
	 * @return type
	 */
	private function getHeader($ch, $header)
	{
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			self::$http_header[$key] = $value;
		}
		return strlen($header);
	}

    /**
     * 请求接口 返回数组
     * @param $url
     * @return mixed
     */
    public static function getArrayData($url){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        $navigation = json_decode($output,true);
        return $navigation;
    }
	public static function curl($url,$data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $charset);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);

		if (curl_errno($ch)) {
		    \Yii::error(json_encode($response).'|'.json_encode(curl_errno($ch)),'hcerror');
			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
                \Yii::error(json_encode($response).'|'.$httpStatusCode,'hcerror');
				throw new Exception($response, $httpStatusCode);
			}
		}
		curl_close($ch);
		return $response;
	}


    public static function wzdCurl($url, $method, $post_data = null, $token_key = null,$timeout = 10,$json=true)
    {
        self::init();
        $c_timeout = $timeout;
        if($c_timeout > 10){//不允许设置超过10秒以上的请求
            $c_timeout = 10;
        }
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $c_timeout);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        //以下两行，忽略https证书
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);

        //如果用单例模式调用方法 curlHttp，则new self()可以换成$this；单例模式、静态方法各有优缺点，暂时用静态方法
        curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array(new self(), 'getHeader'));
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                if(!is_null($post_data) && is_array($post_data)){
                    $param = [];
                    foreach($post_data as $k => $v){
                        $param[] = "${k}=${v}";
                    }
                    $param = implode('&',$param);
                    $url = "${url}?${param}";
                }
                if (!empty($token_key)) {
                    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                        'Content-Type:application/x-www-form-urlencoded;charset=UTF-8',
                        'AuthorizationToken:'.$token_key
                    ));
                } else {
                    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded;charset=UTF-8'));
                }
                break;
            case 'POST':
                curl_setopt($curl_handle, CURLOPT_POST, true);
                if (!empty($post_data)) {
                    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($post_data));
                    self::$http_info['post_data'] = $post_data;
                }
                if (!empty($token_key)) {
                    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                        "Content-Type:application/json;charset=UTF-8",
                        "AuthorizationToken:{$token_key}"
                    ));
                } else {
                    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=UTF-8'));
                }
                break;
        }
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        $response = curl_exec($curl_handle);
        self::$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        if (!curl_errno($curl_handle)) {
            self::$http_info['errno'] = curl_errno($curl_handle);
            self::$http_info['error'] = curl_error($curl_handle);
        }
        self::$http_info['response'] = $response;
        if ($response === false) {
            self::$err_code = 10616;
            self::$err_msg = 'cURL errno: ' . curl_errno($curl_handle) . '; error: ' . curl_error($curl_handle);
            // 关闭连接
            curl_close($curl_handle);
            return false;
        }elseif (!empty($response) && $json) {
            $response = json_decode($response, true);
        }
        self::$http_info = array_merge(self::$http_info, curl_getinfo($curl_handle));
        curl_close($curl_handle);
        return $response;
    }

    public static function FinancialCurl($url, $method, $post_data = null, $timeout = 5, $json = true, $useragent = null, $cookie = null,$token_key = null)
    {
        self::init();
        $c_timeout = $timeout;

        $curl_handle = curl_init();
        if (!empty($useragent)) {
            curl_setopt($curl_handle, CURLOPT_USERAGENT, $useragent);
        }
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $c_timeout);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
        //以下两行，忽略https证书
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);

        //如果用单例模式调用方法 curlHttp，则new self()可以换成$this；单例模式、静态方法各有优缺点，暂时用静态方法
        curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array(new self(), 'getHeader'));
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                if(!is_null($post_data) && is_array($post_data)){
                    $param = [];
                    foreach($post_data as $k => $v){
                        $param[] = "${k}=${v}";
                    }
                    $param = implode('&',$param);
                    $url = "${url}?${param}";
                }
                curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded;charset=UTF-8'));
                break;
            case 'POST':
                curl_setopt($curl_handle, CURLOPT_POST, true);
                if (!empty($post_data)) {
                    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
                    self::$http_info['post_data'] = $post_data;
                }
                break;
        }
        if (isset($cookie['SESSIONID'])) {
            curl_setopt($curl_handle, CURLOPT_COOKIE, "SESSIONID=" . $cookie['SESSIONID']);
        }
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        $response = curl_exec($curl_handle);
        self::$http_info['info']['all'] = curl_getinfo($curl_handle);
        if (!curl_errno($curl_handle)) {
            self::$http_info['errno'] = curl_errno($curl_handle);
            self::$http_info['error'] = curl_error($curl_handle);
        }
        self::$http_info['response'] = $response;
        if ($response === false) {
            self::$err_code = 10616;
            self::$err_msg = 'cURL errno: ' . curl_errno($curl_handle) . '; error: ' . curl_error($curl_handle);
            // 关闭连接
            curl_close($curl_handle);
            return false;
        } else if (!empty($response) && $json) {
            $response = json_decode($response, true);
        }

        self::$http_info = array_merge(self::$http_info, curl_getinfo($curl_handle));
        curl_close($curl_handle);
        return $response;

    }

}

//End of script

