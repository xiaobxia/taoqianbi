<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\LoanRepaymentPeriod;
use backend\models\LoanCollectionRecord;
use backend\models\LoanFkRecord;

/**
 * @var backend\components\View $this
 */
$this->shownav('asset', 'menu_loan_fk');
$this->showsubmenu('放款记录列表');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan-period/loan-fk-list']]); ?>
借款记录ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_record_id', ''); ?>" name="loan_record_id" class="txt" style="width:60px;">&nbsp;
放款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('credit_repayment_time_start', ''); ?>"  name="credit_repayment_time_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('credit_repayment_time_end', ''); ?>"  name="credit_repayment_time_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>借款人ID</th>
            <th>借款记录ID</th>
            <th>放款状态</th>
            <th>放款金额</th>
            <th>还款类型</th>
            <th>服务费</th>
            <th>加急费</th>
            <th>借款利率</th>
            <th>还款操作方式</th>
            <th>借款期限(月)</th>
            <th>操作人</th>
            <th>放款时间</th>
        </tr>
        <?php foreach ($loan_fk as $value): ?>
            <tr class="hover">
                <td><?php echo $value['loan_person_id']; ?></td>
                <td><?php echo $value['loan_record_id']; ?></a></td>
                <td><?php echo LoanFkRecord::$status[$value['status']]; ?></td>
                <td><?php echo sprintf('%.2f', $value['fk_money'] / 100); ?></td>
                <td><?php echo LoanFkRecord::$repay_type[$value['repay_type']]; ?></td>
                <td><?php echo sprintf('%.2f', $value['fee_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['urgent_money'] / 100); ?></td>
                <td><?php echo $value['apr']; ?></td>
                <td><?php echo LoanFkRecord::$operation[$value['repay_operation']]; ?></td>
                <td><?php echo $value['period']; ?></td>
                <td><?php echo $value['audit_person']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['credit_repayment_time']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_fk)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
