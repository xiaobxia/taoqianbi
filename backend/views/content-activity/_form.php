<?php
	use backend\components\widgets\ActiveForm;
    use common\models\ContentActivity;
    use common\helpers\Url;
?>

<script type="text/javascript" xmlns="http://www.w3.org/1999/html">
    var UEDITOR_HOME_URL = '<?php echo Url::toStatic('/js/ueditor/'); ?>'; //一定要用这句话，否则你需要去ueditor.config.js修改路径的配置信息
</script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.config.js'); ?>?v=2015060801"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.all.js'); ?>?v=2015060801"></script>
<script type="text/javascript">
    var ue = UE.getEditor('contentactivity-remark');
</script>

<?php $form = ActiveForm::begin(["id"=>"content-activity-form"]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">标题</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'title')->textInput(); ?></td>
    </tr>
    <tr><td class="td27" colspan="2">附标题</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'subtitle')->textInput(); ?></td>
    </tr>
    <tr><td class="td27" colspan="2">内容描述</td></tr>
    <tr class="noborder">
        <td class="msg_push_td_width_right"><?php echo $form->field($model, 'content')->textarea(); ?></td>
    </tr>
    <tr><td class="td27" colspan="2">添加链接</td></tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'link')->textInput(); ?></td>
    </tr>
    <tr><td class="td27" colspan="2">上传banner地址</td></tr>
    <tr class="noborder">
        <td class="msg_push_td_width_right"><?php echo $form->field($model, 'banner')->textarea(); ?></td>
        <td class="tips">
            <a style="margin-left:10px;color:#1B9BDB;" target="_blank" href="<?php echo Url::toRoute(['attachment/add']) ?>">上传附件</a>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">是否置顶</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform" >
            <?php if( $model->is_up == 1){ ?>
            <input type="checkbox" name="sel_up" value="1" checked="checked" /> 置顶
            <?php }else{ ?>
            <input type="checkbox" name="sel_up" value="1" /> 置顶
            <?php } ?>
        </td>
    </tr>

    <tr>
        <td class="td27" colspan="2">使用场景</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform" ><?php echo $form->field($model, 'use_case')->dropDownList(ContentActivity::$use_case); ?></td>
    </tr>

    <tr>
        <td class="td27" colspan="2">是否同步APP弹窗</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform" >
            <input type="checkbox" name="sel_pop[]" value="1" /> 同步首页弹窗
            <input type="checkbox" name="sel_pop[]" value="2" /> 同步启动弹窗
        </td>
    </tr>

    <tr><td class="td27" colspan="2">上传首页弹窗图片</td></tr>
    <tr class="noborder">
        <td class="msg_push_td_width_right"><textarea name="pop_img_1" rows="3" cols="60"></textarea></td>
        <td class="tips">
            <a style="margin-left:10px;color:#1B9BDB;" target="_blank" href="<?php echo Url::toRoute(['attachment/add']) ?>">上传附件</a>
        </td>
    </tr>

    <tr><td class="td27" colspan="2">上传启动弹窗图片</td></tr>
    <tr class="noborder">
        <td class="msg_push_td_width_right"><textarea name="pop_img_2" rows="3" cols="60"></textarea></td>
        <td class="tips">
            <a style="margin-left:10px;color:#1B9BDB;" target="_blank" href="<?php echo Url::toRoute(['attachment/add']) ?>">上传附件</a>
        </td>
    </tr>

    <tr>
        <td class="td27" colspan="2">活动内容</td>
    </tr>
    <tr class="noborder">
        <td colspan="2">
            <div style="width:780px;height:400px;margin:5px auto 40px 0;">
                <?php echo $form->field($model, 'remark')->textarea(['style' => 'width:780px;height:295px;']); ?>
            </div>
            <div class="help-block"></div>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">当前状态</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform" ><?php echo $form->field($model, 'status')->dropDownList(ContentActivity::$status); ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">初始次数</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'count')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">开始时间</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'start_time')->textInput(array("onfocus"=>"WdatePicker({startDate:'%y/%M/%d 00:00:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true});")) ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">结束时间</td>
    </tr>
    <tr class="noborder">
        <td class="vtop rowform"><?php echo $form->field($model, 'end_time')->textInput(array("onfocus"=>"WdatePicker({startDate:'%y/%M/%d 23:59:59',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true});")) ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
