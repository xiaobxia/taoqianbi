<?php
use yii\helpers\Url;
?>
<div class="phone-verify">
  <ul>
    <li>支付金额：<?=sprintf("%0.2f", $money / 100)?>元</li>
    <li>预留手机：<?=$phone?></li>
    <li>银行卡号：<?=$bank_info?></li>
    <li>
      <input class="verify" id="code" placeholder="请输入短信验证码" />
      <a href="JaveScript:;" id="resend" class="send" style="background-color: gray">还需150秒</a>
    </li>
  </ul>
  <p class="error" id='error'></p>
  <p class="tips"></p>
  <a class="button" href="javascript:;" id="submit">确认</a>
  <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>
<div class="popup" id="loading">
   <div class="overlay">
     <p class="tips-msg">正在提交，请稍后…</p>
   </div>
   <div class="spin" id="preview">
   </div>
</div>
<script>
    $(function () {
        var dpr = lib.flexible.dpr;
        new Spinner({color:'#fff',width:3*dpr,radius:11*dpr,length:8*dpr}).spin(document.getElementById('preview'));
        document.getElementById('loading').style.display='none';

        var caption_count;
        var caption_intval;
        captionCountDown();
        //验证码计时
        function captionCountDown(){
            caption_count = 150;
            caption_intval = window.setInterval(function(){
                if(caption_count > 1){
                    caption_count -= 1;
                    $('#resend').html('还需'+caption_count+'秒');
                }else{
                    window.clearInterval(caption_intval);
                    $('#resend').html('重新发送').css('background-color','#1ec8e1');
                }
            },1000);
        }
        $('#resend').click(function(e){
            if(caption_count > 1){
                return false;
            }
            window.location.reload();
        });
        $('#submit').click(function(){
            var code = $.trim($('#code').val());
            console.log(code);
            if(!code){
                $('#error').html('请输入验证码');
                return false;
            }
            var url = '<?php echo Url::to(['loan/confirm-charge']);?>';
            $.ajax({
                url : url,
                type : 'get',
                data : {"code":code,'card_id':<?=$card_id;?>'phone':<?=$phone;?>,'oid':<?=$loan_order_id?>},
                dataType : 'json',
                success : function(data){
                    if(data.code == 0){
                        $('#loading').show();
                        jumpTo('<?php echo Url::to(["loan/pay-result?id=$loan_order_id"]);?>');
                    }else if(data.message){
                        $('#error').html(data.message);
                    }
                },
                fail:function(){
                }
            });
            return false;
        });
    });


</script>
