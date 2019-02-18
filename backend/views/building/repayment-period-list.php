<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRepaymentPeriod;
use common\models\LoanRepayment;


/**
 * @var backend\components\View $this
 */
$this->shownav('building', 'menu_loan-repayment-period-list');

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['building/repayment-period-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
所属项目：<?php echo Html::dropDownList('project', Yii::$app->getRequest()->get('project', ''), $projects, array('prompt' => '-所有项目-')); ?>&nbsp;
门店ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_id', ''); ?>" name="shop_id" class="txt" style="width:60px;">&nbsp;
还款ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('repayment_id', ''); ?>" name="repayment_id" class="txt" style="width:60px;">&nbsp;
借款人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_person_name', ''); ?>" name="loan_person_name" class="txt" style="width:60px;">&nbsp;
借款人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_person_id', ''); ?>" name="loan_person_id" class="txt" style="width:60px;">&nbsp;
扣款订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:60px;">&nbsp;
计划还款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('plan_repayment_time_start', ''); ?>"  name="plan_repayment_time_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('plan_repayment_time_end', ''); ?>"  name="plan_repayment_time_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
是否只筛选计划还款本金:<?php echo Html::dropDownList('operation', Yii::$app->getRequest()->get('operation', ''), ['0'=>'否','1'=>'是']); ?>&nbsp;
实际还款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('true_repayment_time_start', ''); ?>"  name="true_repayment_time_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('true_repayment_time_end', ''); ?>"  name="true_repayment_time_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), LoanRepayment::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<?php $form = ActiveForm::begin(['method' => "get",'action'=>['building/export'], 'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
计划还款时间：<input type="text" style="width: 90px" value="<?php echo Yii::$app->getRequest()->get('foreloan_begintime', ''); ?>" name="foreloan_begintime" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" style="width: 90px" value="<?php echo Yii::$app->getRequest()->get('foreloan_endtime', ''); ?>"  name="foreloan_endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
是否只筛选计划还款本金:<?php echo Html::dropDownList('operation', Yii::$app->getRequest()->get('operation', ''), ['0'=>'否','1'=>'是']); ?>&nbsp;
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('export');return true;" class="btn">
<?php $form = ActiveForm::end(); ?>

<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>借款ID</th>
            <th>还款ID</th>
            <th>借款用户ID</th>
            <th>借款用户</th>
            <th>借款项目</th>
            <th>产品名称</th>
            <th>所属门店</th>
            <th>当前期数</th>
            <th>计划还款金额</th>
            <th>计划还款本金</th>
            <th>计划还款利息</th>
            <th>实际还款金额（元）</th>
            <th>实际还款本金（元）</th>
            <th>实际还款利息（元）</th>
            <th>剩余还款金额（元）</th>
            <th>计划还款时间</th>
            <th>实际还款时间</th>
            <th>扣款订单ID</th>
            <th>还款状态</th>
            <th>操作人</th>
            <th>操作</th>
        </tr>
        <?php foreach ($loan_repayment_period_list as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td><a href="<?php echo Url::toRoute(['loan-period/loan-record-period-view', 'id' => $value['loan_record_id']]); ?>"><?php echo $value['loan_record_id']; ?></a></td>
                <td><?php echo $value['repayment_id']; ?></td>
                <td><a href="<?php echo Url::toRoute(['loan/loan-person-view', 'id' => $value['loan_person_id'], 'type' => '2']); ?>"><?php echo $value['loan_person_id']; ?></a></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php echo $projects[$value['loanRepayment']['loan_project_id']]."/".$value['loanRepayment']['loan_project_id']; ?></td>
                <td><?php echo $value['loanRecordPeriod']['product_type_name']; ?></td>
		<td><?php echo (empty($shops[$value['loanRepayment']['shop_id']]) ? "自营客户" : $shops[$value['loanRepayment']['shop_id']])."/".$value['loanRepayment']['shop_id']; ?></td>
                <td><?php echo $value['period']; ?></td>
                <td><?php echo sprintf('%.2f', $value['plan_repayment_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['plan_repayment_principal'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['plan_repayment_interest'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['true_repayment_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['true_repayment_principal'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['true_repayment_interest'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['plan_will_repayment_amount'] / 100); ?></td>
                <td><?php echo date('Y-m-d', $value['plan_repayment_time']); ?></td>
                <td><?php echo date('Y-m-d', $value['true_repayment_time']); ?></td>
                <td><?php echo $value['order_id']; ?></td>
                <td>
                    <?php
                        if($value['status'] == LoanRepaymentPeriod::STATUS_REPAYED) {
                            echo "<font color='green'>".LoanRepaymentPeriod::$status[$value['status']]."</font>";
                        }
                        else if($value['status'] != LoanRepaymentPeriod::STATUS_REPAYING){
                             echo "<font color='red'>".LoanRepaymentPeriod::$status[$value['status']]."</font>";
                        } else {
							echo LoanRepaymentPeriod::$status[$value['status']];
						}
                    ?>
                </td>
                <td><?php echo $value['admin_username']; ?></td>
                <td>
                    <?php
                        if($value['status'] == LoanRepaymentPeriod::STATUS_REPAYED) {
                            echo "<a href='".Url::toRoute(['building/repay-detail', 'id' => $value['id']])."'>查看</a>";
                        }
                        else {
                            echo "<a href='".Url::toRoute(['building/repay', 'id' => $value['id']])."'>操作</a>";
                        }
                    ?>
                    <a href="<?php echo Url::toRoute(['building/sms', 'id' => $value['id']]); ?>">短信</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_repayment_period_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php if(!empty($total_principal_amount) || !empty($total_interest_amount) || !empty($remain_principal_amount)): ?>
    <table frame="above" align="right">
        <tr>
            <td align="center" style="color: red;">项目</td>
            <td align="center" style="color: red;">金额(元)</td>
        </tr>
        <tr>
            <td style="color: red;">计划还款总本金：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$plan_repayment_principal / 100) ?></td>
        </tr>
        <tr>
            <td style="color: red;">已经还款总本金：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$true_repayment_principal / 100) ?></td>
        </tr>
        <tr>
            <td style="color: red;">剩余还款总本金：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$remain_principal / 100) ?></td>
        </tr>
        <tr>
            <td style="color: red;">计划还款总利息：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$plan_repayment_interest / 100) ?></td>
        </tr>
        <tr>
            <td style="color: red;">已经还款总利息：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$true_repayment_interest / 100) ?></td>
        </tr>
        <tr>
            <td style="color: red;">剩余还款总利息：</td>
            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$remain_interest / 100) ?></td>
        </tr>

    </table>
<?php endif; ?>
