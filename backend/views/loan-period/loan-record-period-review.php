<?php

use common\models\Project;

/**
 * @var backend\components\View $this
 */
$this->shownav('project', 'menu_project_list');
if(empty($loan_person_info)){
    $this->showsubmenuanchors('查看分期记录订单', array(
        array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
        array('征信信息', 'credit_check', ($type == "credit_check") ? 1 : 0),
        array('初审记录', 'trial', ($type == "trial") ? 1 : 0 ),
        array('电话审核', 'tele', ($type == "tele") ? 1 : 0),
        array('复审记录', 'review', ($type == "review") ? 1 : 0),
    //    array('扣款银行卡绑卡', 'bind', ($type == "bind") ? 1 : 0),
        array('放  车', 'car', ($type == "car") ? 1 : 0),
//    array('放  款', 'credit', ($type == "credit") ? 1 : 0),
        array('后台放款', 'credit_backend', ($type == "credit_backend") ? 1 : 0),
        array('还  款', 'repay', ($type == "repay") ? 1 : 0),
    ));
}else{
    $this->showsubmenuanchors('查看分期记录订单', array(
        array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
        array('培训分期信息', 'train_period_info', ($type == "train_period_info") ? 1 : 0),
        array('征信信息', 'credit_check', ($type == "credit_check") ? 1 : 0),
        array('初审记录', 'trial', ($type == "trial") ? 1 : 0 ),
        array('电话审核', 'tele', ($type == "tele") ? 1 : 0),
        array('复审记录', 'review', ($type == "review") ? 1 : 0),
    //    array('扣款银行卡绑卡', 'bind', ($type == "bind") ? 1 : 0),
        array('放  车', 'car', ($type == "car") ? 1 : 0),
//    array('放  款', 'credit', ($type == "credit") ? 1 : 0),
        array('后台放款', 'credit_backend', ($type == "credit_backend") ? 1 : 0),
        array('还  款', 'repay', ($type == "repay") ? 1 : 0),
    ));
}

?>
<div id="baseinfo" style="display: none;">
    <?php echo $this->render('_baseinfo', ['action' => $action, 'loan_record_period' => $loan_record_period, 'user' => $user, 'shop' => $shop]); ?>
</div>
<?php if(!empty($loan_person_info)): ?>
    <div id="train_period_info" style="display: none;">
        <?php echo $this->render('_train_period_info', ['loan_person_info'=>$loan_person_info,'loan_person_info_img'=>$loan_person_info_img]); ?>
    </div>
<?php endif;?>
<div id="credit_check" style="display: none;">
    <?php echo $this->render('credit_check', ['action' => $action, 'loan_person' => $loan_person , 'loan_record_period'=>$loan_record_period]); ?>
</div>
<div id="trial" style="display:none;">
    <?php echo $this->render('trial_info', ['action' => $action, 'loan_record_period' => $loan_record_period, 'loan_trial' => $loan_trial, 'type' => 'trial_info', 'loan_audit' => $loan_audit]); ?>
</div>

<div id="tele" style="display:none;">
    <?php echo $this->render('tele_info', ['action' => $action, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit]); ?>
</div>

<div id="review" style="display:none;">
    <?php echo $this->render('review_info', ['action' => $action, 'loan_record_period' => $loan_record_period,'loan_review' => $loan_review, 'loan_audit' => $loan_audit]); ?>
</div>

<div id="car" style="display:none;">
    <?php echo $this->render('car_info', ['action' => $action, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit]); ?>
</div>

<!--<div id="credit" style="display:none;">-->
<!--    --><?php //echo $this->render('credit_info', ['action' => $action, 'type_action' => $type, 'result' => $result, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit, 'loan_repayment' => $loan_repayment]); ?>
<!--</div>-->
<div id="credit_backend" style="display:none;">
    <?php echo $this->render('credit_info_backend', ['action' => $action,'type_action' => $type, 'result' => $result, 'loan_record_period' => $loan_record_period, 'loan_audit' => $loan_audit, 'loan_repayment' => $loan_repayment]); ?>
</div>
<div id="repay" style="display:none;">
    <?php echo $this->render('repay_info', ['action' => $action, 'loan_record_period' => $loan_record_period, 'loan_repayment' => $loan_repayment, 'loan_repayment_period' => $loan_repayment_period,'loan_audit' => $loan_audit]); ?>
</div>



<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>
