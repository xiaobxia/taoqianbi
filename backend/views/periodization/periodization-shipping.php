<?php
$this->shownav('project', 'menu_project_list');
    $this->showsubmenuanchors('分期发货',array(
        array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
        array('用户资金流水', 'accountlog', ($type == "accountlog") ? 1 : 0 ),
        array('发货', 'credit_backend', ($type == "credit_backend") ? 1 : 0),
    ));
?>
<div id="baseinfo" style="display: none;">
    <?php echo $this->render('_baseinfo', [
        'user'=>$user,
        'user_account'=>$user_account,
        'indiana_order'=>$indiana_order,
        'loan_person' => $loan_person,
        'phoneReviewLog'=>$phoneReviewLog,
        'loanContract' => $loanContract,
        'loanRecordPeriod' => $loanRecordPeriod,
        'action' => 'detail',
    ]); ?>
</div>
<div id="accountlog" style="display: none;">
    <?php echo $this->render('_accountlog', [
        'user_account_log_list'=>$user_account_log_list,
        'pages'=>$pages,
    ]); ?>
</div>
<div id="credit_backend" style="display:none;">
    <?php echo $this->render('credit_info_backend', [
        'action' => 'edit',
//        'type_action' => $type,
//        'result' => $result,
        'loanRecordPeriod' => $loanRecordPeriod,
        'loan_audit' => $loan_audit,
        'loan_repayment' => $loan_repayment
    ]); ?>
</div>
<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>