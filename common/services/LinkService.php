<?php
namespace common\services;

class LinkService
{
    const SINA_APPKEY = 1681459862; //新浪App_Key

    public function ShortUrl($source_tag = ''){
//        $source_tag = trim($this->request->get('source_tag'), ''); //获取渠道id
        if (empty($source_tag)){
            return [
                'code' => -1,
                'message' => '获取source_id失败',
                'data' => [],
            ];
        }
        $long_url = SHORT_DOWNLOAD_URL.'?source_tag='.$source_tag;
        $url = 'http://api.t.sina.com.cn/short_url/shorten.json?source=' . self::SINA_APPKEY . '&url_long=' . $long_url;
        $result = $this->curlQuery($url);
        $json = json_decode($result);

        if (isset($json->error) || !isset($json[0]->url_short) || $json[0]->url_short == '') {
            return [
                'code' => -1,
                'message' => '获取短链接失败',
                'data' => $url,
            ];
        }else {
            $short_url = $json[0]->url_short;
        }
        $data = [
            'source_tag' => $source_tag,
            'long_url' => $long_url,
            'short_url' => $short_url,
            'result' => $result
        ];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 根据长网址获取短网址
     * @return mixed
     */
    private function curlQuery($url){
        $addHead = array(
            "Contenr-type:application/json"
        );
        $curl_obj = curl_init();
        //设置网址
        curl_setopt($curl_obj, CURLOPT_URL, $url);
        //附加Head内容
        curl_setopt($curl_obj, CURLOPT_HTTPHEADER, $addHead);
        //是否输出返回头信息
        curl_setopt($curl_obj, CURLOPT_HEADER, 0);
        //将curl_exec的结果返回
        curl_setopt($curl_obj, CURLOPT_RETURNTRANSFER, 1);
        //设置超时时间
        curl_setopt($curl_obj, CURLOPT_TIMEOUT, 15);
        //执行
        $result = curl_exec($curl_obj);
        //关闭curl回话
        curl_close($curl_obj);
        return $result;
    }
}