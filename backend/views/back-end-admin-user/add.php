<?php

use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_adminuser_list');
$this->showsubmenu('管理员管理', array(
	array('列表', Url::toRoute('back-end-admin-user/list'), 0),
	array('添加管理员', Url::toRoute('back-end-admin-user/add'), 1),
));
?>

<?php echo $this->render('_form', [
	'model' => $model,
	'roles' => $roles,
    'current_roles_arr' => $current_roles_arr,
    'current_user_groups_arr' => $current_user_groups_arr,
	'is_super_admin' => $is_super_admin,
]); ?>