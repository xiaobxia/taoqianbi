<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\LoanFund */

$this->title = '�����ʷ��˺����� ' . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Loan Funds', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';

echo $this->render('/fund-account/submenus');
?>
<div class="loan-fund-update">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
