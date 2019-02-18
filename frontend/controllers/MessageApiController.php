<?php
/**
 * 发送邮件、短信
 */
namespace frontend\controllers;

use common\helpers\MailHelper;
use common\helpers\MessageHelper;

class MessageApiController extends BaseController
{
    public function beforeAction($action)
    {
        header("Access-Control-Allow-Origin: *");
        return true;
    }

    public function actionSend()
    {
        $this->response->format = \yii\web\Response::FORMAT_JSON;
        $data = \Yii::$app->request->post('data');
        $sign = \Yii::$app->request->post('sign');
        if(empty($data) || empty($sign)){
            return [
                'code' => -1,
                'msg' => '参数错误'
            ];
        }

        $public_content=file_get_contents(\Yii::getAlias('@common/attachment/rsa_key/public_key.pem'));
        $public_key=openssl_get_publickey($public_content);
        $sign=base64_decode($sign);

        $check=(bool)openssl_verify($data,$sign,$public_key);
        if(!$check){
            return [
                'code' => -1,
                'msg' => '验签失败'
            ];
        }
        $ret = json_decode($data,true);

        if (!$this->checkParams($ret, ['receiver', 'message', 'type'])) {
            return [
                'code' => -3,
                'msg' => '参数错误'
            ];
        }

        switch ($ret['type']) {
            case 'mail': //邮件
                if (!MailHelper::send($ret['receiver'], $ret['subject'] ?? '', $ret['message'])) {
                    return [
                        'code' => -1,
                        'msg' => '发送失败'
                    ];
                }
                break;
            case 'short_msg':  // 短信
                if (!MessageHelper::sendSMS($ret['receiver'], $ret['message'])) {
                    return [
                        'code' => -1,
                        'msg' => '发送失败'
                    ];
                }
                break;
            default:
                return [
                    'code' => -1,
                    'msg' => '参数错误'
                ];
        }

        return [
            'code' => 0,
            'msg' => '发送成功'
        ];
    }

    private function checkParams($data, $params) {
        foreach ($params as $param) {
            if (!isset($data[$param])) {
                return false;
            }
        }

        return true;
    }
}
