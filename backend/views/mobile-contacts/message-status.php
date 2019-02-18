<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
use common\models\PushSms;

/**
 * @var backend\components\View $this
 */
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get', 'action' => ['mobile-contacts/message-status'], 'options' => ['style' => 'margin-top:5px;']]); ?>
手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone"
            class="txt" style="width:120px;">&nbsp;
内容：<input type="text" value="<?php echo Yii::$app->getRequest()->get('content', ''); ?>" name="content"
            class="txt" style="width:100px;">&nbsp;
发送状态<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), PushSms::$status, ['prompt' => '所有类型']); ?>&nbsp;
到达状态：<input type="text" value="<?php echo Yii::$app->getRequest()->get('sms_status', ''); ?>" name="sms_status" class="txt" style="width:100px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>

<?php $form = ActiveForm::begin(['id' => 'search_form_post', 'action' => ['mobile-contacts/message-status'], 'options' => ['style' => 'margin-top:5px;', 'enctype' => 'multipart/form-data']]); ?>

<input type="file" name="file" id="file" >
<input type="submit" name="search_submit" value="提交csv文件" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>手机</th>
            <th>内容</th>
            <th>发送状态</th>
            <th>到达状态</th>
            <th>发送时间</th>
            <!-- <th>操作</th> -->
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo $value['content']; ?></td>
                <td><?php echo PushSms::$status[$value['status']]; ?></td>
                <td><?php echo $value['sms_status']; ?></td>
                <td><?php echo date('Y-m-d H:i:s', $value['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($info)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
