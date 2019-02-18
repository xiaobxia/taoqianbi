<?php
use yii\helpers\Url;
?>
<style>
    *{margin: 0;padding: 0}
    body,html{width:100%;height: 100%;}
    #free_coupons.layout{
        width: 100%;
        height: 18.066667rem;
        background: url("<?=  $this->absBaseUrl;?>/image/jshb-sms/SMS_1.png") no-repeat center center/cover;
        position: relative;
        text-align: center;
    }
    #free_coupons.layout .down_load{
        position: absolute;
        top: 13.5rem;
        left: 2.2rem;
        width: 5.6rem;
        height: 1.35rem;
        line-height: 1.35rem;
        background-color: transparent;
        color: #459DF3;
        border: 1px solid #459DF3;
        font-size: 0.472222rem;
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    #free_coupons.layout .apply{
        position: absolute;
        top: 11rem;
        left: 2.2rem;
        width: 5.6rem;
        height: 1.4rem;
        line-height: 1.4rem;
        background-color: #459DF3;
        color: #fff;
        font-size: 0.472222rem;
        border-radius: 0.266667rem;
        -webkit-border-radius: 0.266667rem;
    }
    .desc{
        text-align: center;
        color: #666;
        font-size: 0.333333rem;
    }
    .desc span{
        text-align: center;
        color:#459DF3;
        font-size: 0.333333rem;
    }
    .desc_1{
        position: relative;
        top: 12.7rem;
    }
    .desc_2{
        position: relative;
        top: 14.7rem;
    }
</style>
<body>
	<div class="layout" id="free_coupons">
		<a href="jshb://jshb" class="apply">马上借款</a>
        <p class="desc desc_1">已安装<span><?php echo APP_NAMES;?></span>的用户</p>
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
                window.location.href = 'jshb://com.xybt.jshb/openapp';
            }else{
                window.location.href = 'jshb://jshb';
            }
        })
        $('.down_load').on('click',function() {
            downLoad('jshb')
        })        
    })
	 

		
</script>
</html>