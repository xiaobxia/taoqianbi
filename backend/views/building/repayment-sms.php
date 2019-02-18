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
	<tr><th class="partition" colspan="15">借款信息</th></tr>
    <tr>
        <td class="td22" >姓名：</td>
        <td width="100"><?php echo $loan_person_info['name']; ?></td>
        <td class="td22">联系方式：</td>
        <td><?php echo $loan_person_info['phone']; ?></td>
    </tr>
    <tr>
        <td class="td22" >紧急联系人：</td>
        <td width="100"><?php echo $loan_person_info['contact_username']; ?></td>
        <td class="td22">联系方式：</td>
        <td><?php echo $loan_person_info['contact_phone']; ?></td>
    </tr>
    <tr>
        <td class="td22" >借款项目：</td>
        <td width="100"><?php echo $projects[$loan_record_info['loan_project_id']]; ?></td>
        <td class="td22">申请门店：</td>
        <td><?php echo isset($shops[$loan_record_info['shop_id']])?$shops[$loan_record_info['shop_id']]:""; ?></td>
    </tr>
    <tr>
    	<td class="td22" >借款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_record_info['amount'] / 100); ?></td>
        <td class="td22" >还款类型：</td>
        <td width="100"><?php echo LoanRecordPeriod::$repay_type[$loan_record_info['repay_type']]; ?></td>
    </tr>
	<tr>
        <td class="td22" >计划还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_period_info['plan_repayment_money'] / 100); ?>元</td>
        <td class="td22">计划还款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_period_info['plan_repayment_time']); ?></td>
    </tr>
    <tr>
        <td class="td22" >当前还款期数：</td>
        <td width="100">第<?php echo $loan_repayment_period_info['period']; ?>期</td>
        <td class="td22">当前还款状态：</td>
        <td><?php echo LoanRepaymentPeriod::$status[$loan_repayment_period_info['status']]; ?></td>
    </tr>
    <tr><th class="partition" colspan="15">发送提醒短信</th></tr>
    <?php $form = ActiveForm::begin(['id' => 'repay-form']); ?>
    <tr>
        <td class="td22">手机号码：</td>
        <td>
            <input type="text" name="send_phone" value="<?php echo $loan_person_info['phone'];?>"/>

        </td>
    </tr>
    <tr>
        <td class="td22">短信内容：</td>
        <td><textarea cols="40" rows="4" name="send_sms"><?php echo $sms;?></textarea></td>
        <td class="td22"></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="发送" name="submit_btn" class="btn">
        </td>
    </tr>
    <?php ActiveForm::end(); ?>
</table>