<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\LoanFund */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Loan Funds', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="loan-fund-view">

    <?php echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'day_quota_default',
            'status',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
