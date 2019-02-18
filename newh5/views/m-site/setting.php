<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style type="text/css">
#setting_wraper{background:#f5f5f7;min-height:100%;}
#setting_wraper .column{border-top:1px solid #e4e4e7;border-bottom:1px solid #e4e4e7;overflow:hidden;}
#setting_wraper ._spacing{padding-top:6.25%;}
#setting_wraper ._border{width:200%;border-top:1px solid #e4e4e7;}
#setting_wraper .column > div.icon{background:url('<?php echo $this->absBaseUrl;?>/image/m-site/icon_right_1.png') no-repeat center right;background-size:2.5%;}
</style>
<div id="setting_wraper">
    <div class="_spacing"></div>
    <div class="column padding bg_fff">
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_1.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;关于我们</span>
            <a class="indie" href="<?php echo Url::toRoute(['app-page/about-company'],true)?>"></a>
        </div>
    </div>
    <div class="_hidden column padding bg_fff">
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_1.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;关于我们</span>
            <a class="indie" href="<?php echo Url::toRoute(['app-page/about-company'],true)?>"></a>
        </div>
        <div class="_border"></div>
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_2.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;意见反馈</span>
            <a class="indie" href="###"></a>
        </div>
        <div class="_border"></div>
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_3.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;在线客服</span>
            <a class="indie" href="###"></a>
        </div>
        <div class="_border"></div>
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_4.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;催收投诉</span>
            <a class="indie" href="###"></a>
        </div>
    </div>
    <div class="_spacing"></div>
    <div class="column padding bg_fff">
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_5.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;修改登录密码</span>
            <a class="indie" href="<?php echo Url::toRoute(['m-site/change-pwd','type'=>0],true)?>"></a>
        </div>
        <div class="_border"></div>
        <div class="p_relative icon lh_em_3_5">
            <img class="v_center" width="6%" src="<?php echo $this->absBaseUrl;?>/image/m-site/setting_6.png"><span class="em_1 v_center _666">&nbsp;&nbsp;&nbsp;修改交易密码</span>
            <a class="indie" href="<?php echo Url::toRoute(['m-site/change-pwd','type'=>1],true)?>"></a>
        </div>
    </div>
    <div class="_spacing"></div>
    <div class="p_relative column bg_fff lh_em_3 a_center em_1 _666">退出登录<a class="indie" href="javascript:loginOut();"></a></div>
</div>
<script type="text/javascript">
    function loginOut(){
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-user/logout'],true)?>";
        $.post(url, function(data){
            if(data && data.code == 0){
                jumpTo("<?php echo Url::toRoute(['m-site/index'],true)?>");
            }else{
                showExDialog(data.message || '操作失败，请稍后重试！','确定');
            }
        },'json');
    }
</script>