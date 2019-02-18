<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRecord;
use common\models\LoanRepayment;


/**
 * @var backend\components\View $this
 */
$this->shownav('building', 'menu_loan-repayment-list');
$this->showsubmenu('还款明细');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['building/repayment-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
门店ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_id', ''); ?>" name="shop_id" class="txt" style="width:60px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
每期还款金额：<input type="text" value="<?php echo Yii::$app->getRequest()->get('period_repayment_amount', ''); ?>" name="period_repayment_amount" class="txt" style="width:60px;">&nbsp;
借款人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:60px;">&nbsp;
借款人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_person_id', ''); ?>" name="loan_person_id" class="txt" style="width:60px;">&nbsp;
放款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('credit_repayment_time_start', ''); ?>"  name="credit_repayment_time_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('credit_repayment_time_end', ''); ?>"  name="credit_repayment_time_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>借款用户ID</th>
            <th>借款ID</th>
            <th>借款用户</th>
            <th>借款项目</th>
            <th>产品名称</th>
            <th>所属门店</th>
            <th>总还款金额（元）</th>
            <th>总还款本金（元）</th>
            <th>总还款利息（元）</th>
            <th>已还款金额（元）</th>
            <th>还款期数（期）</th>
            <th>每期还款金额（元）</th>
            <th>放款时间</th>
            <th>签约还款日期</th>
            <th>最终还款日期</th>
            <th>还款状态</th>
            <th>操作</th>
        </tr>
        <?php foreach ($loan_repayment_list as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td><a href="<?php echo Url::toRoute(['loan/loan-person-view', 'id' => $value['loan_person_id'], 'type' => '2']); ?>"><?php echo $value['loan_person_id']; ?></a></td>
                <td><a href="<?php echo Url::toRoute(['loan-period/loan-record-period-view', 'id' => $value['loan_record_id']]); ?>"><?php echo $value['loan_record_id']; ?></a></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php echo $projects[$value['loan_project_id']]."/".$value['loan_project_id']; ?></td>
                <td><?php echo $value['loanRecordPeriod']['product_type_name']; ?></td>
		<td><?php echo (empty($shops[$value['shop_id']]) ? "自营客户" : $shops[$value['shop_id']])."/".$value['shop_id']; ?></td>
                <td><?php echo sprintf('%.2f', $value['repayment_amount'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['repayment_principal'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['repayment_interest'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['repaymented_amount'] / 100); ?></td>
                <td><?php echo $value['period']; ?></td>
                <td><?php echo sprintf('%.2f', $value['period_repayment_amount'] / 100); ?></td>
                <td><?php echo date('Y-m-d', $value['credit_repayment_time']); ?></td>
                <td><?php echo date('Y-m-d', $value['sign_repayment_time']); ?></td>
                <td><?php echo date('Y-m-d', $value['repayment_time']); ?></td>
                <td>
                    <?php
                        if($value['status'] == LoanRepayment::STATUS_REPAYED) {
                            echo "<font color='green'>".LoanRepayment::$status[$value['status']]."</font>";
                        }
                        else if($value['status'] != LoanRepayment::STATUS_REPAYING){
                             echo "<font color='red'>".LoanRepayment::$status[$value['status']]."</font>";
                        } else {
                            echo LoanRepayment::$status[$value['status']];
                        }
                    ?>
                </td>
                <td>
                    <a href="<?php echo Url::toRoute(['building/repayment-period-list', 'repayment_id' => $value['id']]); ?>">还款计划</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_repayment_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
