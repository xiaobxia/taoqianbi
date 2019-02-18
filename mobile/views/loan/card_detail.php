<?php
use common\helpers\StringHelper;
use yii\helpers\Url;
?>
<div class="bank-card">
  <style>
      body{
          background:#eff3f5;
      }
      .disabled-button {
          background: #6a4dfc;
      }
      .common-button{
          background: #6a4dfc;
      }
  </style>
  <ul>
    <li class="clearfix">
      <label>所属银行</label>
      <span><?=$card_info['bank_name']?></span>
    </li>
    <li class="clearfix">
      <label>银行卡号</label>
      <span><?=StringHelper::blurCardNo($card_info['card_no'])?></span>
    </li>
    <li class="clearfix">
      <label>预留手机号</label>
      <span><?=StringHelper::blurPhone($card_info['phone'])?></span>
    </li>
  </ul>
  <p>备注<br/>
  1. 借款通过申请后，我们将会将您的所借款项发放到该张银行卡；<br/>
  2. 若申请重新绑卡，则新卡将被激活为收款银行卡；<br/>
  3. 未完成借款期间不允许更换银行卡；<br/>
  </p>
  <?php
  if($source==='xjk-shandai'):?>
      <a href="<?=Url::toRoute(['shandai/update', 'source_order_id'=>$source_id])?>" class="common-button">返回</a>
  <?php
  else:
    if($can_rebind){ ?>
        <a href="<?=Url::toRoute(['loan/bind-card'])?>" class="common-button">重新绑卡</a>
    <?php }else { ?>
        <a href="#" id="band_card" class="disabled-button common-button">重新绑卡</a>
    <?php }
  endif;
  ?>
</div>

<!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
