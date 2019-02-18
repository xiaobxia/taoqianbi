<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanRepaymentPeriod;
use common\models\LoanRecordPeriod;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<table class="tb tb2 fixpadding">
	<tr><th class="partition" colspan="15">借款人信息</th></tr>
    <tr>
        <td class="td22" >姓名：</td>
        <td width="100"><?php echo $loan_person_info['name']; ?></td>
        <td class="td22">联系方式：</td>
        <td><?php echo $loan_person_info['phone']; ?></td>
    </tr>
    <tr>
        <td class="td22" >出生日期：</td>
        <td width="100"><?php echo date('Y-m-d', $loan_person_info['birthday']); ?></td>
        <td class="td22">授信额度：</td>
        <td><?php echo sprintf('%.2f', $loan_person_info['credit_limit'] / 100); ?>元</td>
    </tr>
    <tr>
        <td class="td22" >紧急联系人：</td>
        <td width="100"><?php echo $loan_person_info['contact_username']; ?></td>
        <td class="td22">联系方式：</td>
        <td><?php echo $loan_person_info['contact_phone']; ?></td>
    </tr>
	<tr><th class="partition" colspan="15">借款信息</th></tr>
    <tr>
        <td class="td22" >借款项目：</td>
        <td width="100"><?php echo $projects[$loan_record_info['loan_project_id']]; ?></td>
        <td class="td22">申请门店：</td>
        <td><?php echo empty($loan_record_info['shop_id']) ? "自营客户" : $shops[$loan_record_info['shop_id']]; ?></td>
    </tr>
    <tr>
        <td class="td22">借款时间：</td>
        <td><?php echo date('Y-m-d', $loan_record_info['apply_time']); ?></td>
        <td class="td22">借款期限：</td>
        <td><?php echo $loan_record_info['period']; ?>个月</td>
    </tr>
    <tr>
    	<td class="td22" >借款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_record_info['amount'] / 100); ?></td>
        <td class="td22" >还款类型：</td>
        <td width="100"><?php echo LoanRecordPeriod::$repay_type[$loan_record_info['repay_type']]; ?></td>
    </tr>
    <tr>
        <td class="td22" >借款利率：</td>
        <td width="100"><?php echo $loan_record_info['apr']; ?>%</td>
        <td class="td22" >服务费率：</td>
        <td width="100"><?php echo $loan_record_info['service_apr']; ?>%</td>
    </tr>
    <tr>
        <td class="td22" >放款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_record_info['credit_amount'] / 100); ?>元</td>
        <td class="td22">放款时间：</td>
        <td><?php echo date('Y-m-d', $loan_record_info['created_at']); ?></td>
    </tr>
	<tr><th class="partition" colspan="15">总还款信息</th></tr>
    <tr>
        <td class="td22" >总还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_info['repayment_amount'] / 100); ?>元</td>
        <td class="td22">已还款金额：</td>
        <td><?php echo sprintf('%.2f', $loan_repayment_info['repaymented_amount'] / 100); ?>元</td>
    </tr>
    <tr>
    	<td class="td22" >签约日期：</td>
        <td width="100"><?php echo date('Y-m-d', $loan_repayment_info['sign_repayment_time']); ?></td>
        <td class="td22">放款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_info['credit_repayment_time']); ?></td>
    </tr>
    <tr>
        <td class="td22">最终还款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_info['repayment_time']); ?></td>
        <td class="td22" >还款状态：</td>
        <td width="100"><?php echo LoanRecordPeriod::$status[$loan_repayment_info['status']]; ?></td>
    </tr>
    <tr>
        <td class="td22" >总还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_info['repayment_amount'] / 100); ?>元</td>
        <td class="td22" >总还款本金：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_info['repayment_principal'] / 100); ?>元</td>
        <td class="td22" >总还款利息：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_info['repayment_interest'] / 100); ?>元</td>
    </tr>
    <tr>
        <td class="td22">总还款期数：</td>
        <td><?php echo $loan_repayment_info['period']; ?>期</td>
    </tr>
	<tr><th class="partition" colspan="15">还款操作</th></tr>
	<tr>
        <td class="td22" >计划还款金额：</td>
        <td><?php echo sprintf('%.2f', $loan_repayment_period_info['plan_repayment_money'] / 100); ?>元</td>
        <td class="td22" >计划还款本金：</td>
        <td><?php echo sprintf('%.2f', $loan_repayment_period_info['plan_repayment_principal'] / 100); ?>元</td>
        <td class="td22">计划还款利息：</td>
        <td><?php echo sprintf('%.2f', $loan_repayment_period_info['plan_repayment_interest'] / 100); ?>元</td>
        <td class="td22">计划还款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_period_info['plan_repayment_time']); ?></td>
    </tr>
    <?php $form = ActiveForm::begin(['id' => 'repay-form']); ?>
    <tr>
        <td class="td22">还款状态：</td>
        <td>
        	<?php echo Html::radioList('repay_status', $loan_repayment_period_info['status'], LoanRepaymentPeriod::$status); ?>
        </td>
    </tr>
    <tr>
        <td class="td27">还款凭证：</td>
        <td class="vtop rowform">
            <?php echo Html::textarea('repayment_img',
                $loan_repayment_period_info['repayment_img'] ? implode(',',json_decode($loan_repayment_period_info['repayment_img'])) : $loan_repayment_period_info['repayment_img'],
                ['placeholder'=>'请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...']); ?>
        </td>
        <td colspan="15">
            <a style="color:#7f63fe;font-weight:600;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件 - </a>
            （<font color="green">请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...</font>）
        </td>
    </tr>
    <tr>
        <td class="td22">实际还款本金：</td>
        <td><input type="text" value="" name="principal">&nbsp元</td>
        <td class="td22"></td>
        <td></td>
    </tr>
    <tr>
        <td class="td22">实际还款利息：</td>
        <td><input type="text" value="" name="interest">&nbsp元</td>
        <td class="td22"></td>
        <td></td>
    </tr>
    <tr>
        <td class="td22">实际还款日期：</td>
        <td><input type="text"   class="txt WdateFmtErr" style="width:180px;" name="true_repay_time" onfocus="WdatePicker({startDate:'%y/%M/%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true});" readonly=""></td>
        <td class="td22"></td>
        <td></td>
    </tr>
    <tr>
        <td class="td22">备注：</td>
        <td><textarea cols="40" rows="4" name="remark"><?php echo $loan_repayment_period_info['remark']?></textarea></td>
        <td class="td22"></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
    <?php ActiveForm::end(); ?>
</table>
