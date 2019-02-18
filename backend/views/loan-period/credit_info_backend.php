<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use backend\components\widgets\ActiveForm;
use common\models\LoanTrial;
use yii\helpers\Html;
?>
<!-- 富文本编辑器 注：360浏览器无法显示编辑器时，尝试切换模式（如兼容模式）-->
<script type="text/javascript" xmlns="http://www.w3.org/1999/html">
    var UEDITOR_HOME_URL = '<?php echo Url::toStatic('/js/ueditor/'); ?>'; //一定要用这句话，否则你需要去ueditor.config.js修改路径的配置信息
</script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.config.js'); ?>?v=2015060801"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.all.js'); ?>?v=2015060801"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript">
    var ue = UE.getEditor('project-desc');
    var uepc = UE.getEditor('projectproperty-pc_desc');
</script>
<style>.credit{ display: none;}</style>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15" style="color: red;">后台放款</th></tr>
    <?php if($action == "edit"):?>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-credit-backend']]); ?>
        <tr>
            <td>
                放款状态：
                &nbsp;&nbsp;&nbsp;&nbsp;放款通过：<input type="radio"  onclick ="credit()" value="17" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_REPAYING)): ?>checked="true" <?php endif;?>>
                &nbsp;&nbsp;&nbsp;&nbsp;放款驳回：<input type="radio"  onclick ="cancle()"  value="16" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_MONEY_FALSE)): ?>checked="true" <?php endif;?>>
            </td>
        </tr>
        <tr class="credit">
            <td>
                放款方式：<?php echo LoanRecordPeriod::$repay_type[$loan_record_period->repay_type]; ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                服务费率：<?php echo $loan_record_period->service_apr ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                服务费：<?php echo sprintf("%.2f", $loan_record_period->fee_amount/100); ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                加急费：<?php echo sprintf("%.2f", $loan_record_period->urgent_amount/100); ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                借款利率：<?php echo $loan_record_period->apr."%" ?>
            </td>
        </tr>
        <tr class="credit" id="repay_operation">
            <td>
                利息收取方式：
                &nbsp;&nbsp;&nbsp;&nbsp;前置：<input type="radio" name="repay_operation" value="1" >
                &nbsp;&nbsp;&nbsp;&nbsp;后置：<input type="radio" name="repay_operation" value="2">
            </td>
        </tr>
        <tr class="credit">
            <td>
                放款时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="credit_repayment_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:true});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                签约时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="sign_repayment_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                首次还款时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="repayment_start_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                放款金额：<input type="text" name="repayment_amount" value="<?php echo $loan_record_period->amount / 100 ?>">
            </td>
        </tr>
        <tr class="credit">
            <td>
                借款期限(月)：<input type="text" name="period" value="<?php echo $loan_record_period->period?>">
            </td>
        </tr>
        <tr>
            <td>
                备   注：<textarea cols="40" rows="4" name="message"><?php echo  !empty($loan_audit) ? $loan_audit['credit_remark'] :  "";?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <input type="hidden" name="type" value="credit_backend">
                <input type="hidden" name="loan_record_period_id" value="<?php echo $loan_record_period->id?>">
                <input type="hidden" name="result" id="result" value="<?php echo $result?>">
                <input type="submit" name="submit" value="提交" class="btn">
            </td>
        </tr>
        <?php ActiveForm::end(); ?>
    <?php else:?>
        <tr>
            <td>
                总还款状态：<?php echo  !empty($loan_repayment) ? \common\models\LoanRepayment::$status[$loan_repayment['status']] :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                每期还款金额：<?php echo  !empty($loan_repayment) ? sprintf("%.2f", $loan_repayment['period_repayment_amount']/ 100) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放款金额：<?php echo  !empty($loan_repayment) ? sprintf("%.2f", $loan_repayment['repayment_amount']/ 100) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                已还款金额：<?php echo  !empty($loan_repayment) ? sprintf("%.2f", $loan_repayment['repaymented_amount'] / 100) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                下一还款ID：<?php echo  !empty($loan_repayment) ? $loan_repayment['next_period_repayment_id']  :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放款时间：<?php echo  !empty($loan_repayment) ? date("Y-m-d", $loan_repayment['credit_repayment_time']) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                签约时间：<?php echo  !empty($loan_repayment) ? date("Y-m-d", $loan_repayment['sign_repayment_time']) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                最终还款时间：<?php echo  !empty($loan_repayment) ? date("Y-m-d", $loan_repayment['repayment_time']) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放款期限：<?php echo  !empty($loan_repayment) ? $loan_repayment['period'] :  "";?>
            </td>
        </tr>
        <tr><th class="partition" colspan="15" style="color: red;">放款审核表：</th></tr>
        <tr>
            <td>
                放款时间：<?php echo  !empty($loan_audit) ? date("Y-m-d H:i:s", $loan_audit['credit_time']) :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放款意见：<?php echo  !empty($loan_audit) ? $loan_audit['credit_remark'] :  "";?>
            </td>
        </tr>
        <tr>
            <td>
                放款操作人：<?php echo  !empty($loan_audit) ? $loan_audit['credit_username'] :  "";?>
            </td>
        </tr>
    <?php endif;?>
</table>
<script>
    var result = document.getElementById("result").value;
    if(result.length != 0 ){
        alert(result);
    }

    function credit(){
        var credit = document.getElementsByClassName("credit");
        for(var i=0;i<credit.length;i++){
            credit[i].style.display = "block";
        }
    }

    function cancle(){
        var credit = document.getElementsByClassName("credit");
        for(var i=0;i<credit.length;i++){
            credit[i].style.display = "none";
        }
    }
</script>




