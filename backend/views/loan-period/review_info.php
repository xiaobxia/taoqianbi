<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\widgets\ActiveForm;
use common\models\LoanReview;
use common\helpers\Url;

?>
<?php if(empty($loan_review)):
    ?>
    抱歉，没有复审记录信息。
<?php else:
    $risk_book = [];
    $loan_agreement =  [];
    $loan_agreement_photo =  [];
    $warranty_card =  [];
    $certificate = [];
    $entrust_deductions =  [];
    $other_information = [];
    $column = LoanReview::$review_column;
    foreach($column as $k => $v){
        if(!empty($loan_review->$k)){
            $$k = json_decode($loan_review->$k, true);
        }
    }
    ?>
    <table class="tb tb2 fixpadding">
        <?php foreach(LoanReview::$column_desc as $k => $v):
            $value = $$k;
            ?>
            <tr><th class="partition" colspan="15"><?php echo LoanReview::$review_column[$k]?></th></tr>
            <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-review']]); ?>
            <?php foreach($v as $m => $n):?>
            <tr>
                <td width="15%"><?php echo $n['title']?>：</td>
                <td>
                    <?php if(empty($value) || !isset($value['data'][$m])):?>
                        等待上传
                    <?php elseif(isset($value['data'][$m]) && !empty($value['data'][$m])):?>
                        <a target="_blank" href="<?php echo Url::toRoute(['loan_record_id' => $loan_review->loan_record_id,'loan-period/view-pic', 'handle' => LoanRecordPeriod::HANDLE_REVIEW,'type' => $k, 'column' => $m])?>">点击查看</a>
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
                    <input type="hidden" name="type" value="review">
                    <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_review->loan_record_id?>">
                    <input type="hidden" name="column" value="<?php echo $k;?>">
                    <input type="hidden" name="handle" value="<?php echo LoanRecordPeriod::HANDLE_REVIEW;?>">
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
        <tr><th class="partition" colspan="15" style="color: red;">复审最终状态</th></tr>
        <?php if($action == "edit"):?>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-audit']]); ?>
        <tr>
            <td colspan="2">
                最终复审状态：
                &nbsp;&nbsp;&nbsp;&nbsp;复审驳回：<input type="radio" value="10" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_REVIEW_FALSE)): ?>checked="true" <?php endif;?>>
                &nbsp;&nbsp;&nbsp;&nbsp;复审补充资料：<input type="radio" value="11" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_REVIEW_APPLYING)): ?>checked="true" <?php endif;?>>
                &nbsp;&nbsp;&nbsp;&nbsp;复审通过<input type="radio" value="13" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_CAR_APPLYING)): ?>checked="true" <?php endif;?>>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                复审意见：<textarea cols="40" rows="4" name="message"><?php echo  !empty($loan_audit) ? $loan_audit['review_remark'] :  "";?></textarea>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="hidden" name="type" value="review">
                <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_review->loan_record_id?>">
                <input type="submit" name="submit" value="提交" class="btn">
            </td>
        </tr>
        <?php ActiveForm::end(); ?>
        <?php else:?>
            <tr>
                <td colspan="2">
                    审核时间：<?php echo  !empty($loan_audit) ? date("Y-m-d H:i:s", $loan_audit['review_time']) :  "";?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    审核意见：<?php echo  !empty($loan_audit) ? $loan_audit['review_remark'] :  "";?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    审核人：<?php echo  !empty($loan_audit) ? $loan_audit['review_username'] :  "";?>
                </td>
            </tr>
        <?php endif;?>
    </table>
<?php endif;?>
