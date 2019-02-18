<?php
use newh5\components\ApiUrl;
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
                height: 7.25rem;
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
				font-size: 0.3rem;
				color: #949494;
			}

			.form-wrap .item-links .link {
				margin: 0 0.1rem;
				font-size: 0.3rem;
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
				font-size: 0.3rem;
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
				margin-top: 0.35rem;
				text-align: center;
			}

			.form-register-wrap .item-link .link {
				line-height: 0.36rem;
				font-size: 0.3rem;
				color: #008aff;
				text-decoration: underline;
			}

			.copyright-wrap {
				margin-top: 1.55rem;
				text-align: center;
				font-size: 0.3rem;
				color: rgba(0, 0, 0, 0.4);
			}
		</style>

		<div class="page-main">
			<div class="banner"></div>
			<div class="chunk-wrap">
				<div class="form-wrap form-login-wrap">
					<form>
						<div class="form-list">
							<div class="item-line">
								<input id="phone" type="tel" class="input-text" placeholder="请输入手机号码">
							</div>
							<div class="item-line">
								<input id="password" type="password" class="input-text" placeholder="请输入登陆密码">
							</div>
							<div class="item-links">
								<a href="<?php echo Url::to(['page/jshbreg']); ?>" class="link">立即<span>注册</span></a>|
								<a href="<?php echo url::to(['weixin-page/change-pwd']); ?>" class="link">忘记密码</a>
							</div>
							<div class="item-btn">
								<input id="autoRegister" type="button" class="form-btn" value="立即绑定">
							</div>
						</div>
					</form>
					<p class="copyright-wrap"><?php echo $company_name?></p>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			var get_code_url = '<?php echo Url::toRoute(['xqb-user/reg-get-code', 'clientTyp' => 'wap']);?>'; // 获取验证码链接
			var register_url = '<?php echo Url::toRoute(['xqb-user/register', 'clientTyp' => 'wap', 'appMarket' => $this->source_tag]); ?>';
			var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
			var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
			var source = {
				source_id: <?php echo $this->source_id;?>,
				source_tag: '<?php echo $this->source_tag; ?>',
				source_app: '<?php echo $this->source_app?>'
			};
			var roll_url = "<?= Url::toRoute(['credit-app/user-multi-message', 'clientTyp' => 'wap']); ?>"; // 获取轮播数据地址
			$(function(){
				var url = "<?= Url::toRoute(['weixin-page/user-login']); ?>"
				$('#autoRegister').click(function(){
					$.post(url, {
						phone: $('#phone').val(),
						pwd: $('#password').val()
					}, function(data){
						if(typeof data == 'object' && data.message){
							alert(data.message);
							if(data.code.toString()=='1'){
								location.href=url;
							}
						}else{
							alert('网络访问失败');
						}
					});
				});
			});
		</script>