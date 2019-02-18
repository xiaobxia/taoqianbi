<?php
namespace common\helpers\messages;

use Yii;
use common\base\LogChannel;

class WeiWangSms extends BaseSms
{

    private  $_sprdid;   //产品编号
    /**
     * @desc
     * @param string $baseUrl  请求地址   对应文档 APIURL
     * @param string $userName 用户名     对应文档nonce_str
     * @param string $password 密码       对应文档app_secret
     * @param string $extArr   扩展参数
     * @return
     */
    public function __construct($baseUrl, $userName, $password, $extArr = '', $smsServiceUse = '')
    {
        $this->_baseUrl    = $baseUrl;
        $this->_userName   = $userName;
        $this->_password   = $password;
        $this->_extArr	   = $extArr;
        $this->_smsServiceUse   = $smsServiceUse;
        $this->_sprdid = $extArr['prdid'];
        $this->_timeStamp = date("YmdHis");
    }

    public function getSmsId()
    {
        return $this->_smsId;
    }

    /**
     * @desc
     * @param array $phone
     * @param string $message
     * @return
     */
    public function sendSMS($phone, $message, $name = APP_NAMES)
    {
        $msg = urlencode($message);
        $url = $this->_baseUrl;
        $uid = $this->_userName;
        $pwd = $this->_password;
        $sprdid  = $this->_sprdid;
        $result = \file_get_contents("{$url}?sname={$uid}&spwd={$pwd}&scorpid=&sprdid={$sprdid}&sdst={$phone}&smsg={$msg}");
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
        $result = (array)$result;
        if (isset($result['State']) && $result['State'] == 0) {
            return $result["MsgID"];
        }
        return false;
    }

    #请求数据和接收数据大集合
    public function getRequestReturnCollect()
    {
        return array('url'=>$this->_baseUrl,'raw'=>$this->_raw,'return'=>$this->_return);
    }


    #取得余额
    public function balance()
    {
        return $this->_return;
    }


    public function acceptReport()
    {

    }

    public function collectReport()
    {

    }

}
