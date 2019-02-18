<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\fund\FundAccount;
/* @var $this yii\web\View */
/* @var $model common\models\fund\FundAccount */
/* @var $form ActiveForm */
?>
<div class="form">

    <?php $form = ActiveForm::begin(); ?>

        <?php echo $form->field($model, 'name') ?>
        <?php echo $form->field($model, 'account_type')->dropDownList([null=>'请选择']+FundAccount::ACCOUNT_TYPE) ?>
       	<?php echo $form->field($model, 'status')->dropDownList([null=>'请选择']+FundAccount::STATUS_LIST) ?>

        <div class="form-group">
            <?php echo Html::submitButton('提交', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>

</div><!-- _form -->
