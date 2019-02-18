<?php

use yii\widgets\ActiveForm;
use common\helpers\StringHelper;

?>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">用户放款信息</th>
    </tr>
    <tr>
        <td class="td22">打款ID：</td>
        <td><?php echo $financial_loan_record->id; ?></td>
        <td class="td22">业务订单ID：</td>
        <td><?php echo $financial_loan_record->business_id; ?></td>
        <td class="td22">用户ID：</td>
        <td><?php echo $financial_loan_record->user_id; ?></td>
        <td class="td22">打款状态：</td>
        <td><?php echo \common\models\FinancialLoanRecord::$ump_pay_status[$financial_loan_record->status] ?></td>
    </tr>
    <tr>
        <td class="td22">申请金额：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($financial_loan_record->money); ?>元</td>
        <td class="td22">手续费：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($financial_loan_record->counter_fee); ?>元</td>
        <td class="td22">实际打款金额：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($financial_loan_record->money-$financial_loan_record->counter_fee); ?>元</td>
        <td class="td22">放款时间：</td>
        <td><?php echo date('Y-m-d H:i:s', $financial_loan_record->success_time) ?></td>
    </tr>

    <tr>
        <th class="partition" colspan="15">用户还款信息</th>
    </tr>
    <tr>
        <td class="td22">还款订单ID：</td>
        <td><?php echo $user_loan_order_repayment->id; ?></td>
        <td class="td22">业务订单ID：</td>
        <td><?php echo $user_loan_order_repayment->order_id; ?></td>
        <td class="td22">用户ID：</td>
        <td><?php echo $user_loan_order_repayment->user_id; ?></td>
        <td class="td22">还款状态：</td>
        <td><?php echo \common\models\UserLoanOrderRepayment::$status[$user_loan_order_repayment->status] ?></td>
    </tr>
    <tr>
        <td class="td22">本金：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($user_loan_order_repayment->principal); ?>元</td>
        <td class="td22">滞纳金：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($user_loan_order_repayment->late_fee); ?>元</td>
        <td class="td22">已还金额：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($user_loan_order_repayment->true_total_money); ?>元</td>
        <td class="td22">抵扣券金额：</td>
        <td><?php echo StringHelper::safeConvertIntToCent($user_loan_order_repayment->coupon_money) ?>元</td>
    </tr>
    <tr>
        <td class="td22">放款日期：</td>
        <td><?php echo date('Y-m-d',$user_loan_order_repayment->loan_time); ?></td>
        <td class="td22">应还日期：</td>
        <td><?php echo date('Y-m-d',$user_loan_order_repayment->plan_fee_time); ?></td>
        <td class="td22">实际还款日期：</td>
        <td><?php echo $user_loan_order_repayment->true_repayment_time ? date('Y-m-d',$user_loan_order_repayment->true_repayment_time) : '-'; ?></td>
        <td class="td22">逾期天数：</td>
        <td><?php echo $user_loan_order_repayment->overdue_day ?>天</td>
    </tr>

    <tr>
        <th class="partition" colspan="15">用户还款流水信息</th>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr>
        <th>流水ID</th>
        <th>业务订单ID</th>
        <th>用户ID</th>
        <th>第三方支付号</th>
        <th>金额</th>
        <th>通道</th>
        <th>还款成功时间</th>
        <th>状态</th>
    </tr>
    <?php foreach($logs as $log):?>
        <tr>
            <td><?php echo $log->id; ?></td>
            <td><?php echo $log->order_id; ?></td>
            <td><?php echo $log->user_id; ?></td>
            <td><?php echo $log->pay_order_id ?></td>
            <td><?php echo StringHelper::safeConvertIntToCent($log->operator_money) ?>元</td>
            <td><?php echo \common\models\UserCreditMoneyLog::$third_platform_name[$log->debit_channel]?></td>
            <td><?php echo date('Y-m-d',$log->success_repayment_time)?></td>
            <td><?php echo \common\models\UserCreditMoneyLog::$status[$log->status] ?></td>
        </tr>
    <?php endforeach;;?>
    <tr>
        <th class="partition" colspan="8">用户退款流水信息</th>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr>
        <th>流水ID</th>
        <th>用户ID</th>
        <th>入账类型</th>
        <th>支付流水号</th>
        <th>退款人姓名</th>
        <th>退款人账号</th>
        <th>退款金额</th>
        <th>退款成功时间</th>
        <th>状态</th>
    </tr>
    <?php foreach($financialRefundLog as $frlog):?>
        <tr>
            <td><?php echo $frlog->id; ?></td>
            <td><?php echo $user_loan_order_repayment->user_id; ?></td>
            <td><?php echo \common\models\FinancialRefundLog::$type_list[$frlog->in_type] ?></td>
            <td><?php echo $frlog->in_pay_order; ?></td>
            <td><?php echo $frlog->name; ?></td>
            <td><?php echo $frlog->account; ?></td>
            <td><?php echo StringHelper::safeConvertIntToCent($frlog->out_money) ?>元</td>
            <td><?php echo date('Y-m-d',$frlog->operation_time)?></td>
            <td><?php echo \common\models\FinancialRefundLog::$status_list[$frlog->status] ?></td>
        </tr>
    <?php endforeach;;?>
</table>
