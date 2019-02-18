<?php
use yii\helpers\Url;
?>
<div class="popup" id="defray" style="display:none">
    <div class="overlay"></div>
    <div class="dialog">
        <div class="title">
          <p>请输入交易密码</p>
          <span class="close"></span>
        </div>
        <!--<span class="close"></span>-->
        <?=$header?>
        <p class="clearfix entry">
            <i></i>
            <i></i>
            <i></i>
            <i></i>
            <i></i>
            <i></i>
        </p>
        <p class="error-tips" id="error_tip"></p>
        <input name="" type="number" value="" pattern="\d*"/>
        <?php if(Yii::$app->controller->isFromXjk()){?>
            <a href="javascript:returnNative(2)">忘记密码?</a>
        <?php } ?>
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
  function showPayPwd(){
      $('#defray').show();
      $('#defray i').removeClass('point');
      // $('#defray input').val('').focus();
      $('#error_tip').html('');
  }
  (function(){
    var dpr = lib.flexible.dpr;
    new Spinner({color:'#fff',width:3*dpr,radius:11*dpr,length:8*dpr}).spin(document.getElementById('preview'));
    document.getElementById('loading').style.display='none';
  }());

  $(function(){
    $('#defray .close').click(function(event){
        $('#defray').hide();
    })

    $('#defray .entry').click(function(event){
        $('#defray input').focus();
    })
	$('#defray input').focus(function(){
       var interval = setInterval(function(){
           if(document.activeElement.nodeName == 'INPUT'){
              $('#defray .dialog').css({top:0,marginTop:0});
           }else{
              $('#defray .dialog').attr('style','')
              if (interval) {
                clearInterval(interval);
                interval = null;
              }
           }
         },500);
     });

    $('#defray input').bind('input',function(event){
      var val = $(this).val();
       $('#defray i').css("border","1px solid #e6e6e6");
      $('#defray i').removeClass('point');
      for(var i = 0; i < val.length; i++){
          $('#defray i').eq(i).addClass('point');
          if((i+1) <= 5){
            $('#defray i').eq(i+1).css("border","1px solid #6a4dfc").siblings().css("border","1px solid #e6e6e6");
          }else{
              $('#defray i').css("border","1px solid #e6e6e6");
          }
      }
      if (val.length >= 6){
        $('#loading').show();
        $(this).val(val.slice(0,6));
        var url = '<?=Url::toRoute(['user/check-pay-pwd-post'])?>';
        $.post(url,{password:$(this).val()}, function(data){
            if(data && data.code == 0){
                <?php if($success_url){ ?>
                jumpTo('<?=$success_url?>&pay_pwd_sign='+data.sign);
                <?php }else if($js_callback){ ?>
        <?=$js_callback?>(data.sign);
                <?php }?>
            }else if(data && data.message){
                $('#error_tip').html(data.message);
                $('#defray i').removeClass('point');
                $("#defray input").val("");
            }
            $('#loading').hide();
        });
      }
    })
  })
</script>
