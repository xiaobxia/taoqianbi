<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<style type="text/css">
	.btn{
	display: inline-block;
	width: 100%;
	height: 1.053333rem;
	border:none;  
	background-color: #fa5558;
	border-radius: 0.106667rem;
	font-size: 0.453333rem;
	}
	.word{
		color:#ffffff;
		font-weight: bold;
		font-size: 0.453333rem;
	}
	.result footer {
		padding:0 0.4rem;
	}
	.result footer div {
		/*display: inline-block;*/
		float: left;
		width: 50%;
	}
	.one {
		padding-right: 0.093333rem;
		box-sizing: border-box;
	}
	.two {
		padding-left: 0.093333rem;
		box-sizing: border-box;
	}
	.bg img {
		width: 2.6rem;
		height: 2.6rem;
	}
	.bg {
		margin: 1.773333rem 0 0.32rem;
		text-align: center;
	}
	.detail {
		font-size: 0.346667rem;
		line-height: 0.506667rem;
		text-align: center;
		color: #333;
		margin-bottom: 1.84rem;
	}
</style>
<div class="result">
	<div class="bg">
		<img src="<?php echo $baseUrl;?>/image/building/right.png">
	</div>
	<p class="detail">您的订单已成功提交！<br/>请等待结果反馈，会以电话通知您！</p>
	<footer>
		<div class="one">
			<button class="btn" onclick="continue_sumbit()"><span class="word">继续提交</span></button>
		</div>		
		<div class="two" >
			<button class="btn" onclick="look_detail()"><span class="word">查看订单</span></button>
		</div>
	</footer>
</div>
<script type="text/javascript">
	function continue_sumbit() {
		window.location.href = "<?php echo Url::toRoute('building/product-list');?>";
	}
	function look_detail() {
		window.location.href = "<?php echo Url::toRoute('building/personal-order');?>";
	}
</script>