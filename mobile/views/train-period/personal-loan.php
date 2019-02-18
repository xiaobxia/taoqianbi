<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
use common\models\LoanRecordPeriod;
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
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016040702" rel="stylesheet"/>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
		<h1 class="mui-title font-color1">我的借款</h1>
	</header> -->
	<div class="mui-content">
		<div id="personal-loan">
			<div class="loan-top">
				<div class="loan-txt">当前累计借款(元)</div>
				<div class="loan-money"><?php echo isset($total_money)?number_format($total_money/100,2):""?></div>
			</div>
			<?php foreach ($loan_record_list as  $value): ?>
				<div class="loan-record">
					<div class="loan-title">
						<div class="inline-b pl15"><?php echo isset($value['loanProject']['loan_project_name'])?$value['loanProject']['loan_project_name']:""?></div>
						<div class="inline-b"><?php echo isset(LoanRecordPeriod::$status[$value['status']])?LoanRecordPeriod::$status[$value['status']]:""?></div>
						<div class="clear"></div>
					</div>
					<div class="loan-main">
						<div class="li-p">
							<div class="inline-b pl15">借款金额：<?php echo isset($value['credit_amount'])?number_format($value['credit_amount']/100,2):""?>元</div>
							<div class="inline-b">借款期限：<?php echo isset($value['period'])?$value['period']:0 ?>期</div>
							<div class="clear"></div>
						</div>
						<div class="li-p">
							<div class="inline-b pl15">分期金额：<?php echo isset($value['loanRepayment']['period_repayment_amount'])?number_format($value['loanRepayment']['period_repayment_amount']/100,2):""?>元</div>
							<div class="inline-b">下一还款日：<?php echo isset($value['loanRepaymentPeriod']['plan_repayment_time'])?$value['loanRepaymentPeriod']['plan_repayment_time']:""?></div>
							<div class="clear"></div>
						</div>
						<div class="li-p">
							<div class="inline-b pl15">剩余还款期数：<?php echo isset($value['loanRepaymentPeriod']['remaining_period'])?$value['loanRepaymentPeriod']['remaining_period']:""?>期</div>
							<div class="inline-b">历史逾期次数：<?php echo isset($value['loanRepaymentPeriod']['late_period'])?$value['loanRepaymentPeriod']['late_period']:""?></div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>




		</div>
	</div>
</body>
</html>