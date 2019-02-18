<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
?>


<?php $form = ActiveForm::begin(['method' => "post", 'action' => ['global/no-login-phone']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">设置万能密码登录的手机号</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($setting, 'svalue')->textArea(['rows' => '6']); ?></td>
        <td class="vtop tips2"></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" value="2" name="type"/>
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>