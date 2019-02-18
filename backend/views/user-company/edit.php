<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use backend\components\widgets\ActiveForm;
use common\models\loan\LoanCollection;
use common\helpers\Url;
use yii\helpers\Html;
?>
<style>
    td.label {
        width: 170px;
        text-align: right;
        font-weight: 700;
    }
    .txt{ width: 100px;}

    .tb2 .txt, .tb2 .txtnobd {
        width: 200px;
        margin-right: 10px;
    }
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'loan-person-edit']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">编辑催收人员信息</th></tr>
    <tr>
        <td class="label">用户组：</td>
        <td ><?php echo $form->field($loan_collection, 'group')->dropDownList(LoanCollection::$group); ?></td>
    </tr>
    <tr>
        <td class="label">机构：</td>
        <td ><?php echo $form->field($loan_collection, 'outside')->dropDownList(LoanCollection::$outside); ?></td>
    </tr>
    <tr>
        <td class="label" id="real_name">姓名：</td>
        <td ><?php echo $form->field($loan_collection, 'real_name')->textInput(); ?><p id = "real_name_msg"></p></td>
    </tr>
    <tr>
        <td class="label" id="phone">手机号：</td>
        <td ><?php echo $form->field($loan_collection, 'phone')->textInput(); ?><p id = "phone_msg"></p></td>
    </tr>

    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
