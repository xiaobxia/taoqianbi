<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #valentinemsg.layout{
        height: 18.066667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/xybt/valentinebg.png") no-repeat center center/cover;
        position: relative;
    }
    #valentinemsg.layout .down_load{
        position: absolute;
        top: 10.853333rem;
        left: 0.32rem;
        
    }
    #valentinemsg.layout .down_load a{
        display: block;
        width: 9.36rem;
        height: 1.466667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/xybt/btn1.png") no-repeat center center/cover;
    }
    #valentinemsg.layout .apply{
        position: absolute;
        top: 13.413333rem;
        left: 0.32rem;
    }
    #valentinemsg.layout .apply a{
        display: block;
        width: 9.36rem;
        height: 1.466667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/xybt/btn2.png") no-repeat center center/cover;
    }
    #valentinemsg.layout p{
        text-align: center;
        font-size: 0.32rem;
        color: #666666;
        padding-bottom: 0.186667rem;
    }
</style>
<body>
	<div class="layout" id="valentinemsg">
        <div class="down_load">
            <p>未安装<?php echo APP_NAMES;?>APP</p>
        <a href="<?= Url::to(['page/download-xybt-loan'])?>" ></a>
        </div>
        <div class="apply">
            <p>已安装<?php echo APP_NAMES;?>APP</p>
            <a href="xybt://com.xybaitiao/openapp"></a>
        </div>
        
	</div>
</body>
</html>