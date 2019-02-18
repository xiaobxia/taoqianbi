<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
$rate = 1;
?>
<style>
    table th{text-align: center}
    table td{text-align: center}
</style>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get',  'options' => ['style' => 'margin-top:5px;']]); ?>
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-2*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;

    <input type="submit" name="search_submit" value="过滤" class="btn">
    <input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th >日期</th>
                <th>放款单数</th>
                <th>7天期限放款单数</th>
                <th>14天期限放款单数</th>
                <th>放款总额</th>
                <th>7天期限放款总额</th>
                <th>14天期限放款总额</th>
            </tr>
            <?php foreach ($data as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['date_time']; ?></td>
                    <td class="td25"><?php echo floor($rate*$value['loan_num']); ?></td>
                    <td class="td25"><?php echo floor($rate*$value['loan_num_7']); ?></td>
                    <td class="td25"><?php echo floor($rate*$value['loan_num_14']); ?></td>
                    <td class="td25"><?php echo sprintf("%0.0f",$rate*$value['loan_money']/100); ?></td>
                    <td class="td25"><?php echo sprintf("%0.0f",$rate*$value['loan_money_7']/100); ?></td>
                    <td class="td25"><?php echo sprintf("%0.0f",$rate*$value['loan_money_14']/100); ?></td>
                   </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>

<?php //echo LinkPager::widget(['pagination' => $pages]); ?>

    <table frame="above" align="right">
        <tr>
            <td align="center" style="color: red;">放款单数总计：<?php echo floor($rate*$total_loan_num) ?></td>
        </tr>
        <tr>
            <td align="center" style="color: red;">放款总额总计：<?php echo sprintf("%.0f",$rate*$total_loan_money / 100) ?></td>
        </tr>
    </table>
