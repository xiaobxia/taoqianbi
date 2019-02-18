<?php
use newh5\components\ApiUrl;
?>
<?php if(isset($page_js)){
    echo $page_js;
};?>
<?php if(isset($gdt)){
    echo $gdt;
};?>
<!-- 补充css -->
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream1/css/stream.css">
<!-- 补充js -->
<div class="layout">
    <div id="new_channel_reg_one">
        <img src="<?= $this->source_url();?>/themes/stream1/image/new-channel-reg-one/banner.png" alt="">
        <ul>
            <li class="zhouqi">
                <i></i>
                <p>
                    <span>周期短</span>
                    <span>14天可循环</span>
                </p>
            </li>
            <li class="linghuo">
                <i></i>
                <p>
                    <span>更灵活</span>
                    <span>500-1万元</span>
                </p>
            </li>
            <li class="fangkuan">
                <i></i>
                <p>
                    <span>放款快</span>
                    <span>最慢3分钟</span>
                </p>
            </li>
        </ul>
        <div class="input-form _radiu_10px">
            <div class="title">————— 测测你的信用分能借多少 —————</div>
            <div class="form-box">
                <input class="_radiu_10px" type="text" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
                <div class="input-code">
                    <input class="_radiu_10px" type="text" id="code" maxlength="6" placeholder="请输入短信验证码" />
                    <button class="btn _radiu_10px get-code" id="autoGetCode">获取验证码</button>
                </div>
                <div class="btn _radiu_10px" id="autoRegister">立即申请</div>
            </div>
        </div>
        <div class="footer">
            @ 2011~2017 <?php echo COMPANY_NAME;?><br>
            <?php echo SITE_ICP; ?><br>
            电话：<?php echo SITE_TEL; ?> &nbsp;&nbsp;地址：<?php echo COMPANY_ADDRESS;?>
        </div>
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