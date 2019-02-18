<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use common\models\UserOrderLoanCheckLog;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>

</table>

<?php $form =  ActiveForm::begin(['id' => 'review-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">编辑还款金额</th></tr>
    <tr>
        <td >
            还款金额:<?php echo sprintf("%0.2f",($repay['true_total_money'])/100)?>
            <input type="hidden" value="<?php echo sprintf("%0.2f",($repay['true_total_money'])/100)?>" name="true_total_money" />
        </td>
    </tr>
    <tr>
        <td >
            实际还款金额:<input type="input" value="<?php echo sprintf("%0.2f",($repay['true_total_money'])/100)?>" name="true_repay_money" />
        </td>
    </tr>
    <tr>
        <td >
            修改原因:   <textarea name="remark" style="width:300px; height:100px;"><?php echo $repay['remark']?></textarea>
        </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<script>
$(document).ready(function(){
    $('input[name=submit_btn]').click(function(){
        if($.trim($('input[name=true_repay_money]').val())==''){
            alert('实际还款金额不能为空！');
            return false;
        }
        if(isNaN($.trim($('input[name=true_repay_money]').val()))){
            alert('请输入数字！');
            return false;
        }
        if($.trim($('textarea[name=remark]').val())==''){
            alert('修改原因不能为空！');
            return false;
        }
    });
})
</script>
