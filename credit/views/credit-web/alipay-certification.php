<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();

?>

<div class="alipay-certification">
	<!-- <div style="display:none"> -->
	<div id="inputInfo">
		<style>body{background:#f2f2f2}</style>
		<h3 class="tips">请填写正确的支付宝帐号与密码</h3>
		<ul>
			<li class="clearfix">
				<label for="">支付宝帐号</label>
				<input id="account" class="user" name="" type="text" value=""/>
			</li>
			<li class="clearfix">
				<label for="">支付宝密码</label>
				<input id="password" name="" type="password" value=""/>
			</li>
			<li class="code clearfix" id="auth_img" style="display:none">
				<input placeholder="请输入图片验证码！"  name="" type="text" value=""/>
				<img onclick="refreshCaptcha();" title="点击刷新验证码" src="<?php echo Url::toRoute(['credit-info/captcha', 'v' => uniqid()]); ?>" id="verifycode-image">
			</li>
		</ul>

		<p class="err_msg error" style="display:none">支付宝帐号或密码不能为空</p>

		<button id="btn_check" class="common-button">马上认证</button>
	</div>

	<div id="inputCaptcha" style="display:none">
		<ul>
			<li class="tel"><?php echo $phone;?></li>
			<li class="code clearfix">
				<input placeholder="请输入短信校验码" id="captcha" name="" type="text" value=""/>
				<a href="JaveScript:;" id="resend" class="send" style="background-color: gray">还需60秒</a>
			</li>
		</ul>

		<p class="err_msg error" style="display:none">验证码不能为空</p>
		<button id="btn_submit" class="common-button">确认提交</button>
	</div>
	<div class="result" id="inputRes" style="display:none">
		<div id="res1" style="display:none">
			<img alt="恭喜通过支付宝认证" src="/credit/img/img-1.png"/>
			<p>恭喜通过支付宝认证</p>
		</div>
		<div id="res2" style="display:none">
			<img alt="认证失败，请重新认证" src="/credit/img/img-2.png"/>
			<p>认证超时，请重新认证</p>
			<p><a href="<?php echo Url::to(['credit-web/alipay-certification']);?>">重新认证></a></p>
		</div>
	</div>
</div>

<script>

	$(function () {
		var caption_count  = 0;
		var input_err_count= 0;

		// 验证
		$('#btn_check').click(function () {
            var account  = $("#account").val();
            var password = $("#password").val();

            if (input_err_count > 1) {
            	$('#auth_img').show();
            }else{
            	$('#auth_img').hide();
            }

            if (!account || !password) {
            	$('#inputInfo .err_msg').show();
            	input_err_count++;
            }else{
            	$('#inputInfo .err_msg').hide();

	            var url   = '<?php echo Url::to(['credit-info/get-alipay-info']);?>';
	            var param = {
	            	"account" : account,
	            	"password": password,
	            	"isupdate": 2
	            };

	            $.ajax({
	                url : url,
	                type : 'post',
	                dataType : 'json',
	                data: param,
	                success : function(data){

	                	if (data.code == 0) {
	                		input_err_count = 0;
	                		$("#inputInfo").hide();
	                		// 验证码倒计时
							$("#inputCaptcha").show();
	                		captionCountDown();
	                		sendCaptcha();
	    					$("#inputRes").hide();
	                	}else{
	                		input_err_count++;
	                	}
	                }
	            });
            }
        });

		// 短信验证码
		$('#resend').click(function(e){
            captionCountDown();
            sendCaptcha();
            return false;
        });

        // 请求发送验证码
        function sendCaptcha(){
        	if(caption_count > 1){
                return false;
            }
            var url = '<?php echo Url::to(['credit-info/send-alipay-auth']);?>';
            $.ajax({
                url : url,
                type : 'get',
                dataType : 'json',
                success : function(data){
                	if(data.code == 0){
                        $('#inputCaptcha .err_msg').html('发送成功').show();
                    }else{
                        $('#inputCaptcha .err_msg').html(data.message).show();
                    }
                }
            });
        }

        //验证码计时
        function captionCountDown(){
            caption_count = 60;
            caption_intval = window.setInterval(function(){
                if(caption_count > 1){
                    caption_count -= 1;
                    $('#resend').html('还需'+caption_count+'秒');
                }else{
                    window.clearInterval(caption_intval);
                    $('#resend').html('重新发送').css('background-color','#1ec8e1');
                }
            },1000);
        };

        // 确认提交
        $("#btn_submit").click(function(){
        	var captcha  = $("#captcha").val();

            if (!captcha) {
            	$('#inputCaptcha .err_msg').show();
            }else{
            	// 验证发送验证码AuthBindAlipay
            	var url = '<?php echo Url::to(['credit-info/auth-bind-alipay']);?>';
	            $.ajax({
	                url : url,
	                type : 'post',
	                dataType : 'json',
	                data:{captcha:captcha},
	                success : function(data){
	                	console.log(data);
	                	$('#inputCaptcha .err_msg').hide();
		            	$("#inputInfo").hide();
			    		$("#inputCaptcha").hide();
	                    if(data.code == 0){
				    		$("#inputRes").show();
				    		$("#res1").show();
	                    }else{
	                        // 验证失败
                        	$('#inputCaptcha .err_msg').html(data.message).show();
	                    }
	                }
	            });
            }
        })
    })



	function refreshCaptcha() {
		$.ajax({
			url: '<?php echo Url::toRoute(['credit-info/captcha', 'refresh' => 1]); ?>',
			dataType: 'json',
			success: function(data){
				$('#verifycode-image').attr('src', data.url);
			}
		});
	}
</script>
