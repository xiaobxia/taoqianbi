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
use common\models\LoanPerson;
use common\models\UserInterestLog;

$this->shownav('user', 'menu_interest_log');
$this->showsubmenu('用户资金流水');
?>

    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:80px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    操作类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), UserInterestLog::$tradeTypes,array('prompt' => '-所有类型-')); ?>&nbsp;
    按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th rowspan="2">ID</th>
            <th rowspan="2">用户ID</th>
            <th rowspan="2">姓名</th>
            <th rowspan="2">手机号</th>
            <th rowspan="2">操作类型</th>
            <th rowspan="2">操作金额</th>
            <th colspan="2" style="text-align:center;">计息前数据</th>
            <th colspan="3" style="text-align:center;">订单信息</th>
            <th colspan="3" style="text-align:center;">额度情况</th>
            <th rowspan="2">银行卡</th>
            <th rowspan="2">操作IP</th>
            <th rowspan="2">操作时间</th>
            <th rowspan="2">备注</th>
        </tr>
        <tr class="header">
            <th>计息前利息</th>
            <th>计息前违约金</th>
            <th>订单ID</th>
            <th>分期总表ID</th>
            <th>分期计划表ID</th>
            <th>锁定额度</th>
            <th>已使用额度</th>
            <th>总额度</th>
        </tr>
        <?php foreach ($loan_collection_list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <th><?php echo $value['name']; ?></th>
                <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
                <th><?php echo empty($value['type']) ? "" : UserInterestLog::$tradeTypes[$value['type']]; ?></th>
                <th><?php echo sprintf("%.2f",$value['operate_money'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['before_interests'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['before_late_fee'] / 100); ?></th>
                <th><?php echo $value['order_id']; ?></th>
                <th><?php echo $value['repayment_id']; ?></th>
                <th><?php echo $value['repayment_period_id']; ?></th>
                <th><?php echo sprintf("%.2f",$value['unabled_money'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['used_money'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['total_money'] / 100); ?></th>
                <td><?php echo $value['card_no']; ?></td>
                <td><?php echo $value['created_ip']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                <td><?php echo $value['remark']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($loan_collection_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    /**
     * 电话显示*，点击后正常显示
     */
    (function initClickPhoneCol() {
        $('.click-phone').each(function () {
            var $item = $(this);
            var phone = $item.attr('data-phoneraw');
            if (phone && phone.length>5) {
                var phoneshow = phone.substr(0, 3) + '****' + phone.substr(phone.length - 2, 2);
                $item.attr('data-phoneshow', phoneshow);
                $item.text(phoneshow);
            } else {
                $item.attr('data-phoneshow', phone);
                $item.text(phone);
            }
        });
        $('.click-phone').one('click', function () {
            $(this).text($(this).attr('data-phoneraw'));
        })
    })();
</script>