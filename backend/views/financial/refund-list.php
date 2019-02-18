<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use common\models\FinancialRefundLog;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;

$this->shownav('financial', 'menu_refund_list');
$this->showsubmenu('退款列表', array(
    array('退款列表', Url::toRoute('financial/refund-list'), 1),
    array('添加记录', Url::toRoute('financial/add-refund'), 0),
));

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    入账流水：<input type="text" value="<?php echo Yii::$app->getRequest()->get('in_pay_order', ''); ?>" name="in_pay_order" class="txt" style="width:280px;">&nbsp;
    退款流水：<input type="text" value="<?php echo Yii::$app->getRequest()->get('out_pay_order', ''); ?>" name="out_pay_order" class="txt" style="width:280px;">&nbsp;
    退款账号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('account', ''); ?>" name="account" class="txt" style="width:120px;">&nbsp;
    退款用户名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    <br/>
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), FinancialRefundLog::$status_list, ['prompt' => '所有状态']); ?>&nbsp;
    入账类型：<?php echo Html::dropDownList('in_type', Yii::$app->getRequest()->get('in_type', ''), FinancialRefundLog::$type_list, ['prompt' => '所有状态']); ?>&nbsp;
    退款类型：<?php echo Html::dropDownList('out_type', Yii::$app->getRequest()->get('out_type', ''), FinancialRefundLog::$type_list, ['prompt' => '所有状态']); ?>&nbsp;
    创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_begin', ''); ?>" name="created_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_end', ''); ?>"  name="created_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    退款时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('operation_time_begin', ''); ?>" name="operation_time_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('operation_time_end', ''); ?>"  name="operation_time_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($data)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>退款人姓名</th>
            <th>退款人账号</th>
            <th>入账类型</th>
            <th>入账流水</th>
            <th>入账金额(元)</th>
            <th>退款金额(元)</th>
            <th>退款理由</th>
            <th>审核备注</th>
            <th>出账类型</th>
            <th>出账流水</th>
            <th>状态</th>
            <th>退款发起人</th>
            <th>创建时间</th>
            <th style="width: 150px; text-align: center;">操作</th>
        </tr>
        <?php foreach ($data as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo htmlentities($value['name']); ?></td>
                <td><?php echo htmlentities($value['account']); ?></td>
                <td><?php echo FinancialRefundLog::$type_list[$value['in_type']]; ?></td>
                <td><?php echo htmlentities($value['in_pay_order']); ?></td>
                <td><?php echo $value['in_money']/100; ?></td>
                <td><?php echo $value['out_money']/100; ?></td>
                <td><?php echo htmlentities($value['remark']); ?></td>
                <td><?php echo $value['remark_2']; ?></td>
                <td><?php echo isset(FinancialRefundLog::$type_list[$value['out_type']]) ? FinancialRefundLog::$type_list[$value['out_type']] : '-'; ?></td>
                <td><?php echo htmlentities($value['out_pay_order']) ? htmlentities($value['out_pay_order']) : '-'; ?></td>
                <td><?php echo FinancialRefundLog::$status_list[$value['status']]; ?></td>
                <td><?php echo $value['apply_username']; ?></td>
                <td><?php echo date('Y-m-d',$value['created_at']); ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['financial/refund-detail','id' => $value['id']]);?>">查看详情</a>
                    <?php if($value['status'] == FinancialRefundLog::STATUS_APPLY):?>
                        <a href="#" onclick="auditConfirm(<?php echo $value['id']?>,1)">审核通过</a>
                        <a href="#" onclick="auditConfirm(<?php echo $value['id']?>,-1)">审核拒绝</a>
                    <?php elseif($value['status'] == FinancialRefundLog::STATUS_REFUNDING):?>
                        <a href="<?php echo Url::toRoute(['financial/add-refund','id' => $value['id']]);?>">编辑</a>
                    <?php endif;?>
                    <?php if ($value['status'] == FinancialRefundLog::STATUS_REFUNDING) {?>
                        <a href="<?php echo Url::toRoute(['financial/set-refund','id' => $value['id']]);?>">置为已退款</a>
                    <?php }?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php
    echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>


<?php $form = ActiveForm::begin(['id' => 'set_remark', 'method' => "post",'action'=>Url::toRoute(['financial/audit-refund'])]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="status">
<input type="hidden" value="" name="remark">
<?php ActiveForm::end(); ?>
<?php $form = ActiveForm::begin(['id' => 'setRefund', 'method' => "post",'action'=>Url::toRoute(['financial/set-refund'])]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="out_pay_order">
<?php ActiveForm::end(); ?>
<script type="text/javascript">
    function auditConfirm(id,status){
        var remark = prompt("请输入审核意见","");
        if (remark == "") {
            alert("未输入审核意见");
            return false;
        }
        var myform = document.forms["set_remark"];
        console.log(remark);
        myform["id"].value = id;
        myform["remark"].value = remark;
        myform["status"].value = status;
        myform.submit();
    }
</script>