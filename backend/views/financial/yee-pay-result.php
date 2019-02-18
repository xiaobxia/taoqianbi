<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['id' => 'loan-project-form']); ?>
<table class="tb tb2 fixpadding">
    <tr class="noborder">
        <td class="label">
        <label>订单号</label>
        <div class="form-group">
            <?php echo Html::dropDownList('fund', $fund, [1=>APP_NAMES]); ?>
            <?php echo Html::dropDownList('type', $type, [1=>'代扣查询', 2=>'放款查询']); ?>
            <input value="<?php echo $order_id?>" name="order_id" class="input" style="width:400px"/>
        <div class="help-block"></div>
        </div>
        <?php if($order_id): ?>
            <?php if($data): ?>
                <?php if($type == 1): ?>
                <div>
                    状态：<?php echo isset($data['status']) ? $map[$data['status']] : '--' ?>
                </div>
                <div>金额：<?php echo isset($data['money']) ? $data['money']/100:'--'?> 元</div>
                <div>错误码：<?php echo $data['error_code']?></div>
                <div>错误描述：<?php echo $data['error_msg']?></div>
                <div>订单号：<?php echo $data['pay_order_no']?></div>
                <div>代扣通道：<?php echo $data['pay_channel']?></div>
                    <pre>
                        <?php print_r($ret);?>
                    </pre>
                <?php elseif($type == 2): ?>
                    <pre>
                        <?php print_r($ret);?>
                    </pre>
                <?php endif;?>
            <?php else:?>
                <div>无订单记录或者查询失败</div>
            <?php endif;?>
        <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="执行" name="ok" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
