<?php
/**
 * Created by phpdesigner.
 * User: user
 * Date: 2016/12/06
 * Time: 18:14
 */
use backend\components\widgets\ActiveForm;
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
<?php $form = ActiveForm::begin(['id' => 'can-loan-time-update']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">重置可再借时间</th></tr>
    <tr>
        <td class="label" >可再借时间</td>
        <td>
            <?php echo Html::dropDownList('loan_date', Yii::$app->getRequest()->post('loan_date', 0), $can_loan_date); ?>
        </td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

