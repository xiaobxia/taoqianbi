<?php

namespace common\external;


use yii\base\Exception;

class WeixinJssdk
{
    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function getSignPackage($source = 'api')
    {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        if ($source != 'api' && isset($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0) ? "https://" : "http://";
            //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            //\Yii::info($url);
        }

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket()
    {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = @json_decode(\Yii::$app->cache->get("jsapi_ticket_" . $this->appId), true);
        if (!$data || $data['expire_time'] < time()) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url), true);
            $ticket = $res['ticket'];
            $data = [];
            if ($ticket) {
                $data['expire_time'] = time() + 7000;
                $data['jsapi_ticket'] = $ticket;
                \Yii::$app->cache->set("jsapi_ticket_" . $this->appId, json_encode($data));
            }
        } else {
            $ticket = $data['jsapi_ticket'];
        }
        return $ticket;
    }

	public function getAccessToken()
	{
		// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		$data = @json_decode(\Yii::$app->cache->get("access_token_" . $this->appId), true);
		if (!$data || $data['expire_time'] < time()) {
			// 如果是企业号用以下URL获取access_token
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
			$res = json_decode($this->httpGet($url), true);
			$access_token = isset($res['access_token']) ? $res['access_token'] : null;

			$data = [];
			if ($access_token) {
				$data['expire_time'] = time() + 7000;
				$data['access_token'] = $access_token;
				\Yii::$app->cache->set("access_token_" . $this->appId, json_encode($data));
			}else{
				\Yii::error([
					'获取授权秘钥失败',
					$url,
					$res
				]);
				return false;
			}
		} else {
			$access_token = $data['access_token'];
		}
		return $access_token;
	}

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * 获取微信短链（有次数限制）
     * @param string $url
     * @return bool|string
     */
    public function getShortUrl($url, $from = '', $time = 0)
    {
        try {
            if ($from == 'weixin') {

                $token = $this->getAccessToken();
                $data = ['action' => 'long2short', 'long_url' => $url];
                $ret = $this->postData("https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$token}", json_encode($data));
                $retData = json_decode($ret);

                if (!isset($retData->errcode) || $retData->errcode != 0)
                    return false;
                else
                    return $retData->short_url;

            } else {
                $ret = file_get_contents("http://985.so/api.php?url=" . urlencode($url));
                if ($ret)
                    return $ret;
                else
                    return false;
            }
        } catch (Exception $exception) {
            if ($time == 0) {
                $this->getShortUrl($url, 'weixin', 1);
            } else {
                \yii::error('获取短链失败：' . $exception->getMessage() . ' data:' . $url . '----' . $from);
                return false;
            }
        }
    }


    /**
     * CURL获取
     * @param $url
     * @return mixed
     */
    public static function postData($url, $data = '')
    {
        $timeout = 1000;
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在


        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        }
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }
}

