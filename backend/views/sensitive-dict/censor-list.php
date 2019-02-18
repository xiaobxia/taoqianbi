<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_sensitive_dict_censor');
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get', 'action' => ['sensitive-dict/censor-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('log_time', ''); ?>" name="log_time" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})" style="width:140px;">
&nbsp;&nbsp;&nbsp;&nbsp;敏感词：<input type="text" value="<?php echo Yii::$app->getRequest()->get('message', ''); ?>" name="message"
           class="txt" style="width:200px;" placeholder="请输入关键字">&nbsp;&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding" style="text-align: center">
        <tr class="header">
            <th style="text-align: center">ID</th>
            <th style="text-align: center">时间</th>
            <th style="text-align: center">问题详情</th>
        </tr>
        <?php foreach ($user_log as $key=>$value): ?>
            <tr class="hover">
                <td><?php echo $key; ?></td>
                <td><?php echo date('Y-m-d H:i:s',$value['log_time']); ?></td>
                <td><?php echo $value['message']?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($user_log)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

