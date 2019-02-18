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
<link rel="stylesheet" href="<?= $this->source_url(); ?>/themes/stream12/css/stream.css">

<div id="stream" class="streambg">
    <div class="con">
        <div class="input-form">
            <!-- 输入手机号-->
            <div class="user-input">
                <i class="iconUser"></i>
                <input class="_input_us _radiu_20px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入您的手机号码"/>
                <i class="iconCancel"></i>
            </div>
            <!-- 获取验证码 -->
            <div class="code-input">
                <i class="iconCode"></i>
                <input class="_input_sp _radiu_20px" type="text" id="code" maxlength="6" placeholder="请输入验证码"/>
                <button class="btn get_code _radiu_20px" id="autoGetCode">获取验证码</button>
            </div>
            <!--提交按钮-->
            <div class="submit btn _radiu_20px" id="autoRegister"></div>
        </div>
        <!--  关于福利-->
        <div class="about_welfare">
            <div class="_line clear">
                <div class="fl line"></div>
                <h3 class="fl">关于福利</h3>
                <div class="fl line"></div>
            </div>
            <div class="welfareBg"></div>
        </div>
        <!-- 关于极速荷包-->
        <div class="about_xybt">
            <div class="_line clear">
                <div class="fl line"></div>
                <h3 class="fl">关于<?php echo APP_NAMES;?></h3>
                <div class="fl line"></div>
            </div>
            <div class="xybtBg"></div>
        </div>
        <div class="p_footer">
            <p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
            <p><?php echo SITE_ICP;?></p>
        </div>
    </div>
</div>
<script>
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#f07366', btn_txt_color: '#fff', btn_txt_size: '0.4rem'}; // 弹框样式默认
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
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params,password);
        });
    });

    // 获取光标时 显示边框
    function focusAddBorder(select) {
        select.focus(function () {
            select.css('border','1px solid #e02e3b')
        });
        select.blur(function () {
            select.css('border','none')
        });
    }
    focusAddBorder($('._input_sp'));
    focusAddBorder($('._input_us'));

    // 取消按钮
    $('#phone').on('keyup', function () {
        $('.iconCancel').css('display','block');
    });
    $('.iconCancel').on('click',function () {
        $('#phone').val('');
        $('.iconCancel').css('display','none');
    });
</script>
