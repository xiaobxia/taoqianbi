<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\LoanPerson;
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
<title>每日还款金额</title>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action'=>Url::toRoute(['core-data/day-data-statistics','type'=>'loan_money','search_date'=>'2']), 'options' => ['style' => 'margin-top:5px;']]); ?>
<?php echo Html::dropDownList('search_date', Yii::$app->getRequest()->get('search_date', '2'), array(1=>'借款日期',2=>'还款日期')) ?>
<input type="text" value="<?php echo empty(Yii::$app->request->get('begin_created_at')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('begin_created_at'); ?>"  name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:false,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('end_created_at')) ? date("Y-m-d", time()+86400*2) : Yii::$app->request->get('end_created_at'); ?>"  name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:false,readOnly:true})">&nbsp;
&nbsp;&nbsp;<input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">&nbsp;
APP来源：<?php echo Html::dropDownList('source_type', Yii::$app->getRequest()->get('source_type', ''), LoanPerson::$app_loan_source); ?>&nbsp;

<input type="submit" name="search_submit" value="过滤" class="btn">
&nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportmoney');return true;" class="btn">
&nbsp;&nbsp;最后更新时间：<?php echo $update_time;?>
<?php ActiveForm::end(); ?>

<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th colspan="2" style="text-align:center;border-right:1px solid #A9A9A9;">借款信息</th>
            <th colspan="7" style="text-align:center;border-right:1px solid #A9A9A9;">所有用户</th>
            <th colspan="5" style="text-align:center;color:blue;border-right:1px solid blue;">新用户</th>
            <th colspan="5" style="text-align:center;color:red;border-right:1px solid red;">老用户</th>
        </tr>
        <tr class="header">
            <!-- 借款信息 -->
            <th>借款日</th>
            <th style="border-right:1px solid #A9A9A9;">还款日</th>

            <!-- 所有用户 -->
            <th>到期金额</th>
            <th>正常还款</th>
            <th>已还款</th>
            <th>首逾</th>
            <th>还款率</th>
            <th>逾期金额</th>
            <th style="border-right:1px solid #A9A9A9;">逾期率</th>

            <!-- 新用户 -->
            <th style="text-align:center;color:blue">到期金额</th>
            <th style="text-align:center;color:blue">首逾</th>
            <th style="text-align:center;color:blue">还款率</th>
            <th style="text-align:center;color:blue;">逾期金额</th>
            <th style="text-align:center;color:blue;border-right:1px solid blue;">逾期率</th>

            <!-- 老用户 -->
            <th style="text-align:center;color:red">到期金额</th>
            <th style="text-align:center;color:red">首逾</th>
            <th style="text-align:center;color:red">还款率</th>
            <th style="text-align:center;color:red">逾期金额</th>
            <th style="text-align:center;color:red;border-right:1px solid red;">逾期率</th>
        </tr>
        <tr class="hover">
            <!-- 借款信息 -->
            <td>汇总信息</td>
            <td style="border-right:1px solid #A9A9A9;"></td>
            <!-- 所有用户 -->
            <td class="td25"><?php echo isset($total_info['expire_money_0'])?number_format(floor($total_info['expire_money_0'])/100):0; ?></td>
            <td class="td25"><?php echo (!empty($total_info['repay_zc_money_0']))?number_format($total_info['repay_zc_money_0']/100):0; ?></a></td>
            <td class="td25"><?php echo isset($total_info['repay_money_0'])?number_format(floor($total_info['repay_money_0'])/100):0; ?></td>
            <td class="td25"><?php echo (!empty($total_info['t_expire_money_0'])) ? sprintf("%0.2f",($total_info['t_expire_money_0']-$total_info['t_repay_zc_money_0'])/$total_info['t_expire_money_0']*100)."%" : '-'; ?></td>
            <td class="td25"><?php echo (!empty($total_info['expire_money_0'])) ? sprintf("%0.2f",($total_info['repay_money_0']/$total_info['expire_money_0'])*100)."%" : '-'; ?></td>
            <td class="td25"><?php echo (isset($total_info['t_repay_money_0']) && isset($total_info['t_repay_money_0'])) ? number_format(($total_info['t_expire_money_0']-$total_info['t_repay_money_0'])/100) : '-'; ?></td>
            <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo (!empty($total_info['t_expire_money_0'])) ? sprintf("%0.2f",(($total_info['t_expire_money_0']-$total_info['t_repay_money_0'])/$total_info['t_expire_money_0'])*100)."%" : '-'; ?></td>

            <!-- 新用户 -->
            <td class="td25" style="text-align:center;color:blue"><?php echo isset($total_info['expire_money_1']) ? number_format(floor($total_info['expire_money_1'])/100):0; ?></td>
            <td class="td25" style="text-align:center;color:blue"><?php echo (!empty($total_info['t_expire_money_1'])) ? sprintf("%0.2f",($total_info['t_expire_money_1']-$total_info['t_repay_zc_money_1'])/$total_info['t_expire_money_1']*100)."%" : '-'; ?></td>
            <td class="td25" style="text-align:center;color:blue"><?php echo (!empty($total_info['expire_money_1']))? sprintf("%0.2f",($total_info['repay_money_1']/$total_info['expire_money_1'])*100)."%" : '-'; ?></td>
            <td class="td25" style="text-align:center;color:blue"><?php echo (isset($total_info['t_expire_money_1']) && isset($total_info['t_repay_money_1'])) ? number_format(($total_info['t_expire_money_1']-$total_info['t_repay_money_1'])/100) : '-'; ?></td>
            <td class="td25" style="text-align:center;color:blue;border-right:1px solid blue;"><?php echo (!empty($total_info['t_expire_money_1'])) ? sprintf("%0.2f",(($total_info['t_expire_money_1']-$total_info['t_repay_money_1'])/$total_info['t_expire_money_1'])*100)."%" : '-'; ?></td>

            <!-- 老用户 -->
            <td class="td25" style="text-align:center;color:red"><?php echo isset($total_info['expire_money_2']) ? number_format(floor($total_info['expire_money_2'])/100):0; ?></td>
            <td class="td25" style="text-align:center;color:red"><?php echo (!empty($total_info['t_expire_money_2'])) ? sprintf("%0.2f",($total_info['t_expire_money_2']-$total_info['t_repay_zc_money_2'])/$total_info['t_expire_money_2']*100)."%" : '-'; ?></td>
            <td class="td25" style="text-align:center;color:red"><?php echo (!empty($total_info['expire_money_2']))? sprintf("%0.2f",($total_info['repay_money_2']/$total_info['expire_money_2'])*100)."%" : '-'; ?></td>
            <td class="td25" style="text-align:center;color:red"><?php echo (isset($total_info['t_expire_money_2']) && isset($total_info['t_repay_money_2'])) ? number_format(($total_info['t_expire_money_2']-$total_info['t_repay_money_2'])/100) : '-'; ?></td>
            <td class="td25" style="text-align:center;color:red;border-right:1px solid red;"><?php echo (!empty($total_info['t_expire_money_2'])) ? sprintf("%0.2f",(($total_info['t_expire_money_2']-$total_info['t_repay_money_2'])/$total_info['t_expire_money_2'])*100)."%" : '-'; ?></td>
        </tr>
        <?php foreach ($info as $key=> $value): ?>
            <tr class="hover" style="<?php echo date('w', $value['unix_time_key']) == 0 || date('w', $value['unix_time_key']) == 6 ?'background:#edecf9':'';?>">
                <!-- 借款信息 -->
                <?php $now_date_time = strtotime(date('Y-m-d', time()));?>
                <td class="td25"><?php echo $value['created_time']; ?></td>
                <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo date('n-j',strtotime($key)); ?></td>

                <!-- 所有用户 -->
                <td class="td25"><a href="<?php echo Url::toRoute(['pocket/pocket-list','plan_fee_time'=>$value['time_key'],'page_type'=>'3']); ?>" target="_blank"><?php echo isset($value['expire_money_0'])?number_format(floor($value['expire_money_0'])/100):0; ?></a></td>
                <td class="td25"><?php echo number_format($value['repay_zc_money_0']/100); ?></a></td>
                <td class="td25"><a href="<?php echo Url::toRoute(['pocket/pocket-list','plan_fee_time'=>$value['time_key'],'_status'=>4,'page_type'=>'3']); ?>" target="_blank"><?php echo isset($value['repay_money_0'])?number_format(floor($value['repay_money_0'])/100):0; ?></a></td>
                <td class="td25"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['expire_money_0'])?'-':sprintf("%0.2f",($value['expire_money_0']-$value['repay_zc_money_0'])/$value['expire_money_0']*100)."%"); ?></td>
                <td class="td25"><?php echo empty($value['expire_money_0'])?'-':sprintf("%0.2f",($value['repay_money_0']/$value['expire_money_0'])*100)."%"; ?></td>
                <td class="td25"><a href="<?php echo Url::toRoute(['pocket/pocket-list','plan_fee_time'=>$value['time_key'],'overdue_day'=>1,'page_type'=>'3']); ?>" target="_blank"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : number_format(($value['expire_money_0']-$value['repay_money_0'])/100); ?></a></td>
                <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : empty($value['expire_money_0'])?'-':sprintf("%0.2f",(($value['expire_money_0']-$value['repay_money_0'])/$value['expire_money_0'])*100)."%"; ?></td>

                <!-- 新用户 -->
                <td class="td25" style="text-align:center;color:blue"><?php echo isset($value['expire_money_1'])?number_format(floor($value['expire_money_1'])/100):0; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['expire_money_1'])?'0%':sprintf("%0.2f",($value['expire_money_1']-$value['repay_zc_money_1'])/$value['expire_money_1']*100)."%"); ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo empty($value['expire_money_1'])?'-':sprintf("%0.2f",($value['repay_money_1']/$value['expire_money_1'])*100)."%"; ?></td>
                <td class="td25" style="text-align:center;color:blue"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : ((isset($value['expire_money_1']) && isset($value['repay_money_1'])) ? number_format(($value['expire_money_1']-$value['repay_money_1'])/100) : 0) ; ?></td>
                <td class="td25" style="text-align:center;color:blue;border-right:1px solid blue;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : empty($value['expire_money_1'])?'-':sprintf("%0.2f",(($value['expire_money_1']-$value['repay_money_1'])/$value['expire_money_1'])*100)."%"; ?></td>

                <!-- 老用户 -->
                <td class="td25" style="text-align:center;color:red"><?php echo isset($value['expire_money_2'])?number_format(floor($value['expire_money_2'])/100):0; ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : (empty($value['expire_money_2'])?'0%':sprintf("%0.2f",($value['expire_money_2']-$value['repay_zc_money_2'])/$value['expire_money_2']*100)."%"); ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo empty($value['expire_money_2'])?'-':sprintf("%0.2f",($value['repay_money_2']/$value['expire_money_2'])*100)."%"; ?></td>
                <td class="td25" style="text-align:center;color:red"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' : ((isset($value['expire_money_2']) && isset($value['repay_money_2'])) ? number_format(($value['expire_money_2']-$value['repay_money_2'])/100) : 0) ; ?></td>
                <td class="td25" style="text-align:center;color:red;border-right:1px solid red;"><?php echo ($value['unix_time_key'] >= $now_date_time) ? '-' :empty($value['expire_money_2'])?'-': sprintf("%0.2f",(($value['expire_money_2']-$value['repay_money_2'])/$value['expire_money_2'])*100)."%"; ?></td>
            </tr>
        <?php endforeach; ?>

    </table>
    <?php if (empty($info)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
<!--    --><?php //echo LinkPager::widget(['pagination' => $pages]); ?>
</form>
<br>
<p>默认显示的数据为过去七天到期金额的数据</p>
<p>逾期金额（当日为空）:当天到期金额中未还款的金额</p>
<p>逾期率（当日为空）：逾期金额/当天到期金额</p>
<p>还款率：当天到期金额中已还款的金额/当天的到期金额</p>
<p>首逾（当日为空）：(到期金额-正常还款金额)/到期金额</p>
<br/>
<p>"正常还款"的计算时间会按照催收订单脚本执行时间(每天凌晨4:10)往后延长</p>
<p>当天至14天以后的数据10分钟更新一次</p>
<p>7天前15分钟更新一次</p>
<p>7-30天以前的数据2小时更新一次</p>
<p>30-120天以前的数据一天更新一次（每天凌晨3点更新）</p>
<p>“首逾”，“逾期金额”，“逾期率”汇总只统计小于今日的数据</p>
