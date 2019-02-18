<?php
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;

$this->shownav('service', 'user_feedback_list');
$this->showsubmenu('反馈列表');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>用户ID</th>
        <th>反馈内容</th>
        <th>反馈时间</th>
    </tr>
    <?php foreach($data as $item){?>
    <tr>
        <th><?php echo $item['user_id'];?></th>
        <th><?php echo $item['content'];?></th>
        <th><?php echo date('Y-m-d H:i:s', $item['created_at']);?></th>
    </tr>
    <?php }?>
</table>
<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
