<?php
use common\helpers\Url;

$this->shownav('content', 'menu_operate_activity_list');
$this->showsubmenu('公告中心', array(
    array('公告列表', Url::toRoute('content-activity/list'), 0),
    array('公告添加', Url::toRoute('content-activity/add'), 1),
));
echo $this->render('_form', [
	'model' => $model,
]); ?>