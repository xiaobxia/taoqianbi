<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;
use common\models\Setting;
/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_global_daily_quota');
$this->showsubmenu('设置待抢金额');
?>


<?php $form = ActiveForm::begin(['id' => 'setting-form','method' => "post", 'action' => ['global/daily']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">APP首页白卡每日待抢金额</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($setting_obj, 'svalue')->textInput(); ?></td>
        <td class="vtop tips2">单位：万，默认：2000w ;当前显示值：<?php echo $now_amount ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" value="2" name="type"/>
            <input type="submit" value="提交" name="submit_btn" class="btn" />
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<?php $form = ActiveForm::begin(['id' => 'setting-form','method' => "post", 'action' => ['global/daily']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">APP首页《发薪卡》每日待抢金额</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($setting_golden, 'svalue')->textInput(); ?></td>
        <td class="vtop tips2">单位：万，默认：200w ;当前显示值：<?php echo $golden_amount ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" value="3" name="type"/>
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<?php $form = ActiveForm::begin(['id' => 'setting-form-rate','method' => "post", 'action' => ['global/daily']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">递减放大系数</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($setting_ratio, 'svalue')->textInput(); ?></td>
        <td class="vtop tips2">默认: 1 </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" value="1" name="type" />
            <input type="submit" value="提交" name="submit_btn" class="btn" />
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<?php $form=ActiveForm::begin(['id' => 'setting-form-rate','method'=>"post",'action'=>['global/daily']])?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">支付宝支付维护时间</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            日期：<input type="text" value="<?php echo date("Y-m-d H:i",$zhifubao['bentime'])?>"  name="start_date" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd HH:mm',alwaysUseStartDate:true,readOnly:true})">&nbsp;
            至：<input type="text" value="<?php echo date("Y-m-d H:i",$zhifubao['endtime'])?>"  name="end_date" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd HH:mm',alwaysUseStartDate:true,readOnly:true})">
        </td>
    </tr>
    <td class="vtop tips2">(不在维护时间内的默认都是开启支付)</td>
    <tr>
        <td colspan="15">
            <input type="hidden" value="4" name="type" />
            <input type="submit" value="提交" name="submit_btn" class="btn" />
        </td>
    </tr>
</table>
<?php ActiveForm::end()?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
