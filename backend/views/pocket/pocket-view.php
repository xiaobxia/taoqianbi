<?php

use common\models\UserLoanOrder;
use common\models\UserQuotaPersonInfo;
use common\models\UserContact;
use common\models\CardInfo;
use common\models\UserProofMateria;
use common\models\UserQuotaWorkInfo;
use common\helpers\Url;

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

    .hide {
        display: none;
    }
</style>

<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">零钱贷借款详情页</th></tr>
    <tr>
        <th width="110px;" class="person">借款详情</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th class="td24">用户ID：</th>
                    <td width="180"><?php echo $information['info']['user_id']; ?></td>
                    <th class="td24">订单号：</th>
                    <td ><?php echo $information['info']['id']; ?></td>
                    <th class="td24">钱包白名单：</th>
                    <td width="180">
                        <?php echo $information['jsqb_watchlist']['is_white']==null ? "未知" : ($information['jsqb_watchlist']['is_white'] ? '是':'否'); ?>
                    </td>
                    <th class="td24">钱包黑名单：</th>
                    <td width="180">
                        <?php echo $information['jsqb_watchlist']['is_black']==null ? "未知" : ($information['jsqb_watchlist']['is_black'] ? '是':'否'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="td24">借款项目：</th>
                    <td class="mark"><?php echo isset(UserLoanOrder::$loan_type[$information['info']['order_type']])?UserLoanOrder::$loan_type[$information['info']['order_type']]:""; ?></td>
                    <th class="td24">借款金额(元)：</th>
                    <td class="mark"><?php echo sprintf("%.2f",$information['info']['money_amount'] / 100); ?></td>
                    <th class="td24">申请时间：</th>
                    <td class="mark"><?php echo date("Y-m-d H:i:s",$information['info']['order_time']); ?></td>
                    <th class="td24">服务费：</th>
                    <td ><?php echo sprintf("%.2f",$information['info']['counter_fee'] / 100); ?></td>
                </tr>
                <tr>
                    <th class="td24">借款利息：</th>
                    <td class="mark"><?php echo sprintf("%.2f",$information['info']['loan_interests'] / 100); ?></td>
                    <th class="td24">借款利率(‱)：</th>
                    <td ><?php echo $information['info']['apr']; ?></td>
                    <th class="td24">服务费率(‱)：</th>
                    <td ><?php echo "--"; ?></td>
                    <th class="td24">最迟还款日：</th>
                    <td ><?php echo !empty($information['info']['loan_time'])?  date("Y-m-d",$information['info']['loan_time'] + $information['info']['loan_term'] * 86400): "--:--"; ?></td>
                </tr>
                <tr>
                    <th class="td24">总额度：</th>
                    <td width="180"><?php echo sprintf("%.2f",$information['credit']['amount'] / 100); ?></td>
                    <th class="td24">已使用额度：</th>
                    <td width="180"><?php echo sprintf("%.2f",($information['credit']['used_amount']+$information['credit']['locked_amount']) / 100); ?></td>
                    <th class="td24">剩余额度：</th>
                    <td><?php echo sprintf("%.2f",($information['credit']['amount']-$information['credit']['used_amount']-$information['credit']['locked_amount']) / 100); ?></td>
                    <th class="td24">&nbsp;</th>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th class="td24">授信总额度：</th>
                    <td width="180"><?php echo $information['credit_line']['credit_line'] ?? '--'; ?></td>
                    <th class="td24">基础额度：</th>
                    <td width="180"><?php echo $information['credit_line']['credit_line_base'] ?? '--'; ?></td>
                    <th class="td24">公积金额度：</th>
                    <td><?php echo $information['credit_line']['credit_line_gjj'] ?? '--'; ?></td>
                    <th class="td24">&nbsp;</th>
                    <td>&nbsp;</td>
                </tr>

            </table>
        </td>
    </tr>
</table>

<?php if(!empty($information['user_credit_data'])):?>
<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">零钱贷借款规则验证结果</th></tr>
    <tr>
        <td>禁止命中项</td>
        <td><?php echo $information['user_credit_data']['forbid_detail'];?></td>
    </tr>
    <tr>
        <td>过滤命中项</td>
        <td><?php echo $information['user_credit_data']['filter_detail'];?></td>
    </tr>
    <tr>
        <td>高风险命中项</td>
        <td><?php echo $information['user_credit_data']['high_detail'];?></td>
    </tr>
    <tr>
        <td>中风险命中项</td>
        <td><?php echo $information['user_credit_data']['middle_detail'];?></td>
    </tr>
    <tr>
        <td>低风险命中项</td>
        <td><?php echo $information['user_credit_data']['low_detail'];?></td>
    </tr>
    <tr>
        <td>白户信息</td>
        <td><?php echo $information['user_credit_data']['is_white_detail'];?></td>
    </tr>
    <tr>
        <td>自动通过规则</td>
        <td><?php echo $information['user_credit_data']['pass_auto_detail'];?></td>
    </tr>
</table>
<?php endif;?>

<?php echo $this->render('/public/pocket-person-info', [
    'information' => $information
]); ?>
<script>
$('.more_info').click(function(){
    if($(this).html() == '点击查看更多'){
        $(this).html('点击隐藏非高风险项');
        $('.hide').show();
    }else{
        $(this).html('点击查看更多');
        $('.hide').hide();
    }
});
</script>
