<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\LoanPerson;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
$rate = Yii::$app->request->get('from_st','0') ? 1.1 : 1;
$session = Yii::$app ->session;
?>
<p style="color: red;font-size: medium">每日复借情况</p>
<!--<button id="change_tag" class="btn change_tag">点击切换显示模式</button>-->
<div id="tb_data">
    <?php $form = ActiveForm::begin(['method' => "get",'action' => ['core-data/day-again-loan-statistics'],'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    显示日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('date_start')) ? date("Y-m-d", time()-3*86400) : Yii::$app->request->get('date_start'); ?>"  id="date_start" name="date_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('date_end')) ? date("Y-m-d", time()-86400) : Yii::$app->request->get('date_end'); ?>"  name="date_end" id="date_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    &nbsp;
    <input type="submit" name="search_submit" value="筛选" class="btn"  >

    <?php ActiveForm::end(); ?>
    <br/>

    <table class="tb tb2 fixpadding" id="tb">
        <tr class="header">
            <th>日期</th>
            <th>当天成功借款用户</th>
            <!--            <th> 当天复借人数/占比/成功/占比</th>-->
            <!--            <th>1~5天内复借人数/占比/成功/占比</th>-->
            <!--            <th>6~10天内复借人数/占比/成功/占比</th>-->
            <!--            <th>11~15天复借人数/占比/成功/占比</th>-->
            <!--            <th>15天以上复借人数/占比/成功人数/占比</th>-->
            <th> 当天复借人数/占比</th>
            <th>7天内复借人数/占比</th>
            <th>14天内复借人数/占比</th>
            <th>30天内复借人数/占比</th>
            <th>30天以上复借人数/占比</th>
        </tr>


        <!--  显示每日流失情况数据   -->
        <?php foreach($day_lose_data as $rows):?>
            <tr class="hover">
                <td class="td25"><?php echo $rows['date_time']?></td>
                <td class="td25"><?php echo $rows['repay_num']?></td>
                <td class="td25"><?php echo empty($rows['repay_num'])? 0 : $rows['loan_again_success_num_0'] .'/'. sprintf("%0.2f", ($rows['loan_again_success_num_0']/$rows['repay_num'])*100) .'%'?></td>
                <td class="td25"><?php echo empty($rows['repay_num'])? 0 : $rows['loan_again_success_num_5'] .'/'. sprintf("%0.2f", ($rows['loan_again_success_num_5']/$rows['repay_num'])*100) .'%'?></td>
                <td class="td25"><?php echo empty($rows['repay_num'])? 0 : $rows['loan_again_success_num_10'] .'/'. sprintf("%0.2f", ($rows['loan_again_success_num_10']/$rows['repay_num'])*100) .'%'?></td>
                <td class="td25"><?php echo empty($rows['repay_num'])? 0 : $rows['loan_again_success_num_15'] .'/'. sprintf("%0.2f", ($rows['loan_again_success_num_15']/$rows['repay_num'])*100) .'%'?></td>
                <td class="td25"><?php echo empty($rows['repay_num'])? 0 : $rows['loan_again_success_num_40'] .'/'. sprintf("%0.2f", ($rows['loan_again_success_num_40']/$rows['repay_num'])*100) .'%'?></td>

            </tr>
        <?php endforeach; ?>

    </table>
    <?php if (empty($day_lose_data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</div>

<br>
<br>
<p>每日复借情况：以当天成功借款的用户为基础，计算这些用户上一笔还款的时间与此次借款的的间隔的时间分布，以此来计算流失率</p>
<p>当天成功借款用户：以查看日期当天成功借款的用户为基础：总人数A</p>
<p>当天复借人数：当天成功借款用户的上一笔还款时间与此次借款的时间间隔在一天之内</p>
<p>当天复借人数/占比：A中当天复借的人数B/在A中的占比</p>
<p>7天内复借人数：当天成功借款的用户上一笔还款时间与此次借款的时间间隔为7天</p>
<p>7天内复借人数/占比/成功/占比：A中7天内复借的人数C/在A中的占比</p>
<p>后面的数据同上</p>










