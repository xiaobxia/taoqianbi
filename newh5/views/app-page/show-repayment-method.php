<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<style>
	*{
		margin:0;
		padding:0;
	}
	.image{
		position: relative;
		width:10.0rem;
        padding-bottom:1.4rem;
	}
	img{
		width:100%;
	}
	.mask{
		position: absolute;
		bottom: 0;
		background-color: #f8f8f8;
		width: 100%;
		height: 2rem;
		text-align: center;
	}
	.to-repay{
		position: fixed;
		bottom: 0;
		z-index: 99;
		width: 100%;
		height: 1.4rem;
		line-height: 1.4rem;
		background-color: #6a4dfc;
		color: #fff;
		font-size: 0.45rem;
		text-align: center;
	}
</style>
<div class="image">
	<img src="<?=$this->absBaseUrl;?>/image/app-page/repayment_methods_1.png?v=20180813">
	<img src="<?=$this->absBaseUrl;?>/image/app-page/repayment_methods_2.png?v=2018081302" >
	<!--<div class="mask"></div>-->
	<div class="to-repay">马上还款</div>
</div>
<script>
	$('.to-repay').on('click',function  () {
		if (window.browser.wx) {
			location.href = '/newh5/web/page/jshb-sms-two';
		}else{
			 nativeMethod.returnNativeMethod('{"type":"13"}');
		}
	})
</script>
