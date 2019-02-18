<?php 
use common\models\UserLoanOrderDelay;
use yii\helpers\Url;
use mobile\components\CheckPayPwd;
?>
<div class="extension">
  <style>body{background:#f2f2f2;}</style>
  <div id="delay">
    <div class="tips">申请还款续期服务需要支付费用，请确认并支付!</div>
    <ul class="common-list">
      <li>
        <label>待还本金</label>
        <span><i id="principal"><?=sprintf("%0.2f",$repayment['remain_principal'] / 100)?></i> 元</span>
      </li>
      <li>
        <label>续期天数</label>
        <span>
        <select id="day">
          <?php //foreach(UserLoanOrderDelay::$delay_days as $idx => $day){ ?>
          <?php foreach(UserLoanOrderDelay::getDalayDays() as $idx => $day){ ?>
          <option value ="<?=$idx?>"><?=$day?></option>
          <?php } ?>
        </select>
        天</span>
      </li>
      <li>
        <label>服务费</label>
        <span><i id="counter_fee"><?=$fees[0]?></i> 元</span>
      </li>
      <li>
        <label>续期费</label>
        <span><i id="service_fee"><?=sprintf("%0.2f",$service_fee / 100)?></i> 元</span>
      </li>
      <li style="display:none;">
        <label>逾期费</label>
        <span><i id="late_fee"><?=sprintf("%0.2f",$repayment['late_fee'] / 100)?></i> 元</span>
      </li>
    </ul>
    <p>总服务费：<i id="total_money" class="total_money"><?=$total_moneys[0]?></i>元 <a href="<?=Url::toRoute(['loan/delay-help'])?>">关于续期></a></p>
    <p>完成支付续期总服务费后，即可成功续期</p>
    <a class="common-button" href="javascript:showPayPwd()">马上支付</a>
  </div>
</div>
<?php 
echo CheckPayPwd::widget([
        'js_callback' => 'success_callback',
        'header' => '<h2>总服务费</h2><h1><span class="total_money">'.$total_moneys[0].'</span>元</h1>',
]);
?>
<script>
    var fees = <?=json_encode($fees) ?>;
    var total_moneys = <?=json_encode($total_moneys) ?>;
    $(function(){
        $('#day').change(function(){
            var idx = $(this).val();
            $('.total_money').html(total_moneys[idx]);
            $('#counter_fee').html(fees[idx]);
        });
    });
    function success_callback(pwd_sign){
        var params = {pay_pwd_sign:pwd_sign};
        params['day'] =  $('#day').val();
        params['late_fee'] =  $('#late_fee').html();
        params['service_fee'] =  $('#service_fee').html();
        params['counter_fee'] =  $('#counter_fee').html();
        params['total_money'] =  $('#total_money').html();
        params['principal'] =  $('#principal').html();
        formPost('<?=Url::toRoute(['loan/delay-apply','id'=>$repayment['order_id'],'type'=>$type])?>',params);
    }
</script>