<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
$this->showsubmenu('资方列表', array(
    ['资方列表', Url::toRoute('building/capital-list'), \Yii::$app->requestedRoute == 'building/capital-list' ? 1 : 0],
    ['编辑资方', Url::toRoute('building/edit-capital'), \Yii::$app->requestedRoute == 'building/edit-capital' ? 1 : 0]
));
?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>资方名字</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($capitals as $capital): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $capital['id']; ?></td>
                    <td><?php echo $capital['name']; ?></td>
                    <td><?php echo date('Y-m-d',$capital['created_at']); ?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['building/del-capital', 'id' => $capital['id']]); ?>" onclick="return confirm('确认要删除吗？');">删除</a>
                        <a href="<?php echo Url::toRoute(['building/edit-capital', 'id' => $capital['id']]); ?>">编辑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($capitals)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>