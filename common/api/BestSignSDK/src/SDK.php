<?php
namespace BestSignSDK;

require(__DIR__ . '/init.php');

class SDK
{
    private $_mid = '';
    private $_pem = '';
    private $_host = '';
	private $_http_utils = null;
    
    private static $instance;
    
    /** 
     * 初始化
     * @param string $mid 开发者编号
     * @param string $pem rsa证书文件或证书内容
     * @param string $host 服务器地址
     * @param string $pem_type 证书类型. 一般保持为空
     * @return
     */
    public static function getInstance($mid, $pem, $host = Constants::DEFAULT_HOST, $pem_type = '')
    {
        $key = "{$mid}::{$pem}::{$host}::{$pem_type}";
        if (isset(self::$instance[$key]))
        {
            return self::$instance[$key];
        }
        $class = __CLASS__;
        self::$instance[$key] = new $class($mid, $pem, $host, $pem_type);
        return self::$instance[$key];    
    }
    
    private function __construct($mid, $pem, $host, $pem_type)
    {
        $this->_pem = $this->_formatPem($pem, $pem_type);
        $this->_mid = $mid;
        $this->_host = $host;
		$this->_http_utils = HttpUtils::create();
		$this->_http_utils->setDefaultUserAgent(Constants::APP_NAME . "/" . Constants::VERSION);
    }
    
    /**
     * 设置日志目录
     * @param $path
     */
    public function setLogDir($path)
    {
        Logger::setLogDir($path);
    }
    
    /**
     * 设置调试等级
     * @param $debugLevel
     */
    public function setDebugLevel($debugLevel)
    {
        Logger::setDebugLevel($debugLevel);
    }
    
    /**
     * 获取签名串
     * @param $args
     * @return
     */
    public function getRsaSign()
    {
        $pkeyid = openssl_pkey_get_private($this->_pem);
        if (!$pkeyid)
        {
            throw new \Exception("openssl_pkey_get_private wrong!", -1);
        }
        
        if (func_num_args() == 0) {
            throw new \Exception('no args');
        }
        $sign_data = func_get_args();
        $sign_data = trim(implode("\n", $sign_data));
        
        openssl_sign($sign_data, $sign, $this->_pem);
        openssl_free_key($pkeyid);
        return base64_encode($sign);
    }
    
    
    //********************************************************************************
    // 接口
    //********************************************************************************
    /**
     * 合同发送（签署人数可以不确定）
     * 
     * @param userlist
     * @param senduser
     * @param reqcontent
     * @param filename 文件名
     * @return
     * 
     */
    public function sjdsendcontractdocUpload(array $userlist, array $senduser, $reqcontent, $filename = 'contract.pdf')
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        if (empty($filename))
        {
            $filename = "contract.pdf";
        }
		
		if (strtolower(substr($filename, -4, 4)) == ".pdf" && !Utils::isPdf($reqcontent)) {
			throw new \Exception("not a pdf file");
		}
        
        $json_userlist = json_encode($userlist);
        $json_senduser = json_encode($senduser);
        
        //post data
        $post_data = $reqcontent;
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data), rawurlencode($filename), $json_userlist, $json_senduser);
        
        //header data
        $header_data = array();
        $header_data['filename'] = $filename;
        $header_data['userlist'] = $json_userlist;
        $header_data['senduser'] = $json_senduser;
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
		return $result;
    }
	
	/**
	 * 合同上传 (模版)
	 *  
	 * @param tid
	 * @param senduser
	 * @param tempcontents
	 * @param fontSize
	 * @return
	 */
	public function uploadcontractly($tid, array $senduser, array $tempcontents, $font_size = 14)
	{
		$method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        $filename = "contract.pdf";
        
        //post data
        $post_data['request']['content']['tid'] = $tid;
        $post_data['request']['content']['filename'] = $filename;
		$post_data['request']['content']['senduser'] = $senduser;
		$post_data['request']['content']['tempcontents'] = $tempcontents;
		$post_data['request']['content']['fontSize'] = "{$font_size}";
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
		return $result;
	}
    
    /**
     * 追加签署人
     * @param signid
     * @param userlist
     * @return
     */
    public function sjdsendcontract($signid, array $userlist)
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['signid'] = $signid;
        $post_data['request']['content']['userlist'] = $userlist;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
        
		return $result;
    }
    
    /**
     * 用户图片上传接口
     * 
     * @param useracount
     * @param usermobile
     * @param imgtype
     * @param image
     * @param imgName
     * @param usertype
     * @param username
     * @return
     */
    public function uploaduserimage($useracount, $usermobile, $imgtype, $image, $imgName, $username, $usertype = Constants::USER_TYPE_PERSONAL, $sealname = '', $overwrite_seal = false)
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['useracount'] = $useracount;
        $post_data['request']['content']['usermobile'] = $usermobile;
        $post_data['request']['content']['imgtype'] = $imgtype;
        $post_data['request']['content']['image'] = base64_encode($image);
        $post_data['request']['content']['imgName'] = $imgName;
        $post_data['request']['content']['username'] = $username;
        $post_data['request']['content']['usertype'] = $usertype;
		if (strlen($sealname) > 0) {
			$post_data['request']['content']['sealname'] = $sealname;
			$post_data['request']['content']['updateflag'] = $overwrite_seal ? "1" : "0";
		}
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
		
        return $result;
    }
    
    /**
     * 用户图片查询
     * 
     * @param useracount
     * @return
     */
    public function queryuserimage($useracount, $sealname = '')
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['useracount'] = $useracount;
		if (strlen($sealname) > 0) {
			$post_data['request']['content']['sealname'] = $sealname;
		}
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
        
		return $result;
    }
    
    /**
     * 手动签名接口. 返回页面url
     * 合同签名（甲方指定位置，不生成默认签名，不允许乙方拖动）
     * 
     * @param fsid
     * @param email
     * @param pagenum
     * @param signx
     * @param signy
     * @param returnurl
     * @param typedevice
     * @param openflagString
     * @return
     */
    public function getSignPageSignimagePc($fsid, $email, $pagenum, $signx, $signy, $returnurl, $typedevice = Constants::DEVICE_TYPE_PC, $openflagString = true, $sealname = '', $pushurl = '')
    {
        $method = __FUNCTION__ . ".json";
        $path = "/openpagec/" . $method;
        
        $openflagString = $openflagString ? "1" : "0";
        
        //sign data (don't sign $pushurl)
		if (strlen($sealname) < 1) {
			$sign_data = $this->_getSignData($method, $this->_mid, $fsid, $email, "$pagenum", "$signx", "$signy", $returnurl, "$typedevice", $openflagString);
		}
        else {
			$sign_data = $this->_getSignData($method, $this->_mid, $fsid, $email, "$pagenum", "$signx", "$signy", $returnurl, "$typedevice", $openflagString, $sealname);
		}
        
        //签名串
        $sign = $this->getRsaSign($sign_data);
		
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "fsid=" . urlencode($fsid) . "&";
        $requestPath .= "email=" . urlencode($email) . "&";
        $requestPath .= "pagenum=" . "$pagenum" . "&";
        $requestPath .= "signx=" . $signx . "&";
        $requestPath .= "signy=" . $signy . "&";
        $requestPath .= "returnurl=" . urlencode($returnurl) . "&";
        $requestPath .= "typedevice=" . $typedevice . "&";
        $requestPath .= "openflagString=" . $openflagString;
		if (strlen($sealname) > 0) {
			$requestPath .= "&sealname=" . urlencode($sealname);
		}
		if (strlen($pushurl) > 0) {
			$requestPath .= "&pushurl=" . urlencode($pushurl);
		}
        $path = $requestPath;
        
        return $this->_host . $path;
    }
	
	/**
     * 手动签名接口.
     * 
     * @param fsid
     * @param email
     * @param pagenum
     * @param signx
     * @param signy
     * @param returnurl
     * @param typedevice
     * @param openflagString
     * @return
     */
    public function getSignPageSignimagePcNy($fsid, $email, $pagenum, $signx, $signy, $returnurl, $typedevice = Constants::DEVICE_TYPE_PC, $openflagString = true, $sealname = '', $pushurl = '')
    {
        $method = __FUNCTION__ . ".json";
        $path = "/openpageny/" . $method;
        
        $openflagString = $openflagString ? "1" : "0";
        
        //sign data (don't sign $pushurl)
		if (strlen($sealname) < 1) {
			$sign_data = $this->_getSignData($method, $this->_mid, $fsid, $email, "$pagenum", "$signx", "$signy", $returnurl, "$typedevice", $openflagString);
		}
        else {
			$sign_data = $this->_getSignData($method, $this->_mid, $fsid, $email, "$pagenum", "$signx", "$signy", $returnurl, "$typedevice", $openflagString, $sealname);
		}
        
        //签名串
        $sign = $this->getRsaSign($sign_data);
		
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "fsid=" . urlencode($fsid) . "&";
        $requestPath .= "email=" . urlencode($email) . "&";
        $requestPath .= "pagenum=" . "$pagenum" . "&";
        $requestPath .= "signx=" . $signx . "&";
        $requestPath .= "signy=" . $signy . "&";
        $requestPath .= "returnurl=" . urlencode($returnurl) . "&";
        $requestPath .= "typedevice=" . $typedevice . "&";
        $requestPath .= "openflagString=" . $openflagString;
		if (strlen($sealname) > 0) {
			$requestPath .= "&sealname=" . urlencode($sealname);
		}
		if (strlen($pushurl) > 0) {
			$requestPath .= "&pushurl=" . urlencode($pushurl);
		}
        $path = $requestPath;
        
        return $this->_host . $path;
    }
    
    /**
     * 自动签名接口
     * 
     * @param signid
     * @param email
     * @param pagenum
     * @param signx
     * @param signy
     * @param openflag
     * @return
     */
    public function AutoSignbyCA($signid, $email, $pagenum, $signx, $signy, $openflag = true, $sealname = '')
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        $openflag = $openflag ? "1" : "0";
        
        //post data
        $post_data['request']['content']['email'] = $email;
        $post_data['request']['content']['signid'] = $signid;
        $post_data['request']['content']['pagenum'] = $pagenum;
        $post_data['request']['content']['signx'] = $signx;
        $post_data['request']['content']['signy'] = $signy;
        $post_data['request']['content']['openflag'] = $openflag;
		$post_data['request']['content']['sealname'] = $sealname;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult2($response);
		
        return $result;
    }
	
	/**
	 * 获取合同预览地址
	 *
	 * @param $fsdid
	 * @param $status
	 * @return
	 */
	public function ViewContract($fsdid, $docid, $status = 1)
	{
		$method = __FUNCTION__ . ".page";
        $path = "/openpage/" . $method;
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, $fsdid, $docid);
        
		//签名串
        $sign = $this->getRsaSign($sign_data);
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "fsdid=" . urlencode($fsdid) . "&";
        $requestPath .= "docid=" . urlencode($docid) . "&";
        $requestPath .= "status=" . "$status";
        
        $path = $requestPath;
        
        return $this->_host . $path;
	}
	
	/**
	 * 合同zip下载
	 *
	 * @param $fsdid
	 * @param $status
	 * @return
	 */
	public function contractDownload($fsdid, $status = 3)
	{
		$method = __FUNCTION__ . ".page";
        $path = "/openpage/" . $method;
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, $fsdid, $status);
        
		//签名串
        $sign = $this->getRsaSign($sign_data);
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "fsdid=" . urlencode($fsdid) . "&";
        $requestPath .= "status=" . "$status";
        
        $path = $requestPath;
        $url = $this->_host . $path;
		$cookie_file = __DIR__ . '/.c' . microtime(true) . rand(100, 999) . '.tmp';
		$result = $this->_http_utils->get($url, array(), true, $cookie_file);
		@unlink($cookie_file);
		
		if (200 != $result['http_code']) {
			throw new \Exception("Http Request Wrong: not HTTP 200");
		}
		
		$headers = $result['headers'];
		$lines = explode("\n", $headers);
		$found_attachment = false;
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$item = explode(':', $line);
			if (count($item) < 2) {
				continue;
			}
			$name = trim($item[0]);
			$value = trim($item[1]);
			if (strcasecmp('Content-Disposition', $name) !== 0) {
				continue;
			}
			if (stripos($value, 'filename=') !== -1) {
				$found_attachment = true;
				break;
			}
		}
		
		if (!$found_attachment) {
			throw new \Exception("Can not found attachment header");
		}
		
		return $result['response'];
	}
	
	/**
	 * 合同pdf下载
	 *
	 * @param $fsdid
	 * @param $status
	 * @return
	 */
	public function contractDownloadMobile($fsdid, $status = 3)
	{
		$method = __FUNCTION__ . ".page";
        $path = "/openpage/" . $method;
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, $fsdid, $status);
        
		//签名串
        $sign = $this->getRsaSign($sign_data);
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "fsdid=" . urlencode($fsdid) . "&";
        $requestPath .= "status=" . "$status";
        
        $path = $requestPath;
        $url = $this->_host . $path;
		$cookie_file = __DIR__ . '/.c' . microtime(true) . rand(100, 999) . '.tmp';
		$result = $this->_http_utils->get($url, array(), true, $cookie_file);
		@unlink($cookie_file);
		
		if (200 != $result['http_code']) {
			throw new \Exception("Http Request Wrong: not HTTP 200");
		}
		
		$headers = $result['headers'];
		$lines = explode("\n", $headers);
		$found_attachment = false;
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$item = explode(':', $line);
			if (count($item) < 2) {
				continue;
			}
			$name = trim($item[0]);
			$value = trim($item[1]);
			if (strcasecmp('Content-Disposition', $name) !== 0) {
				continue;
			}
			if (stripos($value, 'filename=') !== -1) {
				$found_attachment = true;
				break;
			}
		}
		
		if (!$found_attachment) {
			throw new \Exception("Can not found attachment header");
		}
		
		return $result['response'];
	}
	
	/**
     * 获取用户签署合同设置
     * 
     * @param $list
     * @return
     */
    public function getContractUsersConfig(array $list)
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['list'] = $list;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult($response);
        
        return $result;
    }

	/**
	 * 发送合同参数
	 *
	 * @param $contract_id
	 * @param $email
	 * @param $user_type
	 * @param $signature_positions
	 * @param $typedevice
	 * @param $openflag
	 * @param $sealname
	 * @param $pushurl
	 * @return
	 */
	public function sendContractConfig($contract_id, $email, $mobile, $user_type = Constants::USER_TYPE_PERSONAL, $signature_positions = array(), $typedevice = Constants::DEVICE_TYPE_PC, $openflag = true, $sealname = '', $pushurl = '')
	{
		$method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
		
		$openflag = $openflag ? "1" : "0";
        
        //post data
		$post_data['request']['content']['docId'] = $contract_id;
        $post_data['request']['content']['email'] = $email;
        $post_data['request']['content']['mobile'] = $mobile;
        $post_data['request']['content']['usertype'] = $user_type;
        $post_data['request']['content']['signaturePosition'] = $signature_positions;
        $post_data['request']['content']['typedevice'] = $typedevice;
        $post_data['request']['content']['openflag'] = $openflag;
		$post_data['request']['content']['sealname'] = $sealname;
		$post_data['request']['content']['pushurl'] = $pushurl;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
		$result = $this->_parseExecuteResult($response);
		
        return $result;
	}
	
	/**
	 * 删除合同文档
	 *
	 * @param $contract_id
	 * @return
	 */
	public function delContractDoc($contract_id)
	{
		$method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
		$post_data['request']['content']['contractId'] = $contract_id;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
		$result = $this->_parseExecuteResult($response);
		
        return $result;
	}
	
	public function templateCreate($rtick, $uid)
	{
		$method = "/template/create/";
        $path = $method;
		
		//sign data
        $sign_data = $this->_getSignData($method, $this->_mid, $rtick, $uid);
        
		//签名串
        $sign = $this->getRsaSign($sign_data);
        
        $requestPath  = "{$path}?";
        $requestPath .= "mid=" . urlencode($this->_mid) . "&";
        $requestPath .= "sign=" . urlencode($sign) . "&";
        $requestPath .= "rtick=" . urlencode($rtick) . "&";
        $requestPath .= "uid=" . urlencode($uid);
        
        $path = $requestPath;
        
        return $this->_host . $path;
	}
    
    /**
     * 证书申请
     * 
     * @param $ca_type
     * @param $name
     * @param $password
     * @param $link_man
     * @param $link_mobile
     * @param $email
     * @param $address
     * @param $province
     * @param $city
     * @param $link_id_code
     * @param $ic_code
     * @param $org_code
     * @param $tax_code
     * @return
     */
    public function certificateApply($ca_type, $name, $password, $link_man, $link_mobile, $email, $address, $province, $city, $link_id_code = '', $ident_type = Constants::CERT_IDENT_TYPE_PERSONAL_ID_CARD, $ic_code = '', $org_code = '', $tax_code = '')
    {
        if ($ca_type == Constants::CA_TYPE_CFCA)
		{
            if (!empty($ic_code)) {
                $user_type = Constants::USER_TYPE_ENTERPRISE;
                $ident_type = Constants::CERT_IDENT_TYPE_ENTERPRISE_BIZ_LICENCES;
                $ident_no = $ic_code;
            }
            else if (!empty($org_code)) {
                $ident_type = Constants::CERT_IDENT_TYPE_ENTERPRISE_ORG_CODE_CERT;
                $ident_no = $org_code;
            }
            else if (!empty($tax_code)) {
                $ident_type = Constants::CERT_IDENT_TYPE_ENTERPRISE_TAX_REG_CERT;
                $ident_no = $tax_code;
            }
            else {
                $ident_type = Constants::CERT_IDENT_TYPE_PERSONAL_ID_CARD;
                $ident_no = $link_id_code;
                $user_type = Constants::USER_TYPE_PERSONAL;
            }
            return $this->cfcaCertificateApply($name, $password, $email, $link_mobile, $address, $ident_no, $ident_type, $user_type);
        }
        else
		{
            if (!empty($ic_code) || !empty($org_code) || !empty($tax_code))
            {
                $user_type = Constants::USER_TYPE_ENTERPRISE;
            }
            else {
                $user_type = Constants::USER_TYPE_PERSONAL;
            }
            return $this->zjcaCertificateApply($name, $password, $email, $link_man, $link_mobile, $address, $province, $city, $link_id_code, $ic_code, $org_code, $tax_code, $user_type);
        }
    }
    
    /**
     * CFCA证书申请
     * 
     * @param name
     * @param password
     * @param email
     * @param linkMobile
     * @param address
     * @param identNo
     * @param identType
     * @param userType
     * @param duration
     * @param certificateType
     * @return
     */
    private function cfcaCertificateApply($name, $password, $email, $linkMobile, $address, $identNo, $identType = Constants::CERT_IDENT_TYPE_PERSONAL_ID_CARD, $userType = Constants::USER_TYPE_PERSONAL, $duration = 24, $certificateType = Constants::CERT_TYPE_NORMAL)
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['userType'] = $userType;
        $post_data['request']['content']['name'] = $name;
        $post_data['request']['content']['password'] = $password;
        $post_data['request']['content']['certificateType'] = $certificateType;
        $post_data['request']['content']['identType'] = $identType;
        $post_data['request']['content']['identNo'] = $identNo;
        $post_data['request']['content']['duration'] = $duration;
        $post_data['request']['content']['address'] = $address;
        $post_data['request']['content']['linkMobile'] = $linkMobile;
        $post_data['request']['content']['email'] = $email;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult2($response);
        if (isset($result['cerNo']))
		{
			unset($result['cerNo']);
		}
		
        return $result;
    }
    
    /**
     * 浙江CA证书申请
     * 
     * @param name
     * @param password
     * @param email
     * @param linkMan
     * @param linkMobile
     * @param address
     * @param province
     * @param city
     * @param linkIdCode
     * @param icCode
     * @param orgCode
     * @param taxCode
     * @param userType
     * @return
     */
    private function zjcaCertificateApply($name, $password, $email, $linkMan, $linkMobile, $address, $province, $city, $linkIdCode, $icCode, $orgCode, $taxCode, $userType = Constants::USER_TYPE_PERSONAL)
    {
        $method = __FUNCTION__ . ".json";
        $path = "/open/" . $method;
        
        //post data
        $post_data['request']['content']['userType'] = $userType;
        $post_data['request']['content']['name'] = $name;
        $post_data['request']['content']['password'] = $password;
        $post_data['request']['content']['linkIdCode'] = $linkIdCode;
        $post_data['request']['content']['icCode'] = $icCode;
        $post_data['request']['content']['linkMan'] = $linkMan;
        $post_data['request']['content']['orgCode'] = $orgCode;
        $post_data['request']['content']['taxCode'] = $taxCode;
        $post_data['request']['content']['province'] = $province;
        $post_data['request']['content']['city'] = $city;
        $post_data['request']['content']['address'] = $address;
        $post_data['request']['content']['linkMobile'] = $linkMobile;
        $post_data['request']['content']['email'] = $email;
        $post_data = json_encode($post_data);
        
        //sign data
        $sign_data = $this->_getSignData($method, $this->_mid, md5($post_data));
        
        //header data
        $header_data = array();
        
        //content
        $response = $this->execute('POST', $path, $post_data, $sign_data, $header_data, true);
        $result = $this->_parseExecuteResult2($response);
		if (isset($result['cerNo']))
		{
			unset($result['cerNo']);
		}
		
        return $result;
    }
    
    //执行请求
    public function execute($method, $path, $post_data = null, $sign_data = '', array $header_data = array(), $auto_redirect = true, $cookie_file = null)
    {
        $response = $this->request($method, $path, $post_data, $sign_data, $header_data, $auto_redirect, $cookie_file);
        $response_body = $response["response"];
        $result = @json_decode($response_body, true);
        $response['result'] = $result;
        return $response;
    }
    
    //发送请求
    public function request($method, $path, $post_data = null, $sign_data = '', array $header_data = array(), $auto_redirect = true, $cookie_file = null)
    {
        $url = $this->_host . $path;
        
        //签名串
        $sign = $this->getRsaSign($sign_data);
        
        $header_data['mid'] = $this->_mid;
        $header_data['sign'] = $sign;
        
        //headers
        $headers = array();
        $headers[] = 'Content-Type: application/json; charset=UTF-8';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Connection: keep-alive';
        foreach ($header_data as $name => $value)
        {
            $line = $name . ': ' . rawurlencode($value);
            $headers[] = $line;
        }
        
        if (strcasecmp('POST', $method) == 0)
        {
            $ret = $this->_http_utils->post($url, $post_data, null, $headers, $auto_redirect, $cookie_file);
        }
        else
        {
            $ret = $this->_http_utils->get($url, $headers, $auto_redirect, $cookie_file);
        }
        return $ret;
    }
    
    /**
     * 解析 execute_result
     * @param execute_result
     * @return
     */
    private function _parseExecuteResult($execute_response)
    {
        if (!isset($execute_response['result']['response']['info']['code']))
		{
            throw new \Exception("execute response format wrong: no ['result'][response][info][code] field", -1);
        }
        $code = $execute_response['result']['response']['info']['code'];
        if ($code != 100000)
		{
            throw new \Exception("execute wrong, code: {$code}", $code);
        }
        return $execute_response['result'];
    }
    
    private function _parseExecuteResult2($execute_response)
    {
        //get result
        if (!isset($execute_response['result'])) {
            throw new \Exception("execute response format wrong: no [result] field", -1);
        }
        return $execute_response['result'];
    }
    
    private function _getSignData()
    {
        if (func_num_args() == 0)
        {
            return "";
        }
        $sign_data = func_get_args();
        $sign_data = trim(implode("\n", $sign_data));
        return $sign_data;
    }
    
    private function _formatPem($rsa_pem, $pem_type = '')
    {
        //如果是文件, 返回内容
        if (is_file($rsa_pem))
        {
            return file_get_contents($rsa_pem);
        }
        
        //如果是完整的证书文件内容, 直接返回
        $rsa_pem = trim($rsa_pem);
        $lines = explode("\n", $rsa_pem);
        if (count($lines) > 1)
        {
            return $rsa_pem;
        }
        
        //只有证书内容, 需要格式化成证书格式
        $pem = '';
        for ($i = 0; $i < strlen($rsa_pem); $i++)
        {
            $ch = substr($rsa_pem, $i, 1);
            $pem .= $ch;
            if (($i + 1) % 64 == 0)
            {
                $pem .= "\n";
            }
        }
        $pem = trim($pem);
        if (0 == strcasecmp('RSA', $pem_type))
        {
            $pem = "-----BEGIN RSA PRIVATE KEY-----\n{$pem}\n-----END RSA PRIVATE KEY-----\n";
        }
        else
        {
            $pem = "-----BEGIN PRIVATE KEY-----\n{$pem}\n-----END PRIVATE KEY-----\n";
        }
        return $pem;
    }
	
	private function _getCookieFileName()
	{
		$tmpname = tempnam('/tmp/', 'bss_cookie_');
		@unlink($tmpname);
		return $tmpname;
	}
}