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
        <tr><th class="partition" colspa:n="15">手动置为已还款</th></tr>
        <tr>
            <td class="td24">应还金额(元)：</td>
            <td><?php echo ($repayment_money/100); ?></td>
        </tr>
        <tr>
            <td class="td24">还款类型</td>
            <td><?php echo Html::radioList('operation', 2, [
                    //'1' => '已线下还款',
                    '2' => '减免滞纳金',
                    '3' => '部分还款'
                ]); ?></td>
        </tr>
        <tr class="dis_class">
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
        <tr class="dis_class">
            <td class="td24">银行流水号：</td>
            <td><?php echo Html::textInput('order_uuid'); ?></td>
        </tr>
        <tr class="dis_class">
            <td class="td24">还款流水号：</td>
            <td><?php echo Html::textInput('pay_order_id','',['id'=>'payorderid','onBlur'=>'getPayOrderMoney(this)']); ?></td>
        </tr>
<!--        <tr>-->
<!--            <td class="td24">还款凭证url：</td>-->
<!--            <td>--><?php //echo Html::textInput('img_url'); ?><!--&nbsp;&nbsp;&nbsp;<a href="--><?//=Url::toRoute(['attachment/add'])?><!--" target="_blank">去上传凭证</a></td>-->
<!--        </tr>-->
        <tr class="dis_class">
            <td class="td24">通道：</td>
            <td><?php echo Html::dropDownList('debit_channel', 0,  array(0=>'-------')+UserCreditMoneyLog::$third_platform_name); ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="td24">实际已还金额(元)：</td>
            <td><?php echo $money/100; ?></td>
            <input name="actual_repay_money" type="hidden" value="<?php echo $money; ?>">
        </tr>
        <tr>
            <td class="td24" id="money_td">实际还款金额(元)：</td>
            <td><?php echo Html::textInput('money','',['id'=>'payment_money_id']); ?></td>
        </tr>
        <tr>
            <td class="td24">还款备注(注明减免金额，已还金额等)：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="hidden" name="view_type" value="loan"/>
                <input type="submit" value="提交" name="submit_btn" class="btn"/>
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

<?php echo $this->render('pocket-view', [
    'common' => $common,
    'private'=>$private,
]); ?>
<script>
    $(function(){
        updateTypeHtml();
        $("input[name='operation']").click(function(){
            updateTypeHtml();
        });

    });
    function updateTypeHtml() {
        if($(":radio:checked").val() == 2){
            $("input[name='money']").val(<?php echo $money / 100;?>);
            $("#money_td").html('实际还款金额(元)：');
            $(".dis_class").hide();
        }else if($(":radio:checked").val() == 3){
            $("input[name='money']").val('');
            $("#money_td").html('本次还款金额(元)：');
            $(".dis_class").show();
        }else{
            $("input[name='money']").val(<?php echo $repayment_money / 100;?>);
            $("#money_td").html('实际还款金额(元)：');
            $(".dis_class").show();
        }
    }
    // 动态获得支付宝支付的金额
    function getPayOrderMoney(that)
    {
        var paymentType = $("#payment_type_id").val();
        if (paymentType == '<?php echo UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?>')
        {
            $("#payment_money_id").attr("readonly","readonly");
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
                        $("#payment_money_id").val(money = res.data.money);
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
            $("#payment_money_id").removeAttr("readonly");
        }else{
            $("#payment_money_id").attr("readonly","readonly");
        }
    }

    (function(){
        var paymentType = $("select[name=repayment_type]").val();
        if (paymentType == '<?php echo UserCreditMoneyLog::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS?>')
        {
            $("#payment_money_id").attr("readonly","readonly");
        }
    })()
</script>
