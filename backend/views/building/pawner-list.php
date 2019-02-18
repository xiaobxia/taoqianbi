<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\models\Capital;
$this->showsubmenu('抵押人列表', array(
    ['抵押人列表', Url::toRoute('building/pawner-list'), \Yii::$app->requestedRoute == 'building/pawner-list' ? 1 : 0],
    ['编辑抵押人', Url::toRoute('building/edit-pawner'), \Yii::$app->requestedRoute == 'building/edit-pawner' ? 1 : 0]
));
?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>抵押人名字</th>
            <th>所属资方</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($pawners as $pawner): ?>
            <tr class="hover">
                <td class="td25"><?php echo $pawner['id']; ?></td>
                <td><?php echo $pawner['name']; ?></td>
                <td><?php echo Capital::getnamebyid($pawner['capital_id']); ?></td>
                <td><?php echo date('Y-m-d',$pawner['created_at']); ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['building/del-pawner', 'id' => $pawner['id']]); ?>" onclick="return confirm('确认要删除吗');">删除</a>
                    <a href="<?php echo Url::toRoute(['building/edit-pawner', 'id' => $pawner['id']]); ?>">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($capitals)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>