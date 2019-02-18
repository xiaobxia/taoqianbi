<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\fund\LoanFund;
use common\models\fund\LoanFundDayQuota;

/* @var $this yii\web\View */
/* @var $model common\models\fund\LoanFundDayQuota */
/* @var $form yii\widgets\ActiveForm */
echo $this->render('/loan-order-quota/submenus',['route'=>Yii::$app->controller->route]);
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<div class="loan-fund-day-quota-form">

    <?php $form = ActiveForm::begin(); ?>
    <?php echo $form->field($model, 'date')->textInput([
        'maxlength' => true,
        'onfocus'=>$model->isNewRecord ? "WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})" : '',
        'readonly' => $model->isNewRecord ? false : true
    ]) ?>
    <?php echo $form->field($model, 'norm_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'gjj_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'other_orders')->textInput(['maxlength' => true]) ?>
    <?php echo $form->field($model, 'old_user_orders')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
