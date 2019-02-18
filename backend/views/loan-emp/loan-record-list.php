<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 20:24
 */
$this->shownav('project', 'menu_loan_record');
$this->showsubmenu('借款记录信息');
?>

<!--借款记录列表-->
<?php echo $this->render('_loan-record-list', ['loan_record_list' => $loan_record_list, 'pages' => $pages]); ?>
