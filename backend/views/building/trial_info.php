<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\widgets\ActiveForm;
use common\models\LoanTrial;
use common\helpers\Url;
?>
<?php if(empty($loan_trial)):?>
    抱歉，没有初审记录信息。
<?php else:
    $id_card = [];
    $bank_card =  [];
    $income_proof =  [];
    $all_person_photo =  [];
    $hands_id_card = [];
    $finger_application_form =  [];
    $signing_photos =  [];
    $other_information = [];
    $column = LoanTrial::$trial_column;
    foreach($column as $k => $v){
        if(!empty($loan_trial->$k)){
            $$k = json_decode($loan_trial->$k, true);
        }
    }
?>
    <table class="tb tb2 fixpadding">
    <?php foreach(LoanTrial::$column_desc as $k => $v):
        $value = $$k;
        ?>
        <tr><th class="partition" colspan="15"><?php echo LoanTrial::$trial_column[$k]?></th></tr>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-review']]); ?>
            <?php foreach($v as $m => $n):?>
            <tr>
                <td width="15%">
                    <?php echo $n['title']?>：
                </td>
                <td>
                    <?php if(empty($value) || !isset($value['data'][$m])):?>
                        等待上传
                    <?php elseif(isset($value['data'][$m]) && !empty($value['data'][$m])):?>
                        <a target="_blank" href="<?php echo Url::toRoute(['loan_record_id' => $loan_trial->loan_record_id,'loan-period/view-pic', 'handle' => LoanRecordPeriod::HANDLE_TRIAL,'type' => $k, 'column' => $m])?>">点击查看</a>
                    <?php elseif(isset($value['data'][$m]) && empty($value['data'][$m])):?>
                        上次删除，等待继续上传
                    <?php endif;?>
                </td>
            </tr>
            <?php endforeach;?>
        <?php if($action == "edit"):?>
            <tr>
                <td colspan="2">
                审核状态：
                    &nbsp;&nbsp;&nbsp;&nbsp;待提交<input type="radio" value="0" name="status" <?php if(empty($value)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;待审核<input type="radio" value="1" name="status" <?php if(!empty($value) && isset($value['status']) && ($value['status'] == LoanRecordPeriod::ATTACHMENT_STATUS_AUDIT)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;审核不通过<input type="radio" value="2" name="status" <?php if(!empty($value) && isset($value['status']) && ($value['status'] == LoanRecordPeriod::ATTACHMENT_STATUS_AUDIT_FALSE)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;待修改<input type="radio" value="3" name="status" <?php if(!empty($value) && isset($value['status']) && ($value['status'] == LoanRecordPeriod::ATTACHMENT_STATUS_AUDIT_AUDIT_ING)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;审核通过<input type="radio" value="4" name="status" <?php if(!empty($value) && isset($value['status']) && ($value['status'] == LoanRecordPeriod::ATTACHMENT_STATUS_AUDIT_SUCCESS)): ?>checked="true" <?php endif;?>>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    审核意见：<textarea cols="40" rows="4" name="message"><?php echo  !empty($value) ? $value['message'] :  "";?></textarea>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="hidden" name="type" value="trial">
                    <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_trial->loan_record_id?>">
                    <input type="hidden" name="column" value="<?php echo $k;?>">
                    <input type="hidden" name="handle" value="<?php echo LoanRecordPeriod::HANDLE_TRIAL;?>">
                    <input type="submit" name="submit" value="提交" class="btn">
                </td>
            </tr>
        <?php else: ?>
        <tr>
            <td colspan="2">
                审核意见：<?php echo  !empty($value) ? $value['message'] :  "";?>
            </td>
        </tr>
        <?php endif;?>
        <?php ActiveForm::end(); ?>
    <?php endforeach;?>
        <tr><th class="partition" colspan="15" style="color: red;">初审最终状态</th></tr>
        <?php if($action == "edit"):?>
            <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-audit']]); ?>
            <tr>
                <td colspan="2">
                    最终初审状态：
                    &nbsp;&nbsp;&nbsp;&nbsp;初审驳回：<input type="radio" value="3" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_TRIAL_FALSE)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;初审补充资料：<input type="radio" value="4" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_TRIAL_APPLING)): ?>checked="true" <?php endif;?>>
                    &nbsp;&nbsp;&nbsp;&nbsp;初审通过<input type="radio" value="6" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_TELE_APPLY)): ?>checked="true" <?php endif;?>>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    初审意见：<textarea cols="40" rows="4" name="message"><?php echo  !empty($loan_audit) ? $loan_audit['trial_remark'] :  "";?></textarea>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="hidden" name="type" value="trial">
                    <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_trial->loan_record_id?>">
                    <input type="submit" name="submit" value="提交" class="btn">
                </td>
            </tr>
            <?php ActiveForm::end(); ?>
        <?php else:?>
            <tr>
                <td colspan="2">
                    审核时间：<?php echo  !empty($loan_audit) ? date("Y-m-d H:i:s", $loan_audit['trial_time']) :  "";?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    审核意见：<?php echo  !empty($loan_audit) ? $loan_audit['trial_remark'] :  "";?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    审核人：<?php echo  !empty($loan_audit) ? $loan_audit['trial_username'] :  "";?>
                </td>
            </tr>
        <?php endif;?>
    </table>
<?php endif;?>