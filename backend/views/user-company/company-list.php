<?php
use common\helpers\Url;

$this->shownav('user', 'menu_user_list');
if(empty($tip)){
    $tip = 0;
}
$this->showsubmenu('催收用户管理', array(
    array('催收人员列表', Url::toRoute('user-collection/user-list'), 0),
    // array('添加催收人员', Url::toRoute(['user-collection/user-add','tip'=>0]),0),//原洪立峰内容
    array('添加催收人员', Url::toRoute(['user-company/user-add','tip'=>0]),0),
    array('催收分配规则', Url::toRoute(['user-company/user-schedule','tip'=>0]),0),
    array('催收公司', Url::toRoute(['user-company/company-lists','tip'=>0]),1),
));
?>

    <!--催收公司列表-->
<?php echo $this->render('_company-list', ['user_collection' => $user_collection, 'pages' => $pages]); ?>