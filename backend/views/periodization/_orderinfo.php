<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">订单信息</th></tr>
    <tr>
        <td class="td24">商品名称：</td>
        <td width="300"><?php echo $indiana_order['indiana']['title']; ?></td>
    </tr>
    <tr>
        <td class="td24">商品规格：</td>
        <td width="300"><?php echo $indiana_order['installment_option']; ?></td>
    </tr>
    <tr>
        <td class="td24">商品价格：</td>
        <td width="300"><?php echo $indiana_order['indiana']['installment_price']; ?> 元</td>
    </tr>
    <tr>
        <td class="td24">分期期数：</td>
        <td width="300"><?php echo $indiana_order['installment_month']; ?> 期</td>
    </tr>
    <tr>
        <td class="td24">收获地址：</td>
        <td width="300"><?php echo empty(!$indiana_order['shipping_address'])?$indiana_order['shipping_address']:'---';?></td>
    </tr>
    <tr>
        <td class="td24">申请时间：</td>
        <td width="300"><?php echo $indiana_order['created_at']; ?></td>
    </tr>
</table>