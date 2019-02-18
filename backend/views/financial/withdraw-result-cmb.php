<?php

use backend\components\widgets\ActiveForm;
use common\models\FinancialLoanRecord;
use yii\helpers\Html;
use common\models\asset\AssetOrder;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('financial', 'menu_loan_list');
$this->showsubmenu('付款查询');

?>
<style>
    .red{color:red}
</style>
<table class="tb tb2 fixpadding">
    <?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <tr>
        <th class="partition" colspan="15">借款用户信息</th>
    </tr>
    <tr>
        <td class="td24">用户ID：</td>
        <td width="300"><?php echo $withdraw_info['user_id'] ?></td>
        <td class="td24">用户手机：</td>
        <td><?php echo $withdraw_info['user_name']; ?></td>
    </tr>
    <tr>
        <td class="td24">用户名：</td>
        <td width="300"><?php echo $withdraw_info['user_realname']; ?></td>
        <td class="td24">生日：</td>
        <td><?php echo date("Y-m-d", $withdraw_info['user_birthday']) ?></td>
    </tr>
    <tr>
        <th class="partition" colspan="15">打款信息</th>
    </tr>
    <tr>
        <td class="td24">业务订单号：</td>
        <td>
            <?php if(in_array($withdraw_info['type'], FinancialLoanRecord::$other_platform_type)){ ?>
            <a href="<?php echo Url::toRoute(['asset/orders-detail', 'id' => $withdraw_info['business_id']]);?>">
            <?php echo $withdraw_info['business_id'].'(点击查看)'; ?>
            </a>
            <?php }else{?>
            <?php echo $withdraw_info['business_id']; ?>
            <?php }?>
        </td>
        <td class="td24">借款来源</td>
        <td><?php echo empty($withdraw_info['type']) ? "---" : FinancialLoanRecord::$types[$withdraw_info['type']] ?></td>
    </tr>
    <tr>
        <td class="td24">打款金额：</td>
        <td class="red"><?php echo $withdraw_info['money'];?></td>
        <td class="td24">打款手续费：</td>
        <td class="red"><?php echo $withdraw_info['counter_fee']; ?></td>
    </tr>
    <tr>
        <td class="td24">实际打款金额</td>
        <td class="red"><?php echo $withdraw_info['true_money'];?></td>
        <td class="td24">申请时间：</td>
        <td><?php echo $withdraw_info['created_at']; ?></td>
    </tr>
    <tr>
        <td class="td24">订单ID</td>
        <td><?php echo $withdraw_info['order_id']; ?></td>
        <td class="td24">打款状态</td>
        <td><?php echo !empty($withdraw_info['status']) ? FinancialLoanRecord::$ump_pay_status[$withdraw_info['status']] : '无效状态'; ?></td>
    </tr>
    <tr>
        <td class="td24">打款摘要：</td>
        <td><?php echo $withdraw_info['pay_summary'] ?></td>
        <td class="td24">申请打款渠道类型：</td>
        <td>
            <?php
            echo empty($withdraw_info['payment_type']) ? "---" : FinancialLoanRecord::$payment_types[$withdraw_info['payment_type']];
            ?>
        </td>
    </tr>
    <tr>
        <th class="partition" colspan="15">打款银行卡信息</th>
    </tr>
    <tr>
        <td class="td24">打款银行卡号：</td>
        <td width="300"><?php echo $card_info['card_no'] ?></td>
        <td class="td24">打款银行名称：</td>
        <td><?php echo $card_info['bank_name'] ?></td>
    </tr>
    <tr>
        <td class="td24">持卡人姓名：</td>
        <td width="300"><?php echo $card_info['name'] ?></td>
        <td class="td24">开户行地址：</td>
        <td><?php echo $card_info['bank_address'] ?></td>
    </tr>
    <tr>
        <th class="partition" colspan="15">其他信息</th>
    </tr>
    <tr>
        <td class="td24">审核人：</td>
        <td><?php echo $withdraw_info['review_username'] ?></td>
        <td class="td24">审核时间：</td>
        <td><?php echo $withdraw_info['review_time'] ?></td>
    </tr>
    <tr>
        <td class="td24">审核状态：</td>
        <td width="300"><font color="red"><?php echo FinancialLoanRecord::$review_status[$withdraw_info['review_result']]; ?></font></td>
        <td class="td24">打款状态：</td>
        <td><font color="red"><?php echo $withdraw_info['status_desc'] ?>(仅提现中状态可以人工打款)</font></td>
    </tr>
    <tr>
        <td class="td24">最近更新时间：</td>
        <td><?php echo $withdraw_info['updated_at'] ?></td>
    </tr>
</table>
<table style="margin-top: 10px;">
    <tr><th class="partition" colspan="15">重新发起</th></tr>
    <tr style="margin-top: 15px;">
        <td class="td24">更新订单id：</td>
        <td><?php echo Html::textInput('newOrderID', $withdraw_info['order_id'], ['style' => 'width:220px;']); ?></td>
        <td style="width:400px;margin-left: 10px;">此订单id仅限于在提现重新发起的时候使用，其他情况禁止使用！！</td>
        <td></td>
    </tr>
    <tr><td style="height: 20px;"></td></tr>
    <tr style="margin-top: 15px;">
        <td class="td24">备注：</td>
        <td><?php echo Html::textarea('remarkMessage', '', ['style' => 'width:200px;height:50px;']); ?></td>
        <td class="td24"></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn_tj" class="btn">
        </td>
    </tr>
</table>
<?php $form = ActiveForm::begin(['id' => 'activity-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">订单信息</th></tr>
        <tr>
            <td class="td24">商户端订单号：</td>
            <td colspan="2"><?php echo $result['order_id']; ?></td>
        </tr>
        <tr>
            <td class="td24">订单日期：</td>
            <td colspan="2"><?php echo date("Y-m-d H:i:s", $result['order_date']); ?></td>
        </tr>
        <tr>
            <td class="td24">打款金额：</td>
            <td colspan="2"><?php echo $result['amount']/100; ?>元</td>
        </tr>

        <tr>
            <td class="td24">流程实例号：</td>
            <td colspan="2"><?php echo $withdraw_info['result']; ?></td>
        </tr>

        <tr>
            <td class="td24">直连打款结果：</td>
            <td colspan="2"><?php echo $withdraw_info['notify_result']; ?></td>
        </tr>

        <tr>
            <td class="td24">回调业务方结果：</td>
            <td colspan="2"><?php
                if (!empty($withdraw_info['callback_result'])) {
                        $ca_res = json_decode($withdraw_info['callback_result'], true);
                        print_r($ca_res);
                }
                 ?></td>
        </tr>
        </br>
        <tr>
            <td colspan="15">
                <input type="submit" value="直连失败手动置为成功" name="submit_btn_zl" class="btn"> 主要用于第三方支付异步通知出现问题时使用，请确认提现已到账才做此操作！
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
    <tr>
        <td colspan="15">
            <a class="btn" href="<?php echo \yii\helpers\Url::toRoute(['financial/review-success', 'id' => $withdraw_info['id'] ])?>">直连审核通过提现中</a>主要用于初始状态为直连失败体现中！        </td>
    </tr>
</table>

<?php
if(in_array($withdraw_info['type'], FinancialLoanRecord::$other_platform_type)){
    echo $this->render('/asset/_operator_log_list',['table_name'=>AssetOrder::tableName(),'table_id'=>$withdraw_info['business_id']]);
}
?>