<?php

namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\CurlHelper;
use common\models\CreditSauron;
use common\models\CreditSauronLog;
use common\models\ErrorMessage;
use common\models\UserEBusinessInfo;
/**
 *  葫芦数据：
 *     帐号：
 *     密码：
 *     前端认证地址：https://collect.hulushuju.com/#/koudailc_CRAWLER/home
 *     查看数据地址：https://dev.hulushuju.com/#/login
 *     机构密钥（signature）：(通过接口获取数据使用)
 *
 *     Sauron帐号：
 *     密码：
 *     Sauron地址：https://s.hulushuju.com
 *     signature：(通过接口获取数据使用)
 */
class HuluService extends Component
{
    private $companyAccount = '';
    private $signature = '';
    private $price = 0;  // ?

    const DATA_ACCESS_TOKEN_IS_ILLEGAL = 16896;
    const DATA_SAURON_ACCESS_EXCEPTION = 17152;
    const DATA_SAURON_ACCESS_SUCCESS = 17153;
    const DATA_SAURON_IDCARD_INVALID = 17154;
    const DATA_SAURON_NAME_INVALID = 17155;
    const DATA_SAURON_PHONE_INVALID = 17156;

    public function getAccessToken() {
        if (!$token = Yii::$app->cache->get('HuluService::getAccessToken')) {
            $url = "https://data.hulushuju.com/api/companies/{$this->companyAccount}/access_token?signature={$this->signature}";
            $json = CurlHelper::curlHttp($url, 'get');
            if (!$json) {
                $token = [
                    'code' => -1,
                    'message' => '令牌调用失败，请联系管理员'
                ];
            } elseif ($json['code'] == 0) { //成功
                $token = $json['data']['access_token'];
                $expire_in = $json['data']['expire_in'];
                $duration = (int)($expire_in / 1000) - time() - 60;
                $duration = $duration > 0 ? $duration : 300;
                Yii::$app->cache->set("HuluService::getAccessToken", $token, $duration);
            } else { //失败
                return [
                    'code' => -1,
                    'message' => '令牌解析失败，请联系管理员'
                ];
            }
        }
        return $token;
    }

    /**
     * 远程调用索伦
     *
     * @param $name
     * @param $idCard
     * @param $phone
     * @return array
     */
    public function getSauron($name, $idCard, $phone)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $accessToken = $this->getAccessToken();
        $url = "https://ad.hulushuju.com/api/sauron?name={$name}&phone={$phone}&idCard={$idCard}&companyAccount={$this->companyAccount}&accessToken={$accessToken}";

        $result = CurlHelper::curlHttp($url, 'get');
        return $result;
    }


    /**
     * 获取不良信息的接口
     *
     * @param $name
     * @param $idcard
     * @param $phone
     * @param $person_id
     * @return array
     *
     */
    public function getBadInfo($name, $idcard, $phone, $person_id)
    {
        $ret = $this->getSauron($name, $idcard, $phone);

        if (!$ret || $ret['code'] != self::DATA_SAURON_ACCESS_SUCCESS) {  // 失败的情况
            if (isset($ret['code_description']) || isset($ret['message'])) {
                ErrorMessage::getMessage($person_id, print_r($ret, true), ErrorMessage::SOURCE_SAURON);
            }
            return [
                'code' => -1,
                'message' => '信息获取失败，请联系管理员'
            ];
        } else { // 成功了
            $result = $this->saveSauronData($ret, $person_id);
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
    }

    /**
     * 保存葫芦索伦数据
     *
     * @param $ret
     * @param $person_id
     * @return bool
     *
     */
    public function saveSauronData($ret, $person_id)
    {
        //保存日志
        $creditMgLog = new CreditSauronLog();
        $creditMgLog->person_id = $person_id;
        $creditMgLog->type = 0;
        $creditMgLog->price = $this->price;

        $creditMgLog->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
        $creditMgLog->save();

        $result = $ret['data'];
        $update_time = $ret['data']['update_time'];
        $model = CreditSauron::findLatestOne(['person_id' => $person_id]);
        if (is_null($model)) {
            $model = new CreditSauron();
        }
        $model->person_id = $person_id;
        $model->update_time = $update_time;
        $model->data = json_encode($result);
        $ret = $model->save();
        if (!$ret) {
            return false;
        } else {
            return $result;
        }

    }

}
