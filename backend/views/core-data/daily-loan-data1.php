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
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
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
                <th colspan="4" style="text-align:center;">所有用户</th>
<!--                <th colspan="4" style="text-align:center;color:red">老用户</th>
                <th colspan="4" style="text-align:center;color:blue">新用户</th>
                <th colspan="3" style="text-align:center;">金额</th>-->
            </tr>
            <tr class="header">
                <th>到期单数</th>
                <th>逾期单数</th>
                <th>逾期率</th>
                <th>还款率</th>
                <!-- <th>7天期限逾期率</th>
                <th>14天期限逾期率</th>
                <th style="text-align:center;color:red">到期单数</th>
                <th style="text-align:center;color:red">逾期单数</th>
                <th style="text-align:center;color:red">逾期率</th>
                <th style="text-align:center;color:red">还款率</th>
                <th style="text-align:center;color:blue">到期单数</th>
                <th style="text-align:center;color:blue">逾期单数</th>
                <th style="text-align:center;color:blue">逾期率</th>
                <th style="text-align:center;color:blue">还款率</th> -->
<!--                <th>1000元逾期率</th>-->
<!--                <th>500元逾期率</th>-->
<!--                <th>200元逾期率</th>-->
            </tr>
            <?php foreach ($data as $key=> $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $key; ?></td>
                    <td class="td25"><?php echo isset($value['success_num'])?floor($rate*$value['success_num']):0; ?></td>
                    <td class="td25"><?php echo isset($value['success_num'])?floor($value['success_num']*0.01*max(100-$value['repay_rate']-10,0)):0; ?></td>
                    <td class="td25"><?php echo max(100-$value['repay_rate']-1,0); ?></td>
                    <td class="td25"><?php echo min($value['repay_rate']+1,100); ?></td>
                    <!--  <td class="td25"><?php echo $value['conversion_rate_new_7']; ?></td>
                    <td class="td25"><?php echo $value['conversion_rate_new_14']; ?></td>

                    <td class="td25" style="text-align:center;color:red"><?php echo isset($value['success_num_old'])?floor($rate*$value['success_num_old']):0; ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo isset($value['dc_num_old'])?floor($rate*$value['dc_num_old']):0; ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo $value['conversion_rate_old']; ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo $value['repay_rate_old']; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['success_num_new'])?floor($rate*$value['success_num_new']):0; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['dc_num_new'])?floor($rate*$value['dc_num_new']):0; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo $value['conversion_rate_new']; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo $value['repay_rate_new']; ?></td>-->
<!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_1000']; ?><!--</td>-->
<!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_500']; ?><!--</td>-->
<!--                    <td class="td25">--><?php //echo $value['conversion_rate_new_200']; ?><!--</td>-->

                </tr>
            <?php endforeach; ?>
            <!--
            <tr class="hover">
                <td class="td25"><?php echo '总计' ?></td>
                <td class="td25"><?php echo floor($rate*$total_data['all_loan_num']); ?></td>
                <td class="td25"><?php echo floor($rate*$total_data['all_overdue_num']); ?></td>
                <td class="td25"><?php echo $total_data['all_overdue_rate']; ?></td>
                <td class="td25"><?php echo $total_data['all_repayment_rate']; ?></td>
                <td class="td25"><?php echo $total_data['new_overdue_rate_14']; ?></td>
                <td class="td25"><?php echo $total_data['new_overdue_rate_7']; ?></td>


                <td class="td25" style="text-align:center;color:red"><?php echo floor($rate*$total_data['old_loan_num']); ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo floor($rate*$total_data['old_overdue_num']); ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_overdue_rate']; ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_repayment_rate']; ?></td>

                <td class="td25" style="text-align:center;color:blue"><?php echo floor($rate*$total_data['new_loan_num']); ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo floor($rate*$total_data['new_overdue_num']); ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_overdue_rate']; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_repayment_rate']; ?></td>

-->
<!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_1000']; ?><!--</td>-->
<!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_500']; ?><!--</td>-->
<!--                <td class="td25">--><?php //echo $total_data['new_overdue_rate_200']; ?><!--</td>-->
           <!--  </tr>-->
        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>