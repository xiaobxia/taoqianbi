<?php
use yii\widgets\ActiveForm;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\FinancialDebitRecord;
use common\helpers\Url;

$this->shownav('financial', 'menu_debit_list');
$this->showsubmenu('扣款驳回');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    .control-label{display: none;} .td24{ width: 70px;}
</style>
<table class="tb tb2 fixpadding">
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>

    <tr>
        <th class="partition" colspan="15">扣款驳回信息</th>
    </tr>

    <tr>
        <td class="td24">ID：</td>
        <td width="300"><?php echo $info['id']; ?></td>
    </tr>

    <tr>
        <td class="td24">用户ID：</td>
        <td width="300"><?php echo $info['user_id']; ?></td>
    </tr>

    <tr>
        <td class="td24">驳回原因：</td>
        <td width="300">
            <label><input type="radio" name="remark" value="1"> 客户要求取消扣款</label>
                <label><input type="radio" name="remark" value="2"> 订单异常驳回</label>
            <input type="hidden" name="id" value="<?php echo $info['id']?>">
        </td>
    </tr>
    <tr>
        <td class="td24">提交：</td>
        <td width="300">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
    <?php ActiveForm::end(); ?>
</table>
