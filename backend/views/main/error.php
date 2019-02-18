<?php

use yii\helpers\Html;
use yii\helpers\VarDumper;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
?>
<div class="site-error">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <div class="alert alert-danger" style="margin-top: 10px; margin-bottom: 10px;">
        <?php echo nl2br(Html::encode($message)) ?>
    </div>

    <p>
        <a href="mailto:<?php echo isset(\yii::$app->params['adminEmail']) ? \yii::$app->params['adminEmail'] : NOTICE_MAIL  ?>?subject=wzd_backend_error">联系相关开发</a>
        <p <?php echo YII_ENV_DEV ? '' : "style='display:none'" ?>>
        <?php echo VarDumper::dump($exception, 5, true); ?>
        </p>
    </p>

</div>
