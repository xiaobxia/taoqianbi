<?php
/**
 *

 */
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('content', 'menu_operate_red_packet_list');
$this->showsubmenu('App升级管理', array(
    array('配置升级列表', Url::toRoute('list'), 0),
    array('添加新升级配置', Url::toRoute('add'), 0),
    array('版本级配置列表', Url::toRoute('list-rule'), 1),
    array('添加版本级配置', Url::toRoute('add-rule'), 0),
));
?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>规则名称</th>
            <th>appMarket</th>
            <th>pkgname</th>
            <th>是否启用</th>
            <th>操作</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value->id; ?></td>
                <td><?php echo $value->name; ?></td>
                <td><?php echo $value->remark; ?></td>
                <td><?php echo $value->pkgname; ?></td>
                <td><?php if($value->status == 1){echo "启用";}else{ echo "停用";}; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['edit-rule', 'id' => $value->id]);?>">编辑</a>
                    <a class="delItem" href="javascript:void(0)" tip="<?php echo Url::toRoute(['del-rule', 'id' => $value->id]);?>">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php ActiveForm::end(); ?>

<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    $('.delItem').click(function(){
        var url = $(this).attr('tip');
        if(confirm('确定删除该版本么？')) {
            window.location.href = url;
        }
    })
</script>
