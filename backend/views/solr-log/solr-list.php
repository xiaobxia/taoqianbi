<?php
use common\helpers\Url;
if($type==1){

	$this->showsubmenu('solr日志', array(
	    array('更新日志', Url::toRoute('solr-log/update-log-list'), 1),
	    array('插入日志', Url::toRoute('solr-log/insert-log-list'),0),
		array('用户详情更新日志', Url::toRoute('solr-log/user-detail-log-list'),0),
	));
}elseif($type==2){
	$this->showsubmenu('solr日志', array(
	    array('更新日志', Url::toRoute('solr-log/update-log-list'), 0),
	    array('插入日志', Url::toRoute('solr-log/insert-log-list'),1),
		array('用户详情更新日志', Url::toRoute('solr-log/user-detail-log-list'),0),
	));
}elseif($type==3){
	$this->showsubmenu('solr日志', array(
			array('更新日志', Url::toRoute('solr-log/update-log-list'), 0),
			array('插入日志', Url::toRoute('solr-log/insert-log-list'),0),
			array('用户详情更新日志', Url::toRoute('solr-log/user-detail-log-list'),1),
	));
}
?>


<?php echo $this->render('_solr-list', ['data_list' => $data_list, 'pages' => $pages]); ?>
