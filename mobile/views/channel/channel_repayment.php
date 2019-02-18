<?php
use yii\helpers\Url;
use mobile\components\CheckPayPwd;

?>
<div class="choose">
    <div class="head">
        <h3><?= $this->title ?></h3>
    </div>
    <ul>
        <li>
            <a href="<?=Url::toRoute(['loan/confirm-code','id'=>$order['id'],'result_url'=>$result_url])?>">
                <h2>银行卡还款<b>官方推荐</b></h2>
                <span><?=$order['bank_info']?></span>
                <i></i>
            </a>
        </li>
        <!--
        <li>
            <h2>银行卡转账</h2>
            <i></i>
          </a>
        </li>
         -->
    </ul>
    <p>备注：若在借款期间内未主动发起还款，则默认于还款日当天从绑定银行卡<?= $order['bank_info'] ?>自动扣除所借款项，请保证在扣款之前帐户资金充足。</p>
</div>
