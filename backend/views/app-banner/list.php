<?php
/**
 *

 */
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('content', 'menu_operate_red_packet_list');
$this->showsubmenu('App banner管理', array(
    array('banner列表', Url::toRoute('list'), 1),
    array('添加新banner', Url::toRoute('add'), 0),
));
?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>banner图片</th>
            <th>类型分类</th>
            <th>是否悬浮</th>
            <th>适用版本</th>
            <th>外部链接</th>
            <th>内部跳转值</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value->id; ?></td>
                <td><a href="<?php echo $value->image_url; ?>" target="_blank"><img title="点击查看大图" src="<?php echo $value->image_url; ?>" width="50" height="50"></a></td>
                <td><?php echo $type_name[$value->type]; ?></td>
                <td><?php echo $type_float[$value->is_float]; ?></td>
                <td><?php echo \common\models\LoanPerson::$person_source[$value->source_id];?></td>
                <td><?php echo $value->link_url; ?></td>
                <td><?php echo $value->sub_type; ?></td>
                <td><?php echo $status_name[$value->status]; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['edit', 'id' => $value->id]);?>">编辑</a>
                    <a class="delItem" href="javascript:void(0)" tip="<?php echo Url::toRoute(['del', 'id' => $value->id]);?>">删除</a>
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
        if(confirm('确定删除该banner么？')) {
            window.location.href = url;
        }
    })
</script>
