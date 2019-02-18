<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>ios安装贴士</title>
	<style>
		*{margin: 0;padding: 0;}
		.layout{
			position: relative;
			margin:0 auto;
			width: 100%;
			height: 22.458333rem;
			background: url('<?= $this->source_url();?>/image/jshb-reg/ios_tips.png') no-repeat center center/cover;
		}
		.layout a{
			position: fixed;
			bottom: 0;
			left: 50%;
			margin-left: -4rem;
			width: 7.986111rem;
			height: 1.583333rem;
			background: url('<?= $this->source_url();?>/image/jshb-reg/ios_btn.png') no-repeat center center/cover;
		}
	</style>
</head>
<body>
	<div class="layout">
		<a href="<?php echo APP_IOS_DOWNLOAD_URL; ?>"></a>
	</div>
</body>
</html>