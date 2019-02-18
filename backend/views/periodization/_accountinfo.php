<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">账户信息</th></tr>
    <tr>
        <td class="td24">总资产：</td>
        <td width="300"><?php echo $user_account['total_money']; ?> 元</td>
    </tr>
    <tr>
        <td class="td24">可用余额：</td>
        <td width="300"><?php echo $user_account['usable_money']; ?> 元</td>
    </tr>
    <tr>
        <td class="td24">提现中金额：</td>
        <td width="300"><?php echo $user_account['withdrawing_money']; ?> 元</td>
    </tr>
    <tr>
        <td class="td24">投资中金额：</td>
        <td width="300"><?php echo $user_account['investing_money']; ?> 元</td>
    </tr>
    <tr>
        <td class="td24">口袋宝总金额：</td>
        <td width="300"><?php echo $user_account['kdb_total_money']; ?> 元</td>
    </tr>
</table>