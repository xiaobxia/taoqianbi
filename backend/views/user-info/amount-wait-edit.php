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
        <tr><th class="partition" colspan="15">用户额度审核</th></tr>
        <tr>
            <td class="label" id="pocket_amount">总额度：</td>
            <td width="120px"><?php echo $form->field($user_credit_total, 'amount')->label(); ?></td>
            <td width="70px"><?php echo Html::dropDownList('pm_algorithm', 1, [
                    '1' => '增加',
                    '2' => '减少',
                ]); ?></td>
            <td width="100px"><input type="text" name="operate_amount" value="" size="10"></td>
            <td class="vtop tips2">（可不填，同下）</td>
        </tr>

        <tr>
            <td></td>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>