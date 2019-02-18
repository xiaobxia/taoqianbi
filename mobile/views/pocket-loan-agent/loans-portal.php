<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script src="<?php echo $baseUrl;?>/js/flexable.js"></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/swiper.min.css">
<style>
	html,body,.wrapper,.swiper-container,.swiper-wrapper{width: 100%;height: 100%;}
	.page1{background: #ecf9ff url(../image/loansPortal/bg1.png) 0 0 no-repeat;background-size: 100% auto;}
	.page1 button,.btn-submit{display: block;position: absolute;top:12.4rem;left: 50%;margin-left: -4.333333rem;width: 8.666667rem;height: 1.04rem;background-color: #fd5353;font-size: 17px;color: #fff;border:none;border-radius: 3px;}
	[data-dpr="2"] .page1 button,[data-dpr="2"] .btn-submit{font-size: 34px;border-radius: 6px;}
	[data-dpr="3"] .page1 button,[data-dpr="3"] .btn-submit{font-size: 51px;border-radius: 9px;}
	.page2 .input-group{position: relative;padding: 0 0.4rem;height: 1.2rem;line-height: 1.2rem;color:#333333;font-size: 15px;border-bottom: 1px solid #f2f2f2;}
	.page2 input{display: inline-block;padding-left: 0.4rem;border:none;}
	.page2 .btn-submit{position: static;margin: 0.973333rem auto;}
	.page2 .input-group button{display: block;position: absolute;top:0;right: 0;width: 2.893333rem;height: 100%;border-left: 1px solid #f2f2f2;color:#333;border:none;background-color: transparent;}
	.page2 .input-group .disable{background-color: #f2f2f2;}
	.notice{padding: 0.2rem 0.4rem 0;font-size: 12px;color: #999;}
	[data-dpr="2"] .page2 .input-group,[data-dpr="2"] .page2 .input-group button{font-size: 30px;}
	[data-dpr="3"] .page2 .input-group,[data-dpr="3"] .page2 .input-group button{font-size: 45px;}
	.page3{padding-top: 1.08rem;text-align: center;line-height: 1;}
	.page3 img{display: block;width: 1.226667rem;height: 1.226667rem;margin:0 auto;}
	.page3 .success{margin-top: 0.346667rem;margin-bottom: 1.773333rem;color:#333333;font-size: 17px;}
	[data-dpr="2"] .page3 .success{font-size: 34px;}
	[data-dpr="3"] .page3 .success{font-size: 51px;}
	.page3 .link{color:#666666;font-size: 13px;line-height: 2;}
	.page3 .link span{display: block;color:#00a0e9;}
	[data-dpr="2"] .page3 .link{font-size: 26px;}
	[data-dpr="3"] .page3 .link{font-size: 39px;}
	.page3 .link small{display: block;font-size: 12px;color: #fd5353;}
	[data-dpr="2"] .page3 .link small,[data-dpr="2"] .notice{font-size: 24px;}
	[data-dpr="3"] .page3 .link small,[data-dpr="3"] .notice{font-size: 36px;}
	.pop-box{position: fixed;top: 0px;left: 0px;width: 100%;height: 100%;z-index: 9999;display: -webkit-box;display: box;-webkit-box-orient: horizontal;-webkit-box-pack: center;-webkit-box-align: center;background: rgba(0, 0, 0, 0.4);}
	.pop-box .pop-con{box-sizing:border-box;position: relative;padding: 0.4rem;width: 84%;background-color: #fff;border-radius:0.2rem;text-align: center;}
	.pop-box .pop-con p{padding: 1rem 0;font-size: 14px;}
	.pop-box button{display: block;width: 100%;height: 1.04rem;background: #FD5353;color: #fff;border:none;border-radius:0.12rem;font-size: 14px;}
	[data-dpr="2"] .pop-box .pop-con p,[data-dpr="2"] .pop-box button{font-size: 28px;}
	[data-dpr="3"] .pop-box .pop-con p,[data-dpr="3"] .pop-box button{font-size: 42px;}
</style>
<div class="wrapper">
	<div class="swiper-container">
		<div class="swiper-wrapper">
			<div class="swiper-slide swiper-no-swiping page1"><button onclick="Apply()">我要赚佣</button></div>
			<div class="swiper-slide swiper-no-swiping page2">
				<div class="input-group"><label for="username">姓名</label><input type="text" name="username" required="required" placeholder="请输入姓名" id="username"></div>
				<div class="input-group"><label for="phone">手机号</label><input type="text" name="phone" required="required" placeholder="请输入手机号" id="phone"></div>
				<div class="input-group"><label for="code">验证码</label><input type="text" name="code" required="required" placeholder="请输入验证码" id="code"><button class="sendcode" id="sendcode">发送验证码</button></div>
				<p class="notice">*&nbsp;请先注册成为全民经纪人，坐享高额返佣</p>
				<button class="btn-submit" id="btn-submit">提交</button>
			</div>
			<div class="swiper-slide swiper-no-swiping page3">
				<img src="<?php echo $baseUrl;?>/image/loansPortal/checked.png">
				<p class="success">恭喜你成为经纪人</p>
				<p class="link">您的专属邀超链：<span id="arr">http://m.koudailc.com/quick-loan/page-haofang-info?source=2</span><small>/长按复制您的专属链接</small></p>
			</div>
		</div>
	</div>
</div>
<script src="<?php echo $baseUrl;?>/js/swiper.min.js"></script>
<script>
	function Apply(){
		window.location.href = "<?php echo Url::toRoute(['building/product-list'],true);?>";
	}
</script>