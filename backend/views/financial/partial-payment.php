<?php
use yii\widgets\ActiveForm;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\FinancialDebitRecord;
use common\helpers\Url;
use common\models\BankConfig;

$this->shownav('financial', 'menu_debit_list');
$this->showsubmenu('生成扣款记录');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    .control-label{display: none;}
</style>

<?php $form = ActiveForm::begin(['id' => 'debit-record']); ?>
<table class="tb tb2">
    <input type="hidden" name="id" value="<?php echo $info['id'];?>"/>
    <tr>
        <td width="200px"><font color="red">*</font>快借用户手机号：</td>
        <td><?php echo $info['loanPerson']['phone']?></td>
    </tr>
    <tr id="id_card">
        <td width="200px"><font color="red">*</font>身份证</td>
        <td><?php echo $info['loanPerson']['id_number']?><font color="red"></font></td>
    </tr>
    <tr>
        <td width="200px">应还金额</td>
        <td><?php echo sprintf("%.2f", $info['plan_repayment_money'] / 100)?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="button" id="submit_btn" value="提交" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
<script type="text/javascript">
    $("#submit_btn").click(function(){
        $(this).attr("disabled", true);
        $(this).val("正在提交中");
        $("#debit-record").submit();
    });
</script>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">扣款详细信息</th>
    </tr>

    <tr>
        <td class="td24">ID：</td>
        <td width="300"><?php echo $info['status']; ?></td>
        <td class="td24">扣款订单号：</td>
        <td width="300"><?php echo $info['order_id']; ?></td>
    </tr>

    <tr>
        <td class="td24">借款用户id：</td>
        <td width="300"><?php echo $info['user_id']; ?></td>
        <td class="td24">扣款银行卡ID：</td>
        <td width="300"><?php echo $info['debit_card_id']; ?></td>
    </tr>
    <tr>
        <td class="td24">总还款ID：</td>
        <td width="300"><?php echo $info['repayment_id']; ?></td>
        <td class="td24">分期还款计划ID：</td>
        <td width="300"><?php echo $info['repayment_peroid_id']; ?></td>
    </tr>

    <tr>
        <td class="td24">预期还款金额：</td>
        <td width="300"><?php echo sprintf("%.2f", $info['plan_repayment_money'] / 100); ?></td>
        <td class="td24">预期还款本金：</td>
        <td width="300"><?php echo sprintf("%.2f", $info['plan_repayment_principal']  / 100); ?></td>
    </tr>

    <tr>
        <td class="td24">预期还款利息：</td>
        <td width="300"><?php echo sprintf("%.2f", $info['plan_repayment_interest'] / 100); ?></td>
        <td class="td24">滞纳金：单位分：</td>
        <td width="300"><?php echo sprintf("%.2f", $info['plan_repayment_late_fee'] / 100); ?></td>
    </tr>

    <tr>
        <td class="td24">预期还款时间：</td>
        <td width="300"><?php  echo date("y-m-d H:i:s", $info['plan_repayment_time']); ?></td>
        <td class="td24">业务类型：</td>
        <td width="300"><?php  echo !isset(UserLoanOrder::$loan_type[$info['type']]) ? "未知" : UserLoanOrder::$loan_type[$info['type']]; ?></td>
    </tr>

    <tr>
        <td class="td24">实际还款金额：</td>
        <td width="300"><?php echo sprintf("%.2f", $info['true_repayment_money'] / 100); ?></td>
        <td class="td24">实际还款时间：</td>
        <td width="300"><?php echo date("y-m-d H:i:s", $info['true_repayment_time']); ?></td>
    </tr>

    <tr>
        <td class="td24">扣款状态：</td>
        <td width="300"><?php echo !isset(FinancialDebitRecord::$status[$info['status']]) ? "未知" : FinancialDebitRecord::$status[$info['status']]; ?></td>
        <td class="td24">还款管理员名称：</td>
        <td width="300"><?php echo $info['admin_username']; ?></td>
    </tr>

    <tr>
        <td class="td24">备注：</td>
        <td width="300"><?php echo $info['remark'];?></td>
        <td class="td24">还款凭证：</td>
        <td width="300"><?php echo $info['repayment_img']; ?></td>
    </tr>

    <tr>
        <td class="td24">创建时间：</td>
        <td width="300"><?php echo date("y-m-d H:i:s", $info['created_at']);?></td>
        <td class="td24">更新时间：</td>
        <td width="300"><?php echo date("y-m-d H:i:s", $info['updated_at']); ?></td>
    </tr>

    <tr>
        <th class="partition" colspan="15">用户信息</th>
    </tr>
    <tr>
        <td class="td24">用户ID：</td>
        <td width="300"><?php echo $info['loanPerson']['id']; ?></td>
        <td class="td24">用户姓名：</td>
        <td width="300"><?php echo isset($info['loanPerson']['name'])?$info['loanPerson']['name']:'--'; ?></td>
    </tr>
    <tr>
        <td class="td24">联系方式：</td>
        <td width="300"><?php echo isset($info['loanPerson']['phone'])?$info['loanPerson']['phone']:'--'; ?></td>
        <td class="td24">紧急联系人：</td>
        <td width="300"><?php echo $info['loanPerson']['contact_username'].": ".$info['loanPerson']['contact_phone']; ?></td>
    </tr>
    <tr>
        <td class="td24">银行卡绑定状态：</td>
        <td width="300"><?php echo isset($info['loanPerson']['card_bind_status'])? LoanPerson::$status_bind[$info['loanPerson']['card_bind_status']]:LoanPerson::$status_bind[LoanPerson::BIND_CARD_NO]; ?></td>
        <td class="td24">实名认证状态：</td>
        <td width="300"><?php echo isset($info['loanPerson']['is_verify'])?LoanPerson::$is_real_verify[$info['loanPerson']['is_verify']]:LoanPerson::$is_real_verify[LoanPerson::BIND_CARD_YES]; ?></td>
    </tr>


    <tr>
        <th class="partition" colspan="15">订单信息</th>
    </tr>
    <tr>
        <td class="td24">订单ID：</td>
        <td width="300"><?php echo $info['userLoanOrder']['id']; ?></td>
        <td class="td24">业务类型：</td>
        <td width="300"><?php echo !isset(UserLoanOrder::$loan_type[$info['userLoanOrder']['order_type']]) ? "未知" : UserLoanOrder::$loan_type[$info['userLoanOrder']['order_type']]; ?></td>
    </tr>
    <tr>
        <td class="td24">创建时间：</td>
        <td width="300"><?php echo date("Y-m-d H:i", $info['userLoanOrder']['created_at']); ?></td>
        <td class="td24">更新时间：</td>
        <td width="300"><?php echo date("Y-m-d H:i", $info['userLoanOrder']['updated_at']); ?></td>
    </tr>
    <tr>
        <td class="td24">订单状态：</td>
        <td width="300"><?php echo !isset($info['userLoanOrder']['status']) ? "未知" : UserLoanOrder::$status[$info['userLoanOrder']['status']]; ?></td>
        <td class="td24">订单银行卡ID：</td>
        <td width="300"><?php echo $info['userLoanOrder']['card_id']; ?></td>
    </tr>

    <tr>
        <th class="partition" colspan="15">银行卡信息</th>
    </tr>
    <tr>
        <td class="td24">银行名称：</td>
        <td width="300"><?php echo $info['cardInfo']['bank_name']; ?></td>
        <td class="td24">银行卡号：</td>
        <td width="300"><?php echo $info['cardInfo']['card_no']; ?></td>
    </tr>
    <tr>
        <td class="td24">银行卡号类型：</td>
        <td width="300"><?php echo !isset(CardInfo::$type[$info['cardInfo']['type']]) ? "未知" : CardInfo::$type[$info['cardInfo']['type']]; ?></td>
        <td class="td24">银行卡状态：</td>
        <td width="300"><?php echo !isset(CardInfo::$type[$info['cardInfo']['status']]) ? "未知" :  CardInfo::$status[$info['cardInfo']['status']]; ?></td>
    </tr>

</table>
