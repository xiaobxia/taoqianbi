<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:47
 */
use common\helpers\Url;

$this->shownav('project', 'menu_loan_project');
$this->showsubmenu('借款项目', array(
    array('列表', Url::toRoute('loan/loan-project-list'), 1),
    array('添加借款项目', Url::toRoute('loan/loan-project-add'), 0),
));
?>

    <!--借款项目列表-->
<?php echo $this->render('_loan-project-list', ['loan_project_list' => $loan_project_list, 'pages' => $pages]); ?>
