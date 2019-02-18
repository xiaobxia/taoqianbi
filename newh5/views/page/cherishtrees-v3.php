<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #cherishtrees.layout{
        width: 100%;
        height: 17.786667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/xybt/beijing_v3.png") no-repeat center center/cover;
        position: relative;
    }
    #cherishtrees.layout .hongbao{
        width: 3.226667rem;
        height: 0.866667rem;
        position: absolute;
        top: 8.706667rem;
        left: 3.546667rem;
    }
    #cherishtrees.layout .down_load{
        position: absolute;
        top: 14.706667rem;
        left: 2.546667rem;
        width: 4.853333rem;
        height: 1.173333rem;
        line-height: 1.173333rem;
        font-size: 0.506667rem;
        font-weight: bold;
        text-align: center;
        color: rgb(240,33,41);
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
</style>
<body>
	<div class="layout" id="cherishtrees">
        <img src="<?=  $this->absBaseUrl;?>/image/xybt/888yuan.png" alt="888红包" class="hongbao">
		<a href="<?= Url::to(['page/download-xybt-loan-url'])?>" class="down_load">点击下载领红包</a>
	</div>
</body>
</html>