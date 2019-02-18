<?php

use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\fund\LoanFundDayPreQuota */
/* @var $form ActiveForm */


use common\models\fund\LoanFund;

echo $this->render('/loan-fund/submenus',['route'=>Yii::$app->controller->route]);
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<div class="loan_fund_day_quota_form">

    <?php $form = ActiveForm::begin(); ?>

  		<?php echo $form->field($model, 'fund_id')->dropDownList([null=>'请选择']+LoanFund::getBigClientFundArray()) ?>
		<?php echo $form->field($model, 'date')->textInput(['maxlength' => true,'onfocus'=>"WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"]) ?>
     	<?php echo $form->field($model, 'incr_amount') ?>
        <?php echo $form->field($model, 'decr_amount') ?>

   <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div><!-- _loan_fund_day_quota_form -->
