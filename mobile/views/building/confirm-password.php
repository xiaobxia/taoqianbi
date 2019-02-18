<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="wrapper">
    <div class="input-group">
        <input type="text" id="code" required="required" placeholder="请输入验证码">
        <button class="sendcode" id="sendcode" onclick="sendCode()">发送验证码</button>
    </div>
    <div class="input-group">
        <input type="password" id="pwd" required="required" placeholder="请输入登录密码">
    </div>
    <div class="input-group">
        <input type="password" id="repwd" required="required" placeholder="再次输入登录密码">
    </div>

    <button id="btn" onclick="userLogin()"><span id="word">马上注册</span></button>
    <p>注册即成为房产中介经纪人，坐享高额返佣</p>
</div>
<script type="text/javascript">
    var phone = "<?php echo Yii::$app->request->post('phone') == null ?  '' :  Html::encode( Yii::$app->request->post('phone') );?>";
    var sendcode = $('#sendcode');
    window.onload = function(){
        if(phone != ""){
            sendcode.html('<span id="second">60s</span>').addClass('disable').attr('disabled', true);
            countdown();
        }
    };

    function sendCode(){
        if(phone == ""){
            dialog("手机号不能为空",function(){
                window.location.href = "<?php echo Url::toRoute(['building/register'],true);?>";
            });
        }else {
            KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/reg-get-code','clientType'=>'h5'], true); ?>",phone,function(data){
                if(data.code == 0) {
                    sendcode.html('<span id="second">60s</span>').addClass('disable').attr('disabled', true);
                    countdown();
                }else {
                    dialog(data.message);
                }
            });
        }
    }

    function userLogin(){
        var code = $("#code").val();
        if(code == "") {
            dialog("验证码不能为空");
            return false;
        }
        var pwd = $("#pwd").val();
        var repwd = $("#repwd").val();
        var password_reg = /^[\@A-Za-z0-9\!\#\$\%\^\&\*\.\~]{6,}$/;
        if(pwd == "") {
            dialog("新的登录密码不能为空");
            return false;
        }
        if(repwd == "") {
            dialog("再次输入新的登录密码");
            return false;
        }
        if(pwd.length > 25){
            dialog("密码过长");
            return false;
        }
        if(pwd == repwd){
            if(!password_reg.test(pwd)) {
                dialog("密码不能少于6位");
                return false;
            }
            var source = "<?php echo Yii::$app->request->post('source') == null ?  '' :  Html::encode( Yii::$app->request->post('source') );?>";
            var name = "<?php echo Yii::$app->request->post('name') == null ?  '' :  Html::encode( Yii::$app->request->post('name') );?>";
        } else {
            dialog("两次登录密码输入不一致");
            return false;
        }

        var params = {
            name:name,
            phone:phone,
            code:code,
            source:source,
            password:pwd
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/register','clientType'=>'h5'], true); ?>",params,function(data){
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