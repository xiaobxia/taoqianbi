<?php

namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\Curl;
use common\helpers\CurlHelper;
use common\models\CreditMg;
use common\models\CreditMgLog;
use common\models\ErrorMessage;


class MgService extends Component
{
    private $client_secret = '61a372909b5b44278020f2e7f1379ada';  //聚信立，秘钥
    private $account = 'shandianhb';  //聚信立用户名
    private $token_hours = 24;
    private $price = 0;

    /**
     * @param $name
     * @param $idcard
     * @param $phone
     * @param $person_id
     * @return array
     * 获取不良信息的接口
     */
    public function getBadInfo($name, $idcard, $phone, $person_id)
    {
        $start_time = \microtime(true);
        $ret = $this->getMiGuanInfo($name, $idcard, $phone);
//        Yii::warning(\sprintf('%s %s', $person_id, \microtime(true) - $start_time), 'new_mg_time');
        if (!isset($ret) || $ret['code'] != 'MIGUAN_SEARCH_SUCCESS') {
            Yii::error(\sprintf('%s %s', $person_id, json_encode($ret, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES)), 'mg_ret');
            if (isset($ret['message'])) {
                ErrorMessage::getMessage($person_id, $ret['message'], ErrorMessage::SOURCE_MG);
            }
            else {
                ErrorMessage::getMessage($person_id, sprintf('ret_none_msg:%s', json_encode($ret)), ErrorMessage::SOURCE_MG);
            }
            return [
                'code' => -1,
                'message' => '信息获取失败，请联系管理员'
            ];
        }
        $result = $this->saveMgData($ret, $person_id);
        if (!$result) {
            return [
                'code' => -1,
                'message' => '数据保存失败，请联系管理员'
            ];
        }

        return [
            'code' => 0,
            'message' => '数据获取成功',
            'data' => $ret
        ];
    }

    public function getMiGuanInfo($name, $idcard, $phone)
    {

        $url = 'https://mi.juxinli.com/api/search';
//        $url='https://www.juxinli.com/api/access_report_data';
        $access_token = $this->GetMgToken();
        if ($access_token['code'] != 0) {
            return false;
        }

        $params = [
            'client_secret' => $this->client_secret,
            'access_token' => $access_token['data'],
            'name' => $name,
            'phone' => $phone,
            'id_card' => $idcard,
        ];
        //$result = CurlHelper::curlHttp($url, 'get', $params);
        $curl = new Curl();
        $response = $curl->get($url, $params);
        $result = json_decode($response->body, true);
        return $result;
    }

    public function GetMgToken()
    {

        $url = 'https://mi.juxinli.com/api/access_token';  //接口地址
//        $url='https://www.juxinli.com/api/access_report_token';
//        $param = [
//            'client_secret' => $this->client_secret,
//            'account' => $this->account,
//        ];
        $param = [
            'client_secret' => $this->client_secret,
            'account' => $this->account
//            'hours'=>$this->token_hours
        ];
        $result = CurlHelper::curlHttp($url, 'get', $param);
        if ($result) {
            if ($result['code'] == 0) {
                if (!isset($result['data']['access_token'])){
                    return [
                        'code' => -1,
                        'message' => $result['message']
                    ];
                }
                return [
                    'code' => 0,
                    'data' => $result['data']['access_token'],
                ];
            }
            return [
                'code' => -1,
                'message' => $result['message']
            ];
        } else {
            return [
                'code' => -1,
                'message' => 'access token获取失败'
            ];
        }
    }

    /**
     * @param $ret
     * @param $person_id
     * @return bool
     * 保存蜜罐数据
     */
    public function saveMgData($ret, $person_id)
    {
        //保存日志
        $creditMgLog = new CreditMgLog();
        $creditMgLog->person_id = $person_id;
        $creditMgLog->type = 0;
        $creditMgLog->price = $this->price;
        $creditMgLog->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
        $creditMgLog->save();

        $result = $ret['data'];
        $update_time = date('Y-m-d H:i:s', substr($ret['data']['update_time'], 0, 10));
        $creditMg = CreditMg::findLatestOne(['person_id' => $person_id]);
        if (is_null($creditMg)) {
            $creditMg = new CreditMg();
        }
        $creditMg->person_id = $person_id;
        $creditMg->update_time = $update_time;
        $creditMg->data = json_encode($result);
        $ret = $creditMg->save();
        if (!$ret) {
            return false;
        } else {
            return $result;
        }
    }

}