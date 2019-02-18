<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="wrapper pwd_css">
    <div class="input-group">
        <input type="password" id="pwd1" required="required" placeholder="设置新登录密码">
    </div>
    <div class="input-group" style="padding-bottom:0;">
        <input type="password" id="repwd" required="required" placeholder="确认新登录密码">
    </div>
    <p class="detail">登录密码须为6~16位字符，区分大小写</p>
    <button id="btn" style="margin-top:0.426667rem;"  onclick="refer()"><span id="word">提交</span></button>
</div>
<script>
    function refer(){
        var pwd = $("#pwd1").val();
        var repwd = $("#repwd").val();
        if(pwd == "") {
            dialog("新的登录密码不能为空");
            return false;
        }
        if(repwd == "") {
            dialog("再次输入新的登录密码");
            return false;
        }
        if(pwd == repwd){
            var code = "<?php echo Yii::$app->request->get('code') == null ?  '' :  Html::encode( Yii::$app->request->get('code') );?>";
            var phone = "<?php echo Yii::$app->request->get('phone') == null ?  '' :  Html::encode( Yii::$app->request->get('phone') );?>";
        } else {
            dialog("两次登录密码输入不一致");
            return false;
        }
        phone = window.atob(phone);
        code = window.atob(code);
        var params = {
            phone:phone,
            code:code,
            password:pwd
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/reset-password','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0) {
                dialog(data.message,function(){
                    window.location.href = "<?php echo Url::toRoute(['building/login'],true);?>";
                });
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
            if(typeof (f) == "function"){
                f();
            }
        });
    }
</script>