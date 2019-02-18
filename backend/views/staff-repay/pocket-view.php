<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\helpers\Url;

?>
<?php
echo $this->render('/public/repayment-common-view', array(
    'common'=>$common,
));
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">还款信息</th></tr>
    <tr>
        <td class="td21">记录ID：</td>
        <td width="200"><?php echo $private['repayment']['id']; ?></td>
        <td class="td21">预期还款总金额：</td>
        <td ><?php echo sprintf("%0.2f",($private['repayment']['principal']+$private['repayment']['interests'])/100); ?></td>
    </tr>

    <tr>
        <td class="td21">预期还款利息：</td>
        <td ><?php echo sprintf("%0.2f",($private['repayment']['interests'])/100); ?></td>
        <td class="td21">预期还款本金：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['principal']/100); ?></td>
    </tr>

    <tr>
        <td class="td21">预期还款时间：</td>
        <td ><?php echo date('Y-m-d',$private['repayment']['plan_fee_time']); ?></td>
        <td class="td21">实际还款时间：</td>
        <td ><?php echo empty($private['repayment']['true_repayment_time'])?'':date('Y-m-d',$private['repayment']['true_repayment_time']); ?></td>
    </tr>

    <tr>
        <td class="td21">计息天数：</td>
        <td ><?php echo $private['repayment']['interest_day'];?></td>
        <td class="td21">日利率：</td>
        <td><?php echo $private['repayment']['apr'];?></td>
    </tr>

    <tr>
        <td class="td21">实际已还款总金额：</td>
        <td ><?php echo sprintf("%0.2f",$private['repayment']['true_total_money']/100); ?></td>
        <td class="td21">申请还款时间：</td>
        <td><?php echo empty($private['repayment']['apply_repayment_time'])?'':date('Y-m-d',$private['repayment']['apply_repayment_time']);?></td>
    </tr>

    <tr>
        <td class="td21">申请还款金额：</td>
        <td colspan="3"><?php echo sprintf("%0.2f",$private['repayment']['current_debit_money']/100); ?></td>
    </tr>

</table>