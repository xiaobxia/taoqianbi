<?php

use common\models\UserLoanOrder;
use common\models\UserQuotaPersonInfo;
use common\models\UserContact;
use common\models\CardInfo;
use common\models\UserProofMateria;
use common\models\UserQuotaWorkInfo;
use common\models\UserOrderLoanCheckLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use common\models\PhoneReviewLog;
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>


<style>
    .person {
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .table td{
        border:1px solid darkgray;
    }
    .tb2 th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .tb2 td{
        border:1px solid darkgray;
    }
    .tb2 {
        border:1px solid darkgray;
    }
    .mark {
        font-weight: bold;
        /*background-color:indianred;*/
        color:red;
    }
</style>

<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">用户信息</th></tr>
    <tr>
        <td class="td21">用户ID：</td>
        <td width="200"><?php echo $common['loanPerson']['id']; ?></td>
        <td class="td21">注册时间：</td>
        <td ><?php echo empty($common['loanPerson']['created_at'])?'--':date('Y-m-d',$common['loanPerson']['created_at']); ?></td>
    </tr>

    <tr>
        <td class="td21">姓名：</td>
        <td ><?php echo $common['loanPerson']['name']; ?></td>
        <td class="td21">联系方式：</td>
        <td ><?php echo $common['loanPerson']['phone']; ?></td>
    </tr>

    <tr>
        <td class="td21">身份证号：</td>
        <td ><?php echo $common['loanPerson']['id_number']; ?></td>
        <td class="td21">出生日期：</td>
        <td ><?php echo empty($common['loanPerson']['birthday'])?'--':date('Y-m-d',$common['loanPerson']['birthday']); ?></td>
    </tr>

    <tr>
        <td class="td21">公司：</td>
        <td ><?php echo $common['equipment']['company_name']; ?></td>
    </tr>
</table>
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">借款信息</th></tr>
    <tr>
        <td class="td21">订单号：</td>
        <td width="200"><?php echo $common['loanOrder']['id']; ?></td>
        <td class="td21">借款类型：</td>
        <td ><?php echo UserLoanOrder::$loan_type[$common['loanOrder']['order_type']]; ?></td>
    </tr>

    <tr>
        <td class="td21">借款金额（元）：</td>
        <td ><?php echo sprintf("%0.2f",$common['loanOrder']['money_amount']/100); ?></td>
        <td class="td21">申请日期：</td>
        <td ><?php echo date('Y-m-d H:i:s',$common['loanOrder']['order_time']); ?></td>
    </tr>

    <tr>
        <td class="td21">还款方式：</td>
        <td >按<?php echo UserLoanOrder::$loan_method[$common['loanOrder']['loan_method']]; ?></td>
        <td class="td21">最迟还款时间：</td>
        <td ><?php echo date("Y-m-d",strtotime("+1day",strtotime($common['last_repayment_time']))); ?></td>
    </tr>

    <tr>
        <td class="td21">借款利率(‱)：</td>
        <td ><?php echo $common['loanOrder']['apr'];?></td>
        <td class="td21">服务费率：</td>
        <td >--</td>
    </tr>

    <tr>
        <td class="td21">借款利息（元）：</td>
        <td><?php echo sprintf("%0.2f",$common['loanOrder']['loan_interests']/100); ?></td>
        <td class="td21">服务费（元）：</td>
        <td ><?php echo sprintf("%0.2f",$common['loanOrder']['counter_fee']/100); ?></td>
    </tr>

</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >审核信息</th></tr>
    <tr>
        <?php if (empty($common['trail_log'])): ?>
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
                    <?php foreach ($common['trail_log'] as $log): ?>
                        <tr>
                            <td><?php echo $log['operator_name'];?></td>
                            <td><?php echo isset($log['type']) ? UserOrderLoanCheckLog::$type[$log['type']] : "--";?></td>
                            <td><?php echo date("Y-m-d H:i:s",$log['created_at']);?></td>
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
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">扣款卡信息</th></tr>
    <tr>
        <td class="td21">银行卡ID：</td>
        <td width="200"><?php echo $common['card_info']['id']; ?></td>
        <td class="td21">银行卡类型：</td>
        <td ><?php echo empty(CardInfo::$type[$common['card_info']['type']])?'':CardInfo::$type[$common['card_info']['type']]; ?></td>
    </tr>

    <tr>
        <td class="td21">银行名称：</td>
        <td ><?php echo $common['card_info']['bank_name'];?></td>
        <td class="td21">银行卡卡号：</td>
        <td ><?php echo $common['card_info']['card_no']; ?></td>
    </tr>

    <tr>
        <td class="td21">绑卡时间：</td>
        <td ><?php echo date('Y-m-d',$common['card_info']['created_at']); ?></td>
        <td class="td21">状态：</td>
        <td ><?php echo empty(CardInfo::$status[$common['card_info']['status']])?'':CardInfo::$status[$common['card_info']['status']]; ?></td>
    </tr>
</table>
