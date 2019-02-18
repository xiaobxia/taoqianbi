<?php

namespace common\models\encrypt;

use Yii;

use common\models\encrypt\EncryptKeys;

class RsaEncrypt extends EncryptKeys
{
	/**
	 * 2048位密钥分段解析的长度
	 */
	const ENCRYPT_SEGMENT_BITS_2048 = 245;
	const DECRYPT_SEGMENT_BITS_2048 = 256;

	/**
	 * 密钥类型
	 */
	const ENCRYPT_TYPE_PRIVATE_KEY = "openssl_private_encrypt";
	const ENCRYPT_TYPE_PUBLIC_KEY = "openssl_public_encrypt";
	const DECRYPT_TYPE_PRIVATE_KEY = "openssl_private_decrypt";
	const DECRYPT_TYPE_PUBLIC_KEY = "openssl_public_decrypt";

	/**
	 * 生成密钥
	 */
	public function generateKeys(){

		//生成密钥数, 每次随机
        $count = rand(10, 20);

        //缓存密钥, 批量插入
        $keys = [];

        //私钥临时文件的路径
        $private_key_file_path = dirname(__FILE__).'/keys/rsa_private_key.pem';

        //公钥临时文件的路径
        $public_key_file_path = dirname(__FILE__).'/keys/rsa_public_key.pem';

        for ($i = 0; $i < $count; $i++) {
        	
            exec("openssl genrsa -out ".$private_key_file_path." 2048", $out, $status);
            if($status == 1){
                return "生成第 $i 个私钥失败!";
            }
            exec("openssl rsa -in ".$private_key_file_path." -pubout -out ".$public_key_file_path, $out, $status);
            if($status == 1){
                return "生成第 $i 个公钥失败!";
            }

            if(!extension_loaded('openssl')){
				return 'PHP需要OPENSSL扩展支持';  
            }
          
          	if(!file_exists($private_key_file_path) || !file_exists($public_key_file_path)){
          		return '私钥或者公钥的文件路径不存在';
          	}

            $private_key = file_get_contents($private_key_file_path);

            $public_key = file_get_contents($public_key_file_path);

            if(empty($private_key) || empty($public_key)){
                return '密钥文件内容为空！';
            }

            $temp = [];
            $temp['private_key'] = $private_key;
            $temp['public_key'] = $public_key;
            $temp['encrypt_type'] = 'RSA';
            $temp['encrypt_bits'] = '2048';
            $temp['create_time'] = date('Y-m-d H:i:s');

            $keys[] = $temp;

            exec("rm -f ".$private_key_file_path, $out, $status);
            if($status == 1){
                return "删除第 $i 个私钥失败!";
            }
            exec("rm -f ".$private_key_file_path, $out, $status);
            if($status == 1){
                return "删除第 $i 个公钥失败!";
            }
        }

        if (($tempModel = self::find()->where(['state'=>self::STATE_USABLE])->one()) != null) {
            self::updateAll(['state'=>self::STATE_DISABLE], ['state'=>self::STATE_USABLE]);
        }
        if(!empty($keys)){
            self::getDb()->createCommand()
            ->batchInsert(self::tableName(), 
                ['private_key', 'public_key', 'encrypt_type', 'encrypt_bits', 'create_time'], $keys)
            ->execute();
        }
        return "密钥替换成功!";
	}

	/**
	 * 分配密钥
	 */
	public function getKeysByHash($value){

		$result = self::find()->where(['state'=>self::STATE_USABLE, 'status'=>self::STATUS_ACTIVE])->all();

		$count = count($result);

		if($count <= 0) return false;

		$index = intval($value) % $count;

		$encryptKey = $result[$index];

		return $this->encapsulate($encryptKey);
	}

	public function getKeysById($id){

		$encryptKey = self::findOne($id);

		return $this->encapsulate($encryptKey);
	}

	/**
	 * 包装一下
	 */
	private function encapsulate($encryptKey){

		$private_key_content = $encryptKey->private_key;

		$public_key_content = $encryptKey->public_key;

		//生成Resource类型的密钥，如果密钥文件内容被破坏，openssl_pkey_get_private函数返回false  
		$private_key = openssl_pkey_get_private($private_key_content);

		//生成Resource类型的公钥，如果公钥文件内容被破坏，openssl_pkey_get_public函数返回false 
		$public_key = openssl_pkey_get_public($public_key_content);

		return [
			"id" => $encryptKey->id,
			"private_key" => $private_key,
			"public_key" => $public_key,
			"private_key_content" => $private_key_content,
			"public_key_content" => $public_key_content
		];
	}

	/**
	 * 加密
	 */
	public function encrypt($key, $encrypt_type, $segment, $originalData){

		$data = '';

        foreach (str_split($originalData, $segment) as $chunk) {

            if($encrypt_type($chunk, $encryptData, $key)){

            	$data .= $encryptData;

            }else{

            	return false;  

            }
        }

        return base64_encode($data);
	}

	/**
	 * 解密
	 */
	public function decrypt($key, $decrypt_type, $segment, $originalData){

		$data = '';

        foreach (str_split(base64_decode($originalData), $segment) as $chunk) {

            if($decrypt_type($chunk, $decryptData, $key)){

            	$data .= $decryptData;

            }else{

            	return false;

            }
        }
        return $data;
	}


	/**
	 * BKDR散列
	 */
	public function BKDRHash($str, $seed = 13131){

		// $seed = 31 131 1313 13131 131313 etc.. 

		$hash = 0;
		$length = strlen($str);

		for($i = 0; $i < $length; $i++){

			$hash = ((floatval($hash * $seed) & 0x7FFFFFFF) + ord($str[$i])) & 0x7FFFFFFF;

		} 
		
		return ($hash & 0x7FFFFFFF);
	}

}