<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style type="text/css">
#login_wraper{min-height:100%;background:#fff;}
#login_wraper .logo_wraper{padding-top:3em;padding-bottom:3em;}
#login_wraper .input_wraper{width:87%;padding-top:.7em;padding-bottom:.7em;border:1px solid #1782e0;background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_pwd.png') no-repeat 5% center;background-size:3.5%;}
#login_wraper .input_wraper input{margin-left:12%;width:60%;height:21px;line-height:21px;}
#login_wraper #find_pwd{width:27%;border-left:1px solid #1782e0;}
#login_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;border:1px solid #1782e0;}
</style>
<div id="login_wraper">
    <p class="a_center lh_em_3"><?php echo \common\helpers\StringHelper::blurPhone($phone);?></p>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" type="password" id="pwd" name="pwd" maxlength="16" placeholder="请输入登录密码"/><span class="_inline_block a_center _999" id="find_pwd" onclick="toFindPwd();">忘记密码</span>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius">登录<a class="indie" href="javascript:login();"></a></div>
</div>
<script type="text/javascript">
    function login(){
        var phone = '<?php echo $phone?>';
        var pwd = $.trim($("#pwd").val());
        if(!isPhone(phone)){
            return showExDialog('手机号码格式不正确','确定');
        }
        if(!pwd){
            return showExDialog('请输入登录密码','确定');
        }
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/login'],true)?>";
        $.post(url, {username:phone,password:pwd}, function(data){
            if(data && data.code == 0){
                jumpTo("<?php echo urldecode($redirect_url)?>");
            }else{
                showExDialog(data.message || '登录失败，请稍后重试','确定');
            }
        },'json');
    }
    function toFindPwd(){
        var phone = '<?php echo $phone?>';
        formPost("<?php echo Url::toRoute(['m-site/login'],true)?>",{type:3,phone:phone,source_url:"<?php echo urldecode($redirect_url)?>"});
    }
</script>