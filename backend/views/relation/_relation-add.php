<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/2/13
 * Time: 15:32
 */
use backend\components\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.js'); ?>"></script>
<style>
    .td24{
        width: 120px;
        font-weight: bold;
    }
</style>
<?php $form = ActiveForm::begin(); ?>
<table class="tb tb2 fixpadding">
    <tr>
        <th colspan="15" class="partition">关系添加</th>
    </tr>
    <tr class="noborder"><td class="td24">关系名称:</td></tr>
    <tr>
        <td><?php echo $form->field($relation, 'name')->textInput()?></td>
    </tr>
    <tr class="noborder"><td class="td24">是否启用:</td></tr>
    <tr>
        <td><?php echo $form->field($relation, 'status')->textInput()?></td>
    </tr>
    <tr class="noborder"><td class="td24">权重:</td></tr>
    <tr>
       <td><?php echo $form->field($relation, 'weight')->textInput()?></td>
    </tr>
    <tr class="noborder"><td class="td24">备注:</td></tr>
    <tr>
       <td><?php echo $form->field($relation, 'message')->textarea()?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>