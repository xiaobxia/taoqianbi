<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Monitor;

/* @var $this yii\web\View */
/* @var $model common\models\Monitor */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
    textarea {width:600px;}
    .form-group {margin:10px 0;}
    .clearfix:before,
    .clearfix:after {
        display: table;
        content: " ";
    }
    .clearfix:after {
        clear: both;
    }
    .hint-block {
        margin-left:50px;
    }
    .control-label {
        position: relative;
        vertical-align: top;
    }
    #config_templates pre{
        display: inline-block;
        vertical-align: top;
        padding: 0;
        margin:0;
    }
</style>
<div class="monitor-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($model, 'type')->dropDownList([null=>'请选择']+Monitor::TYPE_LIST) ?>

    <?php echo $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'config')->textarea(['rows' => 12]) ?>

    <div class="form-group clearfix" id="config_templates">
        <label class="control-label">配置模板</label>
        <pre id="config_template">请选择类型</pre>
        <?php foreach(Monitor::TYPE_LIST as $type=>$type_name): ?>
        <pre id="config_template_<?php echo $type?>" <?php if($type!=$model->type){?> style="display:none;" <?php } ?>><?php echo Monitor::getConfigTemplate($type)?></pre>
        <?php endforeach;?>
    </div>

    <?php echo $form->field($model, 'check_interval')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'status')->dropDownList([null=>'请选择']+Monitor::STATUS_LIST) ?>

    <?php if(!$model->isNewRecord):?>

    <?php echo $form->field($model, 'recent_log')->textarea(['rows' => 6]) ?>

    <?php echo $form->field($model, 'last_check_time')->textInput(['maxlength' => true]) ?>

    <?php echo $form->field($model, 'last_notify_time')->textInput(['maxlength' => true]) ?>

    <?php endif; ?>

    <div class="form-group">
        <?php echo Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
$(function(){
    $('#monitor-type').on('change',function(){
        var val = $(this).val();
        $('#config_templates pre').hide();
        if(val) {
            $('#config_template_'+val).show();
        } else {
            $('#config_template').show();
        }
    });
})
</script>