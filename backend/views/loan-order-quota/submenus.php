<?php
use common\helpers\Url;
$route = isset($route) ? $route : '';
$this->showsubmenu('订单配额', array(
    array('默认配额', Url::toRoute(['/loan-order-quota/index']), $route==='loan-order-quota/index'),
    array('每日配额', Url::toRoute(['/loan-order-quota/day-quota-list']), $route==='loan-order-quota/day-quota-list'),
    array('添加指定日期配额', Url::toRoute(['/loan-order-quota/add-day-quota']), $route==='loan-order-quota/add-day-quota'),

));
?>