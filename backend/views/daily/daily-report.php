<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\LoanBlackList;

$this->shownav('data_analysis', 'menu_daily_report_list');
?>

<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
统计时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
            <th>日期</th>
            <th>用户总注册量</th>
            <th>当前借款总数量</th>
            <th>当前借款总额(元)</th>
            <th>已经还款总数量</th>
            <th>已经还款总额(元)</th>
            <th> 待收总数量</th>
            <th> 待收总金额(元)</th>
            <th>s1级逾期率(按金额)</th>
            <th>s2级逾期率(按金额)</th>
            <th>s3级逾期率(按金额)</th>
            <th>s1级逾期率(按单数)</th>
            <th>s2级逾期率(按单数)</th>
            <th>s3级逾期率(按单数)</th>
            <th>逾期总金额（元）</th>
        </tr>
        <?php foreach ($daily_data as $value): ?>
            <tr class="hover">
                <td><?php echo $value['date_time']; ?></td>
                <td><?php echo $value['user_total']; ?></td>
                <td><?php echo $value['loan_total']; ?></td>
                <td><?php echo number_format($value['loan_total_money']/100); ?></td>
                <td><?php echo $value['finish_total']; ?></td>
                <td><?php echo number_format($value['finish_total_money']/100); ?></td>
                <td><?php echo $value['live_total']; ?></td>
                <td><?php echo number_format($value['live_total_money']/100); ?></td>
                <td><?php echo empty($value['plan_repayment_total_money'])?"0%":sprintf("%0.2f",($value['overdue_s1_total_money']/$value['loan_total_money'])*100)."%";?></td>
                <td><?php echo empty($value['plan_repayment_total_money'])?"0%":sprintf("%0.2f",($value['overdue_s2_total_money']/$value['loan_total_money'])*100)."%";?></td>
                <td><?php echo empty($value['plan_repayment_total_money'])?"0%":sprintf("%0.2f",($value['overdue_s3_total_money']/$value['loan_total_money'])*100)."%"; ?></td>
                <td><?php echo empty($value['plan_repayment_total'])?"0%":sprintf("%0.2f",($value['overdue_s1_total']/$value['loan_total'])*100)."%";?></td>
                <td><?php echo empty($value['plan_repayment_total'])?"0%":sprintf("%0.2f",($value['overdue_s2_total']/$value['loan_total'])*100)."%";?></td>
                <td><?php echo empty($value['plan_repayment_total'])?"0%":sprintf("%0.2f",($value['overdue_s3_total']/$value['loan_total'])*100)."%";?></td>
                <td><?php echo number_format($value['plan_repayment_total_money']/100); ?></td>
            </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($daily_data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<table>
    <br>
    <br>
    <br>
    <br>
    <tr class="hover"> 由于以前统计的数据有误 ，逾期天数是变动，s1级，s2级，s3级逾期率 从16年 12月15日，开始正常计算，以前的S1,s2,s3数据不做参考</tr>
    <br>
    <tr class="hover"> 当前借款总额（不包括利息） ：指从起始日到现在的所有借款额</tr>
    <br>
    <tr class="hover">  待收总金额（不包括利息）：指 未逾期借款金额 + 已逾期借款金额</tr>
    <br>
    <tr class="hover">  已经还款总额（包括利息）：所有还款总数</tr>
    <br>
    <tr class="hover">s1级(逾期天数1-10天)   ||（不包括利息）  S1逾期率=S1期未还款本金/出借总金额*100%  ||   s1级逾期率（单数）=S1期单数/出借总单数*100% </tr>
    <br>
    <tr class="hover">s2级(逾期天数11-30天)  ||（不包括利息） S2逾期率=S2期未还款本金/出借总金额*100%   || s2级逾期率（单数）=S2期单数/出借总单数*100% </tr>
    <br>
    <tr class="hover">s3级(逾期天数31-60天)  ||（不包括利息） S3逾期率=S3期未还款本金/出借总金额*100%   || s3级逾期率（单数）=S3期单数/出借总单数*100% </tr>
    <br>
    <tr class="hover">  所有逾期总金额（不包括利息）：指 所有逾期出借本金额</tr>
    <br>
</table>
