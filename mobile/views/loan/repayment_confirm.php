<?php
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use yii\helpers\Url;
use mobile\components\ApiUrl;

?>
<style>
    body,html,.container{
        background-color: #f5f5f5;
        width: 100%;
        height:100%;
        margin: 0;
        padding: 0;
    }
    .container .wave{
        width: 100%;
        height: 0.453333rem;
    }
    .container .confirm{
        width: 100%;
        height: 7.946667rem;
        background: #fff;
        padding-top: 2.053333rem;
    }
    .container .confirm img{
        display: block;
        margin: 0 auto;
        width: 4.213333rem;
        height: 3.68rem;
    }
    .container .confirm h2{
        font-size: 0.533333rem;
        color: #24c9b4;
        text-align: center;
    }
</style>
<div class="container">
    <div class="container">
        <div class="confirm">
            <img src="<?= $this->source_url();?>/image/loan/confirm/chenggong.png" alt="">
            <h2>恭喜您, 还款申请已提交!</h2>
        </div>
        <img class="wave" src="<?= $this->source_url();?>/image/loan/confirm/top.png" alt="">
    </div>
</div>



