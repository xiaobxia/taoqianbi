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
        <tr><th class="partition" colspan="15">添加退款记录</th></tr>
        <?php if(isset($model->id)) {?>
            <tr>
                <td class="td24">ID：</td>
                <td><?php echo Html::textInput('id', ($model->id)??'',['readonly'=>'readonly']); ?>&nbsp;</td>
            </tr>
        <?php } ?>
        <tr>
            <td class="td24">入账类型：</td>
            <td><?php echo Html::dropDownList('in_type', ($model->in_type)??0,  ['0'=>'-请选择类型-']+\common\models\FinancialRefundLog::$type_list); ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="td24">支付流水号：</td>
            <td><?php echo Html::textInput('in_pay_order',($model->in_pay_order)??'',['id'=>'in_pay_order']); ?></td>
        </tr>
        <tr>
            <td class="td24">退款人姓名：</td>
            <td><?php echo Html::textInput('name',($model->name)??'',['id'=>'name']); ?></td>
        </tr>
        <tr>
            <td class="td24">退款人账号：</td>
            <td><?php echo Html::textInput('account',($model->account)??'',['id'=>'account']); ?></td>
        </tr>
        <tr>
            <td class="td24">退款金额（元）：</td>
            <td><?php echo Html::textInput('out_money',isset($model->out_money) ? $model->out_money/100 : '',['id'=>'out_money']); ?></td>
        </tr>
        <tr>
            <td class="td24">退款原因：</td>
            <td><?php echo Html::textarea('remark', ($model->remark)??'', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <?php if($id > 0) {?>
                <td colspan="15">
                    <input type="submit" value="保存" name="submit_btn" class="btn"/>
                </td>
            <?php } else {?>
                <td colspan="15">
                    <input type="submit" value="提交" name="submit_btn" class="btn"/>
                </td>
            <?php }?>
        </tr>
        <!--
        <tr><th class="partition" colspan="15">流水信息</th></tr>
        <tr>
            <td class="td24">用户姓名：</td>
            <td>--</td>
        </tr>
        <tr>
            <td class="td24">订单ID：</td>
            <td>--</td>
        </tr>

        -->
    </table>
<?php ActiveForm::end(); ?>
