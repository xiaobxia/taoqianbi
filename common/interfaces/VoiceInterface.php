<?php
/**
 *语音接口
 */
namespace common\interfaces;

interface VoiceInterface{

	/**
     *根据手机号，产品类型，生成短信通道所需参数
     *@param string $phone 接收手机号
     *@param int $type 产品类型
     */
	public function get_params($phone, $type);


	/**
     *根据返回值，解析发送结果
     *@param string $response 短信通道返回结果
     */
	public function trans_code($response);


	/**
     *根据错误码，返回错误描述
     */
    public function error_no($errno);


	/**
	 *发送语音日志记录
	 */
	public function voice_log();



}