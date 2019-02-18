<?php
use yii\widgets\ActiveForm;
use common\models\CardInfo;
use common\models\BankConfig;
use common\models\FinancialLoanRecord;
use common\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .control-label{display: none;}
</style>
<?php $form = ActiveForm::begin(['id' => 'loan-project-form']); ?>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">打款信息</th>
    </tr>
    <tr>
        <td class="td24">打款用户ID：</td>
        <td><?php echo $record['user_id'] ?></td>
        <td class="td24">打款银行卡ID</td>
        <td><?php echo $record['bind_card_id'];?></td>
    </tr>
    <tr>
        <td class="td24">打款ID：</td>
        <td><?php echo $record['id'] ?></td>
        <td class="td24">申请金额</td>
        <td><?php echo $record['money'] / 100;?>元</td>
    </tr>
    <tr>
        <td class="td24">手续费：</td>
        <td><?php echo $record['counter_fee'] / 100; ?></td>
        <td class="td24">实际打款金额</td>
        <td><?php echo ($record['money'] - $record['counter_fee'])/ 100;?>元</td>
    </tr>
    <tr>
        <td class="td24">打款摘要</td>
        <td><?php echo $record['pay_summary']; ?></td>
        <td class="td24">打款状态</td>
        <td><?php echo !empty($record['status']) ? FinancialLoanRecord::$ump_pay_status[$record['status']] : '无效状态'; ?></td>
    </tr>

    <tr>
        <td class="td24">业务类型</td>
        <td>
            <?php echo empty($record['type']) ? "---" : FinancialLoanRecord::$types[$record['type']] ?>
        </td>
        <td class="td24">打款渠道类型：</td>
        <td>
            <?php
            echo empty($record['payment_type']) ? "---" : FinancialLoanRecord::$payment_types[$record['payment_type']];
            ?>
        </td>
    </tr>
	<tr>
        <td class="label"><?php echo $this->activeLabel($cardInfo, 'bank_id'); ?></td>
        <td ><?php echo Html::dropDownList('CardInfo[bank_id]', $cardInfo->bank_id, BankConfig::$bankInfo,['prompt' => '其他银行','onchange'=>'change(this);']); ?></td>
    </tr>
    <tr id="bank_name" style="<?php echo $cardInfo->bank_id ? 'display: none' : ''?>">
        <td class="label"><?php echo $this->activeLabel($cardInfo, 'bank_name'); ?></td>
        <td class="rowform"><?php echo $form->field($cardInfo, 'bank_name')->textInput(['style'=>'width:500px;']); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($cardInfo, 'card_no'); ?></td>
        <td class="rowform"><?php echo $form->field($cardInfo, 'card_no')->textInput(['style'=>'width:500px;']); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($cardInfo, 'name'); ?></td>
        <td class="rowform"><?php echo $form->field($cardInfo, 'name')->textInput(['style'=>'width:500px;']); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($cardInfo, 'bank_address'); ?></td>
        <td class="rowform"><?php echo $form->field($cardInfo, 'bank_address')->textInput(['style'=>'width:500px;']); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" onclick="return confirm('确定要修改银行卡信息吗？');" value="确认修改" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>
function change(obj){
    if(!$(obj).val()){
        $('#bank_name').show();
    }else{
        $('#bank_name').hide();
    }
}
</script>