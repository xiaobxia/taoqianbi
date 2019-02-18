<!DOCTYPE html>
<html>
    <?php

    use yii\helpers\Url;
    use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
    ?>
    <head>
        <meta charset="UTF-8">
        <title>慢就赔活动</title>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/flexible.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/jquery.js?v=2016112901"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/common.js?=2016120903"></script>
        <link href="<?= $this->staticUrl('css/act/slow.css?v=2016113001'); ?>" rel="stylesheet" />
    </head>
    <body>
        <style type="text/css">
            body {
                background-color: #fbca04;
            }
        </style>
        <div class="slow-rule-body">
		<h2>活动规则</h2>
		<div class="content">
			<h3>1.活动时段：</h3>
			<p>2016年12月5日至2017年1月15日 每日9：00-18：00</p>
			<h3>2. 什么情况下可获得红包赔偿？</h3>
			<p><?php echo APP_NAMES;?>的注册用户（新老用户均可）在活动时段内申请借款，如成功提交申请，且审核通过时间大于2小时，就可获得慢就赔红包赔偿，红包内含现金抵扣券。</p>
			<h3>3. 在哪里可以领取到红包赔偿？</h3>
			<p><span>第一步：</span>当你符合赔偿条件时，系统将会自动显示赔偿红包领取图标<i></i>，您可在首页审核结果页面，或“借款记录-借款详情”中找到，图标显示时间为7天，如7天内您还未领取，则视为自动放弃，图标将不再显示。</p>
			<p><span>第二步：</span>点击图标领取红包，根据页面提示操作成功后，系统将随机发放一张现金抵用券（5~100元）至您的账户中，可至“我的优惠”中查看，现金抵用券使用有效期为30天，请在有效期内使用。</p>
			<p class="notice"><span>注意：</span>
				a. 慢就赔只有在借款审核通过的情况下才可参加，首次审核不通过可参加拒就赔活动（详情见拒就赔活动说明页）；<br>
				b. 赔偿红包领取图标只有在符合条件后才会在相关页面显示；<br>
				c. 红包取时间为贷款申请成功之日起7天内，过期作废。
			</p>
			<p><span>4.</span> 活动期间每天按照申请顺序限量发出10000个慢就赔红包，如果截止当日23:59:59仍未发放完，则当日剩余红包作废。</p>
			<p><span>5.</span> 针对利用非法手段恶意刷奖的用户，平台有权利取消红包领取资格。</p>
                        <p><span>6.</span>活动最终解释权归<?php echo APP_NAMES;?>所有，如有疑问请联系：<a href="javascript:callPhoneMehtod('400-681-2016')" >400-681-2016</a></p>
                        
		</div>
	</div>
    </body>

</html>
