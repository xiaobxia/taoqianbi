<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/style.css?v=20150601101">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/data.js" ></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/cookie.js"></script>
<div id="set_pay_pwd_wraper">
    <div class="padding _666 em__9" id="tips">交易密码须为6位数字</div>
    <input class="padding em_1" id="pay_pwd" type="password" maxlength="6" onkeyup="JustInt(this);" placeholder="请输入交易密码"/>
    <input class="padding em_1" id="rep_pay_pwd" type="password" maxlength="6" onkeyup="JustInt(this);" placeholder="请重复交易密码"/>
    <p class="padding lh_em_2 _666 em__9">交易密码用于提现、购买和转出</p>
    <div class="padding fd5457 em__8" id="msg">&nbsp;&nbsp;</div>
    <div class="padding">
        <div class="fff em_1 a_center _cursor _b_radius" id="btn">下一步</div>
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
    $("#set_pay_pwd_wraper #btn").click(function(){
        var pay_pwd = $("#pay_pwd").val();
        var rep_pay_pwd = $("#rep_pay_pwd").val();
        var preg_text = /^\d{6}$/;
        if( pay_pwd == '' ) {
            $("#msg").html("请输入交易密码");
            return false;
        } else if( !preg_text.test(pay_pwd) ) {
            $("#msg").html("密码格式有误！");
            return false;
        } else if( rep_pay_pwd == '' ){
            $("#msg").html("请重复交易密码");
            return false;
        } else if( pay_pwd != rep_pay_pwd ){
            $("#msg").html("密码两次不一致！");
            return false;
        }
        var url = "<?php echo ApiUrl::toRoute('user/set-paypassword',true); ?>";
        var url_redirect = "<?php echo Url::toRoute('site/index',true); ?>";
        if( getCookie('url_redirect') == 'go_kd' ){
            var url_redirect = "<?php echo Url::toRoute('site/kd',true); ?>";
        }
        userSetPayPassword(url,pay_pwd,url_redirect);
    });
    function toRelname(){
        var url_redirect = "<?php echo Url::toRoute('user/user-real-name-vertify',true); ?>";
        location = url_redirect;
    }
</script>