<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #free_coupons.layout{
        width: 100%;
        height: 18.066667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/jshb-sms/SMS_2.png?V=20181119") no-repeat center center/cover;
        position: relative;
        text-align: center;
    }
    #free_coupons.layout .down_load{
        position: absolute;
        top: 10.8rem;
        left: .2rem;
        width: 9.6rem;
        height: 1.35rem;
        line-height: 1.4rem;
        background-color: #ff8400;
        color: #fff;
        font-size: 0.472222rem;
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    #free_coupons.layout .apply{
        position: absolute;
        top: 10.8rem;
        left: 0.2rem;
        width: 9.6rem;
        height: 1.4rem;
        line-height: 1.4rem;
        font-size: 0.472222rem;
        background-color: #D9694E;
        color: #fff;
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    .desc{
        text-align: center;
        color: #fff;
        font-size: 0.333333rem;
    }
    .desc span{
        text-align: center;
        color:#ff8400;
        font-size: 0.333333rem;
    }
    .desc_1{
        position: relative;
        top: 12.5rem;
    }
    .desc_2{
        position: relative;
        top: 12.5rem;
    }
</style>
<body>
	<div class="layout" id="free_coupons">
		<a href="javascript:;" style="display:none" class="apply">马上还款</a>
        <p class="desc desc_1" style="display:none">已安装<span><?php echo APP_NAMES;?></span>的用户</p>
        <a href="javascript:;" class="down_load">立即下载</a>
        <p class="desc desc_2">未安装<span><?php echo APP_NAMES;?></span>的用户</p>
</body>
<script>
	$(function(){
        if (window.browser.wx) {
            return wxDownload();
        }
        $('.apply').on('click',function(){
            if(window.browser.android){
                window.location.href = 'sdhb://com.wq.shandianhebao/openapp';
            }else{
                window.location.href = 'sdhb://sdhb';
            }
        })
        $('.down_load').on('click',function() {
            downLoad('jshb')
        })        
    })
</script>
</html>