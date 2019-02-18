<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:21
 */
use common\helpers\Url;

$this->shownav('project', 'menu_loan_project');
$this->showsubmenu('借款项目', array(
    array('列表', Url::toRoute('loan/loan-project-list'), 0),
    array('添加借款项目', Url::toRoute('loan/loan-project-add'), 1),
));
?>

    <!--借款项目-->
<?php echo $this->render('_loan-project-form', ['loan_project' => $loan_project]); ?>
