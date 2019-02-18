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
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream2/css/stream.css">
<div class="layout" id="page-turntable">
	<img src="<?= $this->source_url();?>/themes/stream2/image/page-turntable/banner.png" alt="" style="width: 100%;">
	<div class="main">
		<div class="input-form">
			<div class="form-box _radiu_20px">
				<div class="_input">
					<i class="iconfont telIcon"></i>
					<input class="_radiu_20px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
					<i class="iconfont" id="tel-input-close"></i>
				</div>
				<div class="input-code">
					<i class="iconfont"></i>
					<input class="_radiu_20px" type="text" id="code" maxlength="6" placeholder="请输入短信验证码" />
					<button class="btn _radiu_20px get-code" id="autoGetCode">获取验证码</button>
				</div>
				<div class="btn _radiu_20px" id="autoRegister"></div>
			</div>
		</div>
		<div class="feature">
			<img src="<?= $this->source_url();?>/themes/stream2/image/page-turntable/feature.png" alt="">
		</div>
		<div class="footer">
			@ 2015-2017 <?php echo COMPANY_NAME;?><br>
            <?php echo SITE_ICP; ?>
		</div>
	</div>
</div>
<script type="text/javascript">
	// 初始化数据
	var get_code_url = '<?= ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
	var register_url = '<?= ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
	var reg_sms_key = '<?= $reg_sms_key;?>'; // 验证码防刷key
	var isfromweichat = '<?= $this->isFromWeichat() ? 1 : 0;?>'; // 是否在微信里
	var pop_params = {btn_txt_size: '0.4rem'}; // 弹框样式默认
	var source = {
		source_id: <?= $this->source_id;?>,
		source_tag: '<?= $this->source_tag;?>',
		source_app:'<?= $this->source_app?>'
	};
	$(function() {
		// 获取验证码
		$('#autoGetCode').click(function(){
			getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params,"","num S 后重试")
		});
		$('#autoRegister').click(function(){
			var password = $("#password").length > 0 ? $("#password").val() : undefined;
			register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
		});
	});

	//  input框添加删除按钮
    $("#phone").bind("keyup",function(){
        $("#tel-input-close").show();
    });
    $("#tel-input-close").click(function(){
        $("#phone").val("");
        $("#tel-input-close").hide();
	});
	
	/**
 * 获取验证码倒计时
 * @params btnTips1 默认 获取
 * @params btnTips2 默认 num秒
 * @params eleId 默认 action
 * @params fun 默认 getCode
 * @params Tcolor 倒计时文字颜色
 */
function getCodeCountDown(btnTips1, btnTips2, eleId, fun) {
    btnTips1 = btnTips1 || '获取';
    btnTips2 = btnTips2 || 'num秒';
    eleId = eleId || 'autoGetCode';
    fun = fun || 'getCode();';
    // Tcolor = Tcolor || 'inherit';
    var obj = ID(eleId);
    var _div = document.createElement("div");
    var second = document.createElement("i");
    second.id = 'second';
    second.innerHTML = 60;
    _div.appendChild(second);
    obj.style.color = '#969696';           //未点击倒计时文字颜色
    obj.innerHTML = strReplace(btnTips2, 'num', _div.innerHTML);
    obj.setAttribute('disabled', 'true'); // 倒计时标签需使用button
    countdown();

    function countdown() {
        var obj1 = ID('second');
        obj1.innerHTML = intval(obj1.innerHTML) - 1;
        obj.style.cursor = 'not-allowed';
        //倒计时结束
        if (obj1.innerHTML <= 0) {
            window.clearInterval(timing);
            obj.innerHTML = btnTips1;
            obj.removeAttribute('disabled'); // 倒计时标签需使用button
			obj.style.cursor = '';
			obj.style.color = '#1782e0';  //倒计时开始时文字颜色
        }
    }
    var timing = window.setInterval(countdown, 1000);
}
</script>