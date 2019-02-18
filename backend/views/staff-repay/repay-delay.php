<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/16
 * Time: 11:29
 */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrderDelay;
/**
 * @var backend\components\View $this
 */
$this->shownav('staff', 'menu_ygb_zc_lqd_lb');
?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">手动延期</th></tr>
        <tr>
            <td class="td24">待还本金：</td>
            <td><?php echo Html::textInput('principal',sprintf("%0.2f",$repayment['remain_principal'] / 100),['readonly'=>'true']); ?></td>
        </tr>
        <tr>
            <td class="td24">续期天数</td>
            <td ><?php echo Html::dropDownList('day', '', UserLoanOrderDelay::$delay_days,['id'=>'day','readonly'=>'true']); ?></td>
        </tr>
        <tr>
            <td class="td24">服务费(元)：</td>
            <td><?php echo Html::textInput('counter_fee',$fees[0],['id'=>'counter_fee','readonly'=>'true']); ?></td>
        </tr>
        <tr>
            <td class="td24">续期费(元)：</td>
            <td><?php echo Html::textInput('service_fee',$service_arr[0],['readonly'=>'true','id'=>'service_fee']); ?></td>
        </tr>
        <tr>
            <td class="td24">逾期费(元)：</td>
            <td><?php echo Html::textInput('late_fee',sprintf("%0.2f",$repayment['late_fee'] / 100),['id'=>'late_fee']); ?></td>
        </tr>
        <tr>
            <td class="td24">累计费用(元)：</td>
            <td><?php echo Html::textInput('total_money',$total_moneys[0],['id'=>'total_money','readonly'=>'true']); ?></td>
        </tr>
        <tr>
            <td class="td24">备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="确定" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>

<script>
    var fees = <?php echo json_encode($fees) ?>;
    var total_moneys = <?php echo json_encode($total_moneys) ?>;
    var service_fee  = <?php echo json_encode($service_arr) ?>;
    $(function(){
        $('#day').change(function(){
            var idx = $(this).val();
            $('#total_money').val(total_moneys[idx]);
            $('#counter_fee').val(fees[idx]);
            $('#service_fee').val(service_fee[idx]);
        });
        $('input').blur(function(){
            $('#total_money').val((parseFloat($('#late_fee').val())+parseFloat($('#service_fee').val())+parseFloat($('#counter_fee').val())).toFixed(2));
        });
    });
</script>