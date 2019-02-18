<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style>
	.layout{
		padding: 0.4rem 0.32rem;
		color: #333;
	}
	[data-dpr="1"] .layout .title{
		font-size: 16px;
	}
	[data-dpr="2"] .layout .title{
		font-size: 32px;
	}
	[data-dpr="3"] .layout .title{
		font-size: 48px;
	}
	[data-dpr="1"] .layout ul li{
		font-size: 14px;
	}
	[data-dpr="2"] .layout ul li{
		font-size: 28px;
	}
	[data-dpr="3"] .layout ul li{
		font-size: 42px;
	}
	.layout .process{
		padding-top: 0.266667rem;
		padding-bottom: 0.8rem;
	}
	.layout .process ul li{
		list-style: none;
		height: 1.0rem;
		vertical-align: middle;
		position: relative;
		padding-left: 0.96rem;
		padding-bottom: 0.266667rem;
	}
	.layout .process ul li .line{
		display: block;
		width: 0.04rem;
		height: 0.64rem;
		background-color: <?php echo $color;?>;
		position: absolute;
		left: 0.306667rem;
		top: 0.64rem;
	}
	.layout .process ul li .icon{
		display: block;
		position: absolute;
		left: 0;
		width: 0.64rem;
		height: 0.64rem;
		background-color: <?php echo $color;?>;
		color: #fff;
		line-height: 0.64rem;
		text-align: center;
		border-radius: 50%;
		margin-right: 0.32rem;
	}
	.layout .process ul li p{
		padding-top: 0.106667rem;
	}
	.layout .tips{
		padding-top: 0.4rem;
		padding-left: 0.32rem;
	}
	.layout .tips li{
		list-style: disc;
	}
	.layout .tips li .strong{
		font-weight: 600;
	}
	.layout #toWx{
		width: 100%;
		height: 1.133333rem;
		display: block;
		margin-top: 2.0rem;
		line-height: 1.133333rem;
		background-color: <?php echo $color;?>;
		color: #fff;
		text-align: center;
		border-radius: 0.133333rem;
	}
	[data-dpr="1"] .layout #toWx{
		font-size: 15px;
	}
	[data-dpr="2"] .layout #toWx{
		font-size: 30px;
	}
	[data-dpr="3"] .layout #toWx{
		font-size: 45px;
	}
</style>

<body>
	<div class="layout">
		<div class="title">微信认证流程</div>
		<div class="process">
			<ul>
                <li><span class="icon">1</span><i class='line'></i><p>点击“复制去微信认证”，复制微信公众号名称，打开微信。</p></li>
                <li><span class="icon">2</span><i class='line'></i><p>微信内搜索关注“<?php echo WEIXIN_GONGZHONGNHAO;?>”公众号</p></li>
                <li><span class="icon">3</span><p>公众号内，点击-<?php echo WEIXIN_GONGZHONGNHAO;?>-绑定账号，绑定完成即可点亮微信认证。</p></li>
            </ul>
		</div>
		<div class="title">完成<?php echo APP_NAMES;?>微信认证，即可享受：</div>
			<ul class="tips">
				<li><span class="strong">提升额度：</span>完成微信认证，健全个人信息有助于提升借款额度</li>
				<li><span class="strong">查询提醒：</span>公众号内轻松掌控账户动态，借还款信息及时提醒。</li>
				<li><span class="strong">微信福利：</span>抢红包，大转盘，诚意满满的微信福利不定期放送。</li>
			</ul>
		<a onclick="jump()" id="toWx">复制去微信认证</a>
	</div>
    <script>
		setWebViewFlag();
		MobclickAgent.onEvent("weixin","微信认证页面事件"); //页面进入打点
        function jump(){
        	// 点击微信认证打点
			try {
				MobclickAgent.onEventWithLabel("weixin_go","微信认证-去微信");
			} catch (e) {
				console.log(e);
			}
            nativeMethod.returnNativeMethod('{"type":"14"}');
            nativeMethod.copyTextMethod('{"text":"'+'<?php echo '信合宝';?>'+'","tip":"复制微信号成功!"}');
        }
    </script>
</body>
