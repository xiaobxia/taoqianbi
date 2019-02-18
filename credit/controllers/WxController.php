<?php
namespace credit\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\Url;
use common\models\WeixinUser;

/**
 * Wx controller
 */
class WxController extends BaseController
{
    public function actionTest(){
        $this->autoWeChatLogin();
        echo 1;
    }
    /**
     * 新版微信重定向入口地址，以base的方式获取用户openid
     *
     * @name 通用微信授权模板-获取用户openid [UserAuth]
     * @method get
     */
    public function actionUserAuthTemplate()
    {
        $this->layout = 'credit';
        $this->getResponse()->format = Response::FORMAT_HTML;
        $params = $this->request->get();
        if ( empty($params['redirectUrl']) ) { // 回调地址没传
            return $this->render('error', [
                    'message' => '亲爱的用户，该链接无效哦！',
            ]);
        }

        $weixinService = Yii::$app->weixinService;
        $url = urldecode($params['redirectUrl']);
        $base_url = Url::toRoute(['wx/user-auth-template', 'redirectUrl' => $url], true);
        $base_redirect_uri = urlencode($base_url);
        $openid = $this->getCookie('openid');
        if (!$openid && !isset($_GET['code'])) {
            // 用户同意授权后得到code并请求本接口，get请求附带code参数，根据code得到网页授权access_token
            return $this->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=".WEIXIN_APPID."&redirect_uri={$base_redirect_uri}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
        }
        $redirectUrl = urldecode($params['redirectUrl']);
        if ($openid) {
            return $this->redirect($redirectUrl);
        }
        $result = $weixinService->get_code_token($_GET['code']);
        if ( isset($result['errcode']) && $result['errcode'] ) {
            return $this->render('error', [
                    'message' => '亲爱的用户，微信服务器出错了！',
            ]);
        }

        $openid = $result['openid'];
        // 根据access_token和openid检验授权凭证
        $valid_access = $weixinService->check_auth_access_token($result['access_token'], $openid);
        if (!$valid_access){
            return $this->render('error', [
                    'message' => '授权凭证失效，请重试！',
            ]);
        }
        $weixin_user = WeixinUser::getUserInfo($openid);
        if (!$weixin_user) {
            /*获取用户信息*/
            $weixin_user_info = $weixinService->get_auth_user_info($openid,$result['access_token']);
            if($weixin_user_info === false)
            {
                return $this->render('error', [
                    'message' => '系统繁忙，请稍后重试！',
                ]);
            }

            $weixin_user = new WeixinUser();
            $weixin_user->openid = $openid;
            $weixin_user->nickname = $weixin_user_info['nickname'];
            $weixin_user->headimgurl = $weixin_user_info['headimgurl'];
            $weixin_user->status = 1;
            if (!$weixin_user->save()) {
                return $this->render('error', [
                        'message' => '系统繁忙，请稍后重试！',
                ]);
            }
        }
        $session = Yii::$app->session;
        if(!isset($weixin_user->openid)){
            $this->setCookie('openid', $openid);
            $session->set('openid', $openid);
        }else{
            // openid存储到cookie中
            $this->setCookie('openid', $weixin_user->openid, 3600* 24 * 14);
            $session->set('openid', $openid);
        }
        return $this->redirect($redirectUrl);
    }

    public $token = 'f990514e095c8528'; // 测试

    /**
     * 微信测试
     */
    public function actionTest2(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($this->token, $timestamp, $nonce);
        sort ($tmpArr);
        $tmpStr = implode ($tmpArr);
        $tmpStr = sha1 ($tmpStr);
        if ($tmpStr == $signature) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                echo $_GET['echostr'];
                exit;
            }
        } else {
            return false;
        }
    }
}
