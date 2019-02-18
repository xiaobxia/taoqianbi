<?php
use common\helpers\Url;

$this->shownav('project', 'menu_loan_project');
$this->showsubmenu('借款人管理', array(
    array('列表', Url::toRoute('loan/loan-person-list'), 1),
    array('添加借款人', Url::toRoute('loan/loan-person-add'), 0),
));
?>

<!--借款项目列表-->
<?php echo $this->render('_loan-person-list', ['loan_person' => $loan_person, 'pages' => $pages]); ?>
