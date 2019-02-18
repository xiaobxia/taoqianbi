<?php 
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\ContentActivity;

$form = ActiveForm::begin(['id' => 'update-packet-form']); ?>
<table class="tb tb2 fixpadding">
    <tr>
        <td class="td24 label"><label>状态</label></td>
        <td ><?php echo $form->field($model, 'status')->dropDownList(ContentActivity::$status); ?></td>
        <td class="tips">
            《注意》： 需要手动操作一下 启动弹窗列表 》》 立即发送 按钮
        </td>
    </tr>
    <!--  -->
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="ok" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>