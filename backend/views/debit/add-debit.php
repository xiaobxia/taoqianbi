<?php

use common\models\BankConfig;
use backend\components\widgets\ActiveForm;

$this->shownav('custom_service', 'menu_submit_sheet');
$this->showsubmenu('发起扣款');

$bank_id = $period_data['bank_id'];


?>

<?php $form = ActiveForm::begin(['id' => 'add-debit']); ?>
<table class="tb tb2">
    <tr>
        <td width="100px"><font color="red">*</font>口袋账号</td>
        <td><input type="text" name="account" value="<?php echo $period_data['account']?>" size="14"></td>
    </tr>
    <tr id="id_card">
        <td width="100px"><font color="red">*</font>身份证</td>
        <td><input type="text" name="id_card" value="<?php echo $period_data['id_card']?>" size="30"><font color="red">(扣款校验身份证，防止误操作)</font></td>
    </tr>
    <tr id="platform">
        <td width="100px">银行列表</td>
        <td>
        <select name="bank_id">
            <option value="">请选择银行</option>
            <option value="-1">账户余额扣款</option>
            <?php
                $banks = BankConfig::$bankInfo;
                foreach ($banks as $key => $val) {
            ?>

            <option value="<?php echo $key; ?>" <?php echo $bank_id == $key ? 'selected' : ''?>><?php echo $val;?></option>

            <?php }?>
        </select>
        </td>
    </tr>
    <tr id="card_no">
        <td width="100px">银行卡</td>
        <td><input type="text" name="card_no" value="<?php echo $period_data['card_no']?>" size="30"></td>
    </tr>
    <tr>
        <td width="100px">扣款金额</td>
        <td><input type="text" name="amount" value="<?php echo $period_data['amount']?>" size="14"></td>
    </tr>
    <tr>
        <td width="100px">预留手机号</td>
        <td><input type="text" name="stay_phone" value="<?php echo $period_data['stay_phone']?>" size="14"></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" name="period_id" value="<?php echo $period_data['period_id']?>">
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

	$("#add-debit").submit();
});
</script>

