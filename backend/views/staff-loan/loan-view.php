<?php
/**
* Created by PhpStorm.
* User: user
* Date: 2015/9/11
* Time: 15:53
*/
use common\models\UserLoanOrder;
use common\models\UserOrderLoanCheckLog;
use common\models\UserRepaymentPeriod;
use common\models\UserLoanOrderRepayment;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\CardInfo;
use common\helpers\Url;

?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.artZoom.js'); ?>"></script>
    <link href="<?php echo Url::toStatic('/css/jquery.artZoom.css'); ?>" rel="stylesheet" type="text/css">

    <style>
        .ui-artZoom{
            position:absolute;
            top:0;
            left:0;
            right:0;
        }
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
    </style>

    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="10">用户信息</th></tr>
        <tr>
            <th class="td24">用户ID：</th>
            <td width="200"><?php echo $loanPerson['id']; ?></td>
            <th class="td24">注册时间：</th>
            <td ><?php echo date('Y-m-d H:i:s',$loanPerson['created_at']); ?></td>
        </tr>
        <tr>
            <th class="td24">姓名：</th>
            <td ><?php echo $loanPerson['name']; ?></td>
            <th class="td24">联系方式：</th>
            <td ><?php echo $loanPerson['phone']; ?></td>
        </tr>
        <tr>
            <th class="td24">性别：</th>
            <td ><?php echo $loanPerson['property']; ?></td>
            <th class="td24">身份证：</th>
            <td ><?php echo $loanPerson['id_number']; ?></td>
        </tr>
        <tr>
            <th class="td24">出生日期：</th>
            <td ><?php echo empty($loanPerson['birthday'])?"--:--":date('Y-m-d',$loanPerson['birthday']); ?></td>
            <th class="td24">公司：</th>
            <td ><?php echo $userDetail['company_name']; ?></td>
        </tr>
    </table>

    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="10">借款信息</th></tr>
        <tr>
            <th class="td24">订单号：</th>
            <td colspan="10"><?php echo $info['id']; ?></td>
        </tr>
        <tr>
            <th class="td24">借款项目：</th>
            <td width="200"><?php echo isset(UserLoanOrder::$loan_type[$info['order_type']])?UserLoanOrder::$loan_type[$info['order_type']]:""; ?></td>
            <th class="td24">借款金额(元)：</th>
            <td ><?php echo sprintf("%.2f",$info['money_amount'] / 100); ?></td>
        </tr>
        <tr>
            <th class="td24">最迟还款日：</th>
            <td ><?php echo !empty($info['loan_time'])?date("Y-m-d",$info['loan_time'] + $info['loan_term'] * 86400):"--:--"; ?></td>
            <th class="td24">申请时间：</th>
            <td ><?php echo date("Y-m-d",$info['order_time']); ?></td>
        </tr>
        <tr>
            <th class="td24">借款利息：</th>
            <td ><?php echo sprintf("%.2f",$info['loan_interests'] / 100); ?></td>
            <th class="td24">借款利率(‱)：</th>
            <td ><?php echo $info['apr']; ?></td>
        </tr>
        <tr>
            <th class="td24">服务费：</th>
            <td ><?php echo sprintf("%.2f",$info['late_fee'] / 100); ?></td>
            <th class="td24">服务费率(‱)：</th>
            <td ><?php echo $info['late_fee_apr']; ?></td>
        </tr>
    </table>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="10">放款信息</th></tr>
        <tr>
            <th class="td24">借款金额：</th>
            <td width="200"><?php echo sprintf("%.2f",$info['money_amount'] / 100); ?></td>
            <th class="td24">前置费用：</th>
            <td><?php echo sprintf("%.2f",$info['late_fee'] / 100); ?></td>
        </tr>
        <tr>
            <th class="td24">实际打款：</th>
            <td ><?php echo sprintf("%.2f",($info['money_amount']-$info['counter_fee'])/ 100); ?></td>

        </tr>
    </table>
    <?php if($card){ ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="10">放款卡信息</th></tr>
        <tr>
            <th class="td24">银行卡ID：</th>
            <td width="200"><?php echo $card['id']; ?></td>
            <th class="td24">银行卡类型：</th>
            <td><?php echo isset(CardInfo::$type[$card['type']])?CardInfo::$type[$card['type']]:''; ?></td>
        </tr>
        <tr>
            <th class="td24">绑卡银行：</th>
            <td ><?php echo $card['bank_name']; ?></td>
            <th class="td24">银行卡号：</th>
            <td ><?php echo $card['card_no']; ?></td>
        </tr>
        <tr>
            <th class="td24">绑卡日期：</th>
            <td ><?php echo date("Y-m-d",$card['created_at']); ?></td>
            <th class="td24">状态：</th>
            <td ><?php echo isset(CardInfo::$status[$card['status']])?CardInfo::$status[$card['status']]:''; ?></td>
        </tr>
    </table>
    <?php } ?>
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
                                <th>审核时间：</th>
                                <th>审核内容：</th>
                                <th>操作类型：</th>
                                <th>审核前状态：</th>
                                <th>审核后状态：</th>
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
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核此项目</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '放款通过',
                    '2' => UserLoanOrder::$status[UserLoanOrder::STATUS_PENDING_CANCEL]
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">审核备注：</td>
            <td class="pass"><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''), $pass_tmp); ?></td>
            <td class="reject" style="display: none"><?php echo Html::dropDownList('nocode', Yii::$app->getRequest()->get('code', ''), $reject_tmp); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>
<script>
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').show();
            $('.pass select').attr('name','code');

            $('.reject').hide();
            $('.reject select').attr('name','nocode');
        }else{
            $('.pass').hide();
            $('.pass select').attr('name','nocode');
            $('.reject').show();
            $('.reject select').attr('name','code');
        }
    });
</script>
