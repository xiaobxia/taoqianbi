<?php
namespace mobile\components;

use mobile\components\ApiUrl;
use Yii;
use yii\helpers\Url;

class View extends \common\components\View
{
    /**
     * 入口文件，不包括域名的目录
     */
    public $baseUrl;

    /**
     * 域名
     */
    public $hostInfo;

    /**
     * $hostInfo + $baseUrl
     */
    public $absBaseUrl;

    /**
     * other
     */
    public $userName;
    public $realName;
    public $idCard;


    public function init()
    {
        parent::init();
        $this->baseUrl = Yii::$app->getRequest()->getBaseUrl();
        $this->hostInfo = Yii::$app->getRequest()->getHostInfo();
        $this->absBaseUrl = $this->hostInfo . $this->baseUrl;
//        $this->userName = Yii::$app->user->identity['username'];
//        $this->realName = Yii::$app->user->identity['realname'];
//        $this->idCard = Yii::$app->user->identity['id_card'];
    }
    public function getSource(){
        return Yii::$app->controller->getSource();
    }
    public function getActionName(){
        return Yii::$app->requestedAction->id;
    }

    public function getController(){
        return Yii::$app->controller->id;
    }
    public function staticUrl($path,$type=''){
        return Yii::$app->controller->staticUrl($path,$type);
    }
    //百度统计
    function baiDuStatistics(){
        $html = <<<EOT
        <script>
        var _hmt = _hmt || [];
        (function() {
          var hm = document.createElement("script");
          hm.src = "//hm.baidu.com/hm.js?d354b0dc48b9fd9cb21724a5fb17a281";
          var s = document.getElementsByTagName("script")[0];
          s.parentNode.insertBefore(hm, s);
        })();
        </script>
EOT;
        return $html;
    }

    public function setFooter(){
        $html = '';
        $html_add = '';
        $html_add .= '<div class="p_relative"><ul class="p_absolute v_hidden f_right _b_radius link" style="right:0">
            <a class="_5fa2e7" href="http://api.kdqugou.com/page/invite-sixteen-mar?isShare=1"><li class="em__8 a_center">代言人</li></a>
            <a class="_505050" href="http://zqm.kdqugou.com/mExchangeMarket/stock.html?type_code=0"><li class="em__8 a_center">股票频道</li></a>
            <a class="_505050" href="'.Url::toRoute(["site/about"]).'"><li class="em__8 a_center">关于'.APP_NAMES.'</li></a>
            <a class="_505050" href="http://api.kdqugou.com/page/fxbzj?"><li class="em__8 a_center">安全保障</li></a>
            <a class="_505050" href="'.Url::toRoute(["site/question"]).'"><li class="em__8 a_center">常见问题</li></a>
            <a class="_505050" href="'.Url::toRoute(['site/guide']).'"><li class="em__8 a_center">关注微信</li></a>
            <a class="_5fa2e7" href="javascript:downLoad()"><li class="em__8 a_center">下载APP</li></a>
            <div class="m_center triangle_down"></div>
            </ul>';
        $html_add .= '<ul class="p_absolute v_hidden f_right _b_radius link" style="right:25%">
            <a class="_5fa2e7" href="'.Url::to(['insurance/intro', 'company' => '999']).'"><li class="em__8 a_center">保险验真</li></a>
            <div class="m_center triangle_down"></div>
            </ul>';
        $html_add .= '<div class="clear"></div></div>';

        $html .= '<div class="p_fixed" style="bottom:0;width:100%;">';
        $html .= $html_add.'<ul id="footer">';
        $html .= '<a class="_323232" href="'.Url::toRoute(["site/index"]).'" ActionName="index"><li class="f_left em__9 a_center"><img class="v_bottom" src="'.$this->absBaseUrl.'/image/site/home.png?v=2015060802" width="22%"><img class="_hidden v_bottom" src="'.$this->absBaseUrl.'/image/site/home_visited.png?v=2015060802" width="22%"><span class="v_bottom"><br/>首页</span></li></a>';
        if ( !empty($this->userName )){
            $html .= '<a class="_323232" href="'.Url::toRoute(["site/kd"]).'" ActionName="kd"><li class="f_left em__9 a_center"><img class="v_bottom" src="'.$this->absBaseUrl.'/image/site/user.png?v=2015060802" width="22%"><img class="_hidden v_bottom" src="'.$this->absBaseUrl.'/image/site/user_visited.png?v=2015060802" width="22%"><span class="v_bottom"><br/>我的口袋</span></li></a>';
        }else{
            $html .= '<a class="_323232" href="'.Url::toRoute(["user/user-login"]).'" ActionName="kd"><li class="f_left em__9 a_center"><img class="v_bottom" src="'.$this->absBaseUrl.'/image/site/user.png?v=2015060802" width="22%"><img class="_hidden v_bottom" src="'.$this->absBaseUrl.'/image/site/user_visited.png?v=2015060802" width="22%"><span class="v_bottom"><br/>登录/注册</span></li></a>';
        }
            $html .= '<a class="_323232" id="activity" href="javascript:void(0)"><li class="f_left em__9 a_center"><img class="v_bottom" src="'.$this->absBaseUrl.'/image/site/insure.png?v=2015060802" width="22%"><img class="_hidden v_bottom" src="'.$this->absBaseUrl.'/image/site/new_activity.png?v=2015060802" width="22%"><span class="v_bottom"><br/>保险相关</span></li></a>';
            $html .= '<a class="_323232" id="more" href="javascript:void(0)"><li class="f_left em__9 a_center"><img class="v_bottom" src="'.$this->absBaseUrl.'/image/site/more.png?v=2015060802" width="22%"><span class="v_bottom"><br/>更多</span></li></a>';
        $html .= '<div class="clear"></div></ul>';
        $html .= '</div>';
        $script = '';
        $script .= '<script><!--
            $("#activity").click(function(){
                $(".link:eq(1)").toggleClass("v_hidden").siblings().addClass("v_hidden");
            });
            $("#more").click(function(){
                $(".link:eq(0)").toggleClass("v_hidden").siblings().addClass("v_hidden");
            });
        --></script>';
        return $html.$script;
    }

    public function SetMenuJs(){
        //js高亮样式控制
        $script = '
            $("#footer a").each(function(){
                if( $(this).attr("ActionName") != undefined ){
                    if( "'.$this->getActionName().'".indexOf( $(this).attr("ActionName") ) >= 0 ){
                        $(this).addClass("fd5353").removeClass("_323232");
                        $(this).find("img").toggleClass("_hidden");
                    }
                }
            });
        ';

        return $script;
    }

    public function actionSetHeaderUrl(){
        //获取header参数
        $header = Yii::$app->request->headers;
        $appVersion = $header->get('appVersion');
        $appMarket = $header->get('appMarket');
        $str = [];
        if(isset($appVersion)){
            $str['appVersion'] = $appVersion;
        }
        if(isset($appMarket)){
            $str['appMarket'] = $appMarket;
        }
        return $str;
    }

    /**
     * 皮肤资源路径
     */
    public function source_url(){
        $baseUrl = Yii::$app->request->getHostInfo() . Yii::$app->request->getBaseUrl();
        return $baseUrl;
    }

}
