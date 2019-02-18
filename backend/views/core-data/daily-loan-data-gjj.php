<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
?>
<style>
    table th{text-align: center}
    table td{text-align: center}
</style>
<title>每日公积金还款</title>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action'=>Url::toRoute(['core-data/daily-loan-data-gjj']), 'options' => ['style' => 'margin-top:5px;']]); ?>
    <?php echo Html::dropDownList('search_date', Yii::$app->getRequest()->get('search_date', '2'), array(1=>'借款日期',2=>'还款日期')) ?>
    <input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:false,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:false,readOnly:true})">&nbsp;
    <?php if($channel!=1){?>
    来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
    <?php }else{?>
    来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), array('prompt'=>UserLoanOrder::$sub_order_type[$sub_order_type])); ?>&nbsp;
    <?php }?>
    <input type="submit" name="search_submit" value="过滤" class="btn">
    &nbsp;&nbsp;<input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
    &nbsp;&nbsp;最后更新时间：<?php echo date("n-j H:i", time());?>
<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th colspan="2" style="text-align:center;border-right:1px solid #A9A9A9;">公积金还款信息</th>
                <th colspan="10" style="text-align:center;border-right:1px solid #A9A9A9;">公积金用户</th>
                <th colspan="6" style="text-align:center;color:blue;border-right:1px solid blue;">公积金新用户</th>
                <th colspan="6" style="text-align:center;color:red;border-right:1px solid red;">公积金老用户</th>
            </tr>
            <tr class="header">
                <!-- 公积金还款信息 -->
                <th>借款日</th>
                <th style="border-right:1px solid #A9A9A9;">还款日</th>

                <!-- 公积金用户 -->
                <th>到期单数</th>
                <th>到期金额</th>
                <th>正常还款单数</th>
                <th>正常还款金额</th>
                <th>已还款单数</th>
                <th>已还款金额</th>
                <th>首逾</th>
                <th>还款率</th>
                <th>逾期数</th>
                <th style="border-right:1px solid #A9A9A9;">逾期率</th>

                <!--公积金新用户-->
                <th style="text-align:center;color:blue">到期单数</th>
                <th style="text-align:center;color:blue">到期金额</th>
                <th style="text-align:center;color:blue">首逾</th>
                <th style="text-align:center;color:blue">还款率</th>
                <th style="text-align:center;color:blue;">逾期单数</th>
                <th style="text-align:center;color:blue;border-right:1px solid blue;">逾期率</th>

                <!--公积金老用户-->
                <th style="text-align:center;color:red">到期单数</th>
                <th style="text-align:center;color:red">到期金额</th>
                <th style="text-align:center;color:red">首逾</th>
                <th style="text-align:center;color:red">还款率</th>
                <th style="text-align:center;color:red">逾期单数</th>
                <th style="text-align:center;color:red;border-right:1px solid red;">逾期率</th>
            </tr>
            <tr class="hover">
                <!-- 公积金还款信息 -->
                <td class="td25"><?php echo '总计' ?></td>
                <td class="td25" style="border-right:1px solid #A9A9A9;"></td>
                <!-- 公积金用户 -->
                <td class="td25"><?php echo $total_data['all_loan_num']; ?></td>
                <td class="td25"><?php echo number_format($total_data['all_loan_money'],2);?></td>
                <td class="td25"><?php echo $total_data['all_zc_num']; ?></td>
                <td class="td25"><?php echo number_format($total_data['all_zc_money'],2); ?></td>
                <td class="td25"><?php echo $total_data['all_repayment_num']; ?></td>
                <td class="td25"><?php echo number_format($total_data['all_repayment_money'],2); ?></td>
                <td class="td25"><?php echo $total_data['all_rc_rate']; ?></td>
                <td class="td25"><?php echo $total_data['all_repayment_rate']; ?></td>
                <td class="td25"><?php echo $total_data['all_overdue_num']; ?></td>
                <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo $total_data['all_overdue_rate']; ?></td>

                <!-- 新用户 -->
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_loan_num']; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo number_format($total_data['new_loan_money'],2); ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['all_rc_rate_new'] ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_repayment_rate']; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo $total_data['new_overdue_num']; ?></td>
                <td class="td25" style="text-align:center;color:blue;border-right:1px solid blue;"><?php echo $total_data['new_overdue_rate']; ?></td>

                <!-- 老用户 -->
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_loan_num']; ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo number_format($total_data['old_loan_money'],2); ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['all_rc_rate_old'] ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_repayment_rate']; ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo $total_data['old_overdue_num']; ?></td>
                <td class="td25" style="text-align:center;color:red;border-right:1px solid red;"><?php echo $total_data['old_overdue_rate']; ?></td>
            </tr>
            <?php foreach ($data as $key=> $value): ?>
                <tr class="hover" style="<?php echo date('w', strtotime($value['create_time'])) == 0 || date('w', strtotime($value['create_time'])) == 6?'background:#3325ff':'';?>">
                    <?php $now_date_time = strtotime(date('Y-m-d', time()));?>
                    <!-- 公积金还款信息 -->
                    <td class="td25"><?php echo $value['create_time']; ?></td>
                    <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo $key; ?></td>

                    <!-- 公积金用户 -->
                    <td class="td25"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>$value['time_key'],'loan_term'=>'14','page_type'=>'3', 'is_gjj' => 1]); ?>"target="_blank"><?php echo isset($value['success_num'])?$value['success_num']:0; ?></a></td>
                    <td class="td25"><?php echo isset($value['success_money'])?number_format($value['success_money'],2):0; ?></td>
                    <td class="td25"><?php echo $value['zc_num']; ?></td>
                    <td class="td25"><?php echo isset($value['zc_money'])?number_format($value['zc_money'],2):0; ?></td>
                    <td class="td25"><?php echo isset($value['repay_num'])?$value['repay_num']:0; ?></td>
                    <td class="td25"><?php echo isset($value['repay_money'])?number_format($value['repay_money'], 2):0; ?></td>
                    <td class="td25"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['success_num'])?'-':sprintf("%0.2f",($value['success_num']-$value['zc_num'])/$value['success_num']*100)."%"); ?></td>
                    <td class="td25"><?php echo $value['repay_rate']; ?></td>
                    <td class="td25"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (isset($value['dc_num'])?$value['dc_num']:0); ?></a></td>
                    <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : $value['conversion_rate']; ?></td>

                    <!-- 新用户 -->
                    <td class="td25" style="text-align:center;color:blue"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>$value['time_key'],'old_user'=>'-1','page_type'=>'3', 'is_gjj' => 1]); ?>"target="_blank"><?php echo isset($value['success_num_new'])?$value['success_num_new']:0; ?></a></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['success_money_new'])?number_format($value['success_money_new'],2):0; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['success_num_new'])?'0%':sprintf("%0.2f",($value['success_num_new']-$value['zc_num_new'])/$value['success_num_new']*100)."%"); ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo $value['repay_rate_new']; ?></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (isset($value['dc_num_new'])?$value['dc_num_new']:0); ?></td>
                    <td class="td25" style="text-align:center;color:blue;border-right:1px solid blue;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : $value['conversion_rate_new']; ?></td>

                    <!-- 老用户 -->
                    <td class="td25" style="text-align:center;color:red"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>$value['time_key'],'loan_term'=>'14','page_type'=>'3','old_user'=>'1', 'is_gjj' => 1]); ?>"target="_blank"><?php echo isset($value['success_num_old'])?$value['success_num_old']:0; ?></a></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo isset($value['success_money_old'])?number_format($value['success_money_old'],2):0; ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['success_num_old'])?'0%':sprintf("%0.2f",($value['success_num_old']-$value['zc_num_old'])/$value['success_num_old']*100)."%"); ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo $value['repay_rate_old']; ?></td>
                    <td class="td25" style="text-align:center;color:red"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (isset($value['dc_num_old'])?$value['dc_num_old']:0); ?></td>
                    <td class="td25" style="text-align:center;color:red;border-right:1px solid red;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : $value['conversion_rate_old']; ?></td>
                </tr>
            <?php endforeach; ?>

        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<br>
<p>默认显示的数据为过去七天到期单数的数据</p>
<p>逾期单数（当日为空）:当天到期单数中未还款的单数</p>
<p>逾期率（当日为空）：逾期单数/当天到期单数</p>
<p>还款率：当天到期单数中已还款的单数/当天的到期单数</p>
<p>首逾（当日为空）：(到期单数-正常还款单数)/到期单数</p>
<p>PS："正常还款"的计算时间会按照催收订单脚本执行时间(每天凌晨4:10)往后延长</p>
