<style type="text/css">
#index_wraper{
    background: #d9ecf4;
    min-height: 100%;
}
#index_wraper #noice{
    padding-top: .6em;
    padding-bottom: .6em;
}
.column{
    
}
</style>
<div id="index_wraper">
    <div id="noice" class="padding bg_fff">
        <div class="_999 em__9">
            <img class="f_left v_center" src="<?php echo $this->absBaseUrl;?>/image/m-site/index_001_1.png?v=2016030701" width="4%"/>
            <div class="f_left" id="Marquee" style="width:92%;padding-left:3%;"></div>
            <div class="clear"></div>
        </div>
        <div class="column"></div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
    // 文字滚动
    var marquee_content = '***808成功借款1000元，申请到放款耗时6分钟。';
    initMarquee('Marquee',marquee_content,'1.5em');
});
</script>