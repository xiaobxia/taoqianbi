<?php
use yii\helpers\Url;
?>
<style type="text/css">
#verify_user_wraper{min-height:100%;background:#fff;}
#verify_user_wraper .logo_wraper{padding-top:3em;padding-bottom:3em;}
#verify_user_wraper .input_wraper{width:87%;padding-top:.7em;padding-bottom:.7em;border:1px solid #1782e0;background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_phone.png') no-repeat 5% center;background-size:3.5%;}
#verify_user_wraper .input_wraper input{margin-left:12%;width:88%;height:21px;line-height:21px;}
#verify_user_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;border:1px solid #1782e0;}
</style>
<div id="verify_user_wraper">
    <p class="logo_wraper a_center"><img src="<?php echo $this->absBaseUrl;?>/image/common/logo_120.png?v=2017032301" width="22.5%"></p>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" id="phone" name="phone" maxlength="11" oninput="justInt(this);" onkeyup="justInt(this);" placeholder="请输入手机号"/>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius">下一步<a class="indie" href="javascript:nextStep();"></a></div>
</div>
<script type="text/javascript">
    function nextStep(){
        var phone = $.trim($('#phone').val());
        if(!isPhone(phone)){
            return showExDialog('请输入正确的手机号码','确定');
        }
        formPost("<?php echo Url::toRoute(['m-site/login'],true)?>",{phone:phone,source_url:getSourceUrl()});
    }
</script>