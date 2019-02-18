<?php 
use yii\helpers\Url;
?>
<style type="text/css">
    .result-wait{
        background: #f5f5f7;
    }
    .result-wait .wait img {
        margin-bottom: 8%;
    }
</style>
<div class="result-wait" id="apply" <?=$msg ? 'style="display:none"':''?>>
  <div class="content wait">
    <img alt="" src="<?=\Yii::$app->controller->staticUrl('credit/img/icon-1-03.png', 1)?>"/>
    <h1>还款申请提交中……</h1>
  </div>
</div>
<div class="result-wait" id="result" <?=$msg ? '':'style="display:none"'?>>
  <div class="content wait">
    <img alt="" src="<?=\Yii::$app->controller->staticUrl('credit/img/icon-1-04.png', 1)?>"/>
    <h1 id="result_msg"><?=$msg?></h1>
    <div id="showOther"></div>
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
    var url = '<?=Url::toRoute(['loan/pay-status','id'=>$id])?>';
    $(function(){
        <?php if(!$msg){ ?>
        intval = window.setTimeout(getPayResult,1000);
        $('#loading').show();
        <?php }?>
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
        if(!msg){
            msg = '';
        }

        var alipay_url = "<?= Url::toRoute(['loan/loan-repayment-aliapy','id'=>$id])?>"
        var detail_url = "<?= Url::toRoute(['loan/loan-detail','id'=>$id])?>"

        $('#apply').hide();

        if(1 == status){//成功
            $('#result_msg').html('恭喜还款成功，点滴信用，弥足珍贵!');
            $("#showOther").html("<a href='" + detail_url + "' target='_blank'>查看详情</a>");
	    }else if(-1 == status){
	        $('#result_msg').html('遗憾还款失败，请尝试其他还款方式吧!'+msg);
            $("#showOther").html("<a href='" + alipay_url + "' target='_blank'>试试支付宝还款</a>");
	    }else if(-2 == status){
            $('#result_msg').html('还款未申请，请重新申请还款!');
            $("#showOther").html("<a href='" + alipay_url + "' target='_blank'>试试支付宝还款</a>");
        }else if(-3 == status){
            $('#result_msg').html('很抱歉，您的银行卡今日支付次数已达上限，请明日再试或者联系客服！');
            $("#showOther").html("<a href='" + alipay_url + "' target='_blank'>试试支付宝还款</a>");
        }else{
            $('#result_msg').html('遗憾还款失败，请尝试其他还款方式吧!'+msg);
            $("#showOther").html("<a href='" + alipay_url + "' target='_blank'>试试支付宝还款</a>");
        }
        $('#result').show();
        $('#loading').hide();
    }
</script>