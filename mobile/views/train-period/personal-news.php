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
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016040704" rel="stylesheet"/>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
		<h1 class="mui-title font-color1">消息通知</h1>
	</header> -->
	<div class="mui-content">
		<div id="personal-loan">
			<div class="personal-news">
				<div class="li-p border-b">
					<div class="news-pad">
						<div class="inline-b align-tl">还款通知</div>
						<div class="inline-b align-tr color-f2">2016-04-01 17:52:22</div>
						<div class="clear"></div>
					</div>
					<div class="color-f2 news-pad">距离您的培训分期借款第4期还款日（2016年4月7日）还剩7天，请您于7天内将本期还款金额转入口袋提供的还款账户，感谢您的配合。</div>
				</div>
				<div class="li-p border-b">
					<div class="news-pad">
						<div class="inline-b align-tl">还款通知</div>
						<div class="inline-b align-tr color-f2">2016-03-01 11:20:02</div>
						<div class="clear"></div>
					</div>
					<div class="color-f2 news-pad">距离您的培训分期借款第3期还款日（2016年3月7日）还剩7天，请您于7天内将本期还款金额转入口袋提供的还款账户，感谢您的配合。</div>
				</div>
				<div class="li-p">
					<div class="news-pad">
						<div class="inline-b align-tl">还款通知</div>
						<div class="inline-b align-tr color-f2">2016-02-01 10:00:25</div>
						<div class="clear"></div>
					</div>
					<div class="color-f2 news-pad">距离您的培训分期借款第2期还款日（2016年2月7日）还剩7天，请您于7天内将本期还款金额转入口袋提供的还款账户，感谢您的配合。</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>