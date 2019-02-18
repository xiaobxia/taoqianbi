<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\LoanFund */

$this->title = '创建资方账户';
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('/fund-account/submenus');

?>

<div class="loan-fund-create">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
