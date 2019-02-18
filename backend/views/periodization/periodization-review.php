<?php
use common\helpers\Url;
use yii\widgets\ActiveForm;
/**
 * @var backend\components\View $this
 */
$this->shownav('project', 'menu_project_list');
$this->showsubmenuanchors('查看订单信息',array(
    array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
    array('用户资金流水', 'accountlog', ($type == "accountlog") ? 1 : 0 ),
));
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<div id="baseinfo" style="display: none;">
    <?php echo $this->render('_baseinfo', [
        'user'=>$user,
        'user_account'=>$user_account,
        'indiana_order'=>$indiana_order,
        'user_account_log_list'=>$user_account_log_list,
        'pages'=>$pages,
        'loan_person' => $loan_person,
        'phoneReviewLog'=>$phoneReviewLog,
        'action' => $action,
    ]); ?>
</div>
<div id="accountlog" style="display: none;">
    <?php echo $this->render('_accountlog', [
        'user_account_log_list'=>$user_account_log_list,
        'pages'=>$pages,
    ]); ?>
</div>

<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>
