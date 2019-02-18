<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<?php if(isset($page_js)){
    echo $page_js;
};?>
<?php if(isset($gdt)){
    echo $gdt;
};?>
<!-- 补充css -->
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream10/css/stream.css">
<div id="stream" class="streambg">
    <div class="con">
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
	        <div class="submit btn _radiu_45px" id="autoRegister">马上借</div>
	    </div>
    </div>
</div>
<script>
    // 初始化数据
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#ff6156', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};
    var roll_url = "<?=  ApiUrl::toRouteCredit(['credit-app/user-multi-message']);?>"; // 获取轮播数据地址
   
    var verify_code_url = '<?php echo  ApiUrl::toRoute(["xqb-user/verify"]); ?>'; //生成验证码url
    var check_verify_code_url = '<?php echo  ApiUrl::toRoute(["xqb-user/check-verify"]); ?>';//检查验证码url
    $(function(){
        // 获取轮播数据且进行滚动轮播
        rollShowData("#rollshow", 1, roll_url, "#rollshowbox");

        // 检测图片验证码获取短信
       $('#autoGetCode').click(function(){
            getImgCode($("#phone").val(),source,reg_sms_key,isfromweichat,pop_params,verify_code_url,check_verify_code_url);
        });
        $('#autoRegister').click(function(){
            var password = $("#password").length > 0 ? $("#password").val() : undefined;
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
        });
    });
    
</script>
