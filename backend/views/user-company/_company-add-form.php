<?php
/**
 * User: 李振国
 * Date: 2016/10/27
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
<?php $form = ActiveForm::begin(['id' => 'company-add-form']); ?>
<table class="tb tb2 fixpadding">
    <tr>
        <td class="label">名称：</td>
        <td ><?php echo $form->field($user_company, 'title'); ?></td>
    </tr>
    <tr>
        <td class="label">自营：</td>
        <?php $user_company['system']=0;?>
        <td ><?php echo $form->field($user_company, 'system')->radioList(['1'=>'是','0'=>'否']); ?></td>
    </tr>

    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
