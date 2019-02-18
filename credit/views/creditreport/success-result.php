<?php
use yii\helpers\Url;
use frontend\components\ApiUrl;

?>
<link rel="stylesheet" type="text/css" href="<?= Yii::$app->request->getAbsoluteBaseUrl(); ?>/css/general.css?v=2016042201">
 <style type="text/css">
    html,body {
        min-width:320px;
        max-width:480px;
        min-height:100%;
        margin:0 auto;
    }
    body *{
        max-width: 480px;
    }
    ._width_limit *{
        max-width: none;
    }
    html {
      position: relative;
      width: 100%;
      height: 100%;
    }
    ::-webkit-scrollbar{
      opacity: 0;
    }
    .container{
        margin: 0 auto;
        width: 100%;
        height: 100%;
    }
    #pay_result_wraper{
        height: 100%;
        background: #F5F6F8;
    }
    #pay_result_wraper .column{
        background: #FFF;
    }
    #pay_result_wraper .line{
        padding: .3em 0;
    }
    #pay_result_wraper ._border{
        padding-top: 1px;
    }
    #pay_result_wraper .logo_wraper{
        padding-top: 1.5em;
        padding-bottom: .5em;
    }
    #pay_result_wraper .result_desc{
        padding: 1em 2.5em;
    }
    #pay_result_wraper .btn_wraper{
        padding-top: 4em;
    }
    #pay_result_wraper ._btn{
        background: #fd5353;
        height: 2.3em;
        line-height: 2.3em;
        margin: auto 6.25%;
        color: #fff;
        font-size: 1.2em;
    }
    .lh_em_3{
        line-height: 3em;
    }
</style>
<div id="pay_result_wraper">
    <div class="column">
        <p class="logo_wraper a_center"><img src="<?= Yii::$app->request->getAbsoluteBaseUrl(); ?>/image/installment-shop/layouts/<?=$img?>" width="10%"></p>
        <p class="a_center em_1_2 _22ac38" style="font-weight: bold;color: <?php echo $color;?>"><?php echo !empty($describe)? $describe: '恭喜您，申请成功'?></p>
        <div class="result_desc">
            <p class="_999 lh_em_1_5" style="text-align: center"><?php if($content == 'empty'){echo '';}else{echo '工作人员将在1个工作日内与您联系，<br/>请注意接听';}?></p>
        </div>
    </div>
    <p class="line"></p>
    <div class="btn_wraper m_center">
        <div style="background:<?php echo $color;?> none repeat scroll 0 0" class="_btn bg_fd5353 em_1_2 fff a_center _b_radius" id= "back" onclick="jumpTo('<?= !empty($url) ? $url : Url::toRoute(['credit-app/index']);?>')">返回首页</div>
        <div style="display: none;background:<?php echo $color;?> none repeat scroll 0 0" class="_btn bg_fd5353 em_1_2 fff a_center _b_radius" id="next" onclick="jumpToBank()">下一步</div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    // 进入页面打点
    setWebViewFlag();
    MobclickAgent.onEvent('zhima_sucess');
    res = nativeMethod.isFirstCertificationNext();
    if(res == 0){
        $("#back").show();
        $("#next").hide();
    }else{
        $("#back").hide();
        $("#next").show();
    }
});
function jumpTo(url) {
    if (typeof nativeMethod == 'undefined') {
        window.location.href = url;
    }
    else {
        nativeMethod.returnNativeMethod('{"type":"0"}');
    }
}

function jumpToBank() {
    nativeMethod.returnNativeMethod('{"type" : "1004","verify_info":{"real_verify_status":"1","real_bind_bank_card_status":"0"}}');
    nativeMethod.returnNativeMethod('{"type" : "4002"}');
}
</script>