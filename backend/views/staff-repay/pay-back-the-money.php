<?php

use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
$this->shownav('loan', 'menu_ygb_money');
$this->showsubmenu('零钱包贷后管理', array(
    array('还款凭证图片列表', Url::toRoute('staff-repay/pay-back-the-money'), 1),
));
?>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => 'get','options' => ['style' => 'margin-bottom:5px;']]); ?>
    订单id：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?><?php echo isset($search['user_id']) ? trim($search['user_id']) : ''; ?>" name="user_id" class="txt">&nbsp;
     <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>订单号</th>
        <th>凭证名称</th>
        <th>凭证内容</th>
        <th>图片链接（图片无法打开或大小问题时可以复制链接去浏览器查看）</th>
        <th>上传时间</th>
    </tr>
    <?php foreach ($data as $value): ?>
        <tr class="hover">
            <th><?php echo $value['user_id']; ?></th>
            <td><?php echo isset($value['url']) ? '还款凭证图片' : ''; ?></td>
            <td><img src="<?php echo $value['url']; ?>" width="100"/></td>
            <td><a href="<?php echo $value['url']; ?>" target="_blank"><?php echo $value['url']; ?></a></td>
            <td><?php echo date('Y-m-d H:i:s', $value['created_at']); ?></td>
        </tr>
    <?php endforeach; ?>

</table>
<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
