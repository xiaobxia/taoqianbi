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
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditLog;

$this->shownav('user', 'menu_lqd_interest_check_list');
$this->showsubmenu('零钱贷利息错误核对');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
分期表ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:80px;">&nbsp;
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
状态：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('status', ''), UserLoanOrderRepayment::$status,array('prompt' => '-所有类型-')); ?>&nbsp;
按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>分期表ID</th>
            <th>用户ID</th>
            <th>订单号</th>
            <th>状态</th>
            <th>计算利息时间</th>
        </tr>
        <?php foreach ($loan_collection_list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['order_id']; ?></td>
                <td><?php echo empty($value['status']) ? "--" : UserLoanOrderRepayment::$status[$value['status']]; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['interest_time']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($loan_collection_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>