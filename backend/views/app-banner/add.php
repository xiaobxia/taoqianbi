<?php

use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;
use common\models\AppBanner;
use common\models\LoanPerson;

$this->shownav('content', 'menu_operate_red_packet_list');
if($type=='edit') {
    $this->showsubmenu('App banner管理', array(
        array('banner列表', Url::toRoute('list'), 0),
        array('添加新banner', Url::toRoute('add'), 0),
        array('编辑banner', '#', 1),
    ));
} else {
    $this->showsubmenu('App banner管理', array(
        array('banner列表', Url::toRoute('list'), 0),
        array('添加新banner', Url::toRoute('add'), 1),
    ));
}
?>
<style>
    .rowform .txt{width:450px;height:25px;font-size:15px}
</style>
<script>
    $(function(){
        $("[name='AppBanner[type]']").change(function(e){
            e.preventDefault();
            if($(this).val() == "2")
            {
                $("#app_type_area_tr1").show();
                $("#app_type_area_tr2").show();
            }
            else
            {
                $("#app_type_area_tr1").hide();
                $("#app_type_area_tr2").hide();

            }
        });

    })

</script>
<?php $form = ActiveForm::begin(['id' => 'banner-form','options' => ['enctype' => 'multipart/form-data']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">图片链接</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            <?php echo Html::fileInput('file'); ?>
            <?php if($model->image_url): ?>
                <a href="<?php echo $model->image_url; ?>" target="_blank"><img title="点击查看大图" src="<?php echo $model->image_url; ?>" width="50" height="50"></a>
            <?php endif;?>
        </td>
        <td class="vtop tips2">
            <span style="color: red;">(*必填项!)</span>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">类型</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'type')->radioList([AppBanner::BANNER_TYPE_NORMAL=>'普通',AppBanner::BANNER_TYPE_URL=>'链接',AppBanner::BANNER_TYPE_SKIP=>'app跳转']); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;">(*Banner类型，普通为仅图片展示，链接为图片附带跳转链接!)</span></td>
    </tr>


        <tr <?php if($model->type != 3):?>style="display: none"<?php endif;?> id="app_type_area_tr1"><td class="td27" colspan="2" >类型</td></tr>
        <tr class="noborder" <?php if($model->type != 3):?>style="display: none"<?php endif;?> id="app_type_area_tr2">
            <td>
            <?php if(!isset($model->app_type)){$model->app_type = 0;};?>
            <?php echo $form->field($model, 'app_type')->dropDownList([
                AppBanner::APP_TYPE_REPAYMENT=>'还款页(登录用户并且有未还款才会显示)',
            ])
            ?>
            </td>
            <td>
                <span style="color: red;">(*Banner类型为App跳转)</span>
            </td>
        </tr>

    <tr><td class="td27" colspan="2">是否悬浮</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'is_float')->radioList([AppBanner::BANNER_TYPE_LAND=>'否',AppBanner::BANNER_TYPE_FLOAT=>'是']); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;">(悬浮banner将以悬浮的形式在首页显示!)</span></td>
    </tr>


    <tr><td class="td27" colspan="2">版本选择</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'source_id')->dropDownList(LoanPerson::$current_loan_source); ?>
        </td>
        <td class="vtop tips2"></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">要显示的用户</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'loan_search_public_list_id')->dropDownList($public); ?></td>
    </tr>
    <tr><td class="td27" colspan="2">外部链接</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            <?php echo $form->field($model, 'link_url')->textInput(); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;">(*类型为链接时为必填项!)</span></td>
    </tr>

    <tr><td class="td27" colspan="2">跳转值</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php
                $sub_type = $model->sub_type ? $model->sub_type : 0;
            ?>
            <?php echo $form->field($model, 'sub_type')->textInput(['value'=>$sub_type]); ?>
        </td>
        <td class="vtop tips2"><span style="color: red;">(*类型为链接时为必填项! app内跳转值，待完善约定!)</span></td>
    </tr>
    <tr><td class="td27" colspan="2">状态</td></tr>
    <tr class="noborder">
        <td class="vtop tips2">
            <?php echo $form->field($model, 'status')->radioList([\common\models\AppBanner::BANNER_STATUS_USE=>'启用',\common\models\AppBanner::BANNER_STATUS_NO_USE=>'不启用']); ?>
        </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
