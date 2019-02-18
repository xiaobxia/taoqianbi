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
    <tr><th class="partition" colspan="10">用户额度详情页</th></tr>
    <tr>
        <th width="110px;" class="person">额度详情</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th class="td24" rowspan="2">用户ID：</th>
                    <td width="200"><?php echo $information['credit']['user_id']; ?></td>
                </tr>
                <tr>
                    <th class="td24">总额度：</th>
                    <td width="200"><?php echo sprintf("%.2f",$information['credit']['amount'] / 100); ?></td>
                    <th class="td24">已使用额度：</th>
                    <td width="200"><?php echo sprintf("%.2f",($information['credit']['used_amount']+$information['credit']['locked_amount']) / 100); ?></td>
                    <th class="td24">剩余额度：</th>
                    <td><?php echo sprintf("%.2f",($information['credit']['amount']-$information['credit']['used_amount']-$information['credit']['locked_amount']) / 100); ?></td>
                </tr>

            </table>
        </td>
    </tr>
</table>

<?php echo $this->render('limit-person-info', [
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