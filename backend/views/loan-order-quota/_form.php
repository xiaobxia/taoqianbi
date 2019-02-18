<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\fund\LoanFund;
use common\models\fund\FundAccount;

/* @var $this yii\web\View */
/* @var $model common\models\fund\LoanFund */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="loan-fund-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($model, 'norm_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'gjj_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'other_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'old_user_orders')->textInput(['maxlength' => true]) ?>


    <div class="form-group">
        <?php echo Html::submitButton( '更新', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
