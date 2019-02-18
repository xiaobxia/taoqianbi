<?php
/**
 * 与小赢理财投资批改接口对接
 * 
 */
namespace common\api\xiaoying;

require('Encryption.php');

class XiaoyingInvest{
	
	//将对接数据传给小赢
	public static function sendDataToXY($data){
	    $partnerId = '100026';
	    $url = 'https://www.xiaoying.com/api/apiInvest/invest';
	    
		$data['partnerId'] = $partnerId;

		$Encryption = new Encryption();

		//加密
		$data = $Encryption->encrypt($data, $partnerId);
        
		//传输
		$result = Util::send_post($url, array_merge($data, array('partner'=>$partnerId)));
		
		//解密返回结果
		$result = $Encryption->decrypt(json_decode($result, true), $partnerId);

		return $result;
	}
}