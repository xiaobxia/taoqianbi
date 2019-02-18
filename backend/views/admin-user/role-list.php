<?php

use common\helpers\Url;
use backend\models\AdminUser;
use backend\models\AdminUserRole;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;

/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_adminuser_role_list');
$this->showsubmenu('角色管理', array(
	array('列表', Url::toRoute('admin-user/role-list'), 1),
	array('添加角色', Url::toRoute('admin-user/role-add'), 0),
));

?>
<?php $form = ActiveForm::begin(['id' => 'searchform','method'=>'get', 'options' => ['style' => 'margin-bottom:5px;']]); ?>
所属分组：<?php echo Html::dropDownList('group_id', Yii::$app->getRequest()->get('group_id', ''), AdminUserRole::$status, ['prompt' => '所有分组']); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
	<tr class="header">
		<th>ID</th>
		<th>标识</th>
		<th>所属组</th>
		<th>名称</th>
		<th>描述</th>
		<th>创建人</th>
		<th>创建时间</th>
		<th>操作</th>
	</tr>
	<?php foreach ($roles as $value): ?>
	<tr class="hover">
		<td class="td25"><?php echo $value->id; ?></td>
		<td><?php echo $value->name; ?></td>
		<td><?php echo $value->groups?AdminUserRole::$status[$value->groups]:'暂无分组'; ?></td>
		<td><?php echo $value->title; ?></td>
		<td><?php echo $value->desc; ?></td>
		<td><?php echo $value->created_user; ?></td>
		<td><?php echo date('Y-m-d', $value->created_at); ?></td>
		<td class="td23">
            <a href="<?php echo Url::toRoute(['admin-user/role-details','role'=> $value->name])?>">成员</a>
			<?php if ($value->name != AdminUser::SUPER_ROLE && ($value->groups != AdminUserRole::TYPE_COLLECTION) ): ?>
			<a href="<?php echo Url::toRoute(['admin-user/role-edit', 'id' => $value->id]); ?>">编辑</a>
			<a onclick="return confirmMsg('确定要删除吗？\n删除后该角色对应的管理员将失去权限');" href="<?php echo Url::toRoute(['admin-user/role-delete', 'id' => $value->id]); ?>">删除</a>
			<?php else: ?>
            <?php endif; ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
