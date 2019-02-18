<?php
/**
 *短信接口
 */
namespace common\interfaces;

interface SMSInterface{

	/**
	 *发送短信
	 */
	public function sendSMS();

	/**
	 *发送短信日志记录
	 */
	public function smsLog();


}