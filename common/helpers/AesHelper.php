<?php
namespace common\helpers;
//php aes加密类
class AesHelper {

    public function encryptString($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = AesHelper::pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    private static function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function decryptString($sStr, $sKey) {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $decrypted= @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, base64_decode($sStr), MCRYPT_MODE_ECB, $iv);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

//    public $iv = null;
//    public $key = null;
//    public $bit = 128;
//    private $cipher;
//    public function __construct($bit, $key, $iv, $mode) {
//        if(empty($bit) || empty($key) || empty($iv) || empty($mode))
//            return NULL;
//        $this->bit = $bit;
//        $this->key = $key;
//        $this->iv = $iv;
//        $this->mode = $mode;
//        switch($this->bit) {
//            case 192:$this->cipher = MCRYPT_RIJNDAEL_192; break;
//            case 256:$this->cipher = MCRYPT_RIJNDAEL_256; break;
//            default: $this->cipher = MCRYPT_RIJNDAEL_128;
//        }
//        switch($this->mode) {
//            case 'ecb':$this->mode = MCRYPT_MODE_ECB; break;
//            case 'cfb':$this->mode = MCRYPT_MODE_CFB; break;
//            case 'ofb':$this->mode = MCRYPT_MODE_OFB; break;
//            case 'nofb':$this->mode = MCRYPT_MODE_NOFB; break;
//            default: $this->mode = MCRYPT_MODE_CBC;
//        }
//    }
//    public function encrypt($data) {
//        $data = base64_encode(mcrypt_encrypt( $this->cipher, $this->key, $data, $this->mode, $this->iv));
//        return $data;
//    }
//    public function decrypt($data) {
//        $data = mcrypt_decrypt( $this->cipher, $this->key, base64_decode($data), $this->mode, $this->iv);
//        //$data = rtrim(rtrim($data), "..");
//        return $data;
//    }
}