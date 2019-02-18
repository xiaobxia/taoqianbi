<?php

use backend\components\widgets\ActiveForm;
use common\models\UserCouponPrizes;
use yii\helpers\Html;
use common\models\UserLoanOrderForzenRecord;
?>

<?php $form = ActiveForm::begin(["id" => "red-packet-user-form", "method" => 'post']); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">冻结状态</td></tr>
    <tr class="noborder">
        <td class="vtop rowform" ><?php echo Html::dropDownList('status',"1",$user_forzen_record::$status_forzen_list) ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">备注说明</td>
    </tr>
    <tr class="noborder" >
        <td class="vtop rowform"><?php echo Html::textarea('remark',$user_forzen_record->remark); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="恢复冻结" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<?php
echo $this->render('/public/repayment-common-view', array(
    'common'=>$common,
));
?>