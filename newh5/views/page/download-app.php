<style type="text/css">
#download_app_wraper{width:100%;}
#download_app_wraper .top{padding:1em 0;background:#1782e0;}
#download_app_wraper .top img{width:10%;}
#download_app_wraper ._body{margin-top:1.5em;}
#download_app_wraper ._btn{margin-top:3em;margin-bottom:3em;padding:1em 0;width:83%;}
.container {
    margin: 0 auto;
    width: 100%;
    height: 100%;
    background: #FFFFFF;
}
</style>
<div id="download_app_wraper">
    <div class="top">
        <img class="" width="10%" src="<?php echo $this->absBaseUrl;?>/image/page/top-icon.png"/>
        <i class="">&nbsp;<?php echo APP_NAMES;?></i>
    </div>
    <div class="body">
        <img width="88%" src="<?php echo $this->absBaseUrl;?>/image/page/body.jpg">
    </div>
    <div class="download" onclick="downLoad('xybt');">下载APP</div>
</div>