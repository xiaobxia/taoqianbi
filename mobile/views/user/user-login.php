<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/style.css?v=20150601101">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/cookie.js?v=2015062301" ></script>
<div id="user_login_wraper">
    <div class="padding _666 em__9 a_right" id="tips">还没账号？<a class="fa5558" href="<?php echo Url::toRoute(["user/register-phone"],true); ?>">点击注册</a></div>
    <input class="padding em_1" id="login_username" type="text" maxlength="11" onkeyup="JustInt(this);" placeholder="请输入手机号"/>
    <input class="padding em_1" id="login_pwd" type="password" placeholder="请输入登录密码"/>
    <div class="padding fd5457 em__8" id="msg">&nbsp;&nbsp;</div>
    <div class="padding">
        <div class="fff em_1 a_center _b_radius" id="btn" onclick="userLogin();">登录</div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        Initialization();
    });
    $(window).resize(function(){
        Initialization();
    });
    function Initialization(){
        fontSize();
        isOneScreen();
    }
    function userLogin(){
        var username = $("#login_username").val();
        var login_pwd = $("#login_pwd").val();
        var mobile_reg = /^[1]\d{10}$/;
        if(username == ""){
            $('#msg').html('手机号不能为空！');
            return false;
        }
        if ( !mobile_reg.test(username) ){
            $('#msg').html('手机号不合法');
            return false;
        }
        if(login_pwd == ""){
            $('#msg').html('密码不能为空！');
            return false;
        }
        var params = {
            username: username,
            password: login_pwd
        };

        var url = '<?php echo ApiUrl::toRoute("user/login",true); ?>';

        KD.util.post(url, params, function(result){
            if(result.code == 0){
                setCookie('SESSIONID',result.sessionid,'h12');
                window.location.href = "<?php echo $this->absBaseUrl.'/train-period/page-personal-center'; ?>";

            }else{
                $('#msg').html(result.message);
            }
        });
    }
</script>