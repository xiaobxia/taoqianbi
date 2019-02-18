<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\fund\LoanFund;
use common\models\fund\LoanFundDayQuota;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\fund\LoanFundDayQuota */
/* @var $form yii\widgets\ActiveForm */
echo $this->render('/loan-fund/submenus',['route'=>Yii::$app->controller->route]);
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<div class="loan-fund-day-quota-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($model, 'fund_id')->dropDownList($fund_options) ?>

    <?php echo $form->field($model, 'quota')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'remaining_quota')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'date')->textInput(['maxlength' => true,'onfocus'=>"WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})"]) ?>

    <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
