<?php

use common\models\Project;

/**
 * @var backend\components\View $this
 */
$this->shownav('project', 'menu_project_list');
$this->showsubmenuanchors('查看分期记录订单', array(
    array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
    array('后台放款', 'credit_backend', ($type == "credit_backend") ? 1 : 0),
    array('还  款', 'repay', ($type == "repay") ? 1 : 0),
));


?>
<div id="baseinfo" style="display: none;">
    <?php echo $this->render('_baseinfo', ['action' => $action, 'loan_record_period' => $loan_record_period, 'shop' => $shop]); ?>
</div>
<div id="credit_backend" style="display:none;">
    <?php echo $this->render('credit_info_backend', ['action' => $action,'type_action' => $type, 'result' => $result, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit, 'loan_repayment' => $loan_repayment, 'financial_money' => $financial_money,
        'financial_time' => $financial_time,]); ?>
</div>
<div id="repay" style="display:none;">
    <?php echo $this->render('repay_info', ['action' => $action, 'loan_record_period' => $loan_record_period, 'loan_repayment' => $loan_repayment, 'loan_repayment_period' => $loan_repayment_period,'loan_audit' => $loan_audit]); ?>
</div>



<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>
