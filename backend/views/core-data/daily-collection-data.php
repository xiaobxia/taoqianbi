<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\helpers\Url;
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
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get','action'=>Url::toRoute(['core-data/day-data-statistics','type'=>'collection_list']),  'options' => ['style' => 'margin-top:5px;']]); ?>
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('begin_created_at')) ? date("Y-m-d", time()-2*86400) : Yii::$app->request->get('begin_created_at'); ?>"  name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('end_created_at')) ? date("Y-m-d", time()) : Yii::$app->request->get('end_created_at'); ?>"  name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;
    <input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcollection');return true;" class="btn">
<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <?php if (empty($info)): ?>
                <div class="no-result">暂无记录</div>
            <?php else: ?>
            <tr class="header">
                <th >日期</th>
                <th>到期单数</th>
                <th>到期金额</th>
                <th>逾期单数</th>
                <th>逾期金额</th>
                <th>逾期已还单数</th>
                <th>逾期已还金额</th>
                <th>更新时间</th>
            </tr>
            <?php foreach ($info as $date=> $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $date; ?></td>
                    <td class="td25"><?php echo $value['expire_num']; ?></td>
                    <td class="td25"><?php echo $value['expire_money']; ?></td>
                    <td class="td25"><?php echo $value['overdue_num']; ?></td>
                    <td class="td25"><?php echo $value['overdue_money']; ?></td>
                    <td class="td25"><?php echo $value['overdue_pay_num']; ?></td>
                    <td class="td25"><?php echo $value['overdue_pay_money']; ?></td>
                    <td class="td25"><?php echo $value['upddated_time']; ?></td>
                   </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>

    </form>

<?php //echo LinkPager::widget(['pagination' => $pages]); ?>


