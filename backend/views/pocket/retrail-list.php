<?php
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\LoanPerson;
$this->shownav('loan', 'menu_ygb_zc_lqd_fs');
$this->showsubmenu('复审列表', array(
    array('零钱包放款', Url::toRoute('pocket/pocket-retrail-list'), 1),
//    array('房租宝放款', Url::toRoute('house-rent/house-rent-retrail-list'),0),
//    array('分期购发货', Url::toRoute('installment-shop/orders-retrail-list'),0)
));
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
身份证：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id_number', ''); ?>" name="id_number" class="txt" style="width:120px;">&nbsp;
公司名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('company_name', ''); ?>" name="company_name" class="txt" style="width:120px;">&nbsp;
用户类型：<?php echo Html::dropDownList('customer_type', Yii::$app->getRequest()->get('customer_type', ''), [0=>'全部',1=>'老用户',-1=>'新用户']); ?>&nbsp;
申请时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    借款金额：<input type="text" value="<?php echo Yii::$app->getRequest()->get('min_money', ''); ?>" name="min_money">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('max_money', ''); ?>"  name="max_money">
    <input type="submit" name="search_submit" value="过滤" class="btn">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>订单号</th>
        <th>用户ID</th>
        <th>姓名</th>
        <th>手机号</th>
        <th>身份证</th>
        <th>用户类型</th>
        <th>借款金额(元)</th>
        <th>借款项目</th>
        <th>借款期限</th>
        <th>公司名称</th>
        <th>申请来源</th>
        <th>申请时间</th>
        <th>子类型</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    <?php foreach ($info as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['user_id']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
            <th><?php echo $value['id_number']; ?></th>
            <th><?php echo isset(LoanPerson::$customer_type[$value['customer_type']])?LoanPerson::$customer_type[$value['customer_type']]:""; ?></th>
            <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
            <th><?php echo isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?></th>
            <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
            <th><?php echo $value['company_name']; ?></th>
            <th><?php echo isset(UserLoanOrder::$from_apps[$value['from_app']])?UserLoanOrder::$from_apps[$value['from_app']]:""; ?></th>
            <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
            <th><?php echo UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.@\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?></th>
            <th><?php echo isset(UserLoanOrder::$status[$value['status']])?UserLoanOrder::$status[$value['status']]:""; ?></th>
            <th>
                <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>
                <a href="<?php echo Url::toRoute(['pocket/pocket-twice-trail', 'id' => $value['id']]);?>">审核</a>
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

