<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
use common\models\message\MessageCollectLog;

/**
 * @var backend\components\View $this
 */
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get', 'action' => ['mobile-contacts/message-log'], 'options' => ['style' => 'margin-top:5px;']]); ?>
手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone"
            class="txt" style="width:120px;">&nbsp;
内容：<input type="text" value="<?php echo Yii::$app->getRequest()->get('message', ''); ?>" name="message"
            class="txt" style="width:100px;">&nbsp;
短信通道：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), MessageCollectLog::$type_all, ['prompt' => '所有类型']); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>手机</th>
            <th>内容</th>
            <th>通道</th>
            <th>发送时间</th>
            <!-- <th>操作</th> -->
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo $value['message']; ?></td>
                <td><?php echo MessageCollectLog::$type_all[$value['type']]; ?></td>
                <td><?php echo date('Y-m-d H:i:s', $value['send_time']); ?></td>
                <!-- <td>
                    <a href="#" class="delete" value-id="<?php echo $value['id']; ?>">删除</a>
                </td> -->
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($info)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<script>
    $('.delete').click(function () {
        var id = $(this).attr('value-id');
        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/sensitive-dict/delete']); ?>",
            data: {_csrf: "<?php echo Yii::$app->request->csrfToken ?>", id: id},
            dataType: "json",
            success: function (o) {
                console.log(o)
                if (o.code == 0) {
                    alert('删除成功');
                    location.reload()
                } else {
                    alert(o.message);
                }
            }
        })
    })
</script>
