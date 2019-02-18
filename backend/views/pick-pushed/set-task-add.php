<?php
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
?>
<style>
    td.label {
        width: 170px;
        text-align: right;
        font-weight: 700;
    }
    .txt{ width: 100px;}

    .tb2 .txt, .tb2 .txtnobd {
        width: 200px;
        margin-right: 10px;
    }
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>

<?php $form = ActiveForm::begin(['method' => 'post'])?>
<input type="hidden" name="type" value="1"></input>
<input type="hidden" name="id" value="<?php echo (string)$info['_id'];?>"></input>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">新增短信任务</th></tr>
    <tr>
        <td class="label"><font color="red">*</font>选择用户包：</td>
        <td >
        	<select name="code">
        		<?php
        		      foreach ($bag_list as $item) {
        		          echo '<option value=' . $item['code'] . '>' . $item['name'] . '</option>';
        		      }
        		?>
        	</select>
        </td>
    </tr>
    <tr>
        <td class="label"><font color="red">*</font>发送时间：</td>
        <td >
        	<input type="text" value="<?php echo isset($info['begin_time']) ? date('Y-m-d H:i:s', $info['begin_time']) : ''; ?>" name="begin_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
        </td>
    </tr>
        <tr>
        <td class="label"><font color="red">*</font>发送内容：</td>
        <td >
        	<?php echo Html::textarea('content', isset($info['content']) ? $info['content'] : '', ['style' => 'height:100px;width:500px;']);?>
        	注：内容只发纯内容，不要加“【<?php echo APP_NAMES;?>】”，“回T退订”这样的文字，短信通道会自动加上的
        </td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php $form = ActiveForm::end();?>

<script>
   $('#uid').parent().css({display : "none"});
   $('#open_id').parent().css({display : "none"});
</script>