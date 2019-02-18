<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/26 0026
 * Time: 上午 11:20
 */

namespace credit\controllers;

use common\services\fundChannel\JshbService;
use common\services\RealNameAuthService;
use common\helpers\CurlHelper;
use common\services\FinancialService;
use common\models\BankCardCheckWeb;
use common\helpers\MessageHelper;

class TestController extends BaseController
{

    public function actionMessage(){
        $phone = '17682449388';
        $sms_channel = 'smsService_TianChang_HY';
        $source_id = 21;
        $send_message = "尊敬的吴磊，您在".APP_NAMES."的款项明日到期，平台将对您尾号1234银行卡进行扣款，请确保资金充足，已还、款，请忽略。如有疑问请致电400-857-7966 退订回T";
        $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
        var_dump($ret);
    }

    public function actionHeLiBao(){
        $api = 'http://paynew.sdpurse.com:15422/';
        $card_verify_api_url = "auth_msg";
        $merchant_id = '1';
        $customParams = [
            'name' => (string)'',                                   //四要素 - 用户名
            'phone' => (string)'',                             //四要素 - 手机号
            'id_card_no' => (string)'',                  //四要素 - 身份证号
            'bank_card_no' => (string)'',                         //四要素 - 银行卡号
            'bank_id' => (string)'1',                                 //畅捷四要素鉴权需要 - 银行卡ID
            'channel_id' => 2
        ];

        //请求支付系统四要素验证-合利宝
        $type = "POST";
        $customParams['merchant_id'] = $merchant_id;
        //加密方式
        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams['sign'] = $this->getSign($customParams,$product_name);

        $url = $api. $card_verify_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);
        var_dump($ret);exit;

    }

    public function actionTest(){
//        echo YII_ENV_TEST;exit;
        echo 'lmjjay623ASDCVF';
        echo "\r\n";
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $redis->auth('lmjjay623ASDCVF');
        $redis->select(11);
//        $redis->set('testkey','hello');
        $key = $redis->get('testkey');
//        $key = $this->redis->keys('wzd_user:' . "*");
        var_dump($key);
    }

    public function actionIndex(){
        echo 1;
        $name = '';
        $id_number = '';
        $RealNameAuthService = new RealNameAuthService();
        $result = $RealNameAuthService->realNameAuth($name, $id_number);
//        $ret = JshbService::realnameAuth($name, $id_number);
        var_dump($result);
    }

    public function actionBankCardChangJie(){
        $api = 'http://'.API_PAYURL.'/';
        $card_verify_api_url = "auth";
        $merchant_id = '1';
        $customParams = [
            'name' => (string)'',                                         //四要素 - 用户名
            'bank_card_no' => (string)'',                         //四要素 - 银行卡号
            'id_card_no' => (string)'',                              //四要素 - 身份证号
            'phone' => (string)'',                                       //四要素 - 手机号
            'bank_id' => (string)'1',                                   //畅捷四要素鉴权需要 - 银行卡ID
        ];

        //请求支付系统四要素验证-畅捷
        $type = "POST";
        $customParams['merchant_id'] = $merchant_id;
        //加密方式
        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        var_dump($customParams);
        $url = $api. $card_verify_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        var_dump($ret);
    }



    /**
     * 获取签名
     */
    public function getSign(array $postArray, $privateKey)
    {
        ksort($postArray);
        $sign = json_encode($postArray, JSON_UNESCAPED_UNICODE);
        return hash('sha256', $sign . $privateKey);
    }
}