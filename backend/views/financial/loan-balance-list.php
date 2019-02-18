<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\fund\LoanFund;

$this->shownav('financial', 'menu_financial_loan_balance_list');

$this->showsubmenu('贷款余额统计表', array(
    array('贷款余额统计', Url::toRoute('financial/loan-balance-list'), 1),

));

?>

<style>
.tb2 th{ font-size: 12px;}
table th{text-align: center}
table td{text-align: center}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：
    <input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
   至<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
&nbsp;&nbsp;资方<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), LoanFund::$loan_source); ?>
<input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;
更新时间：<?php echo $update_time;?> &nbsp;(每两小时更新一次)
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>日期</th>
                <th>贷款余额</th>
                <th>未到期余额</th>
                <th>续期余额</th>
                <th>逾期余额</th>
                <th>M0逾期本金</th>
                <th>M1逾期本金</th>
                <th>M2逾期本金</th>
                <th>M3逾期本金</th>
                <th>应收滞纳金</th>
                <th>实收滞纳金</th>
                <th>滞纳金减免总额</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $value): ?>
                    <tr>
                        <td><?php echo empty($value['date']) ? '--' : $value['date'];?></td>
                        <td><?php echo empty($value['loan_balance'])?'--':number_format($value['loan_balance']/100,2);?></td>
                        <td><?php echo empty($value['prematurity_balance'])?'--':number_format($value['prematurity_balance']/100,2);?></td>
                        <td><?php echo empty($value['rollover_balance'])?'--':number_format($value['rollover_balance']/100,2);?></td>
                        <td><?php echo empty($value['overdue_balance'])?'--':number_format($value['overdue_balance']/100,2);?></td>
                        <td><?php echo empty($value['overdue_moneym0'])?'--':number_format($value['overdue_moneym0']/100,2);?></td>
                        <td><?php echo empty($value['overdue_moneym1'])?'--':number_format($value['overdue_moneym1']/100,2);?></td>
                        <td><?php echo empty($value['overdue_moneym2'])?'--':number_format($value['overdue_moneym2']/100,2);?></td>
                        <td><?php echo empty($value['overdue_moneym3'])?'--':number_format($value['overdue_moneym3']/100,2);?></td>
                        <td><?php echo empty($value['late_fee'])?'--':number_format($value['late_fee']/100,2);?></td>
                        <td><?php echo empty($value['net_late_fee'])?'--':number_format($value['net_late_fee']/100,2);?></td>
                        <td><?php echo empty($value['derate_late_fee'])?'--':number_format(($value['late_fee']-$value['net_late_fee'])/100,2);?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<br>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<br>
<br>
<br>
<p style="color: red">备注</p>
<p>贷款余额：截止当前，借款人尚未归还的贷款总额（不含滞纳金）</p>
<p>未到期余额：截至当前，未到期且未还款的总金额</p>
<p>续期余额：截至当前，到期应还借款，申请续期服务后延期还款的总金额</p>
<p>逾期余额：截止当前，到期未还款的本金（不含滞纳金）</p>
<p>M0逾期本金：逾期1-30天的未还本金（包含综合服务费，不包含滞纳金）</p>
<p>M1逾期本金：逾期31-60天的未还本金（包含综合服务费，不包含滞纳金）</p>
<p>M2逾期本金：逾期61-90天的未还本金（包含综合服务费，不包含滞纳金）</p>
<p>M3逾期本金：逾期91天及以上的未还本金（包含综合服务费，不包含滞纳金）</p>



