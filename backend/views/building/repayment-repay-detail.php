<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanRepaymentPeriod;
use common\models\LoanRecordPeriod;
use common\models\BankConfig;

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
        <td><?php echo isset($shops[$loan_record_info['shop_id']])?$shops[$loan_record_info['shop_id']]:""; ?></td>
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
        <td width="100"><?php echo LoanRecordPeriod::$repay_type[$loan_record_info['repay_type']]; ?>%</td>
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
        <td class="td22" >每期还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_info['period_repayment_amount'] / 100); ?>元</td>
        <td class="td22">总还款期数：</td>
        <td><?php echo $loan_repayment_info['period']; ?>期</td>
    </tr>
	<tr><th class="partition" colspan="15">当期还款信息</th></tr>
	<tr>
        <td class="td22" >计划还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_period_info['plan_repayment_money'] / 100); ?>元</td>
        <td class="td22">计划还款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_period_info['plan_repayment_time']); ?></td>
    </tr>
    <tr>
        <td class="td22" >实际还款金额：</td>
        <td width="100"><?php echo sprintf('%.2f', $loan_repayment_period_info['true_repayment_money'] / 100); ?>元</td>
        <td class="td22">实际还款日期：</td>
        <td><?php echo date('Y-m-d', $loan_repayment_period_info['true_repayment_time']); ?></td>
    </tr>
    <tr>
        <td class="td22">还款凭证：</td>
        <td colspan="3">
            <?php if($loan_repayment_period_info['repayment_img']){?>
                <?php foreach(json_decode($loan_repayment_period_info['repayment_img'],true) as $v):?>
                    <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php endforeach;?>
            <?php }?>
        </td>
    </tr>
    <?php if (!empty($repay_info)): ?>
        <tr><th class="partition" colspan="15">扣款信息</th></tr>
        <tr>
            <td class="td22" >扣款平台：</td>
            <td width="100"><?php echo BankConfig::$platform[$repay_info['third_platform']]; ?></td>
            <td class="td22" >扣款卡号：</td>
            <td width="100"><?php echo $repay_info['card_no']; ?></td>
        </tr>
        <tr>
            <td class="td22" >订单号：</td>
            <td width="100"><?php echo $repay_info['order_char_id']; ?></td>
            <td class="td22" >扣款金额：</td>
            <td width="100"><?php echo sprintf('%.2f', $repay_info['pay_amount'] / 100); ?></td>
        </tr>
    <?php endif; ?>
</table>