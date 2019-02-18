<?php
use newh5\components\ApiUrl;
?>
<!-- 补充css -->
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream/css/stream.css">
<?php if(isset($page_js)){
    echo $page_js;
};?>
<?php if(isset($gdt)){
    echo $gdt;
};?>
<link>
<style>
    #app_reg_wraper{
          background: #001934 url("<?= $this->source_url();?>/themes/stream/image/banner.png") no-repeat center center;
    }
</style>
<div id="app_reg_wraper">
    <div class="input-form _radiu_10px">
        <div class="form-box">
            <input class="_radiu_10px" type="text" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
            <div class="input-code">
                <input class="_radiu_10px" type="text" id="code" maxlength="6" placeholder="请输入短信验证码" />
                <button class="btn _radiu_10px get-code" id="autoGetCode">获取验证码</button>
            </div>
            <div class="btn _radiu_10px" id="autoRegister">极速兑换</div>
        </div>
    </div>
    <div class="footer">
        @ 2011~<?= date('Y'); ?> <?php echo COMPANY_NAME;?>
        <br>
        <?php echo SITE_ICP; ?>
    </div>
</div>
<script type="text/javascript">
    // 初始化数据
    var get_code_url = '<?= ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?= ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?= $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?= $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {
        source_id: <?= $this->source_id;?>,
        source_tag: '<?= $this->source_tag;?>',
        source_app:'<?= $this->source_app?>'
    };
    $(function() {
        // 获取验证码
        $('#autoGetCode').click(function(){
            getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
        });
        $('#autoRegister').click(function(){
            var password = $("#password").length > 0 ? $("#password").val() : undefined;
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
        });
    });
</script>