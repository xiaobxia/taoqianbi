<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:21
 */
use common\helpers\Url;
$this->shownav('loan', 'loan_blacklist_list');
$this->showsubmenu('黑名单规则列表', array(
    array('黑名单规则列表', Url::toRoute('loan-blacklist-detail/list'), 0),
    array('黑名单规则添加', Url::toRoute('loan-blacklist-detail/add'),1),
    array('黑名单用户列表', Url::toRoute('loan-blacklist-detail/black-users'),0)
));
?>

    <!--添加公司-->
<?php echo $this->render('_form', ['data' => $data]); ?>
