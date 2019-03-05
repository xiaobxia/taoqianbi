<?php
use yii\helpers\Url;
use  mobile\components\ApiUrl;
?>
<style>
	*{
		margin:0;
		padding:0;
	}
	.image{
		position: relative;
		width: 10.0rem;
        padding-bottom:1.4rem;
	}
	img{
		width: 100%;
	}
	.to-loan{
		position: fixed;
		bottom: 0;
		z-index: 99;
		width: 100%;
		height: 1.4rem;
		line-height: 1.4rem;
		background-color: #1ec8e1;
		color: #fff;
		font-size: 0.45rem;
		text-align: center;
	}
	.mask{
		position: absolute;
		bottom: 0;
		width: 100%;
		height: 2rem;
		background-color: #fff;
	}
</style>
<div class="image">
	<img src="<?=$this->absBaseUrl;?>/image/app-page/loan-procedure-1.png?v=20180813">
	<img src="<?=$this->absBaseUrl;?>/image/app-page/loan-procedure-2.png?v=20180813">
	<!--<div class="mask"></div>-->
	<div class="to-loan">马上借钱</div>
</div>
<script>
	$('.to-loan').on('click',function  () {
		if (window.browser.wx) {
			location.href = '/newh5/web/page/jshbreg';
		}else{
			 nativeMethod.returnNativeMethod('{"type":"4"}');
		}
	})

</script>
