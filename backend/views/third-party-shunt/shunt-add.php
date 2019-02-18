<?php

use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;
use backend\models\ThirdPartyShunt;
use backend\models\ThirdPartyShuntType;

$this->shownav('content', 'menu_operate_red_packet_list');
if($type=='edit') {
    $this->showsubmenu('分流管理', array(
        array('分流列表', Url::toRoute('shunt-list'), 0),
        array('添加新分流', Url::toRoute('shunt-add'), 0),
        array('编辑', '#', 1),
    ));
} else {
    $this->showsubmenu('分流管理', array(
        array('分流列表', Url::toRoute('shunt-list'), 0),
        array('添加新分流', Url::toRoute('shunt-add'), 1),
    ));
}
?>
<style>
    .rowform .txt{width:450px;height:25px;font-size:15px}
</style>

<?php $form = ActiveForm::begin(['id' => 'shuntadd-form','options' => ['enctype' => 'multipart/form-data']]); ?>


<table class="tb tb2">


    <tr><td class="td27" colspan="2">名称</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo  $form->field($model, 'name')->textInput() ;?>
        </td>
    </tr>


    <tr><td class="td27" colspan="2">logo链接</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
           <?php echo Html::fileInput('log_url'); ?>
           <?php if($model->log_url): ?>
                <a href="<?php echo $model->log_url; ?>" target="_blank"><img title="点击查看大图" src="<?php echo $model->log_url; ?>" width="50" height="50"></a>
            <?php endif;?>
        </td>

    </tr>

    <tr><td class="td27" colspan="2">所属类型</td></tr>
    <tr class="noborder">
        <td class="vtop tips20" '>
          <?php echo $form->field($model, 'type_id')->dropDownList($types, ['prompt'=>'请选择','style'=>'width:120px']) ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>

    <tr><td class="td27" colspan="2">说明</td></tr>
    <tr class="noborder">
        <td class="vtop tips20" '>
          <?php echo  $form->field($model, 'remark')->textInput(['style' => 'width:300px;']); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>

     <tr><td class="td27" colspan="2">地址</td></tr>
    <tr class="noborder">
        <td class="vtop tips20" '>
          <?php echo  $form->field($model, 'url')->textInput(['style' => 'width:300px;']); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>


    <tr><td class="td27" colspan="2">申请人数</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
          <?php echo  $form->field($model, 'number')->textInput() ;?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
    </tr>

    <tr><td class="td27" colspan="2">利率</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
          <?php echo  $form->field($model, 'rate')->textInput() ;?>
        </td>
        <td class="vtop tips2"><span style="color: red;">eg：0.83</span></td>
    </tr>

    <tr><td class="td27" colspan="2">特点</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
          <?php echo  $form->field($model, 'trait')->textInput() ;?>
        </td>
        <td class="vtop tips2"><span style="color: red;"></span></td>
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
