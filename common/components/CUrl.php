<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\helpers\Json;
use yii\web\HttpException;

/**
 * CUrl
 * cURL
 * ----
 * @version 1.0.0
 * @author Verdient。
 */
class CUrl extends Component
{
	/**
	 * const CURLOPT_QUERY
	 * 查询参数
	 * -------------------
	 * @author Verdient。
	 */
	const CURLOPT_QUERY = 'query';

	/**
	 * @var $onlyContent
	 * 只返回消息体
	 * -----------------
	 * @author Verdient。
	 */
	public $onlyContent = true;

	/**
	 * @var $autoParse
	 * 是否自动解析响应体
	 * ---------------
	 * @author Verdient。
	 */
	public $autoParse = true;

	/**
	 * @var $_url
	 * url地址
	 * ----------
	 * @author Verdient。
	 */
	protected $_url = null;

	/**
	 * @var $_response
	 * 响应内容
	 * ---------------
	 * @author Verident。
	 */
	protected $_response = null;

	/**
	 * @var $_responseCode
	 * 状态码
	 * ---------------------------
	 * @author Verdient。
	 */
	protected $_responseCode = null;

	/**
	 * @var $_responseHeader
	 * 响应头
	 * ---------------------
	 * @author Verdient。
	 */
	protected $_responseHeader = [];

	/**
	 * @var $_responseBody
	 * 响应体
	 * -------------------
	 * @author Verdient。
	 */
	protected $_responseBody = null;

	/**
	 * @var $_options
	 * 参数
	 * --------------
	 * @author Verdient。
	 */
	protected $_options = [];

	/**
	 * @var $_curl
	 * cUrl实例
	 * -----------
	 * @author Verdient。
	 */
	protected $_curl = null;

	/**
	 * @var $_defaultOptions
	 * 默认参数
	 * ---------------------
	 * @author Verdient。
	 */
	protected $_defaultOptions = [
		CURLOPT_USERAGENT => 'Yii2-CUrl-Agent',
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_HTTPHEADER => [],
		self::CURLOPT_QUERY => [],
	];

	/**
	 * __construct([String $url = null])
	 * 构造函数
	 * ---------------------------------
	 * @author Verdient。
	 */
	public function __construct($url = null){
		parent::__construct([]);
		$this->_url = $url;
	}

	/**
	 * get()
	 * get访问
	 * ------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function get(){
		return $this->_httpRequest('GET');
	}

	/**
	 * head()
	 * head访问
	 * --------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function head(){
		return $this->_httpRequest('HEAD');
	}

	/**
	 * post()
	 * post访问
	 * -------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function post(){
		return $this->_httpRequest('POST');
	}

	/**
	 * put()
	 * put访问
	 * ------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function put(){
		return $this->_httpRequest('PUT');
	}

	/**
	 * delete()
	 * delete访问
	 * ---------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function delete(){
		return $this->_httpRequest('DELETE');
	}

	/**
	 * setUrl($url)
	 * 设置访问地址
	 * ------------
	 * @author Verdient。
	 */
	public function setUrl($url){
		$this->_url = $url;
	}

	/**
	 * setHeader(Array $headers)
	 * 设置发送的头部信息
	 * -------------------------
	 * @param Array $headers 头部信息
	 * -----------------------------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function setHeader(Array $headers){
		$header = [];
		foreach($headers as $key => $value){
			$header[] = $key . ':' . $value;
		}
		$this->setOption(CURLOPT_HTTPHEADER, $header);
	}

	/**
	 * setBody(Array $data, Callable / String $callback = null)
	 * 设置发送的数据
	 * --------------------------------------------------------
	 * @param Array $data 发送的数据
	 * @param Callable / String $callback 回调函数
	 * -------------------------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function setBody(Array $data, $callback = null){
		$this->setOption(CURLOPT_POST, 1);
		if(is_string($callback)){
			if(strtoupper($callback) == 'JSON'){
				$data = Json::encode($data);
				$this->setHeader(['Content-Type' => 'application/json', 'Content-Length' => strlen($data)]);
				return $this->setOption(CURLOPT_POSTFIELDS, $data);
			}
		}
		if(is_callable($callback)){
			$data = call_user_func($callback, $data);
		}
		return $this->setOption(CURLOPT_POSTFIELDS, $callback == null ? http_build_query($data) : $data);
	}

	/**
	 * setQuery(Array $query)
	 * 设置查询信息
	 * ----------------------
	 * @param Array $query 查询信息
	 * ---------------------------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function setQuery(Array $query){
		$this->setOption(self::CURLOPT_QUERY, $query);
	}

	/**
	 * setOption(String $key, Mixed $value)
	 * 设置选项
	 * ------------------------------------
	 * @param String $key 选项名称
	 * @param Mixed $value 选项内容
	 * ----------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function setOption($key, $value){
		if(isset($this->_options[$key]) && is_array($this->_options[$key])){
			$this->_options[$key] = array_merge($this->_options[$key], $value);
		}else{
			$this->_options[$key] = $value;
		}
		return $this;
	}

	/**
	 * setOptions(Array $options)
	 * 批量设置选项
	 * --------------------------
	 * @param String $options 选项集合
	 * -------------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function setOptions($options){
		foreach($options as $key => $value){
			$this->setOption($key, $value);
		}
		return $this;
	}

	/**
	 * unsetOption(String $key)
	 * 删除选项
	 * ------------------------
	 * @param String $key 选项名称
	 * --------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function unsetOption($key){
		if(isset($this->_options[$key])){
			unset($this->_options[$key]);
		}
		return $this;
	}

	/**
	 * resetOptions()
	 * 重置选项
	 * --------------
	 * @return Object
	 * @author Verdient。
	 */
	public function resetOptions(){
		if (isset($this->_options)) {
			$this->_options = [];
		}
		return $this;
	}

	/**
	 * reset()
	 * 重置
	 * -------
	 * @return Object
	 * @author Verdient。
	 */
	public function reset(){
		if($this->_curl !== null){
			@curl_close($this->_curl);
		}
		$this->_url = null;
		$this->_curl = null;
		$this->_options = [];
		$this->_response = null;
		$this->_responseCode = null;
		$this->_responseHeader = [];
		return $this;
	}

	/**
	 * getOption(String $key)
	 * 获取选项内容
	 * ----------------------
	 * @param String $key 选项名称
	 * ---------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function getOption($key){
		$mergesOptions = $this->getOptions();
		return isset($mergesOptions[$key]) ? $mergesOptions[$key] : false;
	}

	/**
	 * getOptions()
	 * 获取所有的选项内容
	 * ------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function getOptions(){
		return $this->_options + $this->_defaultOptions;
	}

	/**
	 * getInfo(String $opt)
	 * 获取连接资源句柄的信息
	 * ----------------------
	 * @param String $opt 选项名称
	 * ---------------------------
	 * @return Object
	 * @author Verdient。
	 */
	public function getInfo($opt = null){
		if($this->_curl !== null && $opt === null){
			return curl_getinfo($this->_curl);
		}else if($this->_curl !== null && $opt !== null){
			return curl_getinfo($this->_curl, $opt);
		}else{
			return [];
		}
	}

	/**
	 * getResponse()
	 * 获取响应内容
	 * -------------
	 * @return Mixed
	 * @author Verdient。
	 */
	public function getResponse(){
		return $this->_response;
	}

	/**
	 * getResponseCode()
	 * 获取状态码
	 * -----------------
	 * @return Integer
	 * @author Verdient。
	 */
	public function getResponseCode(){
		return $this->_responseCode;
	}

	/**
	 * _httpRequest(String $method, String $dataType)
	 * http请求
	 * ---------------------------------------------
	 * @param String $method 请求方式
	 * @param String $dataType 返回数据格式
	 * ------------------------------------
	 * @return Object
	 * @author Verdient。
	 */
	private function _httpRequest($method, $dataType = null){
		$url = $this->_url;
		$this->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($method));
		if($method === 'HEAD'){
			$this->setOption(CURLOPT_NOBODY, true);
			$this->unsetOption(CURLOPT_WRITEFUNCTION);
		}
		$query = $this->getOption(self::CURLOPT_QUERY);
		if(!empty($query)){
			$url = $url . '?' . http_build_query($query);
		}
		$this->_curl = curl_init($url);
		$options = $this->getOptions();
		$curlOptions = [];
		foreach($options as $key => $value){
			if(is_numeric($key)){
				$curlOptions[$key] = $value;
			}
		}
		curl_setopt_array($this->_curl, $curlOptions);
		$response = curl_exec($this->_curl);
		if($response === false){
			$errorCode  = curl_errno($this->_curl);
			Yii::error(['code' => $errorCode, 'type' => curl_strerror($errorCode), 'message' => curl_error($this->_curl), 'info' => curl_getinfo($this->_curl), 'version' => curl_version()], __METHOD__);
			switch($errorCode){
				case 7:
					throw new HttpException(504, 'cUrl requset timeout');
					break;
				default:
					throw new HttpException(502, 'cUrl requset error(' . $errorCode . ')');
					break;
			}
		}
		if($this->getOption(CURLOPT_HEADER) === true){
			$headerSize = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);
			$headers = substr($response, 0, $headerSize);
			$headers = explode("\r\n", $headers);
			foreach($headers as $header){
				if($header){
					$header = explode(': ', $header);
					if(isset($header[1])){
						$this->_responseHeader[$header[0]] = $header[1];
					}
				}
			}
			$this->_responseBody = substr($response, $headerSize);
			if($this->autoParse === true && isset($this->_responseHeader['Content-Type'])){
				$contentType = $this->_responseHeader['Content-Type'];
				$contentType = explode(';', $contentType)[0];
				switch($contentType){
					case 'application/json':
						$this->_responseBody = Json::decode($this->_responseBody);
						break;
					case 'application/xml':
						break;
				}
			}
		}else{
			$this->_responseBody = $response;
		}
		$this->_responseCode = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
		$this->_response = $response;
		Yii::trace([
			'method' => $method,
			'url' => $url,
			'options' => $options,
			'code' => $this->_responseCode,
			'body' => $this->_responseBody,
			'header' => $this->_responseHeader
		], __METHOD__);
		if($this->onlyContent === true){
			$result = $this->_responseBody;
		}else{
			$result = ['code' => $this->_responseCode, 'header' => $this->_responseHeader, 'body' => $this->_responseBody];
		}
		$this->reset();
		return $result;
	}
}