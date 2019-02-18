<?php
/**
 *

 */
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('system', 'menu_shunt_list');
$this->showsubmenu('导流列表', array(
     array('分流列表', Url::toRoute('shunt-list'), 1),
     array('添加新类型', Url::toRoute('shunt-add'), 0),
));
?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>log图片</th>
            <th>名称</th>
            <th>类型</th>
            <th>说明</th>
            <th>地址</th>
            <th>人数</th>
            <th>利率</th>
            <th>特点</th>
            <th>排序</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value->id; ?></td>
                <td><a href="<?php echo $value->log_url; ?>" target="_blank"><img title="点击查看大图" src="<?php echo $value->log_url; ?>" width="50" height="50"></a></td>
                <td><?php echo $value->name; ?></td>
                <td><?php echo $types[$value->type_id];?>
                <td><?php echo $value->remark; ?></td>
                <td><?php echo $value->url; ?></td>
                <td><?php echo $value->number; ?></td>
                <td><?php echo $value->rate; ?></td>
                <td><?php echo $value->trait?$value->trait:''; ?></td>
                <td><?php echo $value->sort; ?></td>
                <td><?php echo $value->status==1?'显示':'不显示'; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['shunt-edit', 'id' => $value->id]);?>">编辑</a>
                    <a class="delItem" href="javascript:void(0)" tip="<?php echo Url::toRoute(['shunt-del', 'id' => $value->id]);?>">删除</a>
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
        if(confirm('确定删除该数据么？')) {
            window.location.href = url;
        }
    })
</script>
