<?php
use common\helpers\Url;
$route = isset($route) ? $route : '';
$this->showsubmenu('资方管理', array(
    array('资方列表', Url::toRoute(['/loan-fund/index']), $route==='loan-fund/index'),
    array('添加资方', Url::toRoute(['/loan-fund/create']), $route==='loan-fund/create'),
    array('每日配额', Url::toRoute(['/loan-fund/day-quota-list']), $route==='loan-fund/day-quota-list'),
    array('添加指定日期配额', Url::toRoute(['/loan-fund/add-day-quota']), $route==='loan-fund/add-day-quota'),
    array('预留额度', Url::toRoute(['/loan-fund/reserved-list']), $route==='loan-fund/reserved-list'),
    array('添加预留额度', Url::toRoute(['/loan-fund/loan-fund-day-quota-creat']), $route==='loan-fund/loan-fund-day-quota-creat'),


));
?>