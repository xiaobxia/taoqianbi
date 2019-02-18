<?php
use common\helpers\Url;
$this->shownav('loan', 'menu_loan_lqb_list');
$page_type = \yii::$app->request->get('page_type', '1');
if ($channel != 1) {
    if($page_type == 1){
        $this->showsubmenu('借款列表', array(
            array('零钱包列表', Url::toRoute('pocket/pocket-list'), 1),
        ));
    }else if($page_type == 2){
        $this->showsubmenu('放款列表');
    }else{
        $this->showsubmenu('还款列表');
    }

} else {
    $this->showsubmenu('借款列表');
}
?>


<?php echo $this->render('_pocket-list', [
    'data_list' => $data_list,
    'pages' => $pages,
    'channel' => $channel,
    'status_data' => $status_data,
    'begintime' => $begintime,
    'page_type' => $page_type,
    'num' => $num,
]);
?>