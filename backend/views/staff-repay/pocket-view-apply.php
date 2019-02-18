<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use common\models\UserOrderLoanCheckLog;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">用户信息</th></tr>
    <tr>
        <td class="td21">用户ID：</td>
        <td width="200"><?php echo $loanPerson['id']; ?></td>
        <td class="td21">注册时间：</td>
        <td ><?php echo empty($loanPerson['created_at'])?'--':date('Y-m-d',$loanPerson['created_at']); ?></td>
    </tr>

    <tr>
        <td class="td21">姓名：</td>
        <td ><?php echo $loanPerson['name']; ?></td>
        <td class="td21">联系方式：</td>
        <td ><?php echo $loanPerson['phone']; ?></td>
    </tr>

    <tr>
        <td class="td21">身份证号：</td>
        <td ><?php echo $loanPerson['id_number']; ?></td>
        <td class="td21">出生日期：</td>
        <td ><?php echo empty($loanPerson['birthday'])?'--':date('Y-m-d',$loanPerson['birthday']); ?></td>
    </tr>

    <tr>
        <td class="td21">公司：</td>
        <td ><?php echo $equipment['company_name']; ?></td>
    </tr>
</table>
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">借款信息</th></tr>
    <tr>
        <td class="td21">订单号：</td>
        <td width="200"><?php echo $order['id']; ?></td>
        <td class="td21">借款类型：</td>
        <td ><?php echo UserLoanOrder::$loan_type[$order['order_type']]; ?></td>
    </tr>

    <tr>
        <td class="td21">借款金额（元）：</td>
        <td ><?php echo sprintf("%0.2f",$order['money_amount']/100); ?></td>
        <td class="td21">申请日期：</td>
        <td ><?php echo date('Y-m-d H:i:s',$order['order_time']); ?></td>
    </tr>

    <tr>
        <td class="td21">还款方式：</td>
        <td ><?php echo UserLoanOrder::$loan_method[$order['loan_method']]; ?></td>
        <td class="td21">借款利率(‱)：</td>
        <td ><?php echo $order['apr'];?></td>
    </tr>

    <tr>
        <td class="td21">借款利息（元）：</td>
        <td><?php echo sprintf("%0.2f",$order['loan_interests']/100); ?></td>
        <td class="td21">服务费（元）：</td>
        <td ><?php echo sprintf("%0.2f",$order['counter_fee']/100); ?></td>
    </tr>

</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >审核信息</th></tr>
    <tr>
        <?php if (empty($trail_log)): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style="padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th >审核人：</th>
                        <th >审核类型：</th>
                        <th >审核时间：</th>
                        <th >审核内容：</th>
                        <th >操作类型：</th>
                        <th >审核前状态：</th>
                        <th >审核后状态：</th>
                    </tr>
                    <?php foreach ($trail_log as $log): ?>
                        <tr>
                            <td><?php echo $log['operator_name'];?></td>
                            <td><?php echo isset($log['type']) ? UserOrderLoanCheckLog::$type[$log['type']] : "--";?></td>
                            <td><?php echo date("Y-m-d",$log['created_at']);?></td>
                            <td><?php echo $log['remark'];?></td>
                            <td><?php echo empty($log['operation_type']) ? "--" : UserOrderLoanCheckLog::$operation_type_list[$log['operation_type']] ;?></td>
                            <?php if(empty($log['repayment_type'])) : ?>
                                <td><?php echo UserLoanOrder::$status[$log['before_status']];?></td>
                                <td><?php echo UserLoanOrder::$status[$log['after_status']];?></td>
                            <?php else : ?>
                                <?php if($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD) : ?>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['before_status']];?></td>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['after_status']];?></td>
                                <?php elseif ($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD || $log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC) : ?>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['before_status']];?></td>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['after_status']];?></td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php  ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>

<?php $form =  ActiveForm::begin(['id' => 'review-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">发起还款申请</th></tr>
    <tr>
        <td >
            应扣款金额:<?php echo sprintf("%0.2f",($info['principal']+$info['interests']+$info['late_fee']-$info['true_total_money'])/100)?>(本金:<?php echo sprintf("%0.2f",($info['principal']/100))?>+利息:<?php echo sprintf("%0.2f",($info['interests']/100))?>+滞纳金:<?php echo sprintf("%0.2f",($info['late_fee']/100))?>-已还:<?php echo sprintf("%0.2f",($info['true_total_money']/100))?>)
        </td>
    </tr>
    <tr>
        <td >
            申请扣款金额:<input type="input" value="<?php echo sprintf("%0.2f",($info['principal']+$info['interests']+$info['late_fee']-$info['true_total_money'])/100)?>" name="repayment_money" >
        </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="发起还款申请" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>


