<?php 
use yii\helpers\Url;
?>
<div class="extension">
    <style>body{background:#f2f2f2;}</style>
    <div class="reulst" id="success" style="display:none">
        <img alt="" src="<?=\Yii::$app->controller->staticUrl('credit/img/img-1.png')?>"/>
        <p id="success_msg"><?=$msg?></p>
        <a class="common-button" href="<?=Url::toRoute(['loan/loan-detail','id'=>$id])?>">朕知道了</a>
    </div>
    <div class="reulst error" id="failed"  style="display:none">
        <img alt="" src="<?=\Yii::$app->controller->staticUrl('credit/img/img-3.png')?>"/>
        <p id="error_msg">抱歉，您的续期申请失败，请确保支付银行卡金额充足；或者使用支付宝支付续期费用，具体请到我的->设置->帮助中心查看。</p>
        <a class="common-button" href="<?=Url::toRoute(['loan/loan-detail','id'=>$id])?>">朕知道了</a>
    </div>
</div>
<div class="popup" id="loading">
   <div class="overlay">
     <p class="tips-msg">正在提交，请稍后…</p>
   </div> 
   <div class="spin" id="preview">
   </div>     
</div>
<script>
    var times = 0;
    var intval;
    var url = '<?=Url::toRoute(['loan/delay-status','id'=>$id])?>';
    $(function(){
        intval = window.setTimeout(getPayResult,1000);
        $('#loading').show();
    });
    (function(){
        var dpr = lib.flexible.dpr;
        new Spinner({color:'#fff',width:3*dpr,radius:11*dpr,length:8*dpr}).spin(document.getElementById('preview'));
        document.getElementById('loading').style.display='none';
      }());
    function getPayResult(){
        $.post(url,'', function(data){
            if(data.status != data.ing){
                window.clearTimeout(intval);
                showResult(data.status,data.msg);
                return ;
            }
            if(data.max_times && times >= data.max_times){
                window.clearTimeout(intval);
                showResult(-1);
                return ;
            }
            times++;
            intval = window.setTimeout(getPayResult,1000);
        });
    }
    function showResult(status,msg){
        $('#success').hide();
        $('#failed').hide();
        if(1 == status){//成功
            $('#success_msg').html(msg);
            $('#success').show();
        }else if(-3 == status){
            $('#error_msg').html('很抱歉，您的银行卡今日支付次数已达上限，请明日再试或者联系客服!');
            $('#failed').show();
        }else{
            $('#failed').show();
        }
        $('#loading').hide();
    }
</script>