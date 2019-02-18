<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use common\models\mongo\statistics\PickPushedTaskMongo;

$this->shownav('data_analysis_st', 'menu_pick_pushed_userbag_list');
if(empty($tip)){
   $tip = 0;
}
$this->showsubmenu('短信任务列表', array(
    array('列表', Url::toRoute('pick-pushed/set-task-list'), 1),
    array('新增短信任务', Url::toRoute(['pick-pushed/set-task-add','tip'=>$tip]),0),
));
?>

<!--用户包列表-->
<style>.tb2 th{ font-size: 12px;}</style>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>ID</th>
        <th>用户包名称</th>
        <!-- <th>类型</th> -->
        <th>执行时间</th>
        <th>发送用户数</th>
        <th>发送内容</th>
        <th>任务状态</th>
        <th>操作</th>
    </tr>
    <?php foreach ($list as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <!-- <td><?php if ($value['type'] == PickPushedTaskMongo::SEND_TYPE_SMS) { echo '短信';} else { echo '推送';}; ?></td> -->
            <td><?php echo date('Y-m-d H:i:s', $value['begin_time']);?></td>
            <td><?php echo $value['number']?></td>
            <td><?php echo $value['content'];?></td>
            <td><?php if ($value['status'] == PickPushedTaskMongo::TASK_STATUS_UNEXEC){ echo '未执行';} elseif ($value['status'] == PickPushedTaskMongo::TASK_STATUS_DELAY){ echo '待执行';} elseif ($value['status'] == PickPushedTaskMongo::TASK_STATUS_EXEC){ echo '已执行';} ?></td>
            <td>
            	<?php if ($value['status'] == PickPushedTaskMongo::TASK_STATUS_UNEXEC) : ?>
                    <a href="<?php echo Url::toRoute(['pick-pushed/set-task-add', '_id' => (string)$value['_id']]); ?>">编辑</a>
                    <a onclick="confirmRedirect('确定要修改为执行吗？', '<?php echo Url::toRoute(['pick-pushed/set-task-status', '_id' => (string)$value['_id'], 'status' => PickPushedTaskMongo::TASK_STATUS_DELAY]); ?>')" href="javascript:void(0);">执行</a>
                    <a onclick="confirmRedirect('确定要删除吗？', '<?php echo Url::toRoute(['pick-pushed/set-task-del', '_id' => (string)$value['_id']]); ?>')" href="javascript:void(0);">删除</a>
                <?php elseif ($value['status'] == PickPushedTaskMongo::TASK_STATUS_DELAY) :?>
                	<a onclick="confirmRedirect('确定要修改为暂停吗？', '<?php echo Url::toRoute(['pick-pushed/set-task-status', '_id' => (string)$value['_id'], 'status' => PickPushedTaskMongo::TASK_STATUS_UNEXEC]); ?>')" href="javascript:void(0);">暂停</a>
                <?php elseif ($value['status'] == PickPushedTaskMongo::TASK_STATUS_EXEC) :?>
                   	<a href="<?php echo Url::toRoute(['pick-pushed/user-bag-log-list', 'code' => $value['code']]); ?>">查看报告</a>
               	<?php endif;?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
