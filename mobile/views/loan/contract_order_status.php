<?php

use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use yii\helpers\Url;
use mobile\components\ApiUrl;
/* @var $this \yii\web\View */
/* @var $order \common\models\UserLoanOrder */
?>
<style>
    body{
        background: #f2f2f2; 
    }
</style>

<div class="wrap repayment-detail">
    <div class="head"><h3>订单状态</h3></div>  
    <div class="content">
        <ul>
            <?php foreach ($list as $value) { ?>
                <li class="<?= isset($value['class']) ? $value['class'] : '' ?>">
                    <h1><?= $value['title'] ?></h1>
                    <p><?= $value['body'] ?></p>
                </li>
            <?php } ?>
        </ul>
    </div>
    

    <div class="button clearfix" style="position: relative;margin-top:40px;">
        <a class="btn-open" href="https://qbcredit.wzdai.com/credit-web/open-app" style="width:90%;margin:0 auto;float:none">打开APP</a>
    </div>
</div>
