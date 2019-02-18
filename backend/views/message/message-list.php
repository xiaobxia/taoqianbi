<?php
use common\helpers\Url;

$this->shownav('user', 'menu_user_list');
$this->showsubmenu('消息通知中心', array(
    array('消息列表', Url::toRoute('message/message-list'), 1),
    array('发布消息', Url::toRoute('message/message-add'), 1),
));
?>
<!--催收订单列表-->
<?php echo $this->render('_message_list', ['message_list' => $message_list, 'pages' => $pages]); ?>
