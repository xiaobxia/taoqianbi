<?php

//	禁用错误报告
//	error_reporting(0);
//	报告运行时错误
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
//	报告所有错误
//	error_reporting(E_ALL);

//	===== 密钥配置 =====
//	商户的私钥的绝对路径
    define("wzd_mer_id", '25116');

	$commonDir = Yii::getAlias('@common');
	define("privatekey", $commonDir.'/config/cert/25116_wenzhoudai.key.pem');
//	商户私钥配置地址,如果需配置多个商户配置私钥地址，则按如下规则添加
//	global $mer_pk ;//= array();

	$mer_pk = array();
    $mer_pk[wzd_mer_id] = privatekey;

//	UMPAY的平台证书路径
	define("platcert", $commonDir.'/config/cert/cert_2d59.cert.pem');

//	日志生成目录
	define("logpath",Yii::$app->basePath."/runtime/logs/pay.log");

//	记录日志文件的同时是否在页面输出:要输出为true,否则为false
	define("log_echo",false);

//	UMPAY平台地址,无需修改
	define("plat_url","http://pay.soopay.net");

//	支付产品名称:无需修改
	define("plat_pay_product_name","spay");
	return $mer_pk;
?>
