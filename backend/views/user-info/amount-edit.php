<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditLog;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">修改用户额度</th></tr>
        <tr>
            <td class="label" id="pocket_amount">总额度：</td>
            <td width="120px"><?php echo $form->field($user_credit_total, 'amount')->textInput(); ?></td>
            <td width="70px"><?php echo Html::dropDownList('pm_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); ?></td>
            <td width="100px"><input type="text" name="operate_amount" value="" size="10"></td>
            <td class="vtop tips2">（可不填，同下）</td>
        </tr>
        <tr>
            <td class="label" id="current_pocket_apr">零钱贷利率(万分之)：</td>
            <td ><?php echo $form->field($user_credit_total, 'pocket_apr')->textInput(); ?></td>
            <td width="70px"><?php echo Html::dropDownList('pa_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); ?></td>
            <td><input type="text" name="operate_pocket_apr" value="" size="10"></td>
        </tr>

        <tr>
            <td class="label" id="current_counter_fee_rate">手续费率：</td>
            <td ><?php echo $form->field($user_credit_total, 'counter_fee_rate')->label(); ?></td>
            <td width="70px"><?php echo Html::dropDownList('fee_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); ?></td>
            <td><input type="text" name="operate_counter_fee_rate" value="" size="10"></td>
        </tr>
        <!--<tr>
            <td class="td24">房租贷利率(百分之)：</td>
            <td ><?php /*echo $form->field($user_credit_total, 'house_apr')->textInput(); */?></td>
            <td width="70px"><?php /*echo Html::dropDownList('pb_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); */?></td>
            <td><input type="text" name="operate_house_apr" value="" size="10"></td>
        </tr>
        <tr>
            <td class="td24">分期商城利率(百分之)：</td>
            <td ><?php /*echo $form->field($user_credit_total, 'installment_apr')->textInput(); */?></td>
            <td width="70px"><?php /*echo Html::dropDownList('pc_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); */?></td>
            <td><input type="text" name="operate_installment_apr" value="" size="10"></td>
        </tr>-->
        <tr>
            <td></td>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>