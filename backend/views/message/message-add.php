<?php
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

use common\models\message\Message;
use common\models\loan\LoanCollectionOrder;
/**
 * @var backend\components\View $this
 */
?>

<!-- 富文本编辑器 注：360浏览器无法显示编辑器时，尝试切换模式（如兼容模式）-->
<script type="text/javascript" xmlns="http://www.w3.org/1999/html">
    var UEDITOR_HOME_URL = '<?php echo Url::toStatic('/js/ueditor/'); ?>'; //一定要用这句话，否则你需要去ueditor.config.js修改路径的配置信息
</script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.config.js'); ?>?v=2015060801"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/ueditor/ueditor.all.js'); ?>?v=2015060801"></script>
<script type="text/javascript">
    var ue = UE.getEditor('message_body');
</script>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">添加通知消息</th></tr>
    <tr>
        <td class="td24">消息类型：</td>
        <td>
            <?php //echo Html::dropDownList('outside', Yii::$app->getRequest()->get('outside', ''), LoanCollection::outside(),array('prompt' => '-所有机构-')); ?>
            <select id="message_type" class="messageChange" name="message_type" style="float:left;">
                <option value="0">--请选择--</option>
                <?php foreach (Message::$menu as $key => $value) : ?>
                    <option value="<?php echo $key; ?>"><?php echo $value ?></option>
                <?php endforeach; ?>
            </select>
            <p class="type_warning" style="line-height:20px;color: red;display: none;">&nbsp;&nbsp;&nbsp;请选择消息类型</p>
        </td>
    </tr>
    <tr>
        <td class="td24">消息标题：</td>
        <td>
            <?php echo Html::textInput('message_title','', ['class' => 'messageChange', 'id' => 'message_title', 'style' => 'width:30   0px;float:left;']); ?>
            <p class="title_warning" style="line-height:20px;color: red;display: none;">&nbsp;&nbsp;&nbsp;请输入消息标题</p>
        </td>
    </tr>
    <tr>
        <td class="td24">消息内容：</td>
        <td>
            <div style="width:1000;height:500px;margin:5px auto 75px 0;">
            <?php echo Html::Textarea('message_body','', ['class' => 'messageChange', 'id' => 'message_body', 'style' => 'width:90%;height:450px;']); ?>
            <?php //echo $form->field($model, 'message_body')->textarea(['class' => 'messageChange', 'id' => 'message_body', 'style' => 'width:780px;height:295px;']); ?>
            <p class="text_warning" style="line-height:20px;color: red;display: none;">&nbsp;&nbsp;&nbsp;请输入消息内容</p>
            </div>
            <div class="help-block"></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <input type="button" value="提交" name="submit_btn" class="btn" id="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script type="text/javascript">

    $('.messageChange').blur(function(){
        if ($('#message_type').val() != 0) {
            $('.type_warning').css('display', 'none');
        }
        if ($.trim($('#message_title').val()) != '') {
            $('.title_warning').css('display', 'none');
        }

    })
    $(function() {
        $("#btn").click(function() {
            if ($('#message_type').val() == 0) {
                $('.type_warning').css('display', 'block');
                return;
            }
            if ($.trim($('#message_title').val()) == '') {
                $('.title_warning').css('display', 'block');
                return;
            }
            $('form').submit();
        });
    });
</script>
