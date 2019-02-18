<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #cherishtrees.layout{
        width: 100%;
        height: 17.786667rem;
        background: url("<?= $this->absBaseUrl;?>/image/xybt/moonDay.png") no-repeat center center/cover;
        position: relative;
    }
    #cherishtrees.layout .down_load{
        position: absolute;
        top: 12.6rem;
        left: 2.8rem;
        width:4.346667rem;
        height: 1.173333rem;
        background-color: rgba(255,255,255,0);
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    #cherishtrees.layout .apply{
        position: absolute;
        top: 14.8rem;
        left: 2.8rem;
        width:4.346667rem;
        height: 1.173333rem;
        background-color: rgba(255,255,255,0);
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
</style>
<body>
	<div class="layout" id="cherishtrees">
		<a href="<?= Url::to(['page/download-xybt-loan-url'])?>" class="down_load"></a>
		<a href="xybt://com.xybaitiao/openapp" class="apply"></a>
	</div>
</body>
