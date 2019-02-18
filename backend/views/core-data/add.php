<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 11:41
 */
use common\helpers\Url;
use yii\helpers\Html;
use common\models\LoanPersonChannelRebate;
use backend\components\widgets\ActiveForm;
?>
<?php $form = ActiveForm::begin(['id'=>"main_form"]); ?>
<style>
    .td25{
        width: 150px;
    }
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th colspan="15" class="partition">添加渠道返利配置</th>
    </tr>
    <tr>
        <td class="td25">手机号：</td>
        <td><?php echo Html::textInput('phone', ''); ?></td>
    </tr>
    <tr>
        <td>首次佣金比例(%):</td>
        <td><?php echo $form->field($data, 'first_rebate_apr')->textInput(); ?></td>
    </tr>
    <tr>
        <td>二次佣金比例(%):</td>
        <td><?php echo $form->field($data, 'second_rebate_apr')->textInput(); ?></td>
    </tr>
    <tr>
        <td>是否按照成功还款结算:</td>
        <td><?php echo $form->field($data, 'is_apr_equal')->dropDownList(LoanPersonChannelRebate::$choose); ?></td>
    </tr>
    <tr>
        <td>返利是否有次数限制:</td>
        <td><?php echo $form->field($data, 'commission_limit')->dropDownList(LoanPersonChannelRebate::$commission_limit); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>