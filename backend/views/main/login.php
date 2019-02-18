<?php
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>登录管理中心</title>
	<link href="<?php echo Url::toStatic('/image/admincp.css'); ?>?t=2017022801" rel="stylesheet" type="text/css" />
	<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
    <link rel="shortcut icon" href="<?php echo Url::base(); ?>/favicon.ico">
</head>
<body>
<script type="text/JavaScript">
	if (self.parent.frames.length != 0) {
		self.parent.location=document.location;
	}
	function refreshCaptcha() {
		$.ajax({
			url: '<?php echo Url::toRoute(['main/captcha', 'refresh' => 1]); ?>',
			dataType: 'json',
			success: function(data) {
				$('#loginform-verifycode-image').attr('src', data.url);
			}
		});
	}
	function sendCaptcha() {
		$.ajax({
			url: "<?php echo Url::toRoute(['main/phone-captcha', 't' => time()]); ?>",
			type: 'get',
			dataType: 'json',
			data: {username: $('#LoginForm_username').val()},
			success: function(data){
				if (data.code == 0) {
					$('#send-captcha').val('发送成功').attr('disabled', 'disabled');
				} else {
					alert(data.message);
				}
			},
			error: function(){
				alert('发送失败');
			}
		});
	}
</script>
<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
<table class="logintb">
	<tr>
		<td class="login" style="width:210px;">
			<h1><?php echo APP_NAMES;?>管理中心</h1>
		</td>
		<td>
			<p style="color:red;"><?php if ($model->hasErrors()) { $_err = $model->getFirstErrors(); echo array_shift($_err); } ?></p>
			<p class="logintitle">用户名：</p>
			<p class="loginform"><input type="text" class="txt" placeholder="登录名/手机号" name="LoginForm[username]" id="LoginForm_username" value="<?php echo $model->username; ?>"></p>
			<p class="logintitle">密&#12288;码：</p>
			<p class="loginform"><input type="password" class="txt" name="LoginForm[password]" value="<?php echo $model->password; ?>"></p>
			<?php if (YII_ENV == 'prod'): ?>
				<p class="logintitle">验证码：</p>
				<p class="loginform" style="height:30px;width:200px;">
					<input type="text" name="LoginForm[phoneCaptcha]" value="<?php echo $model->phoneCaptcha; ?>" class="txt" id="loginform-phoneCaptcha" style="width:60px;"/>
					<input id="send-captcha" class="btn" type="button" value="发送验证码" onclick="sendCaptcha();" />
				</p>
			<?php endif; ?>

			<?php if (YII_ENV != 'prod'): ?>
			<p class="loginform" style="height:30px;width:200px;">
				<input type="text" name="LoginForm[verifyCode]" value="<?php echo $model->verifyCode; ?>" class="txt" id="loginform-verifycode" style="width:60px;vertical-align:top;">
				<img onclick="refreshCaptcha();" title="点击刷新验证码" src="<?php echo Url::toRoute(['main/captcha', 'v' => uniqid()]); ?>" id="loginform-verifycode-image">
			</p>
			<?php endif; ?>

			<p class="loginnofloat"><input type="submit" class="btn" value="登录" name="submit_btn"></p>
		</td>
	</tr>
</table>
<?php ActiveForm::end(); ?>
<?php
 if(!strpos($_SERVER["HTTP_USER_AGENT"],"Chrome")){

 	echo '<h1 style="color:red;">非高级浏览器，推荐<a href="http://rj.baidu.com/soft/detail/14744.html?ald" target="_blank">下载谷歌浏览器(点此下载)</a>，或开启浏览器的急速模式（当前模式下，可能无法收到【验证码】！）</h1>';
 }
?>
<table class="logintb">
	<tr>
		<td colspan="2" class="footer">
			<div class="copyright">
				<p></p>
				<p></p>
			</div>
		</td>
	</tr>
</table>
</body>
</html>
