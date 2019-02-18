<?php
namespace common\api\fadada;

/**
*
* PHP��3DES�ӽ�����
*
* ����java��3DES(DESede)���ܷ�ʽ����
*
*/
class Crypt3Des {
	/**
	 * ʹ��pkcs7�������
	 * @param unknown $input
	 * @return string
	 */
	static function PaddingPKCS7($input) {
		$srcdata = $input;
		$block_size = mcrypt_get_block_size ( 'tripledes', 'ecb' );
		$padding_char = $block_size - (strlen ( $input ) % $block_size);
		$srcdata .= str_repeat ( chr ( $padding_char ), $padding_char );
		return $srcdata;
	}
	
	/**
	 * 3des����
	 * @param  $string ����ܵ��ַ�
	 * @param  $key �����õ���Կ
	 * @return string
	 */
	static function encrypt($string, $key) {
		$string = self::PaddingPKCS7 ( $string );
		
		// ���ܷ���
		$cipher_alg = MCRYPT_TRIPLEDES;
		// ��ʼ�����������Ӱ�ȫ��
		$iv = mcrypt_create_iv ( mcrypt_get_iv_size ( $cipher_alg, MCRYPT_MODE_ECB ), MCRYPT_RAND );
		
		$encrypted_string = mcrypt_encrypt ( $cipher_alg, $key, $string, MCRYPT_MODE_ECB, $iv );
		$des3 = bin2hex ( $encrypted_string ); // ת����16����
		
		//echo $des3 . "</br>";
		return $des3;
	}
}

// ��ʼ64λ����
// $base64=base64_encode($spid."$".$des3);
// echo "base64:".$base64."<br>";
?>