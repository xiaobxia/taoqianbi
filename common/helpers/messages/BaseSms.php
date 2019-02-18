<?php
namespace common\helpers\messages;

class BaseSms extends \yii\base\Object implements InterfaceSms {

    #请求路径
    protected $_baseUrl;

    #用户名
    protected $_userName;

    #密码
    protected $_password;

    #私钥
    protected $_privateKey;

    #时间戳
    protected $_timeStamp;

    #运营商短信id
    protected $_smsId;

    #运营商数据返回
    protected $_return;

    #签名数据
    protected $_sign;

    #扩展参数
    protected $_extArr;

    #所用配置
    protected $_smsServiceUse;

    protected $_raw;

    // 所有短信接口超时时间
    public static $timeout = 5;
    public static $ctx_params = array(
        'http' => array(
            'timeout' => 5
        )
    );

    public function __construct($baseUrl, $userName, $password, $extArr = '', $smsServiceUse = '')
    {
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_password   = $password;
        $this->_privateKey = $extArr['privateKey'];
        $this->_extArr   = $extArr;
        $this->_smsServiceUse   = $smsServiceUse;

        $this->_timeStamp = date("YmdHis");
        $this->_smsId  = empty($this->_smsId) ? $this->_userName . rand(100000, 999999) . time() : $this->_smsId;
    }

    #取得批次号
    public function getSmsId()
    {
        return $this->_smsId;
    }

    public function getRequestReturnCollect()
    {

    }

    public function sendSMS($mobileArr, $message, $name)
    {

    }

    public function balance()
    {

    }

    public function acceptReport()
    {

    }

    public function collectReport()
    {

    }

}
