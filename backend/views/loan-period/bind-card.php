<?php

use common\models\BankConfig;
use backend\components\widgets\ActiveForm;
use common\models\UserBankCard;

$this->shownav('custom_service', 'menu_submit_sheet');
$this->showsubmenu('绑卡操作');

?>
<?php if(!empty($loan_record_period->debit_card_id)):
        $bank_card = UserBankCard::findOne($loan_record_period->debit_card_id);
    ?>
    <table class="tb tb2">
        <tr>
            <td width="100px"><font color="red">*</font>口袋账号</td>
            <td><?php echo $user['phone']?></td>
        </tr>
        <tr id="id_card">
            <td width="100px"><font color="red">*</font>身份证</td>
            <td><?php echo $user['id_card']; ?></td>
        </tr>
        <tr id="platform">
            <td width="100px">银行列表</td>
            <td>
                <?php echo $bank_card['bank_name']; ?>
            </td>
        </tr>
        <tr id="card_no">
            <td width="100px">银行卡号</td>
            <td>
                <?php echo $bank_card['card_no']; ?>
            </td>
        </tr>
        <tr>
            <td>
                备注
            </td>
            <td>
                <textarea cols="40" rows="4" name="remark"><?php echo empty($loan_repayment) ? "" : $loan_repayment->bind_remark;?></textarea>
            </td>
        </tr>
    </table>
<?php else:?>
    <?php $form = ActiveForm::begin(['id' => 'add-debit','method'=>'post', 'action' => ['loan-period/bind-card']]); ?>
    <table class="tb tb2">
        <tr>
            <td width="100px"><font color="red">*</font>口袋账号</td>
            <td><input type="text" style="background-color: rgba(35,106,81,0.21);" name="account" value="<?php echo $user['phone']?>"  readonly = "true" size="14" ></td>
        </tr>
        <tr id="id_card">
            <td width="100px"><font color="red">*</font>身份证</td>
            <td><input type="text" name="id_card"  style="background-color: rgba(35,106,81,0.21);"  readonly = "true"  size="30" value = "<?php echo $user['id_card']; ?>"><font color="red">(扣款校验身份证，防止误操作)</font></td>
        </tr>
        <tr id="platform">
            <td width="100px">银行列表</td>
            <td>
<!--                <select name="bank_id">-->
<!--                    <option value="">请选择银行</option>-->
<!--                    <option value="1">工商银行</option>-->
<!--                    <option value="2">农业银行</option>-->
<!--                    <option value="7">建设银行</option>-->
<!--                    <option value="3">光大银行</option>-->
<!--                    <option value="5">兴业银行</option>-->
<!--                </select>-->

                <select name="bank_id">
                    <option value="">请选择银行</option>
                    <?php
                    $banks = BankConfig::$bankInfo;
                    foreach ($banks as $key => $val) {
                        ?>
                        <option value="<?php echo $key; ?>"><?php echo $val;?></option>
                    <?php }?>
                </select>
                <font color="red">（未绑卡输入银行卡相关信息）</font>


            </td>
        </tr>
        <tr id="card_no">
            <td width="100px">银行卡号</td>
            <td><input type="text" name="card_no" value="" size="30"></td>
        </tr>
        <tr>
            <td>
                备注
            </td>
            <td>
                <textarea cols="40" rows="4" name="remark"></textarea>
            </td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="hidden" value="<?php echo $user['id'];?>"  name="user_id">
                <input type="hidden" value="<?php echo $loan_record_period['id'];?>"  name="loan_record_period_id">
                <input type="submit" value="提交" class="btn">
            </td>
        </tr>
    </table>
    <?php ActiveForm::end(); ?>
<?php endif;?>

<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>

