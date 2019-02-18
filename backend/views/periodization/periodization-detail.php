<?php
$this->shownav('project', 'menu_project_list');
    $this->showsubmenuanchors('查看分期信息',array(
        array('基本信息', 'baseinfo', ($type == "baseinfo") ? 1 : 0),
        array('用户资金流水', 'accountlog', ($type == "accountlog") ? 1 : 0 ),
        array('还  款', 'repay', ($type == "repay") ? 1 : 0),
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
<div id="repay" style="display:none;">
    <?php echo $this->render('repay_info', [
        'loan_record_period' => $loanRecordPeriod,
        'loan_repayment' => $loan_repayment,
        'loan_repayment_period' => $loan_repayment_period,
        'loan_audit' => $loan_audit
    ]); ?>
</div>
<input type="hidden" id="type" value="<?php echo $type;?>">
<script>
    var type = document.getElementById("type").value;
    document.getElementById(type).style.display = "block";
</script>