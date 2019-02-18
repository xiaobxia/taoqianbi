<?php

use common\models\Project;

/**
 * @var backend\components\View $this
 */
$this->shownav('project', 'menu_project_list');
$this->showsubmenuanchors('查看分期记录订单', array(
    array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
    array('初审记录', 'trial', ($type == "trial") ? 1 : 0 ),
    array('电话审核', 'tele', ($type == "tele") ? 1 : 0),
    array('复审记录', 'review', ($type == "review") ? 1 : 0),
    array('放  车', 'car', ($type == "car") ? 1 : 0),
    array('放  款', 'credit', ($type == "credit") ? 1 : 0),
    array('还  款', 'repay', ($type == "repay") ? 1 : 0),
    array('后台放款', 'credit_backend', ($type == "credit_backend") ? 1 : 0),

));
?>
<div id="baseinfo" style="display: none;">
    <?php echo $this->render('_baseinfo', ['loan_record_period' => $loan_record_period, 'user' => $user, 'shop' => $shop]); ?>
</div>

<div id="trial" style="display:none;">
    <?php echo $this->render('trial_info', ['loan_record_period' => $loan_record_period, 'loan_trial' => $loan_trial, 'type' => 'trial_info', 'loan_audit' => $loan_audit]); ?>
</div>

<div id="tele" style="display:none;">
    <?php echo $this->render('tele_info', ['loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit]); ?>
</div>

<div id="review" style="display:none;">
    <?php echo $this->render('review_info', ['loan_record_period' => $loan_record_period,'loan_review' => $loan_review, 'loan_audit' => $loan_audit]); ?>
</div>

<div id="car" style="display:none;">
    <?php echo $this->render('car_info', ['loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit]); ?>
</div>

<div id="credit" style="display:none;">
    <?php echo $this->render('credit_info', ['result' => $result, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit, 'loan_repayment' => $loan_repayment]); ?>
</div>

<div id="repay" style="display:none;">
    <?php echo $this->render('repay_info', ['loan_record_period' => $loan_record_period, 'loan_repayment' => $loan_repayment, 'loan_repayment_period' => $loan_repayment_period,'loan_audit' => $loan_audit]); ?>
</div>

<div id="credit_backend" style="display:none;">
    <?php echo $this->render('credit_info', ['loan_record_period' => $loan_record_period, 'loan_repayment' => $loan_repayment, 'loan_repayment_period' => $loan_repayment_period,'loan_audit' => $loan_audit]); ?>
</div>

<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>
