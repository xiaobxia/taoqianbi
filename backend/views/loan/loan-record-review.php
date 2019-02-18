<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/14
 * Time: 11:01
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanRecord;

?>
<style>
    td.title {
        width: 100px;
        text-align: right;
        font-weight: 700;
    }
</style>
<?php $form = ActiveForm::begin(['id' => 'loan-record-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款管理</th></tr>
    <tr>
        <td class="title"><?php echo $this->activeLabel($loan_record, 'status'); ?></td>
        <td ><?php echo $form->field($loan_record, 'status')->dropDownList(LoanRecord::$status_list); ?></td>
    </tr>
    <tr>
        <td class="title">用户名：</td>
        <td ><?php echo $loan_record['user']['username']; ?></td>
    </tr>
    <tr>
        <td class="title">借款人：</td>
        <td ><?php echo $loan_record['user']['realname']; ?></td>
    </tr>
    <tr>
        <td class="title">联系方式：</td>
        <td ><?php echo $loan_record['user']['phone']; ?></td>
    </tr>
    <tr>
        <td class="title"><?php echo $this->activeLabel($loan_record, 'remark'); ?></td>
        <td ><?php echo $form->field($loan_record, 'remark')->textarea(); ?></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
