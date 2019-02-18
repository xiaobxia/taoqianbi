<?php

use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_adminuser_role_list');
$this->showsubmenu('角色管理', array(
	array('列表', Url::toRoute('back-end-admin-user/role-list'), 1),
	array('添加角色', Url::toRoute('back-end-admin-user/role-add'), 0),
));

?>

<?php echo $this->render('_roleform', [
	'model' => $model,
	'permissions' => $permissions,
	'permissionChecks' => $permissionChecks,
]); ?>