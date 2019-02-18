<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */
$this->shownav('credit', 'menu_credit_user_onoff');

?>
<div class="site-error">


    <div class="alert alert-danger" style="margin-top: 10px; margin-bottom: 10px;">
        <span style="display: inline-block;font-weight: bold;">错误信息：</span><?php echo nl2br(Html::encode($message)) ?>
    </div>
    <div class="alert alert-danger" style="margin-top: 10px; margin-bottom: 10px;">
        点击<?php echo Html::a('返回', ['user-credit-onoff-add'],['style'=>'font-weight: bold;']);?>添加页面。
    </div>
    <p>
        The above error occurred while the Web server was processing your request.
    </p>
    <p>
        Please contact us if you think this is a server error. Thank you.
    </p>

</div>