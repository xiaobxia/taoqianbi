<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style>
	body,html{
		width: 100%;
		height: 100%;
	}
		#loan-state{
			background-color: #f5f5f5;
			width: 100%;
			height: 100%;
			text-align: center;
			display: none;
		}
		#loan-state .top{
			background-color: #fff;
		}
		#loan-state .top .img{
			margin: 0.933333rem auto 0.6rem;
			width: 2.666667rem;
			height: 2.666667rem;
			border-radius: 50%;
		}
		#loan-state .top .img img{
			display: block;
			width: 100%;
			height: 100%;
		}
		#loan-state .top .state{
			font-size: 0.506667rem;
			color:#333;
			font-weight: 600;
			padding-bottom: 0.133333rem;
		}
		#loan-state .top .tip{
			color: #999;
			font-size: 0.4rem;
			padding-bottom: 0.533333rem;
		}
		#loan-state a{
			color: #FFF;
			font-size: 0.48rem;
			text-decoration: none;
			display: block;
			height: 1.386667rem;
			line-height: 1.386667rem;
			width: 90%;
			margin: 1.866667rem auto;
			background-color: #6a4dfc;
			border-radius: 0.133333rem;
		}
</style>
<div id="loan-state">
	<div class="top">
		<div class="img">
			<img src="<?= $this->absBaseUrl;?>/image/icon-wait.png" alt="">
		</div>
		<p class="state">还款处理中</p>
		<p class="tip">请及时关注还款动态</p>
	</div>
	<a href="javascript:gobacknativepage();">确定</a>
</div>
<script>
    getPayStatus();
    //定时查询支付状态
    var timer;
    function getPayStatus(){
        document.getElementById('loan-state').style.display = 'block';
        request();
        timer = setInterval(request, 8000);
    }
    //关闭H5页面返回原生页面
    function gobacknativepage() {
        return nativeMethod.returnNativeMethod('{"type":"0","is_help":"1"}')
    }
    //ajax请求
    function request() {
        $.ajax({
            url: "<?php echo Url::toRoute('loan/get-call-back-res')?>?id=<?php echo $id?>",
            type: 'json',
            dataType: 'json',
            success: function(res) {
                //不限时，只要有结果返回，就去渲染并且终止定时器
                if (res.status == 1) { //成功
                    document.querySelector('img').src = "<?php echo $this->absBaseUrl;?>/image/icon-succ.png";
                    $('.state').text("还款成功");
                    $('.tip').text('');
                    clearInterval(timer);
                } else if (res.status == -1) {
                    document.querySelector('img').src = "<?php echo $this->absBaseUrl;?>/image/icon-fail.png";
                    $('.state').text("还款失败");
                    document.querySelector('.tip').innerHTML = "若已付款,请稍后尝试刷新还款订单页,<br>查看最新还款状态或联系客服。"
                    clearInterval(timer);
                }
            }
        })
    }
</script>
