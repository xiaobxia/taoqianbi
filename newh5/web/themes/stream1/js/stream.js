// 初始化数据
var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
var isfromweichat = '<?php echo $this->isFromWeichat() ? 1 : 0;?>'; // 是否在微信里
var pop_params = {btn_txt_size: '0.4rem'}; // 弹框样式默认
var source = {source_id:'<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};
$(function(){
    // 获取验证码
    $('#autoGetCode').click(function(){
        getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
    });
    $('#autoRegister').click(function(){
        register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
    });
});
