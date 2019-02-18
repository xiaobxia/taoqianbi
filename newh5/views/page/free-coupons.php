<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #free_coupons.layout{
        width: 100%;
        height: 18.066667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/xybt/background.png") no-repeat center center/cover;
        position: relative;
    }
    #free_coupons.layout .down_load{
        position: absolute;
        top: 12.615rem;
        left: 2.96rem;
        width:4.346667rem;
        height: 1.173333rem;
        background-color: rgba(255,255,255,0);
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    #free_coupons.layout .apply{
        position: absolute;
        top: 14.59rem;
        left: 2.96rem;
        width:4.346667rem;
        height: 1.173333rem;
        background-color: rgba(255,255,255,0);
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
</style>
<!--<script src="<?/*= $this->staticUrl('js',2)*/?>/flexible.js"></script>-->
<body>
	<div class="layout" id="free_coupons">
		<a href="<?= Url::to(['page/download-xybt-loan'])?>" class="down_load"></a>
		<a href="xybt://com.xybaitiao/openapp" class="apply"></a>
	</div>
</body>
<script>
	/*var _a = document.querySelectorAll("a");
	for (var i = 0; i < _a.length; i++) {
		_a[i].onclick = function () {
			this.style.backgroundColor = 'rgba(0,0,0,0.2)'
		}
	}*/
</script>
</html>