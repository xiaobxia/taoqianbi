<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\LoanFund */

$this->title = '创建资方';
$this->params['breadcrumbs'][] = ['label' => 'Loan Funds', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('/loan-fund/submenus',['route'=>Yii::$app->controller->route]);
?>

<div class="loan-fund-create">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
