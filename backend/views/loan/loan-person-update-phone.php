<?php
/**
 * Created by phpdesigner.
 * User: user
 * Date: 2016/10/14
 * Time: 11:00
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
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
<?php $form = ActiveForm::begin(['id' => 'loan-person-update-phone']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">新旧号码更改</th></tr>
    <tr>
        <td class="label" id="loanperson_id">ID</td>
        <td ><?php echo $form->field($loan_person, 'id')->textInput(['readonly'=>'true']); ?></td>
    </tr>
    <tr>
        <td class="label" id="phone_old">旧号码</td>
        <td ><?php echo $form->field($loan_person, 'phone')->textInput(['readonly'=>'true']); ?></td>
    </tr>
    <tr>
        <td class="label" id="phone_new">新号码</td>
        <td ><input type="text" name="phone_new"/></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

