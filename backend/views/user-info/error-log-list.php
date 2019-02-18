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
use common\models\UserShellInterestErrorLog;
use common\models\UserCreditLog;

$this->shownav('user', 'menu_interest_log_list');
$this->showsubmenu('利息错误日志');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:80px;">&nbsp;
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
分期总表ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('total_period_id', ''); ?>" name="total_period_id" class="txt" style="width:120px;">&nbsp;
分期计划表ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('period_id', ''); ?>" name="period_id" class="txt" style="width:120px;">&nbsp;
状态：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('status', ''), UserShellInterestErrorLog::$status,array('prompt' => '-所有类型-')); ?>&nbsp;
按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>订单号</th>
            <th>分期总表ID</th>
            <th>分期计划表ID</th>
            <th>状态</th>
            <th>操作人</th>
            <th>操作时间</th>
            <th>备注</th>
        </tr>
        <?php foreach ($loan_collection_list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['order_id']; ?></td>
                <td><?php echo $value['repayment_id']; ?></td>
                <td><?php echo $value['repayment_period_id']; ?></td>
                <th><?php echo empty($value['status']) ? "" : UserShellInterestErrorLog::$status[$value['status']]; ?></th>
                <td><?php echo $value['operator_name']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                <td><?php echo $value['remark']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($loan_collection_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>