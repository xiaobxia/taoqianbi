<?php
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use backend\models\Capital;
$this->showsubmenu('编辑资方', array(
    ['资方列表', Url::toRoute('building/capital-list'), \Yii::$app->requestedRoute == 'building/capital-list' ? 1 : 0],
    ['编辑资方', Url::toRoute('building/edit-capital'), \Yii::$app->requestedRoute == 'building/edit-capital' ? 1 : 0]
));
?>
<?php $form = ActiveForm::begin(['id' => 'edit-capital-form','method'=>'post']); ?>
<?php //$this->showtips('基本配置（根据不同类别，会出现不同额外配置项）：'); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">基本信息</th></tr>
    <tr class="">
        <td class="td27" width="10%"><?php echo $this->activeLabel($model, 'name'); ?></td>
        <td class="vtop rowform" width="50%"><?php echo $form->field($model, 'name')->textInput(['placeholder'=>'资方名字','value'=>Capital::getnamebyid(\Yii::$app->request->get("id"))]); ?></td>
        <td colspan="15"></td>
    </tr>
    <input type="hidden" name="Capital[id]" value="<?php echo \Yii::$app->request->get("id");?>"/>
    <tr class="">
        <td class="td27"><input type="submit" value="提交" name="submit_btn" class="btn"></td>
        <td colspan="15"></td>
    </tr>
    <tr><td colspan="15"></td></tr><tr><td colspan="15"></td></tr>
</table>
<?php ActiveForm::end(); ?>