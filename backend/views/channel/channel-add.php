<?php

use common\helpers\Url;
use backend\components\widgets\ActiveForm;

$this->shownav('system', 'menu_channel_list');
if($type=='edit') {
    $this->showsubmenu('渠道管理', array(
        array('列表', Url::toRoute('channel-list'), 0),
        array('添加新渠道', Url::toRoute('channel-add'), 0),
        array('编辑', '#', 1),
    ));
} else {
    $this->showsubmenu('渠道管理', array(
        array('列表', Url::toRoute('channel-list'), 0),
        array('添加新渠道', Url::toRoute('channel-add'), 1),
    ));
}
?>

<?php $form = ActiveForm::begin(['id' => 'typeadd-form','options' => ['enctype' => 'multipart/form-data']]); ?>


<table class="tb tb2">

    <tr><td class="td27" colspan="2">渠道中文名称</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'name')->textInput() ;?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">渠道英文名称</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'appMarket')->textInput() ;?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">渠道负责人</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'operator_name')->textInput() ;?>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">状态</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'status')->radioList(['1' => '启用', '0' => '停用']) ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>

    <tr><td class="td27" colspan="2">借款显示</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'loan_show')->radioList(['1' => '显示', '0' => '不显示']) ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>

    <!--    <tr><td class="td27" colspan="2">扣量折扣</td></tr>-->
    <!--    <tr class="noborder">-->
    <!--        <td class="vtop tips2">-->
    <!--            --><?php //echo $form->field($model, 'pv_rate')->dropDownList($pv_rate, ['prompt'=>'请选择','style'=>'width:120px']) ?>
    <!--        </td>-->
    <!--        <td class="vtop tips2"><span style="color: red;"></span></td>-->
    <!--    </tr>-->

    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>

</table>
<?php ActiveForm::end(); ?>
