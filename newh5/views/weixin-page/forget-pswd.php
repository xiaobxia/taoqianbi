<?php
use common\helpers\Url;
$this->title = APP_NAMES;
?>

		<style>

			.page-main {
				position: relative;
				width: 100%;
				padding-bottom: 0.68rem;
			}

			.banner {
				width: 100%;
				height: 5.8rem;
				background: url("<?php echo Url::toStatic('/images/b_bg.png') ?>") no-repeat;
				background-size: 100% 100%;
			}

			.chunk-wrap {
				position: absolute;
				top: 5rem;
				left: 50%;
				width: 8rem;
				margin-left: -4rem;
			}

			.form-wrap {
				width: 8rem;
				background-color: #fff;
				box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
				border-radius: 5px;
			}

			.form-login-wrap {
				height: 7.45rem;
			}

			.form-wrap .form-list {
				width: 100%;
				padding: 0 0.66rem;
			}

			.form-wrap .form-list .item-line {
				width: 100%;
			}

			.form-wrap .input-text {
				width: 100%;
				height: 1rem;
				padding-left: 0.16rem;
				border: none;
				border-bottom: 1px solid #d2d2d2;
				line-height: 0.52rem;
				font-size: 0.35rem;
			}

			.form-wrap .item-links {
				width: 100%;
				height: 0.22rem;
				line-height: 0.22rem;
				text-align: center;
				font-size: 0.22rem;
				color: #949494;
			}

			.form-wrap .item-links .link {
				margin: 0 0.1rem;
				font-size: 0.26rem;
				color: #949494;
			}

			.form-wrap .item-links .link:first-child span {
				color: #008aff;
			}

			.form-wrap .item-btn {
				width: 100%;
			}

			.form-wrap .item-btn .form-btn {
				width: 100%;
				height: 1rem;
				background-color: #008aff;
				border: none;
				border-radius: 0.05rem;
				color: #fff;
				font-size: 0.4rem;
			}

			.form-wrap .item-line-two {
				display: flex;
				justify-content: space-between;
			}

			.form-wrap .item-line-two .item:first-child {
				width: 3.5rem;
			}

			.form-wrap .item-line-two .item:last-child {
				width: 2.5rem;
			}

			.form-wrap .btn-code {
				display: block;
				width: 100%;
				height: 0.8rem;
				background-color: #008aff;
				border-radius: 0.05rem;
				color: #fff;
				font-size: 0.35rem;
				text-align: center;
				line-height: 0.8rem;
			}

			.form-login-wrap .form-list {
				height: 6.45rem;
				padding-top: 1.33rem;
			}

			.form-login-wrap .form-list .item-line:first-child {
				margin-bottom: 0.56rem;
			}

			.form-login-wrap .form-list .item-links, .form-login-wrap .item-btn {
				margin-top: 0.68rem;
			}

			.form-register-wrap .form-list {
				height: 8.98rem;
				padding-top: 0.63rem;
			}

			.form-register-wrap .form-list .item-line {
				margin-bottom: 0.35rem;
			}

			.form-register-wrap .form-list .item-line:first-child {
				margin-bottom: 0.36rem;
			}

			.form-register-wrap .item-btn {
				margin-top: 0.65rem;
			}

			.form-register-wrap .item-link {
				margin-top: 0.4rem;
				text-align: center;
                font-size: 0.3rem;
			}

			.form-register-wrap .item-link .link {
				line-height: 0.36rem;
				font-size: 0.3rem;
				color: #008aff;
				text-decoration: underline;
			}

			.copyright-wrap {
				margin-top: 0.55rem;
				margin-bottom:0.3rem;
				text-align: center;
				font-size: 0.3rem;
				color: rgba(0, 0, 0, 0.4);
			}
		</style>

		<div class="page-main">
			<div class="banner"></div>
			<div class="chunk-wrap">
				<div class="form-wrap form-register-wrap">
					<form>
						<div class="form-list">
							<div class="item-line">
								<input id="phone" type="text" class="input-text" placeholder="请输入手机号码">
							</div>
							<div class="item-line item-line-two">
								<div class="item">
									<input id="code" type="text" class="input-text input-code" placeholder="请输入短信验证码">
								</div>
								<div class="item">
									<a id="autoGetCode" href="javascript:;" class="btn-code">获取验证码</a>
								</div>
							</div>
							<div class="item-line">
								<input id="password" type="password" class="input-text" placeholder="请输入新的登陆密码">
							</div>
							<div class="item-line">
								<input id="re_password" type="password" class="input-text" placeholder="请再次输入新的登陆密码">
							</div>
							<div class="item-btn">
								<input id="changePassword"" type="button" class="form-btn" value="立即修改">
							</div>
							<div class="item-link">
								<a href="<?php echo Url::toRoute('weixin-page/user-login'); ?>" class="link">立即登录</a>
							</div>
						</div>
					</form>
				</div>
				<p class="copyright-wrap"><?php echo $company_name; ?></p>
			</div>
		</div>
		<script type="text/javascript">
			// 初始化数据
			var get_code_url = '<?= Url::to(["weixin-page/send-msg"], true);?>'; // 获取验证码链接
			var pwd_url = '<?= Url::to(["weixin-page/change-pwd"], true); ?>';
			var isfromweichat = <?= $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
			var pop_params = {btn_bg_color: '#6637C5', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
			var source = <?= \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT ?>;
			var reg_sms_key = '<?= $reg_sms_key;?>';

			$(function(){
				// 获取验证码
				$('#autoGetCode').click(function(){
					getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
				});
				$('#changePassword').click(function(){
					//判断两次输入密码是否一致
					var pwd = $('#password').val();
					var res_pwd = $('#re_password').val();
					var phone = $('#phone').val();
					var code = $('#code').val();
					if (pwd && res_pwd && pwd === res_pwd) {
						$.post(pwd_url,{phone:phone,pwd:pwd,res_pwd:res_pwd,code:code,isfromweichat:isfromweichat,reg_sms_key:reg_sms_key},function (data) {
							if(data.code == 0){
								showExDialog(data.message,"确定",'jump_url');
								setTimeout(function(){
									window.location.href= '<?= Url::to(["weixin-page/user-login"], true);?>';
								},10000)
							}else if(data.code == -1){
								showExDialog(data.message,"确定");
							}
						})
					}else{
						showExDialog("两次密码输入不一致，请重新输入","确定");
					}
				});
			});
			function jump_url() {
				window.location.href= '<?= Url::to(["weixin-page/user-login"], true);?>';
			}

		</script>
