<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style type="text/css">
#register_wraper{min-height:100%;background:#fff;}
#register_wraper .input_wraper{width:87%;padding-top:.7em;padding-bottom:.7em;border:1px solid #1782e0;background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_msg.png') no-repeat 5% center;background-size:3.5%;}
#register_wraper .input_wraper+.input_wraper{margin-top:1em;background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_pwd.png') no-repeat 5% center;background-size:3.5%;}
#register_wraper .input_wraper input{margin-left:12%;width:60%;height:21px;line-height:21px;}
#register_wraper .input_wraper+.input_wraper input{width:88%;}
#register_wraper #action{width:27%;border-left:1px solid #1782e0;}
#register_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;border:1px solid #1782e0;}
</style>
<div id="register_wraper">
    <p class="a_center lh_em_3"><?php echo \common\helpers\StringHelper::blurPhone($phone);?></p>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" id="code" name="code" maxlength="6" oninput="justInt(this);" onkeyup="justInt(this);" placeholder="请输入短信验证码"/>
        <button class="_inline_block a_center _999" id="action" onclick="getCode();">获取</button>
    </div>
    <div class="input_wraper m_center _b_radius">
        <input class="em_1 _999" type="password" id="pwd" name="pwd" maxlength="16" placeholder="请设置<?php echo $type == 2 ? '登录' : ($type == 3 ? '新的登录' : '新的交易');?>密码，<?php echo $type == 4 ? '6位数字组成' : '6-16字符组成';?>"/>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius"><?php echo $type == 2 ? '注册' : '提交';?><a class="indie" href="javascript:submitAction();"></a></div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        getCode();
    });
    function getCode(){
        var phone = '<?php echo $phone?>';
        var params = {
            phone:phone
        };
        <?php if($type == 2):?>
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/reg-get-code'],true);?>";
        <?php else:?>
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/reset-pwd-code'],true);?>";
        params.type = "<?php echo $type == 3 ? 'find_pwd' : 'find_pay_pwd';?>";
        <?php endif;?>

        $.post(url, params, function(data){
            if(data && data.code == 0){
                getCodeCountDown();
            }else if(data && data.code == 1000){
                //已注册用户
                formPost("<?php echo Url::toRoute(['m-site/login'],true)?>",{type:1,phone:phone,source_url:"<?php echo urldecode($redirect_url)?>"});
            }else if(data.message){
                showExDialog(data.message || '验证码获取失败，请稍后重试！','确定');
            }
        },'json');
    }
    function submitAction(){
        var phone = '<?php echo $phone?>';
        var code = $.trim($('#code').val());
        var pwd = $.trim($('#pwd').val());
        if(!isPhone(phone)){
            return showExDialog('手机号码格式不正确','确定');
        }
        if(!code){
            return showExDialog('请输入验证码','确定');
        }
        if(!pwd){
            return showExDialog('密码不能为空','确定');
        }
        <?php if($type == 2):?>
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/register'],true);?>";
        <?php elseif($type == 3):?>
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/reset-password'],true);?>";
        <?php else:?>
        if(pwd.length != 6){
            return showExDialog('交易密码由6位数字组成','确定');
        }
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/reset-pay-password'],true);?>";
        <?php endif;?>
        var params = {
            phone:phone,
            code:code,
            password:pwd
        };
        drawCircle();
        $.post(url,params, function(data){
            hideCircle();
            if(data && data.code == 0){
                <?php if($type == 3):?>
                formPost("<?php echo Url::toRoute(['m-site/login'],true)?>",{type:1,phone:phone,source_url:"<?php echo urldecode($redirect_url)?>"});
                <?php else:?>
                jumpTo("<?php echo urldecode($redirect_url)?>");
                <?php endif;?>
            }else if(data.message){
                showExDialog(data.message || '请求失败，请稍后重试','确定');
            }
        },'json');
    }
</script>