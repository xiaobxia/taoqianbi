<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="wrapper">
    <div class="input-group">
        <input type="text" id="phone" required="required" placeholder="请输入注册手机号">
    </div>
    <div class="input-group">
        <input type="text" id="code" required="required" placeholder="请输入验证码">
        <button class="sendcode" id="sendcode" onclick="sendCode()">发送验证码</button>
    </div>
    <button id="btn" onclick="nextStep()"><span id="word">下一步</span></button>
</div>
<script>
    function sendCode(){
        $sendcode = $("#sendcode");
        var phone = $("#phone").val();
        var phone_reg = /^[1]\d{10}$/;
        if(phone == "") {
            dialog("手机号码不能为空");
            return false;
        }
        if ( !phone_reg.test(phone) ){
            dialog("手机号码不合法");
            return false;
        }
        var type = "<?php echo Yii::$app->request->get('type') == null ?  '' :  Html::encode( Yii::$app->request->get('type') );?>";
        var params = {
            phone:phone,
            type:type
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/reset-pwd-code','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0) {
                $sendcode.html('<span id="second">60s</span>').addClass('disable').attr('disabled', true);
                countdown();
            } else {
                dialog(data.message);
            }
        })
    }

    function nextStep(){
        var phone = $("#phone").val();
        var code = $("#code").val();
        var phone_reg = /^[1]\d{10}$/;
        if(phone == "") {
            dialog("手机号码不能为空");
            return false;
        }
        if ( !phone_reg.test(phone) ){
            dialog("手机号码不合法");
            return false;
        }
        if(code == "") {
            dialog("验证码不能为空");
            return false;
        }
        var type = "<?php echo Yii::$app->request->get('type') == null ?  '' :  Html::encode( Yii::$app->request->get('type') );?>";
        var params = {
            phone:phone,
            code:code,
            type:type
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/verify-reset-password','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0) {
                var base64 = window.btoa(code);
                var phone_base64 = window.btoa(phone);
                window.location.href = "<?php echo Url::toRoute(['building/login-pwd'],true);?>" + '?code=' +base64 + '&phone=' + phone_base64;
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

    // 倒计时
    function countdown() {
        var time = 60;
        timing = setInterval(function() {
            time--;
            $('#second').html(time+'s');
            if (time < 0) {
                clearInterval(timing);
                time = 60;
                $('#second').html("获取验证码");
                $sendcode.removeClass('disable').attr('disabled',false);
            }
        }, 1000);
    }
</script>