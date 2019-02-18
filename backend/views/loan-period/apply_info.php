<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\widgets\ActiveForm;
use common\models\LoanTrial;
?>
<table class="tb tb2 fixpadding">
    <?php if(isset($status)&&(LoanRecordPeriod::STATUS_APPLY_TRIAL_TRIAL_APPLY == $status)):?>
        <tr><th class="partition" colspan="15" style="color: red;">借款初审</th></tr>
    <?php else: ?>

    <?php endif;?>

    <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-check']]); ?>
    <tr>
        <td>
            审核状态：
            &nbsp;&nbsp;&nbsp;&nbsp;审核通过：<input type="radio" value="1" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_REVIEW_WILL_APPLY)): ?>checked="true" <?php endif;?>>
            &nbsp;&nbsp;&nbsp;&nbsp;审核不通过：<input type="radio" value="-1" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_TELE_FALSE)): ?>checked="true" <?php endif;?>>
        </td>
    </tr>
    <tr>
        <td>
            审核意见：<textarea cols="40" rows="4" name="message"><?php echo  !empty($loan_audit) ? $loan_audit['contact_remark'] :  "";?></textarea>
        </td>
    </tr>
    <tr>
        <td>
            <input type="hidden" name="source_status" value="<?php echo $status ?>">
            <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_record_period->id?>">
            <input type="submit" name="submit" value="提交" class="btn">
        </td>
    </tr>
    <?php ActiveForm::end(); ?>
</table>



