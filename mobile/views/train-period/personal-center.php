<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title><?php echo $this->title ? $this->title : '口袋快借'; ?></title>
    <!--标准mui.css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.css" rel="stylesheet"/>
    <!--App自定义的css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016041102" rel="stylesheet"/>
</head>
<body>
	<div class="center-p color-f1" id="content-top">
		<div class="pc-title">个人中心</div>
		<div class="pc-person color-f1">
			<div class="pc-hear-bg">
				<img src="<?php echo $this->absBaseUrl; ?>/image/train-period/head-pic.png" alt="" class="pc-hear-pic" />
			</div>
			<div class="center-p pc-user"><?php echo $name;?></div>
		</div>
		<div class="pc-action">
			<a href="<?php echo $this->absBaseUrl; ?>/train-period/page-apply" class="action-w color-f1 left-p">申请分期&nbsp;&nbsp;<span class="action-r"></span></a>
			<div class="action-w">&nbsp;</div>
			<div class="action-w">&nbsp;</div>
			<a href="<?php echo $this->absBaseUrl; ?>/train-period/page-personal-repay" class="action-w color-f1 right-p">立即还款&nbsp;&nbsp;<span class="action-r"></span></a>
			<div class="clear"></div>
		</div>
	</div>
	<ul class="mui-table-view" id="content-main">
		<li class="mui-table-view-cell">
			<a href="<?php echo $this->absBaseUrl; ?>/train-period/page-personal-loan" class="mui-navigate-right"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/my-dk.png?v=2016041101" alt="" class="pic-dk"/>我的借款</a>
		</li>
		<li class="mui-table-view-cell">
			<a href="<?php echo $person_url;?>" class="mui-navigate-right"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/msg-tz.png?v=2016041101" alt="" class="pic-zl"/>我的资料</a>
		</li>
		<li class="mui-table-view-cell">
			<a href="<?php echo $this->absBaseUrl; ?>/train-period/page-trust" class="mui-navigate-right"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/my-zl.png?v=2016041101" alt="" class="pic-tz"/></span>征信授权</a>
		</li>
	</ul>
</body>
</html>