<?php
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\helpers\Url;
?>
<?php $form = ActiveForm::begin(['id' => 'black-list-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">添加黑名单</th></tr>
        <tr>
            <td>
                用户ID：<input type="text" name="user_id">
            </td>
        </tr>
        <tr>
            <td>
                备注：<input type="text" name="remark">
            </td>
        </tr>
        <tr>
            <td ><input type="submit" value="提交" name="submit_btn" class="btn"></td>
        </tr>

    </table>
<?php ActiveForm::end(); ?>