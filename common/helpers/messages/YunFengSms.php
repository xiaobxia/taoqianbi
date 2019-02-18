<?php
namespace common\helpers\messages;

use Yii;

class YunFengSms extends BaseSms {
    protected $_smsType     = 'verify_code';

    protected $_raw;

    /**
    * @desc
    * @param string $baseUrl  请求地址   对应文档 APIURL
    * @param string $userName 用户名       对应文档nonce_str
    * @param string $password 密码            对应文档app_secret
    * @param string $extArr   扩展参数  当前仅仅包含privateKey 对应文档 app_key
    * @return
    */
    public function __construct($baseUrl, $userName, $password, $extArr = '', $smsServiceUse = '')
    {
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_password   = $password;
        $this->_privateKey = $extArr['appkey'];
        $this->_smsServiceUse   = $smsServiceUse;

        $this->_timeStamp = date("YmdHis");
        $this->_smsId  = empty($this->_smsId) ? $this->_userName . rand(100000, 999999) . time() : $this->_smsId;
    }

    public function getSmsId()
    {
        return $this->_smsId;
    }

    /**
    * @desc 公钥算法:1、参数名ASCII码从小到大排序（字典序）;
    *               2、参数名区分大小写；
    *               3、多个dest_id和mission_num没有先后顺序
    *               4、拼接app_secret进行md5
    * @param unknowtype
    * @return
    */
    private function __createSendSmsKey($mobileArr, $messageStr)
    {
        $string = "app_key=".$this->_privateKey."&".
                  "batch_num=".$this->_smsId."&".
                  "content=".$messageStr."&";

        foreach ($mobileArr as $k=>$v)
        {
            $string = $string."dest_id=".$v."&";
        }
        foreach ($mobileArr as $k => $v)
        {
            $k++;
            $string = $string."mission_num=".$k."&";
        }
        $string = $string."nonce_str=".$this->_userName."&".
                          "sms_type=".$this->_smsType."&".
                          "time_stamp=".$this->_timeStamp."&";
        $string = $string."app_secret=".$this->_password;
        $sign = md5($string);
        $this->_sign = $sign;
        return $sign;
    }

    private function __createPublicKey($paramsArr = array())
    {
        $headArr['app_key']    = $this->_privateKey;
        $headArr['time_stamp'] = $this->_timeStamp;
        $headArr['nonce_str']  = $this->_userName;

        $paramsArr = array_merge($headArr, $paramsArr);
        ksort($paramsArr);
        $queryStr  = http_build_query ( $paramsArr);

        $this->_sign = md5($queryStr."&"."app_secret=".$this->_password);
        return $this->_sign;
    }

    private function __createSendSmsRaw($mobileArr, $messageStr)
    {
        if(empty($this->_smsId))
        {
            throw new \Exception("请先进行setUserName操作!");
        }

        $raw  = "<?xml version='1.0' encoding='UTF-8'?>";
        $raw .= '<xml>';
        $raw .= "<head>";
        $raw .= "<app_key>{$this->_privateKey}</app_key>";
        $raw .= "<time_stamp>".$this->_timeStamp."</time_stamp>";
        $raw .= "<nonce_str>{$this->_userName}</nonce_str>";
        $raw .= "<sign>".$this->__createSendSmsKey($mobileArr, $messageStr)."</sign>";
        $raw .= "</head>";
        $raw .= "<body>";
        $raw .= "<dests>";
        foreach ($mobileArr as $k => $v)
        {
            $k++;
            $raw .= "<dest>";
            $raw .= "<mission_num>".$k."</mission_num>";
            $raw .= "<dest_id>{$v}</dest_id>";
            $raw .= "</dest>";
        }
        $raw .= "</dests>";
        $raw .= "<batch_num>{$this->_smsId}</batch_num>";
        $raw .= "<sms_type>{$this->_smsType}</sms_type>";
        $raw .= "<content>{$messageStr}</content>";
        $raw .= "</body>";
        $raw .= "</xml>";
        return $raw;
    }

    /**
    * @desc
    * @param array $mobileArr
    * @param string $messageStr ansi编码字符串
    * @return
    */
    public function sendSMS($mobileArr, $message, $name = APP_NAMES)
    {
        if (is_string($mobileArr)) {
            $mobileArr = [$mobileArr];
        }
        $this->_raw = $this->__createSendSmsRaw($mobileArr, $message);
        $this->_return = Curl::curlXml($this->_baseUrl . '/manySend', $this->_raw);
        return $this->returnResult();
    }

    private function returnResult()
    {
        $result =  (array)simplexml_load_string($this->_return);
        if (isset($result['head'])) {
            $error_code = (array)$result['head']->error_code;
            if ($error_code[0] == '000000') {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    #请求数据和接收数据大集合
    public function getRequestReturnCollect()
    {
        return array('url'=>$this->_baseUrl,'raw'=>$this->_raw,'return'=>$this->_return);
    }

    private function __createBalanceRaw()
    {
        $raw  = "<?xml version='1.0' encoding='UTF-8'?>";
        $raw .= '<xml>';
        $raw .= "<head>";
        $raw .= "<app_key>{$this->_privateKey}</app_key>";
        $raw .= "<time_stamp>".$this->_timeStamp."</time_stamp>";
        $raw .= "<nonce_str>{$this->_userName}</nonce_str>";
        $raw .= "<sign>".$this->__createPublicKey()."</sign>";
        $raw .= "</head>";
        $raw .= "</xml>";
        return $raw;
    }

    #取得余额
    public function balance()
    {
        $url = "http://".parse_url($this->_baseUrl."/getSmsCount",PHP_URL_HOST )."/stardy/balance_jy.jsp?"."&usr=".$this->_userName."&pwd=".$this->_password;
        $this->_raw = $this->__createBalanceRaw();
        $this->_return = Curl::curlXml($this->_baseUrl."/getSmsCount", $this->_raw);
        return $this->_return;
    }



    public function acceptReport()
    {

    }

    public function collectReport()
    {

    }

}

class Curl
{
    static function curlXml($url,$xmlData)
    {
        //初始一个curl会话
        $curl = curl_init();
        //设置url
        curl_setopt($curl, CURLOPT_URL,$url);
        //设置发送方式：
        curl_setopt($curl, CURLOPT_POST, true);
        //设置发送数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        //抓取URL并把它传递给浏览器
        $return = curl_exec($curl);
        //关闭cURL资源，并且释放系统资源
        curl_close($curl);
        return $return;
    }

    static function curlSpider($url)
    {
        $ch  =  curl_init ();

        // 设置URL和相应的选项
        curl_setopt ( $ch ,  CURLOPT_URL ,  $url );
        curl_setopt ( $ch ,  CURLOPT_HEADER ,  0 );
        curl_setopt ( $ch ,  CURLOPT_RETURNTRANSFER ,  true );

        // 抓取URL并把它传递给浏览器
        $result = curl_exec ( $ch );

        // 关闭cURL资源，并且释放系统资源
        curl_close ( $ch );
        return $result;
    }
}
