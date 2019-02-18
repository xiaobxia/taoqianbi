<?php
use common\models\AccumulationFund;
?>
<style>
.td24{
    width: 200px;
    font-weight: bold;
}
</style>
<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">用户公积金详情页</th></tr>
    <tr>
        <th class="td24">ID：</th>
        <td width="200px"><?php echo $list['id']; ?></td>
        <th class="td24">用户ID：</th>
        <td><?php echo $loan_person['id']; ?></td>
    </tr>
    <tr>
        <th class="td24">姓名：</th>
        <td><?php echo $loan_person['name']; ?></td>
        <th class="td24">手机号：</th>
        <td><?php echo $loan_person['phone']; ?></td>
    </tr>
    <tr>
        <th class="td24">来源：</th>
        <td><?php echo $list['channel']; ?></td>
        <th class="td24">状态：</th>
        <td><?php echo isset($list['status']) ? AccumulationFund::$status[$list['status']] : "--"; ?></td>
    </tr>
    <tr>
        <th class="td24">城市：</th>
        <td><?php echo $list['city']; ?></td>
        <th class="td24">备注：</th>
        <td ><?php echo empty($list['message']) ? "--" : $list['message']; ?></td>
    </tr>
        <tr>
            <th class="td24">真实姓名：</th>
            <td><?php echo $param['real_name'] ?? '';?></td>
            <th class="td24">token：</th>
            <td ><?php echo $param['token'] ?? ''; ?></td>
        </tr>
        <tr>
            <th class="td24">公积金账户：</th>
            <td><?php echo $param['fund_num'] ?? ''; ?></td>
            <th class="td24">创建时间：</th>
            <td><?php echo $list['created_at'] ? date("Y-m-d H:i:s",$list['created_at']) : ''; ?></td>
        </tr>
        <tr>
            <th class="td24">最近缴纳：</th>
            <td><?php echo $param['fund_amt'] ?? ''; ?></td>
            <th class="td24">公司：</th>
            <td><?php echo $param['company'] ?? ''; ?></td>
        </tr>
        <tr>
            <th class="td24">公积金余额：</th>
            <td><?php echo $param['balance'] ?? '';?></td>
            <th class="td24">公积金状态：</th>
            <td><?php echo $param['housing_fund_status'] ?? ''; ?></td>
        </tr>
    <tr>
        <th class="td24">最近一年缴纳月数：</th>
        <td><?php echo isset($list['pay_months']) ? $list['pay_months'] : 'TODO';?></td>
        <th class="td24">最近一年缴纳平均金额：</th>
        <td ><?php echo isset($list['average_amt']) ? ($list['average_amt']/100) : 'TODO'; ?></td>
    </tr>
    <tr>
        <td colspan="4">
            <table class="tb tb2 fixpadding">

                <tr class="header">
                    <th>备注</th>
<!--                    <th>pay_base</th>-->
                    <th>交易金额</th>
                    <th>转移金额</th>
                    <th>缴纳公司</th>
<!--                    <th>balance</th>-->
                    <th>缴纳时间</th>
                </tr>
                <?php foreach ($data as $value): ?>
                    <tr class="hover">
                        <td class="td25"><?php echo $value['note']; ?></td>
<!--                        <td class="td25">--><?php //echo $value['pay_base']; ?><!--</td>-->
                        <td class="td25"><?php echo $value['trading_amt']; ?></td>
                        <td class="td25"><?php echo $value['transfer_amount']; ?></td>
                        <td class="td25"><?php echo $value['company']; ?></td>
<!--                        <td class="td25">--><?php //echo $value['balance']; ?><!--</td>-->
                        <td class="td25"><?php echo $value['trading_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
</table>