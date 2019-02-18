<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/7/7
 * Time: 16:01
 */

namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\CurlHelper;

class JsqbService extends Component
{
    private $host = '';
    private $username = '';
    private $password = '';

    private $expire = 3600 * 24;
    private $token_key = 'JSQB_TOKEN_KEY';

    public function init()
    {

    }

    /**
     * 获取token
     * @param bool $force
     * @return array
     */
    public function getToken($force = false)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        return $this->updateToken($this->username, $this->password, $this->expire);
    }

    /**
     * 更新token
     * @param $username
     * @param $password
     * @param $token_hours
     * @return array
     */
    private function updateToken($username, $password, $token_hours)
    {
        $url = $this->host . '/client/authorize/token';
        $param = [
            'username' => $username,
            'password' => $password,
            'hours' => $token_hours,
        ];
        Yii::warning($param, 'debug-token');
        $result = CurlHelper::curlHttp($url, 'WEALIDA', $param, 300);
        Yii::warning($result, 'debug-token');
        Yii::warning(CurlHelper::$err_msg, 'debug-token');
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'token' => $result['data']['token'],
                    'message' => 'token获取成功',
                ];
            }

            if (!isset($result['msg'])) {
                return [
                    'code' => $result['code'],
                ];
            } else {
                return [
                    'code' => $result['code'],
                    'message' => $result['msg'],
                ];
            }
        } else {
            return [
                'code' => -1,
                'message' => 'token获取失败',
            ];
        }
    }

    /**
     * 查询指定用户是否在黑名单
     * @param $token
     * @param $name
     * @param $id_card
     * @param $mobile
     * @param $ip
     * @param $bank_card
     * @return array
     */
    public function queryBlacklist($token, $name, $id_card, $mobile, $ip = '', $bank_card = '')
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/v2/blacklist/black-list/search-one';
        $id_card = str_replace('x', 'X', $id_card);
        $param = [
            'token' => $token,
            'name' => $name,
            'id_card' => $id_card,
            'mobile' => $mobile,
            'ip' => $ip,
            'bank_card' => $bank_card,
        ];
        $result = CurlHelper::curlHttp($url, 'WEALIDA', $param, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'is_in' => $result['data'][0]['is_in'],
                    'message' => $result['msg'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '黑名单信息获取失败，请求发送失败',
            ];
        }
    }

    /**
     * @name 获取openid
     * @param $token
     * @param $name
     * @param $id_card
     * @param $mobile
     * @param string $service_password
     * @param array $options
     * @return array
     */
    public function getCarrierOpenId($token, $name, $id_card, $mobile, $service_password = "", $options = [])
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/open-id';
        $id_card = str_replace('x', 'X', $id_card);
        $post_data = [
            'token' => $token,
            'name' => $name,
            'id_card' => $id_card,
            'mobile' => $mobile,
            'service_password' => $service_password,
        ];
        if (!empty($options)) {
            $post_data['options'] = $options;
        }
        \yii::info($post_data, 'jsqb_getCarrierOpenId');
        $result = CurlHelper::curlHttp($url, 'WEALIDA', http_build_query($post_data), 300);
        \yii::info($result, 'jsqb_getCarrierOpenId');
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'open_id' => $result['data']['open_id'],
                    'message' => isset($result['msg']) ? $result['msg'] : '',
                ];
            }
            return [
                'code' => $result['code'],
                'message' => isset($result['msg']) ? $result['msg'] : '',
            ];
        } else {
            return [
                'code' => -1,
                'message' => 'open_id获取失败，请求发送失败',
            ];
        }
    }

    /**
     * 提交服务密码
     * @param $open_id
     * @param $service_password
     * @return array
     *
     */
    public function submitServicePassword($open_id, $service_password)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/service-password';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'service_password' => $service_password,
        ];

        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'message' => "服务密码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
        }
    }

    /**
     * 提交查询密码
     * @param $open_id
     * @param $query_password
     * @return array
     */
    public function submitQueryPassword($open_id, $query_password)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/query-password';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'query_password' => $query_password,
        ];

        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'message' => "查询密码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '运营商查询密码提交失败，请求发送失败',
            ];
        }
    }

    /**
     * 提交手机验证码
     * @param $open_id
     * @param $captcha
     * @return array
     */
    public function submitCaptcha($open_id, $captcha)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/captcha';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'captcha' => $captcha,
        ];
        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'message' => "验证码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '手机验证码提交失败，请求发送失败',
            ];
        }
    }

    /**
     * 重发手机验证码
     * @param $open_id
     * @return array
     */
    public function resendCaptcha($open_id)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/resend-captcha';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];

        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'message' => "验证码重发成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '手机验证码重发失败，请求发送失败',
            ];
        }
    }

    /**
     * 查询状态
     * @param $open_id
     * @return array
     */
    public function getState($open_id)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/collect/get-state';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];

        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'state' => $result['data']['state'],
                    'message' => $result['data']['message'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '状态查询失败，请求发送失败002',
            ];
        }
    }

    /**
     * 获取原始数据
     * @param $open_id
     * @return array
     */
    public function getRawData($open_id)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/data/raw-data';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];

        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'raw_data' => $result['data'],
                    'message' => $result['msg'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' => $result['msg'],
            ];
        } else {
            return [
                'code' => -1,
                'message' => '原始数据查询失败，请求发送失败',
            ];
        }
    }

    /**
     * 获取报告数据
     * @param $open_id
     * @return array
     */
    public function getReport($open_id)
    {
        $proxy_service = new ProxyService(__CLASS__, __FUNCTION__, func_get_args());
        return $proxy_service->getData();

        $url = $this->host . '/telecom/data/report';
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];

        $start_time = \microtime(true);
        $result = CurlHelper::curlHttp($url, 'WEALIDA', $post_data, 300);
        if (!empty($result)) {
            if ($result['code'] == 0) {
                return [
                    'code' => $result['code'],
                    'report' => $result['data'],
                    'message' => $result['msg'],
                ];
            } else {
                return [
                    'code' => $result['code'],
                    'message' => $result['msg'],
                ];
            }
        } else {
            return [
                'code' => -1,
                'message' => '运营商报告查询失败，请求发送失败',
            ];
        }
    }
}
