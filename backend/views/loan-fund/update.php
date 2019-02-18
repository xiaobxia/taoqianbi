<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\LoanFund */

$this->title = 'Update Loan Fund: ' . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Loan Funds', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="loan-fund-update">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
