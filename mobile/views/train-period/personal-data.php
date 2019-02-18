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
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016040702" rel="stylesheet"/>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
		<h1 class="mui-title font-color1">我的资料</h1>
	</header> -->
	<div class="mui-content">
		<div id="personal-loan">
			<!-- <div class="loan-top">
				<div class="loan-txt">当前累计借款(元)</div>
				<div class="loan-money">52546.00</div>
			</div> -->
			<div class="loan-record">
				<div class="loan-title">
					<div class="pl15">个人信息</div>
				</div>
				<div class="loan-main">
					<div class="li-p">
						<div class="pl15">姓名: <?php echo $data['name'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">身份证号：<?php echo $data['id_number'] ?></div>
					</div>
					<div class="li-p">
						<div class="inline-b pl15">Q Q：<?php echo $data['qq'] ?></div>
						<div class="inline-b">婚姻状况：<?php echo $data['marital_status'] ?></div>
						<div class="clear"></div>
					</div>
					<div class="li-p">
						<div class="inline-b pl15">学历：<?php echo $data['degree'] ?></div>
						<div class="inline-b">就职状态：<?php echo $data['job_status'] ?></div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
			<div class="loan-record">
				<div class="loan-title">
					<div class="pl15">居住信息</div>
				</div>
				<div class="loan-main">
					<div class="li-p">
						<div class="pl15">现居地址：<?php echo $data['present_district_text'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">现居街道：<?php echo $data['present_address'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">家庭地址：<?php echo $data['family_district_text'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">家庭街道：<?php echo $data['family_address'] ?></div>
					</div>
				</div>
			</div>
			<div class="loan-record">
				<div class="loan-title">
					<div class="pl15">工作信息</div>
				</div>
				<div class="loan-main">
					<div class="li-p">
						<div class="pl15">公司名称：<?php echo $data['company_name'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">公司地址：<?php echo $data['company_pca'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">公司街道：<?php echo $data['company_address'] ?></div>
					</div>
					<div class="li-p">
						<div class="pl15">公司电话：<?php echo $data['company_phone'] ?></div>
					</div>
				</div>
			</div>
			<div class="loan-record">
				<div class="loan-title">
					<div class="pl15">联系人</div>
				</div>
				<div class="loan-main">
					<div class="li-p border-b">
						<div class="inline-b pl15">联系人：<?php echo $data['first_contact_name'] ?>（<?php echo $data['first_contact_relation'] ?>）</div>
						<div class="inline-b">联系方式：<?php echo $data['first_contact_phone'] ?></div>
						<div class="clear"></div>
					</div>
					<div class="li-p border-b">
						<div class="inline-b pl15">联系人：<?php echo $data['second_contact_name'] ?>（<?php echo $data['second_contact_relation'] ?>）</div>
						<div class="inline-b">联系方式：<?php echo $data['second_contact_phone'] ?></div>
						<div class="clear"></div>
					</div>
					<div class="li-p">
						<div class="inline-b pl15">联系人：<?php echo $data['third_contact_name'] ?>（<?php echo $data['third_contact_relation'] ?>）</div>
						<div class="inline-b">联系方式：<?php echo $data['third_contact_phone'] ?></div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>