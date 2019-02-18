<?php
use common\models\LoanOrderSource;
use common\models\UserLoanOrder;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $order UserLoanOrder */
/* @var $source_order LoanOrderSource */

$this->params['load_weui'] = true;
$this->title = '查看订单状态';
?>
<div class="result-wait" id="result">
    <div class="content wait">
        <img alt="" src="<?=\Yii::$app->controller->staticUrl('credit/img/icon-1-04.png', 1)?>"/>
        <h1 id="result_msg"><?=$msg?></h1>
        <a href="javascript:void(0);" id="return_58_h5">返回</a>
    </div>
</div>
<script>
    $('#return_58_h5').click(function (e) {
        window.location.replace('<?=$result_url?>');
    });
</script>



