<?php
use common\helpers\Url;

$this->shownav('user', 'menu_ygd_user');
if(empty($tip)){
   $tip = 0;
}
if($type==1) {
   $this->showsubmenu('用户管理', array(
       array('列表', Url::toRoute('loan/ygd-list'), 1)
   ));
}else{
   $this->showsubmenu('用户管理', array(
       array('列表', Url::toRoute('loan/ygd-list'), 1),
//       array('添加借款人', Url::toRoute(['loan/loan-person-add','tip'=>$tip]),0),
       //array('添加渠道商',Url::toRoute(['loan/loan-channel-add']),0)
   ));
}
?>

<!--借款项目列表-->
<?php echo $this->render('_loan-person-list', [
    'loan_person' => $loan_person,
    'details' => $details,
    'pages' => $pages,
    'type' => $type,
]); ?>
