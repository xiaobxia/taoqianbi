<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use yii\helpers\Html;

/**
 * @var backend\components\View $this 当月分渠道各种利率视图
 */
?>
<title>每日还款分析</title>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get','action' => ['core-data/repay-rates-list'],  'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>

<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>日期</th>

             <th>总笔数</th>
            <th>总额度</th>

            <th>提前(0~2)/占比/金额</th>
            <th>提前(非0~2)/占比/金额</th>

            <th>正常/占比/金额</th>

            <th>逾期S1/占比/金额</th>
            <th>逾期S2/占比/金额</th>
            <th>逾期S3/占比/金额</th>

            <th>建议拒绝/占比/金额</th>


        </tr>
<?php foreach ($data as $key => $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['date']; ?></td>

                <td class="td25"><?php echo $value['repay_total_num']; ?></td>
                <td class="td25"><?php echo sprintf("%0.2f", $value['repay_total_money'] / 100); ?></td>

                <td class="td25"><?php echo $value['repay_twoday_num']; ?>/<?php echo bcdiv($value['repay_twoday_num'],$value['repay_total_num'],4)*100 ; ?>% /<?php echo sprintf("%0.2f", $value['repay_twoday_money'] / 100); ?> </td>
                <td class="td25"><?php echo $value['repay_someday_num']; ?>/<?php echo bcdiv($value['repay_someday_num'],$value['repay_total_num'],4)*100 ; ?>%  /<?php echo sprintf("%0.2f", $value['repay_someday_money'] / 100); ?> </td>

                <td class="td25"><?php echo $value['repay_normal_num']; ?>/<?php echo bcdiv($value['repay_normal_num'],$value['repay_total_num'],4)*100 ; ?>%  /<?php echo sprintf("%0.2f", $value['repay_normal_money'] / 100); ?> </td>

                <td class="td25"><?php echo $value['repay_s1_num']; ?>/<?php echo bcdiv($value['repay_s1_num'],$value['repay_total_num'],4)*100 ; ?>% /<?php echo sprintf("%0.2f", $value['repay_s1_money'] / 100); ?> </td>
                <td class="td25"><?php echo $value['repay_s2_num']; ?>/<?php echo bcdiv($value['repay_s2_num'],$value['repay_total_num'],4)*100 ; ?>% /<?php echo sprintf("%0.2f", $value['repay_s2_money'] / 100); ?> </td>
                <td class="td25"><?php echo $value['repay_s3_num']; ?>/<?php echo bcdiv($value['repay_s3_num'],$value['repay_total_num'],4)*100 ; ?>% /<?php echo sprintf("%0.2f", $value['repay_s3_money'] / 100); ?> </td>
                <td class="td25"><?php echo $value['repay_refuse_num']; ?>/<?php echo bcdiv($value['repay_refuse_num'],$value['repay_total_num'],4)*100 ; ?>% /<?php echo sprintf("%0.2f", $value['repay_refuse_money'] / 100); ?> </td>



            </tr>
    <?php endforeach; ?>
    </table>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?>
    <?php if (empty($data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<div class="no-result" style="color: red;">备注：</div>

<div class="no-result">更新时间：每日5点更新一次到今天零点的数据，记录后数据留存不变动，可筛选看历史数据</div>

<div class="no-result">总笔数：当前平台所有还款总笔数 </div>

<div class="no-result">总额度：当前平台所有还款总金额（用户实际还款金额） </div>

<div class="no-result">提前(0~2)/占比/金额：累计在借款后0~2天内提前还款的笔数 / 在所有笔数中的占比 / 相应金额</div>

<div class="no-result">提前(非0~2)/占比/金额：累计在借款后非0~2天内提前还款的笔数 / 在所有笔数中的占比 / 相应金额</div>

<div class="no-result">正常/占比/金额：累计正常还款的笔数 / 在所有笔数中的占比 / 相应金额</div>

<div class="no-result">逾期S1/占比/金额：累计在逾期S1内还款的笔数 / 在所有笔数中的占比 / 相应金额</div>

<div class="no-result">逾期S2/占比/金额：累计在逾期S2内还款的笔数 / 在所有笔数中的占比 / 相应金额</div>

<div class="no-result">逾期S3/占比/金额：累计在逾期S3内还款的笔数 / 在所有笔数中的占比 / 相应金额 </div>

<div class="no-result">建议拒绝/占比/金额：还款后被催收标记为下次拒绝再借款的笔数 / 在所有笔数中的占比 / 相应金</div>

