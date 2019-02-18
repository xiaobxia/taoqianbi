<?php
use common\helpers\Url;

$this->shownav('project', 'menu_loan_project');
$this->showsubmenu('商城分期购管理', array(
    array('列表', Url::toRoute('periodization/periodization-list'), 1),
));
?>


<?php echo $this->render('_periodization-list', ['data_list' => $data_list, 'pages' => $pages, 'loanService' => $loanService,'fqsc_status' => $fqsc_status]); ?>
