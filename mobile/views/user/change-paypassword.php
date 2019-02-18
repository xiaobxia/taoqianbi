<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/style.css?v=20150601101">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/data.js" ></script>
<div id="change_pwd_wraper">
	<div class="padding _666 em__9" id="tips">交易密码须为6位数字</div>
	<input class="padding em_1" id="old_pwd" type="password" maxlength="6" onkeyup="JustInt(this);" placeholder="请输入原交易密码"/>
	<input class="padding em_1" id="new_pwd" type="password" maxlength="6" onkeyup="JustInt(this);" placeholder="请输入新的交易密码"/>
	<input class="padding em_1" id="rep_new_pwd" type="password" maxlength="6" onkeyup="JustInt(this);" placeholder="请重复新的交易密码"/>
	<div class="padding fd5457 em__8" id="msg">&nbsp;&nbsp;</div>
	<div class="padding">
		<div class="fff em_1 a_center _cursor _b_radius" id="btn">确认</div>
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
    function urlRedirect(){
        var url_redirect = "<?php echo Url::toRoute(['site/index'],true); ?>";
        location = url_redirect;
    }
    $("#change_pwd_wraper #btn").click(function(){
        var url = "<?php echo ApiUrl::toRoute(['user/change-paypassword'],true); ?>";
        changePwd(url);
    });
</script>