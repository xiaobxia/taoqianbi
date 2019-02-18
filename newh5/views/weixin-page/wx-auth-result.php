<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style>
	#wx-auth-result.layout{
		width: 100%;
		height: 100%;
		display: none;
	}
	#wx-auth-result.layout .img{
		width: 5.546667rem;
		height: 3.333333rem;
		margin: 2.24rem auto 0;
		background: url("<?=$this->absBaseUrl;?>/image/page/weixinrenzheng.png") no-repeat center center/100%;
	}
	#wx-auth-result.layout .result span:nth-child(1){
		display: block;
		color: #1782e0;
		font-size: 0.48rem;
		text-align: center;
		padding-top: 0.586667rem;
	}
	#wx-auth-result.layout .result span:nth-child(2){
		display: block;
		color: #999;
		font-size: 0.346667rem;
		text-align: center;
	}
	#wx-auth-result.layout .phone{
		text-align: center;
		color: #666;
		font-size: 0.453333rem;
		padding-top: 1.28rem;
	}
    #loan_tips2_layout1{
        width: 100%;
    }
    #loan_tips2_layout1 ._btn {
        position: fixed;
        bottom: 0rem;
        width: 100%;
        height: 1.306667rem;
        line-height: 1.306667rem;
        text-decoration: none;
        color: #fff;
        background-color: #7e8aff;
        text-align: center;
        font-size: 0.48rem; }
</style>
<div class="layout" id="wx-auth-result">
	<div class="img"></div>
	<div class="result">
		<span>恭喜！认证成功</span>
		<span>您的微信已认证成功</span>
	</div>
	<div class="phone">手机号：<?= $phone;?></div>
</div>
<script>
	setTimeout(function () {
		document.getElementById('wx-auth-result').style.display = 'block';
	},10)
</script>