<?php
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;

use common\models\message\Message;
?>
<style>
    .state-column {
        width: 8px;
        padding-left: 4px;
        padding-right: 4px;
    }
    .read {
        color: red;
    }
    .state-column .state-unread {
        font-size: 16px;
        color: #3325ff;
    }
    .already td {
        color: #CCC;
    }
    .already td a{
        color: #CCC;
    }
</style>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th ></th>
            <th >标题内容</th>
            <th >提交时间</th>
            <th >类型</th>
            <th >发布者</th>
            <th>操作</th>
        </tr>
        <?php foreach ($message_list as $value): ?>
        <tr <?php echo $value['read_status']==1 ? 'class="already"' : '';?>>
            <td class="state-column">
                <span class="state-unread ng-scope" ng-if="item.Status*1===0"><?php echo $value['read_status']!=1 ? '●' : '';?></span>
            </td>
            <td><?php echo $value['message_title']; ?></td>
            <td><?php echo date('Y-m-d H:i:s', $value['created_at']); ?></td>
            <td><?php echo Message::$menu[$value['message_type']]; ?></td>
            <td><?php echo $value['sender_name']; ?></td>
            <td>
                <a href="<?php echo Url::toRoute(['message/message-view', 'id' => $value['id']]); ?>">查看</a>
                <a href="<?php echo Url::toRoute(['message/message-edit', 'id' => $value['id']]); ?>">编辑</a>
                <a onclick="confirmRedirect('确定要删除吗？', '<?php echo Url::toRoute(['message/message-del', 'id' => $value['id']]); ?>')" href="javascript:void(0);">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($message_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
