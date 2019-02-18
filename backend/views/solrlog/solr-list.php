<?php
use common\helpers\Url;
if($type==1){

	$this->showsubmenu('solr日志', array(
	    array('更新日志', Url::toRoute('solr-log/update-log-list'), 1),
	    array('插入日志', Url::toRoute('solr-log/insert-log-list'),0),
	));
}else{
	$this->showsubmenu('solr日志', array(
	    array('更新日志', Url::toRoute('solr-log/update-log-list'), 0),
	    array('插入日志', Url::toRoute('solr-log/insert-log-list'),1),
	));
}
?>


<?php echo $this->render('_solr-list', ['data_list' => $data_list, 'pages' => $pages]); ?>
