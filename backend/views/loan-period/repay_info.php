<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\widgets\ActiveForm;
use common\models\LoanTrial;
use common\models\LoanRepayment;
?>
<?php if(empty($loan_repayment) || empty($loan_repayment_period)):?>
    抱歉，没有分期还款记录信息。
<?php else:?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15" style="color: red;">还款详细信息</th></tr>
        <tr>
            <td></td>
            <td>序号</td>
            <td>期数</td>
            <td>预计还款日期</td>
            <td>预计还款金额</td>
            <td>预计还款本金</td>
            <td>预计还款利息</td>
            <td>预计下一还款日期</td>
            <td>预计剩余还款金额</td>
            <td>实际还款日期</td>
            <td>实际还款金额</td>
            <td>管理员</td>
            <td>还款状态</td>
            <td>备注</td>
        </tr>
        <?php foreach($loan_repayment_period as $k => $v):?>
        <tr>
            <td>
                <?php if($v['status'] == LoanRepayment::STATUS_REPAYING && strtotime(date("Y-m-d", $v['plan_repayment_time'])) == strtotime(date("Y-m-d", time()))):?>
                    <input type="radio" onclick="calc(<?php echo $v['id']?>, <?php echo sprintf("%.2f", $v->plan_repayment_money / 100)?>)" name="period_select" value="<?php echo $v['period']; ?>">
                <?php endif;?>
            </td>
            <td><?php echo $v->id;?></td>
            <td><?php echo $v->period;?></td>
            <td><?php echo empty($v->plan_repayment_time) ? "---" : date("Y-m-d", $v->plan_repayment_time);?></td>
            <td><?php echo sprintf("%.2f", $v->plan_repayment_money / 100);?></td>
            <td><?php echo sprintf("%.2f", $v->plan_repayment_principal / 100);?></td>
            <td><?php echo sprintf("%.2f", $v->plan_repayment_interest / 100);?></td>
            <td><?php echo empty($v->plan_next_repayment_time) ? "---" : date("Y-m-d", $v->plan_next_repayment_time);?></td>
            <td><?php echo sprintf("%.2f", $v->plan_will_repayment_amount / 100);?></td>
            <td><?php echo empty($v->true_repayment_time) ? "---" : date("Y-m-d", $v->true_repayment_time);?></td>
            <td><?php echo empty($v->admin_username) ? "---" : sprintf("%.2f", $v->true_repayment_money / 100);?></td>
            <td><?php echo empty($v->admin_username) ? "---":$v->admin_username;?></td>
            <td><?php echo \common\models\LoanRepayment::$status[$v->status];?></td>
            <td><?php echo empty($v->remark) ? "---":$v->remark;?></td>
        </tr>
        <?php endforeach;?>
    </table>
    <?php if($action == "edit"):?>
        <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'post', 'action' => ['loan-period/loan-period-repay']]); ?>
        <table style="margin-top: 30px;">
            <tr>
                <td>
                    实际还款时间：
                </td>
                <td>
                    <input type="text" name="true_repayment_time" id="true_repayment_time" value="<?php echo  date("Y-m-d", time())?>">
                </td>
            </tr>
            <tr>
                <td>
                    实际还款金额：
                </td>
                <td>
                    <input type="text" name="true_repayment_money" readonly = "true" style="background-color: #BAD4C6;" id="true_repayment_money">
                </td>
            </tr>
            <tr>
                <td>
                    备注信息：
                </td>
                <td>
                    <textarea cols="50" rows="6" name="remark"></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    操作：
                </td>
                <td>
                    <input type="hidden" id="repayment_period_id" name="id" value="">
                    <input type="hidden" id="loan_record_period_id" name="loan_record_period_id" value="<?php echo $loan_record_period->id;?>">
                    <input type="submit" name="submit" value="还款" class="btn">
                </td>
            </tr>
        </table>
        <?php ActiveForm::end(); ?>
    <?php endif;?>
<?php endif;?>
<script>
    function calc(id, money){
        document.getElementById("true_repayment_money").value = money;
        document.getElementById("repayment_period_id").value = id;
    }
</script>


