<?php

use common\helpers\Url;
use backend\components\widgets\ActiveForm;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_bankcard_list');
$this->showsubmenu('银行卡添加', array(
	array('银行卡管理列表', Url::toRoute('bank-card/card-list'), 0),
	array('银行卡添加', Url::toRoute('bank-card/card-add'), 1),
));

?>

<?php $form = ActiveForm::begin(['id' => 'card-form','method' => "post"]); ?>
	<table class="tb tb2">
		<tr><td class="td27" colspan="2">用户ID</td></tr>
		<tr class="noborder">
			<td class="vtop rowform">
				<?php echo $form->field($model, 'user_id')->textInput(); ?>
			</td>
		</tr>
		<tr><td class="td27" colspan="2">开卡银行（银行名称）</td></tr>
		<tr class="noborder">
			<td class="vtop rowform">
				<?php echo $form->field($model, 'bank_id')->dropDownList($card_list); ?>
			</td>
		</tr>
		<tr><td class="td27" colspan="2">银行卡类型</td></tr>
		<tr class="noborder">
			<td class="vtop rowform">
				<?php echo $form->field($model, 'type')->dropDownList(\common\models\CardInfo::$type); ?>
			</td>
		</tr>
		<tr><td class="td27" colspan="2">银行卡号</td></tr>
		<tr class="noborder">
			<td class="vtop rowform">
				<?php echo $form->field($model, 'card_no')->textInput(); ?>
			</td>
		</tr>
		<tr><td class="td27" colspan="2">预留手机号</td></tr>
		<tr class="noborder">
			<td class="vtop rowform">
				<?php echo $form->field($model, 'phone')->textInput(); ?>
			</td>
		</tr>
		<tr>
			<td colspan="15">
				<input type="submit" value="提交" name="submit_btn" class="btn">
			</td>
		</tr>
	</table>
<?php ActiveForm::end(); ?>
