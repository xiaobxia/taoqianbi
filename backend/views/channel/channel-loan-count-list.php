<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use yii\helpers\Html;

$this->showsubmenu('风控管理', array(
    array('列表', Url::toRoute('channel/loan-count-list'), 1),
));
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" xmlns="http://www.w3.org/1999/html"></script>
<link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<h3 style="color: #3325ff;font-size: 14px">错误信息</h3>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['channel/loan-count-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('start')) ? '' : Yii::$app->request->get('start'); ?>"  name="start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('end')) ? '' : Yii::$app->request->get('end'); ?>"  name="end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), LoanPerson::$current_loan_source); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>渠道</th>
        <th>注册数量</th>
        <th>成功借款数量</th>
        <th>借款总金额</th>
        <th>时间</th>
    </tr>
    <?php $source = LoanPerson::$current_loan_source;?>
    <?php foreach ($data as $value): ?>
        <tr class="hover">
            <td><?php echo isset($source[$value['source_id']]) ? $source[$value['source_id']] : ''; ?></td>
            <td><?php echo $value['reg_num']; ?></td>
            <td><?php echo $value['loan_num']; ?></td>
            <td><?php echo $value['loan_money']/100; ?></td>
            <td><?php echo $value['date_time']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>
