<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/cookie.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="wrapper login_css">
    <div class="input-group">
        <input type="tel" id="phone" required="required" placeholder="请输入手机号">
    </div>
    <div class="input-group">
        <input type="password" id="password" required="required" placeholder="请输入小钱包登录密码">
    </div>
    <button id="btn" onclick="userLogin()"><span id="word">马上登录</span></button>
</div>
<script type="text/javascript">
    function userLogin(){
        var phone = $("#phone").val();
        var password = $("#password").val();
        var phone_reg = /^[1]\d{10}$/;
        if(phone == "") {
            dialog("手机号码不能为空");
            return false;
        }
        if ( !phone_reg.test(phone) ){
            dialog("手机号码不合法");
            return false;
        }
        if(password == "") {
            dialog("密码不能为空");
            return false;
        }

        var params = {
            username:phone,
            password:password,
            source:<?php echo $source; ?>
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/login','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0) {
                window.location.href = "http://h5.kdqugou.com/operate/operate.html";
            } else {
                dialog(data.message);
            }
        })
    }

    // 消息弹窗
    function dialog(g,f){
        var $e=$('<div class="pop-box"><div class="pop-con"><p>'+g+"</p><button>确认</button></div></div>");
        $e.appendTo("body");
        $e.find("button").on("click",function(a){
            a.preventDefault();
            $e.remove();
        });
    }
</script>