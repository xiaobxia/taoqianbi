<?php
use common\helpers\Url;

$this->shownav('consumer_finance', 'menu_loan-record-list_create');
$this->showsubmenu('借款创建', array(
    array('列表', Url::toRoute(['loan-secur/loan-person-list-create','create_type'=>$create_type]), 1)
));
?>

<!--借款项目列表-->
<?php echo $this->render('_loan-person-list-create', ['loan_person' => $loan_person, 'pages' => $pages,'create_type'=>$create_type]); ?>
