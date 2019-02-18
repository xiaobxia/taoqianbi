<?php
use common\helpers\Url;
$route = isset($route) ? $route : '';
$this->showsubmenu('资方管理', array(
    array('资方账户列表', Url::toRoute(['/fund-account/index']), $route==='loan-fund/index'),
    array('添加资方账户', Url::toRoute(['/fund-account/create']), $route==='loan-fund/create'),

));
?>