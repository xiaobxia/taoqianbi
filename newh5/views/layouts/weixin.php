<?php
use common\assets\WeChatAsset;
use common\assets\CNZZAsset;
use common\helpers\Url;
WeChatAsset::register($this);
CNZZAsset::register($this);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

	<?php $this->beginPage();?>

	<head>
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="keywords" content="1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。">
		<meta name="description" content="1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。">
		<!--<meta name="viewport" content="width=device-width, initial-scale=0.5, maximum-scale=0.5, minimum-scale=0.5, user-scalable=no"/>-->
		<meta name="format-detection" content="telephone=no" />
		<title><?php echo $this->title; ?></title>
		<script src="<?php echo Url::toStatic('/js/common_hbqb.js'); ?>"></script>
		<script src="<?php echo Url::toStatic('/js/m-layouts.js');?>"></script>
		<?php $this->head();?>

	</head>

	<body>

		<?php $this->beginBody();?>

		<?php echo $content; ?>

		<?php $this->endBody(); ?>

	</body>

	<?php $this->endPage(); ?>

</html>