<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;

$this->shownav('data_analysis_st', 'menu_pick_pushed_userbag_list');
if(empty($tip)){
   $tip = 0;
}
$this->showsubmenu('执行报告列表', array(
    array('列表', Url::toRoute('pick-pushed/user-bag-log-list'), 1),
));
?>

<!--用户包列表-->
<style>.tb2 th{ font-size: 12px;}</style>

<table class="tb tb2 fixpadding">
    <tr class="header">
    	<th>用户包标识</th>
        <th>用户包名称</th>
        <th>用户人数</th>
        <th>发送人数</th>
        <th>发送成功数</th>
        <th>发送失败数</th>
        <th>执行时间</th>
        <th>发送内容</th>
    </tr>
    <?php foreach ($list as $value): ?>
        <tr class="hover">
            <td><?php echo $value['code']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <td><?php echo $value['user_num']; ?></td>
            <td><?php echo $value['send_num']; ?></td>
            <td><?php echo $value['success_num']; ?></td>
            <td><?php echo $value['fail_num']; ?></td>
            <td><?php echo $value['created_at']; ?></td>
            <td><?php echo $value['content']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
