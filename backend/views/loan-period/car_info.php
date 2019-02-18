<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\widgets\ActiveForm;
use common\models\LoanTrial;
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15" style="color: red;">放车</th></tr>
    <?php if($action == "edit"):?>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-audit']]); ?>
        <tr>
            <td>
                放车状态：
                &nbsp;&nbsp;&nbsp;&nbsp;放车通过：<input type="radio" value="15" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_MONEY_APPLY)): ?>checked="true" <?php endif;?>>
                &nbsp;&nbsp;&nbsp;&nbsp;放车驳回：<input type="radio" value="14" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_CAR_FALSE)): ?>checked="true" <?php endif;?>>
            </td>
        </tr>
        <tr>
            <td>
                备注：<textarea cols="40" rows="4" name="message"><?php echo  !empty($loan_audit) ? $loan_audit['delivery_remark'] :  "";?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <input type="hidden" name="type" value="car">
                <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_record_period->id?>">
                <input type="submit" name="submit" value="提交" class="btn">
            </td>
        </tr>
        <?php ActiveForm::end(); ?>
    <?php else:?>
        <tr>
            <td>
                放车时间：<?php echo  !empty($loan_audit) ? date("Y-m-d H:i:s", $loan_audit['delivery_time']) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放车意见：<?php echo  !empty($loan_audit) ? $loan_audit['delivery_remark'] :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放车操作人：<?php echo  !empty($loan_audit) ? $loan_audit['delivery_username'] :  "";?>
            </td>
        </tr>
    <?php endif;?>
</table>



