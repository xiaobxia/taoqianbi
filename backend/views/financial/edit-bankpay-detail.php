<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\UserCreditMoneyLog;
use common\models\fund\LoanFund;
use common\models\BankConfig;

$this->shownav('financial', 'menu_debit_bankpay_list');
$this->showsubmenu('编辑');

?>
<?php
    $form = ActiveForm::begin(['id' => 'review-form']);
    $fund  =  LoanFund::getAllFundArray();
    //if($info['debit_channel']){
    $debit_channel = UserCreditMoneyLog::$third_platform_name;
    //}else{
    //    $debit_channel = UserCreditMoneyLog::$type;
    //}
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<table class="tb tb2 fixpadding">
    <tr>
        <td class="td24">ID：</td>
        <td><?php echo $info['id']; ?></td>
    </tr>
    <tr>
        <td class="td24">资方：</td>
        <td><?php echo !empty($info['fund_id'])?$fund[$info]:"口袋理财" ;?></td>
    </tr>
    <tr>
        <td class="td24">用户ID：</td>
        <td><?php echo $info['user_id']; ?></td>
    </tr>
    <tr>
        <td class="td24">订单ID：</td>
        <td><?php echo $info['order_id']; ?></td>
    </tr>
    <tr>
        <td class="td24">银行流水号：</td>
        <td><?php echo Html::textInput('order_uuid',$info['order_uuid']); ?></td>
    </tr>
    <tr>
        <td class="td24">还款流水号：</td>
        <td><?php echo Html::textInput('pay_order_id',$info['pay_order_id']); ?></td>
    </tr>
    <tr>
        <td class="td24">实际还款金额(元)：</td>
        <td><?php echo Html::textInput('operator_money',$info['operator_money'] / 100); ?></td>
    </tr>
    <tr>
        <td class="td24">状态：</td>
        <td ><?php echo Html::dropDownList('status', $info['status'],
                UserCreditMoneyLog::$status
            );
            ?>
        </td>
    </tr>

    <tr>
        <td class="td24">通道：</td>
        <td><?php echo Html::dropDownList('debit_channel', $info['debit_channel'], $debit_channel); ?>&nbsp;</td>
    </tr>
    <tr>
        <td class="td24">还款方式</td>
        <td ><?php echo Html::dropDownList('payment_type', $info['payment_type'],
                UserCreditMoneyLog::$payment_type
            );
            ?>
        </td>
    </tr>
    <tr>
        <td class="td24">还款成功时间：</td>
        <td><input style="width: 150px" type="text" value="<?php echo $info['success_repayment_time'] ? date('Y-m-d H:i:s',$info['success_repayment_time']) : ''; ?>" name="success_repayment_time" class="txt" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>&nbsp;</td>
    </tr>
    <tr>
        <td class="td24">还款备注(注明减免金额，已还金额等)：</td>
        <td><?php echo Html::textarea('remark', $info['remark'], ['style' => 'width:300px;']); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" name="view_type" value="loan"/>
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>