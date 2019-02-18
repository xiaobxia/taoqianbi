<?php
/**
* 	配置账号信息 2015-05-13 13:16:33
* 	配置文件 
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wx7e15aaace68e5c30';
	//受理商ID，身份标识
	const MCHID = '1318886501';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = 'jddken272hwhxiwyfjztqkGwh21H61pq';//'a3454be17dce99ed7188b7ac524cd8cd';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = 'a3454be17dce99ed7188b7ac524cd8cd';//'jddken272hwhxiwyfjztqkGwh21H61pq';
	
	//=======【JSAPI路径设置】===================================
	
	
	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
	const SSLCERT_PATH = '/cacert/apiclient_cert.pem';
	const SSLKEY_PATH = '/cacert/apiclient_key.pem';
	const SSLROOTCA_PATH  = '/cacert/rootca.pem';
	
	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;

	//=======【idea云平台签名密钥】===================================
	//在云平台 配置的 签名密钥 可不填
	const privateKey = "test";

	// 【接口调用域名白名单】
	const OAUTH_HOST = "|xx|xx|";	

	// 开启接收调试信息的模式
	const DEBUG = false;

}

?>
