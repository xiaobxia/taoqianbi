<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style type="text/css">
#change_paypassword_wraper{min-height:100%;background:#fff;}
#change_paypassword_wraper .input_wraper{width:87%;padding-top:.7em;padding-bottom:.7em;border:1px solid #1782e0;background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_pwd.png') no-repeat 5% center;background-size:3.5%;}
#change_paypassword_wraper .input_wraper+.input_wraper{margin-top:1em;}
#change_paypassword_wraper .input_wraper input{margin-left:12%;width:60%;height:21px;line-height:21px;}
#change_paypassword_wraper .input_wraper+.input_wraper input{width:88%;}
#change_paypassword_wraper #set_pay_pwd{width:27%;border-left:1px solid #1782e0;}
#change_paypassword_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;border:1px solid #1782e0;}
</style>
<div id="change_paypassword_wraper">
    <p class="a_center lh_em_3"><?php echo \common\helpers\StringHelper::blurPhone($this->userName);?></p>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" type="password" id="old_pwd" name="old_pwd" maxlength="6" oninput="justInt(this);" onkeyup="justInt(this);" placeholder="请输入您的旧交易密码"/><span class="_inline_block a_center _999" id="set_pay_pwd" onclick="setPayPwd();">忘记密码</span>
    </div>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" type="password" id="new_pwd" name="new_pwd" maxlength="6" oninput="justInt(this);" onkeyup="justInt(this);" placeholder="请输入您的新交易密码，6位数字组成"/>
    </div>
    <p class="padding lh_em_3 _8d8d8d">＊新交易密码需由6位数字组成</p>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius">提交<a class="indie" href="javascript:changePaypassword();"></a></div>
</div>
<script type="text/javascript">
function changePaypassword() {
    var old_pwd = $.trim($('#old_pwd').val());
    var new_pwd = $.trim($('#new_pwd').val());
    if(!old_pwd){
        return showExDialog('请输入您的旧交易密码','确定');
    }
    if(!new_pwd){
        return showExDialog('请输入您的新交易密码','确定');
    }
    if(new_pwd.length != 6){
        return showExDialog('交易密码由6位数字组成','确定');
    }
    var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/change-paypassword'],true);?>";
    var params = {
        old_pwd:old_pwd,
        new_pwd:new_pwd
    };
    drawCircle();
    $.post(url,params, function(data){
        hideCircle();
        if(data && data.code == 0){
            jumpTo(getSourceUrl());
        }else if(data.message){
            showExDialog(data.message || '请求失败，请稍后重试','确定');
        }
    },'json');
}
function setPayPwd(){
    formPost("<?php echo Url::toRoute(['m-site/login'],true)?>",{type:4,phone:<?php echo $this->userName;?>,source_url:getSourceUrl()});
}
</script>