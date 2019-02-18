<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\helpers\Html;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\UserAccount;
use common\models\BankConfig;
use common\helpers\StringHelper;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">用户资金流水</th>
    </tr>
    <tr>
        <th colspan="15">
            <?php $form = ActiveForm::begin(['action'=>['','type'=>'waterinfo','id'=>Yii::$app->getRequest()->get('id')],'id' => 'searchform', 'method' => "get",'options' => ['style' => 'margin-bottom:5px;']]); ?>
            操作类型：<?php echo Html::dropDownList('operation_type', Yii::$app->getRequest()->get('operation_type', ''), UserAccount::$tradeTypes, ['prompt' => '所有类型', 'options' => [UserAccount::TRADE_TYPE_STAFF_SALARY => ['disabled' => true]]]); ?>&nbsp;
            按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
            至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
            <input type="submit" name="search_submit" value="过滤" class="btn">
            <?php ActiveForm::end(); ?>
        </th>
    </tr>
    <?php if (!empty($user_account_log_list)): ?>
        <tr class="header">
            <th>用户ID</th>
            <th>用户名</th>
            <th>操作类型</th>
            <th>操作金额</th>
            <th>总金额</th>
            <th>可用余额</th>
            <th>提现中金额</th>
            <th>投资中金额</th>
            <th>待收本金</th>
            <th>待收收益</th>
            <th>口袋宝总金额</th>
            <th width="150">操作时间</th>
        </tr>
        <?php foreach ($user_account_log_list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['user']['username']; ?></td>
                <td><?php echo $value['type']; ?></td>
                <td><?php echo sprintf('%.2f', $value['operate_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['total_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['usable_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['withdrawing_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['investing_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['duein_capital'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['duein_profits'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['kdb_total_money'] / 100); ?></td>
                <td><?php echo date('Y-m-d H:i:s', $value['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><th colspan="15"><div class="no-result">暂无记录</div></th></tr>
    <?php endif;?>
</table>
<?php if (!empty($user_account_log_list)): ?>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php endif; ?>