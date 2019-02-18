<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
$rate = Yii::$app->request->get('from_st','0') ? 1.1 : 1;
?>
<style>
    table th{text-align: center}
    table td{text-align: center}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()+86400) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<?php if($channel!=1){?>
来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
<?php }else{?>
    来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), array('prompt'=>UserLoanOrder::$sub_order_type[$sub_order_type])); ?>&nbsp;
<?php }?>
<input type="submit" name="search_submit" value="过滤" class="btn">
<input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
<?php ActiveForm::end(); ?>

<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th rowspan="2">日期</th>
            <th colspan="3" style="text-align:center;">所有用户</th>
            <th colspan="3" style="text-align:center;color:red">老用户</th>
            <th colspan="3" style="text-align:center;color:blue">新用户</th>
            <!--                <th colspan="3" style="text-align:center;">金额</th>-->
        </tr>
        <tr class="header">
            <th>到期金额</th>
            <!-- <th>逾期金额</th> -->
            <th>还款金额</th>
            <!-- <th>逾期率</th> -->
            <th>还款率</th>
            <!-- <th>7天期限逾期率</th> -->
            <!-- <th>14天期限逾期率</th> -->
            <th style="text-align:center;color:red">到期金额</th>
            <!-- <th style="text-align:center;color:red">逾期金额</th> -->
            <th style="text-align:center;color:red">还款金额</th>
            <!-- <th style="text-align:center;color:red">逾期率</th> -->
            <th style="text-align:center;color:red">还款率</th>
            <th style="text-align:center;color:blue">到期金额</th>
            <!-- <th style="text-align:center;color:blue">逾期金额</th> -->
            <th style="text-align:center;color:blue">还款金额</th>
            <!-- <th style="text-align:center;color:blue">逾期率</th> -->
            <th style="text-align:center;color:blue">还款率</th>
        </tr>
        <?php foreach ($data as $key=> $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $key; ?></td>
                <td class="td25"><?php echo isset($value['success_money'])?floor($rate*$value['success_money']):0; ?></td>
                <!-- <td class="td25"><?php echo isset($value['dc_money'])?floor($rate*$value['dc_money']):0; ?></td>  -->
                <td class="td25"><?php echo $value['repay_money']; ?></td>
                <!-- <td class="td25"><?php echo $value['conversion_rate']; ?></td> -->
                <td class="td25"><?php echo $value['repay_rate']; ?></td>
                <!-- <td class="td25"><?php echo $value['conversion_rate_new_7']; ?></td> -->
                <!-- <td class="td25"><?php echo $value['conversion_rate_new_14']; ?></td> -->

                <td class="td25" style="text-align:center;color:red"><?php echo isset($value['success_money_old'])?floor($rate*$value['success_money_old']):0; ?></td>
                <!-- <td class="td25" style="text-align:center;color:red"><?php echo isset($value['dc_money_old'])?floor($rate*$value['dc_money_old']):0; ?></td> -->
                <td class="td25" style="text-align:center;color:red"><?php echo $value['true_repay_money_old']; ?></td>
                <!-- <td class="td25" style="text-align:center;color:red"><?php echo $value['conversion_rate_old']; ?></td> -->
                <td class="td25" style="text-align:center;color:red"><?php echo $value['repay_rate_old']; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['success_money_new'])?floor($rate*$value['success_money_new']):0; ?></td>
                <!-- <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['dc_money_new'])?floor($rate*$value['dc_money_new']):0; ?></td>-->
                <td class="td25" style="text-align:center;color:blue"><?php echo $value['true_repay_money_new']; ?></td>
                <!-- <td class="td25" style="text-align:center;color:blue"><?php echo $value['conversion_rate_new']; ?></td> -->
                <td class="td25" style="text-align:center;color:blue"><?php echo $value['repay_rate_new']; ?></td>
                <!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_1000']; ?><!--</td>-->
                <!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_500']; ?><!--</td>-->
                <!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_200']; ?><!--</td>-->

            </tr>
        <?php endforeach; ?>
        <tr class="hover">
            <td class="td25"><?php echo '总计' ?></td>
            <td class="td25"><?php echo floor($rate*$total_data['all_loan_money']); ?></td>
            <!-- <td class="td25"><?php echo floor($rate*$total_data['all_overdue_money']); ?></td> -->
            <td class="td25"><?php echo $total_data['all_repayment_money']; ?></td>
            <!-- <td class="td25"><?php echo $total_data['all_overdue_rate']; ?></td>  -->
            <td class="td25"><?php echo $total_data['all_repayment_rate']; ?></td>
            <!-- <td class="td25"><?php echo $total_data['new_overdue_rate_14']; ?></td> -->
            <!-- <td class="td25"><?php echo $total_data['new_overdue_rate_7']; ?></td> -->


            <td class="td25" style="text-align:center;color:red"><?php echo floor($rate*$total_data['old_loan_money']); ?></td>
            <!-- <td class="td25" style="text-align:center;color:red"><?php echo floor($rate*$total_data['old_overdue_money']); ?></td> -->
            <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_repayment_money']; ?></td>
            <!-- <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_overdue_rate']; ?></td> -->
            <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_repayment_rate']; ?></td>

            <td class="td25" style="text-align:center;color:blue"><?php echo floor($rate*$total_data['new_loan_money']); ?></td>
            <!-- <td class="td25" style="text-align:center;color:blue"><?php echo floor($rate*$total_data['new_overdue_money']); ?></td> -->
            <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_repayment_money']; ?></td>
            <!-- <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_overdue_rate']; ?></td> -->
            <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_repayment_rate']; ?></td>


            <!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_1000']; ?><!--</td>-->
            <!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_500']; ?><!--</td>-->
            <!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_200']; ?><!--</td>-->
        </tr>
    </table>
    <?php if (empty($data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>

<div class="no-result" style="color: red;">备注：还款金额加了滞纳金，日期越是靠近滞纳金越少，所以跟本金计算的比例会很接近。</div>
<div class="no-result">还款率=还款总金额（真实还款金额）/到期总金额</div>

