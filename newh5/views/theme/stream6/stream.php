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
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream6/css/stream.css">
<div id="stream" class="streambg">
	<img src="<?= $this->source_url();?>/themes/stream6/image/bannerBg.png" alt="" style="width: 100%;">
    <div class="con">	
    	<div class="_radiu_16px" id="rollshowbox">
    		<img class="rollline" src="<?= $this->source_url();?>/themes/stream6/image/lunbo.png" alt="">
    		<div id="rollshow">
    			
    		</div>
        </div>
        <div class="input-form">
	        <div class="user-input">
	        	<i class="iconUser"></i>
	        	<input class="_radiu_45px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
	        </div>
	        <div class="code-input">
	        	<i class="iconMsg"></i>
	            <input class="_input_sp _radiu_45px" type="text" id="code" maxlength="6" placeholder="请输入验证码" />
	            <button class="btn get_code _radiu_45px" id="autoGetCode">获取验证码</button>
	        </div>
	        <div class="pwd-input" style="display: none">
	        	<i class="iconPwd"></i>
	        	<input class="_radiu_45px" type="password" id="password" maxlength="16" placeholder="请设置6~16位登录密码" />
	        </div>
	        <div class="submit btn _radiu_45px" id="autoRegister">马上借</div>
	        <div class="checktext">
	            <img class="ture" src="<?= $this->source_url();?>/themes/stream6/image/ture.png" alt="">
	            注册即代表同意 
	            <a href="http://qbcredit.wzdai.com/credit-web/safe-login-txt?id=124" class="agreement">《<?php echo $this->title ? $this->title : APP_NAMES ?>用户使用协议》</a>
	        </div>
	        
	    </div> 
	    <div class="p_footer">
	        <p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
	        <p><?php echo SITE_ICP; ?></p>
	    </div>
    </div>
</div>
<script>
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#ff6457', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};

    var roll_url = "<?= ApiUrl::toRouteCredit(['credit-app/user-multi-message']);?>"; // 获取轮播数据地址
    $(function(){
        // 获取轮播数据且进行滚动轮播
        rollShowData("#rollshow", 1, roll_url, "#rollshowbox");

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
