<?php

namespace common\services;

use common\models\LoanPerson;
use yii\base\Component;
use common\helpers\CurlHelper;
use common\models\CreditLzf;
use yii\base\Exception;

/**
 * 助益 孚临灵芝分
 */
class LzfService extends Component
{
    const BASE_URL = 'http://saas.fullink.tech/';
    const CUSTOMERID = 'HJGS1902021147';
    const CUSTOMERPRODID ='PROD190233034758926193';
    const ENCRYPTKEY = 'iAKwkCPN';

    public function getScore(LoanPerson $loanPerson){
        if (empty($loanPerson->name) || empty($loanPerson->id_number) || empty($loanPerson->phone)) {
            throw new \Exception('缺少必要的用户信息');
        }
        $url = self::BASE_URL . 'eaglehorn-application/api/v1/lzf/report/customizedGeneral';
        $data = [
            'customerId' => self::CUSTOMERID,
            'customerProdId' => self::CUSTOMERPRODID,
            'name' => $loanPerson->name,
            'mobile' => $loanPerson->phone,
            'idCardNo' => $loanPerson->id_number,
            'timestamp' => isset($loanPerson->created_at) ? $loanPerson->created_at : time() . '001',
        ];

        $signData = [];
        foreach ($data as $key => $v) {
            $signData[] = $key . '=' . $v;
        }
        $str = implode('&', $signData);
        $sign = $this->encrypt($str);

        $data['sign'] = $sign;

        $res = CurlHelper::curlHttp($url, 'CS_POST', json_encode($data, JSON_UNESCAPED_UNICODE), 30);
//            echo '请求加密字符串：' . var_export($str, 1) . "\r\n";
//            echo '请求原始数据：' . var_export($data, 1) . "\r\n";
//            echo '请求JSON数据：' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\r\n";
//            echo '请求地址：' . $url . "\r\n";
//            echo '响应结果：' . var_export($res, 1) . "\r\n";

        if (empty($res)) {
            throw new \Exception("返回为空，确认是否网络原因！");
        }

        if (!isset($res['status']) || $res['status'] != 200) {
            throw new \Exception('孚临请求成功，响应错误，错误信息：' . var_export($res, 1));
        }

        $resData = isset($res['data']) ? $res['data'] : [];
        if (empty($resData)) {
            throw new \Exception('孚临请求成功，响应数据为空，错误信息：' . var_export($res, 1));
        }

        $arr_json = $this->decrypt($resData);
        $decryptData = json_decode($arr_json, 1);
//            echo '解密后的数据：' . var_export($decryptData, 1) . "\r\n";

        if (!isset($decryptData['score'])) {
            throw new \Exception('孚临请求成功，数据解密失败，错误信息：' . var_export($decryptData, 1));
        }
        $credit_lxf = CreditLzf::find()->where(['person_id'=>$loanPerson->id])->one();
        if(!$credit_lxf){
            $credit_lxf =  new CreditLzf();
            $credit_lxf->person_id = $loanPerson->id;
        }
        $credit_lxf->id_number = $loanPerson->id_number;
        $credit_lxf->data = $arr_json;
        $credit_lxf->score = $decryptData['score'];

        if(!$credit_lxf->save()){
            throw new Exception("credit_lzf保存失败");
        }
        return $decryptData;
    }


    //加密
    private function encrypt($str)
    {
        $size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );
        $str = $this->Pkcs5Pad ( $str, $size );
        $data = strtoupper(bin2hex(mcrypt_encrypt(MCRYPT_DES,self::ENCRYPTKEY, $str,  MCRYPT_MODE_CBC, self::ENCRYPTKEY)));
        return $data;
    }

    //解密
    private function decrypt($str)
    {
        $str = hex2bin($str);
        $str = mcrypt_decrypt(MCRYPT_DES, self::ENCRYPTKEY, $str, "cbc", self::ENCRYPTKEY );
        $str = $this->pkcs5Unpad( $str );
        return $str;
    }

    private function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }

    private function pkcs5Unpad($text)
    {
        $pad = ord ( $text {strlen ( $text ) - 1} );
        if ($pad > strlen ( $text ))
            return false;
        if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
            return false;
        return substr ( $text, 0, - 1 * $pad );
    }
}