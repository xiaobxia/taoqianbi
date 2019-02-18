<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('data_analysis_st', 'menu_pick_pushed_userbag_list');
if(empty($tip)){
   $tip = 0;
}
$this->showsubmenu('用户包列表', array(
    array('列表', Url::toRoute('pick-pushed/user-bag-list'), 1),
    array('生成用户包', Url::toRoute(['pick-pushed/user-bag-add','tip'=>$tip]),0),
));
?>

<!--用户包列表-->
<style>.tb2 th{ font-size: 12px;}</style>

<?php $form = ActiveForm::begin(['method' => "post", 'options'=> array('enctype' => 'multipart/form-data') ]); ?>
    	导入用户包：
    	<input type="file" name="import_file"></input>
		<input type="submit" name="search_submit" value="导入" class="btn"/></br>
		导入示例:<img src="http://res.koudailc.com/asset/20170117/4587dc1efb7f9b.png">
<?php $form = ActiveForm::end(); ?>
<hr>
<br>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>ID</th>
        <th>用户包名称</th>
        <th>用户类型</th>
        <th>生成时间</th>
        <th>更新时间</th>
        <th>用户数</th>
        <th>操作</th>
    </tr>
    <?php foreach ($list as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <td><?php if ($value['type'] == 1) { echo '申请用户';} elseif ($value['type'] == 2) {echo '导入用户';} else { echo '非申请用户';}; ?></td>
            <td><?php echo $value['created_at']; ?></td>
            <td><?php echo $value['updated_at']; ?></td>
            <td><?php echo $value['number']; ?></td>
            <td>
            	<?php if ($value['type'] != 2) : ?>
                <a href="<?php echo Url::toRoute(['pick-pushed/user-bag-add', 'id' => $value['_id']]); ?>">编辑</a>
                <a onclick="confirmRedirect('确定要更新吗？', '<?php echo Url::toRoute(['pick-pushed/create-user-data', 'id' => $value['_id']]); ?>')" href="javascript:void(0);">更新</a>
                <?php endif; ?>
                <a onclick="confirmRedirect('确定要删除吗？', '<?php echo Url::toRoute(['pick-pushed/user-bag-del', 'id' => $value['_id']]); ?>')" href="javascript:void(0);">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
