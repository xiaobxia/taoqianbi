<?php

use common\helpers\Url;
use yii\helpers\FileHelper;

/**
 * @var backend\components\View $this
 */
$this->shownav('content', 'menu_attachment_add');
$this->showsubmenu('附件管理', array(
	array('列表', Url::toRoute('attachment/list'), 1),
//	array('添加附件', Url::toRoute('attachment/add'), 0),
));

?>

<table class="tb tb2 fixpadding">
	<tr class="header">
		<th>文件名</th>
		<th>预览</th>
		<th>大小</th>
		<th>地址</th>
		<th>创建时间</th>
		<th>操作</th>
	</tr>
	<?php foreach ($prefixes as $v): ?>
	<tr>
		<td><a href="<?php echo Url::toRoute(['attachment/list', 'prefix' => strval($v->Prefix)]); ?>"><?php echo basename($v->Prefix).'/'; ?></a></td>
		<td>文件夹</td>
		<td>-</td>
		<td>-</td>
		<td>-</td>
		<td>-</td>
	</tr>
	<?php endforeach; ?>
	<?php foreach ($contents as $content): ?>
		<?php if ($prefix != $content->Key): ?>
		<tr>
			<td><?php echo basename($content->Key); ?></td>
			<td><?php echo in_array(FileHelper::getMimeTypeByExtension(basename($content->Key)), ['image/jpeg','image/png','image/gif'])
			? '<a target="_blank" href="'.OSS_RES_URL.$content->Key.'"><img src="'.OSS_RES_URL.$content->Key.'" width="50" height="50"/></a>'
            : '-'; ?></td>
			<td><?php echo sprintf('%.3f', $content->Size/1024) . 'KB'; ?></td>
			<td><?php echo OSS_RES_URL . $content->Key; ?></td>
			<td><?php echo date('Y-m-d H:i:s', strtotime($content->LastModified)); ?></td>
			<td><a onclick="return confirmMsg('确定要删除吗？不可恢复哦！');" href="<?php echo Url::toRoute(['attachment/delete', 'key' => strval($content->Key)]); ?>">删除</a></td>
		</tr>
		<?php endif; ?>
	<?php endforeach; ?>
</table>