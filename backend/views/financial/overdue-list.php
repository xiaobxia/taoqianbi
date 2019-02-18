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

$this->shownav('financial', 'menu_financial_overdue_list');

$this->showsubmenu('逾期数据分布', array(
    array('逾期数据分布', Url::toRoute('financial/overdue-list'), 1),

));

?>

<style>
.tb2 th{ font-size: 12px;}
table th{text-align: center}
table td{text-align: center}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM ',alwaysUseStartDate:true,readOnly:true})"/>
    至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM ',alwaysUseStartDate:true,readOnly:true})"/>
    &nbsp;&nbsp;资方<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), LoanFund::$loan_source); ?>
    <input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;
    更新时间：<?php echo $update_time;?> &nbsp;(每两小时更新一次)
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>年月</th>
                <th>借款金额</th>
                <th>续期金额</th>
                <th>还款金额</th>
                <th>还款滞纳金金额</th>
                <th>优惠券抵扣金额</th>
                <th>逾期金额</th>
                <th>逾期1-10天</th>
                <th>逾期11-20天</th>
                <th>逾期21-30天</th>
                <th>逾期31-60天</th>
                <th>逾期61-90天</th>
                <th>逾期90天以上</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $value): ?>
                    <tr>
                        <td><?php echo empty($value['date']) ? '--' : date('Y-m',strtotime($value['date']));?></td>
                        <td><?php echo empty($value['loan_money'])?'--':number_format($value['loan_money']/100,2);?></td>
                        <td><?php echo empty($value['rollover_money'])?'--':number_format($value['rollover_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_money'])?'--':number_format($value['repay_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_late_fee'])?'--':number_format($value['repay_late_fee']/100,2);?></td>
                        <td><?php echo empty($value['coupon_money'])?'--':number_format($value['coupon_money']/100,2);?></td>
                        <td><?php echo empty($value['overdue_money'])?'--':number_format($value['overdue_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s1_money'])?'--':number_format($value['repay_s1_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s2_money'])?'--':number_format($value['repay_s2_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s3_money'])?'--':number_format($value['repay_s3_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s4_money'])?'--':number_format($value['repay_s4_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s5_money'])?'--':number_format($value['repay_s5_money']/100,2);?></td>
                        <td><?php echo empty($value['repay_s6_money'])?'--':number_format($value['repay_s6_money']/100,2);?></td>
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
<p>借款金额：对应月份借款的总金额</p>
<p>续期金额：当月应还金额。申请续期服务后还款的总金额</p>
<p>还款金额：对应月份放款金额里的还款金额（不含优惠券抵扣金额）</p>
<p>逾期金额：对应月份逾期未还款的总额</p>
<p>逾期1-10天：对应月份逾期在1-10天内的未还款总额</p>
<p>逾期11-20天：对应月份逾期在11-20天内的未还款总额</p>


