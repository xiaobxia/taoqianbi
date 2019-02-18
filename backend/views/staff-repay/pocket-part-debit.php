<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/16
 * Time: 11:29
 */
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\UserCreditMoneyLog;
/**
 * @var backend\components\View $this
 */
$this->shownav('staff', 'menu_ygb_zc_lqd_lb');
?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">部分还款</th></tr>
        <tr>
            <td class="td24">应还金额(元)：</td>
            <td><?php echo ($repayment_money/100); ?></td>
        </tr>
        <tr>
            <td class="td24">银行流水号：</td>
            <td><?php echo Html::textInput('order_uuid'); ?></td>
        </tr>
        <tr>
            <td class="td24">还款流水号：</td>
            <td><input name="pay_order_id" type="text" id="payorderid" onBlur="getPayOrderMoney(this)"/></td>
        </tr>
        <tr>
            <td class="td24">通道：</td>
            <td><?php echo Html::dropDownList('debit_channel', 0,  array(0=>'-------')+UserCreditMoneyLog::$third_platform_name); ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="td24">实际已还金额(元)：</td>
            <td><?php echo $money/100; ?></td>
            <input name="actual_repay_money" type="hidden" value="<?php echo $money; ?>">
        </tr>
        <tr>
            <td class="td24">本次还款金额(元)：</td>
            <td><input type="text" name="money" id="money" value=""/></td>
        </tr>
        <tr>
            <td class="td24">还款方式</td>
            <td ><?php echo Html::dropDownList('repayment_type', '',
                    [
                        0=>'-所有方式-',
                        UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_TRANS => '银行卡转账',
                        UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS => '支付宝转账',
                        UserCreditMoneyLog::PAYMENT_TYPE_AUTO=>'系统代扣',
                        UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT=>'银行卡支付'
                    ],
                    [
                        'id' => 'payment_type_id',
                        'onChange' => 'changeRepaymentType(this)'
                    ]
                );
                ?>
            </td>
        </tr>
        <tr>
            <td class="td24">还款备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn" onclick="return check();"/>
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">还款操作记录</th></tr>
    <tr>
        <td class="td21">还款金额</td>
        <td class="td21">操作人</td>
        <td class="td21">操作时间</td>
        <td class="td21">状态</td>
        <td class="td21">备注</td>
    </tr>
<?php if($logs):?>

    <?php foreach ($logs as $value): ?>
        <tr>
            <td><?php echo sprintf('%.2f', $value->operator_money / 100); ?></td>
            <td><?php echo $value->operator_name; ?></td>
            <td><?php echo date('Y-m-d H:i:s',$value->created_at); ?></td>
            <td><?php echo UserCreditMoneyLog::$status[$value->status]; ?></td>
            <td><?php echo $value->remark; ?></td>
        </tr>
    <?php endforeach;?>
<?php else:?>
    <tr>
        <td colspan="5">暂无记录</td>
    </tr>
<?php endif;?>
</table>


<?php echo $this->render('pocket-view', [
    'common' => $common,
    'private'=>$private,
]); ?>
<script>
    function check(){
        return confirm('确定本次还款金额为:'+$('#money').val()+'元吗？');
    }

    // 动态获得支付宝支付的金额
    function getPayOrderMoney(that)
    {
        var paymentType = $("#payment_type_id").val();
        if (paymentType == '<?php echo UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?>')
        {
            $("#money").attr("readonly","readonly");
            var repaymentLogNum = $(that).val();
            if (repaymentLogNum.length < 1) return false;
            $.ajax({
                'url':'<?php echo Url::toRoute('staff-repay/get-alipay-log-detail')?>',
                'type':'json',
                'method':'get',
                'data':{'alipayOrderId':repaymentLogNum},
                'success':function(res) {
                    if (res.code == 1)
                    {
                        $("#money").val(money = res.data.money);
                        $("input[name=submit_btn]").removeAttr('disabled');
                    }
                    else
                    {
                        alert(res.message);
                    }
                }
            });
        }
    }
    function changeRepaymentType(that){
        var repaymentType = $(that).val();
        if (repaymentType != '<?php echo UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?>')
        {
            $("input[name=submit_btn]").removeAttr('disabled');
            $("#money").removeAttr("readonly");
        }else{
            $("#money").attr("readonly","readonly");
            var recordId = document.getElementById('payorderid');
            getPayOrderMoney(recordId);
        }
    }

    (function(){
        var paymentType = $("select[name=repayment_type]").val();
        console.log(paymentType);
        if (paymentType == '<?php echo UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?>')
        {
            $("#money").attr("readonly","readonly");
        }
    })()

</script>