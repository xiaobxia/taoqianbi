<?php
namespace common\widgets;
use common\models\WxShareConfig;
use Yii;
use yii\base\Widget;
use common\external\WeixinJssdk;
use yii\base\Exception;

class WxShare extends Widget
{
    public $title;// 分享标题
    public $desc = '';// 分享描述
    public $link;// 分享链接
    public $imgUrl = '';// 分享图标
    public $shareButton = 0;// 分享按钮
    public $platform;// 分享平台
    public $params = '';//其他参数，可以是字符串(用'&'分割，例如:a=b&c=d)，也可以是数组([a=>b,c=>d]自动分割成a=b&c=d形式...)
    public $type = '';// 分享平台
    public $successUrl = '';// 分享成功回调
    public $cancelUrl = '';// 分享取消回调
    public $failUrl = '';// 分享失败回调
    public function init(){
        if(!$this->title){
            //throw new Exception("分享title不能为空");
        }
        if(!$this->link){
            //throw new Exception("分享链接不能为空");
        }
        if(!$this->platform){
            $this->platform = WxShareConfig::getPlatformKey();
        }
        /*版本兼容*/
        if(version_compare($this->getClientVersion(), '1.4.3') < 0)
        {
            $this->platform = [];
        }

        if($this->params && is_array($this->params)){
            $this->params = http_build_query($this->params);
        }
    }
    public function run(){
//        $param = Yii::$app->params;
        $jssdk = new WeixinJssdk(Yii::$app->weixinService->appID, Yii::$app->weixinService->secret);
		if($this->type){
			$signPackage = $jssdk->GetSignPackage('h5');
		}else{
			$signPackage = $jssdk->GetSignPackage();
		}
        $baseUrl = Yii::$app->getRequest()->getHostInfo() . Yii::$app->getRequest()->getBaseUrl();
        $fromapp = Yii::$app->controller->isFromApp() ? 1 : 0;
        return $this->render('wx-share',[
                'signPackage' => $signPackage,
                'shareButton' => $this->shareButton,
                'baseUrl' => $baseUrl,
                'successUrl' => $this->successUrl,
                'failUrl' => $this->failUrl,
                'cancelUrl' => $this->cancelUrl,
                'personal_key' => '',
                'fromapp' => $fromapp,
                'title' => $this->title,
                'desc' => $this->desc,
                'link' => $this->link,
                'imgUrl' => str_ireplace('https://', 'http://', $this->imgUrl),//图标全部用http，android部分机器用https有问题
                'platform' => $this->platform,
                'params' => $this->params,
                'type' => $this->type,
        ]);
    }

    /*
    *  返回公众号对应JSSDK的签名包
    *
    */
    public function getSignPackage($source = 'api') {
        $jssdk = new WeixinJssdk(Yii::$app->weixinService->appID, Yii::$app->weixinService->secret);
        $signPackage = $jssdk->GetSignPackage($source);
        return $signPackage;
    }

    /**
     * 获取客户端版本 字符串
     */
    public function getClientVersion(){
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $clent_version = "0";
        if (@stristr($_SERVER['HTTP_USER_AGENT'],'kdxj') ? true : false) {
            $ver_str   = @strstr($user_agent,"kdxj/");
            $agent_arr = @explode("/", $ver_str);
            if (is_array($agent_arr)) {
                $clent_version = end($agent_arr);
            }
        }

        return $clent_version;
    }
}