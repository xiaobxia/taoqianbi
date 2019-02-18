<?php

use common\models\Version;
use yii\helpers\ArrayHelper;
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;
use common\models\AppBanner;
use common\models\LoanPerson;

$this->shownav('content', 'menu_operate_red_packet_list');
if($type=='edit') {
    $this->showsubmenu('app升级管理', array(
        array('升级配置列表', Url::toRoute('list'), 0),
        array('添加新升级配置', Url::toRoute('add'), 0),
        array('编辑升级配置', '#', 1),
    ));
} else {
    $this->showsubmenu('App 升级配置管理', array(
        array('升级配置列表', Url::toRoute('list'), 0),
        array('添加新升级配置', Url::toRoute('add'), 1),
    ));
}
?>
<style>
    .rowform .txt{width:450px;height:25px;font-size:15px}
</style>
<?php $form = ActiveForm::begin(['id' => 'banner-form','options' => ['enctype' => 'multipart/form-data']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">类型</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php if(!isset($model->type)){$model->type = 1;}?>
            <?php echo $form->field($model, 'type')->radioList([
                AppBanner::BANNER_TYPE_URL=>'启用',
                AppBanner::BANNER_TYPE_NORMAL=>'不启用',
            ]); ?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">版本选择</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php if(!isset($model->versions)){echo $model->versions;}?>
            <?php echo $form->field($model, 'versions')->dropDownList([
                ArrayHelper::map($version, 'id', 'name')
            ]);?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">是否提示升级</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php if(!isset($model->has_upgrade)){$model->has_upgrade = 1;}?>
            <?php echo $form->field($model, 'has_upgrade')->radioList([
                Version::HAS_UPGRADE_SUCCESS=>'提示升级',
                Version::HAS_UPGRADE_FALSE=>'不提示升级',
            ]);?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">是否强制升级</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php if(!isset($model->is_force_upgrade)){$model->is_force_upgrade = 1;}?>
            <?php echo $form->field($model, 'is_force_upgrade')->radioList([
                Version::FORCE_UPGRADE_SUCCESS=>'强制升级',
                Version::FORCE_UPGRADE_FALSE=>'不强制升级',
            ]);?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">ios版本号</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'new_ios_version'); ?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">android版本号</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'new_version'); ?>
        </td>
    </tr>
<!--    <tr><td class="td27" colspan="2">ios包地址</td></tr>-->
<!--    <tr class="noborder">-->
<!--        <td class="vtop tips2">-->
<!--            --><?php //echo $form->field($model, 'ios_url'); ?>
<!--        </td>-->
<!--    </tr>-->
    <tr><td class="td27" colspan="2">android包地址</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'ard_url'); ?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">android包的大小</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'ard_size'); ?>
        </td>
    </tr>
    <tr><td class="td27" colspan="2">特性描述</td></tr>
    <tr class="noborder">
        <td colspan="2">
            <div style="width:780px;height:400px;margin:5px auto 40px 0;">
                <?php echo $form->field($model, 'new_features')->textarea(['style' => 'width:780px;height:295px;']); ?>
            </div>
            <div class="help-block"></div>
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