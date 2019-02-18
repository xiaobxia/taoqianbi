<?php
/**
 * Created by PhpStorm.
 * User: ronaldpeng
 * Date: 15/12/11
 * Time: 上午10:20
 */

require('Encryption.php');

$Encryption = new Encryption();
$testData = [
		'partnerId' => '100026',
		'loanId' => '75185166705524736',
		'amount' => '10000',
		'tradeId' => '01201608160000001239',
		'name' => '张三',
		'identifyNo' => '430422199909091056',
		'mobile' => '13706666666',
		'email' => 'test@test.com',
		'isSendSms' => '0',
		'isSendEmail' => '0',
	];
/*$testData = array("amount"=>"10000","email"=>"test@test.com","identifyNo"=>"430422199909091056","isSendEmail"=>"0","isSendSms"=>"0","loanId"=>"72891619809198080","mobile"=>"13706666666","name"=>"张三","partnerId"=>"100026","tradeId"=>"01201608160000001239");*/

$data = $Encryption->encrypt($testData, '100026');

$url = 'https://www.xiaoying.com/api/apiInvest/invest';
echo "url: {$url} \r\n";
echo "request data: \r\n";
var_dump(json_encode($data));

$result = Util::send_post($url, array_merge($data, array('partner'=>'100026')));
echo "response data: \r\n";
var_dump( $result );

var_dump($Encryption->decrypt(json_decode($result, true), '100026'));
