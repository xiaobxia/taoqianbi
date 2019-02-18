<?php

use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;
use backend\models\ThirdPartyShunt;
use backend\models\ThirdPartyShuntType;

$this->shownav('content', 'menu_operate_red_packet_list');
if($type=='edit') {
    $this->showsubmenu('导流分类管理', array(
        array('列表', Url::toRoute('type-list'), 0),
        array('添加新类型', Url::toRoute('type-add'), 0),
        array('编辑', '#', 1),
    ));
} else {
    $this->showsubmenu('导流分类管理', array(
        array('列表', Url::toRoute('type-list'), 0),
        array('添加新类型', Url::toRoute('type-add'), 1),
    ));
}
?>
<style>
    .rowform .txt{width:450px;height:25px;font-size:15px}
</style>

<?php $form = ActiveForm::begin(['id' => 'typeadd-form','options' => ['enctype' => 'multipart/form-data']]); ?>


<table class="tb tb2">


    <tr><td class="td27" colspan="2">类型名称</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'name')->textInput() ;?>
        </td>

    </tr>


    <tr><td class="td27" colspan="2">图片链接</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
           <?php echo Html::fileInput('log_url'); ?>
           <?php if($model->log_url): ?>
                <a href="<?php echo $model->log_url; ?>" target="_blank"><img title="点击查看大图" src="<?php echo $model->log_url; ?>" width="50" height="50"></a>
            <?php endif;?>
        </td>

    </tr>

    <tr><td class="td27" colspan="2">展示状态</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
         <?php echo $form->field($model, 'status')->radioList(['1' => '展示', '0' => '不做展示']) ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>


    <tr><td class="td27" colspan="2">排序</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'sort')->textInput() ;?>
        </td>
        <td class="vtop tips2"><span style="color: red;">数字越大  排序在前</span></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>

</table>
<?php ActiveForm::end(); ?>
