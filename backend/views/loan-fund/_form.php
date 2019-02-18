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

    <?php echo $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'company_name')->textInput(['maxlength' => true]) ?>

    <?php if($model->isNewRecord ||  $model->type==LoanFund::TYPE_BIG_CLIENT): ?>
    <?php echo $form->field($model, 'id_number')->textInput(['maxlength' => true]) ?>
    <?php endif;?>
    <?php echo $form->field($model, 'day_quota_default')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'score')->textInput(['maxlength' => true]) ?>


    <?php echo $form->field($model, 'pre_sign_type')->dropDownList([null=>'请选择']+LoanFund::PRE_SIGN_LIST) ?>

    <?php echo $form->field($model, 'interest_rate')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'loan_term_add_day')->textInput(['maxlength' => true]) ?>

    <?php if($model->isNewRecord || !method_exists($model->getService(), 'getDeposit')): ?>
        <?php echo $form->field($model, 'deposit_rate')->textInput(['maxlength' => true]) ?>
    <?php else:?>
        <div class="form-group"><label class="control-label"><?php echo $model->getAttributeLabel('deposit_rate')?></label><span> 由于已经在代码中配置该资方保证金费用，后台配置保证金失效</span><div>
    <?php endif;?>

    <?php if($model->isNewRecord || !method_exists($model->getService(), 'getFundServiceFee')): ?>
        <?php echo $form->field($model, 'fund_service_fee_rate')->textInput(['maxlength' => true]) ?>
    <?php else:?>
        <div class="form-group"><label class="control-label"><?php echo $model->getAttributeLabel('fund_service_fee_rate')?></label><span> 由于已经在代码中配置该资方服务费用，后台配置资方服务费失效</span><div>
    <?php endif;?>

    <?php echo $form->field($model, 'pay_account_id')->dropDownList([null=>'请选择']+FundAccount::getSelectOptions(FundAccount::TYPE_PAY)) ?>

    <?php echo $form->field($model, 'repay_account_id')->dropDownList([null=>'请选择']+FundAccount::getSelectOptions(FundAccount::TYPE_REPAY)) ?>

    <?php echo $form->field($model, 'status')->dropDownList(LoanFund::STATUS_LIST) ?>

    <?php if($model->isNewRecord): ?>

        <?php echo $form->field($model, 'type')->dropDownList([null=>'请选择']+LoanFund::TYPE_LIST) ?>

        <?php echo $form->field($model, 'quota_type')->dropDownList([null=>'请选择']+LoanFund::QUOTA_TYPE_LIST) ?>
    <?php elseif($model->quota_type== LoanFund::QUOTA_TYPE_TOTAL):?>

        <?php echo $form->field($model, 'can_use_quota')->textInput(['maxlength' => true]) ?>

    <?php endif; ?>

    <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
