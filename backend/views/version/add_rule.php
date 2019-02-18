<?php

use common\models\Version;
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;
use common\models\AppBanner;
use common\models\LoanPerson;

$this->shownav('content', 'menu_operate_red_packet_list');
if($type=='edit') {
    $this->showsubmenu('app升级管理', array(
        array('配置升级列表', Url::toRoute('list'), 0),
        array('添加新升级配置', Url::toRoute('add'), 0),
        array('规则配置列表', Url::toRoute('list-rule'), 1),
        array('添加规则配置', Url::toRoute('add-rule'), 0),
    ));
} else {
    $this->showsubmenu('App 升级配置管理', array(
        array('规则配置列表', Url::toRoute('list-rule'), 0),
        array('添加新规则配置', Url::toRoute('add-rule'), 1),
    ));
}
?>
<style>
    .rowform .txt{width:450px;height:25px;font-size:15px}
</style>
<?php $form = ActiveForm::begin(['id' => 'banner-form','options' => ['enctype' => 'multipart/form-data']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">是否启用</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php if(!isset($model->status)){$model->status = 1;}?>
            <?php echo $form->field($model, 'status')->radioList([
                AppBanner::BANNER_TYPE_URL=>'启用',
                AppBanner::BANNER_TYPE_NORMAL=>'不启用',
            ]); ?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">规则名称</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'name'); ?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">appMarket</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'remark'); ?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">APP包名</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'pkgname'); ?>
        </td>
    </tr>

    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>
    $(function () {

    })
</script>