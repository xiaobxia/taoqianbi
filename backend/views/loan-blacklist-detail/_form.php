<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\LoanBlacklistDetail;
use common\helpers\Url;
use yii\helpers\Html;

?>
<style>
    .tb {
        width: auto;
    }
</style>
<?php $form = ActiveForm::begin(['id' => 'blacklist-detail-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">黑名单规则</th></tr>
    <tr >
        <td class="label">规则类型</td>
        <td class="vtop rowform"><?php echo $form->field($data,'type')->dropDownList(LoanBlacklistDetail::$type_list); ?></td>
    </tr>
    <tr>
        <td class="label">规则内容</td>
        <td class="vtop rowform"><?php echo $form->field($data, 'content')->textInput(); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
