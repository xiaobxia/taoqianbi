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
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016040703" rel="stylesheet"/>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
		<h1 class="mui-title font-color1">立即还款</h1>
	</header> -->
	<div class="mui-content">
		<div id="personal-loan">
			<!-- <div class="loan-top">
				<div class="loan-txt">当前累计借款(元)</div>
				<div class="loan-money">52546.00</div>
			</div> -->
			<div class="loan-record">
				<div class="loan-action">
					还款方式：<br />
					1. 请将您的还款金额以支付宝转账的方式转入15102103575支付宝账户<br />
					2. 请将您的还款金额以银行卡转账的方式转入622848*******890018银行卡
				</div>
				<div class="loan-main">
					<?php if(!empty($delayed_list)): ?>
						<?php foreach($delayed_list as $value): ?>
							<div class="li-p border-b pos-relative">
								<div class="pl15 money" ><?php echo $value['repayment_money'];?></div>
								<div class="pl15 period"><?php echo $value['detail'];?></div>
								<div class="repay-date"><?php echo $value['repayment_time'];?></div>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
					<?php endif; ?>
					<?php if(!empty($repaying_list)): ?>
						<?php foreach($repaying_list as $value): ?>
							<div class="li-p border-b pos-relative" >
								<div class="pl15 money"><?php echo $value['repayment_money'];?></div>
								<div class="pl15 period"><?php echo $value['detail'];?></div>
								<div class="repay-date"><?php echo $value['repayment_time'];?></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php if(empty($delayed_list) && empty($repaying_list)):?>
						<div style="text-align:center;min-height:50px;line-height:50px">暂无还款信息</div>
					<?php endif;?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>