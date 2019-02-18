<?php
use common\helpers\Url;

$this->shownav('credit', 'menu_zmop_user_zmop_list');
$this->showsubmenu('用户授权管理');
?>

<!--借款项目列表-->
<?php echo $this->render('_loan-person-list', ['loan_person' => $loan_person, 'pages' => $pages]); ?>
