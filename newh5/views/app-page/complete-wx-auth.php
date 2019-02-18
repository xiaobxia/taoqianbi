<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style>
	[data-dpr="1"] .complete-wx-auth .info .username {
	  font-size: 16px; }
	[data-dpr="1"] .complete-wx-auth .info .state {
	  font-size: 13px; }

	[data-dpr="2"] .complete-wx-auth .info .username {
	  font-size: 30px;}
	[data-dpr="2"] .complete-wx-auth .info .state {
	  font-size: 26px;}

	[data-dpr="3"] .complete-wx-auth .info .username {
	  font-size: 48px; }
	[data-dpr="3"] .complete-wx-auth .info .state {
	  font-size: 42px; }

	[data-dpr="1"] .complete-wx-auth .tips .title {
	  font-size: 15px; }

	[data-dpr="2"] .complete-wx-auth .tips .title {
	  font-size: 30px; }

	[data-dpr="3"] .complete-wx-auth .tips .title {
	  font-size: 45px; }

	[data-dpr="1"] .complete-wx-auth .tips ul li {
	  font-size: 14px; }

	[data-dpr="2"] .complete-wx-auth .tips ul li {
	  font-size: 28px; }

	[data-dpr="3"] .complete-wx-auth .tips ul li {
	  font-size: 42px; }

	.complete-wx-auth .photo-wall {
	  width: 100%;
	  height: 3.813333rem;
	  background: url("<?= $this->absBaseUrl;?>/image/page/<?php echo $image;?>") no-repeat 0 0/100% 100%;
	  overflow: hidden; }
	  .complete-wx-auth .photo-wall .photo {
	    width: 100%;
	    height: 2.053333rem;
	    margin: 0.133333rem auto;
	    border: 1px solid transparent;
	    background: url("<?= $this->absBaseUrl;?>/image/page/touxiang.png") no-repeat center center/2.053333rem 2.053333rem; }
	    .complete-wx-auth .photo-wall .photo img {
	      border-radius: 50%;
	      display: block;
	      width: 1.866667rem;
	      height: 1.866667rem;
	      margin: 0.093333rem auto;
	      line-height: 100px; }
	    .complete-wx-auth .photo-wall .photo .info {
	      text-align: center;
	      padding-top: 0.266667rem;
	      color: #fff; }
	      .complete-wx-auth .photo-wall .photo .info .username{
	      	display: block;
	      }
	      .complete-wx-auth .photo-wall .photo .info .state{
	      	display: block;
	      	margin-top: -0.133333rem;
	      }
	.complete-wx-auth .tips {
	  padding: 0.32rem; }
	  .complete-wx-auth .tips .title {
	    color: #333; 
		padding-bottom: 0.133333rem;}
	  .complete-wx-auth .tips ul li {
	    color: #666; }

	/*# sourceMappingURL=style.css.map */

</style>
<div class="complete-wx-auth">
	<div class="photo-wall">
		<div class="photo">
			<img src="<?= $data['headimgurl'];?>" alt="头像">
			<div class="info">
				<span class="username"><?= $data['nickname'];?></span>
				<span class="state">已绑定微信</span>
			</div>
		</div>
	</div>
	<div class="tips">
		<div class="title">现在您可以：</div>
		<ul>
			<li>1、使用微信号登录微信服务号：<?php echo WEIXIN_GONGZHONGNHAO;?></li>
			<li>2、及时了解资金动态，还款提醒。</li>
		</ul>
	</div>
</div>
