<?php
/**
 *

 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<style>.tb2 th{ font-size: 12px;}</style>

        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>规则ID</th>
                <th>规则名称</th>
                <th>规则路径</th>
                <th>描述</th>
                <th>优先级</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>修改时间</th>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value->id; ?></td>
                    <td><?php echo $value->name; ?></td>
                    <td><?php echo strtolower($value->url); ?></td>
                    <th><?php echo $value->description; ?></th>
                    <th><?php echo $value->order; ?></th>
                    <th><?php echo $value->state; ?></th>
                    <th><?php echo $value->create_time; ?></th>
                    <th><?php echo $value->update_time; ?></th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
