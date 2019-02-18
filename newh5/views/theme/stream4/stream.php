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
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream4/css/stream.css">
<div class="layout" id="page-turntable">
	<img src="<?= $this->source_url();?>/themes/stream4/image/banner.png" alt="" style="width: 100%;">
	<div class="main _radiu_40px">
		<div class="input-form _radiu_20px">
			<img src="<?= $this->source_url();?>/themes/stream4/image/registerNow.png" alt="">
			<div class="form-box _radiu_20px">
				<div class="_input">
					<i class="iconfont telIcon"></i>
					<input class="_radiu_20px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
				</div>
				<div class="input-code">
					<i class="iconfont timeIcon"></i>
					<input class="_radiu_20px" type="text" id="code" maxlength="6" placeholder="请输入验证码" />
					<button class="btn _radiu_20px get-code" id="autoGetCode">获取验证码</button>
				</div>
				<div class="input-password" style="display: none">
					<i class="iconfont pwdIcon"></i>
					<input class="_radiu_20px" type="text" id="password" maxlength="8" placeholder="请输入密码" />
				</div>
				<div class="btn _radiu_20px" id="autoRegister"></div>
			</div>
		</div>
	</div>
	<div class="footer">
		<p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
		<p><?php echo SITE_ICP; ?></p>
	</div>
</div>
<script type="text/javascript">
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#ff8c00', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};


    $(function(){
        // 获取验证码
        $('#autoGetCode').click(function(){
            getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
        });
        $('#autoRegister').on('click',function(){
			var password = $("#password").length > 0 ? $("#password").val() : undefined;
			register($("#phone").val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
        })
    });
    
</script>