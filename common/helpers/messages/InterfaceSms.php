<?php
namespace common\helpers\messages;

interface InterfaceSms {

    #短信发送和接收数据
    public function getRequestReturnCollect();

    /**
    * @desc
    * @access
    * @param array $mobileArr   手机号码
    * @param string $message 短信内容
    * @param string $name 短信签名
    * @return
    */
    public function sendSMS($mobileArr, $message, $name);

    /**
     * 获取短信余额查询
     * @return [type] [description]
     */
    public function balance();

    /**
     * 收取短信报告
     * @return [type] [description]
     */
    public function acceptReport();

    /**
     * 收取用户上行
     * @return [type] [description]
     */
    public function collectReport();
}
