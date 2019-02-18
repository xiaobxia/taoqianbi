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

?>
<?php $form = ActiveForm::begin(); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">退款信息填写</th></tr>
    <tr>
        <td class="td24">出账类型：</td>
        <td><?php echo Html::hiddenInput('id',($model->id));?></td>
    </tr>
    <tr>
        <td class="td24">ID：</td>
        <td><?php echo $model->id;?></td>
    </tr>
    <tr>
        <td class="td24">用户姓名：</td>
        <td><?php echo $model->name;?></td>
    </tr>
    <tr>
        <td class="td24">入账类型：</td>
        <td><?php echo \common\models\FinancialRefundLog::$type_list[$model->in_type];?></td>
    </tr>
    <tr>
        <td class="td24">入账流水号：</td>
        <td><?php echo $model->in_pay_order;?></td>
    </tr>
    <tr>
        <td class="td24">入账金额：</td>
        <td><?php echo $model->in_money/100;?></td>
    </tr>
    <tr>
        <td class="td24">申请人：</td>
        <td><?php echo $model->apply_username;?></td>
    </tr>
    <tr>
        <td class="td24">最新审核人：</td>
        <td><?php echo $model->audit_username;?></td>
    </tr>
    <tr>
        <td class="td24">退款原因：</td>
        <td><?php echo $model->remark;?></td>
    </tr>
    <tr>
        <td class="td24">审核备注：</td>
        <td><?php echo $model->remark_2;?></td>
    </tr>
    <tr>
        <td class="td24">创建时间：</td>
        <td><?php echo date("Y-m-d H:i:s",$model->created_at);?></td>
    </tr>
    <tr>
        <td class="td24">上次审核时间：</td>
        <td><?php echo date("Y-m-d H:i:s",$model->updated_at);?></td>
    </tr>
    <tr>
        <td class="td24">出账类型：</td>
        <td><?php echo Html::dropDownList('out_type', ($model->out_type)??0,  ['0'=>'-请选择类型-']+\common\models\FinancialRefundLog::$type_list); ?>&nbsp;</td>
    </tr>
    <tr>
        <td class="td24">出账流水号：</td>
        <td><?php echo Html::textInput('out_pay_order',($model->out_pay_order)??'',['id'=>'out_pay_order']); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>

</script>
