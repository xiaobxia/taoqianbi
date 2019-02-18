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
    <tr><th class="partition" colspan="15" style="color: red;">发货</th></tr>
    <?php if($action == "edit"):?>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['periodization/loan-credit-backend']]); ?>
        <tr>
            <td>
                发货状态：
                &nbsp;&nbsp;&nbsp;&nbsp;发货通过：<input type="radio"  onclick ="credit()" value="17" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_REPAYING)): ?>checked="true" <?php endif;?>>
                &nbsp;&nbsp;&nbsp;&nbsp;发货驳回：<input type="radio"  onclick ="cancle()"  value="16" name="status" <?php if(!empty($loan_record_period)  && ($loan_record_period['status'] == LoanRecordPeriod::STATUS_APPLY_MONEY_FALSE)): ?>checked="true" <?php endif;?>>
            </td>
        </tr>
        <tr class="credit">
            <td>
                还款方式：<?php echo LoanRecordPeriod::$repay_type[$loanRecordPeriod->repay_type]; ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                服务费率：<?php echo $loanRecordPeriod->service_apr ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                服务费：<?php echo sprintf("%.2f", $loanRecordPeriod->fee_amount/100); ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                加急费：<?php echo sprintf("%.2f", $loanRecordPeriod->urgent_amount/100); ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                借款利率：<?php echo $loanRecordPeriod->apr."%" ?>
            </td>
        </tr>
        <tr class="credit">
            <td>
                发货时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="credit_repayment_time" onfocus="WdatePicker({startDate:'%y/%M/%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                签约时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="sign_repayment_time" onfocus="WdatePicker({startDate:'%y/%M/%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                首次还款时间：<input type="text"   class="txt WdateFmtErr" style="width:180px;" name="repayment_start_time" onfocus="WdatePicker({startDate:'%y/%M/%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false});" readonly="">
            </td>
        </tr>
        <tr class="credit">
            <td>
                总还款金额：<input type="text" name="repayment_amount" value="<?php echo $loanRecordPeriod->amount / 100 ?>">
            </td>
        </tr>
        <tr class="credit">
            <td>
                借款期限(月)：<input type="text" name="period" value="<?php echo $loanRecordPeriod->period?>">
            </td>
        </tr>
        <tr class="credit">
            <td>
                运单（快递公司+单号）：<input type="text" name="ems_order" value="">
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
                <input type="hidden" name="loan_record_period_id" value="<?php echo $loanRecordPeriod->id?>">
                <input type="submit" name="submit" value="提交" class="btn">
            </td>
        </tr>
        <?php ActiveForm::end(); ?>
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




