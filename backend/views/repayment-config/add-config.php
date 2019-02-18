<?php

use common\helpers\Url;
use yii\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\RepaymentConfig;

$this->shownav('content', 'menu_operate_repayment_info');
$this->showsubmenu('还款配置', array(
    array('还款比例列表', Url::toRoute('repayment-config/list'), 0),
    array( $title , Url::toRoute('repayment-config/add'), 1),
));
?>

<?php $form = ActiveForm::begin(["id" => "add-quan-user-form", "method" => 'post']); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">还款比例 * </td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'percent')->textInput(); ?></td>
    </tr>
    <tr id='coupon_id_tr'><td class="td27" colspan="2">最小还款限额 *</td></tr>
    <tr class="noborder" id='coupon_id_td'>
        <td class="vtop rowform" ><?php echo $form->field($model, 'max')->textInput();?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
