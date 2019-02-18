<?php
use newh5\components\ApiUrl;

?>
<?php if (isset($page_js)) {
    echo $page_js;
}; ?>
<?php if (isset($gdt)) {
    echo $gdt;
}; ?>
<!-- 补充css -->
<link rel="stylesheet" href="<?= $this->source_url(); ?>/themes/stream13/css/stream.css">
<div id="stream" class="streambg">
    <div class="con">
        <div class="input-form">
            <!-- 输入手机号-->
            <div class="user-input">
                <i class="iconUser"></i>
                <input class="_radiu_45px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入您的手机号码"/>
                <i class="iconCancel"></i>
            </div>
            <!-- 获取验证码 -->
            <div class="code-input">
                <i class="iconCode"></i>
                <input class="_input_sp _radiu_45px" type="text" id="code" maxlength="6" placeholder="请输入验证码"/>
                <button class="btn get_code _radiu_45px" id="autoGetCode">获取验证码</button>
            </div>
            <!--提交按钮-->
            <div class="submit btn _radiu_45px" id="autoRegister">领取免单</div>
        </div>
    </div>
    <div class="p_footer">
        <p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
        <p><?php echo SITE_ICP; ?></p>
    </div>
</div>
<script>
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#710db7', btn_txt_color: '#fff', btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {
        source_id:<?php echo $this->source_id;?>,
        source_tag: '<?php echo $this->source_tag;?>',
        source_app: '<?php echo $this->source_app?>'
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

    // 取消按钮
    $('#phone').on('keyup', function () {
        $('.iconCancel').css('display','block');
        if( $('#phone').val() =='' ){
            $('.iconCancel').css('display','none');
        }
    });
    $('.iconCancel').on('click',function () {
        $('#phone').val('');
        $('.iconCancel').css('display','none');
    });
</script>
