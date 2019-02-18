<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Monitor;

/* @var $this yii\web\View */
/* @var $model common\models\MonitorSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="monitor-search clearfix">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php echo $form->field($model, 'id') ?>

    <?php echo $form->field($model, 'type')->dropDownList([null=>'未选择']+Monitor::TYPE_LIST) ?>

    <?php echo $form->field($model, 'name') ?>

    <div class="form-group">
        <?php echo Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
