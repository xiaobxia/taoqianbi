<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\statistics\EncryptKeys */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="encrypt-keys-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($model, 'private_key')->textarea(['rows' => 6]) ?>

    <?php echo $form->field($model, 'public_key')->textarea(['rows' => 6]) ?>

    <?php echo $form->field($model, 'encrypt_type')->textInput(['maxlength' => 45]) ?>

    <?php echo $form->field($model, 'encrypt_bits')->textInput() ?>

    <?php echo $form->field($model, 'create_time')->textInput() ?>

    <?php echo $form->field($model, 'state')->textInput() ?>

    <?php echo $form->field($model, 'status')->textInput() ?>

    <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>