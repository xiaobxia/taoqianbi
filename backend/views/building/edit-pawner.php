<?php
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use backend\models\Capital;
use backend\models\Pawner;
$this->showsubmenu('编辑抵押人', array(
    ['抵押人列表', Url::toRoute('building/pawner-list'), \Yii::$app->requestedRoute == 'building/pawner-list' ? 1 : 0],
    ['编辑抵押人', Url::toRoute('building/edit-pawner'), \Yii::$app->requestedRoute == 'building/edit-pawner' ? 1 : 0]
));
?>
<?php $form = ActiveForm::begin(['id' => 'edit-capital-form','method'=>'post']); ?>
<?php //$this->showtips('基本配置（根据不同类别，会出现不同额外配置项）：'); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">基本信息</th></tr>
        <tr class="">
            <td class="td27" width="10%"><?php echo $this->activeLabel($model, 'name'); ?></td>
            <td class="vtop rowform" width="50%"><?php echo $form->field($model, 'name')->textInput(['placeholder'=>'抵押人名字','value'=>Pawner::getnamebyid(\Yii::$app->request->get("id"))]); ?></td>
            <td colspan="15"></td>
        </tr>
        <tr class="">
            <td class="td27" width="10%"><?php echo $this->activeLabel($model, 'capital_id'); ?></td>
            <?php $capital = Capital::find()->select(['id','name'])->where('status = 1')->asArray()->all();
                $id = array_column($capital,'id');
                $name = array_column($capital,'name');
                $select = array_combine($id,$name);
            ?>
            <td class="vtop rowform" width="50%"><?php echo $form->field($model, 'capital_id')->dropDownList($select, ['prompt' => '请选择资方']); ?></td>
            <td colspan="15"></td>
        </tr>
        <input type="hidden" name="Pawner[id]" value="<?php echo \Yii::$app->request->get("id");?>"/>
        <tr class="">
            <td class="td27"><input type="submit" value="提交" name="submit_btn" class="btn"></td>
            <td colspan="15"></td>
        </tr>
        <tr><td colspan="15"></td></tr><tr><td colspan="15"></td></tr>
    </table>
<?php ActiveForm::end(); ?>