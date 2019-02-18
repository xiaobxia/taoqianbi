<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\UserCreditMoneyLog;

$this->shownav('service', 'menu_user_repay_log');
$this->showsubmenu('用户还款流水列表');
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;"/>&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;"/>&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_name', ''); ?>" name="user_name" class="txt" style="width:120px;"/>&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;"/>&nbsp;
银行流水号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_uuid', ''); ?>" name="order_uuid" class="txt" style="width:120px;"/>&nbsp;
流水订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" style="width:120px;"/>&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<input type="submit" name="search_submit" value="搜索" class="btn"/>
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>用户ID</th>
            <th>订单ID</th>
            <th>银行流水号</th>
            <th>流水订单ID</th>
            <th>金额</th>
            <th>状态</th>
            <th>还款方式</th>
            <th>创建时间</th>
            <th>操作人</th>
            <th>备注</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value->id; ?></td>
                <td><?php echo $value->user_id; ?></td>
                <td><?php echo $value->order_id; ?></td>
                <td><?php echo $value->order_uuid; ?></td>
                <td><?php echo $value->pay_order_id; ?></td>
                <td><?php echo sprintf('%.2f', $value->operator_money / 100); ?></td>
                <td>
                <?php
                    echo isset(UserCreditMoneyLog::$status[$value->status]) ? UserCreditMoneyLog::$status[$value->status] : "---";
                ?>
                </td>
                <td>
                <?php
                    echo isset(UserCreditMoneyLog::$payment_type[$value->payment_type]) ? UserCreditMoneyLog::$payment_type[$value->payment_type] : "---";
                ?>
                </td>
                <td><?php echo date('Y-m-d H:i:s',$value->created_at); ?></td>
                <td><?php echo $value->operator_name; ?></td>
                <td><?php echo $value->remark; ?></td>
                <td >
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
<?php if (empty($info)):?>
    <div>抱歉，暂时没有符合条件的记录!</div>
<?php endif;?>
