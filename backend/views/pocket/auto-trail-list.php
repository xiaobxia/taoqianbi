<?php
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserLoanOrder;

if(isset($tip) && $tip==1){
    $this->shownav('loan', 'menu_ygb_zc_lqd_reject');
    $this->showsubmenu('机审拒绝订单列表', array(
        array('零钱包', Url::toRoute('pocket/pocket-auto-reject-list'), 1),
        array('房租宝', Url::toRoute('house-rent/house-rent-auto-reject-list'),0),
        array('分期购', Url::toRoute('installment-shop/orders-auto-reject-list'),0)
    ));
} else {
    $this->shownav('loan', 'menu_ygb_zc_lqd_auto');
    $this->showsubmenu('待机审订单列表', array(
        array('零钱包', Url::toRoute('pocket/pocket-auto-trail-list'), 1),
        array('房租宝', Url::toRoute('house-rent/house-rent-auto-trail-list'),0),
        array('分期购', Url::toRoute('installment-shop/orders-auto-trail-list'),0)
    ));
}
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin([
    'method' => "get",
    'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'],
]); ?>
订单号：<input type="text" value="<?php echo \yii::$app->request->get('id', ''); ?>" name="id" class="txt" style="width:120px;" />&nbsp;
业务类型：<?php echo Html::dropDownList('sub_order_type', \yii::$app->request->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
机审状态：<?php echo Html::dropDownList('auto_risk_check_status', \yii::$app->request->get('auto_risk_check_status', ''), UserLoanOrder::$auto_check_status_list); ?>&nbsp;
姓名：<input type="text" value="<?php echo \yii::$app->request->get('name', ''); ?>" name="name" class="txt" style="width:120px;" />&nbsp;
手机号：<input type="text" value="<?php echo \yii::$app->request->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;" />&nbsp;
<br />
公司名称：<input type="text" value="<?php echo \yii::$app->request->get('company_name', ''); ?>" name="company_name" class="txt" style="width:120px;" />&nbsp;
申请时间：<input type="text" value="<?php echo \yii::$app->request->get('begintime', ''); ?>" name="begintime"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
    至<input type="text" value="<?php echo \yii::$app->request->get('endtime', ''); ?>"  name="endtime"
        onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
<input type="submit" name="search_submit" value="过滤" class="btn" />&nbsp;&nbsp;&nbsp;

<label><input type="checkbox" name="cache" value="1" <?php if (\yii::$app->request->get('cache')==1): ?> checked <?php endif;?> class="btn" />去除缓存</label>
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>订单号</th>
        <th>用户ID</th>
        <th>姓名</th>
        <th>手机号</th>
        <th>借款金额(元)</th>
        <th>借款项目</th>
        <th>借款期限</th>
        <th>公司名称</th>
        <th>申请来源</th>
        <th>申请时间</th>
        <th>子类型</th>
        <th>自动审核状态</th>
        <th>操作</th>
    </tr>
    <?php foreach ($info as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['user_id']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
            <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
            <th><?php echo isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?></th>
            <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
            <th><?php echo $value['company_name']; ?></th>
            <th><?php echo isset(UserLoanOrder::$from_apps[$value['from_app']])?UserLoanOrder::$from_apps[$value['from_app']]:""; ?></th>
            <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
            <th><?php echo UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.@\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?></th>
            <th><?php echo isset(UserLoanOrder::$auto_check_status_list[$value['auto_risk_check_status']])?UserLoanOrder::$auto_check_status_list[$value['auto_risk_check_status']]:""; ?></th>
            <th>
                <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>
                <a onclick="if(confirmMsg('确定要跳过机审吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['check-status', 'id' => $value['id']]);?>">跳过机审</a>
            </th>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($info)): ?>
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