<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_global_card_list');
$this->showsubmenu('添加黑名单', array(
    array('列表', Url::toRoute('global/bank-card-black-list'), 0),
    array('添加', Url::toRoute('global/bank-card-black-add'), 1)
));
?>


<?php $form = ActiveForm::begin(['id' => 'bank-card-black-form','method' => "post"]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">银行</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            <select name="card">
                <?php foreach ($cards as $key=>$card): ?>
                <option value="<?php echo $key; ?>" <?php if(array_key_exists($key,$black_info)): ?> selected <?php endif ?> ><?php echo $card; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
    <tr><td class="td27" colspan="2">维护时间</td></tr>
    <tr>
    <td class="vtop">
        <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
        <input type="text" value="<?php echo isset($black_info['begin_time']) ? date('Y-m-d H:i:s', $black_info['begin_time']) : ''; ?>" name="begin_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" readonly="">
        至
        <input type="text" value="<?php echo isset($black_info['end_time']) ? date('Y-m-d H:i:s', $black_info['end_time']) : ''; ?>" name="end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" readonly="">
    </td>
    </tr>
    <tr>
    <tr><td class="td27" colspan="2">客户端提示信息（不超过20字，请谨慎填写）</td></tr>
    <td class="vtop">
        <input id="remark" name="remark" value="<?php echo isset($black_info['remark']) ? $black_info['remark'] : '银行系统维护中，请尝试其他还款方式'; ?>" style="width:400px" maxlength="20">
    </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
