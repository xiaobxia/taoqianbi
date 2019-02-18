<?php

use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\RidOverdueLog;
use yii\helpers\Html;
use common\helpers\Url;
$this->shownav('financial', 'menu_debit_rid_overdue_log_list');
$this->showsubmenu('减免滞纳金列表');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
业务订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
还款订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('repayment_id', ''); ?>" name="repayment_id" class="txt" style="width:120px;">&nbsp;
业务类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), RidOverdueLog::$type, ['prompt' => '所有类型']); ?>&nbsp;
操作人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('operator_id', ''); ?>" name="operator_id" class="txt" style="width:120px;">&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
<input type="submit" name="search_submit" value="过滤" class="btn"/>
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($data)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>业务订单ID</th>
            <th>还款订单ID</th>
            <th>用户ID</th>
            <th>减免滞纳金额</th>
            <th>类型</th>
            <th>备注</th>
            <th>创建时间</th>
            <th>更新时间</th>
            <th>操作人id</th>
            <th>操作人名称</th>
        </tr>
    <?php foreach ($data as $item): ?>
        <tr class="hover" style="text-align: center;">
            <td><?php echo $item['id']; ?></td>
            <td><?php echo $item['order_id']; ?></td>
            <td><?php echo $item['repayment_id']; ?></td>
            <td><?php echo $item['user_id']; ?></td>
            <td><?php echo sprintf("%0.2f",$item['rid_money']/100); ?></td>
            <td><?php echo RidOverdueLog::$type[$item['type']]; ?></td>
            <td><?php echo $item['remark']; ?></td>
            <td><?php echo date("Y-m-d H:i:s",$item['created_at']); ?></td>
            <td><?php echo date("Y-m-d H:i:s",$item['updated_at']); ?></td>
            <td><?php echo $item['operator_id']; ?></td>
            <td><?php echo $item['operator_name']; ?></td>
        </tr>
    <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>
