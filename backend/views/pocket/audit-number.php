<?php
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
$this->shownav('loan', 'menu_audit_number_list');
$this->showsubmenu('审核订单表');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['pocket/audit-number'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding" style="border: 1px solid">

    <tr class="header">
        <th style="border: 1px solid;text-align: center">审核人名称</th>
        <th style="border: 1px solid;text-align: center">审核订单数量</th>
        <th style="border: 1px solid;text-align: center">开始时间</th>
        <th style="border: 1px solid;text-align: center">结束时间</th>
    </tr>

    <?php foreach($data as $k=> $item):?>
        <tr class="hover">
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $item['operator_name']; ?></td>
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $item['count']; ?></td>
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $start_time?></td>
            <td style="width:20%;border: 1px solid;text-align: center" class="td25"><?php echo $end_time?></td>
        </tr>
    <?php endforeach?>
</table>
<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
