<?php
use yii\helpers\Url;
use mobile\components\CheckPayPwd;
?>
<style type="text/css">
body{
    background: #f5f5f7;
}
.lh_em_2{
    line-height: 2em;
}
.choose ul{
    background: #fff;
}
.choose ul li:first-child{
    border-top: 1px solid #dad9de;
}
.choose .head {
    height: 1.2rem;
    line-height: 1.2rem;
    padding-left: 0.4rem;
}
.popup .dialog a {
    font-size: 0.3466666667rem;
    color: #6a4dfc;
    text-decoration: none;
    display: block;
    width: 100%;
}
</style>
<div class="choose">
    <div class="head">
        <h3><?=$this->title?></h3>
    </div>
    <ul>
        <?php if($useFy){ ?>
            <li>
                <?php if($selectBank){ ?>
                <a href="javascript:showBanks('<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>')">
                    <?php }else{ ?>
                    <a href="<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>">
                        <?php } ?>
                        <h2>银行卡还款<b>官方推荐</b></h2>
                        <span><?=$order['bank_info']?></span>
                        <i></i>
                    </a>
            </li>
        <?php }else{ ?>
            <li>
                <a href="javascript:<?=($selectBank ? 'showBanks()':'showPayPwd()');?>">
                    <h2>银行卡还款<b>官方推荐</b></h2>
                    <span><?=$order['bank_info']?></span>
                    <i></i>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="<?=Url::toRoute(['loan/loan-repayment-aliapy','id'=>$order['id']])?>">
                <h2>支付宝还款</h2>
                <i></i>
            </a>
        </li>
        <!--
    <li>
      <a href="<?=Url::toRoute(['loan/loan-repayment-quick','id'=>$order['id']])?>">
        <h2>银行卡转账</h2>
        <i></i>
      </a>
    </li>
     -->
    </ul>
    <p class="lh_em_2">备注：若在借款期间内未主动发起还款，则默认于还款日当天从绑定银行卡<?=$order['bank_info']?>自动扣除所借款项，请保证在扣款之前帐户资金充足。</p>
</div>
<?php
if($selectBank){
    echo CheckPayPwd::widget([
        'js_callback' => 'success_callback',
        'header' => '<h2>还款总额</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'元</h1>',
    ]);
}else{
    echo CheckPayPwd::widget([
        'success_url' => Url::toRoute(['loan/pay-apply','id'=>$order['id']]),
        'header' => '<h2>还款总额</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'元</h1>',
    ]);
}
?>
<?php  if($selectBank){ ?>
    <div class="popup-select" style="display: none;">
        <div class="overlay"></div>
        <div class="content">
            <div class="close-div"><span class="close"></span></div>
            <h3>选择银行卡</h3>
            <div class="select-content">
                <?php $bad_cards = []; ?>
                <?php foreach($myCards as $card){ ?>
                    <?php if(isset($card['bank_maintaining'])){ ?>
                        <?php $bad_cards[] = $card; ?>
                    <?php } else { ?>
                        <h4 class="bank" onclick="selectBank(<?=$card['card_id']?>)"><?=$card['bank_name'].'('.$card['card_no_end'].')'?></h4>
                    <?php }?>
                <?php }?>
                <a href="<?=Url::toRoute(['loan/bind-card','source_id'=>$order['id'],'source'=>'add-card'])?>" class="add-bank"><span class="add"></span>添加银行卡</a>
                <?php if(count($bad_cards) > 0){ ?>
                    <?php foreach($bad_cards as $card){ ?>
                        <h4 class="bank single-out"><span><?=$card['bank_name'].'('.$card['card_no_end'].')'?><br><small><?=$card['bank_maintaining_info']?></small></span></h4>
                    <?php }?>
                <?php }?>
            </div>
        </div>
    </div>
    </div>
    <script>
        <?php if($type==1):?>
        showBanks();
        <?php endif;?>
        var repayment_card_id = 0;
        var repayment_url = '';
        function showBanks(url){
            if(url){
                repayment_url = url;
            }
            $(".popup-select").show();
            $(".content").removeClass("popup-out");
            $(".content").addClass("popup-in");
        }
        function selectBank(card_id){
            repayment_card_id = card_id;
            if(repayment_url){
                var params = {card_id:repayment_card_id,id:<?=$order['id']?>};
                formPost(repayment_url,params,'get');
            }else{
                showPayPwd(card_id);
            }
        }
        function success_callback(pwd_sign){
            var params = {pay_pwd_sign:pwd_sign,card_id:repayment_card_id,id:<?=$order['id']?>};
            formPost('<?=Url::toRoute(['loan/pay-apply','id'=>$repayment['order_id']])?>',params,'get');
        }
        $(function() {
            $(".close, .overlay").click(function(){
                $(".popup-select").hide();
                $(".content").addClass("popup-out");
                $(".content").removeClass("popup-in");
            });

            $(".bank").click(function(){
                $(".popup-select").hide();
                $(".p-bank").text($(this).html());
                $(".p-bank").removeClass("placeholder");
                $(".p-bank").addClass("normal");
            });
        });
    </script>
<?php } ?>

