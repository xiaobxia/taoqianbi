<?php

use common\models\UserRepayment;
use common\models\UserRepaymentPeriod;


?>

<?php
echo $this->render('/public/repayment-common-view', array(
    'common'=>$common,
));
?>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">总还款详情</th></tr>
    <tr>
        <td class="td21">总还款ID：</td>
        <td width="200"><?php echo $private['repayment']['id'] ?></td>
        <td class="td21">总还款状态：</td>
        <td ><?php echo UserRepayment::$status[$private['repayment']['status']]; ?></td>
    </tr>
    <tr>
        <td class="td21">总还款金额(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['repayment_amount']/100); ?></td>
        <td class="td21">已还款金额(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['repaymented_amount']/100); ?></td>
    </tr>

    <tr>
        <td class="td21">总还款本金(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['repayment_principal']/100); ?></td>
        <td class="td21">总还款利息(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['repayment_interest']/100); ?></td>
    </tr>

    <tr>
        <td class="td21">总还款期数：</td>
        <td ><?php echo $private['repayment']['period'] ?></td>
        <td class="td21">下一还款ID：</td>
        <td ><?php echo $private['repayment']['next_period_repayment_id'] ?></td>
    </tr>

</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">本期还款详情</th></tr>
    <tr>
        <td class="td21">分期还款ID：</td>
        <td width="200"><?php echo $private['repaymentPeriod']['id']; ?></td>
        <td class="td21">分期还款状态：</td>
        <td ><?php echo UserRepaymentPeriod::$status[$private['repaymentPeriod']['status']]; ?></td>
    </tr>

    <tr>
        <td class="td21">预期还款金额(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['plan_repayment_money']/100); ?></td>
        <td class="td21">预期还款本金(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['plan_repayment_principal']/100); ?></td>

    </tr>

    <tr>
        <td class="td21">预期还款利息(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['plan_repayment_interest']/100); ?></td>
        <td class="td21">预期还款时间：</td>
        <td ><?php echo date('Y-m-d',$private['repaymentPeriod']['plan_repayment_time']); ?></td>
    </tr>

    <tr>
        <td class="td21">计划还滞纳金(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['plan_late_fee']/100); ?></td>
        <td class="td21">月利率%：</td>
        <td ><?php echo $private['repaymentPeriod']['apr']; ?>%</td>
    </tr>
    <tr>
        <td class="td21">实际还款金额(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['true_repayment_money']/100); ?></td>
        <td class="td21">实际还款时间：</td>
        <td ><?php echo date('Y-m-d',$private['repaymentPeriod']['true_repayment_time']); ?></td>
    </tr>



    <tr>
        <td class="td21">实际还款本金(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['true_repayment_principal']/100); ?></td>
        <td class="td21">实际还款利息(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['true_repayment_interest']/100); ?></td>
    </tr>

    <tr>

        <td class="td21">真实还滞纳金(元)：</td>
        <td ><?php echo sprintf("%0.2f",$private['repaymentPeriod']['true_late_fee']/100); ?></td>
    </tr>

    <tr>

        <td class="td21">申请还款时间：</td>
        <td ><?php echo empty($private['repaymentPeriod']['apply_repayment_time'])?'--':date('Y-m-d',$private['repaymentPeriod']['apply_repayment_time']); ?></td>
    </tr>
</table>
