<?php

namespace common\api\xiaoying;

class Encryption {
    /**
     * 合作方密钥信息
     * @var null
     */
    private $partner = null;

    /**
     * 合作方类型
     * @var
     */
    private $partnerType;

    /**
     * 密钥路径
     * @var string
     */
    private $keyPath = '';

    /**
     * 加密密钥
     * @var mixed
     */
    private $privateKey;

    /**
     * 公钥
     * @var mixed
     */
    private $publicKey;

    /**
     * 调试
     * @var bool
     */
    public $isDebug = false;

    /**
     * 错误信息
     * @var array
     */
    private $errors = array();

    /**
     * 清空错误信息
     */
    public function clearErrors(){
        $this->errors = array();
    }

    /**
     * 设置错误信息
     * @param $message
     */
    public function setError($message){
        $this->errors[] = $message;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorStr(){
        if(count($this->errors) <= 0){
            return '';
        }else{
            return CJSON::encode($this->errors);
        }
    }

    /**
     * 初始化
     */
    public function __construct() {
        $this->keyPath = __DIR__ . '/';
        $this->privateKey = $this->keyPath . 'partner_private_key_dev.pem';
        $this->partner['YZT'] = array(
            'md5Key'    => 'leadbrandoin',
            'publicKey' => $this->keyPath . 'yzt_public_key_dev.pem',
        );
        $this->partner['LEADBRAND'] = array(
    	    'md5Key'    => 'leadbrandoin',
    	    'publicKey' => $this->keyPath . 'yzt_public_key_dev.pem',
    	);
        $this->partner['100026'] = array(
            'md5Key'    => 'f51d5c88dc2a37962a450b2a669f839a',
            'publicKey' => $this->keyPath . 'yzt_public_key_dev.pem',
        );
    }

    /**
     * 日志
     * @param $logLevel
     * @param $msg
     */
    private function log($msg,$logLevel='') {
        if ($logLevel) {
            //Yii::log($msg, $logLevel);
        }
        print_r($msg);
    }

    /**
     * 获取请求参数
     * @param string $logLevel
     * @return string
     */
    public function getRequestData($logLevel = '') {
        $this->log($postStr = file_get_contents("php://input"), $logLevel);
        if ($_POST && (count($_POST) > 0)) {
            $data = $_POST;
        } else {
            $postStr = file_get_contents("php://input");
            $data = json_decode($postStr);
        }
        $this->log(json_encode($data), $logLevel);
        if (!$this->getPartner($data['partner'])) {
            $ret = -50;
            $msg = "ret:{$ret}|partner no exist|partner:{$data['partner']}";
            $this->log($logLevel, $msg);
            $this->response($ret, 'partner no exist', null, $logLevel);
        }

        $this->partnerType = $data['partner'];

        if ($this->isDebug) {
            return array();
        }

        $decryptResult = $this->decrypt($data);
        if ($decryptResult['ret'] != 0) {
            $ret = -100;
            $msg = "ret:{$ret}|decrypt failed|result:" . json_encode($decryptResult);
            $this->log($logLevel, $msg);
            $this->response($ret, 'decrypt failed', null, $logLevel);
        }

        $this->log(json_encode($decryptResult['data']), $logLevel);
        return Util::filter($decryptResult['data']);
    }

    /**
     * 判断当前合作伙伴是否可以操作该标的
     * @param Loan $loan
     * @return Boolean
     */
    public function isPartnerLoan($loan) {
        $partner = $this->getPartner();
        if (!$partner) {
            return false;
        }
        return is_array($partner['loanTypeList']) && in_array($loan->FuiType, $partner['loanTypeList']);
    }

    /**
     * HTTP响应
     * @param $ret
     * @param $msg
     * @param null $data
     * @param string $logLevel
     */
    public function response($ret, $msg, $data = null, $logLevel = '') {
        if (!$this->partnerType) {
            Util::renderJSON(-100, 'invalid partner type');
        }

        $responseData = array(
            'ret' => $ret,
            'msg' => $msg,
        );
        if ($data !== null) {
            $responseData['data'] = $data;
        }
        if ($this->isDebug) {
            Util::debug($responseData);
        }

        if ($logLevel) {
            $this->log(json_encode($responseData), $logLevel);
        }

        $encryptData = $this->encrypt($responseData);
        if (!headers_sent()) {
            header("Content-type: application/json");
        }
        echo json_encode($encryptData);
        //Yii::$app->end();
    }

    /**
     * 加密内容
     * @param $partnerType
     * @return array
     */
    public function getPartner($partnerType = null) {
        $partnerType = $partnerType ? $partnerType : $this->partnerType;
        return isset($this->partner[$partnerType]) ? $this->partner[$partnerType] : null;
    }


    /**
     * 获取合作伙伴类型
     * @return string
     */
    public function getPartnerType() {
        return $this->partnerType;
    }

    /**
     * 设置合作伙伴类型
     * @param string $partnerType
     */
    public function setPartnerType($partnerType) {
        $this->partnerType = $partnerType;
    }

    /**
     * 加密内容
     * @param array $data
     * @param $partnerType
     * @return array
     */
    public function encrypt($data, $partnerType = null) {
        if ($partnerType) {
            $this->partnerType = $partnerType;
        }
        $partner = $this->partner[$this->partnerType];
        $content = $this->serializeArray($data);
        $randomKey = $this->randomKey();

        $binaryKey = $this->rsaEncrypt($randomKey, $partner['publicKey']);

        $binaryContent = $this->rc4Encrypt($content, $randomKey);
        $sign = $this->sign($binaryContent, $partner['md5Key']);
        $result = array();
        $result['key'] = bin2hex($binaryKey);//base16编码
        $result['content'] = bin2hex($binaryContent);//base16编码
        $result['sign'] = $sign;
        return $result;
    }

    /**
     * 解密和验证
     * @param $data
     * @param $partnerType
     * @param $contentIsJson
     * @return array
     */
    public function decrypt($data, $partnerType = null, $contentIsJson = true) {
        if (!($data['key'] && $data['content'] && $data['sign'])) {
            return Util::result(-10000, "invalid input|input:" . json_encode($data));
        }
        if ($partnerType) {
            $this->partnerType = $partnerType;
        }
        $partner = $this->partner[$this->partnerType];
        $binaryKey = pack("H*", $data['key']); //base16解码
        $binaryContent = pack("H*", $data['content']); //base16解码
        $sign = $data['sign'];
        $expectedSign = $this->sign($binaryContent, $partner['md5Key']);
        if ($sign != $expectedSign) {
            return Util::result(-11000, "verify sign failed|received:$sign|expected:{$expectedSign}");
        }

        $randomKey = $this->rsaDecrypt($this->privateKey, $binaryKey);
        if (!$randomKey) {
            return Util::result(-11100, "no random key|randomKey:{$randomKey}");
        }

        $content = $this->rc4Decrypt($binaryContent, trim($randomKey));
        if ($contentIsJson) {
            //$this->log($content, 'api/apiBorrower_controller');
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return Util::result(-12000, "no content");
            }
        } else {
            $data = $content;
        }
        return Util::result(0, "OK", $data);
    }

    /**
     * 对内容和约定的key进行md5计算，用于签名
     * @param $encryptContentBinary
     * @param $partnerMd5Key
     * @return string
     */
    private function sign($encryptContentBinary, $partnerMd5Key) {
        $raw = $encryptContentBinary . $partnerMd5Key;
        return md5($raw);
    }

    /**
     * RC4 解密内容
     * @param $decryptContent
     * @param $decryptKeyFile
     * @return string
     */
    public function rc4Decrypt($decryptContent, $decryptKeyFile) {
        return $this->rc4Encrypt($decryptContent, $decryptKeyFile);
    }

    /**
     * 用RC4算法对内容进行快速加密
     * @param $encryptContent
     * @param $encryptKeyFile
     * @return string
     */
    private function rc4Encrypt($encryptContent, $encryptKeyFile) {
        $key = array();
        $data = array();
        for ($i = 0; $i < strlen($encryptKeyFile); $i++) {
            $key[] = ord($encryptKeyFile{$i});
        }
        for ($i = 0; $i < strlen($encryptContent); $i++) {
            $data[] = ord($encryptContent{$i});
        }
        // prepare key
        $state = range(0, 255);
        $len = count($key);
        $index1 = $index2 = 0;
        for ($counter = 0; $counter < 256; $counter++) {
            $index2 = ($key[$index1] + $state[$counter] + $index2) % 256;
            $tmp = $state[$counter];
            $state[$counter] = $state[$index2];
            $state[$index2] = $tmp;
            $index1 = ($index1 + 1) % $len;
        }
        // rc4
        $len = count($data);
        $x = $y = 0;
        for ($counter = 0; $counter < $len; $counter++) {
            $x = ($x + 1) % 256;
            $y = ($state[$x] + $y) % 256;
            $tmp = $state[$x];
            $state[$x] = $state[$y];
            $state[$y] = $tmp;
            $data[$counter] ^= $state[($state[$x] + $state[$y]) % 256];
        }
        // convert output back to a string
        $encryptContent = '';
        for ($i = 0; $i < $len; $i++) {
            $encryptContent .= chr($data[$i]);
        }

        return $encryptContent;
    }


    /**
     * 使用公钥对内容进行RSA加密
     * @param $rawData
     * @param $publicKeyFile
     * @return string
     */
    private function rsaEncrypt($rawData, $publicKeyFile) {
        $pubKey = file_get_contents($publicKeyFile);
        $encryptedList = array();
        $step = 117;
        for ($i = 0, $len = strlen($rawData); $i < $len; $i += $step) {
            $data = substr($rawData, $i, $step);
            $encrypted = '';
            openssl_public_encrypt($data, $encrypted, $pubKey);
            $encryptedList[] = ($encrypted);
        }
        $data = join('', $encryptedList);
        return $data;
    }

    /**
     * RSA解密
     * @param $privateKeyFile
     * @param $encryptedData
     * @return string
     */
    private function rsaDecrypt($privateKeyFile, $encryptedData) {
        $privateKey = file_get_contents($privateKeyFile);
        $decryptedList = array();
        $step = 128;
        //var_dump(strlen($encryptedData));
        for ($i = 0, $len = strlen($encryptedData); $i < $len; $i += $step) {
            $data = substr($encryptedData, $i, $step);
            $decrypted = '';
            openssl_private_decrypt($data, $decrypted, $privateKey);
            $decryptedList[] = $decrypted;
        }
        return join('', $decryptedList);
    }

    /**
     * 生成128位的随机字符串
     * @return string
     */
    private function randomKey() {
        $letters = "`1234567890-=qwertyuiop[]\\asdfghjkl;'zxcvbnm,./~!@#$%^&*()_+QWERTYUIOP{}|ASDFGHJKL:\"ZXCVBNM<>?1234567890-=qwertyuiop[]\\asdfghjkl;'zxcvbnm";
        return substr(str_shuffle($letters), 0, 128);
    }

    /**
     * 序列号数组
     * @param $array
     * @return string
     */ 
    private function serializeArray($array) {
        ksort($array);
        $signContent = json_encode($array);
        return $signContent;
    }

    /**
     * 向赢证通发送请求
     * @param $data array
     * @param string $url
     * @return string
     */
    public function http_post($url,$data)
    {
        $str = $this->encrypt($data,'LEADBRAND');
        //return Util::send_post($url, array_merge($str, array('partner'=>'LEADBRAND')) );
	    $str['partner'] = 'LEADBRAND';
        // return Util::send_post($url,$str);
        return Common::get_url($url,true,$str);
    }
}

class Util {
    /**
     * 获取配置参数
     * @param string $key 可用 "." 获取子成员
     * @return mixed
     */
    public static function config($key){
        $keyArr = explode('.', $key);
        // var_dump($keyArr);exit;
        $ret = 'test';// Yii::$app->params[ array_shift($keyArr) ];
        while(!empty($keyArr) && $ret){
            $ret = $ret[ array_shift($keyArr) ];
        }
        return $ret;
    }

    /**
     * 对内容进行安全过滤存库
     * @param $val
     * @return string
     */
    public static function filter($val){
        if(is_array($val)){
            foreach($val as $key => $v){
                $val[$key] = Util::filter($v);
            }
        }else{
            $val = htmlspecialchars($val, ENT_QUOTES);
        }
        return $val;
    }

    /**
     * 将json 数据输出到前端
     * @param $ret
     * @param string $message
     * @param null $data
     * @param bool $allowJsonP 是否支持使用JSONP
     */
    public static function  renderJSON($ret, $message = '', $data = null, $allowJsonP = false){
        $result = array(
            'ret' => $ret,
            'msg' => $message,
        );
        if($data !== null){
            $result['data'] = $data;
        }
        // $json = CJavaScript::jsonEncode($result);
        $json = json_encode($result);

        /***********************************************
         * 如果允许用JSONP返回数据，且回调函数合法
         * 则用JSONP返回数据
         **********************************************/
        $callback = htmlspecialchars(trim($_REQUEST['_cb_']));
        if($allowJsonP && preg_match('/^[a-z_]\w*$/', $callback)){
            if (!headers_sent()) {
                header("Content-type: application/javascript");
            }
            echo "{$callback}({$json});";
        }else{
            if (!headers_sent()) {
                header("Content-type: application/json");
            }
            echo $json;
        }
        //Yii::$app->end();
    }

    /**
     * 程序调试，打印对象$ob 相关信息
     * @param $ob
     * @param bool $die
     */
    public static function debug($ob, $die = true){
        if (!headers_sent()) {
            header("Content-type:text/html;charset=utf-8");
        }

        if(is_object($ob)){
            $class = get_class($ob);
            if(strpos($class, 'Model') !== false){
                echo $ob->getLastSql();
            }else{
                print_r($ob);
            }
        }else{
            print_r($ob);
        }

        if($die){
            die();
        }else{
            echo "\r\n";
        }
    }

    /**
     * 打包数据，用于接口间通信
     * @param $ret
     * @param string $message
     * @param null $data
     * @return array
     */
    public static function  result($ret, $message = '', $data = null){
        return array(
            'ret' => $ret,
            'msg' => $message,
            'data' => $data,
        );
    }

    /**
     * 发送post请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    public static function send_post($url, $post_data) {

        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                //'header' => 'Content-type:application/json',
		        //'header' => 'Content-type:application/x-www-form-urlencoded\r\nUser-Agent:MyAgent/1.0\r\n',
                'content' => $postdata,
                'timeout' => 10 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        //var_dump($context);exit;
        $result = file_get_contents($url, false, $context);

        return $result;
    }

}
