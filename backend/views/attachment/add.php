<?php
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;

/**
 * @var backend\components\View $this
 */
$this->shownav('content', 'menu_attachment_add');
$this->showsubmenu('附件管理', array(
	array('列表', Url::toRoute('attachment/list'), 0),
	array('添加附件', Url::toRoute('attachment/add'), 1),
));

?>

<?php $form = ActiveForm::begin(['id' => 'attachment-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
<table class="tb tb2">
	<tr><td class="td27" colspan="2">所属业务</td></tr>
	<tr class="noborder">
		<td class="vtop rowform"><?php echo Html::dropDownList('type', $defaultType, [
// 	        'xjk_yy' => '极速钱包运营',
			'xjk_yy' => '极速钱包运营',
// 	        'xjk_app' => '极速钱包APP',
			'xjk_app' => '极速钱包APP',
		    'asset' => 'asset -资产部通用目录',
			'loan_record' => 'loan_record -资产管理店铺资料',
			'asset_plat_order' => 'asset_plat_order -资产第三方订单',
		]); ?></td>
		<td class="vtop tips2">不同业务类型对应到文件存储系统的不同目录，请规范选择</td>
	</tr>
	<tr><td class="td27" colspan="2">文件</td></tr>
	<tr class="noborder">
		<td class="vtop rowform"><?php echo Html::fileInput('attach'); ?></td>
		<td class="vtop tips2"><span style="color: red;">(*请在保证质量的情况下,将文件大小控制最小!)</span>只支持gif,jpg,png,xls,xlsx,csv格式的文件，图片最大1M<br/>图片压缩地址:<a href="https://tinypng.com/" target="blank">图片大小压缩</a></td>
	</tr>
	<tr>
		<td colspan="15">
			<input type="submit" value="提交" name="submit_btn" class="btn">
		</td>
	</tr>
</table>
<?php ActiveForm::end(); ?>

